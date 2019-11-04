<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/12
 * Time: 22:32
 */
//流量导入
namespace console\controllers;
use Yii;
class FlowController extends BaseController
{
    public $conn;
    public function  init(){
        $this->conn = Yii::$app->db;
    }


    /**
     * 单域名单CNAME
     *从日志表导入流量表
     */
    public function actionImportDomain()
    {
        //date_default_timezone_set('Asia/Shanghai');
        $sql_dnames = "select id,dname from lc_domain where status=2 and enable =1 order by id desc";
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
                if(in_array('[0-9a-z]',$arr_remap) || in_array('(.*)',$arr_remap) ||in_array('[a-z0-9]',$arr_remap) || in_array('*',$arr_remap)){
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
                        $this->_total_flow_dname($dname, $siteIds,$did);

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
                        $this->_total_flow_dname($dname, $siteIds,$did);

                    }
                }
            }
        }
    }

    /**
     * 多域名单CNAME
     *从日志表导入流量表
     */
    public function actionImportDefence()
    {
        //date_default_timezone_set('Asia/Shanghai');
        //读取高防包中的已开启的的高防数据
        $sql_defence = "select id from lc_defence where status =2 order by id desc";
        $defence_info =$this->conn->createCommand($sql_defence)->queryAll();
        if($defence_info){
            //有高防
            foreach($defence_info as $v){
                $did = $v['id'];

                $sql_defence_remap = "select originurl from lc_defence_remap where did = $did";
                $remap_info =$this->conn->createCommand($sql_defence_remap)->queryAll();
                if(empty($remap_info))
                    continue ;

                $dname_str = '';
                foreach($remap_info as $d_v){
                    $dname_str .="'".$d_v['originurl']."',";
                }
                $dname_str = rtrim($dname_str,',');
                //完全匹配
                $sql_v = "SELECT id from lc_sar_sites where site in ($dname_str)";
                $site_ids = $this->conn->createCommand($sql_v)->queryall();
                if(empty($site_ids)){
                    continue;
                }else{
                    $siteIds = '';
                    foreach($site_ids as $k=>$site_id){
                        $siteIds .= "'" . $site_id['id'] . "',";
                    }

                    $siteIds = rtrim($siteIds, ',');
                    //一个site,多个id作为参数,调用_total_flow_dname方法
                    $this->_total_flow_defence_dname($dname_str, $siteIds,$did);

                }
            }
        }
    }


    public function _total_flow_dname($site = null,$ids = null,$did = null){
        //$t1 = microtime(true);

        if( empty($site) || empty($ids) ){
            echo ( 'params is empty site ,error');
            return false;
        };
        //清空bak
        $sql_t_before = " TRUNCATE TABLE lc_sar_traffic_bak";
        $this->conn->createCommand($sql_t_before)->execute();

        //配置信息是写入还是修改
        $insert = true;

        //查询did 在配置表中是否存在
        $sql_config = "select t_id from lc_flow_config where did = $did";
        $res_config = $this->conn->createCommand($sql_config)->queryOne();

        if($res_config){
            $insert = false;
            $t_id = $res_config['t_id'];
            if($t_id){
                //有记录t_id  从t_id 开始算起
                $sql_bak = 'insert into lc_sar_traffic_bak select * from lc_sar_traffic where sitesID in ('.$ids.')  and id> '.$t_id.'  order by id   limit 2000000';
            }else{
                //查询traffic表中,siteID在site表id中的，且按日期时间顺序取一个
                $sql = 'select time from lc_sar_traffic  order by `time` ASC limit 0,1';

                $beginT = $this->conn->createCommand($sql)->queryOne();
                //查询traffic表中,siteID在site表id中的，且按日期时间逆序取一个
                $sql = 'select time from lc_sar_traffic order by `time` DESC limit 0,1';


                $lastT = $this->conn->createCommand($sql)->queryOne();
                $sql = "SELECT intime FROM `lc_flow`  WHERE site= '$site' ORDER BY intime desc LIMIT 1";

                $lastF =  $this->conn->createCommand($sql)->queryOne();

                $beginTT = strtotime($beginT['time']);

                $lastTT = strtotime($lastT['time']);

                $lastF['intime'] = empty($lastF['intime'])?0:$lastF['intime'];
                //debug('lastFT:'.$lastF['intime'].',lastFTTime:'.date('Y-m-d H:i:s',$lastF['intime']),40);

                //flow表中的最大时间 大于deny traffic表中的最小时间，且小于traffic表中的最大时间，则开始时间为flow表中的最大时间
                if( $lastF['intime'] >= $beginTT && $lastF['intime'] < $lastTT ){
                    $beginTime = $tmpTime = $lastF['intime'];
                    //flow表中的最大时间小于traffic表中的的最小时间，则开始时间等于traffic表中的最小时间
                }elseif($lastF['intime'] < $beginTT && $lastF['intime'] < $lastTT){
                    $beginTime = $tmpTime = floor( $beginTT/300 )*300;
                }
                //flow表中的最大时间大于等于traffic表中的最大时间，则认为没有数据可以写入
                if( $lastF['intime'] >= floor( $lastTT/300 )*300 ){
                    echo ( 'lastTT is not large than intime,return null,go next....');
                    return true;
                }

                //2016-12-15,判断结束时间，若大于当前时间，则结束时间改为当前时间
                $nowTime = floor(time()/300)*300;
                $endTime = floor( $lastTT/300 )*300;
                if($endTime >$nowTime){
                    $endTime = $nowTime;
                }
                //debug('beginTime:'.$beginTime.',beginTimeDate:'.date('Y-m-d H:i:s',$beginTime),40);
                //debug('endTime:'.$endTime.',endTimeDate:'.date('Y-m-d H:i:s',$endTime),40);

                //区分国内国外 country =1 国内，country=2 国外(不直接区分国内外)
                //2015-05-20 按每个节点来 5分钟来统计流量。遍历每个节点，同时判断此节点是国内或者国外
                $sql_node ='select id,country from lc_node';
                $total_node = $this->conn->createCommand($sql_node)->queryAll();
                //print_r($total_node);
                //echo $tmpTime;
                //2016-12-16  抓起数据时间大于flow最后时间且小于当前时间提前一小时，目的：累计一小时数据再计算，防止遗漏流量
                $nowTime_ahead = $nowTime - 3600;
                $sql_bak = 'insert into lc_sar_traffic_bak select * from lc_sar_traffic where sitesID in ('.$ids.')  and UNIX_TIMESTAMP(time)>=' . $tmpTime .' order by id  limit 2000000';
                //$sql_bak = 'insert into lc_sar_traffic_bak_test select * from lc_sar_traffic_test where sitesID in ('.$ids.')  and UNIX_TIMESTAMP(time)>=' . $tmpTime .' order by id  limit 2000000';
                //$this->conn->createCommand($sql_bak)->execute();
            }
        }else{
            $sql = 'select id from lc_sar_traffic order by `id` asc limit 0,1';
            $beginId = $this->conn->createCommand($sql)->queryOne();
            $t_id = $beginId['id'];
            $sql_bak = 'insert into lc_sar_traffic_bak select * from lc_sar_traffic where sitesID in ('.$ids.')  and id>= '.$t_id.'  order by id  limit 2000000';
            //$sql_bak = 'insert into lc_sar_traffic_bak_test select * from lc_sar_traffic_test where sitesID in ('.$ids.')  and id>= '.$t_id.'  order by id  limit 2000000';
        }


        $this->conn->createCommand($sql_bak)->execute();

        //查询bak表中各个节点的最小时间，存入相应数组中
        $sql_node_minTime_bak = "select min(time) as time,nodeid from lc_sar_traffic_bak GROUP BY nodeid";

        //$sql_node_minTime_bak = "select min(time) as time,nodeid from lc_sar_traffic_bak_test GROUP BY nodeid";

        $res_node_minTime_bak =  $this->conn->createCommand($sql_node_minTime_bak)->queryAll();
        $arr_node_minTime_bak = array();
        if($res_node_minTime_bak){
            foreach($res_node_minTime_bak as $v){
                $arr_node_minTime_bak[$v['nodeid']] = floor(strtotime($v['time'])/300)*300;
            }
        }

        //检查bak 是否有数据
        $check_bak = "select id from lc_sar_traffic_bak ORDER  by id limit 1";

        //$check_bak = "select id from lc_sar_traffic_bak_test ORDER  by id limit 1";

        $check_bak_res = $this->conn->createCommand($check_bak)->queryOne();
        if(!$check_bak_res){
            echo 'bak empty'."\n";
            return false;
        }
        //$endtime改为lc_sar_traffic_bak 最大时间 2017-7-24
        $sql = 'select time from lc_sar_traffic_bak  order by `time` DESC limit 0,1';

        //$sql = 'select time from lc_sar_traffic_bak_test order by `time` DESC limit 0,1';

        $lastT = $this->conn->createCommand($sql)->queryOne();
        $lastTT = strtotime($lastT['time']);

        $endTime = floor( $lastTT/300 )*300;
        $spaceTime = 300;


        //开始时间改为bak中的最小时间 2018-2-7p
        $sql = "select time from lc_sar_traffic_bak where `time`!='0000-00-00 00:00:00'  order by `time` asc limit 0,1";
        //$sql = 'select time from lc_sar_traffic_bak order by `time` asc limit 0,1';

        //$sql = 'select time from lc_sar_traffic_bak_test order by `time` asc limit 0,1';

        $lastF = $this->conn->createCommand($sql)->queryOne();
        $startTT = strtotime($lastF['time']);
        $startTime = floor($startTT/300)*300;

        $sql_node ='select id,country from lc_node';
        $total_node = $this->conn->createCommand($sql_node)->queryAll();

        for( $startTime; $startTime <= $endTime; $startTime += $spaceTime ) {
            /*
            $check_bak = "select id from lc_sar_traffic_bak ORDER  by id limit 1";
            $check_bak_res = $this->conn1->createCommand($check_bak)->queryrow();
            if(!$check_bak_res){
                continue;
            }
            */
            //debug( 'begin for mem:'.memory_get_usage(), 40);
            foreach($total_node as $v) {
                $nodeid = $v['id'];
                $country = $v['country'];
                //判断节点在bak中，若无则下一个
                if(@$arr_node_minTime_bak[$nodeid]){
                    //bak中有节点数据，判断最小时间是否大于开始时间，若大于且大于5分钟则说明五分钟内没有数据

                    if($arr_node_minTime_bak[$nodeid] >$startTime && ($arr_node_minTime_bak[$nodeid] -$startTime) >300){
                        continue;
                    }else{
                        $sql = 'select sum(bytes) as bytesTotal,count(id) as hitTotal from lc_sar_traffic_bak where  nodeid ='.$nodeid.' and  UNIX_TIMESTAMP(time)>=' . $startTime . ' and UNIX_TIMESTAMP(time)<' . ($startTime + 5 * 60);

                        //$sql = 'select sum(bytes) as bytesTotal,count(id) as hitTotal from lc_sar_traffic_bak_test where  nodeid ='.$nodeid.' and  UNIX_TIMESTAMP(time)>=' . $startTime . ' and UNIX_TIMESTAMP(time)<' . ($startTime + 5 * 60);

                        $totalT = $this->conn->createCommand($sql)->queryOne();

                        //流量为null 跳过 正常不会出现null
                        if($totalT['bytesTotal'] == null){
                            continue;
                        }

                        $totalFlow = round(((int)$totalT['bytesTotal']) / 1048576, 2);
                        //如果计算总流量为0，则定义值为0.01
                        if ($totalFlow == 0) {
                            $totalFlow = 0.01;
                        }

                        //debug('sql:' . $sql, 40);
                        //echo ('time:' . date('Y-m-d H:i:s', $tmpTime) . ',flow(bytes):' . $totalT['bytesTotal'] . ',:flow(MB):' . $totalFlow);
                        $hit = $totalT['hitTotal'];
                        $intime = $startTime + 5 * 60;
                        $sql_1 = "insert into lc_flow (site,flow,hit,intime,country,nodeid,did) VALUES ('$site','$totalFlow','$hit','$intime','$country','$nodeid',$did)";
                        //echo $sql_1."\n";
                        $this->conn->createCommand($sql_1)->execute();
                    }
                }

            }
            //删除小于等于tmpTime的数据
            //$sql_del = "delete from lc_sar_traffic_bak where UNIX_TIMESTAMP(time) <= $tmpTime";
            //$this->conn->createCommand($sql_del)->execute();
        }

        //查看bak中最大的ID
        $sql = 'select id from lc_sar_traffic_bak order by id DESC limit 0,1';

        //$sql = 'select id from lc_sar_traffic_bak_test order by id DESC limit 0,1';

        $lastIdRes = $this->conn->createCommand($sql)->queryOne();
        $l_id =  $lastIdRes['id'];
        if($insert){
            $sql_c = "insert into lc_flow_config (did,t_id) VALUES ($did,$l_id)";
        }else{
            $sql_c = "update lc_flow_config set t_id = $l_id where did = $did";
        }

        $this->conn->createCommand($sql_c)->execute();

        $sql_t = " TRUNCATE TABLE lc_sar_traffic_bak";

        //$sql_t = " TRUNCATE TABLE lc_sar_traffic_bak_test";

        $this->conn->createCommand($sql_t)->execute();
    }


    public function _total_flow_defence_dname($site = null,$ids = null,$did = null){
        //$t1 = microtime(true);

        if( empty($site) || empty($ids) ){
            echo  'params is empty site ,error';
            return false;
        }
        //清空bak
        $sql_t_before = " TRUNCATE TABLE lc_sar_traffic_defence_bak";
        $this->conn->createCommand($sql_t_before)->execute();


        $insert = true;

        //查询did 在配置表中是否存在
        $sql_config = "select t_id from lc_flow_defence_config where did = $did";
        $res_config = $this->conn->createCommand($sql_config)->queryOne();

        if($res_config){
            $insert = false;
            $t_id = $res_config['t_id'];
            if($t_id){
                //有记录t_id  从t_id 开始算起
                $sql_bak = 'insert into lc_sar_traffic_defence_bak select * from lc_sar_traffic where sitesID in ('.$ids.')  and id> '.$t_id.'  order by id   limit 2000000';
            }else{
                //debug( 'get data from traffic..,site:'.$site.',ids:'.$ids, 40 );
                //查询traffic表中,siteID在site表id中的，且按日期时间顺序取一个
                $sql = 'select time from lc_sar_traffic  order by `time` ASC limit 0,1';

                $beginT = $this->conn->createCommand($sql)->queryOne();
                //查询traffic表中,siteID在site表id中的，且按日期时间逆序取一个
                $sql = 'select time from lc_sar_traffic order by `time` DESC limit 0,1';
                $lastT = $this->conn->createCommand($sql)->queryOne();
                $sql = "SELECT intime FROM `lc_flow`  WHERE site in ($site) ORDER BY intime desc LIMIT 1";

                $lastF =  $this->conn->createCommand($sql)->queryOne();

                $beginTT = strtotime($beginT['time']);

                $lastTT = strtotime($lastT['time']);

                $lastF['intime'] = empty($lastF['intime'])?0:$lastF['intime'];


                //flow表中的最大时间 大于deny traffic表中的最小时间，且小于traffic表中的最大时间，则开始时间为flow表中的最大时间
                if( $lastF['intime'] >= $beginTT && $lastF['intime'] < $lastTT ){
                    $beginTime = $tmpTime = $lastF['intime'];
                    //flow表中的最大时间小于traffic表中的的最小时间，则开始时间等于traffic表中的最小时间
                }elseif($lastF['intime'] < $beginTT && $lastF['intime'] < $lastTT){
                    $beginTime = $tmpTime = floor( $beginTT/300 )*300;
                }
                //flow表中的最大时间大于等于traffic表中的最大时间，则认为没有数据可以写入
                if( $lastF['intime'] >= floor( $lastTT/300 )*300 ){
                    //echo ( 'lastTT is not large than intime,return null,go next....');
                    return true;
                }

                //2016-12-15,判断结束时间，若大于当前时间，则结束时间改为当前时间
                $nowTime = floor(time()/300)*300;
                $endTime = floor( $lastTT/300 )*300;
                if($endTime >$nowTime){
                    $endTime = $nowTime;
                }
                //debug('beginTime:'.$beginTime.',beginTimeDate:'.date('Y-m-d H:i:s',$beginTime),40);
                //debug('endTime:'.$endTime.',endTimeDate:'.date('Y-m-d H:i:s',$endTime),40);
                $spaceTime = 300;
                //区分国内国外 country =1 国内，country=2 国外(不直接区分国内外)
                //2015-05-20 按每个节点来 5分钟来统计流量。遍历每个节点，同时判断此节点是国内或者国外

                //print_r($total_node);
                //echo $tmpTime;
                //2016-12-16  抓起数据时间大于flow最后时间且小于当前时间提前一小时，目的：累计一小时数据再计算，防止遗漏流量
                $nowTime_ahead = $nowTime - 3600;
                $sql_bak = 'insert into lc_sar_traffic_defence_bak select * from lc_sar_traffic where sitesID in ('.$ids.')  and UNIX_TIMESTAMP(time)>=' . $tmpTime .' order by id  limit 2000000';
            }
        }else{
            $sql = 'select id from lc_sar_traffic order by `id` asc limit 0,1';
            $beginId = $this->conn->createCommand($sql)->queryOne();
            $t_id = $beginId['id'];

            $sql_bak = 'insert into lc_sar_traffic_defence_bak select * from lc_sar_traffic where sitesID in ('.$ids.')  and id>= '.$t_id.'  order by id  limit 2000000';
        }


        $this->conn->createCommand($sql_bak)->execute();

        $sql_node ='select id,country from lc_node';
        $total_node = $this->conn->createCommand($sql_node)->queryAll();

        //查询bak表中各个节点的最小时间，存入相应数组中
        $sql_node_minTime_bak = "select min(time) as time,nodeid from lc_sar_traffic_defence_bak GROUP BY nodeid";
        $res_node_minTime_bak =  $this->conn->createCommand($sql_node_minTime_bak)->queryAll();
        $arr_node_minTime_bak = array();
        if($res_node_minTime_bak){
            foreach($res_node_minTime_bak as $v){
                $arr_node_minTime_bak[$v['nodeid']] = floor(strtotime($v['time'])/300)*300;
            }
        }

        //检查bak 是否有数据
        $check_bak = "select id from lc_sar_traffic_defence_bak ORDER  by id limit 1";
        $check_bak_res = $this->conn->createCommand($check_bak)->queryOne();
        if(!$check_bak_res){
            return false;
        }

        //lc_sar_traffic_bak_gf  最大时间 2017-7-24
        $sql = 'select time from lc_sar_traffic_defence_bak order by `time` DESC limit 0,1';
        $lastT = $this->conn->createCommand($sql)->queryOne();
        $lastTT = strtotime($lastT['time']);
        $endTime = floor( $lastTT/300 )*300;

        //开始时间改为bak中的最小时间 2018-2-7
        $sql = 'select time from lc_sar_traffic_defence_bak order by `time` asc limit 0,1';

        $lastF = $this->conn->createCommand($sql)->queryOne();
        $startTT = strtotime($lastF['time']);
        $startTime = floor($startTT/300)*300;
        $tmpTime = $startTime;

        //$spaceTime = 7200;
        $spaceTime = 300;

        for( $tmpTime; $tmpTime <= $endTime; $tmpTime += $spaceTime ) {
            foreach($total_node as $v) {
                $str_t = '';
                $nodeid = $v['id'];
                $country = $v['country'];
                if(@$arr_node_minTime_bak[$nodeid]){

                    $sql_5 = 'select sum(bytes) as bytesTotal,count(id) as hitTotal,floor(UNIX_TIMESTAMP(time)/300)*300 as gt from lc_sar_traffic_defence_bak where  nodeid ='.$nodeid.' and   UNIX_TIMESTAMP(time)>=' . $tmpTime . ' and UNIX_TIMESTAMP(time)<' . ($tmpTime + $spaceTime)  .' group by gt order by gt';
                    $res_5 =  $this->conn->createCommand($sql_5)->queryAll();
                    $site_a = explode(',',$site);
                    $site_s = $site_a[0];
                    if($res_5){
                        foreach($res_5 as $v){

                            if($v['bytesTotal'] == null){
                                continue;
                            }

                            $totalFlow = round(((int)$v['bytesTotal']) / 1048576, 2);
                            //如果计算总流量为0，则定义值为0.01
                            if ($totalFlow == 0) {
                                $totalFlow = 0.01;
                            }
                            $in_time = $v['gt'];
                            $hit = $v['hitTotal'];
                            $str_t .="(".$site_s.",'".$totalFlow."','".$hit."','".$in_time."','".$country."','".$nodeid."','".$did."','2'),";
                        }
                        if($str_t != ''){
                            $str_t = trim($str_t,',');

                            //一次性写入
                            $sql_1 = "insert into lc_flow (site,flow,hit,intime,country,nodeid,did,type) VALUES $str_t";
                            $this->conn->createCommand($sql_1)->execute();
                        }
                    }
                }
            }

        }


        //查看bak中最大的ID
        $sql = 'select id from lc_sar_traffic_defence_bak order by id DESC limit 0,1';

        $lastIdRes = $this->conn->createCommand($sql)->queryOne();
        $l_id =  $lastIdRes['id'];
        if($insert){
            $sql_c = "insert into lc_flow_defence_config (did,t_id) VALUES ($did,$l_id)";
        }else{
            $sql_c = "update lc_flow_defence_config set t_id = $l_id where did = $did";
        }

        $this->conn->createCommand($sql_c)->execute();

        $sql_t = " TRUNCATE TABLE lc_sar_traffic_defence_bak";
        $this->conn->createCommand($sql_t)->execute();
    }


}
