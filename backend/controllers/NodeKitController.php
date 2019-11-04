<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:35
 */

namespace backend\controllers;


use backend\models\NodeKit;
use Yii;
class NodeKitController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new NodeKit();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);

        $where = [];
        $name = $this->request->post('name',''); //分组名
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

    public function actionUpload(){
        $result = $this->model->upload();
        return $result;
    }

    public function actionDownload(){
        $id = $this->request->post('id');
        $model = NodeKit::findOne($id);
        if(!$model){
            return $this->error('参数错误');
        }
        $ats = $model->ats;
        if(!$ats){
            return $this->error('文件不存在');
        }
        $file_name = $ats;
        $file_dir = Yii::getAlias('@dns_file').'/nodeKit/';
        //检查文件是否存在
        if (! file_exists ( $file_dir . $file_name )) {
            return $this->error('文件不存在');
        } else {
            //以只读和二进制模式打开文件
            $filepath = $file_dir . $file_name;
            $fp=fopen($filepath,"r");
            $filesize=filesize($filepath);
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Accept-Length:".$filesize);
            header("Content-Disposition: attachment; filename=".$file_name);
            $buffer=1024;
            $buffer_count=0;
            while(!feof($fp)&&$filesize-$buffer_count>0){
                $data=fread($fp,$buffer);
                $buffer_count+=$buffer;
                echo $data;
            }
            fclose($fp);
        }
    }


}
