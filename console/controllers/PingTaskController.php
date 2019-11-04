<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/12
 * Time: 19:20
 */

namespace console\controllers;

use common\components\Logger;
use common\models\IpCache;
use common\models\PingTmp;
use common\models\Route;
use common\models\Node;
class PingTaskController extends BaseController
{
    public function actionIndex(){
        $node_count = Node::find()->count();
        $node_count = 2;

        $obj = new \swoole_process([$this,'processWork'],true);


    }


    public function processWork(\swoole_process $worker){
        $node_id = $worker->read();
        $this->disposePing($node_id);
    }


    public function disposePing($node_id){
        $model = PingTmp::find()->where(['node_id'=>$node_id])->asArray()->all();
        $msg = "ping task 节点id ".$node_id ;
        if($model){
            foreach ($model as $v){
                $node = $v['node_id'];
                $group_id = $v['group_id'];
                $ms = $v['MS'];
                $ping_ip = $v['ping_ip'];

                //判断route中是否存在，不存在直接写入
                $routeModel = Route::find()->where(['node_id'=>$node])->andWhere(['group_id'=>$group_id])->one();
                if(!$routeModel){
                    $this->disposeIpCache($ping_ip,$group_id);
                    $m = new Route();
                    $m->group_id = $group_id;
                    $m->node_id = $node;
                    $m->MS = $ms;
                    if(!$m->save()){
                        //保存失败，日志记录，或者报警
                        $err = json_encode($m->getErrors());
                        $msg .=" route 报错失败 ：". $err;
                        Logger::warning($msg);
                    }
                    continue;
                }else{
                    //存在，判断其值
                    $old_ms = $routeModel->MS;
                    //新MS与旧MS上下相差5以内则不作修改，大于20以上则也不作修改，其余则直接修改
                    $abs = abs(($old_ms-$ms));
                    if($abs <=5 || $abs >=20 ){
                        //忽略
                    }else{
                        $this->disposeIpCache($ping_ip,$group_id);
                        //更新
                        $routeModel->MS = $ms;
                        if(!$routeModel->save()){
                            //保存失败，日志记录，或者报警
                            $err = json_encode($routeModel->getErrors());
                        }
                    }
                }
            }
            //删除ping_tmp数据
            PingTmp::deleteAll(['node_id'=>$node_id]);

        }
    }


    public function disposeIpCache($ip,$group_id){
        //判断IP在ip_cache中是否存在
        $model = new IpCache();
        $res = IpCache::find()->where(['ip'=>$ip])->one();
        if(!$res){
            //不存在，判断分组ID个数
            $count = IpCache::find()->where(['group_id'=>$group_id])->count();
            if($count<5){
                //直接写入
                $model->group_id = $group_id;
                $model->ip = $ip;
                if(!$model->save()){
                    //保存失败，日志记录，或者报警
                    $err = json_encode($model->getErrors());
                }
            }else{
                //先删除一个
                $del =IpCache::find()->where(['group_id'=>$group_id])->one();
                $del->delete();
                $model->group_id = $group_id;
                $model->ip = $ip;
                if(!$model->save()){
                    //保存失败，日志记录，或者报警
                    $err = json_encode($model->getErrors());
                }
            }

        }

    }

}
