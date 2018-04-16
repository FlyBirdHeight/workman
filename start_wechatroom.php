<?php

/**
 * Created by PhpStorm.
 * User: jj
 * Date: 2018/4/16
 * Time: 14:33
 */

use Workerman\Worker;
use Utils\Group;

$wechat_room = new Worker("websocket://0.0.0.0:2346");
$group = new Group();
$global_uid = 0;
date_default_timezone_set("Asia/Shanghai");
Worker::$stdoutFile = '/tmp/stdout.log';
Worker::$logFile = '/tmp/workerman.log';
$wechat_room->reloadable = true;
$wechat_room->name = 'wechatRoom';
define('HEARTBEAT_TIME', 30);
$wechat_room->count = 2;
$wechat_room->onConnect = 'connection';
$wechat_room->onMessage = 'message';
$wechat_room->onClose = 'close';
$wechat_room->onWorkerStart = function($wechat_room)
{
    echo "聊天室已经启动成功...\n";
};

function connection($connection){
    $ip = $connection->getRemoteIp();
    $port = $connection->getRemotePort();
    echo $ip.":".$port."接入聊天室";
}

function message($connection, $data){
    global $wechat_room,$group;
    $data = json_decode($data,true);
    echo "{$data['type']},{$data['client_id']}";
    switch ($data['type']){
        case 'pong': //心跳包回复
            return;
        case 'login':
            if(!isset($message_data['room_id']))
            {
                throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$data");
            }
            // 把房间号昵称放到session中
            $room_id = $message_data['room_id'];
            $client_name = htmlspecialchars($message_data['client_name']);
            $_SESSION['room_id'] = $room_id;
            $_SESSION['client_name'] = $client_name;


    }
}

function close($connection){

}

if (!defined('GLOBAL_START')){
    Worker::runAll();
}

