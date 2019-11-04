<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/12
 * Time: 22:32
 */
//域名解析相关
namespace console\controllers;

use common\components\Logger;
use common\models\CountryType;
use yii\base\Exception;
use console\models\Drs;
class DrsController extends BaseController
{
    public $logType = 'drs';

    protected $area_arr = [
        'dnsunions',
        'cdnunions',
        'lesucdn',
        'dns-tw',
        'jbsdnsn',
        'wuxiandns',
        'cdn-w',
        'mly666',
        //'ddos-dns'  ddos 比较特殊
    ];
    public function actionIndex(){
        //$area_arr = $this->area_arr;
        // TODO 19-4-28  数据库中获取
        $area_arr =  CountryType::getAll();

        if(!empty($area_arr)){
            foreach ($area_arr as $v){
                $start = $this->getTime();
                try{
                    $model = new Drs($v['type']);
                    $model->exportData();
                }catch (Exception $e){
                    //报错，
                   // $content = $v['type'].' exec drs error';
                   // Logger::warning($content);
                }
              //  $end = $this->getTime();
               // $content = $v['type']. 'exec drs '.$this->costTime($start,$end);

               // Logger::factoryLog($content,$this->logType);

            }
        }
    }

}
