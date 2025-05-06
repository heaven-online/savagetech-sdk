<?php

namespace LuciferGaming\SavageTechSDK\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use LuciferGaming\SavageTechSDK\Exceptions\SavageTechException;

class SavageTechService
{
    /**
     * API 基礎 URL
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * HTTP 客戶端實例
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Vendor ID
     *
     * @var string
     */
    protected $vendorId;

    /**
     * Vendor Secret
     *
     * @var string
     */
    protected $vendorSecret;

    /**
     * 創建一個新的 SavageTech 服務實例
     *
     * @param string $vendorId
     * @param string $vendorSecret
     * @param string $apiUrl
     * @param array $httpConfig
     * @return void
     */
    public function __construct($vendorId, $vendorSecret, $apiUrl, array $httpConfig = [])
    {
        $this->vendorId = $vendorId;
        $this->vendorSecret = $vendorSecret;
        $this->apiUrl = rtrim($apiUrl, '/');
        
        $this->httpClient = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => Arr::get($httpConfig, 'timeout', 30),
            'connect_timeout' => Arr::get($httpConfig, 'connect_timeout', 10),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Vendor-Id' => $this->vendorId,
                'Vendor-Secret' => $this->vendorSecret,
            ]
        ]);
    }

    /**
     * 請求存取權杖
     *
     * @param string $userId
     * @param string|null $currency
     * @return array
     * @throws \LuciferGaming\SavageTechSDK\Exceptions\SavageTechException
     */
    public function getAccessToken(string $userId, $currency = null)
    {
        if ($currency === null) {
            $currency = config('savagetech.default_currency', 'usd');
        }

        return $this->sendRequest('POST', '/accesstokens', [
            'userId' => $userId,
            'currency' => $currency
        ]);
    }

    /**
     * 紀錄玩家存款完成事件
     *
     * @param string $userId
     * @param float $amount
     * @param string|null $currency
     * @return array
     * @throws \LuciferGaming\SavageTechSDK\Exceptions\SavageTechException
     */
    public function depositMade(string $userId, $amount, $currency = null)
    {
        if ($currency === null) {
            $currency = config('savagetech.default_currency', 'usd');
        }

        return $this->sendRequest('POST', '/depositmade', [
            'userId' => $userId,
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    /**
     * 紀錄玩家投注下注事件
     *
     * @param string $userId
     * @param float $amount
     * @param float $odds
     * @param string|null $currency
     * @return array
     * @throws \LuciferGaming\SavageTechSDK\Exceptions\SavageTechException
     */
    public function betPlaced(string $userId, $amount, $odds, $currency = null)
    {
        if ($currency === null) {
            $currency = config('savagetech.default_currency', 'usd');
        }

        return $this->sendRequest('POST', '/betplaced', [
            'userId' => $userId,
            'amount' => $amount,
            'odds' => $odds,
            'currency' => $currency
        ]);
    }

    /**
     * 設置或更新貨幣
     *
     * @param array $currencies
     * @return array
     * @throws \LuciferGaming\SavageTechSDK\Exceptions\SavageTechException
     */
    public function setCurrencies(array $currencies)
    {
        return $this->sendRequest('POST', '/currencies', [
            'currencies' => $currencies
        ]);
    }

    /**
     * 發送 HTTP 請求到 SavageTech API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \LuciferGaming\SavageTechSDK\Exceptions\SavageTechException
     */
    protected function sendRequest($method, $endpoint, array $data = [])
    {
        $endpoint = ltrim($endpoint, '/');
        $url = "{$this->apiUrl}/{$endpoint}";
        
        try {
            $options = [
                'json' => $data
            ];
            
            $response = $this->httpClient->request($method, $url, $options);
            $contents = $response->getBody()->getContents();
            $result = json_decode($contents, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SavageTechException(
                    'API 回應不是有效的 JSON 格式',
                    $response->getStatusCode()
                );
            }
            
            return $result;
        } catch (GuzzleException $e) {
            $response = null;
            $statusCode = 500;
            $contents = null;
            $errorData = null;
            
            // 只有 RequestException 及其子類才有 hasResponse 方法
            if ($e instanceof RequestException) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $statusCode = $response->getStatusCode();
                    $contents = $response->getBody()->getContents();
                    $errorData = json_decode($contents, true);
                }
            }
            
            throw new SavageTechException(
                $errorData['message'] ?? $e->getMessage(),
                $statusCode,
                $errorData
            );
        }
    }

    /**
     * 生成小工具 JavaScript 初始化代碼
     *
     * @param string $userId
     * @param array $config
     * @param string|null $currency
     * @return array 返回包含初始化代碼和 token 信息的數組
     * @throws \LuciferGaming\SavageTechSDK\Exceptions\SavageTechException
     */
    public function generateWidgetInitCode($userId, array $config = [], $currency = null)
    {
        $accessToken = $this->getAccessToken($userId, $currency);
        
        $initConfig = [
            'credentials' => [
                'vendorId' => $this->vendorId,
                'jwt' => $accessToken['jwt'],
                'pubsub' => $accessToken['pubsub']
            ]
        ];
        
        if (!empty($config)) {
            $initConfig = array_merge($initConfig, ['config' => $config]);
        }
        
        $jsonConfig = json_encode($initConfig);
        $initCode = "window.Savage.init({$jsonConfig});";
        
        // 返回包含初始化代碼和 token 信息的數組
        return [
            'init_code' => $initCode,
            'token' => $accessToken
        ];
    }
} 