<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\User;
use App\Services\NewRelicService;
use Illuminate\Auth\Access\AuthorizationException;

class ApprovalAuthorizationService
{
    protected $newRelicService;

    public function __construct()
    {
        $this->newRelicService = app(NewRelicService::class);
    }

    /**
     * 単一の承認に対する認可チェック
     */
    public function authorizeApprovalAction(User $user, Approval $approval): void
    {
        // New Relicメトリクス記録
        $this->recordAuthorizationAttempt('single', $user, $approval);

        if (!$this->canActOnApproval($user, $approval)) {
            $errorMessage = "承認ID {$approval->id} に対する権限がありません。承認者ID: {$approval->approver_id}, ユーザーID: {$user->id}";

            $exception = new AuthorizationException($errorMessage);

            // New Relicにエラーを記録（例外インスタンスを渡す）
            $this->newRelicService->noticeError($errorMessage, $exception);

            // 認可失敗のメトリクス記録
            $this->recordAuthorizationFailure('single', $user, $approval);

            throw $exception;
        }

        // 認可成功のメトリクス記録
        $this->recordAuthorizationSuccess('single', $user, $approval);
    }

    /**
     * 複数の承認に対する一括認可チェック
     */
    public function authorizeBulkApprovalAction(User $user, array $approvalIds): array
    {
        // New Relicメトリクス記録
        $this->recordBulkAuthorizationAttempt($user, $approvalIds);

        $unauthorizedApprovals = [];

        foreach ($approvalIds as $approvalId) {
            $approval = Approval::find($approvalId);

            if (!$approval) {
                $unauthorizedApprovals[] = [
                    'approval_id' => $approvalId,
                    'reason' => '承認が見つかりません'
                ];
                continue;
            }

            if (!$this->canActOnApproval($user, $approval)) {
                $unauthorizedApprovals[] = [
                    'approval_id' => $approvalId,
                    'reason' => "権限がありません。承認者ID: {$approval->approver_id}, ユーザーID: {$user->id}"
                ];
            }
        }

        if (!empty($unauthorizedApprovals)) {
            $errorMessage = "一括承認で権限のない承認が含まれています。\n";
            foreach ($unauthorizedApprovals as $error) {
                $errorMessage .= "承認ID {$error['approval_id']}: {$error['reason']}\n";
            }
            $exception = new AuthorizationException($errorMessage);

            // New Relicにエラーを記録（例外インスタンスを渡す）
            $this->newRelicService->noticeError($errorMessage, $exception);

            // 一括認可失敗のメトリクス記録
            $this->recordBulkAuthorizationFailure($user, $approvalIds, $unauthorizedApprovals);

            throw $exception;
        }

        // 一括認可成功のメトリクス記録
        $this->recordBulkAuthorizationSuccess($user, $approvalIds);

        return [];
    }

    /**
     * ユーザーが指定された承認に対してアクションを実行できるかチェック
     */
    public function canActOnApproval(User $user, Approval $approval): bool
    {
        // 承認者IDがユーザーIDと一致するかチェック
        if ($approval->approver_id !== $user->id) {
            return false;
        }

        // 承認がpending状態かチェック
        if (!$approval->isPending()) {
            return false;
        }

        return true;
    }

