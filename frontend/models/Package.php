<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/13
 * Time: 20:37
 */

namespace frontend\models;

use common\models\Package as CommonPackage;
use common\models\PackageUser;
use yii\helpers\ArrayHelper;

class Package extends CommonPackage
{
    use ApiTrait;
    public function idToName($uid)
    {
        $res =  PackageUser::find()->where(['{{%package_user}}.user_id'=>$uid])->select('{{%package}}.id,{{%package}}.name')->innerJoin('{{%package}}','{{%package}}.id = {{%package_user}}.package_id')->asArray()->all();
        $arr = ArrayHelper::map($res,'id','name');
        return $arr;
    }

    public static function getPackInfo($user_id,$package_id)
    {
        $res = PackageUser::find()->where(['{{%package_user}}.user_id'=>$user_id,'package_id'=>$package_id])->select('{{%package}}.*')->innerJoin('{{%package}}','{{%package}}.id = {{%package_user}}.package_id')->asArray()->one();
        return $res;
    }
}
