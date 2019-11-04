<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%code_num}}".
 *
 * @property string $id
 * @property string $did did
 * @property string $date 时间
 * @property string $code 状态
 * @property string $num 数量
 * @property string $type 表类型
 * @property string $intime 时间
 */
class CodeNum extends \yii\db\ActiveRecord
{
    const STYPE_DOMAIN = 1; //加速
    const STYPE_DEFENCE = 2; //轮询

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%code_num}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['did', 'code','type','intime','date'], 'required'],
            [['did', 'num','intime'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'did' => 'did',
            'date' => 'date',
            'code' => 'code',
            'num' => 'num',
            'type' => 'type',
            'intime' => 'intime',
        ];
    }
}
