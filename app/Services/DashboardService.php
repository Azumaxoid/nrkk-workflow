<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * ユーザーのダッシュボード統計を取得
     *
     * @param User $user
     * @return array
     */
    public function getStatistics(User $user): array
    {
        $stats = [
            'my_applications' => 0,
            'pending_approvals' => 0,
            'total_applications' => 0,
            'approved_applications' => 0,
            'rejected_applications' => 0,
            'draft_applications' => 0,
            'pending_applications' => 0,
            'my_approvals_count' => 0,
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

        return $stats;
    }

    /**
     * 最近の申請を取得
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getRecentApplications(User $user, int $limit = 5): Collection
    {
        if (!$user->isApplicant()) {
            return collect();
        }

        return Application::byApplicant($user->id)
            ->with(['approvals.approver'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 保留中の承認を取得
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getPendingApprovals(User $user, int $limit = 5): Collection
    {
        if (!$user->isReviewer()) {
            return collect();
        }

        return $this->approvalService->getApprovals([
            'approver_id' => $user->id,
            'status' => 'pending',
            'with' => ['application.applicant'],
            'order_by' => 'created_at',
            'order_direction' => 'desc'
        ])->take($limit);
    }

    /**
     * 月次統計を取得
     *
     * @param User $user
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStatistics(User $user, int $months = 6): Collection
    {
        if (!$user->isAdmin() && !$user->isReviewer()) {
            return collect();
        }

        return Application::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected')
            )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * ダッシュボード用の全データを取得
     *
     * @param User $user
     * @return array
     */
    public function getDashboardData(User $user): array
    {
        return [
            'stats' => $this->getStatistics($user),
            'recentApplications' => $this->getRecentApplications($user),
            'pendingApprovals' => $this->getPendingApprovals($user),
            'monthlyStats' => $this->getMonthlyStatistics($user)
        ];
    }
}