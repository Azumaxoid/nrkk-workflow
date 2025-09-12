<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ApprovalFlow;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create organizations
        $organizations = [
            [
                'name' => '株式会社テクノロジー革新',
                'code' => 'TECH_INNOVATION',
                'description' => 'AI・IoT・ブロックチェーン技術を駆使した次世代ソリューション開発企業',
            ],
            [
                'name' => '株式会社グリーンエネルギー',
                'code' => 'GREEN_ENERGY',
                'description' => '持続可能な再生可能エネルギーシステムの開発・運営企業',
            ],
            [
                'name' => 'やまと建設株式会社',
                'code' => 'YAMATO_KENSETSU',
                'description' => '地域密着型の総合建設業として70年の歴史を持つ老舗企業',
            ],
            [
                'name' => 'みどり食品工業株式会社',
                'code' => 'MIDORI_FOOD',
                'description' => '伝統的な日本の味を守る食品製造業、全国に展開する老舗メーカー',
            ],
            [
                'name' => 'さくら運輸株式会社',
                'code' => 'SAKURA_UNYU',
                'description' => '昭和30年創業の運輸業界のパイオニア、地方物流ネットワークを支える',
            ],
            [
                'name' => '株式会社フィンテック',
                'code' => 'FINTECH',
                'description' => '金融テクノロジーと暗号資産の革新的サービス提供',
            ],
            [
                'name' => '株式会社エデュテック',
                'code' => 'EDUTECH',
                'description' => 'AI教育プラットフォームと個人最適化学習システム',
            ],
            [
                'name' => '株式会社アグリテック',
                'code' => 'AGRI_TECH',
                'description' => 'スマート農業とサステナブルフード産業の推進',
            ],
            [
                'name' => '株式会社ロボティクス',
                'code' => 'ROBOTICS',
                'description' => '産業用ロボットとAI自動化システムの設計・製造',
            ],
            [
                'name' => '株式会社クラウドインフラ',
                'code' => 'CLOUD_INFRA',
                'description' => 'エンタープライズクラウド基盤とセキュリティサービス',
            ],
        ];

        $createdOrganizations = [];
        foreach ($organizations as $orgData) {
            $createdOrganizations[] = Organization::create($orgData);
        }

        // Create admin user
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@wf.nrkk.technology',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => '管理部',
            'position' => '管理者',
            'organization_id' => $createdOrganizations[0]->id,
            'slack_webhook_url' => 'https://hooks.slack.com/triggers/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',
            'notification_preferences' => ['email', 'slack'],
        ]);

        // Tazuma user
        $tazuma = User::create([
            'name' => '田島和也',
            'email' => 'tazuma@wf.nrkk.technology',
            'password' => Hash::make('password'),
            'role' => 'approver',
            'department' => 'IT部',
            'position' => 'エンジニア',
            'organization_id' => $createdOrganizations[0]->id,
            'slack_webhook_url' => 'https://hooks.slack.com/triggers/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',
            'notification_preferences' => ['email', 'slack'],
        ]);

        // Japanese names for approvers and applicants
        $approverNames = [
            '佐藤太郎', '鈴木花子', '高橋一郎', '田中美紀', '伊藤健太',
            '渡辺由美', '山本直樹', '中村恵子', '小林修', '加藤雅子',
            '吉田博文', '山田明美', '佐々木良太', '松本千春', '井上裕介',
            '木村智子', '林大輔', '斎藤真理', '清水俊夫', '山口綾香'
        ];

        $applicantNames = [
            '青木翔太', '石川由紀', '上田拓也', '江川舞', '大野雄一',
            '岡田沙織', '片山健司', '川口美穂', '木下隆史', '小松恵理',
            '斉藤和明', '酒井梨花', '坂本勝彦', '笹田純子', '島田光男',
            '杉山典子', '関口哲也', '高木真由美', '竹内浩二', '田村香織',
            '千葉正樹', '土屋美加', '寺田慎一', '中川麻衣', '永田雅人',
            '中島美智子', '新田健一', '西村由里', '野村大介', '橋本千恵子',
            '長谷川俊介', '浜田真紀', '原田昌幸', '東真理子', '平野浩司',
            '福田恵美', '藤井秀樹', '星野和子', '前田康雄', '増田美穂子',
            '松田隆之', '丸山典子', '水野浩一', '宮田由香', '森下誠一',
            '安田優子', '山下拓郎', '横田美奈', '吉川雅志', '若林恵理'
        ];

        $notificationTypes = [
            ['email'],
            ['slack'],
            ['email', 'slack']
        ];

        // Create users for each organization
        $allApprovers = [];
        $allApplicants = [];

        foreach ($createdOrganizations as $index => $org) {
            // Create 1-3 approvers per organization
            $approverCount = rand(1, 3);
            $orgApprovers = [];
            
            for ($i = 0; $i < $approverCount; $i++) {
                $name = $approverNames[array_rand($approverNames)];
                // Convert to romaji email format
                $emailBase = 'approver' . $index . '_' . $i;
                $email = $emailBase . '@wf.nrkk.technology';
                $notificationPref = $notificationTypes[array_rand($notificationTypes)];
                
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'approver',
                    'department' => '承認部',
                    'position' => '課長',
                    'organization_id' => $org->id,
                    'notification_preferences' => $notificationPref,
                ];

                if (in_array('slack', $notificationPref)) {
                    $userData['slack_webhook_url'] = 'https://hooks.slack.com/triggers/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';
                }

                $approver = User::create($userData);
                $orgApprovers[] = $approver;
                $allApprovers[] = $approver;
            }

            // Create 10-30 applicants per organization
            $applicantCount = rand(10, 30);
            
            for ($i = 0; $i < $applicantCount; $i++) {
                $name = $applicantNames[array_rand($applicantNames)];
                // Convert to romaji email format
                $emailBase = 'applicant' . $index . '_' . $i;
                $email = $emailBase . '@wf.nrkk.technology';
                $notificationPref = $notificationTypes[array_rand($notificationTypes)];
                
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'applicant',
                    'department' => '一般部',
                    'position' => '一般社員',
                    'organization_id' => $org->id,
                    'notification_preferences' => $notificationPref,
                ];

                if (in_array('slack', $notificationPref)) {
                    $userData['slack_webhook_url'] = 'https://hooks.slack.com/triggers/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';
                }

                $applicant = User::create($userData);
                $allApplicants[] = $applicant;
            }

            // Create approval flow for each organization
            ApprovalFlow::create([
                'name' => $org->name . '用承認フロー',
                'description' => $org->name . '専用の承認ワークフロー',
                'application_type' => 'other',
                'organization_id' => $org->id,
                'step_count' => 2,
                'flow_config' => [
                    0 => [
                        'type' => 'review',
                        'approvers' => array_map(function($approver) { return $approver->id; }, $orgApprovers),
                        'approval_mode' => count($orgApprovers) > 1 ? 'any_one' : 'all',
                    ],
                    1 => [
                        'type' => 'approve',
                        'approvers' => [$admin->id],
                    ],
                ],
                'is_active' => true,
            ]);
        }

        // Create sample application
        if (!empty($allApplicants)) {
            $sampleApp = \App\Models\Application::create([
                'title' => 'テスト申請',
                'description' => 'システムテスト用のサンプル申請です。',
                'type' => 'other',
                'priority' => 'medium',
                'applicant_id' => $allApplicants[0]->id,
                'status' => 'under_review',
                'due_date' => now()->addDays(7),
            ]);

            // Set approval flow
            $generalFlow = \App\Models\ApprovalFlow::where('organization_id', $allApplicants[0]->organization_id)->first();
            if ($generalFlow) {
                $sampleApp->update(['approval_flow_id' => $generalFlow->id]);
                $generalFlow->createApprovals($sampleApp);

                // Send notifications
                $notificationService = new \App\Services\NotificationService();
                $notificationService->applicationSubmitted($sampleApp);
            }
        }
    }
}