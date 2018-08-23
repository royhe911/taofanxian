<?php

include 'lanewechat.php';

\LaneWeChat\Core\AccessToken::getAccessToken();

exit;
$redirect_uri = 'http://yf.yipingames.com/getCode.php';
\LaneWeChat\Core\WeChatOAuth::getCode($redirect_uri, $state=1, $scope='snsapi_userinfo');

/*//scope=snsapi_base 实例
\LaneWeChat\Core\UserManage::getUserInfo($_SESSION['openid']);
$appid='wx99d1afda1c8ddbf9';
$redirect_uri = urlencode ( 'http://yf.yipingames.com//getCode.php' );
$url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
header("Location:".$url);*/