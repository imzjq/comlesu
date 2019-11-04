<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/7
 * Time: 22:44
 */

namespace backend\controllers;


use backend\models\DefenceIp;

class DefenceIpController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new DefenceIp();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $cname = $this->request->post('cname','');
        $where = [];
        if($cname){
            $where[]= ['like','cname',$cname];
        }

        $remark = $this->request->post('remark','');
        if($remark){
            $where[] = ['in','remark',$remark];
        }

        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $data = $this->request->post();
        $ip1 = $this->request->post('ip1','');
        if(empty($ip1)){
            return $this->error('首选IP必须选择');
        }
        $ip2 = $this->request->post('ip2','');
        $data['ip'] = $ip1.'|'.$ip2;

        $result = $this->model->add($data);
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
        $ip1 = $this->request->post('ip1','');
        if(empty($ip1)){
            return $this->error('首选IP必须选择');
        }
        $ip2 = $this->request->post('ip2','');
        $data['ip'] = $ip1.'|'.$ip2;

        $result = $this->model->updateInfo($data);
        return $result;
    }

    public function actionDel(){
        $id = $this->request->post('id',0);
        $result = $this->model->del($id);
        return $result;
    }
}
