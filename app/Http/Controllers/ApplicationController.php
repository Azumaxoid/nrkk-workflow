<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApprovalFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['applicant', 'approvals.approver']);

        if (Auth::user()->isApplicant()) {
            $query->byApplicant(Auth::id());
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
        $approvals = Auth::user()->approvals()
            ->with(['application.applicant', 'approvalFlow'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('applications.my-approvals', compact('approvals'));
    }
}