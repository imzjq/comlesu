<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/18
 * Time: 14:50
 */

namespace backend\controllers;


use backend\models\User;
use backend\models\UserCer;

class UserCerController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new UserCer();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $domain = $this->request->post('domain','');
        $username = $this->request->post('username','');
        $is_valid = $this->request->post('is_valid','');
        $where = [];
        if($domain){
            $where[]= ['like','domain',$domain];
        }
        if($username){
            $where[]= ['like','username',$username];
        }
        if($is_valid)
        {
            if($is_valid == 1)
                $where[] =['<','cer_end_time',time()];
            else
                $where[] =['>','cer_end_time',time()];
        }

        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['package_id'=>$package_id];
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


    public function actionDel(){
        $id = $this->request->post('id',0);
        $result = $this->model->del($id);
        return $result;
    }

    public function actionDels(){
        $data = $this->request->post();
        $result = $this->model->dels($data);
        return $result;
    }


    //上传证书，公钥，私钥
    public function actionUpload(){
        $data = $_FILES;
        if($data && isset($data['file']['tmp_name'])){
            $file = $data['file']['tmp_name'];
            $file_content = file_get_contents($file);
            $ssl_content =  openssl_x509_parse($file_content);
            if($ssl_content)
            {
                unset($ssl_content['extensions']);
            }
            return $this->success(['content'=>$file_content,'ssl_content'=>$ssl_content]);
        }
        return $this->error('请选择文件');
    }


    //远程数据
    public function actionGetInfo()
    {
        //用户信息
        $userModel = new User();
        $userData = $userModel->getUsernameToUsername();
        $u_d = [];
        if($userData){
            foreach ($userData as $k=>$v){
                $u_t = [];
                $u_t['value']=$k;
                $u_t['label'] = $v;
                $u_d[] = $u_t;
            }
        }

        $data = [
            'userData'=>$u_d,
        ];
        return $this->success($data);

    }
}
