<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/3
 * Time: 21:36
 */
namespace console\models;

use backend\models\ApiTrait;
use common\lib\Utils;
use common\models\DefenceIp;
use common\components\Logger;
use common\models\IpdatabaseSimplify;
use common\models\NodeGroup;
use common\models\Route;
use yii\base\Model;
use common\models\Config;
use backend\models\Node;
use yii\helpers\ArrayHelper;

class Routing extends Model
{
    use ApiTrait;
    public $logType = 'routing';

    //节点的各个状态值
    protected $switch_on = 1; //开
    protected $switch_off = 0; //关
    protected $status_on =1;
    protected $status_off = 0;
    protected $forbidden_on = 1;//关
    protected $forbidden_off = 0;//开

    protected $alive_on = 1;
    protected $alive_off =0;

    protected $group_type_arr = array(
//        1=>'n',
//        2=>'c',
//        3=>'a',
//        4=>'t',
//        5=>'j',
//        6=>'w',
//        7=>'nw',
//        8=>'ly',
//        9=>'d',
    );

    protected $group_name_arr = array(
//        1=>'lesucdn',
//        2=>'dnsunionsnet',
//        3=>'dnsunionscom',
//        4=>'dns-tw',
//        5=>'jbsdnsn',
//        6=>'wuxiandns',
//        7=>'cdn-w',
//        8=>'mly666',
//        9=>'ddos-dns',
    );

    protected $node_cname_arr = array(
//        1=>'.cdn.lesucdn.net:',
//        2=>'.cdn.dnsunions.net:',
//        3=>'.cdn.dnsunions.com:',
//        4=>'.cdn.dns-tw.net:',
//        5=>'.cdn.jbsdnsn.net:',
//        6=>'.cdn.wuxiandns.com:',
//        7=>'.cdn.cdn-w.net:',
//        8=>'.cdn.mly666.com:',
//        9=>'.cdn.ddos-dns.net:',
    );

    protected $registsource_arr = array(
//        1=>'lesucdn',
//        2=>'cdnunions',
//        3=>'dnsunions',
//        4=>'dns-tw',
//        5=>'jbsdnsn',
//        6=>'wuxiandns',
//        7=>'cdn-w',
//        8=>'mly666',
//        9=>'ddos-dns',
    );

    protected $public_cname_arr = array(
//        1=>'cdn.lesucdn.net:',
//        2=>'cdn.dnsunions.net:',
//        3=>'cdn.dnsunions.com:',
//        4=>'cdn.dns-tw.net:',
//        5=>'cdn.jbsdnsn.net:',
//        6=>'cdn.wuxiandns.com:',
//        7=>'cdn.cdn-w.net:',
//        8=>'cdn.mly666.com:',
//        9=>'cdn.ddos-dns.net:',
    );

    protected $group_path = './';//导出group路径，默认当前目录下
    protected $dns_path = './';//导出dns路径  默认当前目录下

    protected $info_denfence_record = ''; //高防记录
    protected $now; //当前时间戳
    protected $nodeInfo; //节点信息
    protected $aTtl; //A记录的TTL
    protected $gfTtl; //高防TTL
    protected $config_ddos_link_max; //最大连接数
    protected $config_ddos_link_min; //最小链接数
    protected $originip_links; //源IP对应链接数
    protected $node_ip; //节点id=>节点Ip

    protected $source; //注册来源 传参 国内=1 国外=2 高防=3
    protected $node_common = 3; //lc_node中country值 公共节点不区分国内外
    protected $allNodeOn; //所有开启的节点 2016-07-13  针对导出A记录，只判断开关，不再判断禁止

    protected $publicTtl = 300;  //公共别名的ttl
    protected $nodeTtl = 180;

    protected $db;

    public function __construct($source,$data = [])
    {
        if($data)
        {
            foreach ($data as $val)
            {
                $this->group_type_arr[$val['id']] = $val['c_type'];
                $this->group_name_arr[$val['id']] = $val['type'];
                $this->node_cname_arr[$val['id']] = ".cdn.".$val['cname_suffix'].":";
                $this->registsource_arr[$val['id']] = $val['type'];
                $this->public_cname_arr[$val['id']] = "cdn.".$val['cname_suffix'].":";
            }

        }
        $this->db = \Yii::$app->db;
        $this->now = time();
        $this->source = $source;
        $this->aTtl = $this->aTtl();
        $this->gfTtl = $this->gfTtl();
        $this->config_ddos_link_max = $this->triggerMax();
        $this->config_ddos_link_min = $this->triggerMin();
        $this->originip_links = $this->ddosLink();
        $this->node_ip = $this->nodeidIp();
        $this->allNodeOn = $this->getAllNodes($source);
        $this->group_path = \Yii::getAlias('@dns_file').'/routing/';
        $this->dns_path = \Yii::getAlias('@dns_file').'/routing/';
    }



