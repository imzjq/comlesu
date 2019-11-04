<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%user_cer_domain}}".
 *
 * @property string $id
 * @property string $user_id
 * @property string $username
 * @property string $domain 域名
 * @property int $cer_end_time 证书过期时间
 * @property int $user_cer_id 对应证书id
 */
class UserCerDomain extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_cer_domain}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'username','user_cer_id','domain'], 'required'],
            [['user_id','cer_end_time'], 'integer'],
            [['username'], 'string', 'max' => 50],
            [['domain'], 'string', 'max' => 50],
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
            'domain' => 'Domain',
            'cer_end_time'=>'cer_end_time',
            'user_cer_id'=>'user_cer_id',
        ];
    }
}
