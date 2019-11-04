<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/10
 * Time: 21:28
 */

namespace api\models;


use yii\base\Model;
class Base extends Model
{

    public static function success($data='',$msg='success'){
        return [
            'code'=>200,
            'msg'=>$msg,
            'data'=>$data
        ];
    }

    public static function error($msg='无数据',$code=500,$data=''){
        return [
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data
        ];
    }
    public static function getModelError($model) {
        $errors = $model->getErrors();    //得到所有的错误信息
        if(!is_array($errors)){
            return '操作失败1';
        }

        $firstError = array_shift($errors);
        if(!is_array($firstError)) {
            return '操作失败2';
        }
        return array_shift($firstError);
    }

    public static function recordLog($data=''){
        if($data){
            $path = \Yii::getAlias('@runtime');
            $path = $path .'/logs/'.date('Y-m-d').'api_log.txt';
            file_put_contents($path,$data);
        }
    }

}
