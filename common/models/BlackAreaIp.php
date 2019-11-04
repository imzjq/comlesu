<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%black_area_ip}}".
 *
 * @property string $id
 * @property string $user_id 用户id
 * @property string $package_id 套餐
 * @property string $brand_id  品牌
 * @property string $home_id 国内地区
 * @property string $abroad_id 国外地区
 * @property string $create_time 创建时间

 */
class BlackAreaIp extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%black_area_ip}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['brand_id','default','value'=>0],
            ['create_time','default','value'=>date('Y-m-d H:i:s')],
            [['package_id', 'create_time','user_id'], 'required'],
            [['home_id','abroad_id','create_time'], 'string'],
            [['user_id','package_id','brand_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户id',
            'package_id' => '套餐',
            'brand_id' => '品牌',
            'home_id' => '国内地区',
            'abroad_id' => '国外地区',
            'create_time' => '创建时间',
        ];
    }
}
