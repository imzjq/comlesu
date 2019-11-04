<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 21:09
 */

namespace frontend\controllers;


use frontend\models\Domain;


class DomainController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Domain();
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

        $brand_id = $this->request->post('brand_id',''); //品牌名称
        if($brand_id){
            $where[]= ['brand_id'=>$brand_id];
        }

        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['package_id'=>$package_id];
        }

        //状态
        $status = $this->request->post('status','');
        if(is_numeric($status)){
            $where[] = ['in','status',$status];
        }

        $result = $this->model->getList($page,$pagenum,$where,$this->userInfo['uid']);
        return $result;
    }

    public function actionAdd(){
        $result = $this->model->add($this->request->post(),$this->userInfo);
        return $result;
    }

    public function actionAddBatch(){
        $result = $this->model->addBatch($this->request->post(),$this->userInfo);
        return $result;
    }



    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id,$this->userInfo);
        return $result;
    }
    public function actionUpdate(){
        $result = $this->model->updateInfo($this->request->post(),$this->userInfo);
        return $result;
    }




    //添加加速 下一步检查
    public function actionNextCheck(){
       $post = $this->request->post();
        $post['step'] =1 ;
        $result = $this->model->generateCnames($post,$this->userInfo);
        return $result;
    }

    //批量添加加速 下一步检查
    public function actionBatchNextCheck(){
        $post = $this->request->post();
        $result = $this->model->generateBatchCnames($post,'',$this->userInfo);
        return $result;
    }

    //状态修改
    public function actionChangeStatus(){
        $data = $this->request->post();
        $result = $this->model->changeStatus($data,$this->userInfo);
        return $result;
    }


    public function actionCheckSsl(){
        $username =$this->userInfo['username'];
        $dname = $this->request->post('dname');
        if(empty($username) || empty($dname)){
            return $this->error('参数错误');
        }
        $result = $this->model->checkSsl($username,$dname);
        if($result){
            return $this->success();
        }
        return $this->error('请先上传'.$dname .'证书');
    }

    public function actionBatchCheckSsl(){
        $username = $this->userInfo['username'];
        $dname = $this->request->post('dnames');
        if(empty($username) || empty($dname)){
            return $this->error('参数错误');
        }
        $result = $this->model->batchCheckSsl($username,$dname);
        return $result;
    }

    public function actionBatchUpdateOrigin()
    {
        $post = $this->request->post();
        $result = $this->model->batchUpdateOrigin($post,$this->userInfo);
        return $result;
    }

}
