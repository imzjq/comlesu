<?php
/**
 * 路由管理
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/10
 * Time: 15:22
 */

namespace frontend\controllers;


use frontend\models\Route;
use frontend\models\Iparea;
use common\models\IpdatabaseSimplify;

class RouterController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Route();
    }


    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',$this->defaultLimit);
        //搜索
        $where = [];
        $ip = $this->request->post('ip');
        $group_id = $this->request->post('group_id','');
        if($ip){
            $ipModel = new IpdatabaseSimplify();
            $group_id = $ipModel->ipGetGroupId($ip);
            if(!$group_id){
                return $this->error('未找到相应分组');
            }
        }


        if($group_id){
            $where[] = ['group_id'=>$group_id];
        }
        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }


    //批量修改ms，根据country_id, service_id， 获取group_id. 再根据group_id修改lc_route中的ms值
    public function actionBatchMs(){
        $group_ids = $this->request->post('group_id','');
        $node_id = $this->request->post('node_id','');
        $ms = $this->request->post('ms','');
//        if(!$group_ids){
//            $group_ids = $this->model->getGroupId($country_id,$service_id);
//        }
        $res = $this->model->updateMs($group_ids,$ms,$node_id);
        return $res;
    }




    public function actionUpdateInfo()
    {
        $data = \Yii::$app->request->post();
        $res = $this->model->updateInfo($data);
        return $res;
    }



}
