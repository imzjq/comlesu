<?php
/**
 * DNS服务器管理
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/11
 * Time: 9:43
 */

namespace backend\controllers;

use backend\models\DnsServer;
class DnsController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new DnsServer();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $ip = $this->request->post('ip','');
        $where = [];
        if($ip){
            $where[]= ['like','ip',$ip];
        }
        //开关
        $switch = $this->request->post('switch','');
        if($switch){
            $where[] = ['in','switch',$switch];
        }
        //禁止
        $forbidden = $this->request->post('forbidden','');
        if($forbidden){
            $where[] = ['in','forbidden',$forbidden];
        }

        //状态
        $status = $this->request->post('status','');
        if($status){
            $where[] = ['in','status',$status];
        }

        //dns 来源类型
        $type = $this->request->post('type','');
        if($type){
            $where[] = ['in','type',$type];
        }

        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $result = $this->model->add($this->request->post());
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
        $result = $this->model->updateInfo($data);
        return $result;
    }

    //节点开关
    public function actionSwitch(){
        $data = $this->request->post();
        $result = $this->model->changeStatus($data);
        return $result;
    }

    public function actionDel(){
        $result = $this->model->del($this->request->post());
        return $result;
    }

}
