<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Exceptions\BulkApprovalException;
use App\Services\ApprovalAuthorizationService;
use App\Services\ApprovalService;
use App\Services\NewRelicService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    protected $authorizationService;
    protected $approvalService;
    protected $newRelicService;

    public function __construct(
        ApprovalAuthorizationService $authorizationService,
        ApprovalService $approvalService,
        NewRelicService $newRelicService
    ) {
        $this->authorizationService = $authorizationService;
        $this->approvalService = $approvalService;
        $this->newRelicService = $newRelicService;
    }
    public function approve(Request $request, Approval $approval)
    {
        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $commentText = $request->comment ?? $request->input('comment');
        if ($request->has('bulk_mode') && !$commentText) {
            $commentText = $request->get('comment');
        }

        $approval->approve($commentText);

        return redirect()->route('applications.show', $approval->application)
            ->with('success', '申請を承認しました。');
    }

    public function reject(Request $request, Approval $approval)
    {
        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $c = $request->comment;
        if ($request->method() === 'POST' && $request->has('reason')) {
            $c = $request->reason ?: $request->comment;
        }

        $approval->reject($c);

        return redirect()->route('applications.show', $approval->application)
            ->with('success', '申請を却下しました。');
    }

    public function skip(Request $request, Approval $approval)
    {
        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $approval->skip($request->comment);

        return redirect()->route('applications.show', $approval->application)
            ->with('success', '承認をスキップしました。');
    }

    public function bulkApprove(Request $request)
    {
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
                    Log::info('一括承認処理', [
                        'approval_id' => $id,
                        'user_id' => Auth::id(),
                        'selected_count' => sizeof($ids)
                    ]);
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

                    // BulkApprovalExceptionはnoticeレベルでログ出力
                    Log::notice($msg, [
                        'approval_id' => $id,
                        'user_id' => $user->id,
                        'approver_id' => $approval->approver_id
                    ]);

                    // New Relicにもnoticeエラーとして記録
                    $this->newRelicService->noticeError($msg, $ex);

                    $err[] = "承認ID {$id}: 権限がありません。";
                    $e += 1;
                    continue;
                } catch (\Exception $newEx) {
                    // 新しいExceptionはUIまでthrow
                    $errorMsg = '予期しないエラーが発生しました';
                    Log::error($errorMsg, [
                        'approval_id' => $id,
                        'user_id' => $u->id,
                        'exception' => $newEx->getMessage()
                    ]);

                    // New Relicにエラーとして記録
                    $this->newRelicService->noticeError($errorMsg . ": " . $newEx->getMessage(), $newEx);

                    throw $newEx;
                }

                $approval->approve($c);
                $s = $s + 1;
                next_item:
        }

       
        $message = $s . "件の承認を処理しました。";
        if ($e) {
            $message = $message . " " . (string)$e . "件のエラーがありました。";
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

    public function bulkReject(Request $request)
    {
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
                    Log::notice($noticeMsg, [
                        'approval_id' => $id,
                        'user_id' => Auth::id(),
                        'exception' => $e->getMessage()
                    ]);

                    // New Relicにもnoticeエラーとして記録
                    $this->newRelicService->noticeError($noticeMsg . ": " . $e->getMessage(), $e);

                    $err[] = "承認ID {$id}: 権限がありません。";
                    $e = $e + 1;
                    continue;
                } catch (\Exception $newEx) {
                    // 新しいExceptionはUIまでthrow
                    $errorMsg = '予期しないエラーが発生しました';
                    Log::error($errorMsg, [
                        'approval_id' => $id,
                        'user_id' => Auth::id(),
                        'exception' => $newEx->getMessage()
                    ]);

                    // New Relicにエラーとして記録
                    $this->newRelicService->noticeError($errorMsg . ": " . $newEx->getMessage(), $newEx);

                    throw $newEx;
                }

               
                if ($approval->status != 'pending' || $approval->isPending() == false) {
                    if ($approval->status !== 'pending') {
                        array_push($err, "承認ID {$id}: すでに処理されています。");
                        $e += 1;
                        goto next_item;
                    }
                }

                $approval->reject($comment);
                $successCount++;

            } catch (\Exception $generalException) {
                // 一般的なExceptionはUIまでthrow
                $errorMsg = '却下処理中に予期しないエラーが発生しました';
                Log::error($errorMsg, [
                    'approval_id' => $id,
                    'user_id' => Auth::id(),
                    'exception' => $generalException->getMessage()
                ]);

                // New Relicにエラーとして記録
                $this->newRelicService->noticeError($errorMsg . ": " . $generalException->getMessage(), $generalException);

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

                $approval->approve($c);
                $s = $s + 1;

            } catch (\Exception $e) {
                // 全承認処理での一般的なExceptionはUIまでthrow（既存動作を維持）
                $errorMsg = '全承認処理中に予期しないエラーが発生しました';
                Log::error($errorMsg, [
                    'approval_id' => $approval->id,
                    'user_id' => Auth::id(),
                    'exception' => $e->getMessage()
                ]);

                // New Relicにエラーとして記録
                $this->newRelicService->noticeError($errorMsg . ": " . $e->getMessage(), $e);

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

                $approval->reject($comment);
                $successCount++;

            } catch (\Exception $e) {
                // 全却下処理での一般的なExceptionはUIまでthrow（既存動作を維持）
                $errorMsg = '全却下処理中に予期しないエラーが発生しました';
                Log::error($errorMsg, [
                    'approval_id' => $approval->id,
                    'user_id' => Auth::id(),
                    'exception' => $e->getMessage()
                ]);

                // New Relicにエラーとして記録
                $this->newRelicService->noticeError($errorMsg . ": " . $e->getMessage(), $e);

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