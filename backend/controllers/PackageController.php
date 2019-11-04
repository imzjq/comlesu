<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:35
 */

namespace backend\controllers;


use backend\models\Package;
use Yii;
class PackageController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Package();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);

        $where = [];
        $name = $this->request->post('name',''); //套餐名
        if($name){
            $where[]= ['like','name',$name];
        }
        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }


    //新增
    public function actionAdd(){
        $data = $this->request->post();
        $result = $this->model->add($data);
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data);
        return $result;
    }

    //删除
    public function actionDel(){
        $id = $this->request->post('id');
        $result = $this->model->del($id);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    //获取所有节点
    public function actionGetPack(){

        $res = Package::find()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['key'] = $v['id'];
                $tmp['label'] = $v['name'];
                $data[] = $tmp;
            }
        }

        return $this->success($data);
    }

    public function actionGetUserPack(){

        $id = $this->request->post('id',0);
        $result = $this->model->getUserPack($id);
        return $result;
    }




}
