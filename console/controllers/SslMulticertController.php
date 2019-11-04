<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 12:40
 */

namespace console\controllers;

use backend\models\ApiTrait;
use backend\models\UserCer;
use common\lib\FileUtil;
use common\lib\Utils;
use common\models\DefenceRemap;
use common\models\Node;
use common\models\NodeGroup;
use common\models\Remap;
use common\models\UserCerDomain;


class SslMulticertController extends BaseController
{

   use ApiTrait;

   public $cer_path = '/opt/ats/etc/trafficserver/ssl/';
    public function actionIndex(){
         $path = \Yii::getAlias('@dns_file').'/ssl/';
         $remap = Remap::find()->where(['visit_protocol'=>'https://'])->select('dname,originurl')->asArray()->all();
        $content_remap = [];
        $remap_arr = [];
        if($remap){
            foreach ($remap as $key => $data) {
                if($data['originurl'] == '@') {
                    $remap_arr[] = $data['dname'];
                }else{
                    $remap_arr[] =$data['originurl'].'.'.$data['dname'];
                    $remap_arr[] = '*.'.$data['dname'];
                }
            }
        }
        $defenceRemap = DefenceRemap::find()->where(['visit_protocol'=>'https://'])->select('originurl')->asArray()->all();
        if($defenceRemap){
            foreach ($defenceRemap as $key => $data) {
                $remap_arr[] = $data['originurl'];
                $res = Utils::getUrlHost($data['originurl'],3);
                if($res)
                    $remap_arr[] = '*.'.$res;
            }
        }

        if(!empty($remap_arr))
        {
            $list = UserCerDomain::find()->where(['in','domain',$remap_arr])->andWhere(['>','cer_end_time',time()])->distinct('user_cer_id')->select(' user_cer_id')->groupBy('domain')->asArray()->all();
            if(!empty($list))
            {
                $arr = [];
                foreach ($list as $val)
                {
                    $arr[] = (int)$val['user_cer_id'];
                }
                $ulist = UserCer::find()->select('id,domain')->where(['in','id',$arr])->asArray()->all();
                if(!empty($ulist))
                {
                    foreach ($ulist as $uval){
//                        $ex = explode(',',$uval['domain']);
//                        if(!empty($ex[0]))
//                            $hostname = $ex[0];
//                        else
//                            $hostname = $uval['cer_name'];
                        $hostname = $uval['id'];
                        $content_remap[] = "ssl_hostname=".$hostname." ssl_cert_name=".$this->cer_path.$hostname."_pv.crt ssl_key_name=".$this->cer_path.$hostname."_pb.key\n";
                    }
                    $content_remap[] ='#end##';
                }
            }
        }
        $dir = \Yii::getAlias('@dns_file').'/remap/';
        $filename = $dir.'ssl_multicert.config';
        //导出
        $this->export($content_remap,$filename);
        echo 'success'."\n";
    }

    protected function export($content,$filename){

        $result = Utils::fileIsUpdate($filename,$content);
        if($result)
        file_put_contents($filename,$content);
    }

    public function actionNode()
    {

        $dir = \Yii::getAlias('@dns_file').'/node/';
        $remap = Remap::find()->where(['{{%remap}}.visit_protocol'=>'https://'])->leftJoin('{{%domain}}',"{{%domain}}.id = {{%remap}}.did")->select('{{%remap}}.dname,{{%remap}}.originurl,{{%remap}}.did,{{%domain}}.node_group')->asArray()->all();
        $list = [] ;
        if($remap){
            foreach ($remap as $key => $data) {
                $remap_arr = [];
                if($data['originurl'] == '@') {
                    $remap_arr[] = $data['dname'];
                }else{
                    $remap_arr[] =$data['originurl'].'.'.$data['dname'];
                    $remap_arr[] = '*.'.$data['dname'];
                }

                $cerPath =  self::getUserCerPath($remap_arr);
                if($cerPath ==false)
                    continue ;

                $group = NodeGroup::find()->where(['id'=>$data['node_group']])->one();
                if($group)
                {
                    $nodeIds =  explode(',',$group['node_id']);
                    $nodeData = Node::find()->where(['in','id',$nodeIds])->select('id,zabbix_ip')->asArray()->all();
                    if($nodeData)
                    {
                      foreach ($nodeData as $ndata)
                      {
                          $list[$ndata['zabbix_ip']][] = $cerPath."\n";
                      }
                      unset($nodeData);
                      unset($ndata);
                    }
                }
            }
            unset($remap);
            unset($key);
            unset($data);
        }


        $defenceRemap = DefenceRemap::find()->where(['{{%defence_remap}}.visit_protocol'=>'https://'])->leftJoin('{{%defence}}','{{%defence}}.id = {{%defence_remap}}.did ')->select('{{%defence_remap}}.originurl,{{%defence}}.node_group')->asArray()->all();

        if($defenceRemap){
            foreach ($defenceRemap as $key => $data) {

                $remap_arr = [];
                $remap_arr[] = $data['originurl'];
                $res = Utils::getUrlHost($data['originurl'],3);
                if($res)
                    $remap_arr[] = '*.'.$res;
                $cerPath =  self::getUserCerPath($remap_arr);
                if($cerPath ==false)
                    continue ;

                $group = NodeGroup::find()->where(['id'=>$data['node_group']])->one();
                if($group)
                {
                    $nodeIds =  explode(',',$group['node_id']);

                    $nodeData = Node::find()->where(['in','id',$nodeIds])->select('id,zabbix_ip')->asArray()->all();

                    if($nodeData)
                    {
                        foreach ($nodeData as $ndata)
                        {
                            $list[$ndata['zabbix_ip']][] = $cerPath."\n";
                        }
                        unset($nodeData);
                        unset($ndata);
                    }
                }



            }
        }

        if($list)
        {
            foreach ($list  as $key=>$val)
            {
                $val = array_unique($val);
                $fileDir = $dir.$key;
                $filePath = $fileDir."/ssl_multicert.config";
                FileUtil::createDir($fileDir);
                $val[]='#end##';
                $this->export($val,$filePath);
            }
        }

    }

    public function getUserCerPath($where)
    {
        $path = \Yii::getAlias('@dns_file').'/ssl/';
        $cerDomain = UserCerDomain::find()->where(['in','domain',$where])->andWhere(['>','cer_end_time',time()])->select(' user_cer_id')->asArray()->one();
        if(!$cerDomain)
            return false;
        $userCer = UserCer::find()->select('id,domain')->where(['id'=>$cerDomain['user_cer_id']])->asArray()->one();
        if(!$userCer)
            return false;
//        $ex = explode(',',$userCer['domain']);
//        if(!empty($ex[0]))
//            $hostname = $ex[0];
//        else
//            $hostname = $userCer['cer_name'];
        $hostname = $userCer['id'];
       return "ssl_hostname=".$hostname." ssl_cert_name=".$this->cer_path.$hostname."_pv.crt ssl_key_name=".$this->cer_path.$hostname."_pb.key";
    }

}
