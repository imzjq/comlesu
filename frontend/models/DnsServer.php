<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/8
 * Time: 22:56
 */

namespace frontend\models;

use common\lib\FileUtil;
use common\models\DnsServer as CommonDnsServer;
class DnsServer extends CommonDnsServer
{

    public static $switchMap = [
        1=>'开',
        0=>'关'
    ];

    public static $forbiddenMap = [
        1=>'禁止',
        0=>'正常'
    ];
    public static $statusMap = [
        1=>'正常',
        0=>'出错'
    ];

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
            /*
            * 数据处理,
             */
            $countryTypeModel = new CountryType();
            $typeMap = $countryTypeModel->idToType();
            foreach($datas as $data){
                $arr['id'] = $data->id;
                $arr['ip']  = $data->ip;
                $arr['name'] = $data->name;
                $arr['area']  =$data->area;
                $arr['dns_name'] = $data->dns_name;
                $arr['TTL'] =$data->TTL;
                $arr['switch'] = self::$switchMap[$data->switch];
                $arr['forbidden']  =self::$forbiddenMap[$data->forbidden];
                $arr['status'] = self::$statusMap[$data->status];
                $arr['type'] = isset($typeMap[$data->type]) ? $typeMap[$data->type]: "" ;
                $arr['weight'] =$data->weight;
                $csdatas[] = $arr;
            }
            //$csdatas =$datas;
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

        $model = new DnsServer();
        if($data && $model->load($data,'')){
            $ipModel = new Ipdatabase();
            $group_id = $ipModel->ipGetGroupId($data['ip']);
            if(!$group_id){
                $group_id =999;
                //return $this->error('未找到相应分组');
            }
            $model->switch = 1;
            $model->forbidden = 0;
            $model->group_id = $group_id;
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
    public function updateInfo($data){
        $id = isset($data['id'])?$data['id']:'';
         $model = DnsServer::findOne($id);
        if(!$model){
            return $this->error('未找到相应信息');
        }
        unset($data['id']);
        if($model->ip !== $data['ip']){
            $ipModel = new Ipdatabase();
            $group_id = $ipModel->ipGetGroupId($data['ip']);
            if(!$group_id){
                $group_id =888;
                //return $this->error('未找到相应分组');
            }
            $data['group_id'] = $group_id;
        }
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
        $res = DnsServer::deleteAll(['in','id',$ids]);
        if($res)
        return $this->success();
        else
         return $this->error();
    }

    public function changeStatus($data){
        $ids = $data['id'];
        $switch = $data['switch'];
        if(!in_array($switch,[0,1])|| empty($ids)){
            return $this->error('参数错误');
        }
        //批量修改
        $res = DnsServer::updateAll(['switch'=>$switch],['in','id',$ids]);
        if($res){
            return $this->success();
        }
        return $this->error('保存失败');
    }

    public function getOne($id){
        $model = DnsServer::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model = $this->numToInt($model);
        return $this->success($model);
    }


    /**
     * curl 文本
     * @param $action 操作
     * @param $newData 新数据
     * @param null $old 旧数据
     */



}
