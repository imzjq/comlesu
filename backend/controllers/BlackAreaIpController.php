<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:35
 */

namespace backend\controllers;
use backend\models\BlackAreaIp;
use Yii;
class BlackAreaIpController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new BlackAreaIp();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $where = [];
        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['package_id'=>$package_id];
        }
        $user_id = $this->request->post('user_id','');
        if($user_id){
            $where[]= ['user_id'=>$user_id];
        }
        $result = $this->model->getList($page,$pagenum,$where,$this->userInfo['uid']);
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
        $data = $this->request->post();
        $result = $this->model->del($data);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    /**
     * 获取国内的地区
     */
    public function actionGetHomeIparea()
    {
        $result = $this->model->getHomeIparea();
        return $result;
    }

    /**
     * 获取国外的地区
     */
    public function actionGetAbroadIparea()
    {
        $result = $this->model->getAbroadIparea();
        return $result;
    }





}
