<?php

use Workerman\Worker;
require_once __DIR__.'/Autoloader.php';
$ws_worker = new Worker("websocket://0.0.0.0:2347");
$global_uid = 0;
date_default_timezone_set("Asia/Shanghai");
Worker::$stdoutFile = '/tmp/stdout.log';
Worker::$logFile = '/tmp/workerman.log';
$ws_worker->reloadable = true;
define('HEARTBEAT_TIME', 30);
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
}

// 当客户端发送消息过来时
function handle_message($connection, $data)
{
    global $ws_worker,$clients;
    $data = json_decode($data,true);
    $ip = $connection->getRemoteIp();
    $port = $connection->getRemotePort();
    print_r($data);
//    echo $data;
    if($data['type'] == 'register'){ //代表是客户端认证
        if(!array_key_exists($ip.':'.$port,$clients)){ //必须是之前没有注册过
            $clients[$ip.':'.$port] = ['ipp'=>$ip.':'.$port,'name'=>$data['shopInfo']['id'],'conn'=>$connection,'userInfo'=>$data['userInfo']];
            $content = json_encode(['user'=>$data,'notice'=>'success']);
            $connection->send($content);
            echo $ip .':'.$port.'==>'.$data['userInfo']['id'] .'==>login' . PHP_EOL;
            syncUsers();
        }
    }elseif($data['msg']['type'] == 'notify'){ //代表是客户端发送的通知消息
//        if(array_key_exists($ip.':'.$port,$clients)){ //必须是之前验证通过的客户端

            echo 'get notify:' .$data['msg']['notifyInfo'] .PHP_EOL; //这是为了演示,控制台打印信息
            foreach($ws_worker->connections as $conn)
            {
                $messages = json_encode(['date'=>date("Y-m-d h:i:s"),'notice'=>$data['msg']['notifyInfo']]);
                $conn->send($messages);
            }
//        }
    }elseif ($data['type'] == 'getInfo'){
        if(array_key_exists($ip.':'.$port,$clients)){ //必须是之前验证通过的客户端
            $data01 = $clients[$ip.':'.$port];
            $curlHttp = new \Utils\CurlHttp();
            switch ($data['typeInfo']){
                case 1:
                    $orders = $curlHttp->curlGet('allOrder/'.$data01['shopInfo']['id'],$data01['Authorization']);
                    $data01['conn']->send(json_encode(['status'=>'success','orders'=>$orders]));
                    break;
                case 2:
                    $orders = $curlHttp->curlGet('UndoOrder/'.$data01['shopInfo']['id'],$data01['Authorization']);
                    $data01['conn']->send(json_encode(['status'=>'success','orders'=>$orders]));
                    break;
                case 3:
                    $orders = $curlHttp->curlGet('getDoOrder/'.$data01['shopInfo']['id'],$data01['Authorization']);
                    $data01['conn']->send(json_encode(['status'=>'success','orders'=>$orders]));
                    break;
                case 4:
                    $orders = $curlHttp->curlGet('getOrderUnpay/'.$data01['shopInfo']['id'],$data01['Authorization']);
                    $data01['conn']->send(json_encode(['status'=>'success','orders'=>$orders]));
                    break;
                case 5:
                    $orders = $curlHttp->curlGet('shopComment/'.$data01['shopInfo']['id'],$data01['Authorization']);
                    $data01['conn']->send(json_encode(['status'=>'success','goods'=>$orders]));
                    break;
            }
        }
    }elseif($data['type'] == 'msg'){
        foreach($ws_worker->connections as $conn)
        {
            $messages = json_encode(['date'=>date("Y-m-d h:i:s"),'content'=>"user[{$connection->uid}] said: $data"]);
            $conn->send($messages);
        }
    }
}

// 当客户端断开时，广播给所有客户端
function handle_close($connection)
{
    global $ws_worker,$clients;
    unset($clients[$connection->getRemoteIp().':'.$connection->getRemotePort()]);
    echo "用户退出(userId:$connection->uid)\n";
}


$ws_worker->count = 2;
$ws_worker->name = '李景秋测试的workman使用';
$ws_worker->onConnect = 'handle_connection';
$ws_worker->onMessage = 'handle_message';
$ws_worker->onClose = 'handle_close';
Worker::runAll();
