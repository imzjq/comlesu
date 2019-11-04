<?php
/**
 * 客户高防域名管理
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/7
 * Time: 22:44
 */

namespace backend\controllers;

use backend\models\Defence;
use backend\models\User;
use yii\db\Expression;

class DefenceController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Defence();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $countryType = $this->request->post('countryType','');
        $where = [];
        $typeTrue = false;
        $username = $this->request->post('username','');
        if($username){
            $user_id = User::find()->where(['username'=>$username])->one();
            if($user_id){
                $where[] = ['{{%defence}}.user_id'=>$user_id['id']];
            }else{
                $where[] = ['{{%defence}}.user_id'=>0];
            }

        }
        if($countryType){
            $where[]= ['{{%user}}.registsource'=>$countryType];
            $typeTrue = true;
        }

        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['{{%defence}}.package_id'=>$package_id];
        }

        $result = $this->model->getList($page,$pagenum,$where,$typeTrue);
        return $result;
    }

    //状态修改
    public function actionChangeStatus(){
        $data = $this->request->post();
        $result = $this->model->changeStatus($data);
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

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data);
        return $result;
    }

    public function actionDel(){
        $data = $this->request->post();
        $result = $this->model->del($data);
        return $result;
    }

    //预览remap
    public function actionPreview(){
        $result = $this->model->generateRemap($this->request->post());
        return $this->success($result);
    }
}
