<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 20:11
 */

namespace backend\models;

use common\lib\FileUtil;
use common\lib\Utils;
use common\models\DefenceRemap;
use common\models\NodeGroupNodeid;
use common\models\PackageUser;
use common\models\Remap;
use common\models\User as CommonUser;
use common\models\UserCerDomain;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class User extends CommonUser
{
    use ApiTrait;
    public function getList($page=1,$pagenum='',$where=[]){
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
        $list = $this->find();

        if(!empty($where)){
            $i = 0;
            foreach ($where as $k=>$v){
                if($i==0){
                    $list->where($v);
                }else{
                    $list->andWhere($v);
                }
                $i++;
            }
        }
        $count = $list->count();  //总条数
        //echo $count;die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();

            /**
             * 数据处理,
             */
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['username']  = $data->username;
                $arr['realname'] = $data->realname;
                $arr['registsource'] = $data->registsource;
                $arr['last_login_ip']  =$data->last_login_ip;
                $arr['last_login_time'] = date('Y-m-d H:i:s',$data->last_login_time);
                $arr['login_count'] =$data->login_count;
                $arr['create_time'] = date('Y-m-d H:i:s',$data->create_time);
                $arr['status'] = $data->status;
                $arr['level'] = $data->level;
                $arr['role'] = ($data->role)?$data->role:'0';
                $arr =  $this->getPackInfo($arr);
                $csdatas[] = $arr;
            }

        }

        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        //var_dump($result);die;
        return $this->success($result);
    }

    /**
     * 获取用户套餐信息
     * @param $arr
     * @return mixed
     */
    public function getPackInfo($arr)
    {
        $packUser = PackageUser::find()->where(['user_id'=>$arr['id']])->asArray()->all();
        $arr['package_name'] = "";
        $arr['package_ids'] = "";
        $arr['package_id'] = [];
        if($packUser)
        {
            foreach ($packUser as $val)
            {
                $arr['package_id'][] =(int)$val['package_id'];
                $pack = Package::find()->where(['id'=>$val['package_id']])->select('name')->one();
                if($pack)
                    $arr['package_name'] .= $pack->name.",";
            }
            $arr['package_ids'] =   implode(',',$arr['package_id']);
        }
        return $arr;
    }

    public function getOne($id){
        $model = User::find()->select('id,username,status,level')->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['status'] = (int)$model['status'];
        $model['level'] = (int)$model['level'];
        return $this->success($model);
    }

    //新增用户
    public function add($data){
        $model = new User();
        if($data && $model->load($data,'')){
            $model->password = md5($model->password);
            $model->create_time = time();
            if ($model->validate() && $model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        return $this->error('参数错误');
    }

    //修改用户信息,状态，vip,是否代理
    public function updateInfo($data){
        $id = $data['id'];
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        $status = $data['status'];
        $level = $data['level'];

        //检查参数
        if(!in_array($status,[0,1]) || !in_array($level,[0,1])){
            return $this->error('参数错误');
        }
        $model->status = $status;
        $model->level = $level;
        if($model->save()){
            return $this->success();
        }
        $msg = ($this->getModelError($model));
        return $this->error($msg);

    }

    //修改密码
    public function changePassword($data){
        $id = $data['id'];
        $password = $data['password'];
        if(!$password){
            return $this->error('密码不能为空');
        }
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应用户');
        }
        $model->password = md5($password);
        if($model->save()){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    //删除用户
    public function delUser($id){
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $del = User::delete($id);
        return $this->success('','操作成功');
    }

    //激活用户（修改状态）
    public function changeStatus($data){
        $id = $data['id'];
        $status = $data['status'];
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应用户');
        }
        if(!in_array($status,[0,1])){
            return $this->error('参数错误');
        }
        $model->status = $status;
        if($model->save()){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    //获取用户 id=>username
    public function getIdToUsername(){
        $res = User::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','username');
        return $arr;
    }

    //获取用户  username=>username
    public function getUsernameToUsername(){
        $res = User::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'username','username');
        return $arr;
    }

    public function getUserIdByUsername($username){
        $res = User::find()->where(['username'=>$username])->one();
        if(!$res){
            return false;
        }
        return $res['id'];
    }

    /**
     * 修改套餐
     * @param $data
     * @return array
     */
    public function updatePackage($data){
        $id = $data['id'];
        $node_ids = $data['package_ids'];
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应用户');
        }
        PackageUser::deleteAll(['user_id'=>$id]);
        if($node_ids)
        {
            $node_ids = explode(',',$node_ids);
            foreach ($node_ids as $val)
            {
                if(!$val)
                    continue;
                $packUser = new PackageUser();
                $packUser->user_id = $id;
                $packUser->package_id = $val;
                $packUser->create_time = time();
                $packUser->save();
            }
        }
        return $this->success();
    }

    public function getUserPackInfo($data)
    {
        $id = $data['id'];
        $datas =PackageUser::find()->alias("pu")->select("p.*,pu.user_id")->where(['user_id'=>$id])->innerJoin("{{%package}} as p",'pu.package_id = p.id')->asArray()->all();
        foreach ($datas as $key=>$data)
        {
            $datas[$key]['create_time'] = date('Y-m-d H:i',$data['create_time']);
            $datas[$key]['ssl_quantity'] =$data['ssl_quantity']."（" .$this->getPackUseNum($data['id'],$id,'ssl_quantity')."）";
            $datas[$key]['origin_quantity'] =$data['origin_quantity']."（" .$this->getPackUseNum($data['id'],$id,'origin_quantity')."）";
            $datas[$key]['url_quantity'] =$data['url_quantity']."（" .$this->getPackUseNum($data['id'],$id,'url_quantity')."）";
            $datas[$key]['drsd_quantity'] =$data['drsd_quantity']."（" .$this->getPackUseNum($data['id'],$id,'drsd_quantity')."）";
            $datas[$key]['black_quantity'] =$data['black_quantity']."（" .$this->getPackUseNum($data['id'],$id,'black_quantity')."）";
            $datas[$key]['white_quantity'] =$data['white_quantity']."（" .$this->getPackUseNum($data['id'],$id,'white_quantity')."）";
        }
        return $this->success($datas);
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

    public function delPackInfo($data)
    {
        if(empty($data['id']) || empty($data['user_id']))
        return $this->error("参数错误");
        $packUser = PackageUser::find()->where(['user_id'=>$data['user_id'],'package_id'=>$data['id']])->count();
        if(empty($packUser))
            return $this->error("找不到套餐");

        $group_ids = [];
        //加速
        $domain = Domain::find()->where(['user_id'=>$data['user_id'],'package_id'=>$data['id']])->select('id,high_anti,node_group,sys_node_group,sys_high_anti')->asArray()->all();
        if($domain)
        {
            $domain_ids = [];
            foreach ($domain as $d_data)
            {
                $domain_ids[] = $d_data['id'];
                $group_ids[]=$d_data['high_anti'];
                $group_ids[]=$d_data['node_group'];
                $group_ids[]=$d_data['sys_node_group'];
                $group_ids[]=$d_data['sys_high_anti'];
            }
            unset($domain);
            unset($d_data);
            Domain::deleteAll(['user_id'=>$data['user_id'],'package_id'=>$data['id']]);
            Remap::deleteAll(['in','did',$domain_ids]);
        }
        //轮询
        $defence = Defence::find()->where(['user_id'=>$data['user_id'],'package_id'=>$data['id']])->select('id,high_anti,node_group,sys_node_group,sys_high_anti')->asArray()->all();
        if($defence)
        {
            $defence_ids = [];
            foreach ($defence as $d_data)
            {
                $defence_ids[] = $d_data['id'];
                $group_ids[]=$d_data['high_anti'];
                $group_ids[]=$d_data['node_group'];
                $group_ids[]=$d_data['sys_node_group'];
                $group_ids[]=$d_data['sys_high_anti'];
            }
            unset($defence);
            unset($d_data);
            Defence::deleteAll(['user_id'=>$data['user_id'],'package_id'=>$data['id']]);
            DefenceRemap::deleteAll(['in','did',$defence_ids]);
        }
        //更新node
        if($group_ids)
        {
            $group_ids = array_filter($group_ids);
            $group_ids = array_unique($group_ids);
            if($group_ids) {
                $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',$group_ids])->select('node_id')->asArray()->all();
                $nodeArr = [];
                if($nodeGroup)
                {
                    foreach ($nodeGroup as $nval){
                        $nodeArr [] = $nval['node_id'];
                        $node = Node::find()->where(['id'=>$nval['node_id']])->select('id,cluster')->one();
                        if($node)
                        {
                            if($node->cluster != 0)
                            {
                                $nodeList =  Node::find()->where(['cluster'=>$node->cluster])->select('id')->asArray()->all();
                                foreach ($nodeList as $nodeVal)
                                {
                                    $nodeArr[] = $nodeVal['id'];
                                }
                            }
                        }
                    }
                }
                Node::insertNodeUpdate($nodeArr);
            }
        }


        //证书
        $userCer = UserCer::find()->where(['user_id'=>$data['user_id'],'package_id'=>$data['id']])->select('id')->asArray()->all();
        if($userCer)
        {
            $dir = \Yii::getAlias('@dns_file').'/ssl/';
            foreach ($userCer as $usercer)
            {
                $model = UserCer::find()->where(['id'=>$usercer['id']])->one();
                $model->delete();
                FileUtil::rmFile($dir.$usercer['id'].'_pb.key');
                FileUtil::rmFile($dir.$usercer['id'].'_pv.crt');
                UserCerDomain::deleteAll(['user_cer_id'=>$usercer['id']]);
            }
        }

        //域名解析
        $drsd = Drsd::find()->select('id')->where(['user_id'=>$data['user_id'],'package_id'=>$data['id']])->asArray()->all();
        if($drsd)
        {
            $drsd_ids = [];
            foreach ($drsd as $dval)
               $drsd_ids[]=$dval['id'];
            Drsd::deleteAll(['in','id',$drsd_ids]);
            Drs::deleteAll(['in','did',$drsd_ids]);
        }
        //黑白名单
        WhiteIp::deleteAll(['user_id'=>$data['user_id'],'package_id'=>$data['id']]);
        BlackIp::deleteAll(['user_id'=>$data['user_id'],'package_id'=>$data['id']]);

        //用户套餐信息
        PackageUser::deleteAll(['user_id'=>$data['user_id'],'package_id'=>$data['id']]);

        return $this->success($data);

    }

}
