<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_group_nodeid}}".
 *
 * @property string $id
 * @property string $node_id 节点id
 * @property string $node_group_id 节点分组id
 */
class NodeGroupNodeid extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_group_nodeid}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_id','node_group_id'], 'required'],
            [['node_id','node_group_id'], 'integer'],
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
            'node_group_id' => '节点分组id',
        ];
    }
}
