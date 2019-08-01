<?php
/**
 * run with command
 * php start.php start
 */

ini_set('display_errors', 'on');
use Workerman\Worker;

if(strpos(strtolower(PHP_OS), 'win') === 0)
{
    exit("start.php not support windows, please use start_for_win.bat\n");
}

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);
defined('IN_PHAR') or define('IN_PHAR', boolval(\Phar::running(false)));
defined('EASYGATEWAY_ROOT') or define('EASYGATEWAY_ROOT', IN_PHAR ? \Phar::running() : realpath(getcwd()));

require_once __DIR__ . '/vendor/autoload.php';

// 载入函数库
require_once __DIR__ . '/Comm/function.php';

// 加载所有Applications/*/start.php，以便启动所有服务
foreach(glob(__DIR__.'/App/*/start*.php') as $start_file)
{
    require_once $start_file;
}
// 将屏幕打印输出到Worker::$stdoutFile指定的文件中
Worker::$stdoutFile = getcwd().'/pay.log';

// 运行所有服务
Worker::runAll();
