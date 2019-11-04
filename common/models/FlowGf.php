<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%flow_gf}}".
 *
 * @property string $id
 * @property string $site 域名
 * @property string $flow 流量(MB)
 * @property int $hit 点击量
 * @property int $intime 时间
 * @property int $country
 * @property int $nodeid
 * @property int $did
 */
class FlowGf extends \yii\db\ActiveRecord
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
        return '{{%flow_gf}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['site', 'nodeid', 'did'], 'required'],
            [['site'], 'string'],
            [['flow'], 'number'],
            [['hit', 'intime', 'country', 'nodeid', 'did'], 'integer'],
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
