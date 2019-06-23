<?php
/**
 * 微信主动接口类
 */
class Wechat
{
    // 官方给的 appid
    const APPID = 'wx2775a022ea7c4e1b';
    // 官方给的 appsecret
    const APPSECRET = 'cd6b45587d93381b02d6343d3f682a32';

    /**
     * 获取 token
     * @return boolean
     */
    private function getAccssToken()
    {
        $cacheFile = __DIR__ . '/' . self::APPID . '_accessToken.cache';
        if (is_file($cacheFile) && filemtime($cacheFile) + 7000 > time()) {
            $accessToken = file_get_contents($cacheFile);
            return $accessToken;
        }
        $surl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
        $url = sprintf($surl, self::APPID, self::APPSECRET);
        $json = $this->http_request($url);
        $arr = json_decode($json, true);
        if (empty($arr['errcode'])) {
            file_put_contents($cacheFile, $arr['access_token']);
            return $arr['access_token'];
        }
        return false;
    }

    /**
     * 创建底部菜单
     * @return void
     */
    public function createMenu($menArr = array())
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getAccssToken();
        $json = json_encode($menArr, JSON_UNESCAPED_UNICODE);
        $res = $this->http_request($url, $json);
        // 需要对返回值判断
        echo $res;
    }

    /**
     * 删除自定义菜单
     */
    public function deleteMenu()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $this->getAccssToken();
        return $this->http_request($url);
    }

    /**
     * 上传素材
     * @param string $type 类型，图片，音频，视频
     * @param string $file 上传的文件
     * @param integer $flag 标志，0 为临时素材，1 为永久素材
     * @return void
     */
    public function upFile(string $type = 'image', string $file = '', int $flag = 0)
    {
        if ($flag == 0) {
            $surl = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s';
        } else {
            $surl = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=%s&type=%s';
        }
        $url = sprintf($surl, $this->getAccssToken(), $type);
        $json = $this->http_request($url, [], $file);
        // 需要对返回值判断
        $arr = json_decode($json, true);
        return $arr;
    }

    // 获取临时素材
    public function getUpLoad()
    {
        $surl = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s';
        $media_id = 'VgiYcW5iRD79O1okQonG8UBRowRnWGgMmXql6u4O__Pmq9Y27UMsyOiHxZKulWr_';
        $url = sprintf($surl, $this->getAccssToken(), $media_id);
        $file = $this->http_request($url);

        header('content-type:image/jpg');
        echo $file;
    }

    // 获取素材列表
    public function getSucaiList()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=' . $this->getAccssToken();
        $data = $this->http_request($url);
        // 需要对返回值判断
        echo $data;
    }

    /**
     * 生成场景二维码
     * @param integer $flag 标志，0 为临时二维码；1 为永久二维码
     * @return string
     */
    public function getQrCode($flag = 0)
    {
        if ($flag == 0) {
            $filename = 100;
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getAccssToken();
            // 需要请求接口时的数据格式，需json，写死的写法
            $json = '{"expire_seconds": 2592000, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $filename . '}}}';
        } else {
            $filename = 123;
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getAccssToken();
            // 需要请求接口时的数据格式，需json
            $json = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": ' . $filename . '}}}';
        }
        
        $ret = $this->http_request($url, $json);
        // 需要对返回值判断
        $arr = json_decode($ret, 1);
        $ticket = $arr['ticket'];
        // 通过ticket换取二维码
        $qrCodeUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);
        $res = $this->http_request($qrCodeUrl);
        // 需要对返回值判断
        file_put_contents($filename . '.jpg', $res);
        return $filename . '.jpg';
    }

    // 根据openid获取用户信息
    public function getUserInfo($openId)
    {
        $surl = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN';
        $url = sprintf($surl, $this->getAccssToken(), $openId);
        $res = $this->http_request($url);
        // 需要对返回值判断
        $arr = json_decode($res, 1);
        var_dump($arr);
    }

    /**
     * 客服发消息
     * @param string $openId 
     * @param string $mes 发送的内容
     * @return json
     */
    public function kefuSend(string $openId, string $mes)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->getAccssToken();
        $send = '{
            "touser":"'. $openId .'",
            "msgtype":"text",
            "text":
            {
                "content":"' . $mes . '"
            }
        }';
        // 需要对返回值判断$this->http_request()
        return $this->http_request($url, $send);
    }

    /**
     * 群发接口
     * @param string $content 发送信息的内容
     * @return json
     */
    public function sendAll(string $content)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' . $this->getAccssToken();
        $text = '{
            "filter":{
               "is_to_all":true
            },
            "text":{
               "content":"' . $content . '"
            },
             "msgtype":"text"
         }';
         // 需要对返回值判断$this->http_request()
         return $this->http_request($url, $text);
    }

    /**
     * jsApi ticket获取
     * @return string
     */
    public function getJsApiTicket()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $this->getAccssToken() . '&type=jsapi';
        $json = $this->http_request($url);
        // 需要对返回值判断$this->http_request()
        $arr = json_decode($json, 1);
        return $arr['ticket'];
    }

    /**
     * 得到随机字符串
     * @param integer $length
     * @return string
     */
    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取当前url地址
     * @return string
     */
    public function getCurrentUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        return "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * jssdk 签名
     * @return array
     */
    public function getSignPackage()
    {
        // 随机字符串
        $arr['noncestr'] = $this->createNonceStr();
        // 凭据
        $arr['jsapi_ticket'] = $this->getJsApiTicket();
        // 当前时间
        $arr['timestamp'] = time();
        // 当前url
        $arr['url'] = $this->getCurrentUrl();
        asort($arr);
        $string = "jsapi_ticket=" . $arr['jsapi_ticket'] . "&noncestr=" . $arr['noncestr'] . "&timestamp=" . $arr['timestamp'] . "&url=" . $arr['url'];
        // key1=value1&key2=value2…
        // $str = http_build_query($arr);
        $arr['signature'] = sha1($string);
        return $arr;
    }

    /**
     * 封装 curl 接口
     * @param string $url 请求地址
     * @param array|json $params 若为 post 时，要发送的数据
     * @param string $file 需要上传的文件
     * @return void
     */
    private function http_request(string $url, $params = [], string $file = '')
    {
        if (!empty($file)) {
            $params['media'] = new CURLFile($file);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'chrome');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if ($params) {
            // 开启post请求
			curl_setopt($ch, CURLOPT_POST, 1);
			// 发送过去的数据
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $data = curl_exec($ch);
        if (curl_getinfo($ch)['http_code'] != 200) {
            echo curl_error($ch);
            $data = false;
        }
        curl_close($ch);
        return $data;
    }
}
// $arr = include 'config.php';
// $wx = (new Wechat)->createMenu($arr['menu']);
// $wx = (new Wechat)->deleteMenu();
// (new Wechat)->getUpLoad();
// (new Wechat)->getSucaiList();
// var_dump($wx);
// echo "<img src='" . (new Wechat)->getQrCode(1) . "'>";
// (new Wechat)->getUserInfo('obFOv5odfn0weNiEncKdxVUbIcf4');
// (new Wechat)->kefuSend('obFOv5odfn0weNiEncKdxVUbIcf4', '鸡你太美');
// (new Wechat)->sendAll('我就是群发的消息');
// var_dump((new Wechat)->getSignPackage());
