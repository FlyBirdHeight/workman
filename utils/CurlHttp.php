<?php
/**
 * Created by PhpStorm.
 * User: jj
 * Date: 2018/4/9
 * Time: 22:49
 */

namespace Utils;


class CurlHttp
{
    public function curlGet($http,$token){
        $curl = curl_init();
        $headers = [
            'Authorization'=>$token
        ];
        curl_setopt($curl, CURLOPT_URL, 'http://101.132.71.227/api/app/'.$http);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置header
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }

    public function curlPost($http,$token){
        $curl = curl_init();
        $headers = [
            'Authorization'=>$token
        ];
        curl_setopt($curl, CURLOPT_URL, 'http://101.132.71.227/api/app/'.$http);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置header
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //设置post数据
        $post_data = array(

        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }
}