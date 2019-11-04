<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/19
 * Time: 12:40
 */

namespace console\controllers;


use backend\models\Domain;
use common\lib\Utils;
use common\models\Defence;
use common\models\DefenceRemap;
use common\models\Remap;
use common\components\Logger;

class RemapController extends BaseController
{

    public $logType = 'remap';
    public static $blackIp;
    public function actionIndex(){
        $start = $this->getTime();
        self::$blackIp = $this->blackIp();
        $domainInfo = Domain::find()->select('id,dname,user_id,package_id')->where(['status'=>2])->andWhere(['enable'=>1])->asArray()->all();
        $patterDomain = '/^[0-9a-zA-Z*]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,6}$/';
        $content_remap = [];
        if($domainInfo){
            foreach ($domainInfo as $key => $data) {
                if (preg_match($patterDomain, $data['dname'])) {
                    $content_remap = $this->_origin_domain($data['id'],$data['dname'],$content_remap,$data);
                }else{
                    //记录日志，域名格式错误
                    $err = $data['dname']. ' 域名格式匹配错误';
                    //Logger::factoryLog($err,$this->logType);
                    //Logger::warning($err);
                }
            }
            unset($key);
            unset($data);
        }
        $content_remap[] = "\n";
        $defenceInfo = Defence::find()->select('id,,user_id,package_id')->where(['status'=>2,'enable'=>1])->asArray()->all();
        if($defenceInfo){
            foreach ($defenceInfo as $key => $data) {
                    $content_remap = $this->_origin_defence($data['id'],$content_remap,$data);
            }
            unset($key);
            unset($data);
        }




        //导出
        $this->export($content_remap);
        $end = $this->getTime();
        $content = ' exec remap '.$this->costTime($start,$end);
        //Logger::factoryLog($content,$this->logType);
        echo 'success'."\n";
    }

    protected  function _origin_domain($did,$dname,$content_remap,$data)
    {
        $remapInfo = Remap::find()->where(['did'=>$did])->all();
        if($remapInfo){
            foreach ($remapInfo as $v){

                //添加验证(回源地址)
                $pattIP = '/^((([0-9a-zA-Z]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,4})|((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9]))))|\:[0-9]{2,5}$/';
                if(!preg_match($pattIP, $v->aimurl) && !Utils::isUrl($v->aimurl) ){
                    continue;
                }

                $aimport = $v->aimport;
                $originport = $v->originport;

                if($aimport && $aimport !=80){
                    //$aimport = ':'.$aimport.' ';
                    $aimport = ':'.$aimport.'/';
                }else{
                    $aimport = '/';
                }
                if($originport && $originport !=80){
                    //$originport = ':'.$originport.' ';
                    $originport = ':'.$originport.'/ ';
                }else{
                    $originport = '/ ';
                }


               // if($v->is_at || $v->originurl=='@'){
                if( $v->originurl=='@'){
                    //@ 回原
                    if($v->redirect_ssl) {
                        $text =   $v->visit_protocol . $dname  ;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "redirect " . $v->visit_protocol . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result ."\n";
                    }
                    else {
                        $text =   $v->visit_protocol . $dname ;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "map " . $v->visit_protocol . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result. "\n";
                    }
                    continue;
                }
                if($v->originurl == '*')
                    $v->originurl = '(.*)';

                //301
                if($v->redirect_ssl){
                    if($v->originurl ==  '(.*)') {
                        $text =    $v->visit_protocol . $v->originurl.'.' .$dname  ;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "regex_redirect " . $v->visit_protocol . $v->originurl . '\\.' .  str_replace(".","\\.",$dname) . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result ."\n";
                    }
                    else {
                        if($v->originurl != '') {
                            $text =   $v->visit_protocol . $v->originurl . '.' . $dname ;
                            $result = $this->_black_domain($text,$data);
                            $content_remap[] = "redirect " . $v->visit_protocol . $v->originurl . '.' . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result. "\n";
                        }else{
                            $text =    $v->visit_protocol . $dname ;
                            $result = $this->_black_domain($text,$data);
                            $content_remap[] = "redirect " . $v->visit_protocol . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result. "\n";
                        }
                    }
                    continue;
                }

                if( $v->originurl ==='[a-z0-9]' || $v->originurl == '(.*)' || $v->originurl == '[0-9a-z]' ){
                    $text =    $v->visit_protocol .$v->originurl .'.'.$dname;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "regex_map " . $v->visit_protocol .$v->originurl .'\\.'. str_replace(".","\\.",$dname) . $originport. $v->origin_protocol . $v->aimurl.$aimport.$result. "\n";
                    continue;
                }

                if($v->originurl != '') {
                    $text = $v->visit_protocol . $v->originurl . '.' . $dname;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "map " . $v->visit_protocol . $v->originurl . '.' . $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport . $result . "\n";
                }else{
                    $text = $v->visit_protocol .  $dname;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "map " . $v->visit_protocol .  $dname . $originport . $v->origin_protocol . $v->aimurl . $aimport .$result. "\n";
                }


            }
        }

        return $content_remap;
    }

