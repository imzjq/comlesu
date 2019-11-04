<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 22:32
 */

namespace frontend\controllers;

//一些公共方法
use common\lib\Utils;
use common\models\BlackIp;
use common\models\PackageUser;
use common\models\UserCer;
use common\models\WhiteIp;
use frontend\models\Brand;
use frontend\models\Defence;
use frontend\models\Domain;
use frontend\models\Drsd;
use frontend\models\Package;
use yii\db\Query;
use yii\helpers\ArrayHelper;


class PublicController extends AuthController
{

    /**
     * 品牌
     * @return array
     */
    public function actionGetBrand()
    {
        $model = new Brand();
        $res =  $model->idToName($this->userInfo['uid']);
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

    /**
     * 获取加速和高防的域名
     */
    public function actionGetDomainDefence()
    {
        $user_id = $this->userInfo['uid'];

        $query = new Query();
        $query->where(['user_id'=>$user_id])->select("id,dname")->from('{{%domain}}');
        $anotherQuery = new Query();
        $anotherQuery->where(['user_id'=>$user_id])->select("id,dname")->from('{{%defence}}');
        $res =  $query->union($anotherQuery)->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$v['id'];
                $tmp['label'] = $v['dname'];
                $tmp['name'] = $v['dname'];
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }


    public function actionGetPackage()
    {
        $model = new Package();
        $res =  $model->idToName($this->userInfo['uid']);
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

    public function actionGetDomainRemap()
    {
        $data = \Yii::$app->request->post();
        if(!empty($data['ip']))
            $where [] = ['{{%remap}}.aimurl'=>$data['ip']] ;
        if(!empty($data['brand_id']))
            $where [] = ['{{%domain}}.brand_id'=>$data['brand_id']] ;

        if(!empty($data['package_id']))
            $where [] = ['{{%domain}}.package_id'=>$data['package_id']] ;

        $where [] = ['{{%domain}}.user_id'=>$this->userInfo['uid']];
        $res = Domain::find();
        if($where)
        {
            foreach ($where as $val)
                $res->andWhere($val);
        }
        $res =$res->select('{{%remap}}.id,{{%remap}}.originurl,{{%remap}}.dname,{{%remap}}.visit_protocol')->leftJoin('{{%remap}}','{{%remap}}.did = {{%domain}}.id')->asArray()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$v['id'];
                $originurl =  $v['originurl'];
                if($originurl == '@')
                    $originurl = '';
                else
                    $originurl .= '.';
                $tmp['label'] = $v['visit_protocol'].$originurl.$v['dname'];
                $tmp['name'] = $v['visit_protocol'].$originurl.$v['dname'];
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }


    public function actionGetDefenceRemap()
    {
        $data = \Yii::$app->request->post();
        if(!empty($data['ip']))
            $where [] = ['{{%defence_remap}}.aimurl'=>$data['ip']] ;
        if(!empty($data['brand_id']))
            $where [] = ['{{%defence}}.brand_id'=>$data['brand_id']] ;

        if(!empty($data['package_id']))
            $where [] = ['{{%defence}}.package_id'=>$data['package_id']] ;

        $where [] = ['{{%defence}}.user_id'=>$this->userInfo['uid']];

        $res = Defence::find();
        if($where)
        {
            foreach ($where as $val)
                $res->andWhere($val);
        }

        $res = $res->select('{{%defence_remap}}.id,{{%defence_remap}}.originurl,{{%defence_remap}}.visit_protocol')->leftJoin('{{%defence_remap}}','{{%defence_remap}}.did = {{%defence}}.id')->asArray()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$v['id'];
                $tmp['label'] = $v['visit_protocol'].$v['originurl'];
                $tmp['name'] = $v['visit_protocol'].$v['originurl'];
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionGetDrsd()
    {
        $res = Drsd::find()->select('id,dname')->where(['user_id'=>$this->userInfo['uid']])->asArray()->all();

        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $tmp['value']=$v['id'];
                $tmp['label'] = $v['dname'];
                $tmp['name'] =  $v['dname'];
                $data[] = $tmp;
            }
        }
        return $this->success($data);
    }

    public function actionGetFlowDomainOptions(){

        $db = \Yii::$app->db;
        $sql = "select  id,dname,1   from {{%domain}} where user_id ={$this->userInfo['uid']} UNION ALL select  id,dname,2 from {{%defence}} where user_id ={$this->userInfo['uid']}";
        $res = $db->createCommand($sql)->queryAll();

        //$res = ArrayHelper::map($res,'id','dname');
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



    public function actionGetDomainDefenceUrl()
    {
        $data = \Yii::$app->request->post();
        if(!empty($data['url'])) {
            $where [] = ['like', '{{%defence_remap}}.originurl', $data['url']];
            $where2 [] = ['like', 'dname', $data['url']];
        }
        if(!empty($data['brand_id'])) {
            $where [] = ['{{%defence}}.brand_id' => $data['brand_id']];
            $where2 [] = ['brand_id' => $data['brand_id']];
        }

        if(!empty($data['package_id'])) {
            $where [] = ['{{%defence}}.package_id' => $data['package_id']];
            $where2 [] = ['package_id' => $data['package_id']];
        }



        $where [] = ['{{%defence}}.user_id'=>$this->userInfo['uid']];
        $where2 [] = ['user_id'=>$this->userInfo['uid']];

        $res = Defence::find();
        $res2 = Domain::find();
        if($where)
        {
            foreach ($where as $val)
                $res->andWhere($val);
            foreach ($where2 as $val)
                $res2->andWhere($val);
        }

        $res = $res->select('{{%defence_remap}}.id,{{%defence_remap}}.originurl')->leftJoin('{{%defence_remap}}','{{%defence_remap}}.did = {{%defence}}.id')->asArray()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $v['originurl'] = Utils::getUrlHost($v['originurl']);
                $data[] = $v['originurl'];
            }
        }

        $res2 =$res2->select('id,dname')->asArray()->all();
        if($res2){
            foreach ($res2 as $k=>$v){
                $v['dname'] = Utils::getUrlHost($v['dname']);
                $data[] = $v['dname'];
            }
        }
        $data = array_unique($data);
        return $this->success($data);
    }


