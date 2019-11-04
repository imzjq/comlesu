<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%package_user}}".
 *
 * @property string $id
 * @property string $user_id user_id
 * @property string $package_id  package_id
 * @property string $create_time  创建时间
 */
class PackageUser extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%package_user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_time','default','value'=>time()],
            [['user_id','package_id', 'create_time'], 'required'],
            [['user_id','package_id','create_time'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'user_id',
            'package_id' => 'package_id',
            'create_time' => 'create_time',
        ];
    }
}
