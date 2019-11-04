<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%drs}}".
 *
 * @property string $id
 * @property string $did 域名ID
 * @property string $dname 域名
 * @property string $rrtype 记录类型
 * @property string $rr 主机记录
 * @property string $route 解析线路
 * @property string $rval 记录值
 * @property int $mx mx优先级
 * @property int $ttl TTL
 * @property string $remarks 备注
 * @property int $intime 录入时间
 */
class Drs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%drs}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['did', 'dname', 'rrtype', 'rr', 'route', 'rval', 'intime'], 'required'],
            [['did', 'mx', 'ttl', 'intime'], 'integer'],
            [['rr', 'rval', 'remarks'], 'string'],
            [['dname', 'route'], 'string', 'max' => 100],
            [['rrtype'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'did' => 'Did',
            'dname' => 'Dname',
            'rrtype' => 'Rrtype',
            'rr' => 'Rr',
            'route' => 'Route',
            'rval' => 'Rval',
            'mx' => 'Mx',
            'ttl' => 'Ttl',
            'remarks' => 'Remarks',
            'intime' => 'Intime',
        ];
    }
}
