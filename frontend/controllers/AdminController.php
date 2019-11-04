<?php
/**
 * 管理后台用户
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/29
 * Time: 22:57
 */

namespace frontend\controllers;

use frontend\models\AdminInfo;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;

class AdminController extends AuthController
{
    protected $adminModel;
    public function init(){
        parent::init();
        $this->adminModel = new AdminInfo();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',20);
        $result = $this->adminModel->getAdminList($page,$pagenum);
        return $result;

    }


    //新增
    public function actionAdd(){

        $result = $this->adminModel->add($this->request->post());
        return $result;
    }

    //修改密码
    public function actionChangePassword(){
        $id = $this->request->post('id',0);
        $pwd = $this->request->post('password','');
        $result = $this->adminModel->changePassword($id,$pwd);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->adminModel->getOne($id);
        return $result;
    }

}
