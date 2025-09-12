<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function approve(Request $request, Approval $approval)
    {
        $this->authorize('act', $approval);

        if (!$approval->isPending()) {
            return redirect()->back()->with('error', 'この承認はすでに処理されています。');
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        $approval->approve($request->comment);

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

        $approval->reject($request->comment);

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
}