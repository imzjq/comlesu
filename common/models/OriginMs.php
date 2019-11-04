<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%origin_ms}}".
 *
 * @property string $name 配置名
 * @property string $value 配置值
 */
class OriginMs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%origin_ms}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'ori_ip'], 'required'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'ip' => 'ip',
            'ori_ip' => 'ori_ip',
            'attempted' => 'attempted',
            'connected' => 'connected',
            'minimum' => 'minimum',
            'maximum' => 'maximum',
            'average' => 'average',
            'create_time' => 'create_time',
        ];
    }
}
