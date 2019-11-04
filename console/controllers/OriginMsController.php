<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 12:40
 */

namespace console\controllers;



use common\models\OriginMs;

class OriginMsController extends BaseController
{

    /**
     * 凌晨删除两天前的数据
     */
    public function actionDelTwoDay()
    {
        $data =  date("Y-m-d 00:00:00",strtotime("-2 day"));
        OriginMs::deleteAll(['<','create_time',$data]);
    }

}
