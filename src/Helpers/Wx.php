<?php

namespace Sunkeydev\Wx\Helpers;

use GuzzleHttp\Client as HttpClient;
use Config;
use Cache;
use Sunkeydev\Wx\Models\WxOauth;

/**
 * 微信授权帮助类
 * (access_token和js_ticket只有2小时有效期)
 *
 * 客户端接口:
 * 1、获取openid(隐式)
 * 2、获取授权信息(昵称，头像)(显示)
 * 3、地址签名参数(需要关注access_token的有效期)
 * 4、支付签名参数
 *
 * @author: Sunkey
 */
class Wx
{
    /**
     * 微信实例集合
     * @var array
     */
    private static $instances = [];

    /**
     * 公众号appid
     * @var string
     */
    private $appid = '';

    /**
     * 公众号secret
     * @var string
     */
    private $secret = '';

    /**
     * 授权类型
     * @var string
     */
    // 隐式授权
    const SCOPE_BASE = 'snsapi_base';
    // 显示授权
    const SCOPE_USERINFO = 'snsapi_userinfo';

    /**
     * JS签名类型
     */
    // JSAPI签名
    const TICKET_JSAPI = 'jsapi';
    // 卡卷签名
    const TICKET_CARD = 'wx_card';

    /**
     * 过期时间误差修正
     */
    const DEVIATION_TIME = 60;

    /**
     * 默认授权类型
     * @var string
     */
    private $scope = self::SCOPE_BASE;


    /**
     * 构造函数
     * @param  string $appid     公众号appid
     * @param  string $secret    公众号secret
     * @return null
     */
    private function __construct($appid, $secret, $scope=self::SCOPE_BASE)
    {
        $this->appid = $appid;
        $this->secret = $secret;
        $this->scope = $scope;
        if (strpos($this->scope, 'snsapi_') !== 0) {
            $this->scope = 'snsapi_' . $this->scope;
        }
    }

    /**
     * 获取微信实例
     * @param  string $appId     公众号appid
     * @param  string $appSecret 公众号appSecret
     * @return self
     */
    public static function getInstance($appid, $secret, $scope=Wx::SCOPE_BASE)
    {
        $instanceKey = md5($appid.$secret);

        if (isset(self::$instances[$instanceKey])) {
            return self::$instances[$instanceKey];
        }

        $instances[$instanceKey] = new self($appid, $secret, $scope);

        return $instances[$instanceKey];
    }

    /**
     * 获取授权跳转url
     * @param  string $url 前端跳转链接
     * @return string
     */
    public function getJumpUrl($url)
    {
        // 剔除fregment,不然微信的code会添加到fregment里面
        $url = preg_replace('/#.*/', '', $url);

        $urlArr = parse_url($url);
        if (isset($urlArr['query'])) {
            parse_str($urlArr['query'], $queryArr);
            unset($queryArr['state']);
            unset($queryArr['code']);

            $host = substr($url, 0, strpos($url, '?'));
            $query = http_build_query($queryArr);
            $url = $host.'?'.$query;
        }

        $params = array(
            'appid' => $this->appid,
            'redirect_uri' => $url,
            'response_type' => 'code',
            'scope' => $this->scope,
            'state' => 'callback',
        );

        $query = http_build_query($params);
        $jumpUrl = Config::get('wxapi')['oauth']['authorize'] . '?' . $query;

        return $jumpUrl;
    }

    /**
     * 根据code获取授权信息
     * @param  string $code 用户code
     * @return array
     */
    public function oauthCode($code)
    {
        if (!$code) {
            return false;
        }

        $query = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $url = Config::get('wxapi')['oauth']['access_token'];

        $http = new HttpClient;
        $res = $http->request('GET', $url, [
            'query' => $query,
            'timeout' => 3,
        ]);
        $oauthData = json_decode($res->getBody(), true);
        if (!$oauthData) {
            return false;
        }

        if (isset($oauthData['errcode'])) {
            return false;
        }

        // 如果是显示授权，则获取用户信息
        if ($this->scope === self::SCOPE_USERINFO) {
            $oauthData = $this->oauthUserInfo($oauthData);
        }

        $wxOauth = new WxOauth;
        $rt = $wxOauth->upsertOne($this->appid, $this->scope, $oauthData['openid'], $oauthData);
        if (!$rt) {
            return false;
        }

        $oauthData = $wxOauth->filterGuarded($oauthData);

        return $oauthData;
    }

    /**
     * 根据openid获取授权信息
     * @param  string $openid 用户openid
     * @return array
     */
    public function oauthOpenid($openid)
    {
        if (!$openid) {
            return false;
        }

        $wxOauth = new wxOauth;
        $oauthData = $wxOauth->getOne($this->appid, $this->scope, $openid);
        if (!$oauthData) {
            return false;
        }

        if ($oauthData['expires_in'] - self::DEVIATION_TIME > time()) {
            $oauthData = $wxOauth->filterGuarded($oauthData);
            return $oauthData;
        }

        $oauthData = $this->refreshAccessToken($oauthData);
        if (!$oauthData) {
            return false;
        }

        // 显示授权则刷新用户信息
        if ($this->scope === self::SCOPE_USERINFO) {
            $oauthData = $this->oauthUserInfo($oauthData);
        }

        $rt = $wxOauth->upsertOne($this->appid, $this->scope, $oauthData['openid'], $oauthData);
        if (!$rt) {
            return false;
        }

        $oauthData = $wxOauth->filterGuarded($oauthData);

        return $oauthData;
    }

