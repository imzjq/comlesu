<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/7
 * Time: 22:25
 */

namespace frontend\models;

use common\models\DefenceIp as CommonDefenceIp;
use yii\helpers\ArrayHelper;

class DefenceIp extends CommonDefenceIp
{
    use ApiTrait;

    public function getList($page=1,$pagenum='',$where=[]){
        if(empty($pagenum)){
            $pagenum = $this->pagenum;
        }
        //设置起始位置
        $offset = 0;
        if(!empty($page) && is_numeric($page) && $page > 1){
            $offset = ($page-1) * $pagenum;
        }else{
            $page = 1;
        }
        $list = $this->find();

        if(!empty($where)){
            $i = 0;
            foreach ($where as $k=>$v){
                if($i==0){
                    $list->where($v);
                }else{
                    $list->andWhere($v);
                }
                $i++;
            }
        }
        $count = $list->count();  //总条数
        //echo $count;die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();

            if($datas){
                $cModel = new CountryType();
                $cInfo = $cModel->idToType();
                foreach($datas as $data){
                    $arr['id'] = $data->id;
                    $arr['cname']  = $data->cname;
                    $arr['ip'] = $data->ip;

                    $arr['country'] = isset($cInfo[$data->country]) ? $cInfo[$data->country] : "";
                    $arr['remark'] = ($data->remark)?'是':'否';

                    $csdatas[] = $arr;
                }
            }


        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    //add
    public function add($data){
        $model = new DefenceIp();
        if($data && $model->load($data,'')){
            $model->type = (string)$data['country'];
            if ($model->validate() && $model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //修改
    public function updateInfo($data){
        $id = isset($data['id'])?$data['id']:'';
        $model = DefenceIp::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);
        if($model->load($data,'')){
            $model->type = (string)$data['country'];
            if($model->validate() && $model->save()){
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function del($id){

        $model = DefenceIp::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        if($model->remark==1){
            return $this->error('这是条默认数据，删除前请设置新的默认数据');
        }

        //判断高防
        $defence = Defence::find()->where(['defence_ip_id'=>$id])->one();
        if($defence){
            return $this->error('高防包有在使用，不能删除');
        }

        //判断加速域名中是否有在用
        $domain = Domain::find()->where(['high_anti'=>$id])->one();
        if($domain){
            return $this->error('加速域名有在使用，不能删除');
        }
        $del = $model->delete();
        return $this->success();
    }


    //设置默认
    public function setDefault($data){
        if(isset($data['id']) && $model = DefenceIp::findOne($data['id'])){
            $transaction = \Yii::$app->db->beginTransaction();
            //先修改这个类型数据的isDefault = 0
            $country = $model->country;
            $up_one = DefenceIp::updateAll(['remark'=>0],['country'=>$country]);
            //再将这个ID 的isDefault 修改为1
            $model->remark =1;
            $up_two = $model->save();
            if($up_one && $up_two){
                $transaction->commit();
                return $this->success();
            }else{
                $transaction->rollBack();
                return $this->errors('设置失败');
            }
        }
        return $this->error('参数错误');
    }

    public function getOne($id){
        $model = DefenceIp::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $ip = $model['ip'];
        $model = $this->numToInt($model);
        $ip_arr = explode("|",$ip);
        $model['ip1'] = $ip_arr[0];
        $model['ip2']  = $ip_arr[1];
        return $this->success($model);
    }

    //id=>cname
    public function idToCname(){
        $res = DefenceIp::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','cname');
        return $arr;
    }
}
