<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_update}}".
 *
 * @property string $id
 * @property string $node_id 节点id
 * @property string $create_time 时间

 */
class NodeUpdate extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_update}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_id'], 'required'],
            [['create_time'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'node_id' => '节点id',
            'create_time' => '创建时间',
        ];
    }
}
