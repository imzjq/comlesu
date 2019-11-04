<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/13
 * Time: 20:37
 */

namespace backend\controllers;


use backend\models\Drs;

class DrsController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Drs();
    }

    public function actionIndex(){
        $data = $this->request->post();
        $result = $this->model->getList($data);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $result = $this->model->add($this->request->post());
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data);
        return $result;
    }
    public function actionDel(){
        $id = $this->request->post('id',0);
        $result = $this->model->del($id);
        return $result;
    }
}