    public function _black_domain($remap,$data)
    {

        $result = "";
        if(self::$blackIp) {
            if(isset(self::$blackIp[$data['user_id']][$data['package_id']][$remap]))
            {
                $result .=" @action=deny";
                foreach (self::$blackIp[$data['user_id']][$data['package_id']][$remap] as $val)
                {
                    $result .= " @src_ip=".$val;
                }
            }
        }
        return $result;
    }

    public function _origin_defence($did,$content_remap,$data){

        $remapData = DefenceRemap::find()->where(['did'=>$did])->asArray()->all();
        if(!empty($remapData)){
            foreach ($remapData as $v){
                //添加验证(回源地址)
                $pattIP = '/^((([0-9a-zA-Z]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,4})|((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9]))))|\:[0-9]{2,5}$/';
                if(!preg_match($pattIP, $v['aimurl']) && !Utils::isUrl($pattIP)  ){
                    continue;
                }
                $originurl = $v['originurl'];
                $visit_protocol = $v['visit_protocol'];
                $redirect_ssl = $v['redirect_ssl'];
                $originport = isset($v['originport'])?$v['originport']:'';
                $origin_protocol = $v['origin_protocol'];
                $aimurl = $v['aimurl'];
                $aimport = $v['aimport'];
                if($aimport && $aimport != '80'){
                    $aimport = ':'.$aimport.'/';
                }else{
                    $aimport = '/';
                }
                if($originport  && $originport !=80 ){
                    $originport = ':'.$originport.'/ ';
                }else{
                    $originport = '/ ';
                }

                if($originurl == '*')
                    $originurl = '(.*)';
                if($originurl=='@'){
                    //map
                    if($redirect_ssl) {
                        $text = $visit_protocol . $originurl ;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "redirect " . $visit_protocol . $originurl . $originport . $origin_protocol . $aimurl . $aimport .$result. "\n";
                    }
                    else {
                        $text =  $visit_protocol . $originurl ;
                        $result = $this->_black_domain($text,$data);
                        $content_remap[] = "map " . $visit_protocol . $originurl . $originport . $origin_protocol . $aimurl . $aimport .$result. "\n";
                    }
                    continue;
                }

                if($redirect_ssl){
                    //301跳转
                    $text =  $visit_protocol .$originurl ;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "redirect " . $visit_protocol .$originurl . $originport . $origin_protocol . $aimurl.$aimport . $result . "\n";
                    continue;
                }

                if($originurl=='(.*)' || $originurl=='[a-z0-9]' || $originurl=='[0-9a-z]' ){
                    $text = $visit_protocol .$originurl  ;
                    $result = $this->_black_domain($text,$data);
                    $content_remap[] = "map " . $visit_protocol .$originurl . $originport . $origin_protocol . $aimurl.$aimport.$result. "\n";
                    continue;
                }
                $text = $visit_protocol .$originurl  ;
                $result = $this->_black_domain($text,$data);
                $content_remap[] = "map " . $visit_protocol .$originurl . $originport . $origin_protocol . $aimurl.$aimport.$result. "\n";
            }
        }

        return $content_remap;
    }

    public function blackIp()
    {
        $result = [];
        $data = \common\models\BlackIp::find()->select('user_id,package_id,domain,ip')->asArray()->all();
        if($data)
        {
            foreach ($data as $key=>$val)
            {
                if($val['domain']) {
                    $domain = unserialize($val['domain']);
                    foreach ($domain as $d) {
                        //$result[$val['user_id']][$val['package_id']][$d][] = $val['ip'];
                        $result[$val['user_id']][$val['package_id']][$d][] = $val['ip'];
                    }
                }
            }
        }
        return $result ;
    }

    protected function export($content){

        $dir = \Yii::getAlias('@dns_file').'/remap/';
        $filename = $dir.'remap.config';
        $result = Utils::fileIsUpdate($filename,$content);
        if($content)
            $content[] ='#end##';
        if($result) {
            file_put_contents($filename, $content);
        }
    }

}
