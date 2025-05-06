<?php

namespace Tests;

use LuciferGaming\SavageTechSDK\Providers\SavageTechServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * 獲取套件服務提供者
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            SavageTechServiceProvider::class,
        ];
    }

    /**
     * 定義環境設置
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // 設定測試環境變數
        $app['config']->set('savagetech.credentials.vendor_id', 'test-vendor-id');
        $app['config']->set('savagetech.credentials.vendor_secret', 'test-vendor-secret');
        $app['config']->set('savagetech.api_url', 'https://api.test.savagetech.com');
        $app['config']->set('savagetech.default_currency', 'usd');
        $app['config']->set('savagetech.widget.token_refresh_before_minutes', 10);
    }
}
