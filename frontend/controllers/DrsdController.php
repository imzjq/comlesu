<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/12
 * Time: 21:48
 */

namespace frontend\controllers;


use frontend\models\Drsd;

class DrsdController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Drsd();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $dname = $this->request->post('dname','');
        $where = [];
        $where[] = ['user_id'=>$this->userInfo['uid']];
        if($dname){
            $where[]= ['like','dname',$dname];
        }
        //状态
        $status = $this->request->post('status','');
        if(is_numeric($status)){
            $where[] = ['status'=>$status];
        }
        $brand_id = $this->request->post('brand_id',''); //品牌名称
        if($brand_id){
            $where[]= ['brand_id'=>$brand_id];
        }

        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['package_id'=>$package_id];
        }

        $result = $this->model->getList($page,$pagenum,$where,$this->userInfo['uid']);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $result = $this->model->add($this->request->post(),$this->userInfo);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id,$this->userInfo);
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data,$this->userInfo);
        return $result;
    }


    public function actionDel()
    {
        $data = $this->request->post();
        $result = $this->model->del($data,$this->userInfo);
        return $result;
    }

    public function actionAddBatch(){
        $result = $this->model->addBatch($this->request->post(),$this->userInfo);
        return $result;
    }

    public function actionUpdateBatch(){
        $result = $this->model->updateBatch($this->request->post(),$this->userInfo);
        return $result;
    }


}
