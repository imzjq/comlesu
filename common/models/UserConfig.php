<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%user_config}}".
 *
 * @property integer $id
 * @property integer $group_id 分组id
 * @property integer $defence_ip_id  高防别名id
 * @property string $node_ids 节点ids
 * @property integer $user_id 用户id

 */
class UserConfig extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_config}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['user_id'], 'required'],
            [['node_ids'], 'string', 'max' => 200],
            [['user_id','defence_ip_id','group_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => '分组id',
            'defence_ip_id' => '高防别名id',
            'node_ids' => '节点id',
            'user_id' => '用户id',
        ];
    }
}
