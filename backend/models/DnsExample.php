<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:10
 * 节点lc_node
 */

namespace backend\models;


use common\models\DnsExample as CommonDnsExample;
use yii\helpers\ArrayHelper;

class DnsExample extends CommonDnsExample
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
        $count = $list->count();  //总条数
        $allpage = 1;

        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $datas = [];
        if($page <= $allpage) {
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->all();
        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $datas;

        return $this->success($result);
    }

    public function getOne($id){
        $model = $this->find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success($model);
    }

    public function add($data){
        //$model = new DnsExample();
        $model =  new $this;
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
        $model = $this->findOne($id);
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

    public function del($id){
        $model = DnsExample::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model->delete();
        return $this->success('','操作成功');
    }



}
