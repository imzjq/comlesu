<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 20:11
 */

namespace frontend\models;

use common\lib\Utils;
use common\models\PackageUser;
use common\models\User as CommonUser;
use common\models\UserCerDomain;
use yii\db\Query;


class Customer extends CommonUser
{
    use ApiTrait;

    public function getList($page=1,$pagenum='',$userInfo){
        if(empty($pagenum)){
            $pagenum = $this->pagenum;
        }
        //设置起始位置
        $offset = 0;
        if(!empty($page) && is_numeric($page) && $page > 1){
            $offset = ($page-1) * $pagenum;
        }else{
            $page = 1;
        }

        //$res = PackageUser::find()->where(['{{%package_user}}.user_id'=>$user_id,'package_id'=>$package_id])->select('{{%package}}.*')->innerJoin('{{%package}}','{{%package}}.id = {{%package_user}}.package_id')->asArray()->one();
        $list =PackageUser::find()->where(['{{%package_user}}.user_id'=>$userInfo['uid']])->select('{{%package}}.*,{{%package_user}}.id as did,{{%package_user}}.create_time')->innerJoin('{{%package}}','{{%package}}.id = {{%package_user}}.package_id');

        $count = $list->count();  //总条数
        //echo $count;die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $datas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('{{%package_user}}.create_time DESC');
            $datas = $list->asArray()->all();

            /**
             * 数据处理,
             */
            foreach($datas as $key=> $data){

                $datas[$key]['ssl_quantity'] =$data['ssl_quantity']."（" .$this->getPackUseNum($data['id'],$userInfo['uid'],'ssl_quantity')."）";
                $datas[$key]['origin_quantity'] =$data['origin_quantity']."（" .$this->getPackUseNum($data['id'],$userInfo['uid'],'origin_quantity')."）";
                $datas[$key]['url_quantity'] =$data['url_quantity']."（" .$this->getPackUseNum($data['id'],$userInfo['uid'],'url_quantity')."）";
                $datas[$key]['drsd_quantity'] =$data['drsd_quantity']."（" .$this->getPackUseNum($data['id'],$userInfo['uid'],'drsd_quantity')."）";
                $datas[$key]['black_quantity'] =$data['black_quantity']."（" .$this->getPackUseNum($data['id'],$userInfo['uid'],'black_quantity')."）";
                $datas[$key]['white_quantity'] =$data['white_quantity']."（" .$this->getPackUseNum($data['id'],$userInfo['uid'],'white_quantity')."）";
            }
        }

        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $datas;
        return $this->success($result);
    }

    public function getOne($id){
        $model = User::find()->select('id,username,email,mobile')->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success($model);
    }


    //修改用户信息,状态，vip,是否代理
    public function updateInfo($data,$userInfo){

        $model = User::findOne($userInfo['uid']);
        if(!$model){
            return $this->error('未找到相应信息');
        }

        $model->email = trim($data['email']);
        $model->mobile = trim($data['mobile']);

        if($model->save()){
            return $this->success();
        }
        $msg = ($this->getModelError($model));
        return $this->error($msg);

    }

    //修改密码
    public function changePassword($data,$userInfo){
        $old_password = $data['old_password'];
        $password = $data['password'];
        $password2 = $data['password2'];
        if(!$password || !$password2 || !$old_password){
            return $this->error('密码不能为空');
        }
        if($password != $password2)
            return $this->error('两次密码不一致');

        if (preg_match("/\s/", $password)) {
            return $this->error('密码不能存在空格');
        }
        if(strlen($password)<6)
            return $this->error('密码长度不能少于6位数');

        $model = User::findOne($userInfo['uid']);
        if(!$model){
            return $this->error('未找到相应用户');
        }

        if($model->password != md5($old_password))
            return $this->error('原密码错误');

        $model->password = md5($password);
        if($model->save()){
            return $this->success();
        }
        return $this->error('保存失败');
    }


    public function getPackUseNum($pack_id,$user_id,$type)
    {
        $count = 0;
        switch ($type)
        {
            case 'ssl_quantity' :
                $userCer = UserCer::find()->where(['user_id'=>$user_id,'package_id'=>$pack_id])->select('id')->asArray()->all();
                $usercer_ids = [];
                foreach ($userCer as $val)
                {
                    $usercer_ids[] = $val['id'];
                }
                $result =  UserCerDomain::find()->select('domain')->where(['in','user_cer_id',$usercer_ids])->asArray()->all();
                $url_count = [];
                if($result)
                {
                    foreach ($result as $rvalue)
                    {
                        $url_count [] = Utils::getUrlHost($rvalue['domain']);
                    }
                    $url_count =  array_unique($url_count);
                }
                $count =  count($url_count);
                break;
            case 'origin_quantity' :

                $domain = Domain::find()->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$pack_id])->select('id,dname')->asArray()->all();
                $domain_ids = [];
                foreach ($domain as $val)
                {
                    $domain_ids[] = $val['id'];
                }
                unset($val);
                $defence = Defence::find()->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$pack_id])->select('id')->asArray()->all();
                $defence_ids = [];
                foreach ($defence as $dval)
                {
                    $defence_ids [] = $dval['id'];
                }
                unset($dval);
                $query = new Query();
                $query->where(['and',['in','did',$domain_ids],['redirect_ssl'=>0]])->select("aimurl")->from('{{%remap}}');
                $anotherQuery = new Query();
                $anotherQuery->where(['and',['in','did',$defence_ids],['redirect_ssl'=>0]])->select("aimurl")->from('{{%defence_remap}}');
                $result =  $query->union($anotherQuery)->all();
                $count =  count($result); //使用源的数量

                break;
            case 'url_quantity' :

                $defence = Defence::find()->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$pack_id])->select('id')->asArray()->all();
                $defence_ids = [];
                foreach ($defence as $dval)
                {
                    $defence_ids [] = $dval['id'];
                }
                $query = new Query();
                $query->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$pack_id])->select("dname")->from('{{%domain}}');
                $anotherQuery = new Query();
                $anotherQuery->where(['and',['in','did',$defence_ids],['redirect_ssl'=>0]])->select("originurl")->from('{{%defence_remap}}');
                $result =  $query->union($anotherQuery)->all();
                $url_count = [];
                if($result)
                {
                    foreach ($result as $rvalue)
                    {
                        $url_count [] = Utils::getUrlHost($rvalue['dname']);
                    }
                    $url_count =  array_unique($url_count);
                }
                $count =  count($url_count); //使用源的数量

                break;
            case 'drsd_quantity' :
                $count =  Drsd::find()->where(['user_id'=>$user_id,'package_id'=>$pack_id])->count();
                return $count;
                break;
            case 'black_quantity' :
               $count =  BlackIp::find()->where(['user_id'=>$user_id,'package_id'=>$pack_id])->count();
                return $count;
                break;
            case 'white_quantity' :
                $count =  WhiteIp::find()->where(['user_id'=>$user_id,'package_id'=>$pack_id])->count();
                return $count;
                break;
            default:
                break;

        }
        return $count;
    }


}
