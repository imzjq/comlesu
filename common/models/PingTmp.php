<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%ping_tmp}}".
 *
 * @property string $id
 * @property string $node_id 节点ID
 * @property string $ping_ip 节点IP
 * @property string $group_id 分组ID
 * @property string $MS
 */
class PingTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ping_tmp}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_id', 'ping_ip','MS','group_id'], 'required'],
            [['node_id', 'group_id', 'MS'], 'integer'],
            [['ping_ip'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'node_id' => 'Node ID',
            'ping_ip' => 'Ping Ip',
            'group_id' => 'Group ID',
            'MS' => 'Ms',
        ];
    }
}
