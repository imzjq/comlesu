<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%dns_example}}".
 *
 * @property string $id 自增ID
 * @property string $example DNS示例
 * @property string $ename
 * @property string $area
 */
class DnsExample extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dns_example}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['example', 'area'], 'required'],
            [['example', 'ename', 'area'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'example' => 'DNS示例',
            'ename' => 'Ename',
            'area' => 'Area',
        ];
    }
}
