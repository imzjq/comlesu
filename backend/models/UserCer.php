<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/18
 * Time: 14:44
 */

namespace backend\models;

use common\lib\FileUtil;
use common\models\UserCer as CommonUserCer;
use common\models\UserCerDomain;

class UserCer extends CommonUserCer
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

            /*
          * 数据处理,
           */
            $pack = new Package();
            $packages = $pack->idToName();
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['username']  = $data->username;
                $arr['domain'] = $data->domain;
                $arr['created_at']  = date('Y-m-d H:i:s',$data->created_at);
                $arr['cer_end_time']  = date('Y-m-d H:i:s',$data->cer_end_time);
                $arr['del'] = $data->cer_end_time > time() ? 0 : 1;
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
    public function add($data){

        $model = new UserCer();
        if($data && $model->load($data,'')){
            $model->created_at = time();
            $username = $data['username'];
            $userModel = new User();
            $user_id = $userModel->getUserIdByUsername($username);
            if(!$user_id){
                return $this->error('未找到相应用户');
            }
            $model->user_id = $user_id;
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

                $this->UserCerDomain($model,'add');

                $fron_user = new \frontend\models\UserCer();
                $limit  =  $fron_user->packageLimit($model);
                if($limit['code'] != 200)
                {
                    $transaction->rollBack();
                    return $limit;
                }
                $filename_pb = $model->id.'_pb.key';
                $filename_pv = $model->id.'_pv.crt';
                file_put_contents($dir.$filename_pb,$model->pb_key);
                file_put_contents($dir.$filename_pv,$model->pv_key);
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

    //修改
    public function updateInfo($data){
        $id = isset($data['id'])?$data['id']:'';
        $model = UserCer::findOne($id);
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

                $this->UserCerDomain($model,'update');

                $fron_user = new \frontend\models\UserCer();
                $limit  =  $fron_user->packageLimit($model);
                if($limit['code'] != 200)
                {
                    $transaction->rollBack();
                    return $limit;
                }
                $this->updateFile($oldData,$model);
                $transaction->commit();
                return $this->success();
            }
            $transaction->rollBack();
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function del($id){
        $model = UserCer::findOne($id);
         if(!$model)
         return $this->error('未找到相应数据');
         if($model->cer_end_time > time())
             return $this->error('未过期不能删除');
         $model->delete();
         $dir = \Yii::getAlias('@dns_file').'/ssl/';
         FileUtil::rmFile($dir.$model->id.'_pb.key');
         FileUtil::rmFile($dir.$model->id.'_pv.crt');
         UserCerDomain::deleteAll(['user_cer_id'=>$id]);
        return  $this->success();
    }

    public function dels($data){
        $ids= $data['id'];
        $dir = \Yii::getAlias('@dns_file').'/ssl/';
        if(!$ids)
            return $this->error('参数错误');
        foreach ($ids as $val)
        {
            $model = UserCer::findOne($val);
            if(!empty($model))
            {
                if($model->cer_end_time < time())
                {
                    $model->delete();
                    UserCerDomain::deleteAll(['user_cer_id'=>$val]);
                    FileUtil::rmFile($dir.$model->id.'_pb.key');
                    FileUtil::rmFile($dir.$model->id.'_pv.crt');
                }
            }
        }
        return  $this->success();
    }

    public function getOne($id){
        $model = UserCer::find()->where(['id'=>$id])->asArray()->one();
        $model['package_id'] = (int)$model['package_id'];
        if(!$model){
            return $this->error('未找到相应数据');
        }
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

}
