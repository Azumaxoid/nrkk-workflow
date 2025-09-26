<?php

namespace App\Http\Middleware;

use App\Services\NewRelicService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NewRelicCustomAttributes
{
    protected $newRelicService;

    public function __construct(NewRelicService $newRelicService)
    {
        $this->newRelicService = $newRelicService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // リクエスト開始時にカスタム属性を設定
        $this->setRequestAttributes($request);

        // リクエスト処理
        $response = $next($request);

        // レスポンス後にカスタム属性を設定
        $this->setResponseAttributes($request, $response);

        return $response;
    }

    /**
     * リクエスト開始時のカスタム属性を設定
     */
    protected function setRequestAttributes(Request $request): void
    {
        if (!$this->newRelicService->isEnabled()) {
            return;
        }

        Log::info('Add New Relic custom attribute');

        try {

            // ルート情報
            if ($request->route()) {
                $this->newRelicService->addCustomParameter('route.name', $request->route()->getName() ?? 'unnamed');
                $this->newRelicService->addCustomParameter('route.action', $request->route()->getActionName());

                // コントローラーとアクション
                if ($request->route()->getAction()) {
                    $action = $request->route()->getAction();
                    if (isset($action['controller'])) {
                        $controller = class_basename($action['controller']);
                        $this->newRelicService->addCustomParameter('controller.name', $controller);
                    }
                }
            }

            // 環境情報
            $this->newRelicService->addCustomParameter('environment', app()->environment());

            // ユーザー情報（認証されている場合）
            if (Auth::check()) {
                Log::info('Add User attribute');
                $user = Auth::user();
                $this->newRelicService->addCustomParameter('userId', $user->id);
                $this->newRelicService->addCustomParameter('userRole', $user->role ?? 'unknown');
                $this->newRelicService->addCustomParameter('userOrganizationId', $user->organization_id ?? 'none');

                // ユーザーの組織情報
                if ($user->organization) {
                    $this->newRelicService->addCustomParameter('organizationName', $user->organization->name);
                    $this->newRelicService->addCustomParameter('organizationType', $user->organization->type ?? 'unknown');
                }
            } else {
                $this->newRelicService->addCustomParameter('userAuthenticated', false);
            }

            // セッション情報
            if ($request->hasSession()) {
                $this->newRelicService->addCustomParameter('sessionId', $request->session()->getId());
                $this->newRelicService->addCustomParameter('sessionHasErrors', $request->session()->has('errors'));
            }


            // リファラー情報
            if ($request->header('referer')) {
                $this->newRelicService->addCustomParameter('request.referer', $request->header('referer'));
            }

            // カスタムヘッダー（アプリケーション固有）
            if ($request->header('X-Request-Id')) {
                $this->newRelicService->addCustomParameter('request.request_id', $request->header('X-Request-Id'));
            }

        } catch (\Exception $e) {
            Log::error('Failed to set New Relic request attributes: ' . $e->getMessage());
        }
    }

    /**
     * レスポンス後のカスタム属性を設定
     */
    protected function setResponseAttributes(Request $request, $response): void
    {
        if (!$this->newRelicService->isEnabled()) {
            return;
        }

        try {

            // レスポンスサイズ
            if (method_exists($response, 'getContent')) {
                $content = $response->getContent();
                if ($content) {
                    $this->newRelicService->addCustomParameter('response.size', strlen($content));
                }
            }

            // メモリ使用量
            $memoryUsage = memory_get_peak_usage(true) / 1048576; // MB
            $this->newRelicService->addCustomParameter('memory.peak_usage_mb', round($memoryUsage, 2));
            $this->newRelicService->recordMetric('MemoryPeakUsage', $memoryUsage);
        } catch (\Exception $e) {
            Log::error('Failed to set New Relic response attributes: ' . $e->getMessage());
        }
    }
}