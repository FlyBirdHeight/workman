<?php

use Workerman\Worker;
require_once __DIR__.'/Autoloader.php';

$ws_worker = new Worker("tcp://0.0.0.0:2347");

$ws_worker->count = 4;

$global_uid = 0;

// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
function handle_connection($connection)
{
    global $ws_worker, $global_uid;
    // 为这个连接分配一个uid
    $connection->uid = ++$global_uid;
}

$ws_worker->onMessage = function($connection, $data)
{
    // 向客户端发送hello $data
    $connection->send("user[{$connection->uid}] said: $data");
};

// 当客户端断开时，广播给所有客户端
function handle_close($connection)
{
    global $ws_worker;
    foreach($ws_worker->connections as $conn)
    {
        $conn->send("user[{$connection->uid}] 退出了聊天室");
    }
}

$ws_worker->onConnect = 'handle_connection';
$ws_worker->onMessage = 'handle_message';
$ws_worker->onClose = 'handle_close';

Worker::runAll();
