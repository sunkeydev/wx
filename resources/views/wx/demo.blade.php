<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <title>微信接口demo页面(Sunkey)</title>
    <link rel="stylesheet" href="http://weui.github.io/weui/weui.css">
    <style>
        hr {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="base_oauth" style="text-align:center;">
        <div>用户openid:&nbsp;<span id="base-openid"></span></div>
    </div>
    <a href="javascript:;" class="weui_btn weui_btn_primary" id="btn-oauth-base">隐式授权</a> 
    <hr>
    <div class="user_oauth" style="text-align:center;">
        <ul>
            <li>
                <h5>用户头像</h5>
                <img id="headimgurl" src="" style="width: 100px; height: 100px">
            </li>
            <li>
                <div>用户昵称:&nbsp;<span id="nickname"></span></div>
            </li>
            <li>
                <div>用户openid:&nbsp;<span id="user-openid"></span></div>
            </li>
        </ul>
    </div>
    <a href="javascript:;" class="weui_btn weui_btn_primary" id="btn-oauth-user">显示授权</a> 
    <hr>
    <div>
        <div class="weui_cell">
            <div class="weui_cell_hd"><label for="" class="weui_label">标题</label></div>
            <div class="weui_cell_bd weui_cell_primary">
                <input class="weui_input" id="share-title" type="text" value="" placeholder="请输入分享标题">
            </div>
        </div>                    
        <div class="weui_cell">
            <div class="weui_cell_hd"><label for="" class="weui_label">内容</label></div>
            <div class="weui_cell_bd weui_cell_primary">
                <input class="weui_input" id="share-content" type="text" value="" placeholder="请输入分享内容">
            </div>
        </div>  
    </div>
    <a href="javascript:;" class="weui_btn weui_btn_primary" id="btn-share">设置分享信息</a> 
    <hr>
    <a href="javascript:;" class="weui_btn weui_btn_primary" id="btn-card">领取卡卷</a> 
    
    <script src=" http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>
    <script>

        function GetQueryString(name)
        {
             var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
             var r = window.location.search.substr(1).match(reg);
             if(r!=null)return  unescape(r[2]); return '';
        }
        $.ajax({
            url: '/sunkeydev/wx/sign/jsApi',
            type: 'GET',
            dataType: 'json',
            data: {
                url: location.href
            },
            success: function(res) {
                var config = res.data;
                config.debug = true;
                config.jsApiList = [
                    'onMenuShareTimeline',
                    'onMenuShareAppMessage',
                    'addCard'
                ];

                wx.config(config);
                wx.ready(function() {
                    alert('ready');
                });
                wx.error(function(res) {
                });
            },
            error: function(res) {

            }
        });

        // if (GetQueryString('state') == 'callback') {
        //     oauthUser();
        // }

        function oauthBase() {
            var code = GetQueryString('code');
            $.ajax({
                url: '/sunkeydev/wx/oauth/index',
                type: 'GET',
                dataType: 'json',
                data: {
                    code: code,
                    scope: 'base',
                    url: location.href
                },
                success: function(res) {
                    if (!res.errcode) {
                        $('#base-openid').text(res.openid);
                    } else if (res.errcode==4003){
                        location.href = res.data;
                    } else {
                        alert('error');
                    }
                },
                error: function(res) {
                }
            });
        }

        function oauthUser() {
            var code = GetQueryString('code');
            $.ajax({
                url: '/sunkeydev/wx/oauth/index',
                type: 'GET',
                dataType: 'json',
                data: {
                    code: code,
                    scope: 'userinfo',
                    url: location.href
                },
                success: function(res) {
                    if (!res.errcode) {
                        $('#user-openid').text(res.openid);
                        $('#nickname').text(res.nickname);
                        $('#headimgurl').attr('src', res.headimgurl);
                    } else if (res.errcode==4003){
                        location.href = res.data;
                    } else {
                        alert('error');
                    }
                },
                error: function(res) {
                }
            });            
        } 

        $('#btn-oauth-base').on('click', oauthBase);

        $('#btn-oauth-user').on('click', oauthUser);

        $('#btn-share').on('click', function() {
            var title = $('#share-title').val();
            var content = $('#share-content').val();
            var link = location.href;
            var imgUrl = 'http://wx.qlogo.cn/mmopen/y0IEgNs4HT0t2HZ5UBgkPoL1R9fHiaj4V6HiabaoAuglzaQXZmydnkIyPw5uItR9rRxpVunSwNRvRU9y6QUXEG3mnZDC0y0FGx/0';
            // 发送给朋友
            wx.onMenuShareAppMessage({
                title: title,
                desc: content,
                link: link,
                imgUrl: imgUrl,
                success: function() {
                },
                cancel: function() {
                }
            });

            // 分享朋友圈
            wx.onMenuShareTimeline({
                title: title,
                link: link,
                imgUrl: imgUrl,
                success: function() {
                },
                cancel: function() {
                }
            });
            alert('设置成功!');
        });

        $('#btn-card').on('click', function() {
            $.ajax({
                url: '/sunkeydev/wx/sign/card',
                type: 'GET',
                dataType: 'json',
                data: {
                    cardIds: ['pIlMxtylyyiTA0iarLpxf9PnNkbE']
                },
                success: function(res) {
                    var cardList = res.data;
                    wx.addCard({
                        cardList: cardList,
                        success: function(res) {
                            alert(1);
                        },
                        error: function(err) {
                            alert('error');
                        }
                    });
                },
                error: function(error) {
                }
            })
        });
    </script>
</body>
</html>