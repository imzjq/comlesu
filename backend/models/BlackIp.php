<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;
use common\lib\Utils;
use common\models\BlackIp as CommonBlackIp;
use Yii;
class BlackIp extends CommonBlackIp
{
    use ApiTrait;
    public function getList($page,$pagenum='',$where=[]){
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
        //var_dump($list->createCommand()->getRawSql());die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();
            if($datas)
            {
                $user = new User();
                $users = $user->getIdToUsername();
                $pack = new Package();
                $packages =  $pack->idToName();
                foreach ($datas as $key =>$value)
                {
                    $arr['id'] = $value['id'];
                    $arr['ip'] = $value['ip'];
                    $arr['user_id'] = $value['user_id'];
                    $arr['create_time'] = date('Y-m-d H:i',$value['create_time']);
                    $arr['username'] = isset($users[$value['user_id']]) ? $users[$value['user_id']] : "" ;
                    $arr['package_name'] = isset($packages[$value['package_id']]) ? $packages[$value['package_id']] : "";
                    $arr['domains'] ="";
                    if($value['domain'])
                    {
                        $domain = unserialize($value['domain']);
                        $arr['domains'] .=implode(",",$domain);
                    }
                    $csdatas[] = $arr;
                }
            }

        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }


    public function updateInfo($data){
        $model = $this::find()->where(['id'=>$data['id']])->one();
        $oldModel = $model->getOldAttributes();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        if(!Utils::isIp($data['ip']))
            return $this->error('ip格式错误');

        $blackIpModel = new \frontend\models\BlackIp();
        $package_id = $model->getOldAttribute('package_id');
        if($package_id != $data['package_id'])
        {
            $limit =  $blackIpModel->packageLimit($data['user_id'],$data['package_id'],1);
            if($limit['code'] != 200)
            {
                return $limit;
            }
        }

        if(empty($data['domain'])) {
            $data['domain'] = "";
        }else{
            $data['domain'] = serialize($data['domain']);
        }

        if($data && $model->load($data,'')){
            if ($model->save()) {
                $blackIpModel->updateNodeRemap($model,$oldModel,$data['user_id']);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //删除分组
    public function del($data){

        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');

        $model = $this::find()->select('domain,package_id')->where(['in','id',$ids])->asArray()->all();
        $blackIpModel = new \frontend\models\BlackIp();
        $blackIpModel->delUpdateNodeRemap($model,$data['user_id']);
        $this::deleteAll(['in','id',$ids]);
        return $this->success('','操作成功');
    }

    public function getOne($id){
        $model = $this::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        if(empty($model['domain']))
        {
            $model['domain'] = [];
        }else{
            $model['domain'] =unserialize($model['domain']);
        }
        $model['package_id'] = (int)$model['package_id'];
        return $this->success($model);
    }


    public function url($data)
    {

        $package_id = $data['package_id'];
        $user_id = $data['user_id'];
        if(empty($package_id))
            return $this->error("请选择套餐");

        $where [] = ['{{%defence}}.user_id'=>$user_id];
        $where2 [] = ['{{%domain}}.user_id'=>$user_id];
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
        if($res){
            foreach ($res as $k=>$v){
                $data_temp[]= $v['visit_protocol'].$v['originurl'];
            }
        }
        $res2 = $res2->select('{{%remap}}.id,{{%remap}}.dname,{{%remap}}.originurl,{{%remap}}.visit_protocol,{{%domain}}.id as domain_id')->leftJoin('{{%remap}}','{{%remap}}.did = {{%domain}}.id')->asArray()->all();
        if($res2){
            foreach ($res2 as $k=>$v){
                if($v['originurl'] == "@")
                {
                    $data_temp[]= $v['visit_protocol'].$v['dname'];
                }elseif ($v['originurl'] == "*")
                {
                    $data_temp[]=$v['visit_protocol'].'(.*).'.$v['dname'];

                }
                else{
                    $data_temp[]=$v['visit_protocol'].$v['originurl'].'.'.$v['dname'];

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

}
