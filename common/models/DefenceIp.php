<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%defence_ip}}".
 *
 * @property string $id ID
 * @property string $cname 别名
 * @property string $ip 高防IP
 * @property int $country 国内外区分
 * @property int $remark 备注
 * @property string $type
 */
class DefenceIp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%defence_ip}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cname', 'ip', 'country'], 'required'],
            [['country', 'remark'], 'integer'],
            [['cname'], 'string', 'max' => 50],
            [['ip'], 'string', 'max' => 500],
            [['type'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cname' => 'Cname',
            'ip' => 'Ip',
            'country' => 'Country',
            'remark' => 'Remark',
            'type' => 'Type',
        ];
    }
}
