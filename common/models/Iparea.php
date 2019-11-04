<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%iparea}}".
 *
 * @property string $id
 * @property string $country 国家
 * @property string $country_id 国家ID
 * @property string $province 省
 * @property string $province_id 省ID
 * @property string $city 市
 * @property string $city_id 市ID
 * @property string $service 服务商
 * @property string $service_id 服务商ID
 * @property string $status 是否分组
 */
class Iparea extends \yii\db\ActiveRecord
{
//    public static function getDb()
//    {
//        return Yii::$app->get('dbIpku');
//    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%iparea}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['country', 'country_id', 'province', 'province_id', 'city', 'city_id', 'service', 'service_id', 'status'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'country' => 'Country',
            'country_id' => 'Country ID',
            'province' => 'Province',
            'province_id' => 'Province ID',
            'city' => 'City',
            'city_id' => 'City ID',
            'service' => 'Service',
            'service_id' => 'Service ID',
            'status' => 'Status',
        ];
    }
}
