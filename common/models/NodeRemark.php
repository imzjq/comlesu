<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_remark}}".
 *
 * @property string $id
 * @property string $node_id 节点ip
 * @property string $other_ip 其余ip
 * @property string $remark 备注
 * @property string $password 密码

 */
class NodeRemark extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_remark}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_id'], 'required'],
            [['other_ip'], 'string'],
            [['remark','password'], 'string', 'max' => 50],
            [['node_id'], 'integer'],
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
            'other_ip' => '其余ip',
            'remark' => '备注',
            'password' => '密码',
        ];
    }
}
