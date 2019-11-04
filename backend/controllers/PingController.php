<?php
/**
 * lc_ls_ipku 表，分发到各个节点去ping的IP
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/10
 * Time: 22:40
 */

namespace backend\controllers;


use backend\models\Ipdatabase;
use backend\models\Ping;
use common\lib\Utils;
use common\models\LsIpku;

class PingController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new Ping();
    }


    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $ip = $this->request->post('ip','');
        $where = [];
        if($ip){
            $where[]= ['like','ip',$ip];
        }
        $group_id = $this->request->post('group_id','');
        if($group_id){
            $where[] = ['group_id'=>$group_id];
        }

        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }

    public function actionAdd(){
        $post = $this->request->post();
        $ip = $this->request->post('ip','');
        if(empty($ip)){
            return $this->error('请输入IP');
        }
        $check = Utils::isIp($ip);
        if(!$check){
            return $this->error('IP格式错误');
        }

        $ipModel = new Ipdatabase();
        $group_id = $ipModel->ipGetGroupId($ip);
        if(!$group_id){
            return $this->error('未找到相应分组号');
        }
        $post['group_id'] = $group_id;
        $result =$this->model->add($post);
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

    public function actionDel()
    {
        $data = $this->request->post();
        $result = $this->model->del($data);
        return $result;
    }

}
