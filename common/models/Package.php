<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%brand}}".
 *
 * @property string $id
 * @property string $name 套餐名称
 * @property string $group_id  分组id
 * @property string $defence_group_id  高防分组id
 * @property string $defence_ip_id  高防别名id
 * @property string $ssl_quantity  ssl_quantity
 * @property string $origin_quantity  源数量
 * @property string $url_quantity  源域名数量
 * @property string $drsd_quantity 域名解析数量
 * @property string $black_quantity 黑名单数量
 * @property string $white_quantity  白名单数量
 * @property string $create_time  创建时间
 * @property string $hijack_quantity  防劫持数量
 * @property string $bandwidth  带宽
 * @property string $defence  高防
 */
class Package extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%package}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_time','default','value'=>time()],
            [['name', 'create_time','group_id','defence_group_id'], 'required'],
            [['group_id','defence_group_id'],'default','value'=>0],
            [['name'], 'string', 'max' => 30],
            [['bandwidth','defence'], 'string', 'max' => 32],
            [['group_id','defence_ip_id','ssl_quantity','origin_quantity','url_quantity','drsd_quantity','black_quantity','white_quantity','create_time','defence_group_id','hijack_quantity'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'group_id' => '加速分组',
            'defence_group_id' =>'高防分组',
            'defence_ip_id' => '高防别名',
            'ssl_quantity' => 'ssl_quantity',
            'origin_quantity' => 'origin_quantity',
            'url_quantity' => 'url_quantity',
            'drsd_quantity' => 'drsd_quantity',
            'black_quantity' => 'black_quantity',
            'white_quantity' => 'white_quantity',
            'create_time' => 'create_time',
            'hijack_quantity' => '防劫持数量',
            'bandwidth' => '带宽',
            'defence' => '高防',
        ];
    }
}
