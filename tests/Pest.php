<?php

/*
|--------------------------------------------------------------------------
| Pest 設置文件
|--------------------------------------------------------------------------
|
| 這是 Pest 框架的設置文件，用於定義全局的測試設置、輔助函數和分組
|
*/

// 設置單元測試組
uses()
    ->group('unit')
    ->in('Unit');

// 設置功能測試組  
uses()
    ->group('feature')
    ->in('Feature');

// 設置整合測試組  
uses()
    ->group('integration')
    ->in('Integration');

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| 輔助函數
|--------------------------------------------------------------------------
*/

/**
 * 創建一個假的 JWT token
 */
function createFakeJwt($expiryInSeconds = 3600) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $currentTimeInSeconds = time();
    $payload = base64_encode(json_encode([
        'sub' => 'test-user',
        'exp' => $currentTimeInSeconds + $expiryInSeconds,
        'iat' => $currentTimeInSeconds
    ]));
    $signature = base64_encode('fake-signature');
    
    return "$header.$payload.$signature";
}

expect()->extend('toBeJwt', function () {
    $parts = explode('.', $this->value);
    expect($parts)->toHaveCount(3);
    
    $payload = json_decode(base64_decode($parts[1]), true);
    expect($payload)->toBeArray();
    expect($payload)->toHaveKey('exp');
    
    return $this;
});
