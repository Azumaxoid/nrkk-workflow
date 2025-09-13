<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Models\Organization;
use App\Models\User;
use App\Models\ApprovalFlow;
use App\Models\Approval;
use App\Services\NotificationService;

class BulkApprovalTest extends Command
{
    protected $signature = 'test:bulk-approval';
    protected $description = 'マルチ組織一括承認テストの実行';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $startTime = microtime(true);
        
        $this->info('🧪 マルチ組織一括承認テスト開始');
        $this->info('=' . str_repeat('=', 50));

        try {
            // Step 1: 3つの組織をランダムに選択
            $allOrganizations = Organization::with(['users' => function($query) {
                $query->where('role', 'approver');
            }])->get();
            
            if ($allOrganizations->count() < 3) {
                $this->error('❌ 利用可能な組織が3つ未満です');
                return;
            }
            
            $selectedOrganizations = $allOrganizations->random(3);
            
            $this->info("\n📊 選択された組織:");
            foreach ($selectedOrganizations as $org) {
                $this->line("   - {$org->name}");
            }

            // Step 2: 各組織から3-5件の申請を作成
            $this->info("\n📋 フェーズ1: 申請作成");
            $this->line('-' . str_repeat('-', 30));
            
            $createdApplications = [];
            $admin = User::where('role', 'admin')->first();
            
            if (!$admin) {
                $this->error('❌ 管理者ユーザーが見つかりません');
                return;
            }

            foreach ($selectedOrganizations as $org) {
                $this->info("\n🏢 {$org->name}");
                
                // 申請者を取得（申請者ロールまたは一般ユーザー）
                $applicants = User::where('organization_id', $org->id)
                    ->whereIn('role', ['applicant', 'user'])
                    ->get();
                
                if ($applicants->isEmpty()) {
                    $this->warn("   ⚠️ この組織には申請者がいません。スキップします。");
                    continue;
                }
                
                $numApplications = rand(3, min(5, $applicants->count()));
                $selectedApplicants = $applicants->random($numApplications);
                
                $this->line("   {$numApplications}件の申請を作成中...");
                
                foreach ($selectedApplicants as $applicant) {
                    $application = $this->createApplication($org, $applicant);
                    if ($application) {
                        $createdApplications[] = $application;
                        $this->line("   ✅ 申請作成: {$application->title}");
                    }
                }
            }

            $totalCreated = count($createdApplications);
            $this->info("\n📊 作成された申請総数: {$totalCreated}");

            if ($totalCreated === 0) {
                $this->warn('申請が作成されませんでした。テスト終了。');
                return;
            }

            // Step 3: 承認フローの開始と承認処理
            $this->info("\n✅ フェーズ2: 一括承認処理");
            $this->line('-' . str_repeat('-', 30));
            
            $approvedCount = 0;
            
            // 各組織の承認者で承認処理
            foreach ($selectedOrganizations as $org) {
                $approvers = User::where('organization_id', $org->id)
                    ->where('role', 'approver')
                    ->get();
                
                if ($approvers->isEmpty()) {
                    $this->warn("   ⚠️ {$org->name}に承認者がいません");
                    continue;
                }

                foreach ($approvers as $approver) {
                    $this->info("\n👨‍💼 承認者: {$approver->name} ({$org->name})");
                    
                    // この承認者の承認待ち案件を取得
                    $pendingApprovals = Approval::where('approver_id', $approver->id)
                        ->where('status', 'pending')
                        ->with('application')
                        ->get();
                    
                    $this->line("   承認待ち件数: {$pendingApprovals->count()}");
                    
                    foreach ($pendingApprovals as $approval) {
                        try {
                            $approval->update([
                                'status' => 'approved',
                                'comment' => 'テスト一括承認 - 自動承認',
                                'approved_at' => now()
                            ]);
                            
                            $approvedCount++;
                            $this->line("   ✅ 承認: {$approval->application->title}");
                            
                            // 次のステップのチェック（簡単実装）
                            $this->checkApplicationStatus($approval->application);
                            
                        } catch (\Exception $e) {
                            $this->error("   ❌ 承認エラー: {$e->getMessage()}");
                        }
                    }
                }
            }

            // 最終承認（管理者による）
            $this->info("\n🔧 最終承認処理（管理者）");
            $finalApprovals = Approval::where('approver_id', $admin->id)
                ->where('status', 'pending')
                ->with('application')
                ->get();
                
            foreach ($finalApprovals as $approval) {
                try {
                    $approval->update([
                        'status' => 'approved',
                        'comment' => '最終承認 - テスト自動承認',
                        'approved_at' => now()
                    ]);
                    
                    $approvedCount++;
                    $this->line("   ✅ 最終承認: {$approval->application->title}");
                    
                    // アプリケーションのステータスを最終完了に
                    $approval->application->update(['status' => 'approved']);
                    
                } catch (\Exception $e) {
                    $this->error("   ❌ 最終承認エラー: {$e->getMessage()}");
                }
            }

            // 結果表示
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            $this->info("\n🎉 テスト完了!");
            $this->info("📊 結果サマリー:");
            $this->line("   - 選択組織数: 3");
            $this->line("   - 作成申請数: {$totalCreated}");
            $this->line("   - 承認処理数: {$approvedCount}");
            $this->line("   - 実行時間: " . round($executionTime, 2) . "秒");
            
            // New Relicにメトリクスを記録
            // 最終状態確認
            $this->info("\n📋 最終状態確認:");
            foreach ($createdApplications as $app) {
                $app->refresh();
                $this->line("   - {$app->title}: {$app->status}");
            }

        } catch (\Exception $e) {
            $this->error("❌ テスト実行エラー: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
        }
    }

    private function createApplication($organization, $applicant)
    {
        try {
            $application = Application::create([
                'title' => "{$organization->name} - {$applicant->name} - テスト申請" . time(),
                'description' => "組織: {$organization->name}\n申請者: {$applicant->name}\n\n自動テスト申請\n予算: " . rand(50, 300) . "万円\n\n一括承認テスト用申請",
                'type' => 'other',
                'priority' => 'medium',
                'applicant_id' => $applicant->id,
                'status' => 'under_review',
                'due_date' => now()->addDays(7),
            ]);

            // 承認フローの設定
            $approvalFlow = ApprovalFlow::where('organization_id', $organization->id)->first();
            if ($approvalFlow) {
                $application->update(['approval_flow_id' => $approvalFlow->id]);
                $approvalFlow->createApprovals($application);
                
                // 通知送信
                $this->notificationService->applicationSubmitted($application);
            }

            return $application;
        } catch (\Exception $e) {
            $this->error("   ❌ 申請作成エラー: {$e->getMessage()}");
            return null;
        }
    }

    private function checkApplicationStatus($application)
    {
        // 全ての承認が完了したかチェック
        $pendingApprovals = $application->approvals()->where('status', 'pending')->count();
        
        if ($pendingApprovals === 0) {
            $application->update(['status' => 'approved']);
            $this->line("     → 申請完全承認: {$application->title}");
        }
    }
}