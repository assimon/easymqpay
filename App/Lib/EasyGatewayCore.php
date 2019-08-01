<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 2019-05-06
 * Time: 21:19
 */

namespace App\Lib;

use Noodlehaus\Config;
use Noodlehaus\Parser\Php;
use GatewayWorker\Lib\Gateway;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Predis\Client;

class EasyGatewayCore
{

    public static $sysConf = '';
    public static $rediscon;


    /**
     * 数据库初始化
     */
    public static function initDatabase()
    {
        $capsule = new Capsule;
        // 创建链接
        $capsule->addConnection(self::$sysConf->get('DATABASE'));
        // 设置全局静态可访问
        $capsule->setAsGlobal();
        // 启动Eloquent
        $capsule->bootEloquent();
    }

    public static function initRedis()
    {
        self::$rediscon = new Client(self::$sysConf->get('REDIS'));
    }

    /**
     * 配置载入
     */
    public static function loadConf()
    {
        self::$sysConf = Config::load(EASYGATEWAY_ROOT . '/Config');
    }

    /**
     * 核心启动
     * @param $client_id
     * @param $message
     * @return bool|void
     */
    public static function runAll($client_id, $message)
    {
        // 收到消息，解析包体
        $requstData = json_decode($message, true);
        // 消息空包，无视
        if (empty($requstData)) return;
        $controller = ucfirst(strtolower($requstData['_controller_']));
        $action = $requstData['_action_'];
        $paramData = $requstData['_param_data_'];
        // 追加客户端id
        $paramData['client_id'] = $client_id;
        // 加上命名空间
        $paramData['class'] = '\\App\\Controller\\'.$controller;
        if (!class_exists($paramData['class'])) {
            return Gateway::sendToCurrentClient(easygateway_json_response(500, '请求控制器不存在！'));
        }
        if (!method_exists($paramData['class'], $action)) {
            return Gateway::sendToCurrentClient(easygateway_json_response(500, '请求方法不存在！'));
        }
        // 注入请求参数
        $requstObj = new \App\Lib\Requst($controller, $action, $paramData);
        // 调用类的方法
        try
        {
            $callBack = array(new $paramData['class'], $action);
            if(is_callable($callBack))
            {
                call_user_func_array($callBack, [&$requstObj]);
            }
        }
            // 有异常
        catch(Exception $e)
        {
            // 发送数据给客户端，发生异常，调用失败
            return Gateway::sendToCurrentClient(easygateway_json_response(500, '服务器开小差拉！'));
        }
    }




}