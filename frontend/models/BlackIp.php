<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace frontend\models;
use backend\models\Node;
use common\lib\Utils;
use common\models\BlackIp as CommonBlackIp;
use Yii;
class BlackIp extends CommonBlackIp
{
    use ApiTrait;
    public function getList($page,$pagenum='',$where=[],$uid){
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
                $brandModel = new Brand();
                $brands = $brandModel->idToName($uid);
                $pack = new Package();
                $packages = $pack->idToName($uid);
                foreach ($datas as $key =>$value)
                {
                    $arr['id'] = $value['id'];
                    $arr['ip'] = $value['ip'];
                    $arr['create_time'] = date('Y-m-d H:i',$value['create_time']);
                    $arr['brand_name'] = isset($brands[$value['brand_id']]) ? $brands[$value['brand_id']] : "";
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

    public function add($data,$uid){
        $model = new $this();
        $data['user_id'] = $uid;
        if(!Utils::isIp($data['ip']))
            return $this->error('ip格式错误');

        $limit =  $this->packageLimit($uid,$data['package_id'],1);
        if($limit['code'] != 200)
        {
            return $limit;
        }
        if(empty($data['domain'])) {
            $data['domain'] = "";
        }else{
            $data['domain'] = serialize($data['domain']);
        }
        if($data && $model->load($data,'')){
            if($model->save() && $model->validate()){
                $this->addUpdateNodeRemap([$model->id],$uid);
                return $this->success();
            }else{
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
        }
        return $this->error('参数错误');
    }

    /**
     * 添加时修改节点remap
     */
    public function addUpdateNodeRemap($data,$uid)
    {

        if($data){
            $result = [];
            $node_group_ids = [];
            foreach ($data as $val)
            {
                $black = $this::find()->where(['id'=>$val])->select('domain,package_id')->asArray()->one();

                if(!empty($black['domain']))
                {
                    $domains = unserialize($black['domain']);
                    foreach ($domains as $dval)
                    {
                        $result[$black['package_id']][] =$dval;
                    }
                    $result[$black['package_id']] = array_unique($result[$black['package_id']]);
                }
            }

            if($result) {
                foreach ($result as $rkey => $rval) {
                    //查询domain套餐内的remap
                    $list = \common\models\Domain::find()->select("{{%remap}}.id,{{%remap}}.preview,{{%domain}}.node_group,{{%domain}}.sys_node_group")->where(['{{%domain}}.user_id' => $uid, '{{%domain}}.package_id' => $rkey])->leftJoin("{{%remap}}", "{{%remap}}.did = {{%domain}}.id")->asArray()->all();
                    if ($list) {
                        foreach ($list as $lval) {
                            $preview = explode(" ", $lval['preview']);
                            if (in_array($preview[1], $rval)) {
                                $node_group_ids[] = $lval['node_group'];
                                $node_group_ids[] = $lval['sys_node_group'];
                            }
                        }
                    }

                    $list = \common\models\Defence::find()->select("{{%defence_remap}}.id,{{%defence_remap}}.preview,{{%defence}}.node_group,{{%defence}}.sys_node_group")->where(['{{%defence}}.user_id' => $uid, '{{%defence}}.package_id' => $rkey])->leftJoin("{{%defence_remap}}", "{{%defence_remap}}.did = {{%defence}}.id")->asArray()->all();
                    if ($list) {
                        foreach ($list as $lval) {
                            $preview = explode(" ", $lval['preview']);
                            if (in_array($preview[1], $rval)) {
                                $node_group_ids[] = $lval['node_group'];
                                $node_group_ids[] = $lval['sys_node_group'];
                            }
                        }
                    }
                }
            }
            if($node_group_ids)
            {
                $node_group_ids = array_unique(array_filter($node_group_ids));
                Node::insertNodeGroupUpdate($node_group_ids);
            }
        }
    }

    public function addBatch($data,$uid){
        $data['user_id'] = $uid;
        $ips = $data['ips'];
        $ids = [];
        if(!$ips)
            return $this->error('请填写ip');
        $transaction = \Yii::$app->db->beginTransaction();
        $arr=explode("\n",$ips);
        $arr = array_filter($arr);
        $count =  count($arr);
        if($count == 0)
            return $this->error('请填写ip');

        $limit =  $this->packageLimit($uid,$data['package_id'],$count);
        if($limit['code'] != 200)
        {
            $transaction->rollBack();
            return $limit;
        }

        foreach ($arr as $value) {
            if(!Utils::isIp($value)) {
                $transaction->rollBack();
                return $this->error('ip：'.$value.'格式错误');
            }
            $model =  new $this();
            $model->user_id = $uid;
            $model->ip = $value;
            $model->package_id = $data['package_id'];
            $model->brand_id = $data['brand_id'];
            if(empty($data['domain'])) {
                $model->domain = "";
            }else{
                $model->domain = serialize($data['domain']);
            }
            if(!$model->save() && !$model->validate())
            {
                $transaction->rollBack();
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
            $ids[] = $model->id;
        }
        $this->addUpdateNodeRemap($ids,$uid);
        $transaction->commit();
        return $this->success();
    }

    public function updateInfo($data,$uid){
        $model = $this::find()->where(['id'=>$data['id'],'user_id'=>$uid])->one();
        $oldModel = $model->getOldAttributes();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        unset($data['id']);
        if(!Utils::isIp($data['ip']))
            return $this->error('ip格式错误');
        $package_id = $model->getOldAttribute('package_id');
        if($package_id != $data['package_id'])
        {
            $limit =  $this->packageLimit($uid,$data['package_id'],1);
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
                $this->updateNodeRemap($model,$oldModel,$uid);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }


    /**
     * 修改时修改节点remap
     */
    public function updateNodeRemap($data,$old_data,$uid)
    {
//        if($data['package_id'] != $old_data['package_id'] || $data['domain'] != $old_data['domain'])
//        {
            $result = [];
            $node_group_ids = [];
            if($data['package_id'] == $old_data['package_id'])
            {
                $domains = unserialize($data['domain']);
                if($domains) {
                    foreach ($domains as $dval)
                        $result[$data['package_id']][] = $dval;
                }
                $domains = unserialize($old_data['domain']);
                if($domains) {
                    foreach ($domains as $dval)
                        $result[$data['package_id']][] = $dval;
                }
                if(isset($result[$data['package_id']]))
                $result[$data['package_id']] = array_unique($result[$data['package_id']]);
            }else{
                $domains = unserialize($old_data['domain']);
                if($domains) {
                    foreach ($domains as $dval)
                        $result[$old_data['package_id']][] = $dval;
                }
                $domains = unserialize($data['domain']);
                if($domains) {
                    foreach ($domains as $dval)
                        $result[$data['package_id']][] = $dval;
                }
                if($result) {
                    if(isset($result[$old_data['package_id']]))
                    $result[$old_data['package_id']] = array_unique($result[$old_data['package_id']]);
                    if(isset($result[$data['package_id']]))
                    $result[$data['package_id']] = array_unique($result[$data['package_id']]);
                }
            }
            if($result) {
                foreach ($result as $rkey => $rval) {
                    //查询domain套餐内的remap
                    $list = \common\models\Domain::find()->select("{{%remap}}.id,{{%remap}}.preview,{{%domain}}.node_group,{{%domain}}.sys_node_group")->where(['{{%domain}}.user_id' => $uid, '{{%domain}}.package_id' => $rkey])->leftJoin("{{%remap}}", "{{%remap}}.did = {{%domain}}.id")->asArray()->all();
                    if ($list) {
                        foreach ($list as $lval) {
                            $preview = explode(" ", $lval['preview']);
                            if (in_array($preview[1], $rval)) {
                                $node_group_ids[] = $lval['node_group'];
                                $node_group_ids[] = $lval['sys_node_group'];
                            }
                        }
                    }

                    $list = \common\models\Defence::find()->select("{{%defence_remap}}.id,{{%defence_remap}}.preview,{{%defence}}.node_group,{{%defence}}.sys_node_group")->where(['{{%defence}}.user_id' => $uid, '{{%defence}}.package_id' => $rkey])->leftJoin("{{%defence_remap}}", "{{%defence_remap}}.did = {{%defence}}.id")->asArray()->all();
                    if ($list) {
                        foreach ($list as $lval) {
                            $preview = explode(" ", $lval['preview']);
                            if (in_array($preview[1], $rval)) {
                                $node_group_ids[] = $lval['node_group'];
                                $node_group_ids[] = $lval['sys_node_group'];
                            }
                        }
                    }
                }
            }
            if($node_group_ids)
            {
                $node_group_ids = array_unique(array_filter($node_group_ids));
                Node::insertNodeGroupUpdate($node_group_ids);
            }
//        }
    }

    //删除分组
    public function del($data,$uid){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        $data = $this::find()->select('domain,package_id')->where(['and',['user_id'=>$uid],['in','id',$ids]])->asArray()->all();
        if(count($data) != count($ids))
            return $this->error('操作错误');
        self::delUpdateNodeRemap($data,$uid);
        $this::deleteAll(['and',['in','id',$ids],['user_id'=>$uid]]);
        return $this->success('','操作成功');
    }

    /**
     * 删除时更新node-remap节点文件
     */
    public function delUpdateNodeRemap($data,$uid)
    {
        if($data){
            $result = [];
            $node_group_ids = [];
           foreach ($data as $val)
           {
               if(!empty($val['domain']))
               {
                   $domains = unserialize($val['domain']);
                   foreach ($domains as $dval)
                   {
                       $result[$val['package_id']][] =$dval;
                   }
                   $result[$val['package_id']] = array_unique($result[$val['package_id']]);
               }
           }
           if($result) {
               foreach ($result as $rkey => $rval) {
                   //查询domain套餐内的remap
                   $list = \common\models\Domain::find()->select("{{%remap}}.id,{{%remap}}.preview,{{%domain}}.node_group,{{%domain}}.sys_node_group")->where(['{{%domain}}.user_id' => $uid, '{{%domain}}.package_id' => $rkey])->leftJoin("{{%remap}}", "{{%remap}}.did = {{%domain}}.id")->asArray()->all();
                   if ($list) {
                       foreach ($list as $lval) {
                           $preview = explode(" ", $lval['preview']);
                           if (in_array($preview[1], $rval)) {
                               $node_group_ids[] = $lval['node_group'];
                               $node_group_ids[] = $lval['sys_node_group'];
                           }
                       }
                   }

                   $list = \common\models\Defence::find()->select("{{%defence_remap}}.id,{{%defence_remap}}.preview,{{%defence}}.node_group,{{%defence}}.sys_node_group")->where(['{{%defence}}.user_id' => $uid, '{{%defence}}.package_id' => $rkey])->leftJoin("{{%defence_remap}}", "{{%defence_remap}}.did = {{%defence}}.id")->asArray()->all();
                   if ($list) {
                       foreach ($list as $lval) {
                           $preview = explode(" ", $lval['preview']);
                           if (in_array($preview[1], $rval)) {
                               $node_group_ids[] = $lval['node_group'];
                               $node_group_ids[] = $lval['sys_node_group'];
                           }
                       }
                   }
               }
           }
           if($node_group_ids)
           {
               $node_group_ids = array_unique(array_filter($node_group_ids));
               Node::insertNodeGroupUpdate($node_group_ids);
           }
        }
    }

    public function getOne($id,$uid){
        $model = $this::find()->where(['id'=>$id,'user_id'=>$uid])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['package_id'] = (int)$model['package_id'];
        if(empty($model['brand_id']))
            $model['brand_id'] = '';
        else
            $model['brand_id'] = (int)$model['brand_id'];
        if(empty($model['domain']))
        {
            $model['domain'] = [];
        }else{
            $model['domain'] =unserialize($model['domain']);
        }
        return $this->success($model);
    }

    public function packageLimit($user_id,$package_id,$num)
    {
        $res = Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');
        $count = $this::find()->where(['user_id'=>$user_id,'package_id'=>$package_id])->count();
        $count_all = $count + $num;
        if($count_all>$res['black_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['black_quantity']."个,已创建".$count."个");
        }
        return $this->success();
    }

}