    //执行
    public function execute(){
        //节点状态实时判断
       // $this->isForbidden();
        $this->exportGroup();
        $this->exportDomainDns();

    }



    /**
     * 根据alive、状态值判断是否禁止
     * 2015-10-19 增对alive_z_1  alive_z_2 的判断改变 alive的值 两个同时为0则为0,否则为1
     * 根据节点ID,判断当前节点的流量是否超过最大值，则自动关闭 修改status = 0
     * status 跟alive 同时为1 ， 解除禁止
     * 增加流量的判断 除去后端
     */
    public function isForbidden(){

        $arr_backend = $this->getBackend();
        $res_node_alive = Node::find();
        if($arr_backend){
            $res_node_alive ->where(['not in','id',$arr_backend]);
        }
        $res_node_alive = $res_node_alive->asArray()->all();
        //alive_z_1  alive_z_2 同时为0 则alive 为0
        foreach($res_node_alive as $v){
            $alive_id = $v['id'];
            $alive_z_1 = $v['alive_z_1'];
            $alive_z_2 = $v['alive_z_2'];
            $flow = $v['flow'];
            $flow_max = $v['flow_max'];
            $model = Node::findOne($alive_id);
            $o_model = $model->getOldAttributes();
            if($alive_z_1 == $this->alive_off && $alive_z_2 ==$this->alive_off){
                    $model->alive = $this->alive_off;
                    $model->forbidden = $this->forbidden_on;
            }else{
                    $model->alive = $this->alive_on;
            }
            if($flow >= $flow_max){
                $model->status = $this->status_off;
                $model->forbidden = $this->forbidden_on;
            }else{
                    $model->status = $this->status_on;
            }
            if($model->status == $this->status_on && $model->alive == $this->alive_on)
            {
                $model->forbidden = $this->forbidden_off;
            }elseif($model->status == $this->status_off || $model->alive == $this->alive_off){
                $model->forbidden = $this->forbidden_on;
            }
           if($model->forbidden != $o_model['forbidden'] || $model->alive != $o_model['alive'] || $model->status != $o_model['status'])
           {
               $model->save();
           }
        }
    }

    /**
     * 根据相应的routing导出相应的group
     */
    public function exportGroup(){
        $source = $this->source;
        $res_routing = Route::find()->groupBy('group_id')->asArray()->all();
        $text_group = array();
        $text_group[]="\n";
        $this->routingFormat($res_routing,$text_group,$source);

    }

    /**
     * 国内外分组
     */
    public function HomeAbroadGroup()
    {
        $name = 'd1'; //国内
        $arr= array();
        $sql = "select ipduan from lc_ipdatabase_simplify where group_id  in (select id from lc_iparea where country_id='CN')";
        $res = $this->db->createCommand($sql)->queryAll();
        if($res && $name){
            foreach($res as $v){
                $ipduan = $v['ipduan'];
                if(!empty($ipduan)){
                    $arr[] = "%".$name.":".$ipduan.":\n";
                }
            }
        }
        unset($name);
        $name = 'd2'; //国内
        $sql = "select ipduan from lc_ipdatabase_simplify where group_id  in (select id from lc_iparea where country_id != 'CN')";
        $res = $this->db->createCommand($sql)->queryAll();
        if($res && $name){
            foreach($res as $v){
                $ipduan = $v['ipduan'];
                if(!empty($ipduan)){
                    $arr[] = "%".$name.":".$ipduan.":\n";
                }
            }
        }
        //导出d
        //导出
        $filename = $this->group_path.'group_ddos.text';
        $arr[] = "#end\n";

        $result = Utils::fileIsUpdate($filename,$arr);
        if($result)
            file_put_contents($filename,$arr);
    }

