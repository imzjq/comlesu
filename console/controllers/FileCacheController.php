<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 12:40
 */

namespace console\controllers;



class FileCacheController extends BaseController
{

    /**
     *清除缓存
     */
    public function actionIndex()
    {
        $path = \Yii::getAlias('@dns_file').'/conf/cache.txt';
        file_put_contents($path,'');
    }




}
