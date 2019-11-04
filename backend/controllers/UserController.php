<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 20:14
 */

namespace backend\controllers;

use backend\models\User;
class UserController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new User();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        //用户名搜索
        $username = $this->request->post('username','');
        $where = [];
        if(!empty($username)){
            $where[] = ['like','username',$username];
        }
        //状态
        $status = $this->request->post('status','');
        if($status===0 || $status==1){
            $where[] = ['status'=>$status];
        }
        //级别
        $level = $this->request->post('level','');
        if($level===0 || $level==1){
            $where[] = ['level'=>$level];
        }

        //来源类型
        $registsource = $this->request->post('registsource','');
        if(!empty($registsource)){
            $where[] = ['registsource'=>$registsource];
        }

        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    //新增
    public function actionAdd(){
        $result = $this->model->add($this->request->post());
        return $result;
    }

    //修改密码
    public function actionChangePassword(){

        $result = $this->model->changePassword($this->request->post());
        return $result;
    }

    //修改信息
    public function actionUpInfo(){
        $result = $this->model->updateInfo($this->request->post());
        return $result;
    }

    //修改状态，激活
    public function actionUpStatus(){
        $result = $this->model->changeStatus($this->request->post());
        return $result;
    }

    //修改密码
    public function actionUpPass(){
        $result = $this->model->changePassword($this->request->post());
        return $result;
    }


    //修改
    public function actionUpdatePackage(){
        $data = $this->request->post();
        $result = $this->model->updatePackage($data);
        return $result;
    }

    public function actionGetUserPackInfo()
    {
        $data = $this->request->post();
        $result = $this->model->getUserPackInfo($data);
        return $result;
    }
    public function actionDelPackInfo()
    {
        $data = $this->request->post();
        $result = $this->model->delPackInfo($data);
        return $result;
    }

}
