<?php

namespace LuciferGaming\SavageTechSDK\Exceptions;

use Exception;

class SavageTechException extends Exception
{
    /**
     * API 回應數據
     *
     * @var array|null
     */
    protected $response;

    /**
     * 創建一個新的 SavageTech 異常實例
     *
     * @param string $message
     * @param int $code
     * @param array|null $response
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct($message = "", $code = 0, $response = null, \Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 獲取 API 回應數據
     *
     * @return array|null
     */
    public function getResponse()
    {
        return $this->response;
    }
} 