    public function actionGetUserBaseConfig()
    {
         $uid = $this->userInfo['uid'];
         $data = [
             'domainCount'=>(int)\common\models\Domain::find()->where(['user_id'=>$uid])->count(),
             'defenceCount'=>(int)\common\models\Defence::find()->where(['user_id'=>$uid])->count(),
             'drsdCount'=>(int)\common\models\Drsd::find()->where(['user_id'=>$uid])->count(),
             'sslCount'=>(int)UserCer::find()->where(['user_id'=>$uid])->count(),
             'limitSslCount'=>(int)UserCer::find()->where(['user_id'=>$uid])->andWhere(['<','cer_end_time',time()])->count(),
             'whiteCount'=>(int)WhiteIp::find()->where(['user_id'=>$uid])->count(),
             'blackCount'=>(int)BlackIp::find()->where(['user_id'=>$uid])->count(),
             'packageCount'=>(int)PackageUser::find()->where(['user_id'=>$uid])->count(),
             'overSslCount'=>(int)UserCer::find()->where(['user_id'=>$uid])->andWhere(['>','cer_end_time',time()])->andWhere(['<','cer_end_time',strtotime("+1 month")])->count(),//即将过期
             ];
         return $this->success($data);
    }

    /**
     * 获取开启日志的顶级域名
     */
    public function actionGetTopLogUrl()
    {
        $where [] = ['{{%defence}}.user_id'=>$this->userInfo['uid']];
        $where2 [] = ['user_id'=>$this->userInfo['uid']];

        $where [] = ['{{%defence}}.status'=>2];
        $where2 [] = ['status'=>2];
        $where [] = ['{{%defence}}.log'=>1];
        $where2 [] = ['log'=>1];


        $res = Defence::find();
        $res2 = Domain::find();
        if($where)
        {
            foreach ($where as $val)
                $res->andWhere($val);
            foreach ($where2 as $val)
                $res2->andWhere($val);
        }

        $res = $res->select('{{%defence_remap}}.id,{{%defence_remap}}.originurl')->leftJoin('{{%defence_remap}}','{{%defence_remap}}.did = {{%defence}}.id')->asArray()->all();
        $data = [];
        if($res){
            foreach ($res as $k=>$v){
                $v['originurl'] = Utils::getUrlHost($v['originurl']);
                $data[] = $v['originurl'];
            }
        }

        $res2 =$res2->select('id,dname')->asArray()->all();
        if($res2){
            foreach ($res2 as $k=>$v){
                $v['dname'] = Utils::getUrlHost($v['dname']);
                $data[] = $v['dname'];
            }
        }
        $data = array_unique($data);
        return $this->success($data);
    }


