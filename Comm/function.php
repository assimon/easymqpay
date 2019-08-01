<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2019-05-06
 * Time: 18:21
 */

/**
 * json格式返回消息
 * @param int $code 状态码
 * @param string $msg 消息
 * @param array $data 数据
 * @return false|string
 */
function easygateway_json_response($code = 200, $msg = '', $data = [])
{
    $jsonData = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];
    return json_encode($jsonData);

}