<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/3
 * Time: 14:20
 */

namespace frontend\models;

use common\models\Agent as CommonAgent;

class Agent extends CommonAgent
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

            /**
             * 数据处理,
             */

            //获取area对应的remark
            $countyType = new CountryType();
            $areaRemark = $countyType->typeToRemark();

            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['username']  = $data->username;
                $arr['domain'] = $data->domain;
                $arr['level']  =($data->level==1)?'专业代理':'普通代理';
                $arr['area'] =(isset($areaRemark[$data->area]))?$areaRemark[$data->area]:$data->area;
                $csdatas[] = $arr;
            }

        }

        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    //
    public function getOne($id){
        $model = Agent::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['level'] = (int)$model['level'];
        return $this->success($model);
    }

    //添加代理
    public function add($data){
        $model = new Agent();
        if($data && $model->load($data,'')){
            $username = $data['username'];
            $user_info = User::find()->select('id')->where(['username'=>$username])->one();
            if(!$user_info){
                return $this->error('未找到相应用户');
            }
            //获取area对应的remark
            $countyType = new CountryType();
            $areaRemark = $countyType->typeToRemark();
            if(!isset($areaRemark[$data['area']])){
                return $this->error('代理上区域错误');
            }
            $model->userid = $user_info->id;
            if ($model->validate() && $model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //修改，不能修改用户，只能修改domain等其他信息
    public function updateInfo($data){
        $id = $data['id'];
        $model = Agent::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);
        $data['userid'] = $model->userid;
        $data['username'] = $model->username;
        if($model->load($data,'')){
            if($model->validators() && $model->save()){
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //删除
    public function del($id){
        $model = Agent::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        //判断user表中是否有当前代理的，如果有，则不能删除
        $check = User::find()->where(['agentid'=>$id])->one();
        if($check){
            return $this->error('代理下有用户，不能删除');
        }
        $del = Agent::delete($id);
        return $this->success();
    }


}
