<?php

namespace common\models;

use Yii;

/**代理商来源配置表
 * This is the model class for table "{{%country_type}}".
 *
 * @property string $id
 * @property string $type
 * @property string $remark
 * @property string $cname_suffix
 * @property string $c_type
 */
class CountryType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%country_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type','c_type','cname_suffix'], 'required'],
            [['type'], 'string', 'max' => 30],
            [['remark','cname_suffix'], 'string', 'max' => 50],
            [['type'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '类型',
            'remark' => 'Remark',
            'cname_suffix' =>'域名后缀',
            'c_type' =>'类型简称'
        ];
    }

    //获取所有的
    public static function getAll(){
        return CountryType::find()->asArray()->all();
    }
}
