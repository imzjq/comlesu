<?php

/**
 * Class FileUtil
 *
 * 文件操作帮助类
 */
namespace common\lib;
class FileUtil{

    //public $path;
    //public $filename;
    //public $result;
    public function __construct(){

    }
    // 创建目录
    public static function createDir($path){
        if ( !file_exists ( $path ) ){
             if( @mkdir($path,0777) )
                return true;
             else
                 throw new CHttpException(404, '创建目录失败');
        }
    }

    // 写入文件
    public static function createFile($path, $filename, $content){
        self::createDir($path);
        $filename = $path.$filename;
        // 判断文件是否存在
        if( !file_exists($filename) ) {
            // 如果文件不存在,则创建文件
            @fopen($filename, "w");
        }
        // 判断文件是否可写
        if( is_writable($filename) ){
            // 打开文件以添加方式即"a"方式打开文件流
            if( !$handle = fopen($filename,"a") ){
                throw new CHttpException(404, '文件不可打开');
            }
            if( !fwrite($handle,$content) ){
                throw new CHttpException(404, '文件不可写');
            }
            // 关闭文件流
            fclose($handle);
            return true;
        }else{
            throw new CHttpException(404, '文件不可写');
        }
    }

    public static function rmFile($filename){
        // 如果文件存在就删除文件
        if ( file_exists($filename) ) {
            if ( unlink($filename) ){
                return true;
            }else
                throw new CHttpException(404, '删除原文件失败');
        }
    }


    public static function  deldir($path){
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($path.$val)){
                        //子目录中操作删除文件夹和文件
                        deldir($path.$val.'/');
                        //目录清空后删除空文件夹
                        @rmdir($path.$val.'/');
                    }else{
                        //如果是文件直接删除
                        unlink($path.$val);
                    }
                }
            }
            rmdir($path);
        }
    }


    public static function rename($filename,$newFilenam)
    {
        if(file_exists($filename))
            rename($filename,$newFilenam);
    }

    public static function  getDir($dir)
    {
        $handler = opendir($dir);
        $files = array();
        while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != ".." && $filename != 'remap.config' && $filename != 'rules_usr.conf'  && $filename != 'black_country.txt'  && $filename != 'usr_white.txt'  && $filename != 'ssl_multicert.config' ) {
                $files[] = $filename ;
            }
        }
        closedir($handler);
        return $files;
    }

}
