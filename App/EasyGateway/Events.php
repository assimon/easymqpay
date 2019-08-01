<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use App\Lib\EasyGatewayCore;
use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    /**
     * 当进程启动时触发
     * @param $worker worker进程.
     */
    public static function onWorkerStart($worker)
    {
        // 配置载入
        \App\Lib\EasyGatewayCore::loadConf();
        // 数据库初始化
        // \App\Lib\EasyGatewayCore::initDatabase();
        // 初始化redis
        EasyGatewayCore::initRedis();
        if ($worker->id == 0) {
            // 多久心跳一次 秒为单位
            \Workerman\Lib\Timer::add(60, function (){
                \App\Controller\Alipay::pong(EasyGatewayCore::$sysConf->get('alipay_cookie'));
                // 十秒获取一次支付宝订单
                \App\Controller\Alipay::getNewOrderList(EasyGatewayCore::$sysConf->get('alipay_cookie'));
            });
        }
        if ($worker->id == 1) {
            \Workerman\Lib\Timer::add(5, function (){
                \App\Controller\Gatewaymoney::muchOrder();
            });
        }

    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {

    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
       // 核心启动
       \App\Lib\EasyGatewayCore::runAll($client_id, $message);

   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {

   }
}
