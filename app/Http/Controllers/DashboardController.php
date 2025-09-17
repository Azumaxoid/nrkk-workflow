<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    public function index()
    {
        $user = Auth::user();
        
        $stats = [
            'my_applications' => 0,
            'pending_approvals' => 0,
            'total_applications' => 0,
            'approved_applications' => 0,
        ];

        if ($user->isApplicant()) {
            $stats['my_applications'] = Application::byApplicant($user->id)->count();
            $stats['draft_applications'] = Application::byApplicant($user->id)->byStatus('draft')->count();
            $stats['pending_applications'] = Application::byApplicant($user->id)->pending()->count();
        }

        if ($user->isReviewer()) {
            $stats['pending_approvals'] = $this->approvalService->countApprovals([
                'approver_id' => $user->id,
                'status' => 'pending'
            ]);
            $stats['my_approvals_count'] = $this->approvalService->countApprovals([
                'approver_id' => $user->id
            ]);
        }

        if ($user->isAdmin()) {
            $stats['total_applications'] = Application::count();
            $stats['approved_applications'] = Application::byStatus('approved')->count();
            $stats['rejected_applications'] = Application::byStatus('rejected')->count();
            $stats['pending_applications'] = Application::pending()->count();
        }

        $recentApplications = collect();
        $pendingApprovals = collect();

        if ($user->isApplicant()) {
            $recentApplications = Application::byApplicant($user->id)
                ->with(['approvals.approver'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        if ($user->isReviewer()) {
            $pendingApprovals = $this->approvalService->getApprovals([
                'approver_id' => $user->id,
                'status' => 'pending',
                'with' => ['application.applicant'],
                'order_by' => 'created_at',
                'order_direction' => 'desc'
            ])->take(5);
        }

        $monthlyStats = [];
        if ($user->isAdmin() || $user->isReviewer()) {
            $monthlyStats = Application::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
                    DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected')
                )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }

        return view('dashboard', compact('stats', 'recentApplications', 'pendingApprovals', 'monthlyStats'));
    }
}