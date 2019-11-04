<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_group}}".
 *
 * @property string $id
 * @property string $group_name 分组名
 * @property string $node_id 组内节点id
 * @property int $isDefault 是否默认
 * @property int $type 关联注册来源
 * @property string $remark 备注
 */
class NodeGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_group}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_name','isDefault', 'type'], 'required'],
            [['isDefault', 'type'], 'integer'],
            [['group_name'], 'string', 'max' => 100],
            [['node_id'], 'string', 'max' => 255],
            [['remark'], 'string', 'max' => 30],
            ['group_name', 'unique','message'=>'分组名称已存在'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_name' => 'Group Name',
            'node_id' => 'Node ID',
            'isDefault' => 'Is Default',
            'type' => 'Type',
            'remark' => 'Remark',
        ];
    }
}
