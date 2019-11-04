<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/17
 * Time: 21:41
 */

namespace common\components;


use Yii;
use yii\base\BaseObject;
use yii\di\ServiceLocator;

class Logger extends BaseObject
{

    //redis list 保持的最大长度
    public static $maxLen = 500;
    public static $routingKey;
    public static $remapRedisKey;
    public static $drsRedisKey;
    public static $zabbixApiRedisKey;
    public static $pingApiRedisKey;
    public static $dnsServerRedisKey;

    public static function setKey(){
        self::$routingKey =  Yii::$app->params['routingRedisKey'];
        self::$remapRedisKey =  Yii::$app->params['remapRedisKey'];
        self::$drsRedisKey =  Yii::$app->params['drsRedisKey'];
        self::$zabbixApiRedisKey =  Yii::$app->params['zabbixApiRedisKey'];
        self::$pingApiRedisKey =  Yii::$app->params['pingApiRedisKey'];
        self::$dnsServerRedisKey =  Yii::$app->params['dnsServerRedisKey'];
    }

    public static function factoryLog($content,$type){
        self::setKey();
        switch ($type){
            case 'routing':
                self::routingLog($content);
                break;
            case 'remap':
                self::remapLog($content);
                break;
            case 'drs':
                self::drsLog($content);
                break;
            case 'zabbix':
                self::zabbixLog($content);
                break;
            case 'pingApi':
                self::pingApiLog($content);
                break;
            case 'dnsServer':
                self::dnsServerLog($content);
                break;
        }
    }


    public static function factoryLogGet($type){
        self::setKey();
        $data = [];
        switch ($type){
            case 'routing':
                $data = self::getLog(self::$routingKey);
                break;
            case 'remap':
                $data = self::getLog(self::$remapRedisKey);
                break;
            case 'drs':
                $data = self::getLog(self::$drsRedisKey);
                break;
            case 'zabbix':
                $data = self::getLog(self::$zabbixApiRedisKey);
                break;
            case 'pingApi':
                $data = self::getLog(self::$pingApiRedisKey);
                break;
            case 'dnsServer':
                $data = self::getLog(self::$dnsServerRedisKey);
                break;
        }

        return $data;
    }




    //routing 日志记录
    public static function routingLog($content){
        self::logRecord(self::$routingKey,$content);
    }

    //remap 日志记录
    public static function remapLog($content){
        self::logRecord(self::$remapRedisKey,$content);
    }

    //drs 日志记录
    public static function drsLog($content){
        self::logRecord(self::$drsRedisKey,$content);
    }


    public static function zabbixLog($content){
        self::logRecord(self::$zabbixApiRedisKey,$content);
    }

    public static function pingApiLog($content){
        self::logRecord(self::$pingApiRedisKey,$content);
    }

    public static function dnsServerLog($content){
        self::logRecord(self::$dnsServerRedisKey,$content);
    }




    //日志记录,写入redis list
    public static function logRecord($key,$content,$maxLen=0){
        $redis = Yii::$app->redis;
        $content =  date('Y-m-d H:i:s').'--> '.$content;
        if(!$maxLen){
            $maxLen = self::$maxLen;
        }
        //保持一个最大长度
        $len = $redis->llen($key);
        if($len >= $maxLen){
            $redis->rpop($key);
        }
        $redis->lpush($key,$content);


    }



    public static function getLog($key){
        $redis = Yii::$app->redis;
        $data = $redis->lrange($key,0,1000);
        return $data;
    }

    //报警通知，暂时用邮件通知
    public static function warning($content=''){
        if($content){
            $content =  date('Y-m-d H:i:s').'--> '.$content;
            Yii::$app->queue->push(new QueueJob([
                'type' => 'type',
                'content' =>$content,
            ]));
        }
    }

}
