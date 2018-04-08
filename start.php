<?php

//本机IP是10.211.55.13
//需要监听的端口是 9090


use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

require_once __DIR__.'/Autoloader.php';

$clients = []; //保存客户端信息


// 创建一个Worker监听9090端口，使用websocket协议通讯
$ws_worker = new Worker("websocket://0.0.0.0:2347");

// 启动4个进程对外提供服务
$ws_worker->count = 4;
function handle_connection($connection)
{
    global $ws_worker, $global_uid;
    $connection->maxSendBufferSize = 1024000;
    $connection->uid = ++$global_uid;
    echo "新人加入(userId:$connection->uid)\n";
    foreach($ws_worker->connections as $conn){
        $conn->send("user[{$connection->uid}]加入聊天室\n");
    }
}
// 当收到客户端发来的数据后
$ws_worker->onMessage = function($connection, $data)
{
    //这里用global的原因是:php是有作用域的,我们是在onMessage这个回调还是里操作外面的数组
    //想要改变作用域外面的数组,就global一下
    global $clients;

    //验证客户端用户名在3-20个字符
    if(preg_match('/^login:(\w{3,20})/i',$data,$result)){ //代表是客户端认证

        $ip = $connection->getRemoteIp();
        if(!array_key_exists($ip,$clients)){ //必须是之前没有注册过

            $clients[$ip] = $result[1]; //把新用户保存起来

            // 向客户端发送数据
            $connection->send('notice:success'); //验证成功消息
            $connection->send('msg:welcome '.$result[1]); //普通消息
            echo $ip .':' .$result[1] .'login' . PHP_EOL; //这是为了演示,控制台打印信息
        }

    }elseif(preg_match('/^msg:(.*?)/isU',$data,$msgset)){ //代表是客户端发送的普通消息

        if(array_key_exists($connection->getRemoteIp(),$clients)){ //必须是之前验证通过的客户端
            echo 'get msg:' . $msgset[1] .PHP_EOL; //这是为了演示,控制台打印信息
            if($msgset[1] == 'nihao'){
                //如果收到'nihao',就给客户端发送'nihao 用户名'
                //给客户端发送普通消息
                $connection->send('msg:nihao '.$clients[$connection->getRemoteIp()]);
            }
        }
    }

    // 设置连接的onClose回调
    $connection->onClose = function($connection) //客户端主动关闭
    {
        global $clients;
        unset($clients[$connection->getRemoteIp()]);

        echo "connection closed\n";
    };
};

// 运行worker
Worker::runAll();