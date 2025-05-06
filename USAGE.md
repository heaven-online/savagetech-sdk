# SavageTech SDK 整合指南

本文檔提供 SavageTech SDK 的詳細安裝和使用說明，幫助您在 Laravel 專案中集成 SavageTech 遊戲化功能。

## 1. 安裝

### 1.1 通過 Composer 安裝

```bash
composer require lucifergaming/savagetech-sdk
```

### 1.2 發布配置檔案

發布配置檔案到您的 Laravel 專案中：

```bash
php artisan vendor:publish --tag=savagetech-config
```

### 1.3 發布前端資源（可選）

如果您需要使用我們提供的 JavaScript 輔助工具，可以發布前端資源：

```bash
php artisan vendor:publish --tag=savagetech-assets
```

## 2. 配置

### 2.1 設定環境變數

在 `.env` 檔案中添加以下設定：

```
SAVAGETECH_VENDOR_ID=your-vendor-id
SAVAGETECH_VENDOR_SECRET=your-vendor-secret
SAVAGETECH_API_URL=https://api.savagetech.com
SAVAGETECH_TOKEN_REFRESH_BEFORE_MINUTES=10
```

### 2.2 配置檔案詳解

`config/savagetech.php` 檔案包含以下設定：

- `credentials`: API 認證資訊
  - `vendor_id`: 供應商 ID
  - `vendor_secret`: 供應商密鑰
- `api_url`: API 基礎 URL
- `http`: HTTP 請求設定
  - `timeout`: 請求超時時間（秒）
  - `connect_timeout`: 連接超時時間（秒）
- `default_currency`: 預設貨幣
- `widget`: 小工具設定
  - `enabled`: 是否啟用小工具
  - `token_refresh_before_minutes`: Token 過期前多少分鐘刷新（重要，用於 Token 自動刷新機制）

## 3. API 使用

### 3.1 使用 Facade

SavageTech Facade 提供了簡單的方式來使用所有 API 功能：

```php
use LuciferGaming\SavageTechSDK\Facades\SavageTech;

// 獲取訪問權杖 (指定貨幣)
$token = SavageTech::getAccessToken('user-id', 'usd');

// 記錄玩家存款
$result = SavageTech::depositMade('user-id', 100, 'usd');

// 記錄玩家投注
$result = SavageTech::betPlaced('user-id', 100, 1.5, 'usd');

// 設置貨幣
$currencies = [
    'eur' => [
        'symbol' => 'EUR',
        'fullName' => 'Euro',
        'shortName' => 'EUR',
        'conversionToUSD' => 1.09,
        'roundedTo' => 1
    ]
];
$result = SavageTech::setCurrencies($currencies);

// 生成小工具初始化代碼 (指定貨幣)
$initCode = SavageTech::generateWidgetInitCode('user-id', [], 'usd');
```

### 3.2 通過依賴注入使用

您可以在控制器中通過依賴注入使用 SavageTech 服務：

```php
use LuciferGaming\SavageTechSDK\Services\SavageTechService;
use LuciferGaming\SavageTechSDK\Exceptions\SavageTechException;

class BettingController extends Controller
{
    protected $savageTech;
    
    public function __construct(SavageTechService $savageTech)
    {
        $this->savageTech = $savageTech;
    }
    
    public function placeBet(Request $request)
    {
        try {
            $result = $this->savageTech->betPlaced(
                auth()->id(),
                $request->amount,
                $request->odds,
                $request->currency // 使用請求中提供的貨幣
            );
            
            return response()->json(['success' => true, 'data' => $result]);
        } catch (SavageTechException $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}
```

## 4. 整合小工具

SavageTech 小工具是前端用戶界面，需要在您的頁面中嵌入。

### 4.1 HTML 結構

```html
<div id="savage-widget"></div>
```

### 4.2 使用提供的 JavaScript 輔助類

在您的頁面中引入 JavaScript 文件：

