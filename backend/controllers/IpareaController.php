<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/9
 * Time: 22:44
 */

namespace backend\controllers;


use backend\models\Iparea;

class IpareaController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Iparea();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        //搜索
        $country = $this->request->post('country','');
        $where = [];
        if(!empty($country)){
            $where[] = ['like','country',$country];
        }
        $province = $this->request->post('province','');
        if(!empty($province)){
            $where[] = ['like','province',$province];
        }

        $city = $this->request->post('city','');
        if(!empty($city)){
            $where[] = ['like','city',$city];
        }
        $id = $this->request->post('group_id','');
        if($id){
            $where[] = ['id'=>$id];
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
//        $id = $this->request->post('id',0);
        $result = $this->model->del($data);
        return $result;
    }

    public function actionGetProvinceMap(){
        $data = [];
        $res = $this->model->getProvinceMap();
        if($res){
            foreach ($res as $k=>$v){
                $tmp=[];
                $tmp['label'] = $k;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionGetServiceMap(){
        $data = [];
        $res = $this->model->getServiceMap();
        if($res){
            foreach ($res as $k=>$v){
                $tmp=[];
                if($k<=0){
                    continue;
                }
                $tmp['label'] = $k;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }

        }
        return $this->success($data);
    }

    public function actionGetAll(){
        $service_id = $this->request->post('checkedService','');
        $where = [];
        if(!empty($service_id)){
            $where[] = ['in','service_id',$service_id];
        }

        $province_id = $this->request->post('checkedProvince','');
        if(!empty($province_id)){
            $where[] = ['in','province_id',$province_id];
        }

        $id = $this->request->post('group_id','');
        if($id){
            $where[] = ['id'=>$id];
        }
        $res = $this->model->getALl($where);
        return $res;

    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

}
