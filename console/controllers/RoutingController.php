<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/3
 * Time: 21:33
 */
//dns routing导出
namespace console\controllers;


use common\models\CountryType;
use common\components\Logger;
use yii\base\Exception;
use console\models\Routing;
class RoutingController extends BaseController
{
    public $logType = 'routing';

    public function actionIndex(){
        ini_set('memory_limit','256M');
        $model = new Routing('');
        $model->isForbidden();
        $model->HomeAbroadGroup();
        $source_res = CountryType::getAll();
        $source_arr = [];
        $source_name = [];
        if($source_res){
            foreach ($source_res as $v){
                $source_arr[] = $v['id'];
                $source_name[$v['id']] = $v['type'];
            }
            if(!empty($source_arr)){
                foreach ($source_arr as $v){
                    $start = $this->getTime();
                    try{
                        $model = new Routing($v,$source_res);
                        $model->execute();
                        $end = $this->getTime();
                        $content = $source_name[$v] .' exec routing '.$this->costTime($start,$end);
                        echo $content."\r\n";
                        //Logger::factoryLog($content,$this->logType);
                    }catch (Exception $exception){
                        $content = $source_name[$v] .' exec routing  error :'. $exception->getMessage();
                        //Logger::factoryLog($content,$this->logType);
                       // Logger::warning($content);
                    }
                }
            }
        }




    }




}
