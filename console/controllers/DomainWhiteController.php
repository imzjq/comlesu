<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/19
 * Time: 19:51
 */

namespace console\controllers;


use common\components\Logger;
use common\models\Node;

class DomainWhiteController extends BaseController
{

    public function actionIndex(){
        $file = '/home/tongbu/conf/domainWhite.txt';
        $res_node = Node::find()->asArray()->all();
        $arr_node = array();
        if($res_node){
            foreach($res_node as $v){
                $arr_node[$v['id']] = $v['ip'];
            }
        }else{
            Logger::warning('节点数据为空');
        }

    }

}
