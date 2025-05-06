<?php

namespace LuciferGaming\SavageTechSDK\Providers;

use Illuminate\Support\ServiceProvider;
use LuciferGaming\SavageTechSDK\Services\SavageTechService;

class SavageTechServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務提供者
     *
     * @return void
     */
    public function register()
    {
        // 合併配置檔案
        $this->mergeConfigFrom(
            __DIR__.'/../../config/savagetech.php', 'savagetech'
        );

        // 註冊主要服務
        $this->app->singleton('savagetech', function ($app) {
            return new SavageTechService(
                $app['config']['savagetech.credentials.vendor_id'],
                $app['config']['savagetech.credentials.vendor_secret'],
                $app['config']['savagetech.api_url'],
                $app['config']['savagetech.http']
            );
        });
    }

    /**
     * 啟動服務提供者
     *
     * @return void
     */
    public function boot()
    {
        // 發布配置檔案
        $this->publishes([
            __DIR__.'/../../config/savagetech.php' => config_path('savagetech.php'),
        ], 'savagetech-config');

        // 發布前端資源
        $this->publishes([
            __DIR__.'/../assets/js' => public_path('vendor/savagetech'),
        ], 'savagetech-assets');

        // 載入路由檔案
        $this->loadRoutesFrom(__DIR__.'/../routes/savagetech.php');
    }
} 