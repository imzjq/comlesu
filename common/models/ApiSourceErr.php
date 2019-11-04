<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%api_source_err}}".
 *
 * @property string $id
 * @property string $node_ip
 * @property string $source_ip
 * @property string $record_time
 * @property string $status
 * @property string $username
 * @property string $dname
 * @property string $mail_id
 * @property string $remark
 */
class ApiSourceErr extends \yii\db\ActiveRecord
{

//    public static function getDb()
//    {
//        return Yii::$app->get('dbFlow');
//    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%api_source_err}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['node_ip', 'source_ip', 'record_time','status'], 'required'],
            [['status'], 'integer'],
            [['username', 'dname', 'mail_id', 'remark'], 'string'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'node_ip' => 'node_ip',
            'source_ip' => 'source_ip',
            'record_time' => 'record_time',
            'status' => 'status',
            'username' => 'username',
            'dname' => 'dname',
            'mail_id' => 'mail_id',
            'remark' => 'remark',
        ];
    }
}
