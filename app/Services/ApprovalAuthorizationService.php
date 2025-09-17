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
        if (!$this->canActOnApproval($user, $approval)) {
            $errorMessage = "承認ID {$approval->id} に対する権限がありません。承認者ID: {$approval->approver_id}, ユーザーID: {$user->id}";

            $exception = new AuthorizationException($errorMessage);

            // New Relicにエラーを記録（例外インスタンスを渡す）
            $this->newRelicService->noticeError($errorMessage, $exception);

            throw $exception;
        }
    }

    /**
     * 複数の承認に対する一括認可チェック
     */
    public function authorizeBulkApprovalAction(User $user, array $approvalIds): array
    {
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

            throw $exception;
        }

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
}