# SavageTech SDK 測試指南

此文檔提供了有關如何測試 SavageTech SDK 的詳細指南。

## 測試架構

SavageTech SDK 使用 [Pest](https://pestphp.com/) 作為 PHP 測試框架，並使用 [Jest](https://jestjs.io/) 來測試 JavaScript 部分。測試結構分為以下幾個部分：

1. **單元測試** - 測試個別元件和類的功能
2. **功能測試** - 測試 API 和控制器的功能
3. **整合測試** - 與真實 API 的整合測試
4. **JavaScript 測試** - 測試前端 JavaScript 代碼

## 運行 PHP 測試

### 安裝測試依賴

```bash
composer install --dev
```

### 運行所有測試

```bash
./vendor/bin/pest
```

### 運行特定測試組

```bash
# 只運行單元測試
./vendor/bin/pest --group=unit

# 只運行功能測試
./vendor/bin/pest --group=feature

# 只運行整合測試 (需要真實 API 憑證)
./vendor/bin/pest --group=integration
```

> **注意**：某些測試需要完整的 Laravel 環境，或需要真實 API 憑證才能執行。在獨立包環境中，這些測試會被自動跳過。

### 生成測試覆蓋率報告

```bash
./vendor/bin/pest --coverage
```

## 運行 JavaScript 測試

### 安裝 JavaScript 依賴

```bash
npm install
```

### 運行所有 JavaScript 測試

```bash
npm test
```

### 生成 JavaScript 測試覆蓋率報告

```bash
npm test -- --coverage
```

## 設置真實 API 測試

要運行整合測試，您需要有有效的 SavageTech API 憑證。您可以通過兩種方式提供這些憑證：

### 方法 1: 使用 .env 文件

創建一個 `.env.testing` 文件，並填入以下內容：

```
SAVAGETECH_VENDOR_ID=你的測試用-vendor-id
SAVAGETECH_VENDOR_SECRET=你的測試用-vendor-secret
SAVAGETECH_API_URL=https://api.test.savagetech.com
```

### 方法 2: 更新 phpunit.xml 文件

在 `phpunit.xml` 文件中，取消以下行的注釋，並填入您的憑證：

```xml
<env name="SAVAGETECH_VENDOR_ID" value="你的測試用-vendor-id"/>
<env name="SAVAGETECH_VENDOR_SECRET" value="你的測試用-vendor-secret"/>
<env name="SAVAGETECH_API_URL" value="https://api.test.savagetech.com"/>
```

## 在 Laravel 應用中進行測試

如果您想在實際的 Laravel 應用中全面測試此套件，包括 Facade 和控制器，請將套件安裝到 Laravel 應用中，然後：

1. 使用 Laravel 應用中的測試環境設置
2. 引入並擴展我們提供的測試 
3. 確保正確設置了環境變數和配置

例如，在您的 Laravel 應用中：

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use LuciferGaming\SavageTechSDK\Facades\SavageTech;

class SavageTechFacadeTest extends TestCase
{
    public function testFacadeWorks()
    {
        // 設置模擬
        SavageTech::shouldReceive('getAccessToken')
            ->once()
            ->with('test-user', 'usd')
            ->andReturn(['jwt' => 'test-token', 'pubsub' => 'test-pubsub']);
            
        // 測試 Facade
        $result = SavageTech::getAccessToken('test-user', 'usd');
        $this->assertEquals(['jwt' => 'test-token', 'pubsub' => 'test-pubsub'], $result);
    }
}
```

## 測試檔案結構

```
tests/
├── Feature/
│   └── Http/
│       └── Controllers/
│           └── SavageTechControllerTest.php
├── Integration/
│   └── RealApiTest.php
├── Unit/
│   ├── Facades/
│   │   └── SavageTechFacadeTest.php
│   ├── Services/
│   │   ├── ExceptionHandlingTest.php
│   │   └── SavageTechServiceTest.php
│   └── ...
├── js/
│   ├── savage-tech-helper.test.js
│   └── setup.js
├── Pest.php
└── TestCase.php
```

## 撰寫新測試

### PHP 測試 (使用 Pest)

Pest 使用 [Expectation API](https://pestphp.com/docs/expectations) 來撰寫測試斷言，下面是一個簡單的例子：

```php
test('can get access token', function () {
    $result = SavageTech::getAccessToken('test-user', 'usd');
    
    expect($result)
        ->toBeArray()
        ->toHaveKey('jwt')
        ->toHaveKey('pubsub');
});
```

### JavaScript 測試 (使用 Jest)

```javascript
test('init method should fetch initialization code', async () => {
    const result = await helper.init();
    
    expect(global.fetch).toHaveBeenCalledWith(
        '/api/savage-tech/init?user_id=test-user'
    );
    
    expect(result).toHaveProperty('init_code');
});
```

## 故障排除

### PHP 測試問題

1. **缺少依賴項**：確保運行 `composer install --dev` 安裝所有必要的依賴項。

2. **整合測試失敗**：如果整合測試失敗，請檢查您的 API 憑證是否有效，以及 API 端點是否可訪問。

3. **測試超時**：對於需要調用外部 API 的測試，可能會出現超時問題。您可以在 `phpunit.xml` 中增加超時限制：

   ```xml
   <phpunit 
      timeoutForSmallTests="5"
      timeoutForMediumTests="10"
      timeoutForLargeTests="20">
   ```

4. **跳過的測試**：某些測試會自動跳過，因為它們需要：
   - 完整的 Laravel 環境 (Facade 和控制器測試)
   - 真實的 API 憑證 (整合測試)
   
   這是正常的，您可以在適當的環境中運行這些測試。

### JavaScript 測試問題

1. **找不到模組**：確保運行 `npm install` 安裝所有必要的依賴項。

2. **JSDOM 相關錯誤**：如果遇到 JSDOM 相關的錯誤，請確保 Jest 配置使用了正確的測試環境：`testEnvironment: 'jsdom'`。 