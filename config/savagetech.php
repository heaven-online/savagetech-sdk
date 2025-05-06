<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SavageTech API 憑證
    |--------------------------------------------------------------------------
    |
    | 這裡設定您的 SavageTech API 認證資訊，包括 Vendor-Id 和 Vendor-Secret
    | 這些值可以從環境變數中讀取，或者直接在這裡設定
    |
    */
    'credentials' => [
        'vendor_id' => env('SAVAGETECH_VENDOR_ID'),
        'vendor_secret' => env('SAVAGETECH_VENDOR_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SavageTech API 端點
    |--------------------------------------------------------------------------
    |
    | SavageTech API 的基本 URL
    |
    */
    'api_url' => env('SAVAGETECH_API_URL', 'https://func.rc.savagebet.gg'),

    /*
    |--------------------------------------------------------------------------
    | 請求設定
    |--------------------------------------------------------------------------
    |
    | 這裡可以配置 HTTP 請求的相關設定
    |
    */
    'http' => [
        'timeout' => env('SAVAGETECH_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('SAVAGETECH_HTTP_CONNECT_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | 預設貨幣
    |--------------------------------------------------------------------------
    |
    | 預設使用的貨幣代碼
    |
    */
    'default_currency' => env('SAVAGETECH_DEFAULT_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | 小工具設定
    |--------------------------------------------------------------------------
    |
    | SavageTech 小工具相關設定
    |
    */
    'widget' => [
        'enabled' => env('SAVAGETECH_WIDGET_ENABLED', true),
        'token_refresh_before_minutes' => env('SAVAGETECH_TOKEN_REFRESH_BEFORE_MINUTES', 10),
    ],
]; 