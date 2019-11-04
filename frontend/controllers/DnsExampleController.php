<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:17
 */

namespace frontend\controllers;


use frontend\models\DnsExample;

class DnsExampleController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new DnsExample();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $where = [];
        $result = $this->model->getList($page,$pagenum,$where);
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
