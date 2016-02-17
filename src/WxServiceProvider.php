<?php

namespace Sunkeydev\Wx;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

/**
 * 微信服务提供者
 *
 * @author: Sunkey
 */
class WxServiceProvider extends ServiceProvider
{
    /**
     * 启动器
     * @return null
     */
    public function boot()
    {
        $this->setupRoutes($this->app->router);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sunkeydev');
    } 

    /**
     * 设置路由
     * @param  Router $router 路由器
     * @return null
     */
    private function setupRoutes(Router $router)
    {
        $router->group(
            [
                'namespace' => 'Sunkeydev\Wx\Http\Controllers',
                'prefix' => 'sunkeydev/wx',
            ],
            function() {
                require __DIR__.'/Http/routes.php';
            }
        );
    }

    /**
     * 注册扩展包
     * @return null
     */
    public function register()
    {
        $this->publishes([
            __DIR__.'/../config/wxapi.php' => config_path('wxapi.php'),
            __DIR__.'/../config/wxconfig.php' => config_path('wxconfig.php'),
        ]);

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('/migrations'),
        ]);
    }
}