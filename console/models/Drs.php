<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/12
 * Time: 22:33
 */

namespace console\models;


use common\lib\FileUtil;
use common\lib\Utils;
use common\models\Drsd;
use yii\base\Model;
use Yii;
class Drs extends Model
{

    protected $area;
    protected $path ='';
    protected $filename;

    public function __construct($area){
        $this->area = $area;
        $this->path = \Yii::getAlias('@dns_file').'/drs/';
        $this->filename = $area.'.txt';
        //保持原来不变
        /*
        switch($area){
            case 'lesucdn':
                $this->filename = 'ICP.drs.txt';
                break;
            case 'cdnunions':
                $this->filename = 'drs.txt';
                break;
            case 'dnsunions':
                $this->filename = 'gf.txt';
                break;
            default:
                $this->filename = $area.'.txt';
        }
        */
    }


    public function exportData(){
        $content = '';
        $arrayEx = array();
        $sql_drsd = "select drsd.id,drsd.dname from lc_drsd AS drsd JOIN lc_user AS users ON drsd.username = users.username WHERE drsd.status = 1 AND users.registsource = '$this->area' AND drsd.high_anti != '1' order by drsd.id ASC";
        $sql_example = "select id,example FROM lc_dns_example WHERE area = '$this->area'";

        $dDatas = Yii::$app->db->createCommand($sql_drsd)->queryAll();
        $example = Yii::$app->db->createCommand($sql_example)->queryAll();
        //域名验证
        $patter = '/^[0-9a-zA-Z*]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,6}$/';
        if(!empty($dDatas)){

            foreach ($dDatas as $dkey => $dData) {
                //域名过滤
                $dData['dname'] = trim($dData['dname']);
                if(preg_match($patter,$dData['dname'])){
                    foreach ($example as $key => $Exm) {
                        $content .= "." . $this->get_host_domain($dData['dname']) . "::" . $Exm['example'] . ":14400\n";
                    }
                    unset($key);
                    unset($Exm);
                    $content .= $this->_get_export_single($dData['id']);
                }
            }
            unset($dDatas);
            unset($example);
            $result = Utils::contentCompare($this->path.$this->filename,$content);
            if($result) {
                    file_put_contents($this->path.$this->filename,$content);
              //  FileUtil::rmFile($this->path.$this->filename);
               // FileUtil::createFile($this->path, $this->filename, $content);
            }
        }else
        {
            file_put_contents($this->path.$this->filename,$content);
            // FileUtil::rmFile($this->path.$this->filename);
        }

    }



    public function get_host_domain($url){
        $data = parse_url($url);
        if(isset($data['host'])){
            $data = $data['host'];
        }elseif(isset($data['path'])){
            $data = $data['path'];
        }
        $data = explode('.', $data);
        $dataurl = $data[count($data) - 2] . '.' . $data[count($data) - 1];
        if($dataurl == 'com.cn' || $dataurl == 'net.cn' || $dataurl == 'org.cn'|| $dataurl == 'gov.cn' ){
            $dataurl = $data[count($data) - 3].'.'.$dataurl;
        }
        return $dataurl;
    }


    public function _get_export_single($id){
        $rrPrefixArr = array('A'=>'=','CNAME'=>'C','MX'=>'@','NS'=>'&','TXT'=>"'",'AAAA'=>'6','REDIRECT_URL'=>'^','FORWARD_URL'=>'%');
        $content = "";
        if( !is_numeric($id) ){
            die( 'illegal operation' );
        }
        $datas = array();

        //$sql = "select * from lc_drs where did=".$id." ORDER BY rrtype DESC";
        //$datas = $this->db->fetchAll($sql);
        $datas = \common\models\Drs::find()->where(['did'=>$id])->orderBy(['rrtype'=>SORT_DESC])->asArray()->all();
        if( !empty($datas) ){
            foreach( $datas as $key=>$data ){
                $data['rr'] = $data['rr']=='@'?'':$data['rr'];
                $data['dname'] = trim($data['dname']);
                if($data['rrtype'] == 'MX'){
                    $content .= $rrPrefixArr[$data['rrtype']].$data['rr'].($data['rr']==''?'':'.').$data['dname']."::".$data['rval'].':'.$data['mx'].':'.$data['ttl']."\n";
                }else{
                    $content .= $rrPrefixArr[$data['rrtype']].$data['rr'].($data['rr']==''?'':'.').$data['dname'].":".$data['rval'].':'.$data['ttl']."\n";
                }

            }
            unset($key);unset($data);
        }
        if($content != ''){
            $content .= "\n";
        }
        return $content;
    }



    public function createDir($path){
        if ( !file_exists ( $path ) ){
            if( @mkdir($path,0777) )
                return true;
            else
                error_log('exportDrs.php ERROR :  Failed to create the directory.',0);
        }
    }

    public function createFile($path,$filename,$content){

        $this->createDir($path);
        $filename = $path.$filename;
        if( !file_exists($filename) ) {
            // create file
            @fopen($filename, "w");
        }

        if( is_writable($filename) ){
            if( !$handle = fopen($filename,"a") ){
                error_log('exportDrs.php ERROR : The file cannot be opened.',0);
            }
            if($content != ''){
                if( !fwrite($handle,$content) ){
                    error_log('exportDrs.php ERROR : The file is not writable.',0);
                }
            }
            fclose($handle);
            return true;
        }else{
            error_log('exportDrs.php ERROR : The file is not writable.',0);
        }
    }

    public function rmFile($filename){
        if ( file_exists($filename) ) {
            if ( unlink($filename) ){
                return true;
            }else
                error_log('exportDrs.php ERROR : delete file failed.',0);
        }
    }



}
