<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/10
 * Time: 21:23
 */

namespace api\models;


use common\models\IpCache;
use common\models\Node;
use common\models\Route;


class ApiModel extends Base
{


    //zabbix 修改节点状态
    //参数alive_z_1,2,ip
    public static function change_node_status($ip,$alive_z_num,$z_num){
        //update lc_node set alive_z_1=1,status=1 where ip='$IP'
        $model = Node::find()->where(['ip'=>$ip])->one();
        if($model){
            if($z_num==1){
                $model->alive_z_1 = $alive_z_num;
            }else{
                $model->alive_z_2 = $alive_z_num;
            }
            if($model->save()){
                return true;
            }
            $msg = __METHOD__.' '.self::getModelError($model);
            self::recordLog($msg);
            return $msg;
        }
        $msg = __METHOD__.' 未找到节点信息';
        self::recordLog($msg);
        return $msg;
    }


    //zabbix 监听各个节点的信息（流量，cpu,内存）参数：ip,flow,cpu,memory update lc_node

    public static function change_node_data($ip,$flow){
        //update lc_node set flow=$flow,cpu=$cpu,memory=$memory where ip='$IP';
        $model = Node::find()->where(['ip'=>$ip])->one();
        if($model){
           // $model->cpu = $cpu;
            $model->flow = $flow;
           // $model->memory = $memory;
            if($model->save()){
                return true;
            }
            $msg = __METHOD__.' '.self::getModelError($model);
            self::recordLog($msg);
            return $msg;
        }
        $msg = __METHOD__.' 未找到节点信息';
        self::recordLog($msg);
        return $msg;
    }



}
