<?php

namespace Sunkeydev\Wx\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Config;
use Request;
use Response;
use Validator;
use Sunkeydev\Wx\Helpers\Wx;
use Sunkeydev\Wx\Models\WxConfig;
use Sunkeydev\Wx\Support\Arr;

/**
 * 微信授权控制器
 *
 * @author: Sunkey
 */
class OauthController extends Controller
{
    /**
     * 微信授权方法
     * @return json
     */
    public function index()
    {
        $openid = Request::input('openid');
        $code = Request::input('code');
        $scope = Request::input('scope') ?: Wx::SCOPE_BASE;

        $wxConfig = Config::get('wxconfig');

        $wxHelper = Wx::getInstance($wxConfig['appid'], $wxConfig['secret'], $scope);
        if ($openid) {
            $oauthData = $wxHelper->oauthOpenid($openid);
            if ($oauthData) {
                return Arr::camelKey($oauthData);
            }
        }

        if ($code) {
            $oauthData = $wxHelper->oauthCode($code);
            if ($oauthData) {
                return Arr::camelKey($oauthData);
            }
        }
        $url = Request::input('url');
        if (!$url) {
            Response::json(['errcode'=>4004]);
        }
        $jumpUrl = $wxHelper->getJumpUrl($url);

        return Response::json(['errcode'=>4003, 'data'=>$jumpUrl]);
    }
}