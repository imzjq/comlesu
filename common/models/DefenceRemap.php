<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%defence_remap}}".
 *
 * @property string $id
 * @property string $did 域名ID
 * @property string $dname 域名
 * @property string $originurl 源地址
 * @property string $originport 源端口
 * @property string $aimurl 目标地址
 * @property string $aimport 目标端口
 * @property string $visit_protocol 访问域名的协议投
 * @property string $origin_protocol 回原地址的协议头
 * @property int $is_at 是否主机头为@,必须要有一个存在
 * @property int $redirect_ssl 301, http跳转到https
 * @property int $preview remap 预览
 * @property string $ssl_id  证书id
 */
class DefenceRemap extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%defence_remap}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['did'], 'required'],
            [['is_at', 'redirect_ssl','ssl_id'], 'integer'],
            [['originurl'],'match', 'pattern'=>'/^[0-9a-zA-Z]+[0-9a-zA-Z\.-]\.[a-zA-Z]{2,6}$/' ,'message'=>'域名格式不正确'],
            //[['originurl'],'match', 'pattern'=>'/^[0-9a-zA-Z*]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,6}$/' ,'message'=>'域名格式不正确'],
            //[['did'], 'string', 'max' => 50],
            [['dname'], 'string', 'max' => 50],
            [['originurl', 'originport', 'aimport','preview'], 'string', 'max' => 50],
            [['aimurl'], 'string', 'max' => 255],
            [['visit_protocol', 'origin_protocol'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'did' => 'Did',
            'dname' => 'Dname',
            'originurl' => 'Originurl',
            'originport' => 'Originport',
            'aimurl' => 'Aimurl',
            'aimport' => 'Aimport',
            'visit_protocol' => 'Visit Protocol',
            'origin_protocol' => 'Origin Protocol',
            'is_at' => 'Is At',
            'redirect_ssl' => 'Redirect Ssl',
            'preview' =>'preview',
            'ssl_id' => 'ssl_id'
        ];
    }
}
