<?php

namespace Sunkeydev\Wx\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Config;
use Validator;
use Request;
use Response;
use Sunkeydev\Wx\Helpers\Wx;

/**
 * 微信签名控制器
 *
 * @author: Sunkey
 */
class SignController extends Controller
{
    /**
     * 微信分享签名接口
     * @return json
     */
    public function jsApi()
    {
        $url = Request::input('url');
        if (($hash=strpos($url, '#')) !== false) {
            $url = substr($url, 0, $hash);
        }

        $validator = Validator::make(Request::input(), [
            'url' => 'required|url',
        ]);
        if ($validator->fails()) {
            Response::json(['errcode'=>4004]);
        }

        $wxConfig = Config::get('wxconfig');
        $wx = Wx::getInstance($wxConfig['appid'], $wxConfig['secret']);
        $timestamp = time();
        $nonceStr = Str::quickRandom(32);
        $signature = $wx->getJsApiSignature($url, $timestamp, $nonceStr);

        $jsApiConfig = [
            'appId' => $wxConfig['appid'],
            'timestamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
        ];

        return Response::json(['errcode'=>0, 'data'=>$jsApiConfig]);
    }

    /**
     * 微信卡卷签名
     * @return json
     */
    public function card()
    {
        $validator = Validator::make(Request::input(), [
            'cardIds' => 'required|array',
        ]);
        if ($validator->fails()) {
            return Response::json($validator->errors()->first(), 400);
        }

        $cardIds = Request::input('cardIds');
        $wxConfig = Config::get('wxconfig');

        $wx = Wx::getInstance($wxConfig['appid'], $wxConfig['secret']);

        $cardList = [];
        foreach ($cardIds as $cardId) {
            $cardId = trim($cardId);
            $timestamp = time();
            $nonceStr = Str::quickRandom(32);
            $signature = $wx->getCardSignature($cardId, $timestamp, $nonceStr);
            if (!$signature) {
                return Response::json(['errcode'=>5001, 'errmsg'=>'签名失败!']);
            }

            $cardConfig = [
                'cardId' => $cardId,
                'cardExt' => json_encode([
                    'signature' => $signature,
                    'timestamp' => $timestamp,
                    'nonce_str' => $nonceStr,
                ]),
            ];

            array_push($cardList, $cardConfig);
        }

        return Response::json(['errcode'=>0, 'data'=>$cardList]);
    }
}