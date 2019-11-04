<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%white_list}}".
 *
 * @property string $id
 * @property string $ip
 * @property string $intime 时间
 * @property string $dns
 * @property int $status
 */
class WhiteList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%white_list}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'dns'], 'required'],
            [['intime'], 'safe'],
            [['status'], 'integer'],
            [['ip'], 'string', 'max' => 30],
            [['dns'], 'string', 'max' => 60],
            [['ip', 'dns'], 'unique', 'targetAttribute' => ['ip', 'dns']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'intime' => 'Intime',
            'dns' => 'Dns',
            'status' => 'Status',
        ];
    }
}