    /**
     * 导出每个的加速域名对应的节点及定位码
     * 加速域名无分组号的不导出
     * 分组号中所有节点关闭时，则切换高防
     */
    public function exportDomainDns(){
        $text_arr = array();
        $source = $this->source;
        //获取高防cname
        $str_defence = $this->gfCname($source);

        //链接数最大触发值
        $config_ddos_trigger = $this->config_ddos_link_max;



        //查询 没有开启高防，且已部署的加速信息，包括id，cname,节点分组号，回源IP
        //判断国内外
        $type = $this->group_type_arr[$source];

        $registsource = $this->registsource_arr[$source];

        ## zjq
       // $sql_s_c = "select id,cname,node_group,originip,ttl from lc_domain where username in (SELECT username from lc_user where registsource = '$registsource') and high_anti = 0 and status =2 and enable = 1";
        $sql_s_c = "select id,cname,node_group,originip,ttl,high_anti,sys_node_group,sys_high_anti from lc_domain where user_id in (SELECT id from lc_user where registsource = '$registsource') and  status =2 and enable = 1 AND stype = 1 union all select id,cname,node_group,originip,ttl,high_anti,sys_node_group,sys_high_anti from lc_defence where user_id in (SELECT id from lc_user where registsource = '$registsource') and  status =2 and enable = 1 AND stype = 1";

        $res_s_c = $this->db->createCommand($sql_s_c)->queryAll();

        //var_dump($res_s_c);die;
        if($res_s_c){
            foreach($res_s_c as $v_s_c){

                $config_node_C_ttl = $v_s_c['ttl'];
                $cname = "C".$v_s_c['cname'].':'; //cname

                //使用后台设置分组
                if($v_s_c['sys_node_group'] == 0)
                    $group_name = $v_s_c['node_group']; //分组号
                else
                    $group_name = $v_s_c['sys_node_group']; //分组号

                $did = $v_s_c['id']; //domain id
                $originip = $v_s_c['originip'];//回源IP

                //使用后台设置高防
                if($v_s_c['sys_high_anti'] == 0)
                    $g_id = $v_s_c['high_anti'];
                else
                    $g_id = $v_s_c['sys_high_anti'];

                if($g_id){
                    $sql_defence = "select cname from lc_defence_ip where id=$g_id";
                    $res_defence =  $this->db->createCommand($sql_defence)->queryOne();
                    if($res_defence){
                        $str_defence = $res_defence['cname'];
                    }else{
                        $str_defence = $this->gfCname($source);
                    }
                }else{
                    $str_defence = $this->gfCname($source);
                }

                //print_r($res_s_c);
                //查询节点分组下的节点信息 状态正常且国内 属于分组

                //查询当前分组内的节点
                // todo zjq remarks
                //$sql_node_id_group = "select node_id from lc_node_group where id =$group_name and type = $source";
                $sql_node_id_group = "select node_id from lc_node_group where id =$group_name ";
                $res_node_id_group = $this->db->createCommand($sql_node_id_group)->queryOne();
                $node_id_group = $res_node_id_group['node_id'];

                //判断是否有内容，如无，报错，继续下一个（对应的cname则无加速信息）
                if($node_id_group ==''){
                    $content = $this->registsource_arr[$this->source].'  '. $cname.' 没有节点分组数据---1';
                  //  Logger::factoryLog($content,$this->logType);
                    continue;
                }

                //查找当前分组内的节点ID节点NAME(状态正常的,高防不区分国内外)
                ### zjq
                $sql_node_info_c = "select t1.id,t1.name from lc_node t1 where t1.id in ($node_id_group) and t1.switch =$this->switch_on and t1.forbidden =$this->forbidden_off";

                $res_node_info_c = $this->db->createCommand($sql_node_info_c)->queryAll();



                //判断是否有内容，如无（分组号内的所有节点都关闭或者禁止），报错，切换高防，继续下一个
                if(!$res_node_info_c){
                   // echo $cname.'no data in node_group';
                    $name_nomatch =$str_defence. ':'.$config_node_C_ttl;
                    $text_arr[] = $cname.$name_nomatch."\n";
                    $this->info_denfence_record .= "('" . $originip . "'," . "''," . $this->now .",0),"; //高防记录
                    //将此cname存入数组，之后全部转高防
                    $content = $this->registsource_arr[$this->source].'  '. $cname.'没有节点分组数据-- 已切高防 -2';
                    //Logger::factoryLog($content,$this->logType);
                    continue;
                }

                //遍历节点分组内的信息，进行拼接，存入要导出的数组中
                //判断源IP的最大链接数是否超过砝值，若超过，则切换高防

                $sql_originip_max_link = "SELECT MAX(link) as link from lc_ddos where ip = '$originip'";
                $res_originip_max_link = $this->db->createCommand($sql_originip_max_link)->queryOne();
                if($res_originip_max_link){
                    $maxLink = $res_originip_max_link['link'];
                    if($maxLink >= $config_ddos_trigger){
                        $name_nomatch =$str_defence. ':'.$config_node_C_ttl;
                        $text_arr[] = $cname.$name_nomatch."\n";
                        $this->info_denfence_record .= "('" . $originip . "'," . "''," . $this->now .",".$maxLink."),"; //高防记录
                        //将此cname存入数组，之后全部转高防
                        $content = $this->registsource_arr[$this->source].'  '. $cname. 'max link' .$maxLink. '大于最大连接数 '.$config_ddos_trigger .' 已切到高防';
                        $content .= $cname.$name_nomatch;
                        //Logger::factoryLog($content,$this->logType);
                        continue;
                    }
                }


                //正常导出 Ccname:分组别名
                $public_cname = $this->public_cname_arr[$source];
                $name = $group_name.$public_cname;
                $text_arr[] = $cname.$name.$this->publicTtl."\n";

            }
        }


        //导出分组公共别名对应节点别名，节点关闭禁止的不导出

        $text_arr = $this->exportPublicNode($source,$text_arr);
        //$text_arr = $this->exportDomainGf($source,$text_arr);
        $text_arr = $this->exportCluster($source,$text_arr);
        $text_arr = $this->exportDefence($source,$text_arr);

        $typeName = $this->group_name_arr[$source];
        //导出
        $filename = $this->dns_path.'dns_'.$typeName.'.text';

        if(empty($text_arr))
            file_put_contents($filename,"");

        $content =$text_arr;
        $result = Utils::fileIsUpdate($filename,$content);
        if($result)
        file_put_contents($filename,$content);
        //return $text_arr;
    }

