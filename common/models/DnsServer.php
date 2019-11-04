<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%dns_server}}".
 *
 * @property string $id
 * @property string $ip ip地址
 * @property string $name 名称
 * @property string $area 地区
 * @property double $cpu
 * @property int $memory 内存
 * @property double $loads 负载
 * @property int $weight 权重
 * @property int $switch 开关
 * @property int $cluster 集群
 * @property int $forbidden 禁止
 * @property double $flow 流量
 * @property string $operators 运营商
 * @property int $TTL TTL
 * @property string $dns_name dns域名
 * @property int $type 域名类型
 * @property int $status 状态
 * @property int $alive 主机状态
 * @property int $st_op
 * @property int $group_id 分组号
 */
class DnsServer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dns_server}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'name', 'area', 'dns_name', 'group_id'], 'required'],
            [['cpu', 'loads', 'flow'], 'number'],
            [['memory', 'weight', 'switch', 'cluster', 'forbidden', 'TTL', 'type', 'status', 'alive', 'st_op', 'group_id'], 'integer'],
            [['ip', 'name', 'area'], 'string', 'max' => 30],
            [['operators'], 'string', 'max' => 255],
            [['dns_name'], 'string', 'max' => 50],
            [['switch','forbidden'], 'in', 'range' => [0,1],'message'=>'开关禁止只能是0或者1'],
            [['ip'],'match', 'pattern'=>'/^(((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))\.){3}((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))$/' ,'message'=>'ip格式不正确'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'name' => 'Name',
            'area' => 'Area',
            'cpu' => 'Cpu',
            'memory' => 'Memory',
            'loads' => 'Loads',
            'weight' => 'Weight',
            'switch' => 'Switch',
            'cluster' => 'Cluster',
            'forbidden' => 'Forbidden',
            'flow' => 'Flow',
            'operators' => 'Operators',
            'TTL' => 'Ttl',
            'dns_name' => 'Dns Name',
            'type' => 'Type',
            'status' => 'Status',
            'alive' => 'Alive',
            'st_op' => 'St Op',
            'group_id' => 'Group ID',
        ];
    }
}
