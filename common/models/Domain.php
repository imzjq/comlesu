<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%domain}}".
 *
 * @property string $id
 * @property string $user_id 用户id
 * @property string $username 用户名
 * @property string $dname 域名
 * @property string $cname CName
 * @property string $originip ip
 * @property string $origindname 回源域名
 * @property int $stype 加速类型
 * @property string $cachetype 缓存类型
 * @property int $acl 访问控制
 * @property int $remap 正则回源
 * @property int $status 部署状态
 * @property int $enable 启用状态
 * @property int $create_time 录入时间
 * @property int $icp 是否备案（0,未备案）
 * @property string $cname_n 多回原ip时的host别名
 * @property int $high_anti 高防标记(1：高防)
 * @property int $high_anti_date 高防切换时间
 * @property int $node_group 节点分组号
 * @property string $port 端口号
 * @property int $rtmp 是否为rtmp

 * @property int $ttl TTL
 * @property int $log
 * @property string $rtmp_url
 * @property string $is_spider 是否开启蜘蛛回源
 * @property string $spider_originip 针对蜘蛛，回源IP
 * @property int $is_https 是否开启https
 * @property string $g_id 分组ID
 * @property string $ssl_id https证书ID
 * @property int $brand_id  品牌id
 *  @property int $package_id 套餐

 * @property int $sys_node_group 系统选择分组号
 * @property int $sys_high_anti 系统选择高防
 */
class Domain extends \yii\db\ActiveRecord
{
    const STYPE_DOMAIN = 1; //加速
    const STYPE_DEFENCE = 2; //轮询

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%domain}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'node_group','dname','originip','high_anti','user_id','package_id','stype'], 'required'],
            //[['dname'],'match', 'pattern'=>'/^[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\’:+!]*([^<>\”])*$/' ,'message'=>'域名格式不正确'],
            [['dname'],'match', 'pattern'=>'/^[0-9a-zA-Z*]+[0-9a-zA-Z\.-]*\.[a-zA-Z]{2,6}$/' ,'message'=>'域名格式不正确'],
            ['create_time','default','value'=>time()],
            [['brand_id','sys_node_group','sys_high_anti'],'default','value'=>0],
            ['stype','default','value'=>self::STYPE_DOMAIN],
            [['stype'], 'in', 'range' => [self::STYPE_DOMAIN,self::STYPE_DEFENCE],'message'=>'类型只能是1或者2'],
            [['stype', 'acl', 'remap','package_id','user_id','sys_node_group','sys_high_anti','brand_id', 'status', 'enable', 'create_time', 'icp', 'high_anti', 'high_anti_date', 'node_group', 'rtmp', 'ttl', 'log', 'is_https', 'g_id', 'ssl_id'], 'integer'],
            [['username', 'dname', 'rtmp_url'], 'string', 'max' => 100],
            [['cname'], 'string', 'max' => 255],
            [['originip', 'origindname'], 'string', 'max' => 250],
            [['cachetype', 'spider_originip'], 'string', 'max' => 50],
            [['cname_n'], 'string', 'max' => 200],
            [['is_spider'], 'string', 'max' => 20],
            [['dname','cname'], 'unique'],
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
            'username' => '用户名',
            'dname' => '域名',
            'cname' => 'Cname',
            'originip' => '回原地址',
            'origindname' => 'Origindname',
            'stype' => 'Stype',
            'cachetype' => 'Cachetype',
            'acl' => 'Acl',
            'remap' => 'Remap',
            'status' => 'Status',
            'enable' => 'Enable',
            'create_time' => 'Create Time',
            'icp' => 'Icp',
            'cname_n' => 'Cname N',
            'high_anti' => 'High Anti',
            'high_anti_date' => 'High Anti Date',
            'node_group' => 'Node Group',
            'port' => 'Port',
            'rtmp' => 'Rtmp',
            'white_start_time' => 'White Start Time',
            'white_end_time' => 'White End Time',
            'ttl' => 'Ttl',
            'log' => 'Log',
            'rtmp_url' => 'Rtmp Url',
            'is_spider' => 'Is Spider',
            'spider_originip' => 'Spider Originip',
            'is_https' => 'Is Https',
            'g_id' => 'G ID',
            'ssl_id' => 'Ssl ID',
            'brand_id' => 'brand_id',
            'package_id' =>'套餐',
            'sys_node_group' =>'sys_node_group',
            'sys_high_anti' => 'sys_high_anti'
        ];
    }
}
