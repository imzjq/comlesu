<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/8
 * Time: 22:26
 */

namespace backend\models;

use common\models\IpdatabaseSimplify;
use common\models\Route as CommonRoute;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class Route extends CommonRoute
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
            $list->offset($offset)->limit($pagenum)->orderBy('MS asc');
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

    //根据ip 查询 MS， 通过IP获取分组号，根据分组号查MS
    public function getMsByIp($ip){
        $ipModel = new IpdatabaseSimplify();
        $group_id = $ipModel->ipGetGroupId($ip);
        if(!$group_id){
            return $this->error('参数错误或未找到相应分组');
        }
        $res = Route::find()->where(['group_id'=>$group_id])->asArray()->all();
        return $this->success($res);
    }

    //批量修改ms值
    public function updateMs($group_ids, $ms,$node_id){
        if(!$ms || !is_numeric($ms)){
            return $this->error('请输入MS值');
        }
        if(!$node_id || !is_numeric($node_id)){
            return $this->error('请输入节点id');
        }
        if(!is_array($group_ids)){
            return $this->error('请选择分组');
        }

        if(empty($group_ids)){
            return $this->error('未找到相应分组');
        }

        $res = Route::updateAll(['MS'=>$ms],['and',['node_id'=>$node_id],['in','group_id',$group_ids]]);
        if($res){
            return $this->success();
        }
        return $this->error('操作失败');
    }


    //通过country_id,service_id 或group_id
    public function getGroupId($country_id=[],$service_id=[]){
        $group_ids = [];
        $where_area = [];
        if($country_id && is_array($country_id)){
            $where_area[] = ['in','country_id',$country_id];
        }else{
            return $group_ids;
        }
        if($service_id && is_array($service_id)){
            $where_area[] = ['in','service_id',$country_id];
        }else{
            return $group_ids;
        }
        $iparea = Iparea::find();
        if(!empty($where_area)){
            $i = 0;
            foreach ($where_area as $k=>$v){
                if($i==0){
                    $iparea->where($v);
                }else{
                    $iparea->andWhere($v);
                }
                $i++;
            }
        }
        $iparea_datas = $iparea->all();

        if($iparea_datas){
            foreach ($iparea_datas as $v){
                $group_ids[] = $v->id;
            }
        }
        return $group_ids;
    }


    public function updateInfo($data){
        $id = isset($data['id'])?$data['id']:'';
        unset($data['id']);
        $model = $this::findOne($id);
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


}
