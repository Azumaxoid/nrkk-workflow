<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
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
            $stats['pending_approvals'] = Approval::byApprover($user->id)->pending()->count();
            $stats['my_approvals_count'] = Approval::byApprover($user->id)->count();
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
            $pendingApprovals = Approval::byApprover($user->id)
                ->pending()
                ->with(['application.applicant'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
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