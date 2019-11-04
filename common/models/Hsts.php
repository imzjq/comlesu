<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%hsts}}".
 *
 * @property string $id
 * @property string $url url
 * @property string $user_id 用户id
 * @property string $package_id 套餐id
 * @property string $create_time 时间

 */
class Hsts extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hsts}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_time','default','value'=>time()],
            [['url', 'create_time','user_id','package_id'], 'required'],
            [['url'], 'string', 'max' => 100],
            [['user_id','create_time','package_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'url',
            'user_id' => '用户id',
            'package_id' => '套餐',
            'create_time' => 'create_time',
        ];
    }
}
