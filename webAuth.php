<?php
include 'curl.php';
include 'wechat.php';
$wx = new Wechat;
// 签名
$signs = $wx->getSignPackage();
// 官方提供的 appId
$appId = 'wx2775a022ea7c4e1b';
// 官方提供的 secret
$secret = 'cd6b45587d93381b02d6343d3f682a32';

$code = $_GET['code'];
$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appId&secret=$secret&code=$code&grant_type=authorization_code";
$data = http_request($url);
// 此处应加个对 data 返回值的判断
$arr = json_decode($data, 1);
$access_token = $arr['access_token'];
$open_id = $arr['openid'];
// 获取用户信息
$userInfoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$open_id&lang=zh_CN";
$json = http_request($userInfoUrl);
$userInfo = json_decode($json, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>网页授权</title>
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <!-- 引入js -->
    <script src="/jweixin-1.2.0.js"></script>
    <script>
        wx.config({
            debug: false,
            appId: '<?php echo $appId;?>',
            timestamp: <?php echo $signs["timestamp"];?>,
            nonceStr: '<?php echo $signs["noncestr"];?>',
            signature: '<?php echo $signs["signature"];?>',
            jsApiList: [
                // 所有要调用的 API 都要加到这个列表中
                'onMenuShareAppMessage',
                'chooseImage'
            ]
        });
        wx.ready(function () {
            // 在这里调用 API
            wx.onMenuShareAppMessage({
                title: '测试-标题', // 分享标题
                desc: '分享的描述', // 分享描述
                link: 'http://8rmp96.natappfree.cc/go.php', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                imgUrl: 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/logo_redBlue_32fe2c69.png', // 分享图标
                type: 'link', // 分享类型,music、video或link，不填默认为link
                dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                success: function () {
                    // 用户点击了分享后执行的回调函数
                    alert('分享朋友成功');
                }
            }); 
        });
        function chooseImage () {
            wx.chooseImage({
                count: 1, // 默认9
                sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
                sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
                success: function (res) {
                    var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                    document.getElementById('imgs').src = localIds;
                }
            });
        }
    </script>
</head>
<body>
    <h3>用户信息</h3>
    <p><?php echo $userInfo['openid'] ?></p>
    <p><?php echo $userInfo['nickname'] ?></p>
    <p><?php echo $userInfo['sex'] ? '先生' : '女士' ?></p>
    <p><?php echo $userInfo['province'] ?></p>
    <p><?php echo $userInfo['city'] ?></p>
    <p><?php echo $userInfo['country'] ?></p>
    <p><img src="<?php echo $userInfo['headimgurl'] ?>"></p>
    <p>
        <button onclick="chooseImage()">拍照或从相机中选图</button>
        <img src="#" id="imgs">
    </p>
</body>
</html>
