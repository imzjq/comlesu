<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/3
 * Time: 20:56
 */

namespace console\controllers;

use backend\models\CountryType;
use common\models\Defence;
use common\models\DefenceRemap;
use common\models\Domain;
use common\models\Drsd;
use common\models\Node;
use common\models\NodeGroupNodeid;
use common\models\NodeRemark;
use common\models\Remap;
use yii\console\Controller;
use backend\models\NodeGroup;
use yii\helpers\ArrayHelper;

class TestController extends Controller
{

    public function actionDrsd(){
        $db_official = \Yii::$app->dbFlow;
        $res = $db_official->createCommand('SELECT * FROM lc_drsd')->queryAll();
        if($res){
            foreach ($res as $v){
                $model = new Drsd();
                $model->id = $v['id'];
                $model->username = $v['username'];
                $model->dname = $v['dname'];
                $model->remarks = $v['remarks'];
                $model->intime = $v['intime'];
                $model->icp = $v['icp'];
                $model->icpcode = $v['icpcode'];
                $model->status = $v['status'];
                $model->high_anti = $v['high_anti'];
                $model->white_switch = $v['white_switch'];
                $model->cate_id = $v['cate_id'];
                $model->save();
            }
        }
        echo 'success';
    }

    //节点分组迁移
    public function actionNodeGroup(){
        $db_official = \Yii::$app->dbFlow;
        $node_group = $db_official->createCommand('SELECT * FROM lc_node_group')->queryAll();
        if($node_group){
            foreach ($node_group as $v){
                $model = new NodeGroup();
                $model->id = $v['group_name'];
                $rmks = $v['remarks'];
                if(!$rmks){
                    $rmks = 'system'.$v['group_name'];
                }
                $model->group_name = $rmks;
                $model->node_id = $v['node_id'];
                $model->isDefault = $v['isDefault'];
                $model->remark = $v['remark'];
                $model->type = $v['type'];
                $model->save();
            }
        }
        echo "ok\n";
    }

    /**
     * 导入分组节点id
     * @throws \yii\db\Exception
     */
    public function actionNodeGroupNodeid()
    {
        $list = NodeGroup::find()->all();
        if($list)
        {
            $dataIns = [];
            foreach ($list as $key=>$val)
            {
                if($val->node_id)
                {
                    $data = explode(',',$val->node_id);
                    foreach ($data as $vals)
                    {
                        $dataIns[] =  ['node_id'=>$vals,'node_group_id'=>$val->id];

                    }
                }
            }
            \yii::$app->db->createCommand()->batchInsert(NodeGroupNodeid::tableName(),['node_id','node_group_id'], $dataIns)->execute();
        }
    }

    /**
     * 修改节点分组的备注
     */
    public function actionBatchUpdateRemark()
    {
        $list = NodeGroup::find()->all();
        $type = new CountryType();
        $type = $type->idToType();
        if($list)
        {
            foreach ($list as $key => $val)
            {

                $model = NodeGroup::findOne($val->id);
                $model->remark = $type[$val->type];
                $model->save();

            }
        }
    }

    /**
     * 拷贝高防
     */
    public function actionDefence()
    {
        $db = \Yii::$app->dbFlow;
       $user =  $db->createCommand('SELECT id,username FROM lc_user')->queryAll();
       $user = ArrayHelper::map($user,'id','username');
       $sql  = 'select * from lc_defence';
       $defence =  $db->createCommand($sql)->queryAll();
       if($defence)
       {
           $dataIns = [];
           $dataIns2 = [];
           foreach ($defence as $key=>$val)
           {
               $modelDefen = new Defence();
               $modelDefen->user_id = $val['userid'];
               $modelDefen->username = $user[$val['userid']];
               $domains = explode(',',$val['domains']);
               $modelDefen->dname = $domains[0];
               $modelDefen->cname = $val['cname'];
               $modelDefen->nodeids = $val['nodeids'];
               $nodeids = explode(',',$val['nodeids']);
               $modelDefen->originip = $val['aimurl'];
               $modelDefen->status = $val['status']== 0 ? 1 : $val['status'];
               $modelDefen->enable = 1 ;
               $modelDefen->create_time = $val['createdate'];
               $modelDefen->high_anti = $val['defence_ip_id'];
               $modelDefen->port = 80 ;
               $modelDefen->is_https = 0 ;
               $modelDefen->save();
               $i = 0 ;
              foreach ($domains as $do)
              {
                  if($i==0)
                      $at = 1;
                  $dataIns[] = ['did'=>$modelDefen->id,'dname'=>$modelDefen->dname,'originurl'=>$do,'originport'=>80,'aimurl'=>$modelDefen->originip,'aimport'=>80,'visit_protocol'=>'http://','origin_protocol'=>80,'redirect_ssl'=>0,'is_at'=>$at,'ssl_id'=>0,'preview'=>'map http://'.$do];
                  $i++;
              }
              foreach ($nodeids as $no)
              {
                  $dataIns2[] = ['defence'=>$modelDefen->id,'node_id'=>$no];
              }

           }
           \yii::$app->db->createCommand()->batchInsert(DefenceRemap::tableName(),['did','dname','originurl','originport','aimurl','aimport','visit_protocol','origin_protocol','redirect_ssl','is_at','ssl_id','preview'], $dataIns)->execute();

       }
    }