    public function actionGetTime()
    {
        $data = \Yii::$app->request->post();
        $count = $data['count'];
        $toTime = date('Y-m-d');
        $fromTime = date('Y-m-d', strtotime('-'.$count.' days'));
        return $this->success([$fromTime,$toTime]);
    }

    /**
     * 黑名单获取全部域名
     */
    public function actionGetBlackUrl()
    {
        $data = \Yii::$app->request->post();
        $package_id = $data['package_id'];
        if(empty($package_id))
            return $this->error("请选择套餐");

        $where [] = ['{{%defence}}.user_id'=>$this->userInfo['uid']];
        $where2 [] = ['{{%domain}}.user_id'=>$this->userInfo['uid']];
        $where [] = ['{{%defence}}.status'=>2];
        $where2 [] = ['{{%domain}}.status'=>2];
        $where [] = ['{{%defence}}.package_id'=>$package_id];
        $where2 [] = ['{{%domain}}.package_id'=>$package_id];

        $res = Defence::find();
        $res2 = Domain::find();
        if($where)
        {
            foreach ($where as $val)
                $res->andWhere($val);
            foreach ($where2 as $val)
                $res2->andWhere($val);
        }
        $res = $res->select('{{%defence_remap}}.id,{{%defence_remap}}.originurl,{{%defence_remap}}.visit_protocol,{{%defence}}.id as defence_id')->leftJoin('{{%defence_remap}}','{{%defence_remap}}.did = {{%defence}}.id')->asArray()->all();
        $data = array();
        $data_temp = array();
       // $data[0]=['id'=>'defence','label'=>'多域名单CNAME','disabled'=>true,'children'=>[]];
        if($res){
            foreach ($res as $k=>$v){
                $data_temp[]= $v['visit_protocol'].$v['originurl'];
//                $tmp = [];
//                $tmp['id']=$v['id']."=defence=".$v['visit_protocol'].$v['originurl'];
//                $tmp['label'] = $v['visit_protocol'].$v['originurl'];
//                $data[0]['children'][] = $tmp;
            }
        }

        $res2 = $res2->select('{{%remap}}.id,{{%remap}}.dname,{{%remap}}.originurl,{{%remap}}.visit_protocol,{{%domain}}.id as domain_id')->leftJoin('{{%remap}}','{{%remap}}.did = {{%domain}}.id')->asArray()->all();
        //$data[1]=['id'=>'domain','label'=>'单域名单CNAME','disabled'=>true,'children'=>[]];
        if($res2){
            foreach ($res2 as $k=>$v){
                if($v['originurl'] == "@")
                {
                    $data_temp[]= $v['visit_protocol'].$v['dname'];
//                    $tmp = [];
//                    $tmp['id']=$v['id']."=domain=".$v['visit_protocol'].$v['dname'];
//                    $tmp['label'] =$v['visit_protocol'].$v['dname'];
//                    $data[1]['children'][] = $tmp;
                }elseif ($v['originurl'] == "*")
                {
                    $data_temp[]=$v['visit_protocol'].'(.*).'.$v['dname'];
//                    $tmp = [];
//                    $tmp['id']=$v['id']."=domain=".$v['visit_protocol'].'(.*).'.$v['dname'];
//                    $tmp['label'] = $v['visit_protocol'].'(.*).'.$v['dname'];
//                    $data[1]['children'][] = $tmp;
                }
                else{
                    $data_temp[]=$v['visit_protocol'].$v['originurl'].'.'.$v['dname'];
//                    $tmp = [];
//                    $tmp['id']=$v['id']."=domain=".$v['visit_protocol'].$v['originurl'].'.'.$v['dname'];
//                    $tmp['label'] = $v['visit_protocol'].$v['originurl'].'.'.$v['dname'];
//                    $data[1]['children'][] = $tmp;
                }
            }
        }

        $data_temp = array_unique($data_temp);
        if($data_temp)
        {
            foreach ($data_temp as $value)
            $data[]=['id'=>$value,'label'=>$value,'children'=>[]];
        }
        return $this->success($data);
    }
//    public function actionGetBlackUrl()
//    {
//        $data = \Yii::$app->request->post();
//        $package_id = $data['package_id'];
//        if(empty($package_id))
//            return $this->error("请选择套餐");
//
//        $where [] = ['{{%defence}}.user_id'=>$this->userInfo['uid']];
//        $where2 [] = ['{{%domain}}.user_id'=>$this->userInfo['uid']];
//        $where [] = ['{{%defence}}.status'=>2];
//        $where2 [] = ['{{%domain}}.status'=>2];
//        $where [] = ['{{%defence}}.package_id'=>$package_id];
//        $where2 [] = ['{{%domain}}.package_id'=>$package_id];
//
//        $res = Defence::find();
//        $res2 = Domain::find();
//        if($where)
//        {
//            foreach ($where as $val)
//                $res->andWhere($val);
//            foreach ($where2 as $val)
//                $res2->andWhere($val);
//        }
//        $res = $res->select('{{%defence_remap}}.id,{{%defence_remap}}.originurl,{{%defence_remap}}.visit_protocol,{{%defence}}.id as defence_id')->leftJoin('{{%defence_remap}}','{{%defence_remap}}.did = {{%defence}}.id')->asArray()->all();
//        $data = array();
//        if($res){
//            foreach ($res as $k=>$v){
//
//                $tmp = [];
//                $tmp['value']=$v['defence_id']."=defence=".$v['visit_protocol'].$v['originurl'];
//                $tmp['label'] = $v['visit_protocol'].$v['originurl'];
//                $data[] = $tmp;
//                //array_push($data,$v['visit_protocol'].$v['originurl']);
//                //$data[] = $v['visit_protocol'].$v['originurl'];
//            }
//        }
//
//        $res2 = $res2->select('{{%remap}}.id,{{%remap}}.dname,{{%remap}}.originurl,{{%remap}}.visit_protocol,{{%domain}}.id as domain_id')->leftJoin('{{%remap}}','{{%remap}}.did = {{%domain}}.id')->asArray()->all();
//
//        if($res2){
//
//            foreach ($res2 as $k=>$v){
//                if($v['originurl'] == "@")
//                {
//                   // $data[] =$v['visit_protocol'].$v['dname'];
//                   // array_push($data,$v['visit_protocol'].$v['dname']);
//                    $tmp = [];
//                    $tmp['value']=$v['domain_id']."=domain=".$v['visit_protocol'].$v['dname'];
//                    $tmp['label'] =$v['visit_protocol'].$v['dname'];
//                    $data[] = $tmp;
//
//                }elseif ($v['originurl'] == "*")
//                {
//                    $tmp = [];
//                    $tmp['value']=$v['domain_id']."=domain=".$v['visit_protocol'].'(.*).'.$v['dname'];
//                    $tmp['label'] = $v['visit_protocol'].'(.*).'.$v['dname'];
//                    $data[] = $tmp;
//                   // $data[] =$v['visit_protocol'].'(.*).'.$v['dname'];
//                   //array_push($data,$v['visit_protocol'].'(.*).'.$v['dname']);
//                }
//                else{
//                   // $data[] =$v['visit_protocol'].$v['originurl'].'.'.$v['dname'];
//                    $tmp = [];
//                    $tmp['value']=$v['domain_id']."=domain=".$v['visit_protocol'].$v['originurl'].'.'.$v['dname'];
//                    $tmp['label'] = $v['visit_protocol'].$v['originurl'].'.'.$v['dname'];
//                    $data[] = $tmp;
//                    //array_push($data,$v['visit_protocol'].$v['originurl'].'.'.$v['dname']);
//                }
//            }
//        }
//        //$data = array_unique($data);
//        return $this->success($data);
//    }

}
