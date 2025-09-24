<?php

namespace App\Providers;

use App\Services\ApprovalService;
use App\Services\NewRelicService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ApprovalServiceをシングルトンとして登録
        $this->app->singleton(ApprovalService::class, function ($app) {
            return new ApprovalService($app->make(NewRelicService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}