    protected function routingFormat($res,$arr,$country){
        if($res){
            $e = $this->group_type_arr[$country];
            //不增加L分组

            foreach($res as $v){
                $group_id = $v['group_id'];
                if(!$group_id){
                    continue;
                }
                $name = $e.$group_id;
                $res2 = IpdatabaseSimplify::find()->where(['group_id'=>$group_id])->groupBy('group_id')->asArray()->all();
                if($res2 && $name){
                    foreach($res2 as $v2){
                        $ipduan = $v2['ipduan'];
                        if(!empty($ipduan)){
                            $arr[] = "%".$name.":".$ipduan.":\n";
                        }
                    }
                }
                unset($name);
            }
            unset($res);
            //导出
            //导出
            $gourpNamw = $this->group_name_arr[$country];
            $filename = $this->group_path.'group_'.$gourpNamw.'.text';
            $arr[] = "#end\n";
            $content =$arr;
            $result = Utils::fileIsUpdate($filename,$content);
            if($result)
            file_put_contents($filename,$content);

        }else{
            $gourpNamw = $this->group_name_arr[$country];
            $filename = $this->group_path.'group_'.$gourpNamw.'.text';
            file_put_contents($filename,"");
            echo 'lc_routing_n has error';die;
        }

    }


    /**
     * 切换高防链接数的最大链接数
     * @return int
     */

    protected function triggerMax(){
        //查询链接数的阀值 触发值
        $model = Config::find()->where(['name'=>'ddos_trigger'])->one();
        if($model){
            $res = $model->value;
        }else{
            $res = 100;
        }
        return $res;
    }

    /**
     * 高防回复正常的最小链接数
     * @return int
     */
    protected function triggerMin(){
        //最小值
        $model = Config::find()->where(['name'=>'ddos_min'])->one();
        if($model){
            $res = $model->value;
        }else{
            $res = 5;
        }
        return $res;
    }


    /**
     * 获取A记录所对应的TTL
     * @return mixed
     */
    protected function aTtl(){
        //获取节点A记录的TTL
        $model = Config::find()->where(['name'=>'node_A_ttl'])->one();
        if($model){
            $ttl = $model->value;
        }else{
            $ttl = 600;
        }
        return $ttl;
    }


    /**
     * 获取高防所对应的TTL
     * @return mixed
     */
    protected function gfTtl(){
        //获取高防的TTL
        $model = Config::find()->where(['name'=>'defence_ttl'])->one();
        if($model){
            $ttl = $model->value;
        }else{
            $ttl = 600;
        }
        return $ttl;
    }


    /**
     * 状态正常的节点 ID=>IP  [1=>'1.2.3.4']
     * @return array
     */

    protected function nodeidIp(){
        //节点id对应ip数组 (状态正常)
        $model = Node::find()->where(['switch'=>$this->switch_on])->andWhere(['forbidden'=>$this->forbidden_off])->asArray()->all();
        $res = ArrayHelper::map($model,'id','ip');
        return $res;
    }

    /**
     * 获取后端节点，后端节点不受节点状态影响
     * @return int|mixed
     */
    protected function getBackend(){
        $model = Config::find()->where(['name'=>'backend'])->one();
        if($model){
            $res = $model->value;
            $res = explode(',',$res);
        }else{
            $res = false;
        }
        return $res;
    }

    /**
     * 加速切换高防的cname (默认值)
     * @param $country
     * @return mixed
     */
    protected function gfCname($country){
        //获取国内高防域名 lc_defence_ip
        $res_defence = DefenceIp::find()->where(['country'=>$country])->andWhere(['remark'=>1])->asArray()->one();
        if($res_defence){
            $str_defence = $res_defence['cname']; //国内高防域名：gf99.lesucdn.net
        }else{
            $str_defence = '';
        }

        return $str_defence;
    }


