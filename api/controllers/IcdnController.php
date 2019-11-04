<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/10
 * Time: 20:45
 */

namespace api\controllers;


use backend\models\ApiTrait;
use common\models\User;
use yii\web\Controller;
header("Access-Control-Allow-Origin: *");
class IcdnController extends Controller
{
    use ApiTrait;
    public $enableCsrfValidation = false;
    public function init(){
        parent::init();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }
    public function actionRegister()
    {
        $data = \Yii::$app->request->post();
        $username = $data['email'];
        $password = $data['password'];
        $password2 = $data['password2'];
        if(empty($username))
            return $this->error('邮箱不能为空');
        if($password != $password2)
            return $this->error('两次密码需要一致');
        if(!preg_match(' /^[!-~]{6,14}$/',$password))
            return $this->error('密码为6-14位,不能包含空格');

        $model = User::find()->where(['username'=>$username])->count();
        if($model)
            return $this->error('邮箱已经存在');
        $user = new User();
        $user->username = $username;
        $user->password = md5($password);
        $user->email = $username;
        $user->status = 1;
        $user->registsource = "cdnunions";
        $user->create_time = time();
        $user->level = 0 ;
        $user->agentid = 0 ;
        if($user->save() && $user->validate())
        {
           return  $this->success("","注册成功");
        }else{
            $msg = ($this->getModelError($user));
            return $this->error($msg);
        }
    }
}
