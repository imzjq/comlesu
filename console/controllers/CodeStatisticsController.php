<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/12
 * Time: 22:32
 */
//流量导入
namespace console\controllers;
use common\models\CodeNum;
use Yii;
class CodeStatisticsController extends BaseController
{
    public $conn;
    public $unit;
    public function  init(){
        $this->unit = 300 ;
        $this->conn = Yii::$app->db;
    }


    /**
     * domain
     *状态码统计
     * 10分钟一次
     */
    public function actionDomain()
    {
        date_default_timezone_set('Asia/Shanghai');
        $mtime= strtotime(date("Y-m-d H:00:00", strtotime("-1 hour")));
        $data = [];
        $dateFrom =$mtime;
        $dateTo = $dateFrom + 3600;
        for ($dateFrom; $dateFrom < $dateTo; $dateFrom += $this->unit){
            $date = $dateFrom;
            //$sql_dnames = "select id,dname from lc_domain where status=2 and enable =1 order by id desc";
            $sql_dnames = "select id,dname from lc_domain order by id desc";
            $domain_info =  $this->conn->createCommand($sql_dnames)->queryAll();
            foreach($domain_info as $v){
                $dname = $v['dname'];
                $did = $v['id'];
                //判断高级回源格式
                $sql_remap = "select originurl from lc_remap where did = $did";
                $res_remap = $this->conn->createCommand($sql_remap)->queryall();
                if(!empty($res_remap)) {
                    $arr_remap = array();
                    foreach ($res_remap as $v) {
                        $arr_remap[] = $v['originurl'];
                    }
                    if(in_array('[0-9a-z]',$arr_remap) || in_array('(.*)',$arr_remap) ||in_array('[a-z0-9]',$arr_remap)){
                        //泛解析
                        $sql_v = "SELECT id from lc_sar_sites where site like '%$dname'";
                        $site_ids = $this->conn->createCommand($sql_v)->queryall();
                        if(empty($site_ids)){
                            continue;
                        }else{
                            $siteIds = '';
                            foreach($site_ids as $k=>$site_id){
                                $siteIds .= "'" . $site_id['id'] . "',";
                            }
                            unset($tmpSites);
                            unset($k);;
                            unset($tmpSite);
                            $siteIds = rtrim($siteIds, ',');
                            //一个site,多个id作为参数,调用_total_flow_dname方法
                           $data =  $this->getTraffic($did, $siteIds,$date,1,$data);
                        }
                    }else{
                        //完全匹配
                        $sites_str = '';
                        foreach($arr_remap as $v){
                            if($v != '@')
                                $sites_str .="'".$v.'.'.$dname."',";
                        }
                        $sites_str .="'".$dname."',";
                        $sites_str = rtrim($sites_str,',');
                        $sql_v = "SELECT id from lc_sar_sites where site in ($sites_str)";

                        $site_ids = $this->conn->createCommand($sql_v)->queryall();
                        if(empty($site_ids)){
                            continue;
                        }else{
                            $siteIds = '';
                            foreach($site_ids as $k=>$site_id){
                                $siteIds .= "'" . $site_id['id'] . "',";
                            }
                            unset($tmpSites);
                            unset($k);;
                            unset($tmpSite);
                            $siteIds = rtrim($siteIds, ',');
                            //一个site,多个id作为参数,调用_total_flow_dname方法
                            $data =  $this->getTraffic($did, $siteIds,$date,1,$data);
                        }
                    }
                }
            }
        }
        if($data)
        {
            \Yii::$app->db->createCommand()->batchInsert(CodeNum::tableName(), ['did', 'date','code','num','type','intime'], $data)->execute();
        }
    }

    public function actionDefence()
    {
        date_default_timezone_set('Asia/Shanghai');
        $mtime= strtotime(date("Y-m-d H:00:00", strtotime("-1 hour")));
        $data = [];
        $dateFrom =$mtime;
        $dateTo = $dateFrom + 3600;
        for ($dateFrom; $dateFrom < $dateTo; $dateFrom += $this->unit) {
            $date = $dateFrom;
            $sql_defence = "select id from lc_defence  order by id desc";
            $defence_info = $this->conn->createCommand($sql_defence)->queryAll();
            if ($defence_info) {
                //有高防
                foreach ($defence_info as $v) {
                    $did = $v['id'];
                    $sql_defence_remap = "select originurl from lc_defence_remap where did = $did";
                    $remap_info = $this->conn->createCommand($sql_defence_remap)->queryAll();
                    if (empty($remap_info))
                        continue;
                    $dname_str = '';
                    foreach ($remap_info as $d_v) {
                        $dname_str .= "'" . $d_v['originurl'] . "',";
                    }
                    $dname_str = rtrim($dname_str, ',');
                    //完全匹配
                    $sql_v = "SELECT id from lc_sar_sites where site in ($dname_str)";
                    $site_ids = $this->conn->createCommand($sql_v)->queryall();
                    if (empty($site_ids)) {
                        continue;
                    } else {
                        $siteIds = '';
                        foreach ($site_ids as $k => $site_id) {
                            $siteIds .= "'" . $site_id['id'] . "',";
                        }
                        $siteIds = rtrim($siteIds, ',');
                        //一个site,多个id作为参数,调用_total_flow_dname方法
                        $data =  $this->getTraffic($did, $siteIds,$date,2,$data);
                    }
                }
            }
        }
        if($data)
        {
            \Yii::$app->db->createCommand()->batchInsert(CodeNum::tableName(), ['did', 'date','code','num','type','intime'], $data)->execute();
        }
    }

    function getTraffic($did,$siteIds,$date,$type = 1,$data){
        $dateTo =$date + $this->unit;
        if(!empty($siteIds)){
            $sql = "select resultCode,count(id) as num from lc_sar_traffic where `intime` >= '$date' and `intime` < '$dateTo' and sitesID in ($siteIds) GROUP  by resultCode";
            $res = $this->conn->createCommand($sql)->queryall();
            if($res){
                foreach($res as $r){
                    $temp = array();
                    $code = $r['resultCode'];
                    $num = $r['num']; //数量
                    $temp['did']=$did;
                    $temp['date']=date('Y-m-d');
                    $temp['code']=$code;
                    $temp['num']=$num;
                    $temp['type']=$type;
                    $temp['intime']=$dateTo;
                    $data[] = $temp;
                }
            }
        }
        return $data;
    }
}
