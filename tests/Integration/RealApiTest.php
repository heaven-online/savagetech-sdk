<?php

use LuciferGaming\SavageTechSDK\Facades\SavageTech;

// 標記為整合測試組
uses()->group('integration');

// 檢查是否有真實憑證
beforeEach(function () {
    $vendorId = env('SAVAGETECH_VENDOR_ID');
    $vendorSecret = env('SAVAGETECH_VENDOR_SECRET');
    
    if (empty($vendorId) || empty($vendorSecret)) {
        $this->markTestSkipped('需要真實 API 憑證才能運行整合測試');
    }
    
    // 設置測試環境配置
    $this->app['config']->set('savagetech.credentials.vendor_id', $vendorId);
    $this->app['config']->set('savagetech.credentials.vendor_secret', $vendorSecret);
    $this->app['config']->set('savagetech.api_url', env('SAVAGETECH_API_URL', 'https://api.savagetech.com'));
});

// 測試獲取 Access Token
test('can get real access token from API', function () {
    $userId = 'test-user-' . uniqid();
    $token = SavageTech::getAccessToken($userId, 'usd');
    
    expect($token)
        ->toBeArray()
        ->toHaveKey('jwt')
        ->toHaveKey('pubsub');
});

// 測試記錄存款
test('can record real deposit to API', function () {
    $userId = 'test-user-' . uniqid();
    $result = SavageTech::depositMade($userId, 100, 'usd');
    
    expect($result)
        ->toBeArray()
        ->toHaveKey('success');
    
    expect($result['success'])->toBeTrue();
});

// 測試記錄投注
test('can record real bet to API', function () {
    $userId = 'test-user-' . uniqid();
    $result = SavageTech::betPlaced($userId, 50, 1.5, 'usd');
    
    expect($result)
        ->toBeArray()
        ->toHaveKey('success');
    
    expect($result['success'])->toBeTrue();
});

// 測試生成初始化代碼
test('can generate widget init code with real token', function () {
    $userId = 'test-user-' . uniqid();
    $initCode = SavageTech::generateWidgetInitCode($userId, [], 'usd');
    
    expect($initCode)
        ->toBeString()
        ->toContain('window.Savage.init');
}); 