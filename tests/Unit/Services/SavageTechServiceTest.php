<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LuciferGaming\SavageTechSDK\Exceptions\SavageTechException;
use LuciferGaming\SavageTechSDK\Services\SavageTechService;

// Pest 測試設置函數
beforeEach(function () {
    // 設置 Mock HTTP 客戶端
    $this->mockHandler = new MockHandler();
    $handlerStack = HandlerStack::create($this->mockHandler);
    $client = new Client(['handler' => $handlerStack]);

    // 創建服務實例
    $this->service = new SavageTechService('test-vendor-id', 'test-vendor-secret', 'https://api.test.savagetech.com');
    
    // 使用反射來設置私有屬性
    $reflection = new ReflectionClass($this->service);
    $httpClientProperty = $reflection->getProperty('httpClient');
    $httpClientProperty->setAccessible(true);
    $httpClientProperty->setValue($this->service, $client);
    
    // 模擬 config 函數
    if (!function_exists('config')) {
        function config($key, $default = null) {
            return $default;
        }
    }
});

// 測試獲取訪問令牌
test('can get access token', function () {
    // 模擬 API 回應
    $this->mockHandler->append(
        new Response(200, [], json_encode([
            'jwt' => 'test-jwt-token',
            'pubsub' => 'test-pubsub-token'
        ]))
    );

    $result = $this->service->getAccessToken('test-user-id', 'usd');

    expect($result)
        ->toBeArray()
        ->toHaveKey('jwt')
        ->toHaveKey('pubsub');
    
    expect($result['jwt'])->toBe('test-jwt-token');
    expect($result['pubsub'])->toBe('test-pubsub-token');
});

// 測試記錄玩家存款
test('can record deposit', function () {
    // 模擬 API 回應
    $this->mockHandler->append(
        new Response(200, [], json_encode([
            'success' => true,
            'transactionId' => 'test-transaction-id'
        ]))
    );

    $result = $this->service->depositMade('test-user-id', 100, 'usd');

    expect($result)
        ->toBeArray()
        ->toHaveKey('success')
        ->toHaveKey('transactionId');
    
    expect($result['success'])->toBeTrue();
    expect($result['transactionId'])->toBe('test-transaction-id');
});

// 測試記錄玩家投注
test('can record bet', function () {
    // 模擬 API 回應
    $this->mockHandler->append(
        new Response(200, [], json_encode([
            'success' => true,
            'betId' => 'test-bet-id'
        ]))
    );

    $result = $this->service->betPlaced('test-user-id', 50, 1.5, 'usd');

    expect($result)
        ->toBeArray()
        ->toHaveKey('success')
        ->toHaveKey('betId');
    
    expect($result['success'])->toBeTrue();
    expect($result['betId'])->toBe('test-bet-id');
});

// 測試設置貨幣
test('can set currencies', function () {
    // 模擬 API 回應
    $this->mockHandler->append(
        new Response(200, [], json_encode([
            'success' => true,
            'message' => 'Currencies updated successfully'
        ]))
    );

    $currencies = [
        'eur' => [
            'symbol' => 'EUR',
            'fullName' => 'Euro',
            'shortName' => 'EUR',
            'conversionToUSD' => 1.09,
            'roundedTo' => 1
        ]
    ];

    $result = $this->service->setCurrencies($currencies);

    expect($result)
        ->toBeArray()
        ->toHaveKey('success')
        ->toHaveKey('message');
    
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Currencies updated successfully');
});

// 測試生成小工具初始化代碼
test('can generate widget init code', function () {
    // 模擬 getAccessToken 回應
    $this->mockHandler->append(
        new Response(200, [], json_encode([
            'jwt' => 'test-jwt-token',
            'pubsub' => 'test-pubsub-token'
        ]))
    );

    $result = $this->service->generateWidgetInitCode('test-user-id', [], 'usd');

    expect($result)
        ->toBeArray()
        ->toHaveKey('init_code')
        ->toHaveKey('token');
    
    expect($result['init_code'])
        ->toBeString()
        ->toContain('window.Savage.init')
        ->toContain('test-jwt-token')
        ->toContain('test-pubsub-token');
    
    expect($result['token'])
        ->toBeArray()
        ->toHaveKey('jwt')
        ->toHaveKey('pubsub');
    
    expect($result['token']['jwt'])->toBe('test-jwt-token');
    expect($result['token']['pubsub'])->toBe('test-pubsub-token');
});

// 測試錯誤處理
test('handles API errors correctly', function () {
    // 模擬 API 錯誤回應
    $this->mockHandler->append(
        new Response(400, [], json_encode([
            'message' => '無效的請求參數',
            'code' => 'INVALID_PARAMS'
        ]))
    );

    expect(fn() => $this->service->getAccessToken('test-user-id', 'usd'))
        ->toThrow(SavageTechException::class, '無效的請求參數');
});

// 測試連接錯誤處理
test('handles connection errors correctly', function () {
    // 模擬 連接錯誤
    $this->mockHandler->append(
        new \GuzzleHttp\Exception\ConnectException(
            'Failed to connect to server',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        )
    );

    expect(fn() => $this->service->getAccessToken('test-user-id', 'usd'))
        ->toThrow(SavageTechException::class);
}); 