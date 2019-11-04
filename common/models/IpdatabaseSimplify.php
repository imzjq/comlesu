<?php

namespace common\models;

use common\lib\Utils;
use Yii;

/**
 * This is the model class for table "{{%ipdatabase_simplify}}".
 *
 * @property string $id
 * @property string $ip
 * @property string $ipduan ip段
 * @property int $group_id 自定义所属分组ID
 */
class IpdatabaseSimplify extends \yii\db\ActiveRecord
{
//    public static function getDb()
//    {
//        return Yii::$app->get('dbIpku');
//    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ipdatabase_simplify}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'ipduan','group_id'], 'required'],
            [['group_id'], 'integer'],
            [['ip', 'ipduan'], 'string', 'max' => 30],
            [['ip'], 'ipCheck'],
            ['ip', 'unique'],
            [['ipduan'], 'ipduanCheck'],
        ];
    }

    public function ipCheck($attribute, $params)
    {
        $ip = $this->ip;
        $res = Utils::isIp($ip);
        if(!$res){
            $this->addError($attribute, "ip 格式不正确.");
        }
    }

    public function ipduanCheck($attribute, $params)
    {
        $ipduan = $this->ipduan;
        $ipduan_arr = explode(':',$ipduan);
        if(!is_array($ipduan_arr)){
            $this->addError($attribute, "ipduan 格式不正确.");
        }else{
            $ip = $ipduan_arr[0];
            $res = Utils::isIp($ip);
            if(!$res){
                $this->addError($attribute, "ipduan 格式错误.");
        }
            $ym = $ipduan_arr[1];
            if($ym > 32 || $ym <1){
                $this->addError($attribute, "掩码格式不正确.");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'ipduan' => 'Ipduan',
            'group_id' => 'Group ID',
        ];
    }

    public  function ipGetGroupId($ip){
        //return $ip;
        $arr_sar = explode('.',$ip);
        $ip_1 = $arr_sar['0'].".";
        $ip_long = bindec(decbin(ip2long($ip)));

        //$sql_ipku = "select * from lc_ipdatabase_simplify where  ipduan like '$ip_1%'";
        //$res = $this->conn->createCommand($sql_ipku)->queryAll();
        $res = IpdatabaseSimplify::find()->where(['like','ipduan',$ip_1."%",false])->all();
        if(!$res){
            return 0;
        }else{
            $ipp_arr =array();
            foreach($res as $rows_ipku){
                $ip_addr_ipku = $rows_ipku['ip'];
                $ipduan_ipku = $rows_ipku['ipduan'];
                $arr_ipku = explode(':',$ipduan_ipku);

                $y_ipku = $arr_ipku[1];
                $net_ipku =$this->getSubnet($ip_addr_ipku,$y_ipku);
                if(!($net_ipku > $ip_long)){
                    $ipp_arr[]=$ipduan_ipku;
                }
            }
            //v($ipp_arr);die;
            if(empty($ipp_arr)){
                return 0;
            }else{
                $num = count($ipp_arr);
                foreach($ipp_arr as $k=> $v) {
                    $arr = explode(':', $v);
                    $ip_addr = $arr[0];
                    $y = $arr[1];
                    //$net = $this->getSubnet($ip_addr, $y);
                    $subnet_mask =$this->getBroadcast($ip_addr, $y);
                    if($ip_long < $subnet_mask){
                        //$sql ="select group_id from lc_ipdatabase_simplify where  ipduan = '$v'";
                        //$row =$this->conn->createCommand($sql)->queryRow();
                        $row = IpdatabaseSimplify::find()->where(['ipduan'=>$v])->one();
                        $group_id = $row['group_id'];
                        return $group_id;
                    }
                    if($k+1 == $num){
                        return 0;
                    }
                }
            }
        }
    }


    public  function ipGetGroup($ip){
        //return $ip;
        $arr_sar = explode('.',$ip);
        $ip_1 = $arr_sar['0'].".";
        $ip_long = bindec(decbin(ip2long($ip)));

        //$sql_ipku = "select * from lc_ipdatabase_simplify where  ipduan like '$ip_1%'";
        //$res = $this->conn->createCommand($sql_ipku)->queryAll();
        $res = IpdatabaseSimplify::find()->where(['like','ipduan',$ip_1."%",false])->all();
        if(!$res){
            return 0;
        }else{
            $ipp_arr =array();
            foreach($res as $rows_ipku){
                $ip_addr_ipku = $rows_ipku['ip'];
                $ipduan_ipku = $rows_ipku['ipduan'];
                $arr_ipku = explode(':',$ipduan_ipku);

                $y_ipku = $arr_ipku[1];
                $net_ipku =$this->getSubnet($ip_addr_ipku,$y_ipku);
                if(!($net_ipku > $ip_long)){
                    $ipp_arr[]=$ipduan_ipku;
                }
            }
            //v($ipp_arr);die;
            if(empty($ipp_arr)){
                return 0;
            }else{
                $num = count($ipp_arr);
                foreach($ipp_arr as $k=> $v) {
                    $arr = explode(':', $v);
                    $ip_addr = $arr[0];
                    $y = $arr[1];
                    //$net = $this->getSubnet($ip_addr, $y);
                    $subnet_mask =$this->getBroadcast($ip_addr, $y);
                    if($ip_long < $subnet_mask){
                        //$sql ="select group_id from lc_ipdatabase_simplify where  ipduan = '$v'";
                        //$row =$this->conn->createCommand($sql)->queryRow();
                        $row = IpdatabaseSimplify::find()->where(['ipduan'=>$v])->asArray()->one();
                        //$group_id = $row['group_id'];
                        return $row;
                    }
                    if($k+1 == $num){
                        return 0;
                    }
                }
            }
        }
    }


    public function ipGetIds($ip)
    {
        if($ip)
        {
            $arr_sar = explode('.', $ip);
            $ip_1 = $arr_sar['0'] . ".";
            $ip_long = ip2long($ip);
            $res = IpdatabaseSimplify::find()->where(['like','ipduan',$ip_1."%",false])->asArray()->all();
            foreach ($res as $v) {
                $reslist_3[] = $v['id'];
                list($ip_1, $mark, $ip_start, $ip_end) = self::ip_parseint($v['ipduan']);
                $reslist_1[] = $ip_start;
                $reslist_2[] = $ip_end;
            }
           $id = [];
            for ($i = 0; $i < count($reslist_1); $i++) {
                if ($reslist_1[$i] < $ip_long and $ip_long < $reslist_2[$i]) {
                    $id []= $reslist_3[$i];
                }
            }
          return $id ;
        }else{
            return "";
        }
    }

    public function checkGroup($ip)
    {
        $res = Utils::isIp($ip);
        if(!$res)
            return false;

        $arr_sar = explode('.',$ip);
        $ip_1 = $arr_sar['0'].".";
        $ip_long = bindec(decbin(ip2long($ip)));

        $res = IpdatabaseSimplify::find()->where(['like','ipduan',$ip_1."%",false])->asArray()->all();

        if(!$res){
            return false;
        }
        $ipp_arr =array();
        foreach($res as $rows_ipku){
            $ip_addr_ipku = $rows_ipku['ip'];
            $ipduan_ipku = $rows_ipku['ipduan'];
            $arr_ipku = explode(':',$ipduan_ipku);
            $y_ipku = $arr_ipku['1'];
            $net_ipku =$this->getSubnet($ip_addr_ipku,$y_ipku);

            if(!($net_ipku > $ip_long)){
                $ipp_arr[]=$ipduan_ipku;
            }
        }
        //v($ipp_arr);die;
        if(empty($ipp_arr)){
            return false;
        }

        $num = count($ipp_arr);

        foreach($ipp_arr as $k=> $v) {
            $arr = explode(':', $v);

            $ip_addr = $arr[0];

            $y = $arr['1'];

            //$net = $this->getSubnet($ip_addr, $y);

            $subnet_mask =$this->getBroadcast($ip_addr, $y);

            if($ip_long < $subnet_mask){

//                $sql ="select group_id from lc_ipdatabase_simplify where ipduan = '$v'";
//                $row =$this->conn->createCommand($sql)->queryOne();

                $row = IpdatabaseSimplify::find()->select('group_id')->where(['ipduan'=>$v])->asArray()->one();
                $group_id = $row['group_id'];
                //$sql_area = "select * from lc_iparea where id = $group_id";
                $row = Iparea::find()->where(['id'=>$group_id])->asArray()->one();
                //$row =$this->conn->createCommand($sql_area)->queryOne();
                return $row;
            }

            if($k+1 == $num){
                return false;
            }

        }


    }

    public function getSubnet($ip, $mask){

        $bin_ip = str_pad(decbin(ip2long($ip)),32,'0',STR_PAD_LEFT);
        $msk =$this->mask2bin($mask);

        return bindec($bin_ip & $msk);
    }

    public function getBroadcast($ip, $mask){
        $bin_ip = str_pad(decbin(ip2long($ip)),32,'0',STR_PAD_LEFT);
        $msk =$this->mask2bin($mask);

        return bindec($bin_ip | $this->revBin($msk));
    }

    public function isIp($str) {
        $ip = explode(".", $str);
        if (count($ip) < 4 || count($ip) > 4) return FALSE;
        foreach($ip as $ip_addr) {
            if ( !is_numeric($ip_addr) ) return FALSE;
            if ( $ip_addr < 0 || $ip_addr > 255 ) return FALSE;
        }
        return (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/is", $str));
    }


    public function mask2bin($n){
        $n = intval($n);
        if($n < 0 || $n > 32) return FALSE;
        return str_repeat( "1",$n).str_repeat( "0",32-$n);
    }


    public function revBin($s)   {
        $p = array('0','1','2');
        $r = array('2','0','1');
        return  str_replace($p,$r,$s);
    }

    public  function ip_parse($ip_str) {
        if (strpos($ip_str, ":") > 0) {
            list($ip_str, $mark_len) = explode(":", $ip_str);
            $ip = ip2long($ip_str);
            $mark = 0xFFFFFFFF << (32 - $mark_len) & 0xFFFFFFFF;
            $ip_start = $ip & $mark;
            $ip_end = $ip | (~$mark) & 0xFFFFFFFF - 1;
            $ip_start = long2ip($ip_start);
            $ip_end = long2ip($ip_end);
            return array($ip_start, $ip_end);
        }else{
            return false;
        }
    }

    function ip_parseint($ip_str) {
        $mark_len = 32;
        if (strpos($ip_str, ":") > 0) {
            list($ip_str, $mark_len) = explode(":", $ip_str);
        }
        $ip = ip2long($ip_str);
        $mark = 0xFFFFFFFF << (32 - $mark_len) & 0xFFFFFFFF;
        $ip_start = $ip & $mark;
        $ip_end = $ip | (~$mark) & 0xFFFFFFFF;
        return array($ip, $mark, $ip_start, $ip_end);
    }
}
