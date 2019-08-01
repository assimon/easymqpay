<?php
namespace App\Controller;


use App\Lib\EasyGatewayCore;
use GuzzleHttp\Client;

class Gatewaymoney {

    /**
     * 成功回调过得订单
     */
    const SUCCESS_NOTIFY_ORDERS = 'SUCCESS_NOTIFY_ORDERS';

    const ALIPAY_NEW_ORDERS = 'ALIPAY_NEW_ORDERS_';


    /**
     * 回调订单订单
     */
    public static function muchOrder()
    {
        // 获取所有爬取到支付宝存活的订单
        $newAlipayOrderList = EasyGatewayCore::$rediscon->keys(self::ALIPAY_NEW_ORDERS.'*');
        // 开始匹配
        foreach ($newAlipayOrderList as $item){
            $item = EasyGatewayCore::$rediscon->get($item);
            $item = json_decode($item, true);
            if (!EasyGatewayCore::$rediscon->hget(self::SUCCESS_NOTIFY_ORDERS, $item['tradeNo'])) {
                var_dump('开始回调');
                // 开始回调
                $notifyRes = self::notifyRequst($item['totalAmount'], $item['tradeNo']);
                // 回调成功
                if ($notifyRes == 'success') {
                    // 将订单号载入已回调订单
                    EasyGatewayCore::$rediscon->hset(self::SUCCESS_NOTIFY_ORDERS, $item['tradeNo'], 1);
                }
            }
        }

    }

    public static function notifyRequst($price,$tradeNo,$paytype = 'alipay')
    {
        $requstData = [
            'timeout' => 10,
            'form_params' => [
                'price' => $price, // 支付金额
                'tradeNo' => $tradeNo, // 支付平台id
                'paytype' => $paytype
            ]
        ];
        var_dump('回调参数');
        var_dump($requstData);
        $client = new Client();
        $appres = $client->request('POST',EasyGatewayCore::$sysConf->get('notify_url'),$requstData)->getBody()->getContents();
        var_dump('商户网站回调结果:');
        var_dump($appres);
        return $appres;
    }


}