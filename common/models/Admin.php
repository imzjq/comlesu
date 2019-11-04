<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%admin}}".
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
 * @property int $role 角色
 * @property int $status 用户状态
 * @property int $create_time 录入时间
 */
class Admin extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%admin}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'password', 'email'], 'required'],
            [['balance'], 'number'],
            [['notebook'], 'string'],
            [['last_login_time', 'login_count', 'role', 'status', 'create_time'], 'integer'],
            [['username', 'realname', 'email'], 'string', 'max' => 100],
            [['password'], 'string', 'max' => 50],
            [['qq'], 'string', 'max' => 15],
            [['mobile', 'last_login_ip'], 'string', 'max' => 20],
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
            'role' => 'Role',
            'status' => 'Status',
            'create_time' => 'Create Time',
        ];
    }
}
