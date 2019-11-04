<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:17
 */

namespace backend\controllers;


use backend\models\CountryType;

class CountryTypeController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new CountryType();
    }
    public function actionIndex(){
        $result = $this->model->getList();
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $result = $this->model->add($this->request->post());
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data);
        return $result;
    }



}
