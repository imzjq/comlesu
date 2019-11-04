<?php
/**
 * 代理商管理
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/3
 * Time: 14:54
 */

namespace backend\controllers;


use backend\models\Agent;

class AgentController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Agent();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        //用户名搜索
        $username = $this->request->post('username','');
        $company = $this->request->post('company','');
        $where = [];
        if(!empty($username)){
            $where[] = ['like','username',$username];
        }

        if(!empty($company)){
            $where[] = ['like','company',$company];
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

    //修改信息
    public function actionUpdateInfo(){
        $result = $this->model->updateInfo($this->request->post());
        return $result;
    }

    //删除
    public function actionDel(){
        $id = $this->request->post('id',0);
        $result = $this->model->del($id);
        return $result;
    }
}
