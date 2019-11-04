<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 20:11
 */

namespace frontend\models;

use common\models\User as CommonUser;
use yii\helpers\ArrayHelper;

class User extends CommonUser
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
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['username']  = $data->username;
                $arr['realname'] = $data->realname;
                $arr['last_login_ip']  =$data->last_login_ip;
                $arr['last_login_time'] = date('Y-m-d H:i:s',$data->last_login_time);
                $arr['login_count'] =$data->login_count;
                $arr['create_time'] = date('Y-m-d H:i:s',$data->create_time);
                $arr['status'] = $data->status;
                $arr['level'] = $data->level;
                $arr['role'] = ($data->role)?$data->role:'0';
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


    public function getOne($id){
        $model = User::find()->select('id,username,status,level')->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['status'] = (int)$model['status'];
        $model['level'] = (int)$model['level'];
        return $this->success($model);
    }

    //新增用户
    public function add($data){
        $model = new User();
        if($data && $model->load($data,'')){
            $model->password = md5($model->password);
            $model->create_time = time();
            if ($model->validate() && $model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        return $this->error('参数错误');
    }

    //修改用户信息,状态，vip,是否代理
    public function updateInfo($data){
        $id = $data['id'];
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        $status = $data['status'];
        $level = $data['level'];

        //检查参数
        if(!in_array($status,[0,1]) || !in_array($level,[0,1])){
            return $this->error('参数错误');
        }
        $model->status = $status;
        $model->level = $level;
        if($model->save()){
            return $this->success();
        }
        $msg = ($this->getModelError($model));
        return $this->error($msg);

    }

    //修改密码
    public function changePassword($data){
        $id = $data['id'];
        $password = $data['password'];
        if(!$password){
            return $this->error('密码不能为空');
        }
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应用户');
        }
        $model->password = md5($password);
        if($model->save()){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    //删除用户
    public function delUser($id){
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $del = User::delete($id);
        return $this->success('','操作成功');
    }

    //激活用户（修改状态）
    public function changeStatus($data){
        $id = $data['id'];
        $status = $data['status'];
        $model = User::findOne($id);
        if(!$model){
            return $this->error('未找到相应用户');
        }
        if(!in_array($status,[0,1])){
            return $this->error('参数错误');
        }
        $model->status = $status;
        if($model->save()){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    //获取用户 id=>username
    public function getIdToUsername(){
        $res = User::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','username');
        return $arr;
    }

    //获取用户  username=>username
    public function getUsernameToUsername(){
        $res = User::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'username','username');
        return $arr;
    }

    public function getUserIdByUsername($username){
        $res = User::find()->where(['username'=>$username])->one();
        if(!$res){
            return false;
        }
        return $res['id'];
    }
}
