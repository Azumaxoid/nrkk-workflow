<?php

namespace App\Services;

use App\Models\User;
use App\Services\NewRelicService;

class DashboardService
{
    protected $newRelicService;

    public function __construct(NewRelicService $newRelicService)
    {
        $this->newRelicService = $newRelicService;
    }

    /**
     * ダッシュボードアクセスのメトリクス記録
     */
    public function recordDashboardAccess(User $user)
    {
        $this->newRelicService->addCustomParameter('dashboard.action', 'view');
        $this->newRelicService->addCustomParameter('dashboard.user_role', $user->role ?? 'unknown');
    }

    /**
     * ダッシュボード統計のメトリクス記録
     */
    public function recordDashboardStats(array $stats, User $user)
    {
        $this->newRelicService->addCustomParameter('dashboard.stats.my_applications', $stats['my_applications']);
        $this->newRelicService->addCustomParameter('dashboard.stats.pending_approvals', $stats['pending_approvals']);
        $this->newRelicService->addCustomParameter('dashboard.stats.total_applications', $stats['total_applications']);
        $this->newRelicService->recordMetric('DashboardPendingApprovals', $stats['pending_approvals']);

        // ダッシュボードアクセスイベントを記録
        $this->newRelicService->recordCustomEvent('DashboardAccessed', [
            'user_id' => $user->id,
            'user_role' => $user->role ?? 'unknown',
            'organization_id' => $user->organization_id ?? null,
            'pending_approvals' => $stats['pending_approvals'],
            'my_applications' => $stats['my_applications']
        ]);
    }
}