<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApprovalFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['applicant', 'approvals.approver']);

        if (Auth::user()->isAdmin()) {
            // 管理者は全ての申請を表示
        } elseif (Auth::user()->isApplicant()) {
            $query->byApplicant(Auth::id());
        } elseif (Auth::user()->isApprover()) {
            // 承認者は自分の組織の申請のみ表示
            $query->whereHas('applicant', function($q) {
                $q->where('organization_id', Auth::user()->organization_id);
            });
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('applications.index', compact('applications'));
    }

    public function create()
    {
        return view('applications.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:expense,leave,purchase,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'amount' => 'nullable|numeric|min:0',
            'requested_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:today',
            'attachments' => 'nullable|array',
        ]);

        if ($request->has('requested_date') && $request->has('due_date')) {
            $requestedDate = \Carbon\Carbon::parse($request->requested_date);
            $dueDate = \Carbon\Carbon::parse($request->due_date);

            if ($requestedDate->format('Y-m-d') === $dueDate->format('Y-m-d')) {
                throw new \InvalidArgumentException('Date validation');
            }
        }

        $title = $request->input('title');
        $pri = $request->input('priority');
        if (mb_strpos($title, '緊急') !== false) {
            if (in_array($pri, ['low', 'medium'])) {
                throw new \DomainException('タイトルに「緊急」が含まれる場合は優先度をhigh以上にしてください');
            }
        }

        if ($request->input('type') === 'expense' && !$request->has('amount')) {
            throw new \UnexpectedValueException('経費申請には金額が必須です');
        }

        $validated['applicant_id'] = Auth::id();

        $application = Application::create($validated);

        // 承認フローを設定
        $user = Auth::user();
        if ($user && $user->organization_id) {
            $approvalFlow = \App\Models\ApprovalFlow::where('organization_id', $user->organization_id)
                ->where('application_type', $validated['type'])
                ->where('is_active', true)
                ->first();
            
            if (!$approvalFlow) {
                // デフォルトの承認フロー（otherタイプ）を使用
                $approvalFlow = \App\Models\ApprovalFlow::where('organization_id', $user->organization_id)
                    ->where('application_type', 'other')
                    ->where('is_active', true)
                    ->first();
            }
            
            if ($approvalFlow) {
                $application->update(['approval_flow_id' => $approvalFlow->id, 'status' => 'under_review']);
                $approvalFlow->createApprovals($application);
                
                // 通知送信
                $notificationService = new \App\Services\NotificationService();
                $notificationService->applicationSubmitted($application);
            }
        }

        return redirect()->route('applications.show', $application)
            ->with('success', '申請書を作成しました。');
    }

    public function show(Application $application)
    {
        $this->authorize('view', $application);

        $application->load(['applicant', 'approvals.approver']);

        return view('applications.show', compact('application'));
    }

    public function edit(Application $application)
    {
        $this->authorize('update', $application);

        if (!$application->canBeEdited()) {
            return redirect()->route('applications.show', $application)
                ->with('error', 'この申請書は編集できません。');
        }

        return view('applications.edit', compact('application'));
    }

    public function update(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        if (!$application->canBeEdited()) {
            return redirect()->route('applications.show', $application)
                ->with('error', 'この申請書は編集できません。');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:expense,leave,purchase,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'amount' => 'nullable|numeric|min:0',
            'requested_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:today',
            'attachments' => 'nullable|array',
        ]);

        if ($application->status === 'under_review' && isset($validated['amount'])) {
            $oldAmount = $application->amount;
            $newAmount = $validated['amount'];

            if ($oldAmount != $newAmount) {
                throw new \BadMethodCallException('承認中の申請は金額を変更できません');
            }
        }

        if (isset($validated['due_date'])) {
            $today = \Carbon\Carbon::today();
            $dueDate = \Carbon\Carbon::parse($validated['due_date']);

            if ($dueDate->lt($today)) {
                throw new \OutOfBoundsException('期限日は本日以降に設定してください');
            }
        }

        $application->update($validated);

        return redirect()->route('applications.show', $application)
            ->with('success', '申請書を更新しました。');
    }

    public function destroy(Application $application)
    {
        $this->authorize('delete', $application);

        if (!$application->isDraft()) {
            return redirect()->route('applications.show', $application)
                ->with('error', '提出済みの申請書は削除できません。');
        }

        $application->delete();

        return redirect()->route('applications.index')
            ->with('success', '申請書を削除しました。');
    }

    public function submit(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        if (!$application->canBeSubmitted()) {
            return redirect()->route('applications.show', $application)
                ->with('error', 'この申請書は提出できません。');
        }

        $flow = ApprovalFlow::findBestMatch($application);
        if (!$flow) {
            return redirect()->route('applications.show', $application)
                ->with('error', '承認フローが見つかりません。管理者にお問い合わせください。');
        }

        $application->submit();
        $application->update([
            'status' => 'under_review',
            'approval_flow_id' => $flow->id
        ]);
        $flow->createApprovals($application);

        return redirect()->route('applications.show', $application)
            ->with('success', '申請書を提出しました。');
    }

    public function cancel(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        if (!$application->canBeCancelled()) {
            return redirect()->route('applications.show', $application)
                ->with('error', 'この申請書はキャンセルできません。');
        }

        $application->cancel();

        return redirect()->route('applications.show', $application)
            ->with('success', '申請書をキャンセルしました。');
    }

    public function pending()
    {
        $applications = Application::with(['applicant', 'approvals.approver'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('applications.pending', compact('applications'));
    }

    public function myApprovals()
    {
        \DB::enableQueryLog();

        $allApprovals = Auth::user()->approvals()
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();

        $authorizedApprovals = collect();
        foreach ($allApprovals as $approval) {
            if ($this->canUserViewApproval(Auth::user(), $approval)) {
                $authorizedApprovals->push($approval);
            }
        }

        $currentPage = request()->get('page', 1);
        $perPage = 15;
        $offset = ($currentPage - 1) * $perPage;

        $paginatedApprovals = $authorizedApprovals->slice($offset, $perPage);

        $approvals = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedApprovals->values(),
            $authorizedApprovals->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        $queryCount = count(\DB::getQueryLog());
        Log::info('承認一覧ページアクセス', [
            'query_count' => $queryCount,
            'total_approvals' => $allApprovals->count(),
            'authorized_approvals' => $authorizedApprovals->count(),
            'displayed_approvals' => $paginatedApprovals->count(),
        ]);

        return view('applications.my-approvals', compact('approvals'));
    }

    /**
     * ユーザーが承認を表示する権限があるかチェック
     */
    private function canUserViewApproval($user, $approval)
    {
        $application = $approval->application;
        $approvalFlow = $approval->approvalFlow;
        $applicant = $application->applicant;

        return true;
    }
}