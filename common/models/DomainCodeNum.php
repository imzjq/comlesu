<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%domain_code_num}}".
 *
 * @property string $id
 * @property string $did 加速id
 * @property string $date date
 * @property string $code 数量
 * @property string $num 数量

 */
class DomainCodeNum extends \yii\db\ActiveRecord
{

//    public static function getDb()
//    {
//        return Yii::$app->get('dbFlow');
//    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%domain_code_num}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['did', 'date', 'code','num'], 'required'],
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
        ];
    }
}
