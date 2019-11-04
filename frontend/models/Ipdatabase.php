<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/8
 * Time: 22:00
 */

namespace frontend\models;

use common\models\Iparea;
use common\models\IpdatabaseSimplify;
class Ipdatabase extends IpdatabaseSimplify
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
            $csdatas =$datas;

        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    public function add($data){
        $model = new IpdatabaseSimplify();
        if($data && $model->load($data,'')){
            if ($model->save()) {
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
        $model = IpdatabaseSimplify::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);
        if($model->load($data,'')){
            if( $model->save()){
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
        $model = IpdatabaseSimplify::deleteAll(['in','id',$ids]);
        return $this->success();
    }



    public function getIparea(){
        $this->hasOne(Iparea::className(),['id'=>'group_id'])->one();
    }

    //根据IP查询分组，列出

    //根据IP段，查询相应的分组，判断ip段 是否在同一个分组组
    public function ipduanToGroupId($ipduan){
        $check = $this->ip_parse($ipduan);
        if($check===false){
            return $this->error('请输入正确格式IP段');
        }else{
            list($ip_start, $ip_end) = $check;
            //对ip_start ip_end检查是否有分组，若其中一个有分组号，则提示信息，且不写入
            $checkGroupS = $this->ipGetGroupId($ip_start);
            $checkGroupE = $this->ipGetGroupId($ip_end);
            if($checkGroupS===false && $checkGroupE===false){
                return $this->error("未找到相应分组 第一可用：$ip_start  最后可用：$ip_end");
            }else{
                //
                if($checkGroupS!=$checkGroupE){
                    return $this->error("IP段有不在同一分组号中 分组ID1:$checkGroupS  分组ID2：$checkGroupE");
                }
                return $this->success($checkGroupS);
            }
        }
    }




}
