<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:17
 */

namespace backend\controllers;


use backend\models\NodeRemark;
use common\models\NodeTagId;
use yii\helpers\ArrayHelper;

class NodeRemarkController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new NodeRemark();
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


    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateRemark($data);
        return $result;
    }



}
