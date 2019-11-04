<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/16
 * Time: 10:01
 */

namespace console\models;


use common\lib\Utils;
use common\models\DefenceIp;
use common\models\Node;
use yii\base\Model;
use common\models\DnsServer as CommonDnsServer;
use common\components\Logger;
class DnsServer extends Model
{

    public $logType = 'dnsServer';
    //定义配置变量
    protected $country; //国内1 国外3 高防5  dns-tw=6
    protected $filename;
    protected $country_defence; //对应lc_defence_ip

    public function __construct($country,$area){

        $this->country = $country;
        $this->country_defence = $country;

        //文件名根据所填写的名称来 dns_d_名称.text，因此要确保新增dns服务器的时候，相同country的name要一致

        //$sql = "select name from lc_dns_server where type=$country";
        //$res = $this->db->fetchRow($sql);
        $res = CommonDnsServer::find()->where(['type'=>$country])->one();

        if($res){
            $this->filename = \Yii::getAlias('@dns_file').'/dns/'.$area.'.txt';
        }else{
            $this->filename = \Yii::getAlias('@dns_file').'/dns/'.$area.'.txt';
            //$msg = '未找到 type = '.$this->country.' dns_server 数据';
          //  Logger::factoryLog($msg,$this->logType);
           // Logger::warning($msg);
           // echo  'dns_server error';
        }

    }

    public function execute(){

       $data =  $this->exportData();
       $gfData = $this->exportGf();
        sort($data);
        sort($gfData);
       $list = array_merge($data,$gfData);
       $this->dataFile($list);
    }


    public function exportData(){
        $res_all = CommonDnsServer::find()->where(['switch'=>1])->andWhere(['type'=>$this->country])->orderBy('weight asc')->asArray()->all();
        //$sql_all = "select ip,TTL,dns_name,group_id from lc_dns_server where switch =1  and type =$this->country order by weight";
        //$res_all = $this->db->fetchAll($sql_all);
        //$this->isFile();
        $data = $this->data($res_all);
        return $data;
        $this->dataFile($data);
        echo "dns export completed\n";

    }


    //高防
    public function exportGf(){

        //2015-07-08高防IP导出配置
        //处理可用的节点IP
        //$nodeSql = 'select id,name,ip from lc_node WHERE switch = 1 and forbidden = 0';
        //$nodedata = $this->db->fetchAll($nodeSql);
        $nodedata = Node::find()->select('id,name,ip')->where(['switch'=>1])->andWhere(['forbidden'=>0])->asArray()->all();
        $nodedataSwitch = Node::find()->select('id,name,ip')->where(['switch'=>1])->asArray()->all();
        $nodeArr = array();
        if($nodedata){
            foreach($nodedata as $node){
                $nodeArr[$node['id']] = $node['ip'];
            }
        }
        $nodeArrSwitch = array();
        foreach($nodedataSwitch as $node){
            $nodeArrSwitch[$node['id']] = $node['ip'];
        }


        //国外高防IP
        //$defenceSql = "select cname,ip from lc_defence_ip WHERE country = $this->country_defence";
        //$defenceData = $this->db->fetchAll($defenceSql);
        $defenceData = DefenceIp::find()->where(['country'=>$this->country])->asArray()->all();
        $data = array();
        //print_r($defenceData);die;
        if($defenceData){
            foreach($defenceData as $key=>$dedata){
                $i = 1;
                $temparr = explode('|',$dedata['ip']);
                //遍历节点首选节点 若首先节点全部禁止，则再遍历导出备选节点
                $data_temp = [];
                foreach($temparr as $temp){
                    $temp_id_arr = explode(',',$temp);
                    if(!empty($temp_id_arr)){
                        foreach($temp_id_arr as $v){
                            if($i==2){
                                //备用节点,只判断开关
                                if(@$nodeArrSwitch[$v]){
                                    $data_temp =true;
                                    $data[] = '='.$dedata['cname'].':'.$nodeArrSwitch[$v] .':60'."\n";
                                }
                            }else{
                                if(@$nodeArr[$v]){
                                    $data_temp = true;
                                    $data[] = '='.$dedata['cname'].':'.$nodeArr[$v] .':60'."\n";
                                }
                            }
                        }
                    }
                    if(!empty($data_temp)){
                        break;
                    }
                    $i++;
                }
                unset($temp);
                if(!empty($data)){
                    $data = array_unique($data);
                    //$filename = isFile();
                    //遍历data 每条写入文件

                  //  $this->dataFile($data);
                   // unset($data);
                }

            }

        }

        return $data;
        echo " ,defende Ip export complete";

    }



    //数组写入文件
    public function dataFile($data){
        //排序


        $content_arr = array();
        foreach($data as $v){
            $content_arr[] = $v;
        }
        if(empty($content_arr)) {
            $this->isFile();
            return 0;
        }

//        if(!empty($content_arr))
//            $content_arr[] = "#end";

        $result = Utils::fileIsUpdate($this->filename,$content_arr);

        if($result)
         file_put_contents($this->filename,$content_arr);
    }

    //将数据转成数组并按一定格式拼接
    public function data($res_all){
        $data = array();
        if($res_all){
            foreach($res_all as  $v){
                $text = '';
                $text .=$v['dns_name'].":".$v['ip'].":".$v['TTL']."\n";
                $data[]=$text;
            }
        }
        return $data;
    }

    //文件判断
    public function isFile(){
        $filename = $this->filename;
        if(file_exists($filename)){
            if(!unlink($filename)){
                echo "unlink false";
                die;
            }
        }
    }
}