    /**
     * 加速域名
     */
    public function actionDomain()
    {
        $dataIns = [];
        $dataIns2 = [];
        $domain = Domain::find()->all();
        foreach ($domain as $value)
        {
            $aimport = "80";
            $originip = explode(':',$value->originip);
            if(count($originip)>1)
            {
                $aimport = $originip[1] ;
            }

            $dataIns[] =['did'=>$value->id,'dname'=>$value->dname,'originurl'=>'@','originport'=>'80','aimurl'=>$originip[0],'aimport'=>$aimport,'visit_protocol'=>'http://','origin_protocol'=>'http://','is_at'=>1,'redirect_ssl'=>0,'preview'=>'map http://'.$value->dname,'ssl_id'=>0];
        }
        unset($value);
        \yii::$app->db->createCommand()->batchInsert(Remap::tableName(),['did','dname','originurl','originport','aimurl','aimport','visit_protocol','origin_protocol','is_at','redirect_ssl','preview','ssl_id'], $dataIns)->execute();

        $db = \Yii::$app->dbFlow;
        $remap =  $db->createCommand('SELECT * FROM lc_remap')->queryAll();
        foreach ($remap as $value)
        {
            $dataIns2[] =['did'=>$value['did'],'dname'=>$value['dname'],'originurl'=>$value['originurl'],'originport'=>'80','aimurl'=>$value['aimurl'],'aimport'=>$value['aimport'],'visit_protocol'=>'http://','origin_protocol'=>'http://','is_at'=>0,'redirect_ssl'=>0,'preview'=>'map http://'.$value['originurl'].'.'.$value['dname'],'ssl_id'=>0];
        }
        unset($value);
        \yii::$app->db->createCommand()->batchInsert(Remap::tableName(),['did','dname','originurl','originport','aimurl','aimport','visit_protocol','origin_protocol','is_at','redirect_ssl','preview','ssl_id'], $dataIns2)->execute();
    }


    public function actionNodeFile()
    {
        $list = Node::find()->select('id')->asArray()->all();
        $arr = [];
        foreach ($list as $value)
        {
            $model = new \backend\models\Node();
            $model->updateKit($value['id']);
            $arr[]  = $value['id'];
        }
        $model = new \backend\models\Node();
        $model->exportNode($arr);
    }


    public function actionTest()
    {
        $table_name = 'lc_flow_'.date('Y_m_d');
        $sql  = "CREATE TABLE `{$table_name}` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site` varchar(100) NOT NULL COMMENT '域名',
  `flow` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '流量(MB)',
  `hit` int(10) NOT NULL DEFAULT '0' COMMENT '点击数',
  `intime` int(10) NOT NULL DEFAULT '0' COMMENT '录入时间',
  `country` tinyint(1) NOT NULL DEFAULT '1',
  `nodeid` int(10) NOT NULL,
  `did` int(10) NOT NULL,
  `type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1: 加速表 2:高防表',
  PRIMARY KEY (`id`,`nodeid`,`type`),
  KEY `did` (`did`) USING BTREE,
  KEY `intime` (`intime`) USING BTREE,
  KEY `country` (`country`) USING BTREE,
  KEY `site` (`site`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT
PARTITION BY LIST(type) (
    PARTITION stype_1 VALUES IN (1),
    PARTITION stype_2 VALUES IN (2)
);
";
        \Yii::$app->db->createCommand($sql)->execute();

        $insertSql = "INSERT INTO lc_flow_table (`name`,`ddate`,`create_time`) values('{$table_name}','".date('Y-m-d')."',".time().")";

        \Yii::$app->db->createCommand($insertSql)->execute();
      //  $sql ='alter table lc_flow rename AS lc_flow_'.date('Y_m_d');
       //\Yii::$app->db->createCommand($sql)->execute();
       // $sql ='alter table lc_flow_bak rename AS lc_flow';
        //\Yii::$app->db->createCommand($sql)->execute();

    }


    public function actionRemark()
    {
        $list = Node::find()->select('id,ip,zabbix_ip')->all();
        foreach ($list as $val)
        {
            $model = Node::findOne($val->id);
            $model->zabbix_ip = $model->ip;
            $model->save();
            $remark = new NodeRemark();
            $remark->node_id = $val->id;
            $remark->save();
        }
    }

}
