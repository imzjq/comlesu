<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/8
 * Time: 22:26
 */

namespace backend\models;

use common\lib\Utils;
use common\models\LsIpku as CommonIpku;
use common\models\LsIpku;

class Ping extends CommonIpku
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
            $list->offset($offset)->limit($pagenum)->orderBy('id desc');
            $datas = $list->all();
            $csdatas =$datas;

        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    public function add($data)
    {
        $model = new LsIpku();
        if($data && $model->load($data,'')){
            if ($model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
    }

    public function getOne($id){
        $model = LsIpku::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success($model);
    }

    public function updateInfo($data)
    {
        $ip = $data['ip'];
        if(empty($ip)){
            return $this->error('请输入IP');
        }
        $check = Utils::isIp($ip);
        if(!$check){
            return $this->error('IP格式错误');
        }
        $ipModel = new Ipdatabase();
        $group_id = $ipModel->ipGetGroupId($ip);
        if(!$group_id){
            return $this->error('未找到相应分组号');
        }
        $data['group_id'] = $group_id;
        $model = LsIpku::findOne($data['id']);
        if($data && $model->load($data,'')){
            if ($model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
    }

    public function del($data)
    {
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        $model = LsIpku::deleteAll(['in','id',$ids]);
        return $this->success();


    }






}
