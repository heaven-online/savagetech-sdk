<?php

use Illuminate\Support\Facades\Route;
use LuciferGaming\SavageTechSDK\Http\Controllers\SavageTechController;

/*
|--------------------------------------------------------------------------
| SavageTech API 路由
|--------------------------------------------------------------------------
|
| 這裡是 SavageTech SDK 的預設路由。您可以將這些路由複製到您
| 自己的 routes/web.php 或 routes/api.php 文件中，視需要調整前綴、中間件等
|
*/

// API 路由
Route::prefix('api/savage-tech')->group(function () {
    // 小工具相關路由
    Route::get('/init', [SavageTechController::class, 'getInitCode']);
    Route::get('/refresh-token', [SavageTechController::class, 'refreshToken']);
    
    // 玩家事件記錄路由
    Route::post('/deposit', [SavageTechController::class, 'recordDeposit']);
    Route::post('/bet', [SavageTechController::class, 'recordBet']);
}); 