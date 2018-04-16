<?php

//本机IP是10.211.55.13
//需要监听的端口是 9090


use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

require_once __DIR__.'/Autoloader.php';
define('GLOBAL_START', 1);
// 加载所有Applications/*/start.php，以便启动所有服务
foreach(glob(__DIR__.'/start*.php') as $start_file)
{
    require_once $start_file;
}
// 运行所有服务
Worker::runAll();