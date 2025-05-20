<?php

namespace LuciferGaming\SavageTechSDK\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LuciferGaming\SavageTechSDK\Facades\SavageTech;
use LuciferGaming\SavageTechSDK\Exceptions\SavageTechException;

class SavageTechController extends Controller
{
    /**
     * 獲取 SavageTech 小工具初始化代碼
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInitCode(Request $request)
    {
        try {
            $userId = $request->query('user_id', auth()->user()?->id);
            $currency = $request->query('currency');

            if (!$userId) {
                return response()->json(['error' => '使用者 ID 未提供'], 400);
            }

            $config = $request->query('config', []);
            if (is_string($config)) {
                $config = json_decode($config, true) ?: [];
            }

            $username = auth()->user()?->account_name;

            // 生成初始化代碼和獲取 token
            $result = SavageTech::generateWidgetInitCode($userId, $config, $currency, $username);
            $initCode = $result['init_code'];
            $token = $result['token'];
            
            // 獲取 token 刷新設定
            $refreshBeforeMinutes = config('savagetech.widget.token_refresh_before_minutes', 10);
            
            return response()->json([
                'init_code' => $initCode,
                'refresh_before_minutes' => $refreshBeforeMinutes,
                'jwt' => $token['jwt'] ?? null,
                'credentials' => [
                    'jwt' => $token['jwt'] ?? null,
                    'pubsub' => $token['pubsub'] ?? null
                ]
            ]);
        } catch (SavageTechException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'details' => $e->getResponse()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * 刷新 SavageTech 小工具 Token
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        try {
            $userId = $request->query('user_id', auth()->user()?->id);
            $currency = $request->query('currency');

            if (!$userId) {
                return response()->json(['error' => '使用者 ID 未提供'], 400);
            }

            $token = SavageTech::getAccessToken($userId, $currency);
            
            // 獲取 token 刷新設定
            $refreshBeforeMinutes = config('savagetech.widget.token_refresh_before_minutes', 10);
            
            // 確保 token 結構完整
            $jwtToken = $token['jwt'] ?? null;
            $pubsubToken = $token['pubsub'] ?? null;
            
            return response()->json([
                'jwt' => $jwtToken,
                'credentials' => [
                    'jwt' => $jwtToken,
                    'pubsub' => $pubsubToken
                ],
                'refresh_before_minutes' => $refreshBeforeMinutes
            ]);
        } catch (SavageTechException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'details' => $e->getResponse()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * 記錄玩家存款事件
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordDeposit(Request $request)
    {
        try {
            $userId = $request->input('user_id', auth()->user()?->id);
            $amount = $request->input('amount');
            $currency = $request->input('currency');

            if (!$userId || $amount === null) {
                return response()->json(['error' => '使用者 ID 和金額是必須的'], 400);
            }

            $result = SavageTech::depositMade($userId, $amount, $currency);
            
            return response()->json(['success' => true, 'data' => $result]);
        } catch (SavageTechException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $e->getResponse()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * 記錄玩家投注事件
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordBet(Request $request)
    {
        try {
            $userId = $request->input('user_id', auth()->user()?->id);
            $amount = $request->input('amount');
            $odds = $request->input('odds');
            $currency = $request->input('currency');

            if (!$userId || $amount === null || $odds === null) {
                return response()->json(['error' => '使用者 ID、金額和賠率是必須的'], 400);
            }

            $result = SavageTech::betPlaced($userId, $amount, $odds, $currency);
            
            return response()->json(['success' => true, 'data' => $result]);
        } catch (SavageTechException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $e->getResponse()
            ], $e->getCode() ?: 500);
        }
    }
} 