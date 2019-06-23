<?php
function http_request(string $url, $params = [], string $file = '')
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