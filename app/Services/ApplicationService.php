<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApprovalFlow;
use App\Models\User;
use App\Services\NewRelicService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApplicationService
{
    protected $newRelicService;
    protected $notificationService;

    public function __construct(
        NewRelicService $newRelicService,
        NotificationService $notificationService
    ) {
        $this->newRelicService = $newRelicService;
        $this->notificationService = $notificationService;
    }

    /**
     * アプリケーション一覧の取得とメトリクス記録
     */
    public function recordIndexMetrics(Request $request, $applications)
    {
        $this->newRelicService->addCustomParameter('application.action', 'index');
        $this->newRelicService->addCustomParameter('application.filters.status', $request->input('status', 'all'));
        $this->newRelicService->addCustomParameter('application.filters.type', $request->input('type', 'all'));
        $this->newRelicService->addCustomParameter('application.list.total_count', $applications->total());
        $this->newRelicService->addCustomParameter('application.list.page_count', $applications->count());
        $this->newRelicService->recordMetric('ApplicationListCount', $applications->total());
    }

    /**
     * アプリケーション作成のメトリクス記録
     */
    public function recordCreateMetrics(Request $request)
    {
        $this->newRelicService->addCustomParameter('application.action', 'create');
        $this->newRelicService->addCustomParameter('application.type', $request->input('type'));
        $this->newRelicService->addCustomParameter('application.priority', $request->input('priority'));
        if ($request->input('amount')) {
            $this->newRelicService->addCustomParameter('application.amount', $request->input('amount'));
        }
    }

    /**
     * アプリケーション作成完了のイベント記録
     */
    public function recordApplicationCreated(Application $application)
    {
        $this->newRelicService->addCustomParameter('application.created.id', $application->id);
        $this->newRelicService->addCustomParameter('application.created.type', $application->type);
        $this->newRelicService->recordCustomEvent('ApplicationCreated', [
            'application_id' => $application->id,
            'type' => $application->type,
            'priority' => $application->priority,
            'user_id' => Auth::id(),
            'organization_id' => Auth::user()->organization_id ?? null
        ]);
    }

    /**
     * アプリケーション詳細表示のメトリクス記録
     */
    public function recordShowMetrics(Application $application)
    {
        $this->newRelicService->addCustomParameter('application.action', 'view');
        $this->newRelicService->addCustomParameter('application.id', $application->id);
        $this->newRelicService->addCustomParameter('application.status', $application->status);
        $this->newRelicService->addCustomParameter('application.type', $application->type);
        $this->newRelicService->addCustomParameter('application.priority', $application->priority);
    }

    /**
     * アプリケーション更新のメトリクス記録
     */
    public function recordUpdateMetrics(Application $application)
    {
        $this->newRelicService->addCustomParameter('application.action', 'update');
        $this->newRelicService->addCustomParameter('application.id', $application->id);
        $this->newRelicService->addCustomParameter('application.current_status', $application->status);
    }

    /**
     * アプリケーション更新完了のイベント記録
     */
    public function recordApplicationUpdated(Application $application, array $validated)
    {
        $this->newRelicService->recordCustomEvent('ApplicationUpdated', [
            'application_id' => $application->id,
            'updated_fields' => implode(',', array_keys($validated)),
            'user_id' => Auth::id()
        ]);
    }

    /**
     * アプリケーション提出のメトリクス記録
     */
    public function recordSubmitMetrics(Application $application)
    {
        $this->newRelicService->addCustomParameter('application.action', 'submit');
        $this->newRelicService->addCustomParameter('application.id', $application->id);
        $this->newRelicService->addCustomParameter('application.type', $application->type);
    }

    /**
     * アプリケーション提出完了のイベント記録
     */
    public function recordApplicationSubmitted(Application $application, ApprovalFlow $flow)
    {
        $this->newRelicService->recordCustomEvent('ApplicationSubmitted', [
            'application_id' => $application->id,
            'approval_flow_id' => $flow->id,
            'type' => $application->type,
            'priority' => $application->priority,
            'user_id' => Auth::id(),
            'organization_id' => Auth::user()->organization_id ?? null
        ]);
    }

    /**
     * 承認一覧のメトリクス記録
     */
    public function recordMyApprovalsMetrics($allApprovals, $authorizedApprovals, $paginatedApprovals, $queryCount)
    {
        $this->newRelicService->addCustomParameter('application.action', 'my_approvals');
        $this->newRelicService->addCustomParameter('approvals.total_count', $allApprovals->count());
        $this->newRelicService->addCustomParameter('approvals.authorized_count', $authorizedApprovals->count());
        $this->newRelicService->addCustomParameter('approvals.displayed_count', $paginatedApprovals->count());
        $this->newRelicService->addCustomParameter('database.query_count', $queryCount);
        $this->newRelicService->recordMetric('ApprovalsQueryCount', $queryCount);
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

        $this->recordApplicationCreated($application);

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

        $this->recordApplicationSubmitted($application, $flow);

        return $flow;
    }
}