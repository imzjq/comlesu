<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/17
 * Time: 14:51
 */

namespace backend\models;

use common\models\SpiderType as CommonSpiderType;
use yii\helpers\ArrayHelper;

class SpiderType extends CommonSpiderType
{


    public function idToName(){
        $res = SpiderType::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','name');
        return $arr;
    }
}
