<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/12
 * Time: 21:48
 */

namespace backend\controllers;


use backend\models\Drsd;

class DrsdController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Drsd();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $dname = $this->request->post('dname','');
        $username = $this->request->post('username','');
        $countryType = $this->request->post('countryType','');
        $typeTrue = false;
        $where = [];
        if($dname){
            $where[]= ['like','{{%drsd}}.dname',$dname];
        }
        if($username){
            $where[]= ['like','{{%drsd}}.username',$username];
        }

        //状态
        $status = $this->request->post('status','');
        if(is_numeric($status)){
            $where[] = ['in','{{%drsd}}.status',$status];
        }

        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['package_id'=>$package_id];
        }

        if($countryType){
            $where[]= ['{{%user}}.registsource'=>$countryType];
            $typeTrue = true;
        }
        $result = $this->model->getList($page,$pagenum,$where,$typeTrue);
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
    public function actionChangeStatus(){
        $data = $this->request->post();
        $result = $this->model->changeStatus($data);
        return $result;
    }

    public function actionDel()
    {
        $data = $this->request->post();
        $result = $this->model->del($data);
        return $result;
    }

    public function actionGetCname()
    {
        $data = $this->request->post();
        $result = $this->model->getCname($data);
        return $result;
    }


}