    /**
     * 获取全部加速的分组
     */
    public function getDomainNodeGroup($source)
    {
        $data = [];
        $list = [];
       $nodeGroup =  $this->getAllNodeGroup($source);
        $db = \Yii::$app->db;
        $sql = "select  node_group,sys_node_group   from {{%domain}} where stype = 1 and status = 2  UNION ALL select  node_group,sys_node_group from {{%defence}} where stype = 1 and status = 2";
        $res = $db->createCommand($sql)->queryAll();
        if($res)
        {
            foreach ($res as $val)
            {
                if(isset($nodeGroup[$val['node_group']]))
                {
                    $data[] = $val['node_group'];
                }
                if(isset($nodeGroup[$val['sys_node_group']]))
                {
                    $data[] = $val['sys_node_group'];
                }
            }
        }
        if($data)
        {
            $data = array_filter(array_unique($data));
           // $where = implode(",",$data);
            $list = NodeGroup::find()->where(['in','id',$data])->asArray()->all();
        }
        return $list;
    }

    /**
     * 导出公共别名对应节点别名
     */
    protected function exportPublicNode($source,$text_arr){

        $arr_node_id_name = $this->nodeidName();
        //$res = NodeGroup::find()->where(['type'=>$source])->asArray()->all();
        $res = $this->getDomainNodeGroup($source);
        $arr_route = array();
        if($res){

            foreach($res as $v){
                $group_name = $v['id'];
                $node_id_group = $v['node_id'];
                $sql_node_info_c = "select t1.id,t1.name from lc_node t1 where t1.id in ($node_id_group) and t1.switch =$this->switch_on and t1.forbidden =$this->forbidden_off";

                $res_node_info_c = $this->db->createCommand($sql_node_info_c)->queryAll();
                $arr_node_ok = array();
                if($res_node_info_c){
                    foreach($res_node_info_c as $v_n){
                        $arr_node_ok[] = $v_n['id'];
                    }
                    if(!empty($arr_node_ok)){
                        $str_node_ok = implode(',',$arr_node_ok);
                    }else{
                        continue;
                    }

                    $public_cname = $this->public_cname_arr[$source];
                    $name = 'C'.$group_name.$public_cname;
                    $node_cname_source = $this->node_cname_arr[$source];
                    $type = $this->group_type_arr[$source];

                    $res_route_sql = "select group_id,node_id from (select * from lc_route where  node_id in ($str_node_ok) group by ms,group_id order by ms asc) t group by group_id;";
                    $res_route = $this->db->createCommand($res_route_sql)->queryAll();
                    if($res_route){
                        foreach($res_route as $r){
                            if($r['group_id'] && $r['node_id']){
                                $arr_route[$r['group_id']] = $r['node_id'];
                            }
                        }
                    }
                    if($arr_route){
                        foreach($arr_route as $k=>$v_r){
                            $name_min = @$arr_node_id_name[$v_r];
                            if($name_min){
                                $nodeCname =  $name_min.$node_cname_source.$this->nodeTtl.'::'.$type.$k;
                                $text_arr[] = $name.$nodeCname."\n";
                            }
                        }
                    }

                    $sql_weight = "select name from lc_node where id in ($str_node_ok) and switch =$this->switch_on and forbidden = $this->forbidden_off ORDER BY weight desc LIMIT 1";
                    $res_weight =$this->db->createCommand($sql_weight)->queryOne();

                    if(@$res_weight['name']){
                        $name_nomatch = $res_weight['name'].$node_cname_source.$this->nodeTtl.'::nomatch';
                        $text_arr[] = $name.$name_nomatch."\n";
                    }
                }


            }
        }

        return $text_arr;
    }


    /**
     * 节点ID=>节点name  [1=>n1]
     * @return array
     */

    protected function nodeidName(){
        //节点表中节点ID=>节点name
        $res_node_id_name = Node::find()->asArray()->all();
        $arr_node_id_name = array();
        if($res_node_id_name){
            foreach($res_node_id_name as $v){
                $arr_node_id_name[$v['id']] = $v['name'];
            }
        }
        return $arr_node_id_name;
    }

