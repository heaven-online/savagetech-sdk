<?php

namespace LuciferGaming\SavageTechSDK\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getAccessToken(string $userId, string $currency = null)
 * @method static array depositMade(string $userId, float $amount, string $currency = null)
 * @method static array betPlaced(string $userId, float $amount, float $odds, string $currency = null)
 * @method static array setCurrencies(array $currencies)
 * @method static array generateWidgetInitCode(string $userId, array $config = [], string $currency = null, string $username = null)
 * 
 * @see \LuciferGaming\SavageTechSDK\Services\SavageTechService
 */
class SavageTech extends Facade
{
    /**
     * 獲取 Facade 註冊名稱
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'savagetech';
    }
} 