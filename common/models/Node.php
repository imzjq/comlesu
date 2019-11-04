<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%node}}".
 *
 * @property string $id id
 * @property string $ip 节点IP
 * @property string $zabbix_ip zabbix_IP
 * @property string $group_id 分组ID
 * @property string $name 名称
 * @property string $node_name_a
 * @property string $node_name
 * @property string $area 位置
 * @property int $memory 内存
 * @property double $cpu CPU
 * @property double $loads 负载
 * @property int $loads_n 自加负载
 * @property int $weight 权重
 * @property int $switch 开关
 * @property int $cluster 集群
 * @property int $forbidden 禁止
 * @property int $transfer 转移
 * @property double $flow 实时流量
 * @property int $status 服务器状态
 * @property int $country 国家
 * @property int $alive 主机状态
 * @property int $flow_max
 * @property int $defence_status 高防状态
 * @property int $type 类型 1普通,2独享,3共享,4高防,5综合,6备用
 * @property int $user_id 用户ID
 * @property int $alive_z_1 zabbix 1
 * @property int $alive_z_2 zabbix 2
 * @property int $kit_id 套件id
 * @property int $tag_ids 标签id 列表
 */
class Node extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%node}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip', 'name','group_id','zabbix_ip'], 'required'],
            ['kit_id','default','value'=>0],
            [['group_id', 'memory', 'loads_n', 'weight', 'switch', 'cluster', 'forbidden', 'transfer', 'status', 'country', 'alive', 'flow_max', 'defence_status', 'type', 'user_id', 'alive_z_1', 'alive_z_2','kit_id'], 'integer'],
            [['cpu', 'loads', 'flow'], 'number'],
            [['ip', 'name','zabbix_ip'], 'string', 'max' => 30],
            [['tag_ids'], 'string', 'max' => 200],
            [[ 'area'], 'string', 'max' => 100],
            [['node_name_a', 'node_name'], 'string', 'max' => 10],
           // ['name', 'exist', 'targetClass' => '\common\models\Node', 'filter' => ['name' => $this->name],'message' => '节点名称已存在'],
            //['name', 'unique','message'=>'节点名称已存在'],
            [['switch','forbidden'], 'in', 'range' => [0,1],'message'=>'开关禁止只能是0或者1'],
            [['ip'],'match', 'pattern'=>'/^(((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))\.){3}((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))$/' ,'message'=>'ip格式不正确'],
            [['name'],'match', 'pattern'=>'/^[a-z0-9]+$/' ,'message'=>'节点名称只能输入数字和字母'],
            [['ip','zabbix_ip'],'unique'],
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
            'zabbix_ip' => 'zabbix_ip',
            'group_id' => 'ip库分组号',
            'name' => '节点名称',
            'node_name_a' => 'Node Name A',
            'node_name' => 'Node Name',
            'area' => '备注',
            'memory' => 'Memory',
            'cpu' => 'Cpu',
            'loads' => 'Loads',
            'loads_n' => 'Loads N',
            'weight' => 'Weight',
            'switch' => 'Switch',
            'cluster' => 'Cluster',
            'forbidden' => 'Forbidden',
            'transfer' => 'Transfer',
            'flow' => 'Flow',
            'status' => 'Status',
            'country' => 'Country',
            'alive' => 'Alive',
            'flow_max' => 'Flow Max',
            'defence_status' => 'Defence Status',
            'type' => 'Type',
            'user_id' => 'User ID',
            'alive_z_1' => 'Alive Z 1',
            'alive_z_2' => 'Alive Z 2',
            'kit_id' => 'Kit Id',
            'tag_ids' => 'Tag Ids',

        ];
    }


}
