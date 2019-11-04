<?php
/**
 * DNS服务器管理
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/11
 * Time: 9:43
 */

namespace frontend\controllers;

use frontend\models\Cache;
class CacheController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new Cache();
    }
    public function actionClear(){
        $result = $this->model->clear($this->request->post(),$this->userInfo);
        return $result;
    }


}
