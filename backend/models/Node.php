<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:10
 * 节点lc_node
 */

namespace backend\models;

use common\lib\FileUtil;
use common\models\DefenceRemap;
use common\models\Node as CommonNode;
use common\models\IpdatabaseSimplify;
use common\lib\Utils;
use common\models\NodeGroupNodeid;
use common\models\NodeRemark;
use common\models\NodeTagId;
use common\models\NodeUpdate;
use common\models\Remap;
use yii\helpers\ArrayHelper;

class Node extends CommonNode
{
    use ApiTrait;

    public static $switchMap = [
        1=>'开',
        0=>'关'
    ];
    public static $forbiddenMap = [
        1=>'禁止',
        0=>'正常'
    ];
    public static $statusMap = [
        1=>'正常',
        0=>'出错'
    ];

    public static $groupName;
    public static $blackIp;

    public function getList($page=1,$pagenum='',$where=[]){
        if(empty($pagenum)){
            $pagenum = $this->pagenum;
        }
        $pagenum = 1000;
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
        /*
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        */
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();

            if($datas){
                self::$groupName = self::idToGroupName();
                $tagList = NodeTag::find()->asArray()->all();
                $tagList = ArrayHelper::map($tagList,'id','name');
                $groupName = self::nodeToGroup();
                foreach($datas as $data){
                    $arr['id'] = $data->id;
                    $arr['ip']  = $data->ip;
                    $arr['name'] = $data->name;
                    $arr['cpu']  = $data->cpu;
                    $arr['area'] = $data->area;
                    $arr['memory'] = $data->memory;
                    $arr['weight'] = $data->weight;
                    $arr['cluster'] = $data->cluster;
                    $arr['flow'] = $data->flow;
                    $arr['flow_max'] = $data->flow_max;
                    $arr['switch'] = self::$switchMap[$data->switch];
                    $arr['forbidden'] = self::$forbiddenMap[$data->forbidden];
                    $arr['alive_z_1'] = self::$statusMap[$data->alive_z_1];
                    $arr['alive_z_2'] = self::$statusMap[$data->alive_z_2];
                    $arr['kit_id'] = $data->kit_id;
                    $arr['zabbix_ip'] = $data->zabbix_ip;
                    $arr['kit_name'] = isset($data->nodekit->name)?$data->nodekit->name:'未分配';
                    $arr['status'] =self::$statusMap[$data->status];
                    $arr['group_name'] = isset($groupName[$data->id]) ? $groupName[$data->id] : ''; // self::getGroupName($data->id,self::$groupName);
                    $arr['tag_ids'] = $data->tag_ids;
                    $lable = '';
                    if($data->tag_ids)
                    {
                        $tags = explode(',',$data->tag_ids);
                        foreach ($tags as $tagVal) {
                            if (isset($tagList[$tagVal])){
                                $lable .= $tagList[$tagVal].",";
                            }
                        }
                    }
                    $arr['lable'] = $lable;//标签
                    $csdatas[] = $arr;
                }
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


    public static function idToGroupName()
    {
        $res = NodeGroup::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','group_name');
        return $arr;
    }

    public function nodeToGroup()
    {
        $sql = "select id,ip,node_group_id,node_id from lc_node as a left join (select node_group_id,node_id from lc_node_group_nodeid ) T  on a.id = T.node_id HAVING node_id >0";
        $data = \Yii::$app->db->createCommand($sql)->queryAll();
        $list = [];
        if($data)
        {
            foreach ($data as $value)
            {
                if(isset(self::$groupName[$value['node_group_id']])) {

                    $list[$value['node_id']] = isset($list[$value['node_id']]) ? $list[$value['node_id']].self::$groupName[$value['node_group_id']].",":self::$groupName[$value['node_group_id']].",";
               }
            }
        }
        return $list;
    }

//    public static function getGroupName($id,$groupName)
//    {
//        $list = NodeGroupNodeid::find()->where(['node_id'=>$id])->asArray()->all();
//        $groupNames = '';
//        if($list)
//        {
//            foreach ($list as $val)
//            {
//                if(isset($groupName[$val['node_group_id']]))
//                {
//                    $groupNames .= $groupName[$val['node_group_id']].",";
//                }
//            }
//        }
//        return $groupNames ;
//    }




    //新增节点
    public function add($data){
        $model = new Node();
        $check_ip = Utils::isIp($data['ip']);
        if(!$check_ip){
            return $this->error('ip格式错误');
        }
        $ip = $data['ip'];
        if($data && $model->load($data,'')){
            $ipModel = new IpdatabaseSimplify();
            $group_id = $ipModel->ipGetGroupId($ip);
            if(!$group_id){
                return $this->error('找不到分组号，请先添加分组号');
            }
            $model->group_id = $group_id;
            //$model->switch = 0; //默认关闭
            $model->status = 1;
            $model->type = 1; //共享 （其实没有用了）
            if ($model->save()) {
                $this->afterNodeRemark($model->id,1);//添加节点备注
                self::updateNodeTagId($model,'add');
                $this->updateKit($model->id);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        return $this->error('参数错误');
    }

    /**
     * 添加节点备注
     * type 1 添加  2 删除
     */
    public function afterNodeRemark($node_id,$type = 1)
    {
        if($type == 1)
        {
            $model = new NodeRemark();
            $model->node_id = $node_id;
            $model->save();
        }elseif ($type == 2)
        {
            NodeRemark::deleteAll(['in','node_id',$node_id]);
        }
    }


    //修改节点
    public function updateNode($data){
        $model = Node::findOne($data['id']);
        $odata = $model->getOldAttributes();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        unset($data['id']);
        if($data && $model->load($data,'')){
            if ($model->save()) {

                if($odata['kit_id'] != $model->kit_id)
                $this->updateKit($model->id);
                $this->rename($odata,$model->getOldAttributes());
             self::updateNodeTagId($model,'update');
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function updateNodeTagId($model,$type = 'add')
    {

        if($type == 'update')
            NodeTagId::deleteAll('node_id = :id',[':id'=>$model->id]);
        if(!empty($model->tag_ids))
        {
            $tagArr =  explode(',',$model->tag_ids);
            foreach ($tagArr as $val)
            {
                $TagModel = new NodeTagId();
                $TagModel->node_id =  $model->id;
                $TagModel->tag_id = $val;
                $TagModel->save();

            }
        }
    }


    /**
     * 修改ip时 修改配置文件id
     * @param $omodel
     * @param $nmodel
     */
    public function rename($omodel,$nmodel)
    {
        if($omodel['zabbix_ip'] != $nmodel['zabbix_ip'])
        FileUtil::rename(\Yii::getAlias('@dns_file').'/node/'.$omodel['zabbix_ip'],\Yii::getAlias('@dns_file').'/node/'.$nmodel['zabbix_ip']);
    }

    //节点开关
    public function onAndOff($data){
        $id = $data['id'];
        $switch = $data['switch'];
        if(!in_array($switch,[0,1])|| !$id){
            return $this->error('参数错误');
        }
        //批量修改
        $res = Node::updateAll(['switch'=>$switch],['in','id',$id]);
        if($res){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    //集群设置
    public function setClusters($data){
        $node_ids = $data['node_id']; //数组
        $c_name = $data['name'];

        $nodeArr = [];
        if($node_ids)
        {
            foreach ($node_ids as $node_id) {
                $node =   Node::find()->select('id,cluster')->where(['id'=>$node_id])->asArray()->one();
                if($node)
                {
                    $nodeArr[] = $node_id;
                    if($node['cluster'] != 0)
                    {
                        $nodelist = Node::find()->select('id,cluster')->where(['cluster'=>$node['cluster']])->asArray()->all();
                        foreach ($nodelist as $nval)
                        {
                            $nodeArr[] = $nval['id'];
                        }
                    }
                }
            }
        }
        if($c_name != 0)
        {
            $nodelist = Node::find()->select('id,cluster')->where(['cluster'=>$c_name])->asArray()->all();
            foreach ($nodelist as $nval)
            {
                $nodeArr[] = $nval['id'];
            }
        }

        $nodeArr = array_unique($nodeArr);
        Node::insertNodeUpdate($nodeArr);

        $res = Node::updateAll(['cluster'=>$c_name],['in','id',$node_ids]);
        if($res){
            return $this->success();
        }
        return $this->error('保存失败');
    }


    public function getOne($id){
        $model = Node::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success($model);
    }

    public function del($data){

        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');

        $list = Node::find()->where(['in','id',$ids])->select('id,zabbix_ip')->asArray()->all();
        if($list){

            $transaction = \Yii::$app->db->beginTransaction();
            Node::deleteAll(['in','id',$ids]);
            NodeTagId::deleteAll(['in','node_id',$ids]);
            //DefenceNodeid::deleteAll(['in','node_id',$ids]);
            self::updateNodeIds($ids);
            NodeGroupNodeid::deleteAll(['in','node_id',$ids]);
            Route::deleteAll(['in','node_id',$ids]);
            $this->afterNodeRemark($ids,2);//删除节点备注
           // $this->updateNodeIds($ids);
            $dir =\Yii::getAlias('@dns_file').'/node/';
            foreach ($list as $value)
            {
                $path = $dir.$value['zabbix_ip']."/";
                FileUtil::deldir($path);
            }
            $this->updateDenfeceIp($ids);
            $transaction->commit();
        }
        return $this->success();
    }

    /**
     * 更新高防别名的ip
     * @param $ids
     */
    public function updateDenfeceIp($ids)
    {
        $data = DefenceIp::find()->select('id,ip')->asArray()->all();
        if($data)
        {
            foreach ($data as $val)
            {
                if($val['ip'])
                {
                    $ips =explode("|",$val['ip']);
                    $str_arr = [];
                    $str_arr2 = [];
                    $temp = false;
                    if($ips[0])
                    {
                       $str_arr =  explode(",",$ips[0]);
                       foreach ($str_arr as $str_key =>$str_val)
                       {
                           if(in_array($str_val,$ids)) {
                               unset($str_arr[$str_key]);
                               $temp = true;
                           }
                       }
                    }
                    if($ips[1])
                    {
                        $str_arr2 =  explode(",",$ips[1]);
                        foreach ($str_arr2 as $str_key =>$str_val)
                        {
                            if(in_array($str_val,$ids)) {
                                unset($str_arr2[$str_key]);
                                $temp = true;
                            }
                        }
                    }
                    if($temp == true) {
                        $str = implode(",", $str_arr) . "|" . implode(",", $str_arr2);

                        $defence = DefenceIp::find()->where(['id'=>$val['id']])->one();
                        if($defence){
                            $defence->ip = $str;
                            $defence->save();
                        }
                    }

                }
            }

        }
    }

    /**
     * 删除节点 ，更新分组
     */
    public function updateNodeIds($node_ids)
    {
        foreach ($node_ids as $val)
        {
            $list = NodeGroupNodeid::find()->where(['node_id'=>$val])->asArray()->all();

            if($list)
            {
                foreach ($list as $value)
                {
                    $data = NodeGroup::find()->where(['id'=>$value['node_group_id']])->one();
                    $node_id = $data->node_id ;


                    if($node_id)
                    {
                        $arr = explode(',',$node_id) ;

                        foreach ($arr as $akey => $aval)
                        {
                            if($aval == $value['node_id'])
                            {
                                unset($arr[$akey]);
                            }
                        }
                        $str = implode(',',$arr);
                        $data->node_id = $str;
                        $data->save();
                    }

                }
            }
        }
    }

    //获取所有的节点，根据 id=>ip 方式
    public function idToIp(){
        $res = Node::find()->asArray()->all();
        $res = ArrayHelper::map($res,'id','ip');
        return $res;
    }

    public function idToZabbixIp(){
        $res = Node::find()->select('id,zabbix_ip')->asArray()->all();
        $res = ArrayHelper::map($res,'id','zabbix_ip');
        return $res;
    }

    public function blackIp()
    {
        $result = [];
        $data = \common\models\BlackIp::find()->select('domain,ip,user_id,package_id')->asArray()->all();
        if($data)
        {
            foreach ($data as $key=>$val)
            {
                if($val['domain']) {
                    $domain = unserialize($val['domain']);
                        foreach ($domain as $d) {
                           // $result[$val['user_id']][$val['package_id']][$d][] = $val['ip'];
                            $result[$val['user_id']][$val['package_id']][$d][] = $val['ip'];
                        }
                }
            }
        }
        return $result ;
    }

    /**
     * 更新节点配置
     */
     public function exportNode($newIds =false)
    {

        if(is_array($newIds))
        {
            /**
             * 获取全部黑名单
             */
            self::$blackIp = $this->blackIp();

            foreach ($newIds as $val)
            {
                $path =  \Yii::getAlias('@dns_file').'/node/';
                $content_remap = [];
                $node = Node::find()->where(['id'=>$val])->select('zabbix_ip,cluster')->one();
                if(!empty($node))
                {
                    $path .= $node->zabbix_ip.'/';
                    FileUtil::createDir($path);
                    $file = "remap.config";
                    if($node->cluster != 0)
                    {
                        $nodeArr [] = $val;
                        $clusterNode = Node::find()->where(['cluster'=>$node->cluster])->andWhere(['<>','id',$val])->select('id')->asArray()->all();
                        foreach ($clusterNode as $node_id)
                        {
                            $nodeArr [] = $node_id['id'];
                        }
                        $groups = NodeGroupNodeid::find()->where(['in','node_id',$nodeArr])->groupBy('node_group_id')->asArray()->all();
                    }else {
                        $groups = NodeGroupNodeid::find()->where(['node_id' => $val])->groupBy('node_group_id')->asArray()->all();
                    }
                    if($groups)
                    {
                        $groupArr = [];
                        foreach ($groups as $gval)
                        {
                            $groupArr[] = (int)$gval['node_group_id'];
                        }

                       //$domain =  Domain::find()->select('id,dname')->where(['or',['in','node_group',$groupArr],['in','sys_node_group',$groupArr]])->andWhere(['status'=>2,'enable'=>1])->asArray()->all();
                       $domain =  Domain::find()->select('id,dname,node_group,sys_node_group,user_id,package_id')->where(['and',['in','node_group',$groupArr],['status'=>2],['enable'=>1]])->asArray()->all();
                        if($domain)
                        {
                            foreach ($domain as $key => $data) {
                                if($data['sys_node_group'] == 0 || $data['sys_node_group'] == $data['node_group'] )
                                    $content_remap = $this->_origin_domain($data['id'],$data['dname'],$content_remap,$data);
                            }
                        }

                        $domain =  Domain::find()->select('id,dname,node_group,sys_node_group,user_id,package_id')->where(['and',['in','sys_node_group',$groupArr],['status'=>2],['enable'=>1]])->asArray()->all();
                        if($domain)
                        {
                            foreach ($domain as $key => $data) {
                                    $content_remap = $this->_origin_domain($data['id'],$data['dname'],$content_remap,$data);
                            }
                        }
                        $defende = Defence::find()->select('id,dname,node_group,sys_node_group,user_id,package_id')->where(['and',['in','node_group',$groupArr],['status'=>2],['enable'=>1]])->asArray()->all();
                        if($defende)
                        {
                            foreach ($defende as $dkey => $ddata) {
                                if($ddata['sys_node_group'] ==0 || $ddata['sys_node_group'] == $ddata['node_group']  )
                                $content_remap = $this->_origin_domain_defence($ddata['id'],$ddata['dname'],$content_remap,$ddata);
                            }
                        }
                        $defende = Defence::find()->select('id,dname,node_group,sys_node_group,user_id,package_id')->where(['and',['in','sys_node_group',$groupArr],['status'=>2],['enable'=>1]])->asArray()->all();
                        if($defende)
                        {
                            foreach ($defende as $dkey => $ddata) {
                                    $content_remap = $this->_origin_domain_defence($ddata['id'],$ddata['dname'],$content_remap,$ddata);
                            }
                        }
                    }

                    $content_remap = array_unique($content_remap);
                    if(empty($content_remap))
                        $content_remap = '';
                    else
                        $content_remap[] = '#end##';
                    $result = Utils::fileIsUpdate($path.$file,$content_remap);
                    if($result)
                     file_put_contents($path.$file,$content_remap);
                }
            }
        }
    }


    protected  function _origin_domain($did,$dname,$content_remap,$data)
    {
        $remapInfo = Remap::find()->where(['did'=>$did])->all();
        if($remapInfo){
            foreach ($remapInfo as $v){

                //添加验证(回源地址)
                $pattIP = '/^((([0-9a-zA-Z]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,4})|((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9]))))|\:[0-9]{2,5}$/';
                if(!preg_match($pattIP, $v->aimurl) && !Utils::isUrl($v->aimurl) ){
                    continue;
                }

                $aimport = $v->aimport;
                $originport = $v->originport;

                if($aimport && $aimport !=80){
                    $aimport = ':'.$aimport.'/';
                }else{
                    $aimport = '/';
                }
                if($originport && $originport !=80){
                    $originport = ':'.$originport.'/ ';
                }else{
                    $originport = '/ ';
                }


                //if($v->is_at || $v->originurl=='@'){
                if($v->originurl=='@'){
                    //@ 回原
                    if(!$v->redirect_ssl) {
                        $text = $v->visit_protocol . $dname ;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "map " . $v->visit_protocol . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport . $result ."\n";
                    }
                    else {
                        $text =  $v->visit_protocol . $dname;
                        $result = $this->_black_domain($text,$data);
                         $content_remap[] = "redirect " . $v->visit_protocol . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result ."\n";
                    }
                    continue;
                }
                if($v->originurl == '*')
                    $v->originurl = '(.*)';

                //301
                if($v->redirect_ssl){
                    if( $v->originurl == '(.*)') {
                        $text =   $v->visit_protocol . $v->originurl .'.'. $dname;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "regex_redirect " . $v->visit_protocol . $v->originurl . '\\.' . str_replace(".","\\.",$dname)  . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result ."\n";
                    }else{
                        if($v->originurl != '') {
                            $text =    $v->visit_protocol . $v->originurl . '.' . $dname ;
                            $result = $this->_black_domain($text,$data);
                            $content_remap[] = "redirect " . $v->visit_protocol . $v->originurl . '.' . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport . $result."\n";
                        }else{
                            $text =   $v->visit_protocol  . $dname;
                            $result = $this->_black_domain($text,$data);
                            $content_remap[] = "redirect ". $v->visit_protocol  . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result . "\n";
                        }
                    }
                    continue;
                }

                if( $v->originurl ==='[a-z0-9]' || $v->originurl == '(.*)' || $v->originurl == '[0-9a-z]' ){
                    $text =  $v->visit_protocol .$v->originurl .'.'.$dname ;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "regex_map " . $v->visit_protocol .$v->originurl .'.'. str_replace(".","\\.",$dname)  . $originport. $v->origin_protocol . $v->aimurl.$aimport .$result. "\n";
                    continue;
                }

                $text =  $v->visit_protocol . $v->originurl . '.' . $dname;
                $result = $this->_black_domain($text,$data);
                $content_remap[] = "map " .$v->visit_protocol . $v->originurl . '.' . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport.$result . "\n";
            }
        }

        return $content_remap;
    }

    public function _black_domain($remap,$data)
    {
        $result = "";
        if(self::$blackIp) {
            if(isset(self::$blackIp[$data['user_id']][$data['package_id']][$remap]))
            {
                $result .=" @action=deny";
                foreach (self::$blackIp[$data['user_id']][$data['package_id']][$remap] as $val)
                {
                    $result .= " @src_ip=".$val;
                }

            }
        }
        return $result;
    }

    protected  function _origin_domain_defence($did,$dname,$content_remap,$data)
    {

        $remapInfo = DefenceRemap::find()->where(['did'=>$did])->all();
        if($remapInfo){
            foreach ($remapInfo as $v){

                //添加验证(回源地址)
                $pattIP = '/^((([0-9a-zA-Z]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,4})|((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9]))))|\:[0-9]{2,5}$/';
                if(!preg_match($pattIP, $v->aimurl) && !Utils::isUrl($v->aimurl) ){
                    continue;
                }
                $aimport = $v->aimport;
                $originport = $v->originport;
                if($aimport && $aimport !=80){
                    $aimport = ':'.$aimport.'/';
                }else{
                    $aimport = '/';
                }
                if($originport && $originport !=80){
                    $originport = ':'.$originport.'/ ';
                }else{
                    $originport = '/ ';
                }


                if($v->originurl=='@'){
                    //@ 回原
                    if(!$v->redirect_ssl) {
                        $text = $v->visit_protocol . $v->originurl;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "map " . $v->visit_protocol . $v->originurl . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result. "\n";
                    }
                    else {
                        $text = $v->visit_protocol . $v->originurl;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "redirect " . $v->visit_protocol . $v->originurl . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result. "\n";
                    }
                    continue;
                }
                if($v->originurl == '*')
                    $v->originurl = '(.*)';
                //301
                if($v->redirect_ssl){
                    $text = $v->visit_protocol . $v->originurl;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "redirect " . $v->visit_protocol . $v->originurl  . $originport. $v->origin_protocol . $v->aimurl.$aimport. $result ."\n";
                    continue;
                }
                if( $v->originurl ==='[a-z0-9]' || $v->originurl == '(.*)' || $v->originurl == '[0-9a-z]' ){
                    $text =  $v->visit_protocol .$v->originurl;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "map " . $v->visit_protocol .$v->originurl . $originport. $v->origin_protocol . $v->aimurl.$aimport.$result. "\n";
                    continue;
                }
                $text =  $v->visit_protocol . $v->originurl;
                $result = $this->_black_domain($text,$data);
                $content_remap[] = "map " . $v->visit_protocol . $v->originurl  . $originport. $v->origin_protocol . $v->aimurl.$aimport.$result."\n";
            }
        }
        return $content_remap;
    }



    /**
     * 更新单个节点脚本文件
     * @param $id
     */
    public function updateKit($id)
    {
        $model = Node::find()->where(['id'=>$id])->select('zabbix_ip,kit_id')->asArray()->one();
        if($model)
        {
            $path = \Yii::getAlias('@dns_file').'/node/'.$model['zabbix_ip']."/";
            FileUtil::createDir($path);
            $kitModel =  NodeKit::find()->where(['id'=>$model['kit_id']])->one();
            $curScripts  = array();//选中的脚本
            if($kitModel) {
                $curScripts = $kitModel->script;//选中套件脚本
            }
            $defaultScript = array(); //默认的脚本
            $defaultKitModel = '';

            if(isset($kitModel->is_default) && $kitModel->is_default == 0){
                $defaultKitModel = NodeKit::find()->where(['is_default'=>1])->one();
            }
            if($defaultKitModel){
                $defaultScript = $defaultKitModel->script;
            }
            $dir =  FileUtil::getDir($path);//目录存在的脚本文件

            if($defaultScript)
                $defaultScript= ArrayHelper::map($defaultScript,'name','content');

            if($curScripts)
                $curScripts = ArrayHelper::map($curScripts,'name','content');
            $asArr = [];//需要生成的脚本

            foreach ($curScripts as $key=>$val)
            {
                if(!empty($defaultScript))
                {
                    if(isset($defaultScript[$key]))
                        unset($defaultScript[$key]);
                    $asArr[$key] = $val;
                }else{
                    $asArr[$key] = $val;
                }
            }
            if(!empty($defaultScript))
            {
                foreach ($defaultScript as $dkey=>$dval)
                    $asArr[$dkey] =$dval;
            }
            if(!empty($asArr))
            {
                foreach ($asArr as $askey=>$asval)
                {
                    if(in_array($askey,$dir))
                    {
                        $keys = array_keys($dir,$askey);
                        unset($dir[$keys[0]]);
                        $result = Utils::contentCompare($path.$askey,$asval);
                        if($result){
                            file_put_contents($path.$askey,$asval);
                        }
                    }else{
                        file_put_contents($path.$askey,$asval);
                    }
                }
            }
            if(!empty($dir))
            {
                foreach ($dir as $dkey=>$dval)//删除不需要的脚本
                    FileUtil::rmFile($path.$dval);
            }
        }
    }


    /**
     * 更新脚本
     * 更新选中套件节点的脚本
     */
    public function updateKits($kit_id)
    {
        $node = NodeKit::find()->where(['id'=>$kit_id])->one();
        if($node->is_default) {
            $where = ['>', 'kit_id', 0];
        }else{
            $where = ['kit_id'=>$kit_id];
        }
        $list = Node::find()->where($where)->asArray()->select('id')->all();
        if(!empty($list))
        {
            foreach ($list as $val)
            {
                $this->updateKit($val['id']);
            }
        }
    }


    public static  function insertNodeGroupUpdate($data)
    {
        if($data ) {
            $dataIns = array();
            $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',$data])->select('node_id')->asArray()->all();
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
            foreach ($nodeArr as $nval)
                $dataIns[] = ['node_id' => $nval, 'create_time' => date('Y-m-d H:i:s')];
            \Yii::$app->db->createCommand()->batchInsert(NodeUpdate::tableName(), ['node_id', 'create_time'], $dataIns)->execute();
        }
    }

    public static function insertNodeUpdate($data)
    {
        if($data ) {
            $dataIns = array();
            foreach ($data as $nval)
                $dataIns[] = ['node_id' => $nval, 'create_time' => date('Y-m-d H:i:s')];
            \Yii::$app->db->createCommand()->batchInsert(NodeUpdate::tableName(), ['node_id', 'create_time'], $dataIns)->execute();
        }
    }

    /************************表关联***************************/
    public function getNodekit(){
        return $this->hasOne(NodeKit::className(), ['id' => 'kit_id']);
    }



}
