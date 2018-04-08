<?php

use Workerman\Worker;
require_once __DIR__.'/Autoloader.php';
$ws_worker = new Worker("websocket://0.0.0.0:2347");
$global_uid = 0;
date_default_timezone_set("Asia/Shanghai");
Worker::$stdoutFile = '/tmp/stdout.log';
Worker::$logFile = '/tmp/workerman.log';
$ws_worker->reloadable = true;
define('HEARTBEAT_TIME', 60);
$client = [];

$ws_worker->onWorkerStart = function($ws_worker)
{
    echo "Worker starting...\n";
};

function syncUsers()
{
    global $clients;
    $users = 'users:'.json_encode(array_column($clients,'name','ipp')); //准备要广播的数据
    foreach($clients as $ip=>$client){
        $client['conn']->send($users);
    }
}
// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
function handle_connection($connection)
{
    global $ws_worker, $global_uid;
    $connection->maxSendBufferSize = 1024000;
    $connection->uid = ++$global_uid;
    echo "新人加入(userId:$connection->uid)\n";
//    foreach($ws_worker->connections as $conn){
//        $conn->send("user[{$connection->uid}]加入聊天室\n");
//    }
}

// 当客户端发送消息过来时，转发给所有人
function handle_message($connection, $data)
{
    global $ws_worker,$clients;
    $data = json_decode($data,true);
    print_r($data['type']);
//    $dataInfo = $data.type;
//    echo $dataInfo;
//    foreach($ws_worker->connections as $conn)
//    {
//        $messages = json_encode(['date'=>date("Y-m-d h:i:s"),'content'=>"user[{$connection->uid}] said: $data"]);
//        $conn->send($messages);
//    }
//    if(preg_match('/^login:(\w{0,1024})/i',$data,$result)){ //代表是客户端认证
//        $ip = $connection->getRemoteIp();
//        $port = $connection->getRemotePort();
//        if(!array_key_exists($ip,$clients)){ //必须是之前没有注册过
//            $clients[$ip.':'.$port] = ['ipp'=>$ip.':'.$port,'name'=>$result[1],'conn'=>$connection];
//            $content = json_encode(['user'=>$data,'notice'=>'success']);
//            $connection->send($content);
//            echo $ip .':'.$port.'==>'.$result[1] .'==>login' . PHP_EOL; //这是为了演示,控制台打印信息
//            syncUsers();
//        }
//    }elseif(preg_match('/^notice:(.*?)/isU',$data,$msgset)){ //代表是客户端发送的通知消息
//        if(array_key_exists($connection->getRemoteIp(),$clients)){ //必须是之前验证通过的客户端
//            echo 'get notice:' . $msgset[1] .PHP_EOL; //这是为了演示,控制台打印信息
//            foreach($ws_worker->connections as $conn)
//            {
//                $messages = json_encode(['date'=>date("Y-m-d h:i:s"),'notice'=>$data]);
//                $conn->send($messages);
//            }
//        }
//    }elseif (preg_match('/^getInfo:(.*?)/isU',$data,$msgset)){
//        switch ($data.snedType){
//            case 1:
//                break;
//            case 2:
//                break;
//            case 3:
//                break;
//            case 4:
//                break;
//            case 5:
//                break;
//            case 6:
//                break;
//        }
//    }
}

// 当客户端断开时，广播给所有客户端
function handle_close($connection)
{
    global $ws_worker,$clients;
    unset($clients[$connection->getRemoteIp().':'.$connection->getRemotePort()]);
    foreach($ws_worker->connections as $conn)
    {
        $conn->send("user[{$connection->uid}] 退出了聊天室\n");
        echo "用户退出(userId:$connection->uid)\n";
    }
}



$ws_worker->count = 1;
$ws_worker->name = '李景秋测试的workman使用';
$ws_worker->onConnect = 'handle_connection';
$ws_worker->onMessage = 'handle_message';
$ws_worker->onClose = 'handle_close';

Worker::runAll();
