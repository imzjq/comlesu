<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%spider_type}}".
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property int $status
 */
class SpiderType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%spider_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 30],
            [['type'], 'string', 'max' => 15],
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
            'type' => 'Type',
            'status' => 'Status',
        ];
    }
}
