<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/1
 * Time: 12:48
 */

namespace common\components;

use Yii;
use yii\base\BaseObject;
class QueueJob extends BaseObject implements \yii\queue\JobInterface
{
    public $file;
    public $type; //报警类型 预留
    public $content; //内容


    public function __construct($array)
    {
        $this->type = $array['type'];
        $this->content = $array['content'];
        //$this->file = $array['file'];

        return $this;
    }

    public function execute($queue)
    {
        //写入文件，作为临时记录
        //file_put_contents($this->file,$this->content,FILE_APPEND);
        $this->sendMail($this->content);
    }

    //邮件发送
    public function sendMail($content){
         $res = Yii::$app->mailer->compose()
            ->setTo('412988263@qq.com')
            ->setSubject('测试报警')
            ->setTextBody($content)
            ->send();
    }
}
