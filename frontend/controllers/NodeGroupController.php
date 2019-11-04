<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/31
 * Time: 21:51
 */

namespace frontend\controllers;

use frontend\models\CountryType;
use frontend\models\Node;
use frontend\models\NodeGroup;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use yii\db\Expression;

class NodeGroupController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new NodeGroup();
    }
    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);

        $where = [];
        $group_name = $this->request->post('group_name',''); //分组名
        if($group_name){
            $where[]= ['like','group_name',$group_name];
        }

        $id = $this->request->post('id','');
        if($id){
            $where[]= ['id'=>$id];
        }
        //节点IP搜索
        $ip = $this->request->post('ip','');
        if($ip){
            $node = Node::find()->where(['ip'=>$ip])->select('id')->one();
            if($node){
                $node_id = $node->id;
                $where[] = new Expression("FIND_IN_SET(${node_id}, node_id)");
            }else{
                //不存在，用ID等于0去查，相当于查无数据作用
                $where[]= ['id'=>0];
            }
        }

        $remarks = $this->request->post('remarks',''); //分组名称
        if($remarks){
            $where[]= ['remarks'=>$remarks];
        }
        $isDefault = $this->request->post('isDefault',''); //是否默认
        if($isDefault===1 || $isDefault===0){
            $where[]= ['isDefault'=>$isDefault];
        }

        $type = $this->request->post('type',''); //type
        if($type){
            $where[]= ['type'=>$type];
        }
        $result = $this->model->getList($page,$pagenum,$where);
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }
    //获取所有节点
    public function actionGetNode(){
        //$model = new Node();
        //$res = $model->idToIp();
        $res = Node::find()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['key'] = $v['id'];
                $tmp['label'] = $v['ip'].'--'.$v['name'];
                $data[] = $tmp;
            }
        }

        return $this->success($data);
    }

    //获取countryType
    public function actionGetCountryType(){
        $model = new CountryType();
        $res = $model->idToType();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value'] = $k;
                $tmp['label'] = $v;
                $data[] = $tmp;
            }
        }

        return $this->success($data);
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
        $result = $this->model->updateGroup($data);
        return $result;
    }

    //删除
    public function actionDel(){
        $id = $this->request->post('id');
        $result = $this->model->delGroup($id);
        return $result;
    }

    public function actionSetDefault(){
        $data = $this->request->post();
        $result = $this->model->setDefault($data);
        return $result;
    }
}
