<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/29
 * Time: 21:02
 */

namespace backend\controllers;

use yii\web\Controller;
class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $request;
    public $defaultLimit = 10;


    public function init(){
        if(!\Yii::$app->request->isPost){
            echo json_encode(['code'=>5001,'msg'=>'非法访问','data'=>'']);die;
        }
        $this->request = \Yii::$app->request;
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    public function success($data='',$msg='success'){
    return [
        'code'=>200,
        'msg'=>$msg,
        'data'=>$data
    ];
}

    public function error($msg='服务器错误',$code=500,$data=''){
        return [
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data
        ];
    }


}
