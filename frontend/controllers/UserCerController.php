<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/18
 * Time: 14:50
 */

namespace frontend\controllers;


use frontend\models\User;
use frontend\models\UserCer;

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

        $is_valid = $this->request->post('is_valid','');
        $where = [];
        $where[] = ['user_id'=>$this->userInfo['uid']];
        if($domain){
            $where[]= ['like','domain',$domain];
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

        $result = $this->model->getList($page,$pagenum,$where,$this->userInfo['uid']);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $result = $this->model->add($this->request->post(),$this->userInfo);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id,$this->userInfo);
        return $result;
    }



    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateInfo($data,$this->userInfo);
        return $result;
    }


    public function actionDel(){
        $id = $this->request->post('id',0);
        $result = $this->model->del($id,$this->userInfo);
        return $result;
    }

    public function actionDels(){
        $data = $this->request->post();
        $result = $this->model->dels($data,$this->userInfo);
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

    public function actionBatchAdd()
    {
        $data = $this->request->post();
        $result = $this->model->batchAdd($data,$this->userInfo);
        return $result;
    }

//    //上传证书，公钥，私钥
//    public function actionBatchUpload(){
//        $data = $_FILES;
//        if($data && isset($data['file']['tmp_name'])){
//
//           $result = $this->fileErrer($data,['key','cer','crt']);
//           if($result['code'] != 200 )
//               return $result;
//
//            $file = $data['file']['tmp_name'];
//            $file_content = file_get_contents($file);
//            return $this->success(['content'=>$file_content,'filename'=>$data['file']['name']]);
//        }
//        return $this->error('请选择文件');
//    }

    public function actionBatchPvUpload(){
        $data = $_FILES;
        if($data && isset($data['file']['tmp_name'])){

            $result = $this->fileErrer($data,['key','cer','crt']);
            if($result['code'] != 200 )
                return $result;

            $type = 'pv';
            if($result['data']['suff'] == 'key')
                $type  = 'pb' ;

            $file = $data['file']['tmp_name'];
            $file_content = file_get_contents($file);
            return $this->success(['content'=>$file_content,'filename'=>$data['file']['name'],'type'=>$type]);
        }
        return $this->error('请选择文件');
    }


    public function fileErrer($data,$suff = [])
    {
        $name = $data['file']['name'];
        $arr = explode('.',$name);
        $end = end($arr);
        $suff_name = "";
        foreach ($suff as $val)
            $suff_name .=$val.",";
        $suff_name = rtrim($suff_name,",");
        if(!in_array($end,$suff))
            return $this->error($name.'文件名错误,需要已'.$suff_name.'结尾');

        $size = $data['file']['size'];
        if(!$size)
            return $this->error($name.'文件无内容');
        if($size/1024.2 > 100)
            return $this->error('单个文件不能大于100K');

        return $this->success(['suff'=>$end]);
    }


}
