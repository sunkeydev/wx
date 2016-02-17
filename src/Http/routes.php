<?php
/**
 * 微信功能包路由配置
 *
 * @author: Sunkey
 */

// 微信签名路由组
Route::group(
    [
        'prefix' => 'sign',
    ],
    function() {
        Route::get('/jsApi', 'SignController@jsApi');
        Route::get('/card', 'SignController@card');
    }
);

// 微信授权路由组
Route::group(
    [
        'prefix' => 'oauth',
    ],
    function() {
        Route::get('index', 'OauthController@index');
    }
);

// 微信支付路由组
Route::group(
    [
        'prefix' => 'payment',
    ],
    function() {

    }
);

// 默认的demo页面
Route::get('/index', function() {
    return view('sunkeydev::wx.demo');
});