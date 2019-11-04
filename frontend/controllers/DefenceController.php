<?php
/**
 * 客户高防域名管理
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/7
 * Time: 22:44
 */

namespace frontend\controllers;

use frontend\models\Defence;
use frontend\models\User;
use yii\db\Expression;

class DefenceController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Defence();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $where = [];
        $where[] = ['user_id'=>$this->userInfo['uid']];

        $dname = $this->request->post('dname',10);
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
        $result = $this->model->getList($page,$pagenum,$where,$this->userInfo['uid']);
        return $result;
    }

    //状态修改
    public function actionChangeStatus(){
        $data = $this->request->post();
        $result = $this->model->changeStatus($data,$this->userInfo);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id,$this->userInfo);
        return $result;
    }

    //新增
    public function actionAdd(){
        $result = $this->model->add($this->request->post(),$this->userInfo);
        return $result;
    }

    //批量新增
    public function actionBatchAdd(){
        $result = $this->model->batchAdd($this->request->post(),$this->userInfo);
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data,$this->userInfo);
        return $result;
    }

    public function actionDel(){
        $data = $this->request->post();
        $result = $this->model->del($data);
        return $result;
    }

    public function actionBatchUpdateOrigin()
    {
        $post = $this->request->post();
        $result = $this->model->batchUpdateOrigin($post,$this->userInfo);
        return $result;
    }
}
