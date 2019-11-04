<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/19
 * Time: 19:45
 */
namespace console\controllers;
use common\lib\Utils;
use common\models\Defence;
use common\models\Domain;
use common\models\Hsts;
use common\models\Node;
use common\models\NodeGroupNodeid;


class HstsController extends BaseController
{

    /**
     *
     */
    public function actionExport()
    {
        $hsts = Hsts::find()->select('url,user_id')->asArray()->all();
        if(!$hsts)
          return ;

        $node_data = [];//节点数组
        $domain = Domain::find()->where(['status'=>2])->select('dname,node_group,high_anti,sys_node_group,sys_high_anti')->all();
        $arr = [];
        if($domain)
        {
            foreach ($domain as $d_value)
            {
                $dname = Utils::getUrlHost($d_value['dname']);
                $arr[$dname][] = ['node_group'=>$d_value['node_group'],'sys_node_group'=>$d_value['sys_node_group'],'high_anti'=>$d_value['high_anti'],'sys_high_anti'=>$d_value['sys_high_anti']];
            }
            unset($domain);
            unset($d_value);
        }

        $defence =  Defence::find()->alias("d")->where(['status'=>2])->innerJoin("{{%defence_remap}} as r",'r.did = d.id')->select('r.originurl as domain,d.node_group,d.high_anti,d.sys_node_group,d.sys_high_anti')->asArray()->all();
        if($defence)
        {
            foreach ($defence as $d_value)
            {
                $dname = Utils::getUrlHost($d_value['domain']);
                $arr[$dname][] = ['node_group'=>$d_value['node_group'],'sys_node_group'=>$d_value['sys_node_group'],'high_anti'=>$d_value['high_anti'],'sys_high_anti'=>$d_value['sys_high_anti']];
            }
            unset($defence);
            unset($d_value);
        }
        $nodeModel = new \backend\models\Node();
        $node =  $nodeModel->idToZabbixIp();
        foreach ($hsts as $value)
        {
            if(isset($arr[$value['url']]))
            {
               // var_dump($arr[$value['url']]);
                foreach ($arr[$value['url']] as $a_val)
                {
                    $group_ids = [];
                    if(!empty($a_val['node_group']))
                        $group_ids[] =$a_val['node_group'];
                    if(!empty($a_val['sys_node_group']))
                        $group_ids[] =$a_val['sys_node_group'];
                    if(!empty($a_val['high_anti']))
                        $group_ids[] =$a_val['high_anti'];
                    if(!empty($a_val['sys_high_anti']))
                        $group_ids[] =$a_val['sys_high_anti'];
                    $node_ids = NodeGroupNodeid::find()->where(['in','node_group_id',$group_ids])->select('node_id')->groupBy('node_id')->asArray()->all();
                    if($node_ids)
                    {
                        foreach ($node_ids as $f_node)
                        {
                           if(isset($node[$f_node['node_id']]))
                           {
                               $node_data[$node[$f_node['node_id']]][] = $value['url'];
                           }
                        }
                    }
                }
            }
        }
        unset($node);
        $path = \Yii::getAlias('@dns_file').'/node/';
        if($node_data)
        {
            foreach ( $node_data as $n_key => $n_val )
            {
                $content_remap = [];//文本
                foreach ($n_val as $s_val)
                    $content_remap = $this->rulesContent($s_val,$content_remap);
                $file =$path.$n_key."/rules_usr.conf";
                $result = Utils::fileIsUpdate($file,$content_remap);
                if($content_remap)
                    $content_remap []= "#end##";
                if($result)
                    file_put_contents($file,$content_remap);
            }
            unset($node_data);
            unset($n_key);
            unset($n_val);
        }
    }


    public function rulesContent($url,$content_remap)
    {
        $url = Utils::getUrlHost($url) ;
        $content_remap [] = "cond %{CLIENT-HEADER:Host}  /{$url}/\nset-header Strict-Transport-Security  \"max-age=10886400\"\ncond %{CLIENT-HEADER:Host}  /(.*).{$url}/\nset-header Strict-Transport-Security  \"max-age=10886400\"\n\n";
        return $content_remap;
    }

}
