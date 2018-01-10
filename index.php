<?php

use Workerman\Worker;
require_once __DIR__.'/Autoloader.php';

$ws_worker = new Worker("tcp://0.0.0.0:2347");

$ws_worker->count = 4;

$ws_worker->onMessage = function($connection, $data)
{
    // 向客户端发送hello $data
    $connection->send('hello ' . $data);
};


Worker::runAll();
