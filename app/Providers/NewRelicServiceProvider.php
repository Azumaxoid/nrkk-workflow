<?php

namespace App\Providers;

use App\Services\NewRelicService;
use Illuminate\Support\ServiceProvider;

class NewRelicServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // NewRelicServiceをシングルトンとして登録
        $this->app->singleton(NewRelicService::class, function ($app) {
            return new NewRelicService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // New Relic設定の公開
        $this->publishes([
            __DIR__ . '/../../config/newrelic.php' => config_path('newrelic.php'),
        ], 'newrelic-config');

        // New Relicが有効な場合の初期設定
        if (config('newrelic.enabled', true)) {
            $this->initializeNewRelic();
        }
    }

    /**
     * New Relicの初期設定
     */
    private function initializeNewRelic(): void
    {
        $newRelicService = app(NewRelicService::class);

        // アプリケーション名を設定
        if ($newRelicService->isEnabled()) {
            // エラーグループコールバックを登録
            $newRelicService->setErrorGroupCallback();

            // アプリケーション情報を追加
            $newRelicService->addCustomParameter('app_environment', app()->environment());
            $newRelicService->addCustomParameter('app_version', config('app.version', '1.0.0'));

            // リクエストごとの情報を追加
            if (!app()->runningInConsole()) {
                $request = request();
                $newRelicService->addCustomParameter('request_method', $request->method());
                $newRelicService->addCustomParameter('request_path', $request->path());
            }
        }
    }
}