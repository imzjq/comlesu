<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:35
 */

namespace backend\controllers;


use backend\models\NodeKitScript;
use Yii;
class NodeKitScriptController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new NodeKitScript();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $where = [];
        $name = $this->request->post('name',''); //分组名
        $kid_id = $this->request->post('kit_id',0);
        $where[] = ['=','kit_id',$kid_id];
        if($name){
            $where[]= ['like','name',$name];
        }
        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }


    //新增
    public function actionAdd(){
        $data = $this->request->post();
        $result = $this->model->add($data);
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data);
        return $result;
    }

    //删除
    public function actionDel(){
        $id = $this->request->post('id');
        $result = $this->model->del($id);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    public function actionUpload(){
        $result = $this->model->upload();
        return $result;
    }




}
