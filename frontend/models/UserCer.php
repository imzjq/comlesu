<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/18
 * Time: 14:44
 */

namespace frontend\models;

use common\lib\FileUtil;
use common\lib\Utils;
use common\models\UserCer as CommonUserCer;
use common\models\UserCerDomain;

class UserCer extends CommonUserCer
{
    use ApiTrait;

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

            $pack = new Package();
            $packages = $pack->idToName($uid);
            /*
          * 数据处理,
           */
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['domain'] = $data->domain;
                $arr['cer_end_time']  = date('Y-m-d H:i:s',$data->cer_end_time);
                $arr['package_name'] = isset($packages[$data->package_id]) ? $packages[$data->package_id] : "";
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

    //add
    public function add($data,$userInfo){

        $model = new UserCer();
        $data['user_id'] = $userInfo['uid'];
        $data['username'] = $userInfo['username'];
        if($data && $model->load($data,'')){
            $model->created_at = time();

            //通过
            $pv_key_info = openssl_x509_parse($model->pv_key);

            if(!$pv_key_info){
                return $this->error('证书内容错误');
            }
            $name = $pv_key_info['extensions']['subjectAltName'];

            if(!isset($pv_key_info['subject']['CN']))
            {
                return $this->error('证书不能识别');
            }
            $name_arr = explode(',',$name);
            $domain ="";
            if(!empty($name_arr)){
                foreach ($name_arr as $ar_k =>$ar_v)
                {
                    $value_arr = explode(':',$ar_v);
                $domain .= $value_arr[1].",";
                }
                $domain = rtrim($domain,",");
            }else{
                return $this->error('域名获取失败');
            }


            //检查 pb pv
            $check_ssl = openssl_x509_check_private_key($model->pv_key,$model->pb_key);
            if(!$check_ssl){
                return $this->error('公钥跟私钥不对应');
            }
            $transaction = \Yii::$app->db->beginTransaction();
            $model->cer_start_time = $pv_key_info['validFrom_time_t'];
            $model->cer_end_time = $pv_key_info['validTo_time_t'];
            $dir = \Yii::getAlias('@dns_file').'/ssl/';

            $model->domain = $domain;
            if ($model->save()) {
                $filename_pb = $model->id.'_pb.key';
                $filename_pv = $model->id.'_pv.crt';
                file_put_contents($dir.$filename_pb,$model->pb_key);
                file_put_contents($dir.$filename_pv,$model->pv_key);
                $this->UserCerDomain($model,'add');

                $limit  =  self::packageLimit($model);
                if($limit['code'] != 200)
                {
                    $transaction->rollBack();
                    return $limit;
                }

                $transaction->commit();
                return $this->success();
            }
            $transaction->rollBack();
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        $msg = ($this->getModelError($model));
        return $this->error($msg);
    }

    /**
     * 添加证书域名到中间表
     * @param $model
     * @param $domains
     */
    public function UserCerDomain($model,$type = 'add')
    {
        if($type == 'update')
            UserCerDomain::deleteAll(['user_cer_id'=>$model->id]);
        $domains = explode(',',$model->domain);
        if($domains)
        {
            foreach ($domains as $val)
            {
                $userCerDomain = new UserCerDomain() ;
                $userCerDomain->user_id = $model->user_id ;
                $userCerDomain->username = $model->username ;
                $userCerDomain->domain = $val ;
                $userCerDomain->cer_end_time =$model->cer_end_time ;
                $userCerDomain->user_cer_id = $model->id;
                $userCerDomain->save();
            }

        }

    }


    public function packageLimit($model)
    {

        $user_id = $model->user_id;
        $package_id = $model->package_id;
        $res = Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');

        $userCer = UserCer::find()->where(['user_id'=>$user_id,'package_id'=>$package_id])->select('id')->asArray()->all();
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
        $count =  count($url_count); //使用源的数量

        if($count>$res['ssl_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['ssl_quantity'].'个顶级域名证书');
        }
        return $this->success();
    }

    //修改
    public function updateInfo($data,$userInfo){
        $id = isset($data['id'])?$data['id']:'';
        $model = UserCer::find()->where(['user_id'=>$userInfo['uid'],'id'=>$id])->one();
        $oldData = $model->getOldAttributes();
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);
        if($model->load($data,'')){

            $pv_key_info = openssl_x509_parse($model->pv_key);
            if(!$pv_key_info){
                return $this->error('证书内容错误');
            }
            if(!isset($pv_key_info['subject']['CN']))
            {
                return $this->error('证书不能识别');
            }
            $name = $pv_key_info['extensions']['subjectAltName'];
            $name_arr = explode(',',$name);
            $domain ="";
            if(!empty($name_arr)){
                foreach ($name_arr as $ar_k =>$ar_v)
                {
                    $value_arr = explode(':',$ar_v);
                    $domain .= $value_arr[1].",";
                }
                $domain = rtrim($domain,",");
            }else{
                return $this->error('域名获取失败');
            }
            //检查 pb pv
            $check_ssl = openssl_x509_check_private_key($model->pv_key,$model->pb_key);
            if(!$check_ssl){
                return $this->error('公钥跟私钥不对应');
            }
            $model->domain = $domain;
            $model->cer_start_time = $pv_key_info['validFrom_time_t'];
            $model->cer_end_time = $pv_key_info['validTo_time_t'];
            $transaction = \Yii::$app->db->beginTransaction();
            if( $model->save()){
                $this->updateFile($oldData,$model);
                $this->UserCerDomain($model,'update');

                $limit  =  self::packageLimit($model);
                if($limit['code'] != 200)
                {
                    $transaction->rollBack();
                    return $limit;
                }

                $transaction->commit();
                return $this->success();
            }
            $transaction->rollBack();
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function del($id,$userInfo){
        $model = UserCer::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
         if(!$model)
         return $this->error('未找到相应数据');

         $model->delete();
         $dir = \Yii::getAlias('@dns_file').'/ssl/';
         FileUtil::rmFile($dir.$model->id.'_pb.key');
         FileUtil::rmFile($dir.$model->id.'_pv.crt');
         UserCerDomain::deleteAll(['user_cer_id'=>$id]);
        return  $this->success();
    }

    public function dels($data,$userInfo){
        $ids= $data['id'];
        $dir = \Yii::getAlias('@dns_file').'/ssl/';
        if(!$ids)
            return $this->error('参数错误');

        $count = UserCer::find()->where(['and',['user_id'=>$userInfo['uid']],['in','id',$ids]])->count();
        if($count != count($ids))
            return $this->error('操作错误');

        foreach ($ids as $val)
        {
            $model = UserCer::findOne($val);
            if(!empty($model))
            {
                    $model->delete();
                    UserCerDomain::deleteAll(['user_cer_id'=>$val]);
                    FileUtil::rmFile($dir.$model->id.'_pb.key');
                    FileUtil::rmFile($dir.$model->id.'_pv.crt');
            }
        }
        return  $this->success();
    }

    public function getOne($id,$userInfo){
        $model = UserCer::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['package_id'] = (int)$model['package_id'];
        return $this->success($model);
    }

    public function updateFile($oldData,$newData)
    {
        $dir = \Yii::getAlias('@dns_file').'/ssl/';
        $suff_pb = '_pb.key';
        $suff_pv = '_pv.crt';
        $filename_pb = $newData->id.$suff_pb;
        $filename_pv = $newData->id.$suff_pv;

            if($oldData['pb_key'] == $newData->pb_key && file_exists($dir.$filename_pb)){
                //rename($dir.$filename_pb,$dir.$filename_pb);
            }
            else{
                //FileUtil::rmFile($dir.$filename_pb);
               // FileUtil::createFile($dir,$filename_pb,$newData->pb_key);
                file_put_contents($dir.$filename_pb,$newData->pb_key);
            }
            if($oldData['pv_key'] != $newData->pv_key && file_exists($dir.$filename_pv))
            {
              //  rename($dir.$filename_pv,$dir.$filename_pv);
            }else{
//                FileUtil::rmFile($dir.$filename_pv);
//                FileUtil::createFile($dir,$filename_pv,$newData->pv_key);
                file_put_contents($dir.$filename_pv,$newData->pv_key);
            }
    }


    public function batchAdd($data,$userInfo)
    {
       $sslPbData =  $data['sslPbData'];
       $sslPvData =  $data['sslPvData'];
       $package_id = $data['package_id'];
       $dir = \Yii::getAlias('@dns_file').'/ssl/';

       if(empty($package_id))
           return $this->error('请选择套餐');

       if(empty($sslPbData) || empty($sslPvData))
           return $this->error('请上传文件');

//       if(count($sslPbData) != count($sslPvData))
//         return $this->error('证书数量不对等');


        $arrPb = $this->getSortData($sslPbData);
        $arrPv = $this->getSortData($sslPvData);

        $transaction = \Yii::$app->db->beginTransaction();
        $result = ['success'=>0,'errer'=>[],'valid'=>[],'match'=>[],'exist'=>[]];
        foreach ($arrPb as $key=>$pbData)
        {


            if(!isset($arrPv[$key]))
            {
                $result['match'][] = $pbData['filename'];
                unset($arrPb[$key]);
                continue;
            }

            $model = new UserCer();
            $model->user_id = $userInfo['uid'];
            $model->username = $userInfo['username'];
            $model->pv_key = $arrPv[$key]['content'];
            $model->pb_key = $pbData['content'];
            $model->package_id = $package_id;
            $model->created_at = time();

            //检查 pb pv
            $check_ssl = openssl_x509_check_private_key($model->pv_key,$model->pb_key);
            if(!$check_ssl){
                //return $this->error('公钥跟私钥不对应');
                $result['errer'][] =  $pbData['filename'];
                $result['errer'][] =  $arrPv[$key]['filename'];
                unset($arrPb[$key]);
                unset($arrPv[$key]);
                continue;
            }
            $pv_key_info = openssl_x509_parse($model->pv_key);
            if(!$pv_key_info){
                $result['errer'][] =  $pbData['filename'];
                $result['errer'][] =  $arrPv[$key]['filename'];
                unset($arrPb[$key]);
                unset($arrPv[$key]);
                //return $this->error('证书内容错误');
                continue;
            }
            $name = $pv_key_info['extensions']['subjectAltName'];
            if(!isset($pv_key_info['subject']['CN']))
            {
                $result['valid'][] =  $pbData['filename'];
                $result['valid'][] =  $arrPv[$key]['filename'];
                unset($arrPb[$key]);
                unset($arrPv[$key]);
                //return $this->error('证书不能识别');
                continue;
            }
            $name_arr = explode(',',$name);
            $domain ="";
            if(!empty($name_arr)){
                foreach ($name_arr as $ar_k =>$ar_v)
                {
                    $value_arr = explode(':',$ar_v);
                    $domain .= $value_arr[1].",";
                }
                $domain = rtrim($domain,",");
            }else{
                $result['errer'][] =  $pbData['filename'];
                $result['errer'][] =  $arrPv[$key]['filename'];
                unset($arrPb[$key]);
                unset($arrPv[$key]);
                continue;
                //return $this->error('域名获取失败');
            }
            $model->cer_start_time = $pv_key_info['validFrom_time_t'];
            $model->cer_end_time = $pv_key_info['validTo_time_t'];
            $model->domain = $domain;

            $model->domain = $domain;
            if ($model->save()) {
                $filename_pb = $model->id.'_pb.key';
                $filename_pv = $model->id.'_pv.crt';
                file_put_contents($dir.$filename_pb,$model->pb_key);
                file_put_contents($dir.$filename_pv,$model->pv_key);
                $this->UserCerDomain($model,'add');
                unset($arrPb[$key]);
                unset($arrPv[$key]);
                $result['success'] +=1 ;
                //return $this->success();
            }else {
              //  $transaction->rollBack();
                $result['exist'][] =  $pbData['filename'];
                $result['exist'][] =  $arrPv[$key]['filename'];
                unset($arrPb[$key]);
                unset($arrPv[$key]);
                continue;
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
        }

        $model = new UserCer();
        $model->user_id =  $userInfo['uid'];
        $model->package_id = $package_id;
        $limit  =  self::packageLimit($model);
        if($limit['code'] != 200)
        {
            $transaction->rollBack();
            return $limit;
        }
        if(!empty($arrPb)) {
          foreach ($arrPb as $pval)
              $result['match'][] =  $pval['filename'];
        }
        if(!empty($arrPv)) {
            foreach ($arrPv as $pval)
                $result['match'][] =  $pval['filename'];
        }
        $transaction->commit();
        return $this->success($result);
    }

    public function getSortData($data)
    {
        $result = array();
        foreach($data as $key=>$val){
            $name = $val['filename'];
            $arr = explode('.',$name);
            array_pop($arr);
            $filename = implode(".",$arr);
            $result[$filename]['content']= $val['content'];
            $result[$filename]['filename']= $val['filename'];
        }
        return $result;
    }

}
