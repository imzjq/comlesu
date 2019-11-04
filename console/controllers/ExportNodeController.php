<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/19
 * Time: 19:45
 */

namespace console\controllers;


use common\components\Logger;
use common\models\DnsServer;
use common\models\Node;

class ExportNodeController extends BaseController
{
    public $filename = '/home/tongbu/conf/nodeip.txt';
    public $filename_node = '/home/tongbu/conf/node.txt';
    public function actionIndex(){
        $arr = array();
        //单独导出节点IP
        $arr_node = array();
        //node
        $nodeData = Node::find()->asArray()->all();
        if($nodeData){
            foreach($nodeData as $v){
                $ip = $v['ip'];
                $ip_arr = explode('.',$ip);
                $ip_arr[3]=0;
                $ip_str = implode('.',$ip_arr);
                $ip_str .='/24';
                $arr[] = $ip_str ."\n";
                $arr_node[] = $ip_str."\n";
            }
        }
        //dns server
        $res_dns = DnsServer::find()->asArray()->all();
        foreach($res_dns as $v){
            $ip_dns = $v['ip'];
            $ip_arr_dns = explode('.',$ip_dns);
            $ip_arr_dns[3]=0;
            $ip_str_dns = implode('.',$ip_arr_dns);
            $ip_str_dns .='/24';
            $arr[] = $ip_str_dns ."\n";

        }
        $arr = array_unique($arr);
        $arr_node = array_unique($arr_node);

        if(file_put_contents($this->filename,$arr)===false || file_put_contents($this->filename_node,$arr_node)===false){
            Logger::warning('export node error');
            echo 'export error';
        }else{
            echo 'export ok';
        }

    }


}
