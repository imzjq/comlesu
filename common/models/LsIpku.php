<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%ls_ipku}}".
 *
 * @property string $id
 * @property string $ip ip
 * @property int $group_id 分组ID
 * @property string $st1
 * @property string $st2
 * @property string $st3
 * @property string $date
 */
class LsIpku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ls_ipku}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id', 'st1', 'st2', 'st3'], 'integer'],
            [['date'], 'safe'],
            ['date','default','value'=>date('Y-m-d H:i:s')],
            [['ip'], 'string', 'max' => 30],
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
            'group_id' => 'Group ID',
            'st1' => 'St1',
            'st2' => 'St2',
            'st3' => 'St3',
            'date' => 'Date',
        ];
    }
}