    /**
     * 拼接导出加速域名中开启高防的域名
     */
    protected  function exportDomainGf($source,$text_arr){
        $text_arr[]="\n";
        //判断国内外
        $registsource = $this->registsource_arr[$source];
        ### zjq
        $sql_g = "select id,cname,node_group,originip,ttl,high_anti from lc_domain where username in (SELECT username from lc_user where registsource = '$registsource') and high_anti >0 and status =2 and enable = 1";

        $res_g = $this->db->createCommand($sql_g)->queryAll();

        if(!empty($res_g)){
            //格式为： $cname:国外高防域名:60
            foreach($res_g as $v){
                $g_id = $v['high_anti'];
                $sql_defence = "select cname from lc_defence_ip where id=$g_id";
                $res_defence = $this->db->createCommand($sql_defence)->queryOne();
                if($res_defence){
                    $str_defence = $res_defence['cname'];
                }else{
                    $str_defence = $this->gfCname($source);
                }
                $text_arr[] = "C".$v['cname'].':'.$str_defence.':'.$v['ttl']."\n";
            }
        }
        return $text_arr;
    }

    /**
     * 拼接 集群 A记录
     */
    protected  function exportCluster($source,$text_arr){
        //集群
        $text_arr[]="\n";
        //$res_c = $this->nodeInfo;
        $res_c = $this->allNodeOn;
        $cname_second = $this->node_cname_arr[$source];

        foreach($res_c as $v){
            $name = '+'.$v['name'].$cname_second;
            $text_arr[] = $name.$v['ip'].":".$this->aTtl."\n";
            if($v['cluster'] !=0){
                $cluster = $v['cluster'];
                $ip = $v['ip'];
                $sql_clu = "select name,ip from lc_node where  cluster = '$cluster' and ip !='$ip' and (switch =$this->switch_on and forbidden =$this->forbidden_off)";
                $res_clu = $this->db->createCommand($sql_clu)->queryAll();
                if($res_clu){
                    foreach($res_clu as $v_clu){
                        $text_arr[] = $name.$v_clu['ip'].":".$this->aTtl."\n";
                    }
                }

            }
        }
        return $text_arr;
    }

