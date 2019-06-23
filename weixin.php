<?php
/**
 * 微信被动接口类
 */

$wechatObj = new Weixin();

class Weixin
{
    // 自定义 token
    const TOKEN = 'weixin';
    private $pdo;
    public function __construct()
    {
        $this->pdo = include 'db.php';
        if (empty($_GET["echostr"])) {
            $this->responseMsg();
        } else {
            $this->valid();
        }
    }
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        // php5.5 以前使用
        // $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        // php5.5 以后使用
        $postStr = file_get_contents('php://input');
        $this->writeLog($postStr);
        if (!empty($postStr)) {
            // xml转为obj
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msgType = $postObj->MsgType;
            $xmlStr = '';
            switch ($msgType) {
                case 'text': // 文字
                    $xmlStr = $this->sendContent($postObj, $postObj->Content);
                    break;
                case 'image': // 图片
                    $xmlStr = $this->createImg($postObj);
                    break;
                case 'event': // 事件
                    $xmlStr = $this->handlerEvent($postObj);
                    break;
                case 'voice': // 语音
                    $xmlStr = $this->handlerVoice($postObj);
                    break;
            }
            // 需要对 xmlStr 进行判断
            echo $xmlStr;
            // 记录日志
            $this->writeLog($xmlStr, 2);
        }
        
    }

    /**
     * 语音处理
     * @param object $obj
     * @return string
     */
    private function handlerVoice($obj)
    {
        $msg = $this->autoCreateMessage($obj->FromUserName, $obj->Recognition);
        // 需要对返回值进行判断
        return $this->createText($obj, $msg);
    }

    /**
     * 图灵机器人自动创建回复
     * @param string $userId 用户的 id
     * @param string $content 聊天的内容
     * @return string
     */
    public function autoCreateMessage(string $userId, string $content)
    {
        // 图灵机器人 api url
        $url = 'http://openapi.tuling123.com/openapi/api/v2';
        // 写死的格式，可根据业务不同进行调整
        $json = '{
            "reqType":0,
            "perception": {
                "inputText": {
                    "text": "' . $content . '"
                }
            },
            "userInfo": {
                "apiKey": "342717dfa65f42f480bbc077a59063bf",
                "userId": "' . md5($userId) . '"
            }
        }';
        $ret = $this->http_request($url, $json);
        // 需要对返回值进行判断
        $arr = json_decode($ret, 1);
        return $arr['results'][0]['values']['text'];
    }

    /**
     * 公众号聊天时，回复的内容
     * @param object $obj
     * @param string $content 发送的内容
     * @return string
     */
    private function sendContent($obj, $content = '')
    {
        $sql = "SELECT `media_id` FROM `material` WHERE `is_forever`=0 AND `type`=";
        /**
         * 发送图片，语音，视频时随机从数据库中回复储存的相应数据
         * 发送图文时，返回图文的内容
         * 发送 位置- 时，回复用户搜索的最近位置
         * 若是其他文字则是图灵机器人智能回复的内容
         */
        if ($content == '图片') { 
            $sql .= "'image'";
            $rows = $this->pdo->query($sql)->fetchAll();
            // 需要对返回值进行判断
            $key = array_rand($rows);
            return $this->createImg($obj, $rows[$key]['media_id']);
        } elseif ($content == '语音') {
            $sql .= "'voice'";
            $rows = $this->pdo->query($sql)->fetchAll();
            // 需要对返回值进行判断
            $key = array_rand($rows);
            return $this->createVoice($obj, $rows[$key]['media_id']);
        } elseif ($content == '视频') {
            $sql .="'video'";
            $rows = $this->pdo->query($sql)->fetchAll();
            // 需要对返回值进行判断
            $key = array_rand($rows);
            return $this->createVideo($obj, $rows[$key]['media_id']);
        } elseif ($content == '图文') {
            $data = [
                [
                    'title' => 'Top5盘点：IG仅排第三，榜首毫无悬念',
                    'description' => '历年S赛冠军实力Top第三，榜首毫无悬念',
                    'picurl' => 'https://inews.gtimg.com/newsapp_ls/0/9424677411_295195/0',
                    'url' => 'http://new.qq.com/omn/20190621/20190621A0PYDO.html',
                ],
                [
                    'title' => '历年S赛冠军实力Top5盘点悬念',
                    'description' => '力Top5盘点：IG仅排第三，榜首毫无悬念',
                    'picurl' => 'https://inews.gtimg.com/newsapp_ls/0/9424677411_295195/0',
                    'url' => 'http://new.qq.com/omn/20190621/20190621A0PYDO.html',
                ],
                [
                    'title' => '榜首毫无悬念',
                    'description' => '历年op5盘点：IG仅排第三，榜首毫无悬念',
                    'picurl' => 'https://inews.gtimg.com/newsapp_ls/0/9424677411_295195/0',
                    'url' => 'http://new.qq.com/omn/20190621/20190621A0PYDO.html',
                ]
            ];
            return $this->createNews($obj, $data);
        } elseif (strstr($content, '位置-')) {
            $openId = $obj->FromUserName;
            // 高德周边搜索api
            $surl = 'http://restapi.amap.com/v3/place/around?key=9aff99aae94f76be7bbb9f6c06379390&location=%s,%s&keywords=&s&types=010000&radius=5000&offset=20&page=1&extensions=all';
            $sql = "SELECT * FROM `location` WHERE `openid`='$openId'";
            $ret = $this->pdo->query($sql)->fetch();
            // 需要对返回值进行判断
            $longitude = $ret['longitude'];
            $latitude = $ret['latitude'];
            $keyWord = str_repeat('位置-', '', $content);
            $url = sprintf($surl, $longitude, $latitude, $keyWord);
            $json = $this->http_request($url);
            // 需要对返回值进行判断
            $arr = json_decode($json, 1);
            if (count($arr['pois']) > 0) {
                // 获取最近的信息
                $tmp = $arr['pois'][0];
                $msg = "🌟🌟🌟🌟🌟🌟\n";
                $msg .= '距离您的位置有：' . $tmp['distance'] . "米\n";
                $msg .= '名称为：' . $tmp['name'] . "\n";
                $msg .= '地址：' . $tmp['address'] . "\n";
                $msg .= "🌟🌟🌟🌟🌟🌟\n";
                return $this->createText($obj, $msg);
            } else {
                return $this->createText($obj, '没有搜索到相关服务');
            }
            
        }

        $msg = $this->autoCreateMessage($obj->FromUserName, $obj->Content);
        // 需要对返回值进行判断
        return $this->createText($obj, $msg);
    }
    
    /**
     * 创建 xml 文字信息
     * @param object $postObj mxl 对象
     * @param [type] $content 回复文字的内容
     * @return string
     */
    private function createText($postObj, $content)
    {
        $xml = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%d</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                </xml>';
        $time = time(); 
        $xmlStr = sprintf($xml, $postObj->FromUserName, $postObj->ToUserName, $time, $content);
        return $xmlStr;
    }

    /**
     * 创建 xml 语音信息
     * @param object $obj mxl 对象
     * @param string $mediaId 
     * @return string
     */
    private function createVoice($obj, $mediaId)
    {
        $xml = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%d</CreateTime>
                    <MsgType><![CDATA[voice]]></MsgType>
                    <Voice>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Voice>
                </xml>';
        $time = time();
        $xmlStr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, $mediaId);
        return $xmlStr;
    }

    /**
     * 创建 xml 视频信息
     * @param object $obj mxl 对象
     * @param string $mediaId 
     * @return string
     */
    public function createVideo($obj, $mediaId)
    {
        $xml = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%d</CreateTime>
                    <MsgType><![CDATA[video]]></MsgType>
                    <Video>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Video>
                </xml>';
        $time = time();
        $xmlStr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, $mediaId);
        return $xmlStr;
    }

    /**
     * 创建 xml 图片信息
     * @param object $obj mxl 对象
     * @param string $mediaId 
     * @return string
     */
    private function createImg($obj, $mediaId = '')
    {
        $xml = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[image]]></MsgType>
                    <Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Image>
                </xml>';
        $time = time();
        $mediaId = $mediaId ?? $obj->MediaId;
        $xmlStr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, $mediaId);
        return $xmlStr;
    }

    // 图文消息
    public function createNews($obj, array $data)
    {
        $xml = '<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>
                    %s
                    </Articles>
                </xml>';
        $item = '';
        foreach ($data as $val) {
            $item .= '<item>
                        <Title><![CDATA[' . $val['title'] . ']]></Title>
                        <Description><![CDATA[' . $val['description'] . ']]></Description>
                        <PicUrl><![CDATA[' . $val['picurl'] . ']]></PicUrl>
                        <Url><![CDATA[' . $val['url'] . ']]></Url>
                      </item>';
        }
        
        $time = time();
        $xmlStr = sprintf($xml, $obj->FromUserName, $obj->ToUserName, $time, count($data), $item);
        return $xmlStr;
    }

    /**
     * 写日志
     * @param string $data
     * @param integer $flag 标志，1 为接收，其他为发送
     * @return void
     */
    private function writeLog(string $data, $flag = 1)
    {
        $proStr = $flag == 1 ? '接收' : '发送';
        $date = date('Y-m-d H:i:s');
        $log = $proStr . '--------' . $date . PHP_EOL . $data . PHP_EOL;
        file_put_contents('wx.xml', $log, FILE_APPEND);
    }

    // 官方的方法
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
                
        $token = self::TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ) {
            return true;
        } else {
            return false;
        }
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

?>