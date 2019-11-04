<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;
use common\lib\Utils;
use common\models\Hsts as CommonHsts;
use Yii;
class Hsts extends CommonHsts
{
    use ApiTrait;
    public function getList($page,$pagenum='',$where=[],$uid){
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
        //var_dump($list->createCommand()->getRawSql());die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();
            if($datas)
            {
                $user = new User();
                $users = $user->getIdToUsername();
                $pack = new Package();
                $packages =  $pack->idToName();
                foreach ($datas as $key =>$value)
                {
                    $arr['id'] = $value['id'];
                    $arr['url'] = $value['url'];
                    $arr['create_time'] = date('Y-m-d H:i',$value['create_time']);
                    $arr['username'] = isset($users[$value['user_id']]) ? $users[$value['user_id']] : "" ;
                    $arr['package_name'] = isset($packages[$value['package_id']]) ? $packages[$value['package_id']] : "";
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



    public function updateInfo($data){
        $model = $this::find()->where(['id'=>$data['id']])->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        if(!Utils::isIp($data['ip']))
            return $this->error('ip格式错误');
        if($data && $model->load($data,'')){
            if ($model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //删除分组
    public function del($data){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        $this::deleteAll(['in','id',$ids]);
        return $this->success('','操作成功');
    }

    public function getOne($id){
        $model = $this::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        return $this->success($model);
    }



}