    /**
     * 拼接 高防包
     */
    protected function exportDefence($source,$text_arr){
        //高防包
        $text_arr[]="\n";

        //针对自定义高防分组  ID=>cname
        $sql_defence_id_cname = "select id,cname from lc_defence_ip";
        $res_defence_id_cname = $this->db->createCommand($sql_defence_id_cname)->queryAll();

        $arr_defence_id_cname = array();
        foreach($res_defence_id_cname as  $v){
            $arr_defence_id_cname[$v['id']] = $v['cname'];
        }
        $registsource = $this->registsource_arr[$source];

        ### zjq
        $sql_defence_pocket = "select cname,originip,high_anti,node_group,sys_high_anti,sys_node_group from lc_defence where status =2 AND stype = 2 and user_id in (SELECT id from lc_user where registsource = '$registsource') union all select cname,originip,high_anti,node_group,sys_high_anti,sys_node_group from lc_domain where status =2 AND stype = 2 and user_id in (SELECT id from lc_user where registsource = '$registsource') ";

        $res_defence_pocket = $this->db->createCommand($sql_defence_pocket)->queryAll();

        //获取高防域名 lc_defence_ip
        $sql_defence = "select cname from lc_defence_ip where country = $source and remark =1";
        $res_defence = $this->db->createCommand($sql_defence)->queryOne();
        $str_defence = $res_defence['cname'];

        $originip_node = $this->originip_links;
        $arr_ip = $this->node_ip;

        if(!empty($res_defence_pocket)){
            //遍历
            //获取高防后端的所有IP()$arr_defence_ip = array('61.56.211.94','167.114.41.206');
            //判断ddos中的节点IP是否有高防后端IP（若有，则表名上次有切换高防，则判断其链接数是否大于最低阀值，大于则继续切换高防，小于则切回正常）
            //若没有则判断链接数是否大于触发阀值,大于则切换高防，小于则正常输出
            foreach($res_defence_pocket as $v){
                //判断high_anti 是否为0，若不为0则根据id 来查找arr_defence_id_cname中的值，若未找到，则当作是0来处理, sys_high_anti存在，使用后台设置的高防
                if($v['sys_high_anti'] == 0)
                    $defence_ip_id = $v['high_anti'];
                else
                    $defence_ip_id = $v['sys_high_anti'];

                if($defence_ip_id){
                    if(@$arr_defence_id_cname[$defence_ip_id]){
                        $str_defence = $arr_defence_id_cname[$defence_ip_id];
                    }
                }


                $cname_defence_pocket = $v['cname']; //高防包cname
                //echo $cname_defence_pocket;
               // $arr_node_id = explode(',',$v['nodeids']); //高防包所选择的节点（多个用逗号隔开）

                //todo zjq  后台设置分组存在，使用后台设置
                if($v['sys_node_group'] == 0)
                    $node_group_id = $v['node_group'];
                else
                    $node_group_id = $v['sys_node_group'];
                $sql_node_id_group = "select node_id from lc_node_group where id = " .$node_group_id;
                $res_node_id_group = $this->db->createCommand($sql_node_id_group)->queryOne();
                $arr_node_id = explode(',',$res_node_id_group['node_id']); //高防包所选择的节点（多个用逗号隔开）

                $aimurl = $v['originip'];  //高防包的回源IP，用于判断是ddos，及高防记录

                //遍历高防IP，判断是否在ddos中，只要其中一个有在即认为此回源IP在上次有切换高防
                $G = false;//表示未切换高防
                $defence_ip_link = array();
                $res_intersect = array();
                if(!empty($arr_defence_ip)) {
                    foreach ($arr_defence_ip as $v) {
                        if (@$originip_node[$aimurl][$v]) {
                            $G = true; //找到
                            //将所有链接数放入数组，计算最大的链接数
                            $defence_ip_link[$v] = $originip_node[$aimurl][$v];
                        }
                    }
                    $res_intersect  = array_intersect($arr_node_id,$arr_defence_ip);
                }
                //var_dump($defence_ip_link);die;
                //查询回源IP是否有记录（上一次）
                //var_dump(@$arr_defence_record[$aimurl]);die;

                if($G && !empty($defence_ip_link)  &&  empty($res_intersect)){
                    //有记录，找到其对应的节点
                    //找回源，对应节点的链接数，比较config_ddos_min ,大于则切换高防同时写入记录表,，小于则不切换
                    $defence_ip_link_bak = $defence_ip_link;
                    rsort($defence_ip_link);
                    $max_link = array_pop($defence_ip_link);
                    $defence_ip_max = array_search($max_link,$defence_ip_link_bak);
                    //echo $defence_ip_max;die;
                    //echo $max_link;echo $config_ddos_min;die;
                    if($max_link >$this->config_ddos_link_min){
                        // 最大链接大于最低阀值，切换高防
                        $text_arr_defence_pocket[] = 'C' . $cname_defence_pocket . ':' . $str_defence . ':'.$this->gfTtl . "\n";
                        $this->info_denfence_record .= "('" . $aimurl . "'," . "'" . $defence_ip_max . "'," . $this->now .",".$max_link ."),"; //高防记录
                    }else {
                        //小于最低阀值，则切回正常（若对应的所有节点关闭，则又切换高防）
                        //先判断是否所以高防对应的节点都关闭
                        $A = true; //默认true
                        $c = count($arr_node_id);
                        $i = 0;
                        foreach($arr_node_id  as $v_){
                            if(!@$arr_ip[$v_] || ($this->config_ddos_link_max <= @$originip_node[$aimurl][$arr_ip[$v_]])){
                                $i++;
                            }
                            if($i == $c){
                                $A = false;  //所有节点都关闭,或所有链接数大于触发值
                            }
                        }
                        foreach ($arr_node_id as $v_) {
                            //var_dump(@$arr_ip[$v_]);die;
                            if (@$arr_ip[$v_]) {

                                //此域名的源IP对应的节点是否在记录表中
                                //此域名的源IP对应的节点
                                //var_dump(@$originip_node[$aimurl][$arr_ip[$v_]]);die;
                                if (@$originip_node[$aimurl][$arr_ip[$v_]]) {
                                    //判断链接数
                                    //var_dump($originip_node[$aimurl][$arr_ip[$v_]]);die;
                                    if ($this->config_ddos_link_max <= $originip_node[$aimurl][$arr_ip[$v_]]) {
                                        //大于触发值，切换高防，并记录
                                        if(!$A) {
                                            $text_arr_defence_pocket[] = 'C' . $cname_defence_pocket . ':' . $str_defence . ':' . $this->gfTtl . "\n";
                                            $this->info_denfence_record .= "('" . $aimurl . "'," . "'" . $arr_ip[$v_] . "'," . $this->now .",".$originip_node[$aimurl][$arr_ip[$v_]]. "),"; //高防记录
                                            break;
                                        }
                                    } else {
                                        $A =true;
                                        $text_arr_defence_pocket[] = '+' . $cname_defence_pocket . ':' . $arr_ip[$v_] . ':'.$this->gfTtl . "\n";
                                    }
                                } else {
                                    $A = true;
                                    $text_arr_defence_pocket[] = '+' . $cname_defence_pocket . ':' . $arr_ip[$v_] . ':'.$this->gfTtl . "\n";
                                }
                            } else {
                                //所有节点都关闭
                                if(!$A) {
                                    $text_arr_defence_pocket[] = 'C' . $cname_defence_pocket . ':' . $str_defence . ':' . $this->gfTtl . "\n";
                                    $this->info_denfence_record .= "('" . $aimurl . "'," . "'" . $arr_ip[$v_] . "'," . $this->now .",".'0'. "),"; //高防记录
                                    break;
                                }
                            }
                        }
                    }
                }else{
                    //没有记录
                    //遍历回源对应的所有节点
                    //根据对应的链接数，比较config_ddos_trigger,大于等于则切换高防同时写入记录表中，小于则不切换
                    //先判断是否所以高防对应的节点都关闭
                    $A = true; //默认true
                    $c = count($arr_node_id);
                    $i = 0;
                    foreach($arr_node_id  as $v_){
                        if(!@$arr_ip[$v_] || ($this->config_ddos_link_max <= @$originip_node[$aimurl][$arr_ip[$v_]])){
                            $i++;
                        }
                        if($i == $c){
                            $A = false;  //所有节点都关闭,或所有链接数大于触发值
                        }
                    }

                    foreach($arr_node_id as $v_){
                        if(@$arr_ip[$v_]){
                            //此域名的源IP对应的节点
                            if(@$originip_node[$aimurl][$arr_ip[$v_]]){
                                //判断链接数
                                //var_dump($originip_node[$aimurl][$arr_ip[$v_]]);
                                if($this->config_ddos_link_max <= $originip_node[$aimurl][$arr_ip[$v_]]){
                                    //大于触发值，切换高防，并记录,前提没有A记录
                                    if(!$A) {
                                        $text_arr_defence_pocket[] = 'C' . $cname_defence_pocket . ':' . $str_defence . ':' . $this->gfTtl . "\n";
                                        $this->info_denfence_record .= "('" . $aimurl . "'," . "'" . $arr_ip[$v_] . "'," . $this->now .",".$originip_node[$aimurl][$arr_ip[$v_]]. "),"; //高防记录
                                        break;
                                    }
                                }else{
                                    $A = true;
                                    $text_arr_defence_pocket[]='+'.$cname_defence_pocket.':'.$arr_ip[$v_].':'.$this->gfTtl."\n";
                                }
                            }else{
                                $A = true;
                                $text_arr_defence_pocket[] = '+' . $cname_defence_pocket . ':' . $arr_ip[$v_] . ':'.$this->gfTtl . "\n";
                            }
                        }else{
                            //未找到对应的节点IP（说明节点关闭或者禁止），切换高防，前提没有A记录
                            if(!$A) {
                                $text_arr_defence_pocket[] = 'C' . $cname_defence_pocket . ':' . $str_defence . ':'.$this->gfTtl . "\n";
                                $this->info_denfence_record .= "('" . $aimurl . "'," . "'" . @$arr_ip[$v_] . "'," . $this->now .",".'0'. "),"; //高防记录
                                break;
                            }else{
                                continue;
                            }
                        }
                    }
                }
            }
            $text_arr = array_merge($text_arr,$text_arr_defence_pocket);
        }
        $info_denfence_record = rtrim($this->info_denfence_record,',');

        //写入 高防记录
        if($info_denfence_record !='') {
            $sql_insert_record = "insert into lc_defence_record (aimurl,node_ip,time,link) VALUES $info_denfence_record";
            $this->db->createCommand($sql_insert_record)->execute();
        }
        return $text_arr;
    }

