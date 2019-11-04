<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/21
 * Time: 23:01
 */
//流量带宽点击数统计
namespace backend\controllers;


use backend\models\Flow;

class FlowController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Flow();
    }

    //流量查询
    public function actionFlow(){
        $post = $this->request->post();
        $data = $this->model->flowStat($post);
        return $this->success($data);
    }

    public function actionBandwidth(){
        $post = $this->request->post();
        $data = $this->model->bandwidth($post);
        return $this->success($data);
    }

    public function actionHit(){
        $post = $this->request->post();
        $data = $this->model->hit($post);
        return $this->success($data);
    }

    public function actionLink(){
        $post = $this->request->post();
        $ip = $this->request->post('ip','');
        $node_ip = $this->request->post('node_ip','');
        $where = [];
        if($ip)
        {
            $where[] = ['ip'=>$ip];
        }
        if($node_ip)
        {
            $where[] = ['node_ip'=>$node_ip];
        }
        $data = $this->model->links($post,$where);
        return $data;
    }

    public function actionLog(){
        $post = $this->request->post();
        $data = $this->model->log($post);
        return $data;
    }
    public function actionDns(){
        $post = $this->request->post();
        $data = $this->model->dns($post);
        return $data;
    }

    public function actionApi(){
        $post = $this->request->post();
        $data = $this->model->api($post);
        return $data;
    }

    public function actionTraffic(){
        $post = $this->request->post();
        $data = $this->model->traffic($post);
        return $data;
    }
    public function actionTime(){
        $post = $this->request->post();
        $data = $this->model->time($post);
        return $data;
    }

    public function actionLm(){
        $post = $this->request->post();
        $data = $this->model->lm($post);
        return $data;
    }

    public function actionStatistics(){
        $post = $this->request->post();
        $data = $this->model->statistics($post);
        return $this->success($data);
    }

    public function actionOrims(){
        $post = $this->request->post();
        $data = $this->model->orims($post);
        return $data;
    }


}
