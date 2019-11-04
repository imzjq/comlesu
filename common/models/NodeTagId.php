<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_tag_id}}".
 *
 * @property int $id ID
 * @property int $tag_id 标签id
 * @property int $node_id 节点id
 */
class NodeTagId extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_tag_id}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['tag_id', 'node_id'], 'required'],
            [['tag_id','node_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag_id' => 'Tag Id',
            'node_id' => 'Node Id',
        ];
    }
}
