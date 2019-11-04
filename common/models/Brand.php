<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%brand}}".
 *
 * @property string $id
 * @property string $name 名称
 * @property string $user_id 用户id
 * @property string $desc 描述
 * @property string $create_time 时间

 */
class Brand extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%brand}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_time','default','value'=>time()],
            [['name', 'create_time','user_id'], 'required'],
            [['name'], 'string', 'max' => 20],
            [['desc'], 'string', 'max' => 100],
            [['user_id','create_time'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'desc' => '描述',
            'user_id' => '用户id',
            'create_time' => 'create_time',
        ];
    }
}
