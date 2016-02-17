<?php

namespace Sunkeydev\Wx\Models;

use Illuminate\Database\Eloquent\Model;
use Sunkeydev\Wx\Helpers\Wx;

/**
 * 用户授权信息类
 *
 * @author: Sunkey
 */
class WxOauth extends Model
{
    /**
     * 数据库表名称
     * @var string
     */
    protected $table = 'wx_oauth';

    /**
     * 过滤字段
     * @var array
     */
    protected $guarded = [
        'id',
        'access_token',
        'refresh_token',
        'expires_in',
        'created_at',
        'updated_at',
        'unionid',
    ];

    /**
     * 存储或更新一条用户授权信息
     * @param  string $appId     公众号ID
     * @param  string $scope     授权类型
     * @param  string $openId    用户openid
     * @param  array $oauthData  用户授权信息
     * @return bool
     */
    public function upsertOne($appId, $scope, $openId, $oauthData)
    {
        $where = [
            'openid' => $openId,
            'appid' => $appId,
            'scope' => $scope,
        ];
        $item = $this->where($where)->first();

        if (!$item) {
            $item = new self;
            $item->appid = $appId;
            $item->openid = $openId;
        }
        $item->scope = $oauthData['scope'];
        $item->access_token = $oauthData['access_token'];
        $item->refresh_token = $oauthData['refresh_token'];
        $item->expires_in = time() + $oauthData['expires_in'];

        if ($scope === Wx::SCOPE_USERINFO) {
            $item->nickname = $oauthData['nickname'];
            $item->sex = $oauthData['sex'];
            $item->language = $oauthData['language'];
            $item->city = $oauthData['city'];
            $item->province = $oauthData['province'];
            $item->country = $oauthData['country'];
            $item->headimgurl = $oauthData['headimgurl'];
            $item->privilege = json_encode($oauthData['privilege']);
            $item->unionid = isset($oauthData['unionid'])
                                ? $oauthData['unionid']:'';
        }

        $rt = $item->save();

        if (!$rt) {
            return false;
        }

        return true;
    }
    
    /**
     * 获取授权信息
     * @param  string $appId  公众号ID
     * @param  string $scope  授权类型
     * @param  string $openId 用户openid
     * @return array
     */
    public function getOne($appId, $scope, $openId)
    {
        $where = [
            'appid' => $appId,
            'scope' => $scope,
            'openid' => $openId,
        ];

        $item = $this->where($where)->first();
        if (!$item) {
            return false;
        }

        $result = $item->toArray();
        return $result;
    }

    /**
     * 过滤保护字段
     * @param  array $oauthData 授权数组
     * @return array
     */
    public function filterGuarded($oauthData)
    {
        foreach ($this->guarded as $field) {
            unset($oauthData[$field]);
        }

        return $oauthData;
    }
}