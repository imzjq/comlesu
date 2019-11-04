<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 20:14
 */

namespace frontend\controllers;

use frontend\models\Customer;
class CustomerController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new Customer();
    }

    public function actionGetList(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',20);
        $result = $this->model->getList($page,$pagenum,$this->userInfo);
        return $result;

    }

    public function actionGetOne(){
      $id = $this->userInfo['uid'];
        $result = $this->model->getOne($id);
        return $result;
    }

    //修改信息
    public function actionUpdateInfo(){
        $result = $this->model->updateInfo($this->request->post(),$this->userInfo);
        return $result;
    }

    //修改密码
    public function actionUpPass(){
        $result = $this->model->changePassword($this->request->post(),$this->userInfo);
        return $result;
    }
}
