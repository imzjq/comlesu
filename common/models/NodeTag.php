<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_tag}}".
 *
 * @property int $id ID
 * @property string $name 标签名称
 * @property int $create_time 创建时间
 */
class NodeTag extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_tag}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_time','default','value'=>time()],
            [['name', 'create_time'], 'required'],
            [['create_time'], 'integer'],
            [['name'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'create_time' => 'Create Time',
        ];
    }
}
