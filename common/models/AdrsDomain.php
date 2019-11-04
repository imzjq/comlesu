<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%adrs_domain}}".
 *
 * @property string $id 自增ID
 * @property string $domain 加速主域名
 * @property string $area 区分国内外
 */
class AdrsDomain extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%adrs_domain}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['domain', 'area'], 'required'],
            [['domain'], 'string', 'max' => 30],
            [['area'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'domain' => 'Domain',
            'area' => 'Area',
        ];
    }
}
