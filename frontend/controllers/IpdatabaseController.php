<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/9
 * Time: 20:51
 */

namespace frontend\controllers;


use frontend\models\Ipdatabase;

class IpdatabaseController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Ipdatabase();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        //搜索
        $ip = $this->request->post('ip','');
        $where = [];
        if(!empty($ip)){
            $where[] = ['like','ip',$ip];
        }
        $group_id = $this->request->post('group_id','');

        $ip_overlap = $this->request->post('ip_overlap','');
        if($ip_overlap){
            $ids = $this->model->ipGetIds($ip_overlap);

            $where[] = ['in','id',$ids];

        }

        if($group_id){
            $where[] = ['group_id'=>$group_id];
        }



        $result = $this->model->getList($page,$pagenum,$where);
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
        $data = $this->request->post();
        $result = $this->model->del($data);
        return $result;
    }

    //根据IP段查询分组号

}
