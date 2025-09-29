<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApprovalFlow;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApplicationService
{
    protected $notificationService;

    public function __construct(
        NotificationService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }


    /**
     * 申請を作成して承認フローを設定
     */
    public function createApplication(array $validated): Application
    {
        $validated['applicant_id'] = Auth::id();
        $application = Application::create($validated);

        Log::info('アプリケーション作成完了', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'type' => $application->type,
            'title' => $application->title
        ]);


        // 承認フローを設定
        $user = Auth::user();
        if ($user && $user->organization_id) {
            $approvalFlow = ApprovalFlow::where('organization_id', $user->organization_id)
                ->where('application_type', $validated['type'])
                ->where('is_active', true)
                ->first();

            if (!$approvalFlow) {
                // デフォルトの承認フロー（otherタイプ）を使用
                $approvalFlow = ApprovalFlow::where('organization_id', $user->organization_id)
                    ->where('application_type', 'other')
                    ->where('is_active', true)
                    ->first();
            }

            if ($approvalFlow) {
                $application->update(['approval_flow_id' => $approvalFlow->id, 'status' => 'under_review']);
                $approvalFlow->createApprovals($application);

                Log::info('承認フロー設定完了', [
                    'application_id' => $application->id,
                    'approval_flow_id' => $approvalFlow->id,
                    'user_id' => Auth::id()
                ]);

                // 通知送信
                $this->notificationService->applicationSubmitted($application);
            } else {
                Log::warning('承認フローが見つからない', [
                    'application_id' => $application->id,
                    'user_id' => Auth::id(),
                    'organization_id' => $user->organization_id,
                    'application_type' => $validated['type']
                ]);
            }
        }

        Log::info('アプリケーション作成処理完了', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'final_status' => $application->status
        ]);

        return $application;
    }

    /**
     * 申請を提出
     */
    public function submitApplication(Application $application): ApprovalFlow
    {
        $flow = ApprovalFlow::findBestMatch($application);
        if (!$flow) {
            throw new \Exception('承認フローが見つかりません。管理者にお問い合わせください。');
        }

        $application->submit();
        $application->update([
            'status' => 'under_review',
            'approval_flow_id' => $flow->id
        ]);
        $flow->createApprovals($application);

        Log::info('アプリケーション提出完了', [
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'approval_flow_id' => $flow->id,
            'new_status' => 'under_review'
        ]);


        return $flow;
    }
}