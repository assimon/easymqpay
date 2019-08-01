Easymqpay （支付宝免签支付监控）
=================

基于支付宝网页版协议实现免签约支付监控！  
采用workerman纯php编写，常驻内存！无需添加额外定时任务！

开发初衷
=======

为没有企业支付宝接入资质的站长及个人创业者实现自己的免签约支付


原理
=======
利用支付宝商家中心网页版api接口，实现订单批量查询。   
  
前端网页支付时，只需要根据订单金额及时间即可分析此笔订单由谁支付！       

举个栗子：   

用户A在 2019年7月31日下午5点13分发起支付请求20元。    
用户B 突然在2019年7月31日下午5点14分又发起了一笔20元的支付，但是此时用户A的20元还没支付，那么该怎么判断订单是谁的呢？     
很简单，你只需让用户B支付20.01元，就可以区分是谁支付的！     
以此类推再给订单加上支付限制时间，就可以很直接的判断支付用户
 
安装
=======
~~~
git clone https://github.com/assimon/easymqpay easymqpay

composer install
~~~

启动停止命令
=========

debug调试启动  
`php start.php start`

常驻后台启动  
`php start.php start -d`

重启启动  
`php start.php restart`

平滑重启/重新加载配置  
`php start.php reload`

查看服务状态  
`php start.php status`

停止  
`php start.php stop`



注意事项
=======
1. easymqpay只负责帮助使用者抓取支付宝订单列表，并且上传给使用者的应用服务端。使用者需自行根据订单生成不同金额并且判断！  
2. 请保证服务器或本机为linux系统，且安装了composer 及 PHP7 及 redis    
3. 请保证服务器或本机php扩展支持pcntl、posix扩展
4. easymqpay只会抓取当前5分钟内支付的订单并回调，使用者可据此判断订单    
5. 每次修改代码后请最好重启一次！！     
6. 请先以debug方式启动查看有无报错后再常驻内存启动      
7. 常驻内存启动后可在根目录查询pay.log日志（服务器状态）

使用方法
=========

### 配置支付宝cookie（Config/Mianqian.php）：   

支付宝cookie获取方法：  
1.浏览器访问：https://mbillexprod.alipay.com/enterprise/tradeListQuery.htm    
2.登录支付宝账号   
3.浏览器按f12   
4.找到Network并点击再刷新一下     
5.可以看到tradeListQuery.json点击它    
6.点击headers它找到Cookie: 后面就是cookie(务必复制完整)    



```php
<?php

return [
    // 支付宝cookie
    'alipay_cookie' => '你的cookie',

    // 微信cookie
    'wx_cookie' => '暂时还没做',

    // 订单有效时间 单位分钟
    'order_success_time' => 3,

    // 异步回调url
    'notify_url' => 'http://baidu.com'
];
```
### 修改定时时间,个人建议1分钟一次（App/EasyGateway/Events.php）47行：  
   
```php
 if ($worker->id == 0) {
             // 多久心跳一次 秒为单位
            \Workerman\Lib\Timer::add(60, function (){
                \App\Controller\Alipay::pong(EasyGatewayCore::$sysConf->get('alipay_cookie'));
                // 十秒获取一次支付宝订单
                \App\Controller\Alipay::getNewOrderList(EasyGatewayCore::$sysConf->get('alipay_cookie'));
            });
        }

```

### 抓取到新的支付宝成功支付的订单后，服务器会向使用者服务端进行回调

请接收回调参数：    
```php
'price' => $_POST['price'], // 支付金额
'tradeNo' => $_POST['tradeNo'], // 支付平台id
'paytype' => $_POST['paytype'] // 支付类型  alipay为支付宝 wepay为微信
```

回调服务器接收到回调后：
1. 根据订单时间或金额判断是哪个用户支付的
2. 回调成功后请返回success，否则服务端会一直回调直至订单过期！    



其他使用功能
=========

### 获取配置
```php
\App\Lib\EasyGatewayCore::$sysConf->get('key');
\App\Lib\EasyGatewayCore::$sysConf->get('key1.key2');
```

### 微信功能请等待更新....


鸣谢
=========
[GatewayWorker](https://github.com/walkor/GatewayWorker)、[ChenSee/ChenPay](https://github.com/ChenSee/ChenPay) 