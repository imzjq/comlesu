<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/21
 * Time: 23:01
 */
//流量带宽点击数统计
namespace frontend\controllers;


use frontend\models\Flow;

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
        return $this->success($data);
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

    public function actionLink(){
        $post = $this->request->post();
        $data = $this->model->links($post,$this->userInfo);
        return $data;
    }

    public function actionDdos(){
        $post = $this->request->post();
        $data = $this->model->ddos($post,$this->userInfo);
        return $data;
    }

    public function actionDdosRecord(){
        $post = $this->request->post();
        $data = $this->model->ddosrecord($post,$this->userInfo);
        return $data;
    }

    public function actionTraffic(){
        $post = $this->request->post();
        $data = $this->model->traffic($post,$this->userInfo);
        return $data;
    }

}
