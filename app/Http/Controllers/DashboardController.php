<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * ダッシュボードを表示
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        $this->logDashboardAccess($user);

        $data = $this->dashboardService->getDashboardData($user);

        return view('dashboard', [
            'stats' => $data['stats'],
            'recentApplications' => $data['recentApplications'],
            'pendingApprovals' => $data['pendingApprovals'],
            'monthlyStats' => $data['monthlyStats']
        ]);
    }

    /**
     * ダッシュボードアクセスのログを記録
     *
     * @param \App\Models\User $user
     * @return void
     */
    private function logDashboardAccess($user): void
    {
        Log::info(sprintf(
            'ダッシュボードアクセス | ユーザーID: %d | ユーザーメール: %s | 組織ID: %s | ロール: %s | ユーザーエージェント: %s',
            $user->id,
            $user->email ?? 'unknown',
            $user->organization_id ?? 'N/A',
            $user->role ?? 'unknown',
            request()->userAgent() ?? 'unknown'
        ));
    }
}