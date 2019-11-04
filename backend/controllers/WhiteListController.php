<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/18
 * Time: 21:15
 */

namespace backend\controllers;


use backend\models\WhiteList;

class WhiteListController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new WhiteList();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $dns = $this->request->post('dns','');
        $ip = $this->request->post('ip','');
        $where = [];
        if($dns){
            $where[]= ['like','dns',$dns];
        }
        if($ip){
            $where[]= ['like','ip',$ip];
        }
        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }
}