    /**
     * ユーザーの全承認待ちを取得（認可チェック付き）
     */
    public function getUserPendingApprovals(User $user)
    {
        return Approval::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * 一括承認前の事前チェック
     */
    public function validateBulkApprovalRequest(User $user, array $approvalIds): array
    {
        $validApprovals = [];
        $errors = [];

        foreach ($approvalIds as $approvalId) {
            try {
                $approval = Approval::find($approvalId);

                if (!$approval) {
                    $errors[] = "承認ID {$approvalId}: 承認が見つかりません";
                    continue;
                }

                $this->authorizeApprovalAction($user, $approval);
                $validApprovals[] = $approval;

            } catch (AuthorizationException $e) {
                $errors[] = "承認ID {$approvalId}: " . $e->getMessage();
            }
        }

        return [
            'valid_approvals' => $validApprovals,
            'errors' => $errors
        ];
    }

    /**
     * 単一認可試行のメトリクスをNew Relicに記録
     */
    protected function recordAuthorizationAttempt(string $type, User $user, Approval $approval): void
    {
        $this->newRelicService->addCustomParameter('authorization.action', 'attempt');
        $this->newRelicService->addCustomParameter('authorization.type', $type);
        $this->newRelicService->addCustomParameter('authorization.user_id', $user->id);
        $this->newRelicService->addCustomParameter('authorization.approval_id', $approval->id);
        $this->newRelicService->addCustomParameter('authorization.approval_status', $approval->status);
        $this->newRelicService->addCustomParameter('authorization.approver_id', $approval->approver_id);
        $this->newRelicService->addCustomParameter('authorization.user_organization_id', $user->organization_id ?? 'none');
    }

    /**
     * 単一認可成功のメトリクスをNew Relicに記録
     */
    protected function recordAuthorizationSuccess(string $type, User $user, Approval $approval): void
    {
        $this->newRelicService->addCustomParameter('authorization.result', 'success');
        $this->newRelicService->recordMetric('AuthorizationSuccess', 1);

        $this->newRelicService->recordCustomEvent('AuthorizationSuccess', [
            'type' => $type,
            'user_id' => $user->id,
            'approval_id' => $approval->id,
            'approval_status' => $approval->status,
            'organization_id' => $user->organization_id ?? null
        ]);
    }

    /**
     * 単一認可失敗のメトリクスをNew Relicに記録
     */
    protected function recordAuthorizationFailure(string $type, User $user, Approval $approval): void
    {
        $this->newRelicService->addCustomParameter('authorization.result', 'failure');
        $this->newRelicService->addCustomParameter('authorization.failure_reason', 'unauthorized');
        $this->newRelicService->recordMetric('AuthorizationFailure', 1);

        $this->newRelicService->recordCustomEvent('AuthorizationFailure', [
            'type' => $type,
            'user_id' => $user->id,
            'approval_id' => $approval->id,
            'approval_status' => $approval->status,
            'approver_id' => $approval->approver_id,
            'organization_id' => $user->organization_id ?? null,
            'reason' => 'unauthorized'
        ]);
    }

    /**
     * 一括認可試行のメトリクスをNew Relicに記録
     */
    protected function recordBulkAuthorizationAttempt(User $user, array $approvalIds): void
    {
        $this->newRelicService->addCustomParameter('authorization.action', 'bulk_attempt');
        $this->newRelicService->addCustomParameter('authorization.user_id', $user->id);
        $this->newRelicService->addCustomParameter('authorization.approval_count', count($approvalIds));
        $this->newRelicService->addCustomParameter('authorization.user_organization_id', $user->organization_id ?? 'none');
        $this->newRelicService->recordMetric('BulkAuthorizationAttempt', count($approvalIds));
    }

    /**
     * 一括認可成功のメトリクスをNew Relicに記録
     */
    protected function recordBulkAuthorizationSuccess(User $user, array $approvalIds): void
    {
        $this->newRelicService->addCustomParameter('authorization.result', 'success');
        $this->newRelicService->addCustomParameter('authorization.success_count', count($approvalIds));
        $this->newRelicService->recordMetric('BulkAuthorizationSuccess', count($approvalIds));

        $this->newRelicService->recordCustomEvent('BulkAuthorizationSuccess', [
            'user_id' => $user->id,
            'approval_count' => count($approvalIds),
            'organization_id' => $user->organization_id ?? null
        ]);
    }

    /**
     * 一括認可失敗のメトリクスをNew Relicに記録
     */
    protected function recordBulkAuthorizationFailure(User $user, array $approvalIds, array $unauthorizedApprovals): void
    {
        $this->newRelicService->addCustomParameter('authorization.result', 'failure');
        $this->newRelicService->addCustomParameter('authorization.total_count', count($approvalIds));
        $this->newRelicService->addCustomParameter('authorization.unauthorized_count', count($unauthorizedApprovals));
        $this->newRelicService->addCustomParameter('authorization.authorized_count', count($approvalIds) - count($unauthorizedApprovals));
        $this->newRelicService->recordMetric('BulkAuthorizationFailure', count($unauthorizedApprovals));

        $this->newRelicService->recordCustomEvent('BulkAuthorizationFailure', [
            'user_id' => $user->id,
            'total_count' => count($approvalIds),
            'unauthorized_count' => count($unauthorizedApprovals),
            'organization_id' => $user->organization_id ?? null
        ]);
    }
}