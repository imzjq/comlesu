<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property string $id
 * @property string $username 用户
 * @property string $password 密码
 * @property string $realname 真实姓名
 * @property string $email 邮箱
 * @property string $qq qq
 * @property string $mobile 手机
 * @property string $balance 账户余额
 * @property string $notebook 备忘
 * @property string $last_login_ip 最后登录ip
 * @property int $last_login_time 最后登录时间
 * @property string $login_count 登录次数
 * @property int $status 用户状态
 * @property int $create_time 录入时间
 * @property int $fee_now 当前计费模式
 * @property int $fee_next 下月计费模式
 * @property string $fee_time 设置时间
 * @property int $level 等级（0为普通,1为VIP）
 * @property int $role 角色
 * @property int $agentid 代理ID
 * @property string $registsource 注册来源
 */
class User extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default','value'=>1],
            [['agentid','level'], 'default','value'=>0],
            [['username', 'password', 'email','level','status' ,'agentid', 'registsource'], 'required'],
            [['balance'], 'number'],

            [['notebook'], 'string'],
            [['last_login_time', 'login_count', 'status', 'create_time', 'fee_now', 'fee_next', 'level', 'agentid','role'], 'integer'],
            [['username', 'realname', 'email'], 'string', 'max' => 100],
            [['password', 'fee_time', 'registsource'], 'string', 'max' => 50],
            [['qq'], 'string', 'max' => 15],
            [['mobile', 'last_login_ip'], 'string', 'max' => 20],
            ['username', 'unique','message'=>'用户名已存在'],
            ['email', 'unique','message'=>'邮箱已存在'],
            ['email', 'email','message'=>'邮箱格式不正确'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'realname' => 'Realname',
            'email' => 'Email',
            'qq' => 'Qq',
            'mobile' => 'Mobile',
            'balance' => 'Balance',
            'notebook' => 'Notebook',
            'last_login_ip' => 'Last Login Ip',
            'last_login_time' => 'Last Login Time',
            'login_count' => 'Login Count',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'fee_now' => 'Fee Now',
            'fee_next' => 'Fee Next',
            'fee_time' => 'Fee Time',
            'level' => 'Level',
            'role' => 'Role',
            'agentid' => 'Agentid',
            'registsource' => 'Registsource',
        ];
    }
}
