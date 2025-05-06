# SavageTech SDK for Laravel

這個套件為 Laravel 應用提供了與 SavageTech 遊戲化 API 整合的簡單方法。

## 安裝

使用 Composer 安裝套件：

```bash
composer require lucifergaming/savagetech-sdk
```

## 設定

安裝後，發布配置檔案：

```bash
php artisan vendor:publish --tag=savagetech-config
```

這會創建一個 `config/savagetech.php` 檔案，您可以在此配置 SavageTech API 的相關設定。

## 環境變數

在您的 `.env` 檔案中添加以下設定：

```dotenv
SAVAGETECH_VENDOR_ID=your-vendor-id
SAVAGETECH_VENDOR_SECRET=your-vendor-secret
SAVAGETECH_API_URL=https://api.savagetech.com
SAVAGETECH_TOKEN_REFRESH_BEFORE_MINUTES=10
```

## 基本使用

### 使用 Facade

```php
use LuciferGaming\SavageTechSDK\Facades\SavageTech;

// 獲取 Access Token (可指定貨幣)
$token = SavageTech::getAccessToken('user-id', 'usd');

// 記錄玩家存款事件
SavageTech::depositMade('user-id', 100, 'usd');

// 記錄玩家投注事件
SavageTech::betPlaced('user-id', 100, 1.5, 'usd');

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
SavageTech::setCurrencies($currencies);

// 生成小工具初始化代碼 (可指定貨幣)
$initCode = SavageTech::generateWidgetInitCode('user-id', [], 'usd');
```

### 直接注入服務

```php
use LuciferGaming\SavageTechSDK\Services\SavageTechService;

class GameController extends Controller
{
    protected $savageTech;
    
    public function __construct(SavageTechService $savageTech)
    {
        $this->savageTech = $savageTech;
    }
    
    public function recordBet(Request $request)
    {
        try {
            $result = $this->savageTech->betPlaced(
                $request->user()->id,
                $request->amount,
                $request->odds,
                $request->currency
            );
            
            return response()->json(['success' => true, 'data' => $result]);
        } catch (SavageTechException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode());
        }
    }
}
```

## SavageTech 小工具整合

在您的 Blade 視圖中整合 SavageTech 小工具：

```blade
<div id="savage-widget"></div>

<script>
    // 初始化 SavageTech 代理
    document.addEventListener('DOMContentLoaded', function() {
        // 從後端獲取初始化代碼
        fetch('/api/savage-tech-init?user_id={{ auth()->id() }}&currency=usd')
            .then(response => response.json())
            .then(data => {
                // 執行初始化代碼
                eval(data.init_code);
                
                // 處理 Token 過期
                window.Savage.onTokenExpiration(() => {
                    // 重新獲取 Token
                    fetch('/api/savage-tech-refresh-token?user_id={{ auth()->id() }}&currency=usd')
                        .then(response => response.json())
                        .then(data => {
                            // 更新憑證
                            window.Savage.setCredentials(data.credentials);
                        });
                });
            });
    });
</script>
```

### 使用 SavageTechHelper 類

使用我們提供的 SavageTechHelper 類可以更方便地管理 Token 的自動刷新：

```blade
<div id="savage-widget"></div>

<script src="{{ asset('vendor/savagetech/savage-tech-helper.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 創建 SavageTechHelper 實例
        const savageTech = new SavageTechHelper({
            userId: '{{ auth()->id() }}',
            currency: 'usd',
            refreshBeforeMinutes: {{ config('savagetech.widget.token_refresh_before_minutes', 10) }},
            initEndpoint: '/api/savage-tech-init',
            refreshEndpoint: '/api/savage-tech-refresh-token',
            onError: function(error) {
                console.error('SavageTech 錯誤:', error);
            }
        });
        
        // 初始化小工具 (將自動處理 Token 刷新)
        savageTech.init()
            .then(function(data) {
                console.log('SavageTech 小工具已初始化');
            })
            .catch(function(error) {
                console.error('初始化失敗', error);
            });
    });
</script>
```

對應的路由和控制器：

```php
// 路由
Route::get('/api/savage-tech-init', 'SavageTechController@getInitCode');
Route::get('/api/savage-tech-refresh-token', 'SavageTechController@refreshToken');

// 控制器
public function getInitCode(Request $request)
{
    $userId = $request->user()->id;
    $currency = $request->query('currency'); // 獲取貨幣參數
    $initCode = SavageTech::generateWidgetInitCode($userId, [], $currency);
    
    // 返回初始化代碼和刷新設定
    return response()->json([
        'init_code' => $initCode,
        'refresh_before_minutes' => config('savagetech.widget.token_refresh_before_minutes', 10)
    ]);
}

public function refreshToken(Request $request)
{
    $userId = $request->user()->id;
    $currency = $request->query('currency'); // 獲取貨幣參數
    $token = SavageTech::getAccessToken($userId, $currency);
    
    // 返回憑證和刷新設定
    return response()->json([
        'credentials' => [
            'jwt' => $token['jwt'],
            'pubsub' => $token['pubsub']
        ],
        'refresh_before_minutes' => config('savagetech.widget.token_refresh_before_minutes', 10)
    ]);
}
```

## 異常處理

所有的 API 調用都可能拋出 `SavageTechException`，您應該適當地處理這些異常：

```php
use LuciferGaming\SavageTechSDK\Exceptions\SavageTechException;

try {
    $result = SavageTech::betPlaced('user-id', 100, 1.5, 'usd');
} catch (SavageTechException $e) {
    // 獲取錯誤信息
    $message = $e->getMessage();
    
    // 獲取 HTTP 狀態碼
    $statusCode = $e->getCode();
    
    // 獲取完整的 API 響應（如果有）
    $response = $e->getResponse();
    
    // 記錄錯誤
    Log::error('SavageTech API 錯誤', [
        'message' => $message,
        'code' => $statusCode,
        'response' => $response
    ]);
}
```

## 測試

我們提供了全面的測試套件，包括單元測試、功能測試和整合測試，以確保套件的可靠性和正確性。詳細說明請參考 [TESTING.md](TESTING.md) 文件。

### 運行測試

```bash
# 安裝開發依賴
composer install --dev

# 運行所有 PHP 測試
./vendor/bin/pest

# 運行 JavaScript 測試
npm install
npm test
```

### 測試特點

- **單元測試**：針對服務類和異常處理的獨立測試
- **功能測試**：驗證控制器和路由的正確性（需要 Laravel 環境）
- **整合測試**：與真實 API 進行交互的測試（需要有效的 API 憑證）
- **JavaScript 測試**：測試前端 Token 刷新和 API 交互功能

這些測試確保了整個套件在不同情況下的正確行為，從而提高了代碼質量和可靠性。

## 授權

此套件使用 MIT 授權發布。 