```html
<script src="{{ asset('vendor/savagetech/savage-tech-helper.js') }}"></script>
```

初始化小工具：

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // 創建 SavageTech 輔助類實例
    const savageTech = new SavageTechHelper({
        // 方法 1: 使用 user_id 參數進行認證 (向下兼容)
        userId: '{{ auth()->id() }}', // 登入會員 ID
        
        // 方法 2: 使用 Authorization header 進行認證 (推薦方式)
        useAuthHeader: true, // 啟用 Authorization header
        authToken: '{{ $apiToken }}', // API 令牌或 JWT
        
        currency: 'usd', // 指定貨幣
        config: {}, // 自訂配置
        refreshBeforeMinutes: {{ config('savagetech.widget.token_refresh_before_minutes', 10) }}, // Token 刷新時間設定
        initEndpoint: '/api/savage-tech/init',
        refreshEndpoint: '/api/savage-tech/refresh-token',
        onError: function(error) {
            console.error('SavageTech 錯誤:', error);
        }
    });
    
    // 初始化小工具 (可以直接傳入參數，會覆蓋構造函數中的設定)
    savageTech.init(null, null, null, '{{ $newApiToken }}') // 也可以在 init 時傳入 authToken
        .then(function(data) {
            console.log('SavageTech 小工具已初始化');
        })
        .catch(function(error) {
            console.error('初始化失敗', error);
        });
});
```

### 4.3 手動實現

如果您不想使用輔助類，可以手動實現：

#### 4.3.1 使用 URL 參數 (向下兼容)

```javascript
// 使用 URL 參數方式初始化 SavageTech 小工具
fetch('/api/savage-tech/init?user_id={{ auth()->id() }}&currency=usd')
    .then(response => response.json())
    .then(data => {
        // 執行初始化代碼
        eval(data.init_code);
        
        // 從回應中獲取 Token 刷新時間設定
        const refreshBeforeMinutes = data.refresh_before_minutes || 10;
        
        // 設置 Token 過期處理
        if (window.Savage && typeof window.Savage.onTokenExpiration === 'function') {
            window.Savage.onTokenExpiration(() => {
                // 重新獲取 Token
                fetch('/api/savage-tech/refresh-token?user_id={{ auth()->id() }}&currency=usd')
                    .then(response => response.json())
                    .then(data => {
                        if (window.Savage && typeof window.Savage.setCredentials === 'function') {
                            window.Savage.setCredentials(data.credentials);
                        }
                    })
                    .catch(error => console.error('刷新 Token 失敗', error));
            });
        }
        
        // 如果 Token 包含過期時間，設置提前刷新
        const jwt = data.init_code.match(/jwt["']?\s*:\s*["']([^"']+)["']/);
        if (jwt && jwt[1]) {
            try {
                // 解析 JWT 獲取過期時間
                const parts = jwt[1].split('.');
                if (parts.length === 3) {
                    const payload = JSON.parse(atob(parts[1]));
                    if (payload.exp) {
                        const expiryTime = payload.exp * 1000; // 轉換為毫秒
                        const refreshTime = expiryTime - (refreshBeforeMinutes * 60 * 1000);
                        const timeUntilRefresh = refreshTime - Date.now();
                        
                        if (timeUntilRefresh > 0) {
                            console.log(`Token 將在 ${Math.round(timeUntilRefresh / 60000)} 分鐘後刷新`);
                            
                            // 設置定時器
                            setTimeout(() => {
                                console.log(`Token 即將在 ${refreshBeforeMinutes} 分鐘內過期，提前刷新`);
                                fetch('/api/savage-tech/refresh-token?user_id={{ auth()->id() }}&currency=usd')
                                    .then(response => response.json())
                                    .then(data => {
                                        if (window.Savage && typeof window.Savage.setCredentials === 'function') {
                                            window.Savage.setCredentials(data.credentials);
                                        }
                                    })
                                    .catch(error => console.error('刷新 Token 失敗', error));
                            }, timeUntilRefresh);
                        }
                    }
                }
            } catch (e) {
                console.error('解析 JWT token 時出錯', e);
            }
        }
    })
    .catch(error => console.error('初始化失敗', error));
```

#### 4.3.2 使用 Authorization Header (推薦方式)

```javascript
// 使用 Authorization header 方式初始化 SavageTech 小工具
const apiToken = '{{ $apiToken }}'; // API 令牌
const fetchOptions = {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${apiToken}`
    }
};

// 初始化小工具
fetch('/api/savage-tech/init?currency=usd', fetchOptions)
    .then(response => response.json())
    .then(data => {
        // 執行初始化代碼
        eval(data.init_code);
        
        // 從回應中獲取 Token 刷新時間設定
        const refreshBeforeMinutes = data.refresh_before_minutes || 10;
        
        // 設置 Token 過期處理
        if (window.Savage && typeof window.Savage.onTokenExpiration === 'function') {
            window.Savage.onTokenExpiration(() => {
                // 重新獲取 Token
                fetch('/api/savage-tech/refresh-token?currency=usd', fetchOptions)
                    .then(response => response.json())
                    .then(data => {
                        if (window.Savage && typeof window.Savage.setCredentials === 'function') {
                            window.Savage.setCredentials(data.credentials);
                        }
                    })
                    .catch(error => console.error('刷新 Token 失敗', error));
            });
        }
        
        // 如果 Token 包含過期時間，設置提前刷新
        const jwt = data.init_code.match(/jwt["']?\s*:\s*["']([^"']+)["']/);
        if (jwt && jwt[1]) {
            try {
                // 解析 JWT 獲取過期時間
                const parts = jwt[1].split('.');
                if (parts.length === 3) {
                    const payload = JSON.parse(atob(parts[1]));
                    if (payload.exp) {
                        const expiryTime = payload.exp * 1000; // 轉換為毫秒
                        const refreshTime = expiryTime - (refreshBeforeMinutes * 60 * 1000);
                        const timeUntilRefresh = refreshTime - Date.now();
                        
                        if (timeUntilRefresh > 0) {
                            console.log(`Token 將在 ${Math.round(timeUntilRefresh / 60000)} 分鐘後刷新`);
                            
                            // 設置定時器
                            setTimeout(() => {
                                console.log(`Token 即將在 ${refreshBeforeMinutes} 分鐘內過期，提前刷新`);
                                fetch('/api/savage-tech/refresh-token?currency=usd', fetchOptions)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (window.Savage && typeof window.Savage.setCredentials === 'function') {
                                            window.Savage.setCredentials(data.credentials);
                                        }
                                    })
                                    .catch(error => console.error('刷新 Token 失敗', error));
                            }, timeUntilRefresh);
                        }
                    }
                }
            } catch (e) {
                console.error('解析 JWT token 時出錯', e);
            }
        }
    })
    .catch(error => console.error('初始化失敗', error));
```

## 5. 路由設置

SDK 提供了一組預設路由，您可以通過修改 `routes/web.php` 或 `routes/api.php` 文件添加自己的路由：

```php
use LuciferGaming\SavageTechSDK\Http\Controllers\SavageTechController;

// API 路由
Route::prefix('api/savage-tech')->middleware(['web', 'auth'])->group(function () {
    // 小工具相關路由
    Route::get('/init', [SavageTechController::class, 'getInitCode']);
    Route::get('/refresh-token', [SavageTechController::class, 'refreshToken']);
    
    // 玩家事件記錄路由
    Route::post('/deposit', [SavageTechController::class, 'recordDeposit']);
    Route::post('/bet', [SavageTechController::class, 'recordBet']);
});
```

### 5.1 使用 Authorization Header 的路由設置

如果您希望使用 Authorization header 方式進行認證，您可以修改路由中間件：

```php
use LuciferGaming\SavageTechSDK\Http\Controllers\SavageTechController;

// API 路由 (使用 Auth Bearer Token)
Route::prefix('api/savage-tech')->middleware(['web', 'auth:sanctum'])->group(function () {
    // 小工具相關路由
    Route::get('/init', [SavageTechController::class, 'getInitCode']);
    Route::get('/refresh-token', [SavageTechController::class, 'refreshToken']);
    
    // 玩家事件記錄路由
    Route::post('/deposit', [SavageTechController::class, 'recordDeposit']);
    Route::post('/bet', [SavageTechController::class, 'recordBet']);
});
```

您可能需要在控制器中進行相應調整，以支持從 Authorization header 中獲取用戶信息：

```php
// SavageTechController.php
public function getInitCode(Request $request)
{
    // 從 request 上的 user() 獲取用戶 ID (使用 auth:sanctum 中間件時有效)
    $userId = $request->user()->id;
    
    // 從 URL 參數獲取貨幣代碼
    $currency = $request->input('currency', config('savagetech.default_currency'));
    
    // 獲取自訂配置
    $config = json_decode($request->input('config', '{}'), true);
    
    // 生成初始化代碼
    $initCode = $this->savageTechService->generateWidgetInitCode($userId, $config, $currency);
    
    return response()->json([
        'init_code' => $initCode,
        'refresh_before_minutes' => config('savagetech.widget.token_refresh_before_minutes', 10),
        'jwt' => $this->savageTechService->getAccessToken($userId, $currency)
    ]);
}
```

## 6. 異常處理

SDK 使用 `SavageTechException` 處理 API 調用錯誤。您應該在使用 API 時捕獲和處理這些異常：

```php
use LuciferGaming\SavageTechSDK\Exceptions\SavageTechException;

try {
    $result = SavageTech::betPlaced('user-id', 100, 1.5, 'usd');
} catch (SavageTechException $e) {
    // 獲取錯誤信息
    $message = $e->getMessage();
    
    // 獲取 HTTP 狀態碼
    $statusCode = $e->getCode();
    
    // 獲取完整的 API 響應
    $response = $e->getResponse();
    
    // 記錄錯誤
    Log::error('SavageTech API 錯誤', [
        'message' => $message,
        'code' => $statusCode,
        'response' => $response
    ]);
}
```

## 7. 進階設定

### 7.1 自訂貨幣設定

```php
// 設置多種貨幣
$currencies = [
    'eur' => [
        'symbol' => 'EUR',
        'fullName' => 'Euro',
        'shortName' => 'EUR',
        'conversionToUSD' => 1.09,
        'roundedTo' => 1
    ],
    'btc' => [
        'symbol' => 'BTC',
        'fullName' => 'Bitcoin',
        'shortName' => 'BTC',
        'conversionToUSD' => 30000.00,
        'roundedTo' => 8
    ]
];

SavageTech::setCurrencies($currencies);
```

### 7.2 小工具自訂配置

初始化小工具時可以設置自訂配置：

```php
// 自訂配置
$customConfig = [
    'language' => 'zh-TW',
    'theme' => 'dark',
    'customLabels' => [
        'COINS' => '金幣',
        'SHOP_LABEL' => '商城'
    ]
];

// 指定貨幣同時提供自訂配置
$initCode = SavageTech::generateWidgetInitCode($userId, $customConfig, 'usd');
```

### 7.3 Token 自動刷新機制

SDK 提供了 Token 自動刷新機制，避免用戶在使用過程中因 Token 過期而中斷體驗。此機制通過以下方式工作：

1. **設定刷新時間**：在 `.env` 文件或配置中設定 `SAVAGETECH_TOKEN_REFRESH_BEFORE_MINUTES`，指定 Token 過期前多少分鐘進行刷新（預設為 10 分鐘）。

2. **自動檢測過期時間**：SDK 會解析 JWT Token，獲取其過期時間（`exp` 欄位），然後計算何時應該進行刷新。

3. **定時刷新**：當到達計算出的刷新時間點，SDK 會自動向後端發送請求獲取新的 Token，並更新小工具的憑證。

4. **過期回調處理**：即使自動刷新機制失敗，SDK 也設置了 `onTokenExpiration` 回調，在 Token 完全過期時進行處理。

配置示例：

```php
// 配置文件中的設定
'widget' => [
    'enabled' => env('SAVAGETECH_WIDGET_ENABLED', true),
    'token_refresh_before_minutes' => env('SAVAGETECH_TOKEN_REFRESH_BEFORE_MINUTES', 10),
],
```

JavaScript 使用示例：

```javascript
// 方法 1: 使用 user_id 參數 (向下兼容)
const savageTech = new SavageTechHelper({
    userId: '{{ auth()->id() }}',
    currency: 'usd',
    refreshBeforeMinutes: {{ config('savagetech.widget.token_refresh_before_minutes', 10) }},
    initEndpoint: '/api/savage-tech-init',
    refreshEndpoint: '/api/savage-tech/refresh-token'
});

// 方法 2: 使用 Authorization header (推薦方式)
const savageTech = new SavageTechHelper({
    useAuthHeader: true,
    authToken: '{{ $apiToken }}',
    currency: 'usd',
    refreshBeforeMinutes: {{ config('savagetech.widget.token_refresh_before_minutes', 10) }},
    initEndpoint: '/api/savage-tech-init',
    refreshEndpoint: '/api/savage-tech/refresh-token'
});

// 初始化後將自動處理 Token 刷新
savageTech.init();

// 如果需要更新 auth token，也可以透過 refreshToken 方法更新
savageTech.refreshToken('{{ $newApiToken }}');
```

## 8. 故障排除

### 8.1 常見問題

1. **API 認證錯誤**：
   - 確保 `SAVAGETECH_VENDOR_ID` 和 `SAVAGETECH_VENDOR_SECRET` 正確設置
   - 檢查 `.env` 文件是否被正確加載

2. **小工具未顯示**：
   - 確保 HTML 有一個 ID 為 `savage-widget` 的 div
   - 檢查控制台是否有 JavaScript 錯誤
   - 確認 Token 是否正確獲取

3. **Token 過期問題**：
   - 確保實現了 `onTokenExpiration` 回調
   - 設置適當的 `SAVAGETECH_TOKEN_REFRESH_BEFORE_MINUTES` 值
   - 檢查瀏覽器控制台是否有關於 Token 刷新的錯誤日誌

4. **貨幣問題**：
   - 確保在初始化時提供了正確的貨幣代碼
   - 使用 `setCurrencies` 方法添加支援的貨幣及其轉換率

5. **Authorization Header 問題**：
   - 當使用 `useAuthHeader: true` 時，確保提供了有效的 `authToken`
   - 檢查瀏覽器網絡請求，確認 Authorization header 已正確發送
   - 如果使用 Laravel Sanctum，確保路由已設置 `auth:sanctum` 中間件
   - 檢查 API token 是否有效，未過期，且有足夠的權限

### 8.2 調試提示

- 在開發模式下使用 Laravel 的 `Log` facade 記錄 API 請求和響應
- 使用瀏覽器開發者工具監視網絡請求
- 檢查 Laravel 日誌文件查找錯誤
- SavageTechHelper 類會在瀏覽器控制台輸出 Token 刷新相關日誌，可以用於跟踪 Token 狀態

## 9. 實用程式

SDK 提供了一些實用程式來幫助處理 SavageTech API 數據：

```php
// 使用 ServiceProvider 提供的輔助函數
use LuciferGaming\SavageTechSDK\Facades\SavageTech;

// 生成小工具初始化代碼
$initCode = SavageTech::generateWidgetInitCode($userId, [], 'usd');
```

## 10. 更多幫助

如果您在使用 SDK 時遇到問題，可以：

1. 檢查 README.md 文件
2. 查看 Laravel 和 PHP 錯誤日誌
3. 檢查瀏覽器控制台日誌
4. 聯繫技術支持團隊 