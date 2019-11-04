<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%ddos}}".
 *
 * @property string $id
 * @property string $link 链接数
 * @property string $ip ip
 * @property string $node_ip 节点IP

 */
class Ddos extends \yii\db\ActiveRecord
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
        return '{{%ddos}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['link', 'ip', 'node_ip',], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'link' => 'link',
            'ip' => 'ip',
            'node_ip' => 'node_ip',
        ];
    }
}
