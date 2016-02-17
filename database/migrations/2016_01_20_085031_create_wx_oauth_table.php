<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWxOauthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wx_oauth', function (Blueprint $table) {
            $table->increments('id');
            $table->string('appid', 30);
            $table->string('scope', 20);
            $table->string('openid', 50);
            $table->string('access_token', 200);
            $table->string('refresh_token', 200);
            $table->integer('expires_in');
            $table->string('nickname', 50);
            $table->tinyinteger('sex');
            $table->string('language', 10);
            $table->string('city', 20);
            $table->string('province', 20);
            $table->string('country', 20);
            $table->string('headimgurl', 255);
            $table->string('privilege', 100);
            $table->string('unionid', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wx_oauth');
    }
}
