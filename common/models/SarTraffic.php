<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%sar_traffic}}".
 *
 * @property string $id
 * @property string $date
 * @property string $time
 * @property string $intime
 * @property string $ip
 * @property string $resultCode
 * @property string resultStatus
 * @property string $bytes
 * @property string $url
 * @property string $authuser
 * @property string $sitesID
 */
class SarTraffic extends \yii\db\ActiveRecord
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
        return '{{%sar_traffic}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date', 'time', 'ip','resultCode','bytes','url','authuser','sitesID','usersID','did','nodeid','country','intime'], 'required'],
            [['resultStatus'], 'string', 'max' => 50],


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'date',
            'time' => 'time',
            'ip' => 'ip',
            'resultCode' => 'resultCode',
            'bytes' => 'bytes',
            'url' => 'url',
            'authuser' => 'authuser',
            'sitesID' => 'sitesID',
            'usersID' => 'usersID',
            'did' => 'did',
            'nodeid' => 'nodeid',
            'country' => 'country',
            'resultStatus' =>'resultStatus',
            'intime' =>'intime'
        ];
    }
}
