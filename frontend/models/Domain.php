<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/31
 * Time: 22:42
 */

namespace frontend\models;

use backend\models\Node;
use common\lib\Utils;
use common\models\Config;
use common\models\DefenceRemap;
use common\models\Domain as CommonDomain;
use common\models\NodeGroupNodeid;
use common\models\NodeUpdate;
use common\models\Remap;
use common\models\UserCerDomain;
use yii\db\Query;
use yii\helpers\ArrayHelper;


class Domain extends CommonDomain
{
    use ApiTrait;

    public static $hostSuffix = '.lshost.com';

    public static $statusMap = [
        2=>'已部署',
        1=>'未部署',
        0=>'未解析'
    ];

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
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();
            $brandModel = new Brand();
            $brands = $brandModel->idToName($uid);
            $pack = new Package();
            $packages = $pack->idToName($uid);
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['dname'] = $data->dname;
                $arr['create_time']  = date('Y-m-d H:i:s',$data->create_time);
                $arr['cname'] = $data->cname;
                $arr['status'] = self::$statusMap[$data->status];
                $arr['status_int'] = $data->status;
                $arr['brand_name'] = isset($brands[$data->brand_id]) ? $brands[$data->brand_id] : "";
                $arr['package_name'] = isset($packages[$data->package_id]) ? $packages[$data->package_id] : "";
                $arr['stype'] =  $data->stype  == \common\models\Domain::STYPE_DOMAIN ? "加速" : "轮询";
                $csdatas[] = $arr;
            }
        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }


    //新增加速
    public function add($data,$userInfo){

        $model = new Domain();
        $data['user_id'] = $userInfo['uid'] ;
        $data['username'] = $userInfo['username'] ;
        $data = $this->addBeferData($data,$userInfo);
        //先检查数据
        unset($data['cname']);
        if($model->load($data,'') && !$model->validate()){
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        //参数后台检查
        $originip = $data['originip']; //回原地址/ip 可以多个，用逗号隔开
        $dname = $data['dname'];
        $generateCnames = $this->generateCnames($data,$userInfo);

        if($generateCnames['code'] !=200){
            return $generateCnames;
        }

        $cname = $generateCnames['data']['cname'];
        //多个回原IP时，生成的host name
        $cname_n = $generateCnames['data']['cname_n'];

//        $is_https = $data['is_https'];
//        if($is_https){
//            //检查主域名是否有证书
//            $checkSsl = $this->checkSsl($data['username'],$dname);
//            if(!$checkSsl){
//                return $this->error('请先上传证书');
//            }
//            $model->ssl_id = $checkSsl['id'];
//        }

       // $stype = 0; //加速类型，默认加速
        $remap = 1; //默认开启正则回原
        $enable = 1; // 启用

        //$model->status = 2;
        $model->cname = $cname;
        $model->cname_n = $cname_n;
        //$model->is_https = $is_https;
       // $model->stype = $stype;
        $model->remap = $remap;
        $model->enable = $enable;


        $transaction = \Yii::$app->db->beginTransaction();

        if($model->save()){
            $did = $model->id;
        }else{
            $transaction->rollBack();
            return $this->error('加速保存失败');
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
        //去重复
        /*
        $originurl_arr = array_unique($originurl_arr);
        if(count($originurl_arr) !== count($remapData)){
            return $this->error('主机头有重复!');
        }
        */

//        if(!$remapData[0]['is_at'] || $remapData[0]['redirect_ssl']){
//            //第一高级回原 主机头不是@  或者第一个是301， 则提示参数错误
//            return $this->error('高级回原参数错误');
//        }

        foreach ($remapData as $d){
            $ssl_id = 0;
            if($d['visit_protocol'] == 'https://')
            {
                if($d['originurl'] != '@')
                {
                    $checkSsl = $this->checkSsl($data['username'],$d['originurl'].'.'.$dname,'check');
                    if(!$checkSsl){
                        return $this->error('请先上传'.$d['originurl'].'.'.$dname.'证书');
                    }
                }else{
                    $checkSsl = $this->checkSsl($data['username'],$dname);
                    if(!$checkSsl){
                        return $this->error('请先上传'.$dname.'证书');
                    }
                }
                $ssl_id = $checkSsl->user_cer_id ;
            }
            $d['dname'] = $dname;
            $preview = $this->generateOriginRemap($d);
            $result = $this->urlExisDefence($preview);
            if(!empty($result)) {
                $transaction->rollBack();
                return $this->error($preview . "存在多域名单CNAME中");
            }
            if(!Utils::isIp($d['aimurl']) && !Utils::isUrl($d['aimurl']) )
            {
                $transaction->rollBack();
                return $this->error("源ip填写错误");
            }
            $remapModel = new Remap();
            $remap_data = array(
                'did' => $did,
                'dname' => $dname,
                'is_at'=>$d['is_at'],
                'redirect_ssl'=>$d['redirect_ssl'],
                'visit_protocol'=>$d['visit_protocol'],
                'origin_protocol'=>$d['origin_protocol'],
                'originurl' => $d['originurl'],
                'aimurl' => $d['aimurl'],
                'aimport' => ($d['aimport'])? $d['aimport'] : '80',
                'originport' => isset($d['originport']) ? $d['originport'] : '' ,
                'preview' =>$preview ,
                'ssl_id' => $ssl_id
            );
            if($remapModel->load($remap_data,'') && $remapModel->save()){

            }else{

                $transaction->rollBack();
                return $this->error($remapModel->firstErrors);
            }
        }

        if($data['status'] == 2) {
            $limit = self::packageLimit($data);
            if ($limit['code'] != 200) {
                $transaction->rollBack();
                return $limit;
            }
        }
        $transaction->commit();
        $this->addNodeFile($model);
        return $this->success();

    }

    public function packageLimit($data)
    {
        $user_id = $data['user_id'];
        $package_id = $data['package_id'];
        $res = Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');
        $domain = Domain::find()->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$package_id])->select('id,dname')->asArray()->all();
        $domain_ids = [];
        foreach ($domain as $val)
        {
            $domain_ids[] = $val['id'];
        }
        unset($val);
        $defence = Defence::find()->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$package_id])->select('id')->asArray()->all();
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

        if($count>$res['origin_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['origin_quantity'].'个源');
        }

        $query = new Query();
        $query->where(['user_id'=>$user_id,'status'=>2,'package_id'=>$package_id])->select("dname")->from('{{%domain}}');
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
        $count =  count($url_count); //使用域名的数量

        if($count>$res['url_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['url_quantity'].'个域名');
        }
        return $this->success();
    }

    /**
     * 批量添加
     * @param $data
     * @return array
     * @throws \yii\db\Exception
     */
    public function addBatch($data,$userInfo){


        $dnames = $data['dnames'];
        if(!$dnames)
            return $this->error('请填写域名');

        $transaction = \Yii::$app->db->beginTransaction();
        $arr=explode("\n",$dnames);

        if(empty($arr))
            return $this->error('请填写域名');
        $arr = array_filter($arr);
        foreach ($arr as $dnameArr) {
            //先检查数据
            $dname = $dnameArr ;
            $data['dname'] = $dname ;
            $model = new Domain();
            $data['user_id'] = $userInfo['uid'] ;
            $data['username'] = $userInfo['username'] ;
            $data = $this->addBeferData($data,$userInfo);
            if ($model->load($data, '') && !$model->validate()) {
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
            //参数后台检查
            $generateCnames = $this->generateBatchCnames($data,'add',$userInfo);
            if ($generateCnames['code'] != 200) {
                return $generateCnames;
            }
            $cname = $generateCnames['data']['cname'];
            //多个回原IP时，生成的host name
            $cname_n = $generateCnames['data']['cname_n'];

//            $is_https = $data['is_https'];
//            if ($is_https) {
//                //检查主域名是否有证书
//                $checkSsl = $this->checkSsl($data['username'], $dname);
//                if (!$checkSsl) {
//                    return $this->error('请先上传证书');
//                }
//                $model->ssl_id = $checkSsl['id'];
//            }
            //$stype = 0; //加速类型，默认加速
            $remap = 1; //默认开启正则回原
            $enable = 1; // 启用

            //$model->status = 2;
            $model->cname = $cname;
            $model->cname_n = $cname_n;
            //$model->is_https = $is_https;
            //$model->stype = $stype;
            $model->remap = $remap;
            $model->enable = $enable;

            if ($model->save()) {
                $did = $model->id;
            } else {
                $transaction->rollBack();
                return $this->error('保存失败');
            }
            //remap 高级回原,多个
            $remapDataPost = $data['remapData'];
            if (empty($remapDataPost)) {
                $transaction->rollBack();
                return $this->error('高级回原参数错误');
            }
            $remapData = [];
            foreach ($remapDataPost as $k => $r) {
                //去除空的主机头，回原IP
                if (!empty($r['originurl']) && !empty($r['aimurl'])) {
                    $remapData[] = $r;
                    $originurl_arr[] = $r['originurl'];
                }
            }
            foreach ($remapData as $d) {

                $ssl_id = 0;
                if($d['visit_protocol'] == 'https://')
                {
                    if($d['originurl'] != '@')
                    {
                        $checkSsl = $this->checkSsl($data['username'],$d['originurl'].'.'.$dname,'check');
                        if(!$checkSsl){
                            $transaction->rollBack();
                            return $this->error('请先上传'.$d['originurl'].'.'.$dname.'证书');
                        }
                    }else{
                        $checkSsl = $this->checkSsl($data['username'],$dname);
                        if(!$checkSsl){
                            $transaction->rollBack();
                            return $this->error('请先上传'.$dname.'证书');
                        }
                    }
                    $ssl_id = $checkSsl->user_cer_id  ;
                }
                $d['dname'] = $dname;
                $preview = $this->generateOriginRemap($d);
                $result = $this->urlExisDefence($preview);
                if(!empty($result)) {
                    $transaction->rollBack();
                    return $this->error($preview . "存在多域名单CNAME中");
                }
                if(!Utils::isIp($d['aimurl']) && !Utils::isUrl($d['aimurl']) )
                {
                    $transaction->rollBack();
                    return $this->error("源ip填写错误");
                }
                $remapModel = new Remap();
                $remap_data = array(
                    'did' => $did,
                    'dname' => $dname,
                    'is_at' => $d['is_at'],
                    'redirect_ssl' => $d['redirect_ssl'],
                    'visit_protocol' => $d['visit_protocol'],
                    'origin_protocol' => $d['origin_protocol'],
                    'originurl' => $d['originurl'],
                    'aimurl' => $d['aimurl'],
                    'aimport' => ($d['aimport']) ? $d['aimport'] : '80',
                    'originport' => isset($d['originport']) ? $d['originport'] : '' ,
                    'preview' => $preview ,
                    'ssl_id' => $ssl_id
                );
                if ($remapModel->load($remap_data, '') && $remapModel->save()) {

                } else {
                    $transaction->rollBack();
                    return $this->error($remapModel->firstErrors);
                }
            }
        }
        if($data['status'] == 2) {
            $limit = self::packageLimit($data);
            if ($limit['code'] != 200) {
                $transaction->rollBack();
                return $limit;
            }
        }
        $transaction->commit();
        $this->addNodeFile($model);
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
    public function addNodeFile($model)
    {
        if($model->status == 2){

            if($model->sys_node_group ==0 || $model->sys_node_group == $model->node_group)
                $nodeGroup = NodeGroupNodeid::find()->where(['node_group_id'=>$model->node_group])->asArray()->all();
            else
                $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',[$model->node_group,$model->sys_node_group]])->asArray()->all();

            if($nodeGroup)
            {
                $nodeArr = array();
                foreach ($nodeGroup as $nval) {
                    $nodeArr[] = $nval['node_id'];
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


    public function getOne($id,$userInfo){

        $domain = Domain::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->asArray()->one();
        if(!$domain){
            return $this->error('未找到相应数据');
        }

       // $domain['is_spider'] = explode(',',$domain['is_spider']);

        $remap = Remap::find()->where(['did'=>$id])->asArray()->all();
        if(!$remap){
            return $this->error('未找到高级回源数据');
        }
        if(empty($domain['brand_id']))
            $domain['brand_id'] = "";
        $domain['remapData'] = $remap;
        $domain = $this->numToInt($domain);
        //$data['remapData'] = $remap;
        return $this->success($domain);
    }

    //修改
    public function updateInfo($data,$userInfo){
        $id = isset($data['id'])?$data['id']:'';
        //$model = Domain::findOne($id);
        $model = Domain::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
        if(!$model){
            return $this->error('未找到相应信息');
        }
        $omodel = $model->getOldAttributes();
        unset($data['id']);
        //先检查数据

        $data = $this->addBeferData($data,$userInfo);
        if($model->load($data,'') && !$model->validate()){
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        $originip = $data['originip']; //回原地址/ip 可以多个，用逗号隔开
        $dname = $data['dname'];
        $generateCnames = $this->generateCnames($data,$userInfo);
        if($generateCnames['code'] !=200){
            return $generateCnames;
        }

       // $cname = $generateCnames['data']['cname'];
        //多个回原IP时，生成的host name
        //$cname_n = $generateCnames['data']['cname_n'];

        $user_id = $generateCnames['data']['user_id'];

//        $is_https = $data['is_https'];
//        if($is_https){
//            //检查主域名是否有证书
//            $checkSsl = $this->checkSsl($userInfo['username'],$dname);
//            if(!$checkSsl){
//                return $this->error('请先上传证书');
//            }
//            $model->ssl_id = $checkSsl['id'];
//        }


        //$stype = 0; //加速类型，默认加速
        $remap = 1; //默认开启正则回原
        $status = $data['status']; //部署中
        $enable = 1; // 启用

       // $model->cname = $cname;
        //$model->cname_n = $cname_n;
       // $model->is_https = $is_https;
        //$model->stype = $stype;
        $model->remap = $remap;
        $model->status = $status;
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
        //去重复
        /*
        $originurl_arr = array_unique($originurl_arr);
        if(count($originurl_arr) !== count($remapData)){
            return $this->error('主机头有重复!');
        }
        */

//        if(!$remapData[0]['is_at'] || $remapData[0]['redirect_ssl']){
//            //第一高级回原 主机头不是@  或者第一个是301， 则提示参数错误
//            return $this->error('高级回原参数错误');
//        }



        //先删除
        $del = Remap::deleteAll(['did'=>$did]);
        if(!$del){
            $transaction->rollBack();
            return $this->error('高级回原出错');
        }
        foreach ($remapData as $d){
            $ssl_id = 0 ;
            if($d['visit_protocol'] == 'https://')
            {
                if($d['originurl'] !='@')
                {
                    $checkSsl = $this->checkSsl($data['username'],$d['originurl'].'.'.$dname,'check');
                    if(!$checkSsl){
                        return $this->error('请先上传'.$d['originurl'].'.'.$dname.'证书');
                    }
                }else{
                    $checkSsl = $this->checkSsl($data['username'],$dname);
                    if(!$checkSsl){
                        return $this->error('请先上传'.$dname.'证书');
                    }
                }
                $ssl_id = $checkSsl->user_cer_id ;
            }
            $d['dname'] = $dname;
            $preview = $this->generateOriginRemap($d);
            $result = $this->urlExisDefence($preview);
            if(!empty($result)) {
                $transaction->rollBack();
                return $this->error($preview . "存在多域名单CNAME中");
            }
            if(!Utils::isIp($d['aimurl']) && !Utils::isUrl($d['aimurl']) )
            {
                $transaction->rollBack();
                return $this->error("源ip填写错误");
            }
            $remapModel = new Remap();
            $remap_data = array(
                'did' => $did,
                'dname' => $dname,
                'is_at'=>(int)$d['is_at'],
                'redirect_ssl'=>(int)$d['redirect_ssl'],
                'visit_protocol'=>$d['visit_protocol'],
                'origin_protocol'=>$d['origin_protocol'],
                'originurl' => $d['originurl'],
                'aimurl' => $d['aimurl'],
                'aimport' => $d['aimport'] != '' ?$d['aimport']:'80',
                'originport' =>$d['originport'] ,
                'preview' =>$preview,
                'ssl_id' => $ssl_id,
            );

            if($remapModel->load($remap_data,'') && $remapModel->save()){

            }else{
                $transaction->rollBack();
                $msg = ($this->getModelError($remapModel));
                return $this->error($msg);
                //return $this->error('高级回原保存失败');
            }
        }

        if($data['status'] == 2) {
            $limit = self::packageLimit(['user_id' => $userInfo['uid'], 'package_id' => $data['package_id']]);
            if ($limit['code'] != 200) {
                $transaction->rollBack();
                return $limit;
            }
        }

        $transaction->commit();
        $this->updateNodeFile($omodel,$model);
        return $this->success();

    }

    /**
     * 是否存在高防
     * @param $url
     * @return bool|int|string
     */
    public function urlExisDefence($url)
    {
        if($url)
            return DefenceRemap::find()->where(['preview'=>$url])->count();
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
        $nodeGroup=  NodeGroupNodeid::find()->where(['in','node_group_id',$node_group_arr])->select('node_id')->groupBy('node_id')->asArray()->all();
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





    //根据加速域名，生成cname, 根据回原IP，是否生成host 域名
    public function generateCnames($data,$userInfo){

        $username = $userInfo['username'];
        $dname = $data['dname'];
        $originip = $data['originip'];


        $checkUsername = $this->checkUsername($username);
        if($checkUsername['code']!=200){
            return $checkUsername;
        }
        $registsource = $checkUsername['data']['registsource'];
        $getCnameSuffix = $this->getCnameSuffix($registsource);
        if($getCnameSuffix['code']!=200){
            return $getCnameSuffix;
        }

        $cname_suffix = $getCnameSuffix['data']['suffix'];
        $dname_meg = str_replace('.','',$dname);
        $cname = $dname_meg.'.'.$cname_suffix;

        if(empty($data['step']))
        {
            if(empty($originip)){
                return $this->error('回原地址错误');
            }
            $check_ip = Utils::isIp($originip);
            if(!$check_ip && !Utils::isUrl($originip)){
                return $this->error('回原ip格式错误');
            }
        }

//        //回原地址/ip 可以多个，用逗号隔开
//        $originip_arr = explode(',',$originip);
//        //checkip
//        if(empty($originip_arr)){
//            return $this->error('回原地址错误');
//        }
//        foreach ($originip_arr as $v){
//            $check_ip = Utils::isIp($v);
//            if(!$check_ip){
//                return $this->error('回原ip格式错误');
//            }
//        }
//        $aimport= '';
//        if(count($originip_arr) >1){
//            //多个  //如果有多个回原地址，则必须要有个host
//            $cname_n = $dname_meg.self::$hostSuffix;
//        }else{
//            $cname_n = '';
//            //判断是否有端口
//        }

        $cname_n = "";
        $aimport = '80';
        $data['user_id'] = $checkUsername['data']['user_id'];
        $data['cname'] = $cname;
        $data['cname_n'] = $cname_n;
        $data['aimurl'] = ($cname_n)?$cname_n:$originip;
        $data['aimport'] = $aimport;
        return $this->success($data);

    }


    public function generateBatchCnames($data,$type = '',$userInfo = []){
        $username = $userInfo['username'];
        $originip = $data['originip'];
        $checkUsername = $this->checkUsername($username);
        if($checkUsername['code']!=200){
            return $checkUsername;
        }
        $registsource = $checkUsername['data']['registsource'];
        $getCnameSuffix = $this->getCnameSuffix($registsource);
        if($getCnameSuffix['code']!=200){
            return $getCnameSuffix;
        }

        $cname = '';
        if($type == 'add'){
            $dname = $data['dname'];
            $cname_suffix = $getCnameSuffix['data']['suffix'];
            $dname_meg = str_replace('.','',$dname);
            $cname = $dname_meg.'.'.$cname_suffix;
        }
        $cname_n = "";
        $aimport = '80';
        $data['user_id'] = $checkUsername['data']['user_id'];
        $data['cname_n'] = $cname_n;
        $data['cname'] = $cname ;
        $data['aimurl'] = ($cname_n)?$cname_n:$originip;
        $data['aimport'] = $aimport;
        return $this->success($data);

    }


    //证书检查
    public function checkSsl($username,$domain,$option =''){
        $checkUsername = $this->checkUsername($username);
        if($checkUsername['code']!=200){
            return false;
        }
        //$res = UserCer::find()->where(['user_id'=>$checkUsername['data']['user_id']])->andWhere(['like','domain','%'.$domain.'%'])->one();
        $res = UserCerDomain::find()->where('user_id = :uid AND domain = :domain',[':uid'=>$checkUsername['data']['user_id'],':domain'=>$domain])->one();

        if($option == 'check' && !$res)
        {

          $res = Utils::getUrlHost($domain);
          if(empty($res))
              return false;
           $res =  UserCerDomain::find()->where('user_id = :uid AND domain = :domain',[':uid'=>$checkUsername['data']['user_id'],':domain'=>'*.'.$res])->one();
        }

        if($option == 'dcheck' && !$res)
        {

            $res = Utils::getUrlHost($domain,3);
            if(empty($res))
                return false;
            $res =  UserCerDomain::find()->where('user_id = :uid AND domain = :domain',[':uid'=>$checkUsername['data']['user_id'],':domain'=>'*.'.$res])->one();
        }

        return $res;
    }



    //证书检查
    public function BatchCheckSsl($username,$domain){
        $checkUsername = $this->checkUsername($username);
        if($checkUsername['code']!=200){
            return false;
        }
        $arr=explode("\n",$domain);
        if(empty($arr))
            return $this->error('请填写域名');
        foreach ($arr as $val){
        //$res = UserCer::find()->where(['user_id'=>$checkUsername['data']['user_id']])->andWhere(['like','domain','%'.$domain.'%'])->one();
           $res = UserCerDomain::find()->where('user_id = :uid AND domain = :domain',[':uid'=>$checkUsername['data']['user_id'],':domain'=>$val])->one();
           if(!$res)
            return $this->error('请上传'.$val.'证书');
        }
        return $this->success();
    }

    //检查用户名，返回user_id,registsource 注册来源
    protected function checkUsername($username){
        //通过username,获取user_id,判断是否存在，注册来源是属于哪个区域
        $userInfo = User::find()->where(['username'=>$username])->one();
        if(!$userInfo){
            return $this->error('用户不存在');
        }
        $user_id = $userInfo['id'];
        $registsource = $userInfo['registsource']; //注册来源
        if(!$registsource){
            return $this->error('用户注册来源不存在');
        }
        $data['user_id'] = $user_id;
        $data['registsource'] = $registsource;
        return $this->success($data);
    }

    //通过注册来源，获取域名cname 后缀
    public function getCnameSuffix($registsource){
        $countryType = CountryType::find()->where(['type'=>$registsource])->one();
        if(!$countryType){
            return $this->error('非法注册来源');
        }
        if(!$countryType['cname_suffix']){
            return $this->error('没有找到该注册来源的cname后缀，联系技术');
        }
        $data['suffix'] = $countryType['cname_suffix'];

        return $this->success($data);
    }


    //id=>cname
    public function idToDname(){
        $res = Domain::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','dname');
        return $arr;
    }


    //生成预览remap 格式
    public function generateRemap($data){
        $txt = [];
        $remapData = $data['remapData'];
        $dname = $data['dname'];
        if(!empty($remapData)){
            foreach ($remapData as $v){

                //添加验证(回源地址)
                $pattIP = '/^((([0-9a-zA-Z]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,4})|((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9]))))|\:[0-9]{2,5}$/';
                if(!preg_match($pattIP, $v['aimurl']) && !Utils::isUrl($v['aimurl']) ){
                    continue;
                }

                $originurl = $v['originurl'];
                $is_at = $v['is_at'];
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
                        $txt[] = $content_remap[] = "regex_redirect " . $visit_protocol . $dname . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    else
                        $txt[] = $content_remap[] = "map " . $visit_protocol . $dname . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    continue;
                }

                if($redirect_ssl){
                    //301跳转
                    $txt[] = $content_remap[] = "regex_redirect " . $visit_protocol .$originurl .'.'.$dname . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    continue;
                }

                if($originurl=='(.*)' || $originurl=='[a-z0-9]' || $originurl=='[0-9a-z]' ){
                    $txt[] = $content_remap[] = "regex_map " . $visit_protocol .$originurl .'.'.$dname . $originport . $origin_protocol . $aimurl.$aimport. "\n";
                    continue;
                }

                //
                $txt[] = $content_remap[] = "map " . $visit_protocol .$originurl .'.'.$dname . $originport . $origin_protocol . $aimurl.$aimport. "\n";
            }
        }

        return $txt;
    }


    public function generateOriginRemap($v){
        $dname = $v['dname'];
        //添加验证(回源地址)
        $originurl = $v['originurl'];

        $visit_protocol = $v['visit_protocol'];
        $redirect_ssl = $v['redirect_ssl'];

        if($originurl == '*')
            $originurl = '(.*)';
        if($originurl=='@'){
            //map
            if($redirect_ssl)
                return  "redirect " . $visit_protocol . $dname;
            else
                return "map " . $visit_protocol . $dname;
        }

        if($redirect_ssl){
            //301跳转
            if($originurl=='(.*)') {
                return "regex_redirect " . $visit_protocol . $originurl . '.' . $dname;
            }else{
                    return "redirect " . $visit_protocol . $originurl . '.' . $dname;
            }
        }

        if($originurl=='(.*)' || $originurl=='[a-z0-9]' || $originurl=='[0-9a-z]' ){
           return  "regex_map " . $visit_protocol .$originurl .'.'.$dname;
        }
            return "map " . $visit_protocol . $originurl . '.' . $dname;
    }

    protected function checkParam($data){
        $model = new Domain();
        $data['is_spider'] = implode(',',$data['is_spider']);
        //先检查数据
        if($model->load($data,'') && !$model->validate()){
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return true;
    }

    public function batchUpdateOrigin($data,$userInfo)
    {

        $checkedDomain = $data['checkedDomain'];
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
        $node_group_ids = [];
        foreach ($checkedDomain as $value)
        {
            $model = Remap::find()->where(['id'=>$value])->one();
            if(!$model)
            {
                $transaction->rollBack();
                return $this->error('找不到域名');
            }
            $domain = Domain::find()->where(['user_id'=>$userInfo['uid'],'id'=>$model->did])->one();
            $package_ids [] = $domain->package_id;
            if(!$domain)
            {
                $transaction->rollBack();
                return $this->error('找不到加速');
            }
            if($domain->sys_node_group > 0 && $domain->sys_node_group != $domain->node_group )
                $node_group_ids[]  = $domain->sys_node_group;
            $node_group_ids[] = !empty($domain->node_group) ?  $domain->node_group : "";
            $model->origin_protocol = $origin_protocol;
            $model->aimurl = $aimurl;
            $model->aimport = $aimport;
            if(!$model->save())
            {
                $transaction->rollBack();
                return $this->error('修改失败');
            }
        }
        if($package_ids)//判断套餐是否超过源
        {
            $package_ids =   array_unique($package_ids);

            foreach ($package_ids as $value)
            {
                $data['user_id'] =$userInfo['uid'];
                $data['package_id'] = $value;
               $limit =  self::packageLimit($data);
                if($limit['code'] != 200)
                {
                    $transaction->rollBack();
                    return $this->error('套餐源数量超过限制');
                }
            }
        }
        $transaction->commit();
        $this->batchUpdateOriginNodeFile($node_group_ids);
        return $this->success();
    }

    public function batchUpdateOriginNodeFile($ids)
    {
        $ids = array_unique($ids);
        $ids = array_filter($ids);
           if(!$ids)
               return ;
            $nodeGroup = NodeGroupNodeid::find()->where(['in','node_group_id',$ids])->asArray()->all();
            if($nodeGroup)
            {

                $nodeArr = array();
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
                $nodeArr = array_unique($nodeArr);
                Node::insertNodeUpdate($nodeArr);
            }
    }


    //状态修改
    public function changeStatus($data,$userInfo){
        $ids = $data['id'];
        $status = $data['status'];
        if(!in_array($status,[1,2])|| empty($ids)){
            return $this->error('参数错误');
        }
        //批量修改
        $transaction = \Yii::$app->db->beginTransaction();
        foreach ( $ids as $id)
        {
            $model = Domain::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
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
                    $limit = $this->packageLimit(['user_id' => $model->user_id, 'package_id' => $model->package_id]);
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
// public function changeStatus($data,$userInfo){
//        $ids = $data['id'];
//        $status = $data['status'];
//        if(!in_array($status,[1,2])|| empty($ids)){
//            return $this->error('参数错误');
//        }
//        //批量修改
//        $res = Domain::updateAll(['status'=>$status],['and',['user_id'=>$userInfo['uid']],['in','id',$ids]]);
//        if($res){
//            $this->changeNodeFile($ids);
//            return $this->success();
//        }
//        return $this->error('保存失败');
//    }


    public function changeNodeFile($idArr,$option = 'status')
    {

        if($option == 'status')
            $domain = Domain::find()->where(['in','id',$idArr])->select('node_group,sys_node_group')->asArray()->all();
        else
            $domain = $idArr;
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


}
