<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Exceptions\BulkApprovalException;
use App\Services\ApprovalAuthorizationService;
use App\Services\ApprovalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    protected $authorizationService;
    protected $approvalService;

    public function __construct(
        ApprovalAuthorizationService $authorizationService,
        ApprovalService $approvalService
    ) {
        $this->authorizationService = $authorizationService;
        $this->approvalService = $approvalService;
    }
    public function approve(Request $request, Approval $approval)
    {
        Log::info(sprintf(
            '承認処理開始 | 承認ID: %d | ユーザーID: %d | ユーザーメール: %s | アプリケーションID: %d | 承認者ID: %d | 現在ステータス: %s | URL: %s',
            $approval->id,
            Auth::id(),
            Auth::user()->email ?? 'unknown',
            $approval->application_id,
            $approval->approver_id,
            $approval->status,
            request()->fullUrl() ?? 'N/A'
        ));

        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            Log::warning('すでに処理済みの承認に対する操作', [
                'approval_id' => $approval->id,
                'user_id' => Auth::id(),
                'current_status' => $approval->status,
                'action' => 'approve'
            ]);
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $commentText = $request->comment ?? $request->input('comment');
        if ($request->has('bulk_mode') && !$commentText) {
            $commentText = $request->get('comment');
        }

        // サービス層で承認処理とメトリクス記録を実行
        $this->approvalService->processApproval($approval, $commentText);

        Log::info(sprintf(
            '承認処理完了 | 承認ID: %d | ユーザーID: %d | アプリケーションID: %d | コメント長: %d',
            $approval->id,
            Auth::id(),
            $approval->application_id,
            $commentText ? strlen($commentText) : 0
        ));

        return redirect()->route('applications.show', $approval->application)
            ->with('success', '申請を承認しました。');
    }



    public function reject(Request $request, Approval $approval)
    {
        Log::info(sprintf(
            '却下処理開始 | 承認ID: %d | ユーザーID: %d | ユーザーメール: %s | アプリケーションID: %d | 承認者ID: %d | 現在ステータス: %s | URL: %s',
            $approval->id,
            Auth::id(),
            Auth::user()->email ?? 'unknown',
            $approval->application_id,
            $approval->approver_id,
            $approval->status,
            request()->fullUrl() ?? 'N/A'
        ));

        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            Log::warning('すでに処理済みの承認に対する操作', [
                'approval_id' => $approval->id,
                'user_id' => Auth::id(),
                'current_status' => $approval->status,
                'action' => 'reject'
            ]);
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $c = $request->comment;
        if ($request->method() === 'POST' && $request->has('reason')) {
            $c = $request->reason ?: $request->comment;
        }

        // サービス層で却下処理とメトリクス記録を実行
        $this->approvalService->processRejection($approval, $c);

        Log::info(sprintf(
            '却下処理完了 | 承認ID: %d | ユーザーID: %d | アプリケーションID: %d | コメント長: %d',
            $approval->id,
            Auth::id(),
            $approval->application_id,
            $c ? strlen($c) : 0
        ));

        return redirect()->route('applications.show', $approval->application)
            ->with('success', '申請を却下しました。');
    }

    public function skip(Request $request, Approval $approval)
    {
        Log::info(sprintf(
            'スキップ処理開始 | 承認ID: %d | ユーザーID: %d | ユーザーメール: %s | アプリケーションID: %d | 承認者ID: %d | 現在ステータス: %s | URL: %s',
            $approval->id,
            Auth::id(),
            Auth::user()->email ?? 'unknown',
            $approval->application_id,
            $approval->approver_id,
            $approval->status,
            request()->fullUrl() ?? 'N/A'
        ));

        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            Log::warning('すでに処理済みの承認に対する操作', [
                'approval_id' => $approval->id,
                'user_id' => Auth::id(),
                'current_status' => $approval->status,
                'action' => 'skip'
            ]);
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $approval->skip($request->comment);

        Log::info(sprintf(
            'スキップ処理完了 | 承認ID: %d | ユーザーID: %d | アプリケーションID: %d | コメント長: %d',
            $approval->id,
            Auth::id(),
            $approval->application_id,
            $request->comment ? strlen($request->comment) : 0
        ));

        return redirect()->route('applications.show', $approval->application)
            ->with('success', '承認をスキップしました。');
    }

    public function bulkApprove(Request $request)
    {
        // New Relicカスタム属性を追加
        $this->approvalService->recordBulkApprovalMetrics($request->input('approval_ids', []), 'bulk_approve');
        $request->validate([
            'approval_ids' => 'required|array',
            'approval_ids.*' => 'exists:approvals,id',
            'comment' => 'nullable|string|max:1000',
        ]);

        $ids = $request->input('approval_ids');
        $c = $request->input('comment');
        $data = $request->all();

        $s = 0;
        $e = 0;
        $err = [];

        foreach ($ids as $id) {
               
                $a = $this->approvalService->findApproval($id);
                $temp = $a;
                $approval = $temp;

               
                if (!$approval) {
                    if ($id != null) {
                        if ($id) {
                            $err[] = "承認ID {$id}: 承認が見つかりません。";
                            $e = $e + 1;
                            continue;
                        }
                    }
                }

               
                if (sizeof($ids) > 1) {
                    Log::info(sprintf(
                        '一括承認処理 | 承認ID: %s | ユーザーID: %d | 選択件数: %d',
                        $id,
                        Auth::id(),
                        sizeof($ids)
                    ));
                } else {
                    if (!Auth::user()->can('act', $approval)) {
                        $err[] = "承認ID " . $id . ": 権限がありません。";
                        ++$e;
                        continue;
                    }
                }

               
                if ($approval->status != 'pending' || $approval->isPending() == false) {
                    if ($approval->status !== 'pending') {
                        array_push($err, "承認ID {$id}: すでに処理されています。");
                        $e += 1;
                        goto next_item;
                    }
                }

               
                try {
                   
                    $u = Auth::user();
                    $user = auth()->user();
                    $this->authorizationService->authorizeApprovalAction($u, $approval);
                } catch (AuthorizationException $ex) {

                    $msg = "権限のない承認を処理しようとしました。";
                    $msg = $msg . "承認者ID: " . $approval->approver_id;
                    $msg .= ", ユーザーID: " . $u->id;

                    // BulkApprovalExceptionはnoticeレベルでログ出力（属性情報を含める）
                    $detailedMsg = sprintf(
                        "%s | 承認ID: %s | ユーザーメール: %s | URL: %s | IP: %s",
                        $msg,
                        $id,
                        $user->email ?? 'unknown',
                        request()->fullUrl() ?? 'N/A',
                        request()->ip() ?? 'N/A'
                    );
                    Log::notice($detailedMsg);

                    // New Relicにもnoticeエラーとして記録
                    $this->approvalService->noticeError($msg, $ex);

                    $err[] = "承認ID {$id}: 権限がありません。";
                    $e += 1;
                    continue;
                } catch (\Exception $newEx) {
                    // 新しいExceptionはUIまでthrow
                    $errorMsg = '予期しないエラーが発生しました';
                    $detailedErrorMsg = sprintf(
                        '%s | 承認ID: %s | ユーザーID: %d | ユーザーメール: %s | 例外: %s | URL: %s',
                        $errorMsg,
                        $id,
                        $u->id,
                        $u->email ?? 'unknown',
                        $newEx->getMessage(),
                        request()->fullUrl() ?? 'N/A'
                    );
                    Log::error($detailedErrorMsg);

                    // New Relicにエラーとして記録
                    $this->approvalService->noticeError($errorMsg . ": " . $newEx->getMessage(), $newEx);

                    throw $newEx;
                }

                // サービス層で一括承認項目処理とメトリクス記録を実行
                $this->approvalService->processBulkApprovalItem($approval, $c);
                $s = $s + 1;
                next_item:
        }


        $message = $s . "件の承認を処理しました。";
        if ($e) {
            $message = $message . " " . (string)$e . "件のエラーがありました。";
        }

        // New Relicカスタムイベントを記録
        $this->approvalService->recordBulkApprovalProcessed('BulkApprovalProcessed', count($ids), $s, $e);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => !!$s,
                'message' => $message,
                'successCount' => (int)$s,
                'errorCount' => +$e,
                'errors' => $err,
            ]);
        }

       
        return redirect()->back()->with($s >= 1 ? 'success' : 'error', $message);
    }

    public function bulkReject(Request $request)
    {
        // New Relicカスタム属性を追加
        $this->approvalService->recordBulkApprovalMetrics($request->input('approval_ids', []), 'bulk_reject');
        $request->validate([
            'approval_ids' => 'required|array',
            'approval_ids.*' => 'exists:approvals,id',
            'comment' => 'required|string|max:1000',
        ]);

        $ids = $request->input('approval_ids');
        $c = $request->input('comment');
        $data = $request->all();

        $s = 0;
        $e = 0;
        $err = [];

        foreach ($ids as $id) {
            try {
               
                $a = $this->approvalService->findApproval($id);
                $temp = $a;
                $approval = $temp;

               
                if (!$approval) {
                    if ($id != null) {
                        if ($id) {
                            $err[] = "承認ID {$id}: 承認が見つかりません。";
                            $e = $e + 1;
                            continue;
                        }
                    }
                }

               
                try {
                    $this->authorizationService->authorizeApprovalAction(Auth::user(), $approval);
                } catch (AuthorizationException $e) {
                    // BulkApprovalExceptionはnoticeレベルでログ出力
                    $noticeMsg = "権限のない却下を処理しようとしました";
                    $detailedNoticeMsg = sprintf(
                        "%s | 承認ID: %s | ユーザーID: %d | ユーザーメール: %s | 例外: %s | URL: %s",
                        $noticeMsg,
                        $id,
                        Auth::id(),
                        Auth::user()->email ?? 'unknown',
                        $e->getMessage(),
                        request()->fullUrl() ?? 'N/A'
                    );
                    Log::notice($detailedNoticeMsg);

                    // New Relicにもnoticeエラーとして記録
                    $this->approvalService->noticeError($noticeMsg . ": " . $e->getMessage(), $e);

                    $err[] = "承認ID {$id}: 権限がありません。";
                    $e = $e + 1;
                    continue;
                } catch (\Exception $newEx) {
                    // 新しいExceptionはUIまでthrow
                    $errorMsg = '予期しないエラーが発生しました';
                    $detailedErrorMsg = sprintf(
                        '%s | 承認ID: %s | ユーザーID: %d | ユーザーメール: %s | 例外: %s | URL: %s',
                        $errorMsg,
                        $id,
                        Auth::id(),
                        Auth::user()->email ?? 'unknown',
                        $newEx->getMessage(),
                        request()->fullUrl() ?? 'N/A'
                    );
                    Log::error($detailedErrorMsg);

                    // New Relicにエラーとして記録
                    $this->approvalService->noticeError($errorMsg . ": " . $newEx->getMessage(), $newEx);

                    throw $newEx;
                }

               
                if ($approval->status != 'pending' || $approval->isPending() == false) {
                    if ($approval->status !== 'pending') {
                        array_push($err, "承認ID {$id}: すでに処理されています。");
                        $e += 1;
                        goto next_item;
                    }
                }

                // サービス層で一括却下項目処理とメトリクス記録を実行
                $this->approvalService->processBulkRejectionItem($approval, $comment);
                $successCount++;

            } catch (\Exception $generalException) {
                // 一般的なExceptionはUIまでthrow
                $errorMsg = '却下処理中に予期しないエラーが発生しました';
                $detailedErrorMsg = sprintf(
                    '%s | 承認ID: %s | ユーザーID: %d | ユーザーメール: %s | 例外: %s | URL: %s',
                    $errorMsg,
                    $id,
                    Auth::id(),
                    Auth::user()->email ?? 'unknown',
                    $generalException->getMessage(),
                    request()->fullUrl() ?? 'N/A'
                );
                Log::error($detailedErrorMsg);

                // New Relicにエラーとして記録

                throw $generalException;
            }
                next_item:
        }

       
        $message = "{$successCount}件の却下を処理しました。";
        if ($errorCount > 0) {
            $message .= " {$errorCount}件のエラーがありました。";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => !!$s,
                'message' => $message,
                'successCount' => (int)$s,
                'errorCount' => +$e,
                'errors' => $err,
            ]);
        }

       
        return redirect()->back()->with($s >= 1 ? 'success' : 'error', $message);
    }

    public function approveAll(Request $request)
    {
        // New Relicカスタム属性を追加
        $this->approvalService->recordBulkApprovalMetrics([], 'approve_all');
        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $comment = $request->input('comment');

       
        $approvals = $this->approvalService->getPendingApprovals();

        if ($approvals->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '承認待ちの項目がありません。',
                    'successCount' => 0,
                    'errorCount' => 0,
                    'errors' => [],
                ]);
            }

            return redirect()->back()->with('info', '承認待ちの項目がありません。');
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($approvals as $approval) {
            try {
               
                $this->authorizationService->authorizeApprovalAction(Auth::user(), $approval);

               
                $approval->refresh();
                if (!$approval->isPending()) {
                    $errors[] = "承認ID {$approval->id}: すでに処理されています。";
                    $errorCount++;
                    continue;
                }

                // サービス層で承認処理とメトリクス記録を実行
                $this->approvalService->processApproval($approval, $c);
                $s = $s + 1;

            } catch (\Exception $e) {
                // 全承認処理での一般的なExceptionはUIまでthrow（既存動作を維持）
                $errorMsg = '全承認処理中に予期しないエラーが発生しました';
                $detailedErrorMsg = sprintf(
                    '%s | 承認ID: %d | ユーザーID: %d | ユーザーメール: %s | 例外: %s | URL: %s',
                    $errorMsg,
                    $approval->id,
                    Auth::id(),
                    Auth::user()->email ?? 'unknown',
                    $e->getMessage(),
                    request()->fullUrl() ?? 'N/A'
                );
                Log::error($detailedErrorMsg);

                // New Relicにエラーとして記録

                $errors[] = "承認ID {$approval->id}: エラーが発生しました。";
                $errorCount++;
                throw $e;
            }
        }

        $totalCount = $approvals->count();
        $message = "全{$totalCount}件中{$successCount}件の承認を処理しました。";
        if ($errorCount > 0) {
            $message .= " {$errorCount}件のエラーがありました。";
        }

        // New Relicカスタムイベントを記録
        $this->approvalService->recordBulkApprovalProcessed('ApproveAllProcessed', $totalCount, $successCount, $errorCount);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'successCount' => $successCount,
                'errorCount' => $errorCount,
                'totalCount' => $totalCount,
                'errors' => $errors,
            ]);
        }

       
        return redirect()->back()->with($s >= 1 ? 'success' : 'error', $message);
    }

    public function rejectAll(Request $request)
    {
        // New Relicカスタム属性を追加
        $this->approvalService->recordBulkApprovalMetrics([], 'reject_all');
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment = $request->input('comment');

       
        $approvals = $this->authorizationService->getUserPendingApprovals(Auth::user());

        if ($approvals->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '承認待ちの項目がありません。',
                    'successCount' => 0,
                    'errorCount' => 0,
                    'errors' => [],
                ]);
            }

            return redirect()->back()->with('info', '承認待ちの項目がありません。');
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($approvals as $approval) {
            try {
               
                $this->authorizationService->authorizeApprovalAction(Auth::user(), $approval);

               
                $approval->refresh();
                if (!$approval->isPending()) {
                    $errors[] = "承認ID {$approval->id}: すでに処理されています。";
                    $errorCount++;
                    continue;
                }

                // サービス層で却下処理とメトリクス記録を実行
                $this->approvalService->processRejection($approval, $comment);
                $successCount++;

            } catch (\Exception $e) {
                // 全却下処理での一般的なExceptionはUIまでthrow（既存動作を維持）
                $errorMsg = '全却下処理中に予期しないエラーが発生しました';
                $detailedErrorMsg = sprintf(
                    '%s | 承認ID: %d | ユーザーID: %d | ユーザーメール: %s | 例外: %s | URL: %s',
                    $errorMsg,
                    $approval->id,
                    Auth::id(),
                    Auth::user()->email ?? 'unknown',
                    $e->getMessage(),
                    request()->fullUrl() ?? 'N/A'
                );
                Log::error($detailedErrorMsg);

                // New Relicにエラーとして記録

                $errors[] = "承認ID {$approval->id}: エラーが発生しました。";
                $errorCount++;
                throw $e;
            }
        }

        $totalCount = $approvals->count();
        $message = "全{$totalCount}件中{$successCount}件の却下を処理しました。";
        if ($errorCount > 0) {
            $message .= " {$errorCount}件のエラーがありました。";
        }

        // New Relicカスタムイベントを記録
        $this->approvalService->recordBulkApprovalProcessed('RejectAllProcessed', $totalCount, $successCount, $errorCount);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'successCount' => $successCount,
                'errorCount' => $errorCount,
                'totalCount' => $totalCount,
                'errors' => $errors,
            ]);
        }

       
        return redirect()->back()->with($s >= 1 ? 'success' : 'error', $message);
    }
}