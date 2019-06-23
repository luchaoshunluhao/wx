<?php
$appId = 'wx2775a022ea7c4e1b';
// 此处 8rmp96.natappfree.cc 的 url 地址是服务器的地址
$redirect_uri = urlencode('http://8rmp96.natappfree.cc/webAuth.php');
$surl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=111#wechat_redirect";
$url = sprintf($surl, $appId, $redirect_uri);
// 跳转到授权页面
header('location:' . $url);