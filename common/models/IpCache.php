<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%ip_cache}}".
 *
 * @property string $id
 * @property string $ip
 * @property int $group_id
 * @property int $node_id
 */
class IpCache extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ip_cache}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'group_id'], 'required'],
            [['group_id', 'node_id'], 'integer'],
            [['ip'], 'string', 'max' => 30],
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
            'group_id' => 'Group ID',
            'node_id' => 'Node ID',
        ];
    }
}
