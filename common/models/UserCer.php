<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%user_cer}}".
 *
 * @property string $id
 * @property string $user_id
 * @property string $username
 * @property string $pb_key 公钥
 * @property string $pv_key 私钥
 * @property string $created_at 创建时间
 * @property string $update_at 最后修改时间
 * @property int $is_delete 是否删除1删除，0未删除
 * @property string $domain 证书域名
 * @property int $cer_start_time 证书注册时间
 * @property int $cer_end_time 证书过期时间
 * @property int $package_id 套餐
 */
class UserCer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_cer}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'username', 'pb_key', 'pv_key','package_id'], 'required'],
            [['user_id', 'created_at', 'update_at', 'is_delete','cer_start_time','cer_end_time','package_id'], 'integer'],
            [['username'], 'string', 'max' => 255],
            [['domain'], 'string'],
            [['pb_key','pv_key'],'unique']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'username' => 'Username',
            'pb_key' => 'Pb Key',
            'pv_key' => 'Pv Key',
            'created_at' => 'Created At',
            'update_at' => 'Update At',
            'is_delete' => 'Is Delete',
            'domain' => 'Domain',
            'cer_start_time'=>'cer_start_time',
            'cer_end_time'=>'cer_end_time',
            'package_id' =>'套餐',
        ];
    }
}
