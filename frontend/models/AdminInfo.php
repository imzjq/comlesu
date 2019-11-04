<?php

namespace frontend\models;

use Yii;
use common\models\Admin as CommonAdmin;
class AdminInfo extends CommonAdmin
{
    use ApiTrait;
    public function getAdminList($page=1,$pagenum=''){
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

        $adminList = $this->find()->where('status!=:status',[':status'=>0]);
        $count = $adminList->count();  //总条数
        //echo $count;die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $adminList->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $adminList->all();

            /**
             * 数据处理,
             */
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['username']  = $data->username;
                $arr['realname'] = $data->realname;
                $arr['last_login_ip']  =$data->last_login_ip;
                $arr['last_login_time'] = $data->last_login_time;
                $arr['login_count'] =$data->login_count;
                $arr['create_time'] = date('Y-m-d H:i:s',$data->create_time);
                $csdatas[] = $arr;
            }

        }

        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        //var_dump($result);die;
        return $this->success($result);
    }

    //新增
    public function add($data){
        $model = new AdminInfo();
        if($data && $model->load($data,'')){
            $model->password = md5($model->password);
            $model->create_time = time();
            $model->status = 1;
            if ($model->validate() && $model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        return $this->error('参数错误');
    }

    //修改密码
    public function changePassword($id,$password){
        if(!$password){
            return $this->error('密码不能为空');
        }
        $model = AdminInfo::findOne($id);
        if(!$model){
            return $this->error('未找到相应用户');
        }
        $model->password = md5($password);
        if($model->save()){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    //删除
    public function delAdmin($id){
        $model = AdminInfo::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $del = AdminInfo::delete($id);
        return $this->success('','操作成功');
    }

    public function getOne($id){
        $model = AdminInfo::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success();
    }
}
