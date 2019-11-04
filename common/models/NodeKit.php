<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_kit}}".
 *
 * @property string $id
 * @property string $name 节点套件名称
 * @property string $script_name 脚本名称
 * @property string $script_content 脚本内容
 * @property string $is_default 是否默认
 * @property string $remark 备注
 */
class NodeKit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_kit}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['is_default'], 'integer'],

            [['name'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 50],
            [['script_name'], 'string', 'max' => 32],
            [['script_content'], 'string'],
           // ['name', 'unique','message'=>'名称已存在'],
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
            'script_name' => '脚本名称',
            'script_content' => '脚本内容',
            'is_default' => '是否默认',
            'remark' => '备注',
        ];
    }

    public function getScript()
    {
        return $this->hasMany(NodeKitScript::className(),['kit_id'=>'id'])->asArray();
    }
}
