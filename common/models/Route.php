<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%route}}".
 *
 * @property string $id
 * @property int $group_id 分组id
 * @property int $MS
 * @property int $node_id 节点ID
 */
class Route extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%route}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id', 'MS', 'node_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'MS' => 'Ms',
            'node_id' => 'Node ID',
        ];
    }
}
