<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%agent}}".
 *
 * @property string $id
 * @property string $domain
 * @property int $userid
 * @property string $username
 * @property string $email 邮箱地址
 * @property string $addr 公司地址
 * @property string $logo
 * @property string $nodes 节点
 * @property string $company
 * @property string $name
 * @property string $area
 * @property string $icpcode
 * @property string $kefu
 * @property string $tel 手机号码电话号码
 * @property int $level
 * @property string $remarks
 * @property string $license 营业执照url
 * @property string $title 网站标题
 * @property string $template 自有模板
 */
class Agent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%agent}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['domain', 'username', 'area'], 'required'],
            [['userid', 'level'], 'integer'],
            [['domain'], 'string', 'max' => 255],
            [['username', 'company', 'name', 'template'], 'string', 'max' => 50],
            [['email', 'logo', 'nodes', 'icpcode', 'license', 'title'], 'string', 'max' => 100],
            [['addr', 'remarks'], 'string', 'max' => 200],
            [['area'], 'string', 'max' => 10],
            [['kefu'], 'string', 'max' => 255],
            [['tel'], 'string', 'max' => 15],
            [['domain','userid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'domain' => '代理上域名',
            'userid' => 'Userid',
            'username' => '用户名',
            'email' => 'Email',
            'addr' => 'Addr',
            'logo' => 'Logo',
            'nodes' => 'Nodes',
            'company' => 'Company',
            'name' => 'Name',
            'area' => 'Area',
            'icpcode' => 'Icpcode',
            'kefu' => 'Kefu',
            'tel' => 'Tel',
            'level' => 'Level',
            'remarks' => 'Remarks',
            'license' => 'License',
            'title' => 'Title',
            'template' => 'Template',
        ];
    }
}
