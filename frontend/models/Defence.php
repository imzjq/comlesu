<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/7
 * Time: 21:48
 */

namespace frontend\models;

use backend\models\Node;
use common\lib\Utils;
use common\models\Config;
use common\models\Defence as CommonDefence;
use common\models\DefenceRemap;
use common\models\NodeGroupNodeid;
use common\models\Remap;
use yii\helpers\ArrayHelper;

class Defence extends CommonDefence
{
    use ApiTrait;

    public static $statusMap = [
        2=>'已部署',
        1=>'未部署',
        0=>'未解析'
    ];
    public static $groupName;

    public function getList($page=1,$pagenum='',$where=[],$uid){
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
            if($datas){
                $pack = new Package();
                $packages = $pack->idToName($uid);
                $brandModel = new Brand();
                $brands = $brandModel->idToName($uid);
                foreach ($datas as $k=>$v){
                    $arr['id'] = $v->id;
                    $arr['dname'] = $v->dname;
                    $arr['cname'] = $v->cname;
                    $arr['status'] = self::$statusMap[$v->status];
                    $arr['status_int'] =$v->status;
                    $arr['brand_name'] = isset($brands[$v->brand_id]) ? $brands[$v->brand_id] : "";
                    $arr['package_name'] = isset($packages[$v->package_id]) ? $packages[$v->package_id] : "";
                    $arr['stype'] =  $v->stype  == \common\models\Domain::STYPE_DOMAIN ? "加速" : "轮询";
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


    //add
    public function add($data,$userInfo){
        $model = new Defence();
        //先检查数据
        $data['user_id'] = $userInfo['uid'];
        $data['username'] = $userInfo['username'];
        $data = $this->addBeferData($data,$userInfo);
        if($model->load($data,'') && !$model->validate()){
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        //参数后台检查
        $dname = $data['dname'];
         $domainModel = new Domain();
        $generateCnames = $domainModel->generateCnames($data,$userInfo);
        if($generateCnames['code'] !=200){
            return $generateCnames;
        }
        $cname = $generateCnames['data']['cname'];
        //多个回原IP时，生成的host name
        $cname_n = $generateCnames['data']['cname_n'];



        $enable = 1; // 启用
        $model->cname = $cname;
        $model->cname_n = $cname_n;


        $model->enable = $enable;
        $transaction = \Yii::$app->db->beginTransaction();
        if($model->save()){
            $did = $model->id;
        }else{
            $transaction->rollBack();
            return $this->error('保存失败');
        }
        //remap 高级回原,多个
        $remapDataPost = $data['remapData'];
        if(empty($remapDataPost)){
            $transaction->rollBack();
            return $this->error('高级回原参数错误');
        }
        $remapData = [];
        foreach ($remapDataPost as $k =>$r){
            //去除空的主机头，回原IP
            if(!empty($r['originurl']) && !empty($r['aimurl'])){
                $remapData[] = $r;
                $originurl_arr[] = $r['originurl'];
            }
        }
//        if(!$remapData[0]['is_at'] || $remapData[0]['redirect_ssl']){
//            //第一高级回原 主机头不是@  或者第一个是301， 则提示参数错误
//            return $this->error('高级回原参数错误');
//        }

        $domain = new Domain();
        foreach ($remapData as $d){
            $ssl_id = 0;
            if($d['visit_protocol'] == 'https://')
            {
                $checkSsl = $domain->checkSsl($userInfo['username'],$d['originurl'],'dcheck');
                if(!$checkSsl){
                    return $this->error('请先上传'.$d['originurl'].'证书');
                }
                $ssl_id = $checkSsl->user_cer_id ;
            }
            $preview = $this->generateOriginRemap($d);
            $result = $this->urlExisDomain($preview);
            if(!empty($result)) {
                $transaction->rollBack();
                return $this->error($preview . "存在单域名单CNAME中");
            }
            $remapModel = new DefenceRemap();
            $remap_data = array(
                'did' => $did,
                'dname' => $dname,
                'is_at'=>$d['is_at'],
                'redirect_ssl'=>$d['redirect_ssl'],
                'visit_protocol'=>$d['visit_protocol'],
                'origin_protocol'=>$d['origin_protocol'],
                'originurl' => $d['originurl'],
                'aimurl' => $d['aimurl'],
                'aimport' => ($d['aimport'])?$d['aimport']:'80',
                'originport' => isset($d['originport']) ? $d['originport'] : '' ,
                'preview' => $preview ,
                'ssl_id' => $ssl_id
            );
            if($remapModel->load($remap_data,'') && $remapModel->save()){

            }else{
                $transaction->rollBack();
                $msg = ($this->getModelError($remapModel));
                return $this->error($msg);
            }
        }


        if($data['status'] == 2 ){
        $limit  =  $domain->packageLimit($data);
        if($limit['code'] != 200)
        {
            $transaction->rollBack();
            return $limit;
        }
        }

       // $this->defenNodeid($model->id,$model->node_group,'add');
        $transaction->commit();
        $this->addNodeFile($model);
        return $this->success();
    }



    public function batchAdd($data,$userInfo){

         $datas = $data['remapData'];

         $aimurl = $data['aimurl'];

        $generateCnames = [];
        $cname = "";
        $domainModel = new Domain();
        $transaction = \Yii::$app->db->beginTransaction();
         foreach ($datas as $temp)
         {
             $dataTemp = [];
             $dataTemp['user_id'] = $userInfo['uid'];
             $dataTemp['username'] = $userInfo['username'];
             $dataTemp['dname'] = $temp['originurl'];
             $dataTemp['brand_id'] =$data['brand_id'];
             $dataTemp['package_id'] =$data['package_id'];
             $dataTemp['status'] =$data['status'];
             $dataTemp['enable'] =1;
             $dataTemp['stype'] =$data['stype'];
             $dataTemp['originip'] =$aimurl;
             $dataTemp = $this->addBeferData($dataTemp,$userInfo);

             if(!$generateCnames) {
                 $generateCnames = $domainModel->generateCnames($dataTemp,$userInfo);
                 if($generateCnames['code'] !=200){
                     return $generateCnames;
                 }
                 $cname = $generateCnames['data']['cname'];
             }
             $dataTemp['cname'] = $cname;

             $model = new Defence();
             if($model->load($dataTemp,'') && !$model->validate()){
                 $msg = ($this->getModelError($model));
                 return $this->error($msg);
             }

             if($model->save()){
                 $did = $model->id;
             }else{
                 $transaction->rollBack();
                 return $this->error('保存失败');
             }



             $ssl_id = 0;
             if($temp['visit_protocol'] == 'https://')
             {
                 $checkSsl = $domainModel->checkSsl($userInfo['username'],$temp['originurl'],'dcheck');
                 if(!$checkSsl){
                     return $this->error('请先上传'.$temp['originurl'].'证书');
                 }
                 $ssl_id = $checkSsl->user_cer_id ;
             }
             $temp['redirect_ssl'] = 0;
             $preview = $this->generateOriginRemap($temp);
             $result = $this->urlExisDomain($preview);
             if(!empty($result)) {
                 $transaction->rollBack();
                 return $this->error($preview . "存在单域名单CNAME中");
             }

             $remapModel = new DefenceRemap();
             $remap_data = array(
                 'did' => $did,
                 'dname' => $temp['originurl'],
                 'is_at'=>1,
                 'redirect_ssl'=>0,
                 'visit_protocol'=>$temp['visit_protocol'],
                 'origin_protocol'=>$data['origin_protocol'],
                 'originurl' => $temp['originurl'],
                 'aimurl' => $data['aimurl'],
                 'aimport' => ($data['aimport'])?$data['aimport']:'80',
                 'originport' => isset($temp['originport']) ? $temp['originport'] : '' ,
                 'preview' => $preview ,
                 'ssl_id' => $ssl_id
             );
             if($remapModel->load($remap_data,'') && $remapModel->save()){

             }else{
                 $transaction->rollBack();
                 $msg = ($this->getModelError($remapModel));
                 return $this->error($msg);
             }
             //$this->defenNodeid($model->id,$model->node_group,'add');
             $this->addNodeFile($model);
         }

         if($data['status'] == 2) {
             $limit = $domainModel->packageLimit(['user_id' => $userInfo['uid'], 'package_id' => $data['package_id']]);
             if ($limit['code'] != 200) {
                 $transaction->rollBack();
                 return $limit;
             }
         }
        $transaction->commit();

        return $this->success();

    }


    public function addBeferData($data,$userInfo)
    {

        $config = Config::find()->where(['name'=>'domain_ttl'])->select('value')->one();
        if($config)
            $ttl = $config->value;
        else
            $ttl = 600 ;

        $data['high_anti'] = 0;
        $data['node_group'] = 0;
        $userConfig = Package::find()->where(['id'=>$data['package_id']])->asArray()->one();
        if($userConfig)
        {
              $data['high_anti'] = $userConfig['defence_ip_id'];
                if($data['stype'] == \common\models\Domain::STYPE_DOMAIN) {
                    $data['node_group'] = $userConfig['group_id'];
                }else{
                    $data['node_group'] = $userConfig['defence_group_id'];
                }
        }
        if(empty($data['high_anti']))
        {
            $country = CountryType::find()->select('id')->where(['type'=>$userInfo['registsource']])->one();
            if($country) {
                $nodeData = DefenceIp::find()->where(['country'=>$country->id,'remark'=>1])->select('id')->one();
                if($nodeData) {
                    $data['high_anti'] = $nodeData->id;
                }
            }
        }

        if(empty($data['node_group']))
        {
            $country = CountryType::find()->select('id')->where(['type'=>$userInfo['registsource']])->one();
            if($country) {
                $nodeData = NodeGroup::find()->where(['type'=>$country->id,'isDefault'=>1])->select('id')->one();
                if($nodeData) {
                    $data['node_group'] = $nodeData->id;
                }
            }
        }
        $data['ttl'] = $ttl;
        return $data;
    }


    /**
     *
     * @param $omodel
     * @param $nmodel
     */
    public function addNodeFile($model,$option = false)
    {
        if($model->status == 2 || $option == true){

            if($model->sys_node_group ==0 || $model->sys_node_group == $model->node_group)
                $nodeGroup = NodeGroupNodeid::find()->where(['node_group_id'=>$model->node_group])->asArray()->all();
            else
                $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',[$model->node_group,$model->sys_node_group]])->asArray()->all();

            //$nodeGroup = NodeGroupNodeid::find()->where(['node_group_id'=>$model->node_group])->asArray()->all();
            if($nodeGroup)
            {
                $nodeArr = array();
                foreach ($nodeGroup as $nval) {
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
                $nodeArr = array_unique($nodeArr);
               Node::insertNodeUpdate($nodeArr);
            }
        }
    }

    //修改
    public function updateInfo($data,$userInfo){
        $id = isset($data['id'])?$data['id']:'';

        $model = Defence::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
        if(!$model){
            return $this->error('未找到相应信息');
        }
        $omodel = $model->getOldAttributes();
        unset($data['id']);
        $data = $this->addBeferData($data,$userInfo);
        //先检查数据
        if($model->load($data,'') && !$model->validate()){
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }


        $dname = $data['dname'];
        $domainModel = new Domain();
        $generateCnames = $domainModel->generateCnames($data,$userInfo);
        if($generateCnames['code'] !=200){
            return $generateCnames;
        }

        //$cname = $generateCnames['data']['cname'];
        //多个回原IP时，生成的host name
        //$cname_n = $generateCnames['data']['cname_n'];

        $user_id = $generateCnames['data']['user_id'];


        $enable = 1; // 启用
       // $model->cname = $cname;
       // $model->cname_n = $cname_n;

        $model->user_id = $user_id;
        $model->enable = $enable;
        $transaction = \Yii::$app->db->beginTransaction();
        if($model->save()){
            $did = $id;
        }else{
            $transaction->rollBack();
            $msg = ($this->getModelError($model));
            return $this->error($msg);
            //return $this->error('加速保存失败');
        }
        //remap 高级回原,多个
        $remapDataPost = $data['remapData'];

        if(empty($remapDataPost)){
            $transaction->rollBack();
            return $this->error('高级回原参数错误');
        }
        $remapData = [];
        foreach ($remapDataPost as $k =>$r){
            //去除空的主机头，回原IP
            if(!empty($r['originurl']) && !empty($r['aimurl'])){
                $remapData[] = $r;
                $originurl_arr[] = $r['originurl'];
            }
        }
//        if(!$remapData[0]['is_at'] || $remapData[0]['redirect_ssl']){
//            //第一高级回原 主机头不是@  或者第一个是301， 则提示参数错误
//            return $this->error('高级回原参数错误');
//        }

        //先删除
        $del = DefenceRemap::deleteAll(['did'=>$did]);
        if(!$del){
            $transaction->rollBack();
            return $this->error('高级回原出错');
        }
        $domain = new Domain();
        foreach ($remapData as $d){
            $ssl_id = 0;
            if($d['visit_protocol'] == 'https://')
            {
                $checkSsl = $domain->checkSsl($data['username'],$d['originurl'],'dcheck');
                if(!$checkSsl){
                    return $this->error('请先上传'.$d['originurl'].'证书');
                }
                $ssl_id = $checkSsl->user_cer_id ;
            }
            $preview = $this->generateOriginRemap($d);
            $result = $this->urlExisDomain($preview);
            if(!empty($result)) {
                $transaction->rollBack();
                return $this->error($preview . "存在域名加速中");
            }
            $remapModel = new DefenceRemap();
            $remap_data = array(
                'did' => $did,
                'dname' => $dname,
                'is_at'=>(int)$d['is_at'],
                'redirect_ssl'=>(int)$d['redirect_ssl'],
                'visit_protocol'=>$d['visit_protocol'],
                'origin_protocol'=>$d['origin_protocol'],
                'originurl' => $d['originurl'],
                'aimurl' => $d['aimurl'],
                'aimport' => ($d['aimport'])?$d['aimport']:'80',
                'originport' => isset($d['originport']) ? $d['originport'] : '' ,
                'preview' =>$preview,
                'ssl_id' => $ssl_id
            );

            if($remapModel->load($remap_data,'') && $remapModel->save()){

            }else{
                $transaction->rollBack();
                $msg = ($this->getModelError($remapModel));
                return $this->error($msg);
                //return $this->error('高级回原保存失败');
            }
        }

        if($data['status'] == 2 ) {
            $limit = $domain->packageLimit($data);
            if ($limit['code'] != 200) {
                $transaction->rollBack();
                return $limit;
            }
        }

        //$this->defenNodeid($model->id,$model->node_group,'update');
        $transaction->commit();
        $this->updateNodeFile($omodel,$model);
        return $this->success();
    }


    /**
     * 是否存在加速
     * @param $url
     * @return bool|int|string
     */
    public function urlExisDomain($url)
    {
        if($url)
            return Remap::find()->where(['preview'=>$url])->count();
        return false;
    }

    /**
     *
     * @param $omodel
     * @param $nmodel
     */
    public function updateNodeFile($omodel,$nmodel)
    {

        $node_group_arr = [];
        if($omodel['node_group'] != $nmodel->node_group)
        {
            $node_group_arr[] = $omodel['node_group'];
            $node_group_arr[] = $nmodel->node_group;

        }else{
            $node_group_arr[] = $omodel['node_group'];
        }

        if($omodel['sys_node_group'] != $nmodel->sys_node_group || $omodel['sys_node_group'] != 0 || $nmodel->sys_node_group != 0 )
        {
            if($omodel['sys_node_group'] != 0)
                $node_group_arr[] = $omodel['sys_node_group'];
            if($nmodel->sys_node_group != 0)
                $node_group_arr[] = $nmodel->sys_node_group;
        }
        $node_group_arr = array_unique($node_group_arr);

        $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',$node_group_arr])->select('node_id')->groupBy('node_id')->asArray()->all();


        if($nodeGroup)
        {
            $nodeArr = array();
            foreach ($nodeGroup as $nval) {
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
            $nodeArr = array_unique($nodeArr);
            Node::insertNodeUpdate($nodeArr);
        }
    }


    public function getOne($id,$userInfo){
        $model = Defence::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $remap = DefenceRemap::find()->where(['did'=>$id])->asArray()->all();
        $model['remapData'] = $remap;
        $model = $this->numToInt($model);
        if(empty($model['brand_id']))
            $model['brand_id'] = "";

        return $this->success($model);
    }

    public function del($id){
        return $this->error('错误操作');
    }


    //修改状态，部署status=2或未部署status=0
    public function changeStatus($data,$userInfo){
        $ids = $data['id'];
        $status = $data['status'];
        if(!in_array($status,[1,2])|| empty($ids)){
            return $this->error('参数错误');
        }
        $domain = new Domain();

        //批量修改
        $transaction = \Yii::$app->db->beginTransaction();
        foreach ( $ids as $id)
        {
            $model = Defence::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
            if(empty($model))
            {
                $transaction->rollBack();
                return $this->error('找不到记录');
            }

            if($model->status != $status)
            {
                $model->status = $status;
                $model->save();
                if($status == 2) {
                    $limit = $domain->packageLimit(['user_id'=>$userInfo['uid'],'package_id'=>$model->package_id]);
                    if ($limit['code'] != 200) {
                        $transaction->rollBack();
                        return $limit;
                    }
                }
            }
        }
        $this->changeNodeFile($ids);
        $transaction->commit();
        return $this->success();
    }
//    public function changeStatus($data,$userInfo){
//        $id = isset($data['id'])?$data['id']:'';
//        $transaction = \Yii::$app->db->beginTransaction();
//        // $model = Defence::findOne($id);
//        $model = Defence::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
//        if(!$model){
//            return $this->error('未找到相应信息');
//        }
//        $status = $data['status'];
//        if(!in_array($status,[0,1,2])){
//            return $this->error('参数错误');
//        }
//        $model->status = $status;
//        if($model->save()){
//            if($model->status == 2)
//            {
//                $domain = new Domain();
//                $limit  =  $domain->packageLimit(['user_id'=>$userInfo['uid'],'package_id'=>$model->package_id]);
//                if($limit['code'] != 200)
//                {
//                    $transaction->rollBack();
//                    return $limit;
//                }
//            }
//            $this->addNodeFile($model,true);
//            $transaction->commit();
//            return $this->success();
//        }else{
//            $transaction->rollBack();
//            return $this->error('操作失败');
//        }
//    }



    public function changeNodeFile($idArr)
    {
        $domain = Defence::find()->where(['in','id',$idArr])->select('node_group,sys_node_group')->asArray()->all();
        if($domain)
        {
            $group = array();
            foreach ($domain as $val)
            {
                if($val['sys_node_group'] == 0 || $val['node_group'] == $val['sys_node_group']) {
                    $group[] = (int)$val['node_group'];
                }
                else
                {
                    $group[] = (int)$val['node_group'];
                    $group[] = (int)$val['sys_node_group'];
                }
            }
            $group =  array_unique($group);
            $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',$group])->asArray()->all();

            if($nodeGroup)
            {

                $nodeArr = array();
                foreach ($nodeGroup as $nval) {
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

                $nodeArr = array_unique($nodeArr);
                Node::insertNodeUpdate($nodeArr);
            }
        }
    }


    //id=>cname
    public function idToDomains(){
        $res = Defence::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','domains');
        return $arr;
    }


    //生成预览remap 格式
    public function generateRemap($data){
        $txt = [];
        $remapData = $data['remapData'];
        if(!empty($remapData)){
            foreach ($remapData as $v){
                //添加验证(回源地址)
                $pattIP = '/^((([0-9a-zA-Z]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,4})|((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9]))))|\:[0-9]{2,5}$/';
                if(!preg_match($pattIP, $v['aimurl'])){
                    continue;
                }
                $originurl = $v['originurl'];
                $visit_protocol = $v['visit_protocol'];
                $redirect_ssl = $v['redirect_ssl'];
                $originport = isset($v['originport'])?$v['originport']:'';
                $origin_protocol = $v['origin_protocol'];
                $aimurl = $v['aimurl'];
                $aimport = $v['aimport'];
                if($aimport && $aimport != '80'){
                    $aimport = ':'.$aimport.'/';
                }else{
                    $aimport = '/';
                }
                if($originport  && $originport !=80 ){
                    $originport = ':'.$originport.'/ ';
                }else{
                    $originport = '/ ';
                }

                if($originurl == '*')
                    $originurl = '(.*)';
                if($originurl=='@'){
                    //map
                    if($redirect_ssl)
                        $txt[] = $content_remap[] = "regex_redirect " . $visit_protocol . $originurl . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    else
                        $txt[] = $content_remap[] = "map " . $visit_protocol . $originurl . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    continue;
                }

                if($redirect_ssl){
                    //301跳转
                    $txt[] = $content_remap[] = "regex_redirect " . $visit_protocol .$originurl . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    continue;
                }

                if($originurl=='(.*)' || $originurl=='[a-z0-9]' || $originurl=='[0-9a-z]' ){
                    $txt[] = $content_remap[] = "regex_map " . $visit_protocol .$originurl . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    continue;
                }

                //
                $txt[] = $content_remap[] = "map " . $visit_protocol .$originurl . $originport . $origin_protocol . $aimurl.$aimport. "\n";
            }
        }

        return $txt;
    }


    public function generateOriginRemap($v){
        $originurl = $v['originurl'];
        $visit_protocol = $v['visit_protocol'];
        $redirect_ssl = $v['redirect_ssl'];

        if($originurl == '*')
            $originurl = '(.*)';
        if($originurl=='@'){
            if($redirect_ssl)
               // return "regex_redirect " . $visit_protocol . $originurl;
                return "redirect " . $visit_protocol . $originurl;
            else
                return "map " . $visit_protocol . $originurl;
        }

        if($redirect_ssl){
            //301跳转
           // return "regex_redirect " . $visit_protocol .$originurl;
            return "redirect " . $visit_protocol .$originurl;
        }

        if($originurl=='(.*)' || $originurl=='[a-z0-9]' || $originurl=='[0-9a-z]' ){
            //return "regex_map " . $visit_protocol .$originurl ;
            return "map " . $visit_protocol .$originurl ;
        }

        return "map " . $visit_protocol .$originurl;
    }


    public function batchUpdateOrigin($data,$userInfo)
    {

        $checkedDomain = $data['checkedDefence'];
        if(empty($checkedDomain) || !is_array($checkedDomain))
            return $this->error("请选择域名");
        $origin_protocol = $data['remapData'][0]['origin_protocol'];
        $aimurl = trim($data['remapData'][0]['aimurl']);
        $aimport = trim($data['remapData'][0]['aimport']);
        $transaction = \Yii::$app->db->beginTransaction();
        $check_ip = Utils::isIp($aimurl);
        if(!$check_ip && !Utils::isUrl($aimurl)){
            return $this->error('ip格式错误');
        }
        $package_ids = [];
        foreach ($checkedDomain as $value)
        {
            $model = DefenceRemap::find()->where(['id'=>$value])->one();
            if(!$model)
            {
                $transaction->rollBack();
                return $this->error('找不到域名');
            }
            $domain = Defence::find()->where(['user_id'=>$userInfo['uid'],'id'=>$model->did])->one();
            $package_ids [] = $domain->package_id;
            if(!$domain)
            {
                $transaction->rollBack();
                return $this->error('找不到记录');
            }
            $model->origin_protocol = $origin_protocol;
            $model->aimurl = $aimurl;
            $model->aimport = $aimport;
            if(!$model->save())
            {
                $transaction->rollBack();
                return $this->error('修改失败');
            }
            $this->addNodeFile($domain,true);
        }
        if($package_ids)//判断套餐是否超过源
        {
            $package_ids = array_unique($package_ids);
            $domain = new Domain();
            foreach ($package_ids as $value)
            {
                $data['user_id'] =$userInfo['uid'];
                $data['package_id'] = $value;
                $limit =  $domain->packageLimit($data);
                if($limit['code'] != 200)
                {
                    $transaction->rollBack();
                    return $this->error('套餐源数量超过限制');
                }
            }
        }
        $transaction->commit();
        return $this->success();
    }

}
