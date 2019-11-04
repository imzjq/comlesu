<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/17
 * Time: 20:42
 */

namespace api\controllers;

use Yii;
use common\components\Logger;
//获取各个redis 的列表
class LogController extends BaseController
{

    public function actionRouting(){

        return Logger::factoryLogGet('routing');
    }

}
