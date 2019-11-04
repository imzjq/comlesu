<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%defence_nodeid}}".
 *
 * @property string $id
 * @property string $node_id 节点id
 * @property string $defence 节点分组id
 */
class DefenceNodeid extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%defence_nodeid}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_id','defence'], 'required'],
            [['node_id','defence'], 'integer'],
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
            'defence' => '高防id',
        ];
    }
}
