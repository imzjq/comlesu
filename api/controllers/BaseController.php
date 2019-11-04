<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/10
 * Time: 20:45
 */

namespace api\controllers;


use yii\web\Controller;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public function init(){
        parent::init();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    public function success($data='',$msg='success'){
        return [
            'code'=>200,
            'msg'=>$msg,
            'data'=>$data
        ];
    }

    public function error($msg='出错拉',$code=500,$data=''){
        return [
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data
        ];
    }

    public static function recordLog($data=''){
        if($data){
            $path = \Yii::getAlias('@runtime');
            $path = $path .'/logs/'.date('Y-m-d').'api_log.txt';
            file_put_contents($path,$data."\n",FILE_APPEND);
        }
    }
}
