<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/16
 * Time: 10:00
 */

namespace console\controllers;

//DNS server 导出
use common\components\Logger;
use common\models\CountryType;
use console\models\DnsServer;
class DnsServerController extends BaseController
{
    public $logType = 'dnsServer';

    public function actionIndex(){

        $source_res = CountryType::getAll();
        if($source_res){
            foreach ($source_res as $v){
                $obj = new DnsServer($v['id'],$v['type']);
                $obj->execute();
               // $content = $v['type'].' dns server 导出完成';
                //Logger::factoryLog($content,$this->logType);
            }
        }



    }

}
