<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 12:40
 */

namespace console\controllers;



use backend\models\Node;
use common\lib\FileUtil;
use common\lib\Utils;
use common\models\BlackAreaIp;
use common\models\Iparea;
use common\models\IpdatabaseSimplify;
use common\models\NodeGroup;
use common\models\Package;
use Symfony\Component\Console\Helper\Helper;
use yii\helpers\ArrayHelper;

class BlackAreaIpController extends BaseController
{

    /**
     * 导出地区ip
     */
    public function actionIndex()
    {
       $data = BlackAreaIp::find()->select('package_id,home_id,abroad_id')->asArray()->all();
       $list = [];
       $node_ids = [];
       if($data)
       {
           $nodeModel = new Node();
           $node_ids = $nodeModel->idToZabbixIp();

           foreach ($data as $val)
           {
               $home_arr = unserialize($val['home_id']);
               $abroad_arr = unserialize($val['abroad_id']);
               $ip_base_ids = [];
               $package_id = $val['package_id'];
               $package = Package::find()->where(['id'=>$package_id])->select('group_id,defence_group_id')->asArray()->one();
               if($package)
               {
                   //国内的ip段
                   if($home_arr) {
                       $ip_ids = Iparea::find()->where(['in','province',$home_arr])->select('id')->asArray()->all();
                       if($ip_ids)
                       {
                           $group_ids = [];
                           foreach ($ip_ids as $ip_val)
                               $group_ids[] =  $ip_val['id'];
                           $ip_base =  IpdatabaseSimplify::find()->where(['in','group_id',$group_ids])->select('ipduan')->asArray()->all();
                           if ($ip_base) {
                               foreach ($ip_base as $ip_val) {
                                   $ip_base_ids[] = "add black_country ".str_replace(":","/",$ip_val['ipduan'])."\n";
                               }
                           }
                           unset($ip_ids);
                           unset($ip_val);
                           unset($ip_base);
                       }
                   }

                   //国外ip段
                   if($abroad_arr) {
                       $ip_base = IpdatabaseSimplify::find()->where(['group_id' => $abroad_arr])->select('ipduan')->asArray()->all();
                       if ($ip_base) {
                           foreach ($ip_base as $ip_val) {
                               $ip_base_ids[] =  "add black_country ".str_replace(":","/",$ip_val['ipduan'])."\n";
                           }
                           unset($ip_base);
                           unset($ip_val);
                       }
                       unset($abroad_arr);
                   }

                   //加速节点
                   if($package['group_id'])
                   {
                       $group = NodeGroup::find()->select('node_id')->where(['id'=>$package['group_id']])->asArray()->one();
                       if($group && $group['node_id'])
                       {
                           $ex_node = explode(",",$group['node_id']);
                           if($ex_node)
                           {
                               foreach ($ex_node as $ex_val)
                               {
                                   if($node_ids[$ex_val])
                                   {
                                       if(isset($list[$node_ids[$ex_val]])) {
                                           $list[$node_ids[$ex_val]] = array_merge($ip_base_ids, $list[$node_ids[$ex_val]]);
                                       }else{
                                           $list[$node_ids[$ex_val]] = $ip_base_ids;
                                       }

                                   }
                               }
                           }
                           unset($group);
                       }
                   }

                   //高防节点
                   if($package['defence_group_id'])
                   {
                       $group = NodeGroup::find()->select('node_id')->where(['id'=>$package['defence_group_id']])->asArray()->one();
                       if($group && $group['node_id'])
                       {
                           $ex_node = explode(",",$group['node_id']);
                           if($ex_node)
                           {
                               foreach ($ex_node as $ex_val)
                               {
                                   if($node_ids[$ex_val])
                                   {
                                       if(isset($list[$node_ids[$ex_val]])) {
                                           $list[$node_ids[$ex_val]] = array_merge($ip_base_ids, $list[$node_ids[$ex_val]]);
                                       }else{
                                           $list[$node_ids[$ex_val]] = $ip_base_ids;
                                       }
                                   }
                               }
                           }
                       }
                       unset($group);
                   }
               }
           }
       }

       $has_list = [];
        if($list)
        {
            $dir = \Yii::getAlias('@dns_file').'/node/';
            foreach ($list as $key=>$val)
            {
                $has_list[] = $key;
                $val[] = "#end##";
                $val = array_unique($val);
                $fileDir = $dir.$key;
                $filePath = $fileDir."/black_country.txt";
                FileUtil::createDir($fileDir);
                $this->export($val,$filePath);
                echo $key."\r\n";
            }
            unset($list);
            unset($key);
            unset($val);
         }

        $this->checkNodeBlack($has_list);
    }

    /**
     * 检查节点black_country是否要删除
     */
    public function checkNodeBlack($has_list)
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
                    $filePath = $fileDir."/black_country.txt";
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
