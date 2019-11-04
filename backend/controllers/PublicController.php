<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 22:32
 */

namespace backend\controllers;

//一些公共方法
use backend\models\CountryType;
use backend\models\Defence;
use backend\models\DefenceIp;
use backend\models\Domain;
use backend\models\NodeKit;
use backend\models\NodeTag;
use backend\models\Package;
use backend\models\User;
use yii\helpers\ArrayHelper;

class PublicController extends AuthController
{
    public function actionCountryTypeMap(){
        $model = new CountryType();
        $res = $model->idToType();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$k;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionCountryTypeNewMap(){
        $model = new CountryType();
        $res = $model->idToType();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$v;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    //获取用户信息
    public function actionGetUserOptions(){
        //用户信息
        $userModel = new User();
        $userData = $userModel->getIdToUsername();
        $u_d = [];
        if($userData){
            foreach ($userData as $k=>$v){
                $u_t = [];
                $u_t['value']=$k;
                $u_t['label'] = $v;
                $u_t['name'] = $v;
                $u_d[] = $u_t;
            }
        }
        $data = [
            'userOptions'=>$u_d,
        ];
        return $this->success($data);
    }

    //获取defenceip
    public function actionGetDefenceIpOptions(){
        //用户信息
        $model = new DefenceIp();
        $res = $model->idToCname();
        $d = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp = [];
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $d[] = $tmp;
            }
        }
        $data = [
            'defenceIpOptions'=>$d,
        ];
        return $this->success($data);
    }

    //获取domain  id=>dname
    public function actionGetDomainOptions(){
        //用户信息
        $model = new Domain();
        $res = $model->idToDname();
        $d = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp = [];
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $tmp['name'] = $v;
                $d[] = $tmp;
            }
        }
        $data = [
            'domainOptions'=>$d,
        ];
        return $this->success($data);
    }

    //获取domain  id=>dname
    public function actionGetFlowDomainOptions(){

//        $db = \Yii::$app->dbFlow;
//        $sql = "select  id,dname from {{%domain}} ";
//        $res = $db->createCommand($sql)->queryAll();
//        $res = ArrayHelper::map($res,'id','dname');
//        $d = [];
//        if($res){
//            foreach ($res as $k=>$v){
//                $tmp = [];
//                $tmp['value']=$k;
//                $tmp['label'] = $v;
//                $tmp['name'] = $v;
//                $d[] = $tmp;
//            }
//        }
//        $data = [
//            'domainOptions'=>$d,
//        ];
//        return $this->success($data);

        $db = \Yii::$app->db;
        $sql = "select  id,dname,1   from {{%domain}} UNION ALL select  id,dname,2 from {{%defence}}";
        $res = $db->createCommand($sql)->queryAll();
        $d = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp = [];
                $tmp['value']=$v['id']."_".$v['1'];
                $tmp['label'] = $v['dname'];
                $tmp['name'] = $v['dname'];
                $d[] = $tmp;
            }
        }
        $data = [
            'domainOptions'=>$d,
        ];
        return $this->success($data);


    }


    //获取defence id=>domains
    public function actionGetDefenceOptions(){
        //用户信息
        $model = new Defence();
        $res = $model->idToDomains();
        $d = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp = [];
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $tmp['name'] = $v;
                $d[] = $tmp;
            }
        }
        $data = [
            'defenceOptions'=>$d,
        ];
        return $this->success($data);
    }

    public function actionNodeKitMap(){
        $model = new NodeKit();
        $res = $model->idToName();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionNodeTagMap(){
        $model = new NodeTag();
        $res = $model->idToName();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionGetDomainUsers()
    {
        $db = \Yii::$app->db;
        $sql = "select  id,username from {{%domain}} group by username union select id,username from {{%defence}} group by username ";
        $res = $db->createCommand($sql)->queryAll();
        //$res = Domain::find()->select('id,username')->groupBy('username')->asArray()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$v['username'];
                $tmp['label'] = $v['username'];
                $tmp['name'] = $v['username'];
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    //获取所有节点
    public function actionGetPackage(){
        $res = Package::find()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=(int)$v['id'];
                $tmp['name'] = $v['name'];
                $tmp['label'] = $v['name'];
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionGetUserToPackage()
    {
        $username = \Yii::$app->request->post('username');
       $user = new User();
       $user_id = $user->getUserIdByUsername($username);
       if(!$user_id)
           return $this->error('找不到用户信息');

        $model = new \frontend\models\Package();
        $res =  $model->idToName($user_id);
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionGetUseridToPackage()
    {
        $user_id = \Yii::$app->request->post('user_id');
        if(!$user_id)
            return $this->error('找不到用户信息');
        $model = new \frontend\models\Package();
        $res =  $model->idToName($user_id);
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$k;
                $tmp['label'] = $v;
                $tmp['name'] = $v;
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

}
