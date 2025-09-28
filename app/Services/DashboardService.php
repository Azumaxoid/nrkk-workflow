<?php

namespace App\Services;

use App\Models\User;

class DashboardService
{
    public function __construct()
    {
        //
    }

    /**
     * ダッシュボードアクセスのメトリクス記録
     */
    public function recordDashboardAccess(User $user)
    {
    }

    /**
     * ダッシュボード統計のメトリクス記録
     */
    public function recordDashboardStats(array $stats, User $user)
    {
        // New Relic記録メソッドは削除済み
    }
}