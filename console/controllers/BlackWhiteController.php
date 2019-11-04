<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 12:40
 */

namespace console\controllers;

use common\lib\FileUtil;
use common\lib\Utils;
use common\models\BlackIp;
use common\models\Node;
use common\models\NodeGroup;
use common\models\Package;
use common\models\WhiteIp;
use yii\helpers\ArrayHelper;

class BlackWhiteController extends BaseController
{

    /**
     * 白名单
     */
    public function actionWhite()
    {
        $modelData  = WhiteIp::find()->select('ip,package_id')->asArray()->all();
        $content_arr = [];
        if($modelData)
        {
            $nodeGroup = NodeGroup::find()->select('id,node_id')->asArray()->all();
            $nodeGroupData = ArrayHelper::map($nodeGroup,'id','node_id');

            $node =  Node::find()->select('id,zabbix_ip')->asArray()->all();
            $nodeData = ArrayHelper::map($node,'id','zabbix_ip');

            $pack = Package::find()->select('id,group_id,defence_group_id')->asArray()->all();
            $packData = [];
            foreach ($pack as $packValue)
            {
                $packData[$packValue['id']] =['group_id'=>$packValue['group_id'],'defence_group_id'=>$packValue['defence_group_id']];
            }
            unset($packValue);
            foreach ($modelData as $modelValue)
            {
                $ip = $modelValue['ip'];
                if(empty($ip))
                    continue;

                $package_id = $modelValue['package_id'];
                if(empty($package_id))
                    continue;

                if(!isset($packData[$package_id]))
                    continue;

                $group_id = $packData[$package_id]['group_id'];
                $defence_group_id = $packData[$package_id]['defence_group_id'];
                if(isset($nodeGroupData[$group_id]))
                {
                    $node_ids = explode(',',$nodeGroupData[$group_id]);
                    foreach ($node_ids as $node_id){
                        if(isset($nodeData[$node_id]))
                        {
                            $content_arr[$nodeData[$node_id]][] = "add usr_white ".$ip."\n";
                        }
                    }
                    unset($node_ids);
                    unset($node_id);
                }

                if(isset($nodeGroupData[$defence_group_id]))
                {
                    $node_ids = explode(',',$nodeGroupData[$defence_group_id]);
                    foreach ($node_ids as $node_id){
                        if(isset($nodeData[$node_id]))
                        {
                            $content_arr[$nodeData[$node_id]][] = "add usr_white ".$ip."\n";
                        }
                    }
                    unset($node_ids);
                    unset($node_id);
                }
            }
            unset($nodeGroupData);
            unset($nodeData);
            unset($modelValue);
            unset($packData);
        }
        $has_list = [];
        if($content_arr)
        {

            $dir = \Yii::getAlias('@dns_file').'/node/';
            foreach ($content_arr  as $key=>$val)
            {
                $has_list[] = $key;
                $val[] = "#end##";
                $val = array_unique($val);
                $fileDir = $dir.$key;
                $filePath = $fileDir."/usr_white.txt";
                FileUtil::createDir($fileDir);
                $this->export($val,$filePath);
            }
        }
        $this->checkNode($has_list,'white');
    }

    /**
     * 黑名单
     */
    public function actionBlack()
    {
        $modelData  = BlackIp::find()->select('ip,package_id')->asArray()->all();
        $content_arr = [];
        if($modelData)
        {
            $nodeGroup = NodeGroup::find()->select('id,node_id')->asArray()->all();
            $nodeGroupData = ArrayHelper::map($nodeGroup,'id','node_id');

            $node =  Node::find()->select('id,zabbix_ip')->asArray()->all();
            $nodeData = ArrayHelper::map($node,'id','zabbix_ip');

            $pack = Package::find()->select('id,group_id,defence_group_id')->asArray()->all();
            $packData = [];
            foreach ($pack as $packValue)
            {
                $packData[$packValue['id']] =['group_id'=>$packValue['group_id'],'defence_group_id'=>$packValue['defence_group_id']];
            }
            unset($packValue);

            foreach ($modelData as $modelValue)
            {
                $ip = $modelValue['ip'];
                if(empty($ip))
                    continue;

                $package_id = $modelValue['package_id'];
                if(empty($package_id))
                    continue;

                if(!isset($packData[$package_id]))
                    continue;

                $group_id = $packData[$package_id]['group_id'];
                $defence_group_id = $packData[$package_id]['defence_group_id'];
                if(isset($nodeGroupData[$group_id]))
                {
                    $node_ids = explode(',',$nodeGroupData[$group_id]);
                    foreach ($node_ids as $node_id){
                        if(isset($nodeData[$node_id]))
                        {
                            $content_arr[$nodeData[$node_id]][] = $ip."\n";
                        }
                    }
                    unset($node_ids);
                    unset($node_id);
                }

                if(isset($nodeGroupData[$defence_group_id]))
                {
                    $node_ids = explode(',',$nodeGroupData[$defence_group_id]);
                    foreach ($node_ids as $node_id){
                        if(isset($nodeData[$node_id]))
                        {
                            $content_arr[$nodeData[$node_id]][] = $ip."\n";
                        }
                    }
                    unset($node_ids);
                    unset($node_id);
                }
            }
            unset($nodeGroupData);
            unset($nodeData);
            unset($modelValue);
            unset($packData);
        }
        $has_list = [];
        if($content_arr)
        {
            $dir = \Yii::getAlias('@dns_file').'/node/';
            foreach ($content_arr  as $key=>$val)
            {
                $has_list[] = $key;
                $val = array_unique($val);
                $fileDir = $dir.$key;
                $filePath = $fileDir."/black.txt";
                FileUtil::createDir($fileDir);
                $this->export($val,$filePath);

            }
        }
        $this->checkNode($has_list,'black');
    }

    /**
     * 检查节点是否要删除
     */
    public function checkNode($has_list,$table ='black')
    {
        $nodeList = Node::find()->select('id,zabbix_ip')->asArray()->all();

        if($nodeList) {
            $nodeList =  ArrayHelper::map($nodeList,"zabbix_ip",'zabbix_ip');
            foreach ($has_list as $val)
            {
                if(isset($nodeList[$val]))
                {
                    unset($nodeList[$val]);
                }
            }
            if($nodeList) {
                $dir = \Yii::getAlias('@dns_file') . '/node/';
                foreach ($nodeList as $nval)
                {
                    $content = [];
                    $fileDir = $dir.$nval;
                    //$filePath = $fileDir . "/black_country.txt";
                    if($table == 'black') {
                        $filePath = $fileDir . "/black.txt";
                    }else if($table =='white'){
                          $filePath = $fileDir . "/usr_white.txt";
                    }
                    if(file_exists($filePath))
                    {
                        $content[]="#end##";
                        $this->export($content,$filePath);
                    }
                }
            }

        }
    }

    protected function export($content,$filename){
        $result = Utils::fileIsUpdate($filename,$content);
        if($result)
            file_put_contents($filename,$content);
    }
}
