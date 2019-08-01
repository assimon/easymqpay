<?php
namespace App\Controller;

use App\Lib\EasyGatewayCore;
use GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use App\Lib\Cookie;

class Alipay
{
    // 心跳url
    const PONG_URL = 'https://enterpriseportal.alipay.com/portal/navload.json?t=';

    // 通道1
    const CHANNEL_ONE = 'https://mbillexprod.alipay.com/enterprise/tradeListQuery.json';

    // 通道2
    const CHANNEL_TWO = 'https://mbillexprod.alipay.com/enterprise/fundAccountDetail.json';

    const ALIPAY_NEW_ORDERS = 'ALIPAY_NEW_ORDERS_';

    /**
     * 支付宝网页心跳
     */
    public static function pong($cookie = '')
    {
        var_dump('支付宝心跳开始..');
        $aliPong = new Client();
        // 请求参数
        $requstData = [
            'timeout' => 10,
            'headers' => [
                'Cookie' => $cookie,
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json, text/javascript',
                'Referer' => 'https://mrchportalweb.alipay.com/user/home.htm',
                'Origin' => 'https://mbillexprod.alipay.com',
                'Connection' => 'keep-alive',
            ],
            'body' => 'action=loadEntInfo'
        ];
        try {
            $aliPongReturn = $aliPong->request('POST', self::PONG_URL . time() * 1000, $requstData)->getBody()->getContents();
            $aliPongReturn = iconv('GBK', 'UTF-8', $aliPongReturn);
            $aliPongReturn = json_decode( $aliPongReturn, true);
        } catch (GuzzleException $exception) {
            echo $exception->getMessage();
        }
        //$aliPongReturn = json_decode(, true);
        if (!$aliPongReturn || !isset($aliPongReturn['navResult'])) {
            var_dump('支付宝cookie失效！');
        }
        // 销毁对象
        unset($aliPong);
        var_dump('支付宝心跳结束，cookie正常');
    }

    public static function getNewOrderList($cookie = '')
    {
        $orderList = self::channelOne($cookie) ? self::channelOne($cookie) : self::channeTwo($cookie);
        if (!$orderList) {
            var_dump('无法获取订单，可能是心跳过快，服务异常~');
            return ;
        }
        //var_dump(json_encode($orderList));
        date_default_timezone_set('Asia/Shanghai');
        // 上载最新订单到服务器
        $nowtime = time();
        $success_time = EasyGatewayCore::$sysConf->get('order_success_time');
        if(empty($orderList['result']['detail'])) {
            var_dump('暂无数据');
            return;
        }
        foreach ($orderList['result']['detail'] as $val) {
            if (
                (isset($val['direction']) == '卖出' && isset($val['tradeFrom']) == '外部商户' && strtotime($val['gmtCreate']) > ($nowtime - $success_time * 60)) ||
                (isset($val['signProduct']) == '转账收款码' && isset($val['accountType']) == '交易' && strtotime($val['tradeTime']) > ($nowtime - $success_time * 60) )
            ){
                if(!isset($val['totalAmount'])) $val['totalAmount'] = $val['tradeAmount'];
                    // 将订单信息载入redis  方便服务器上载订单数据
                EasyGatewayCore::$rediscon->setex(self::ALIPAY_NEW_ORDERS . $val['totalAmount'],  $success_time*60, json_encode($val));
            }
        }
    }


    // 通道1
    public static function channelOne($cookie = '')
    {
        // 请求参数
        $requstData = [
            'timeout' => 10,
            'headers' => [
                'Cookie' => $cookie,
                'Origin' => 'https://mbillexprod.alipay.com',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'zh-CN,zh;q=0.9',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept' => 'application/json, text/javascript',
                'Referer' => 'https://mbillexprod.alipay.com/enterprise/tradeListQuery.htm',
                'X-Requested-With' => 'XMLHttpRequest',
                'Connection' => 'keep-alive',
            ],
            'body' => 'queryEntrance=1&billUserId=' . Cookie::getCookieName('uid', $cookie) .
                '&status=SUCCESS&entityFilterType=0&activeTargetSearchItem=tradeNo&tradeFrom=ALL&startTime=' .
                date('Y-m-d', strtotime('-1 day')) . '+00%3A00%3A00&endTime=' . date('Y-m-d') .
                '+23%3A59%3A59&pageSize=20&pageNum=1&total=1&sortTarget=gmtCreate&order=descend&sortType=0&_input_charset=gbk&ctoken=' .
                Cookie::getCookieName('ctoken', $cookie),
        ];

        $channe = new Client();
        $channeReturn = $channe->request('POST', self::CHANNEL_ONE, $requstData)->getBody()->getContents();
        $encode = mb_detect_encoding($channeReturn, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        $channeReturn = mb_convert_encoding($channeReturn, 'UTF-8', $encode);
        $channeReturn = json_decode($channeReturn, true);
        if ($channeReturn['status'] != 'succeed') {
            var_dump($channeReturn['msg']);
            return false;
        }
        unset($channe);
        return $channeReturn;
    }

    // 通道2
    public static function channeTwo($cookie = '')
    {
        // 请求参数
        $requstData = [
            'timeout' => 10,
            'headers' => [
                'Cookie' => $cookie,
                'Origin' => 'https://mbillexprod.alipay.com',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept' => 'application/json, text/javascript',
                'Referer' => 'https://mbillexprod.alipay.com/enterprise/fundAccountDetail.htm',
                'X-Requested-With' => 'XMLHttpRequest',
                'Connection' => 'keep-alive',
            ],
            'body' => 'queryEntrance=1&billUserId=' . Cookie::getCookieName('uid', $cookie) .
                '&showType=1&type=&precisionQueryKey=tradeNo&' .
                'startDateInput=' . date('Y-m-d', strtotime('-1 day')) . '+00%3A00%3A00&endDateInput=' . date('Y-m-d') . '+23%3A59%3A59&' .
                'pageSize=20&pageNum=1&total=1&sortTarget=tradeTime&order=descend&sortType=0&' .
                '_input_charset=gbk&ctoken=' . Cookie::getCookieName('ctoken', $cookie)
        ];
        $channe = new Client();
        $channeReturn = $channe->request('POST', self::CHANNEL_TWO, $requstData)->getBody()->getContents();
        $encode = mb_detect_encoding($channeReturn, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        $channeReturn = mb_convert_encoding($channeReturn, 'UTF-8', $encode);
        $channeReturn = json_decode($channeReturn, true);
        if ($channeReturn['status'] != 'succeed') {
            var_dump($channeReturn['msg']);
            return false;
        }
        unset($channe);
        return $channeReturn;
    }
}