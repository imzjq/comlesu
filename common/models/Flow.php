<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%flow}}".
 *
 * @property string $id
 * @property string $site 域名
 * @property string $flow 流量(MB)
 * @property int $hit 点击数
 * @property int $intime 录入时间
 * @property int $country
 * @property int $nodeid
 * @property int $did
 */
class Flow extends \yii\db\ActiveRecord
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
        return '{{%flow}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['site', 'nodeid', 'did'], 'required'],
            [['flow'], 'number'],
            [['hit', 'intime', 'country', 'nodeid', 'did'], 'integer'],
            [['site'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'site' => 'Site',
            'flow' => 'Flow',
            'hit' => 'Hit',
            'intime' => 'Intime',
            'country' => 'Country',
            'nodeid' => 'Nodeid',
            'did' => 'Did',
        ];
    }
}
