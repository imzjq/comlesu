<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/15
 * Time: 10:25
 */

namespace console\controllers;


use yii\console\Controller;
use common\components\QueueJob;
use Yii;
class BaseController extends Controller
{


    public function getTime(){
         return microtime(true);
    }

    public function costTime($t1,$t2){
        return '耗时'.round($t2-$t1,3).'秒';
    }
}