    /**
     * 将回源IP，节点IP，链接数组成二维数，前两项为键名回源i、节点ip。链接数为键值
     *
     * Array[
    218.32.211.11：源IP
    [218.32.211.11] => Array(	122.147.47.187:节点IP    3：链接数
    [122.147.47.187] => 3
    [112.5.196.227] => 40
    )
    ]
     *
     * @return array
     */

    protected function ddosLink(){
        //查询链接数大于等于$config_ddos的所有源IP及对应的节点，放入数组中
        $sql_ddos = "select ip,node_ip,link from lc_ddos";
        $res_ddos = $this->db->createCommand($sql_ddos)->queryAll();
        $originip_node = array();
        if($res_ddos){
            foreach($res_ddos as $v){
                $originip_node[$v['ip']][$v['node_ip']] = $v['link'];
            }
        }
        return $originip_node;
    }

    /*
返回所有节点开启的节点信息（不包括禁止）
*/
    protected function getAllNodes($country){

        $sql_c = "select id,name,ip,cluster from lc_node where switch = $this->switch_on  order by weight desc";
        $res_c = $this->db->createCommand($sql_c)->queryAll();
        return $res_c;

    }


    /**
     *
     * @param $country
     * @return array
     * @throws \yii\db\Exception
     */
    protected function getAllNodeGroup($country){
        $result = NodeGroup::find()->where(['type'=>$country])->select('id,node_id')->asArray()->all();
        $result = ArrayHelper::map($result,'id','node_id');
        return $result;

    }

}
