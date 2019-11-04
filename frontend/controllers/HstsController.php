<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:35
 */

namespace frontend\controllers;


use frontend\models\Hsts;
use Yii;
class HstsController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Hsts();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);

        $where = [];
        $where[] = ['user_id'=>$this->userInfo['uid']];
        $url = $this->request->post('url','');
        if($url){
            $where[]= ['like','name',$url];
        }
        $result = $this->model->getList($page,$pagenum,$where,$this->userInfo['uid']);
        return $result;
    }


    //新增
    public function actionAdd(){
        $data = $this->request->post();
        $result = $this->model->add($data,$this->userInfo['uid']);
        return $result;
    }

    //删除
    public function actionDel(){
        $id = $this->request->post('id');
        $result = $this->model->del($id,$this->userInfo['uid']);
        return $result;
    }







}
