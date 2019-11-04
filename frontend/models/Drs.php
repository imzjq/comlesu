<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/13
 * Time: 20:37
 */

namespace frontend\models;

use common\models\Drs as CommonDrs;
class Drs extends CommonDrs
{
    use ApiTrait;

    public function getList($data,$userInfo){
        $did = $data['did'];
        if(!$this->checkBaseData($data,$userInfo['uid'])){
            return $this->error('参数错误');
        }
        $model = $this->find()->where(['did'=>$did])->asArray()->all();
        $result['page'] = 0; //当前页码
        $result['count']= 0; //总条数
        $result['allpage'] = 0 ;
        $result['datas'] = $model;
        return $this->success($result);
    }

    public function add($data,$userInfo){
        if(!$dname = $this->checkBaseData($data,$userInfo['uid'])){
            return $this->error('参数错误');
        }

        $model = new Drs();
        if($data && $model->load($data,'')){
            $model->intime = time();
            $model->dname = $dname;
            $model->route = 'default';
            if ($model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        $msg = ($this->getModelError($model));
        return $this->error($msg);
    }

    //修改
    public function updateInfo($data,$userInfo){

        if(!$dname = $this->checkBaseData($data,$userInfo['uid'])){
            return $this->error('参数错误');
        }

        $id = isset($data['id'])?$data['id']:'';
        $model = Drs::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);

        if($model->load($data,'')){
            $model->dname = $dname;
            if( $model->save()){
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //删除
    public function del($id,$userInfo){
        $model = Drs::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        $drsd = Drsd::find()->where(['user_id'=>$userInfo['uid'],'id'=>$model->did])->select('id')->one();
        if(!$drsd)
        {
            return $this->error('未找到相应信息2');
        }
        $model->delete();
        return $this->success();
    }

    protected function checkBaseData($data,$user_id){
        $did = $data['did'];
        if(empty($did)){
           return false;
        }
        //$model = Drsd::findOne($did);
        $model = Drsd::find()->where(['id'=>$did,'user_id'=>$user_id])->one();
        if(!$model){
            return false;
        }
        return $model->dname;
    }

    public function getOne($id){
        $model = Drs::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model = $this->numToInt($model);
        return $this->success($model);
    }
}
