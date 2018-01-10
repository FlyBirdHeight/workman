<?php

use Workerman\Worker;
require_once __DIR__.'/Autoloader.php';
$ws_worker = new Worker("tcp://0.0.0.0:2347");
$global_uid = 0;
date_default_timezone_set("Asia/Shanghai");
// 所有的打印输出全部保存在/tmp/stdout.log文件中
Worker::$stdoutFile = '/tmp/stdout.log';
//workerman日志文件位置
Worker::$logFile = '/tmp/workerman.log';
// 设置此实例收到reload信号后是否reload重启
$ws_worker->reloadable = true;

$ws_worker->onWorkerStart = function($ws_worker)
{
    echo "Worker starting...\n";
};

// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
function handle_connection($connection)
{
    global $ws_worker, $global_uid;
    $connection->uid = ++$global_uid;
    echo "新人加入";
    foreach($ws_worker->connections as $conn){
        $conn->send("user[{$connection->uid}]加入聊天室");
    }
}

// 当客户端发送消息过来时，转发给所有人
function handle_message($connection, $data)
{
    global $ws_worker;
    foreach($ws_worker->connections as $conn)
    {
        $conn->send(date("Y-m-d h:i:s")."\n");
        $conn->send("user[{$connection->uid}] said: $data");
    }
}

// 当客户端断开时，广播给所有客户端
function handle_close($connection)
{
    global $ws_worker;
    foreach($ws_worker->connections as $conn)
    {
        $conn->send("user[{$connection->uid}] 退出了聊天室");
    }
}



$ws_worker->count = 4;
$ws_worker->name = '李景秋测试的workman使用';
$ws_worker->onConnect = 'handle_connection';
$ws_worker->onMessage = 'handle_message';
$ws_worker->onClose = 'handle_close';

Worker::runAll();
