<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%white_ip}}".
 *
 * @property string $id
 * @property string $user_id 用户id
 * @property string $ip ip
 * @property string $package_id 套餐
 * @property string $create_time 时间
 * @property string $brand_id 品牌id
 */
class WhiteIp extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%white_ip}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_time','default','value'=>time()],
            ['brand_id','default','value'=>0],
            [['ip', 'create_time','user_id','package_id'], 'required'],
            [['ip'], 'string', 'max' => 32],
            [['user_id','create_time','package_id','brand_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户id',
            'ip' => 'ip',
            'package_id' => '套餐',
            'create_time' => 'create_time',
            'brand_id' => 'brand_id',
        ];
    }
}
