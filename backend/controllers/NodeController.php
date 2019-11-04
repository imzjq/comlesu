<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:17
 */

namespace backend\controllers;


use backend\models\Node;
use common\lib\Utils;
use common\models\NodeTagId;
use yii\helpers\ArrayHelper;

class NodeController extends AuthController
{

    protected $model;
    public function init(){
        parent::init();
        $this->model = new Node();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $ip = $this->request->post('ip','');
        $where = [];
        if($ip){
            $where[]= ['like','ip',$ip];
        }

        $name = $this->request->post('name','');
        if($name){
            $where[]= ['like','name',$name];
        }
        $cluster = $this->request->post('cluster','');
        if($cluster){
            $where[] = ['cluster'=>$cluster];
        }

        //开关
        $switch = $this->request->post('switch','');
        if($switch ===0 || $switch ===1){
            $where[] = ['switch'=>$switch];
        }
        //禁止
        $forbidden = $this->request->post('forbidden','');
        if($forbidden===0 || $forbidden==1){
            $where[] = ['forbidden'=>$forbidden];
        }

        $kit_id = $this->request->post('kit_id','');
        if($kit_id){
            $where[] = ['kit_id'=>$kit_id];
        }

        $tag_ids = $this->request->post('tag_ids','');
        if($tag_ids){
            $tagData = NodeTagId::find()->where(['tag_id'=>$tag_ids])->asArray()->all();
            $map_id = ArrayHelper::map($tagData,'node_id','node_id');
            $where[] = ['in','id',$map_id];
        }

        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }

    //新增节点
    public function actionAdd(){
        $result = $this->model->add($this->request->post());
        return $result;
    }

    //修改
    public function actionUpdate(){
        $data = $this->request->post();
        $result = $this->model->updateNode($data);
        return $result;
    }

    //节点开关
    public function actionSwitch(){
        $data = $this->request->post();
        $result = $this->model->onAndOff($data);
        return $result;
    }

    //集群
    public function actionCluster(){
        $data = $this->request->post();
        $result = $this->model->setClusters($data);
        return $result;
    }

    public function actionDel(){
        $result = $this->model->del($this->request->post());
        return $result;
    }

}
