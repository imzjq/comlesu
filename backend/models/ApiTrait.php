<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/29
 * Time: 23:31
 */
namespace backend\models;
trait ApiTrait{

    public $pagenum = 10;

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
    public static function getModelError($model) {
        $errors = $model->getErrors();    //得到所有的错误信息
        if(!is_array($errors)){
            return '保存失败1';
        }

        $firstError = array_shift($errors);
        if(!is_array($firstError)) {
            return '保存失败2';
        }
        return array_shift($firstError);
    }

    //将数组中的数字 强制转整形
    public function numToInt($data){
        if(is_array($data)){
            foreach ($data as $k =>$v){
                if(is_array($v)){
                    //递归
                    $this->numToInt($v);
                }else{
                    if(is_numeric($v)){
                        $data[$k] = (int) $v;
                    }
                }
            }
        }else{
            $data = (int) $data;
        }
        return $data;
    }

}
