<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%drsd}}".
 *
 * @property string $id
 * @property string $user_id 用户id
 * @property string $username 用户名
 * @property string $dname 域名
 * @property string $remarks 备注
 * @property int $intime 录入时间
 * @property int $icp 是否备案
 * @property string $icpcode 备案号
 * @property int $status 状态
 * @property int $high_anti 高防标记
 * @property int $white_switch 白名单开关
 * @property string $white_start_time 开始时间
 * @property string $white_end_time 结束时间
 * @property string $brand_id 分类
 * @property int $package_id 套餐
 */
class Drsd extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%drsd}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'dname', 'intime','user_id','package_id'], 'required'],
            ['brand_id','default','value'=>0],
            [['remarks'], 'string'],
            [['intime','user_id', 'icp', 'status', 'high_anti', 'white_switch', 'brand_id','package_id'], 'integer'],
            [['white_start_time', 'white_end_time'], 'safe'],
            [['username', 'dname', 'icpcode'], 'string', 'max' => 100],
            [['status','high_anti','white_switch'], 'in', 'range' => [0,1]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' =>'user id',
            'username' => 'Username',
            'dname' => 'Dname',
            'remarks' => '备注',
            'intime' => 'Intime',
            'icp' => 'Icp',
            'icpcode' => '备案号',
            'status' => 'Status',
            'high_anti' => 'High Anti',
            'white_switch' => 'White Switch',
            'white_start_time' => 'White Start Time',
            'white_end_time' => 'White End Time',
            'brand_id' => 'Brand Id ',
            'package_id' =>'套餐',
        ];
    }
}
