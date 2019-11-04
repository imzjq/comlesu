<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 23:08
 */

namespace common\lib;


class Utils
{

    /**
     * 判断字符串是否为IP
     * @param $str
     * @return bool|false|int
     */
    static function isIp($str){
        $ip = explode(".", $str);
        if (count($ip) < 4 || count($ip) > 4) return FALSE;
        foreach($ip as $ip_addr) {
            if ( !is_numeric($ip_addr) ) return FALSE;
            if ( $ip_addr < 0 || $ip_addr > 255 ) return FALSE;
        }
        return (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/is", $str));
    }


    /**
     * 判断字符串是否是域名
     * @param $str
     */
    static function isUrl($str)
    {
        return  preg_match('/^[0-9a-zA-Z*]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,6}$/',$str);
    }

    static function log($type,$content){


    }

    /**
     * @param $oarr 老数组
     * @param $narr 新数组
     */
    static function arrayNewDel($oarr,$narr)
    {
        $diffInsert =  array_diff($narr,$oarr);//新增
        $diffDel = array_diff($oarr,$narr);//删除
        $arr = array_filter(array_merge($diffDel,$diffInsert));
        return $arr;
    }

    static function varDump($string)
    {
        echo "<pre>";
        var_dump($string);
        echo "</pre>";
    }

    static function fileIsUpdate($filename,$content)
    {
        if(file_exists($filename)) {
            $str = file_get_contents($filename);
            $text = '';
            if($content){
                foreach ($content as $val)
                    $text .=$val;
            }
            if($text == $str)
                return false ;
        }
        return true;
    }

    static function contentCompare($filename,$content)
    {
        if(file_exists($filename)) {
            $str = file_get_contents($filename);
            if($content == $str)
                return 0 ;
        }
        return true;
    }

    /**
     *获取无前缀url
     * @param $url
     */
    static function getUrlHost($url ,$length = 2)
    {
        $res = '';
        if(empty($url))
            return $res;
        $urlArr = explode('.',$url);
        $count = count($urlArr) ;
        if( $count < $length)
            return $res;

        $j = 0;
        for ($i = $count -1 ; $i >= 0 ; $i -- )
        {
            $j ++ ;
            if($j == 2) {
                $res = $urlArr[$i] .'.'.$res;
                return $res;
            }
            $res .= $urlArr[$i];

        }
    }

}
