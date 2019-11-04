<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node_kit_script}}".
 *
 * @property string $id
 * @property string $name 脚本名称
 * @property string $content 脚本内容
 * @property string $kit_id 套件id
 */
class NodeKitScript extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node_kit_script}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name','content','kit_id'], 'required'],
            [['name'], 'string', 'max' => 32],
            [['content'], 'string'],
            [['kit_id'], 'integer'],
            // ['name', 'unique','message'=>'脚本名称已经存在'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '脚本名称',
            'content' => '脚本内容',
            'kit_id' => '套件id',
        ];
    }
}
