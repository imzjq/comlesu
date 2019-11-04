<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:35
 */

namespace frontend\controllers;


use frontend\models\Brand;
use Yii;
class BrandController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Brand();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);

        $where = [];
        $where[] = ['user_id'=>$this->userInfo['uid']];
        $name = $this->request->post('name',''); //品牌名称
        if($name){
            $where[]= ['like','name',$name];
        }
        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }


    //新增
    public function actionAdd(){
        $data = $this->request->post();
        $result = $this->model->add($data,$this->userInfo['uid']);
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data,$this->userInfo['uid']);
        return $result;
    }

    //删除
    public function actionDel(){
        $id = $this->request->post('id');
        $result = $this->model->del($id,$this->userInfo['uid']);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id,$this->userInfo['uid']);
        return $result;
    }





}
