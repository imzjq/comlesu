<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/12
 * Time: 20:42
 */

error_reporting(0);
set_time_limit(0);
$node_id = 2;
$url = '';

$file = '/home/tongbu/conf/lsipku.txt';
if(file_exists($file)){
    $datas = file_get_contents($file);
    $datas =  unserialize($datas);
}else{
    die('file not exists');
}

if($datas){
    foreach($datas as $k=>$v){
        $ip =$k;
        $group_id = $v;
        if($group_id ==0 || !$group_id){
            continue;
        }

        $com = "ping -c1 -w1 $k";
        $r = exec($com, $res, $reval);
        if ($reval or empty($r)) {
            continue;
        }else{
            //ping的通
            $arr = explode(' ', $r);
            $rs = $arr[3];
            $arrs = explode('/', $rs);
            $MS = $arrs[1];
            $MS = ceil($MS);
            //去除为0的值
            if ($MS == '' or $MS == 0 or !isset($MS)) {
                continue;
            } else {
                //ping的通，且MS不为0的
                if($group_id ==0){
                    continue;
                }
                $post_data = [];
                $post_data['MS'] = $MS;
                $post_data['group_id'] = $group_id;
                $post_data['node_id'] = $node_id;
                $post_data['ping_ip'] = $ip;
                curl_post($url,$post_data);
            }
        }
    }
}


function curl_post($url,$data){
    $curl = curl_init();
    //需要请求的是哪个地址
    curl_setopt($curl,CURLOPT_URL,$url);
    //表示把请求的数据已文件流的方式输出到变量中
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    //设置请求方式是post方式
    curl_setopt($curl,CURLOPT_POST,1);
    //设置post请求提交的表单信息
    curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    $result = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($result,true);

    return $result;
}
