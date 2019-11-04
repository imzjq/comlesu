<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/12
 * Time: 21:32
 */

namespace backend\models;

use common\models\Drsd as CommonDrsd;
class Drsd extends CommonDrsd
{
    public static $switchMap = [
        1=>'开',
        0=>'关'
    ];

    public static $statusMap = [
        1=>'已审核',
        0=>'审核中'
    ];
    use ApiTrait;
    public function getList($page=1,$pagenum='',$where=[],$typeTrue = false){
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

        if($typeTrue)
            $list->leftJoin('{{%user}}','{{%user}}.username = {{%drsd}}.username');

        $count = $list->count();  //总条数
        //echo $count;die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->select('{{%drsd}}.*')->offset($offset)->limit($pagenum)->orderBy('{{%drsd}}.id DESC');
            $datas = $list->asArray()->all();

            /*
          * 数据处理,
           */
            $pack = new Package();
            $packages = $pack->idToName();
            foreach($datas as $data){
                $arr['id'] = $data['id'];
                $arr['username']  = $data['username'];
                $arr['dname'] = $data['dname'];
                $arr['intime']  = date('Y-m-d H:i:s',$data['intime']);
                $arr['white_switch'] = self::$switchMap[$data['white_switch']];
                $arr['status'] = self::$statusMap[$data['status']];
                $arr['package_name'] = isset($packages[$data['package_id']]) ? $packages[$data['package_id']] : "";

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

    //add
    public function add($data){
        $model = new Drsd();
         $userModel = new User();
         $user_id = $userModel->getUserIdByUsername($data['username']);
         if($user_id == false)
             return $this->error('找不到用户');
         $data['user_id'] = $user_id;

        $limit =  $this->packageLimit($data,1);
        if($limit['code'] != 200)
            return $limit;
        if($data && $model->load($data,'')){
            $model->intime = time();
            if ($model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        $msg = ($this->getModelError($model));
        return $this->error($msg);
    }

    public function packageLimit($data,$num)
    {

        $user_id = $data['user_id'];
        $package_id = $data['package_id'];
        $res = \frontend\models\Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');
        $count = Drsd::find()->where(['user_id'=>$user_id,'package_id'=>$package_id])->count();
        $count_all =$count + $num;
        if($count_all>$res['drsd_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['drsd_quantity']."个,已创建".$count."个");
        }
        return $this->success();
    }


    //修改
    public function updateInfo($data){
        $id = isset($data['id'])?$data['id']:'';
        $model = Drsd::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);
        $userModel = new User();
        $user_id = $userModel->getUserIdByUsername($data['username']);
        if($user_id == false)
            return $this->error('找不到用户');
        $data['user_id'] = $user_id;

        $package_id = $model->getOldAttribute('package_id');
        if($package_id != $data['package_id'])
        {
            $limit =  $this->packageLimit($data,1);
            if($limit['code'] != 200)
            {
                return $limit;
            }
        }


        if($model->load($data,'')){
            if( $model->save()){
                Drs::updateAll(['did'=>$model->id],['dname'=>$model->dname]);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function del($data){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        Drsd::deleteAll(['in','id',$ids]);
        Drs::deleteAll(['in','did',$ids]);
        return $this->success();
    }

    //状态修改
    public function changeStatus($data){
        $ids = $data['id'];
        $status = $data['status'];
        if(!in_array($status,[0,1])|| empty($ids)){
            return $this->error('参数错误');
        }
        //批量修改
        $res = Drsd::updateAll(['status'=>$status],['in','id',$ids]);
        if($res){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    public function getOne($id){
        $model = Drsd::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        $model['white_start_time'] = date('Y-m-d',strtotime($model['white_start_time']));
        $model['white_end_time'] = date('Y-m-d',strtotime($model['white_end_time']));
        $result = $this->getCname(['username'=>$model['username']]);
        $model['icp'] = (int)$model['icp'];
        $model['high_anti'] = (int)$model['high_anti'];
        $model['package_id'] = (int)$model['package_id'];
        $model['status'] = (int)$model['status'];
        if($result['code'] == 200 )
            $model['cname'] = $result['data']['suffix'];
        return $this->success($model);
    }

    public function getCname($data)
    {
        $username = $data['username'];
        $userInfo = User::find()->where(['username'=>$username])->select('registsource')->one();
        if(!$userInfo){
            return $this->error('用户不存在');
        }
        $registsource = $userInfo['registsource']; //注册来源
        if(!$registsource){
            return $this->error('用户注册来源不存在');
        }
        $domain = new Domain();
        $result = $domain->getCnameSuffix($registsource);
        return $result;
    }


}
