<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%defence}}".
 *
 * @property string $id
 * @property string $user_id 用户id
 * @property string $username 用户名
 * @property string $dname 域名
 * @property string $nodeids 节点id
 * @property string $cname CName
 * @property string $originip ip
 * @property string $origindname 回源域名
 * @property int $status 部署状态
 * @property int $enable 启用状态
 * @property int $create_time 录入时间
 * @property string $cname_n 多回原ip时的host别名
 * @property int $high_anti 高防标记(1：高防)
 * @property string $port 端口号
 * @property int $is_https 是否开启https
 * @property string $ssl_id https证书ID
 * @property string $brand_id brand_id
 * @property int $package_id 套餐
 * @property int $stype 加速类型
 * @property int $node_group 分组
 * @property int $ttl TTL
 * @property int $sys_node_group 系统选择分组号
 * @property int $sys_high_anti 系统选择高防
 * @property int $log 开启日志
 */
class Defence extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%defence}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username','dname','originip','high_anti','user_id','package_id','stype','node_group'], 'required'],
            ['create_time','default','value'=>time()],
            [['brand_id','sys_node_group','sys_high_anti'],'default','value'=>0],
            ['stype','default','value'=>Domain::STYPE_DEFENCE],
            [['stype'], 'in', 'range' => [Domain::STYPE_DOMAIN,Domain::STYPE_DEFENCE],'message'=>'类型只能是1或者2'],
            [['user_id',  'status', 'enable', 'create_time', 'high_anti','sys_node_group','sys_high_anti', 'is_https', 'ssl_id','brand_id','package_id','node_group','ttl','log'], 'integer'],
            [['nodeids'], 'string','max'=>200],
            [['port'], 'safe'],
            [['username', 'dname', ], 'string', 'max' => 100],
            [['cname'], 'string', 'max' => 255],
            [['originip', 'origindname'], 'string', 'max' => 250],
            [['cname_n'], 'string', 'max' => 200],
            [['dname'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usern_id' => '用户id',
            'username' => '用户名',
            'dname' => '域名',
            'nodeids' => '节点id',
            'cname' => 'Cname',
            'originip' => '回原地址',
            'origindname' => 'Origindname',
            'status' => 'Status',
            'enable' => 'Enable',
            'create_time' => 'Create Time',
            'cname_n' => 'Cname N',
            'high_anti' => 'High Anti',
            'port' => 'Port',
            'is_https' => 'Is Https',
            'ssl_id' => 'Ssl ID',
            'brand_id'=>'品牌id',
            'package_id' =>'套餐',
            'stype' => '类型',
            'node_group' => '节点分组',
            'ttl' => 'TTL',
            'sys_node_group' =>'sys_node_group',
            'sys_high_anti' => 'sys_high_anti',
            'log' => 'Log',
        ];
    }

    public function getUsers(){
        return $this->hasOne(User::className(),['id'=>'user_id']);
    }
}
