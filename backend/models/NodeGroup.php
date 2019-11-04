<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/31
 * Time: 21:28
 */

namespace backend\models;

//use common\models\Domain;
use common\lib\Utils;
use common\models\NodeGroup as CommonNodeGroup;
use common\models\NodeGroupNodeid;
use yii\helpers\ArrayHelper;

class NodeGroup extends CommonNodeGroup
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
            $csdatas =$datas;
        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    public function add($data){
        $model = new NodeGroup();
        $data['node_id'] = implode(',',array_filter($data['node_id']));
        if($data && $model->load($data,'')){

            //判断当前type是否有，如果没有，则将这个设置为默认.
            //即首次添加，一定要设置个默认
            $type = $model->type;

            $countryModel = new CountryType();
            $countryArr = $countryModel->idToType();
            if(!isset($countryArr[$type])){
                return $this->error('类型错误');
            }
            $check = NodeGroup::find(['type'=>$type])->one();
            $model->remark = $countryArr[$type];
            if(!$check){
                $model->isDefault = 1;
            }else{
                $model->isDefault = 0;
            }
            if($model->save() && $model->validate()){
                $this->nodeGroupNodeid($model->id,$model->node_id,'add');
                return $this->success();
            }else{
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }


        }
        return $this->error('参数错误');
    }

    public function updateGroup($data){
        $model = NodeGroup::findOne($data['id']);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $odata = $model->getOldAttributes();
        unset($data['id']);
        $data['node_id'] = implode(',',array_filter($data['node_id']));

        $countryModel = new CountryType();
        $countryArr = $countryModel->idToType();
        if(!isset($countryArr[$data['type']])){
            return $this->error('类型错误');
        }
        $data['remark'] = $countryArr[$data['type']];

        if($data && $model->load($data,'')){
            if ($model->save()) {
                $this->nodeGroupNodeid($model->id,$model->node_id,'update');
                $arr = Utils::arrayNewDel(explode(',',$odata['node_id']),explode(',',$model->node_id));
                $nodeArr = [];
                if($arr)
                {
                    foreach ($arr as $nval)
                    {
                        $nodeArr [] = $nval;
                        $node = Node::find()->where(['id'=>$nval])->select('id,cluster')->one();
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
                $nodeArr = array_unique($nodeArr);
                Node::insertNodeUpdate($nodeArr);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        return $this->error('参数错误');
    }

    //设置默认
    public function setDefault($data){
        if(isset($data['id']) && $model = NodeGroup::findOne($data['id'])){
            $transaction = \Yii::$app->db->beginTransaction();
            //先修改这个类型数据的isDefault = 0
            $type = $model->type;
            $up_one = NodeGroup::updateAll(['isDefault'=>0],['type'=>$type]);
            //再将这个ID 的isDefault 修改为1
            $model->isDefault =1;
            $up_two = $model->save();
            if($up_one && $up_two){
                $transaction->commit();
                return $this->success();
            }else{
                $transaction->rollBack();
                return $this->errors('设置失败');
            }
        }
        return $this->error('参数错误');
    }

    //删除分组
    public function delGroup($id){
        $model = NodeGroup::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        if($model->isDefault){
            return $this->error('当前分组号是默认分组');
        }
        //判断加速域名中是否有正在用的该分组，有则不能删除
        //$domain = Domain::find()->where(['node_group'=>$id,'status'=>2,'enable'=>1])->one();
        $domain = Domain::find()->where(['or',['node_group'=>$id],['sys_node_group'=>$id]])->count();
        $defence = Defence::find()->where(['or',['node_group'=>$id],['sys_node_group'=>$id]])->count();
        if($domain || $defence){
            return $this->error('该分组正在使用');
        }
        $del = $model->delete();
        if($del){
            NodeGroupNodeid::deleteAll('node_group_id = :id',[':id'=>$id]);
            $nodes = explode(',',$model->node_id);
            $nodeArr = array();
            if($nodes)
            {
                foreach ($nodes as $nval) {
                    $nodeArr [] = $nval;
                    $node = Node::find()->where(['id' => $nval])->select('id,cluster')->one();
                    if ($node) {
                        if ($node->cluster != 0) {
                            $nodeList = Node::find()->where(['cluster' => $node->cluster])->select('id')->asArray()->all();
                            foreach ($nodeList as $nodeVal) {
                                $nodeArr[] = $nodeVal['id'];
                            }
                        }
                    }
                }
            }
            $nodeArr = array_unique($nodeArr);
            Node::insertNodeUpdate($nodeArr);
        }
        return $this->success('','操作成功');
    }

    //获取分组数据 id=>group_name   分组ID=>分组名称
    public function idToName(){
        $res = NodeGroup::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','group_name');
        return $arr;
    }

    public function getOne($id){
        $model = NodeGroup::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        $model['node_id'] = explode(',',$model['node_id']);
        $model['type'] = $this->numToInt($model['type']);
        $model['node_id']  = $this->numToInt($model['node_id']);
        return $this->success($model);
    }

    /**
     * 更新node group 表 节点id
     */
    public function nodeGroupNodeid($node_group_id,$nodeid,$type = 'add')
    {
        if($type == 'update')
            NodeGroupNodeid::deleteAll('node_group_id = :id',[':id'=>$node_group_id]);
        if(!empty($nodeid))
        {
            $nodeidArr =  explode(',',$nodeid);
            foreach ($nodeidArr as $val)
            {
                $model = new NodeGroupNodeid();
                $model->node_group_id =  $node_group_id;
                $model->node_id = $val;
                $model->save();
            }
        }
    }

    /************************表关联***************************/
    public function getType(){
        return $this->hasMany(CountryType::className(), ['id' => 'type']);
    }
}