    /**
     * 获取用户授权扩展信息
     * @param  array $oauthData 用户授权基本信息
     * @return array
     */
    private function oauthUserInfo($oauthData)
    {
        $query = [
            'access_token' => $oauthData['access_token'],
            'openid' => $oauthData['openid'],
            'lang' => 'zh_CN',
        ];
        $url = Config::get('wxapi')['sns']['userinfo'];

        $http = new HttpClient;
        $res = $http->get($url, ['query' => $query]);
        $userInfo = json_decode($res->getBody(), true);
        if (!$userInfo) {
            return false;
        }

        if (isset($userInfo['errcode'])) {
            return false;
        }

        unset($userInfo['openid']);

        $oauthData += $userInfo;

        return $oauthData;
    }

    /**
     * 刷新accessToken
     * @param  array $oauthData 授权信息
     * @return array
     */
    private function refreshAccessToken($oauthData)
    {
        $query = [
            'appid' => $this->appid,
            'grant_type' => 'refresh_token',
            'refresh_token' => $oauthData['refresh_token'],
        ];
        $url = Config::get('wxapi')['oauth']['refresh_token'];

        $http = new HttpClient;
        $res = $http->request('GET', $url, [
            'query' => $query,
            'timeout' => 3
        ]);
        $resData = json_decode($res->getBody(), true);
        if (!$resData) {
            return false;
        } 

        if (isset($resData['errcode'])) {
            return false; 
        }

        unset($resData['openid']);
        unset($resData['scope']);
        $oauthData = array_merge($oauthData, $resData);

        return $oauthData;
    }

    /**
     * 获取JS接口签名
     * @param  int $timestamp 时间戳
     * @param  string $nonceStr  随机字符串
     * @param  string $url      签名链接
     * @return string
     */
    public function getJsApiSignature($url, $timestamp, $nonceStr)
    {
        $jsTicket = $this->getGlobalJsTicket(self::TICKET_JSAPI);
        if (!$jsTicket) {
            return false;
        }
        $params = [
            'jsapi_ticket' => $jsTicket,
            'noncestr' => $nonceStr,
            'timestamp' => $timestamp,
            'url' => $url,
        ];
        ksort($params, SORT_STRING);

        $paramArr = []; 
        foreach ($params as $key => $val) {
            $kvStr = implode('=', [$key, $val]);
            array_push($paramArr, $kvStr);
        }
        $paramStr = implode('&', $paramArr);

        $signature = sha1($paramStr);

        return $signature;
    }

    /**
     * 获取卡卷签名
     * @param  string $cardId    卡卷ID
     * @param  int $timestamp 时间戳
     * @return string
     */
    public function getCardSignature($cardId, $timestamp, $nonceStr)
    {
        $cardTicket = $this->getGlobalJsTicket(self::TICKET_CARD);
        if (!$cardTicket) {
            return false;
        }

        $paramArr = [
            $cardTicket,
            $cardId,
            $timestamp,
            $nonceStr,
        ];

        sort($paramArr, SORT_STRING);
        $paramStr = implode($paramArr);

        $signature = sha1(implode($paramArr));

        return $signature;
    }

    /**
     * 获取JS密钥
     * @param  string $type 密钥类型
     * @return string
     */
    private function getGlobalJsTicket($type=self::TICKET_JSAPI, $replay=false)
    {
        $cacheKey = 'js_ticket_'.$type.'_'.$this->appid;
        $jsTicket = Cache::get($cacheKey);
        if ($jsTicket) {
            return $jsTicket;
        }

        $accessToken = $this->getGlobalAccessToken($replay);
        if (!$accessToken) {
            return false;
        }

        $query = [
            'access_token' => $accessToken,
            'type' => $type,
        ];
        $query = http_build_query($query);

        $apiUrl = Config::get('wxapi')['cgi-bin']['jsticket'];
        $url = $apiUrl . '?' . $query;

        $client = new HttpClient; 
        $res = $client->request('GET', $url, [
            'timeout' => 3,
        ]);
        $resData = json_decode($res->getBody(), true);
        if (!$resData) {
            return false;
        }

        if (!empty($resData['errcode'])) {
            // access_token过期
            if (!$replay && $resData['errcode'] == '40001') {
                $jsTicket = $this->getGlobalJsTicket($type, true);
                return $jsTicket;
            } else {
                return false;
            }
        }

        $jsTicket = $resData['ticket'];
        Cache::put($cacheKey, $jsTicket, 120);

        return $jsTicket;
    }

    /**
     * 获取全局access_token
     * @return string
     */
    private function getGlobalAccessToken($refresh=false)
    {
        $cacheKey = 'access_token_'.$this->appid;

        if ($refresh) {
            Cache::forget($cacheKey);
        } else {
            $accessToken = Cache::get($cacheKey);
            if ($accessToken) {
                return $accessToken;
            }
        }

        $query = [
            'grant_type' => 'client_credential',
            'appid' => $this->appid,
            'secret' => $this->secret,
        ];
        $query = http_build_query($query);

        $apiUrl = Config::get('wxapi')['cgi-bin']['access_token'];
        $url = $apiUrl . '?' .$query;

        $http = new HttpClient;
        $res = $http->request('GET', $url, [
            'query' => $query,
            'timeout' => 3
        ]);
        $resData = json_decode($res->getBody(), true);
        if (!$resData || isset($resData['errcode'])) {
            return false;
        } 

        $accessToken = $resData['access_token'];
        Cache::put($cacheKey, $accessToken, 120);

        return $accessToken;
    }
}
