<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;

use common\models\NodeKitScript as CommonNodeKit;
use yii\helpers\ArrayHelper;
use Yii;
class NodeKitScript extends CommonNodeKit
{
    use ApiTrait;
    public function getList($page,$pagenum='',$where=[]){
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
        $model = new NodeKitScript();
        if($data && $model->load($data,'')){
           $result =  $this->exitsScript($data);
           if($result)
               return $this->error('脚本名称已经存在');
            if($model->save() && $model->validate()){
                $nodeModel = new Node();
                $nodeModel->updateKits($model->kit_id);
                return $this->success();
            }else{
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
        }
        return $this->error('参数错误');
    }

    public function updateInfo($data){
        $model = NodeKitScript::findOne($data['id']);
        $oldModel =  $model->getOldAttributes();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $result =  $this->exitsScript($data);
        if($result)
            return $this->error('脚本名称已经存在');
        unset($data['id']);
        if($data && $model->load($data,'')){
            if ($model->save()) {
                if($oldModel['name'] != $model->name ||  $oldModel['content'] != $model->content)
                {
                    $nodeModel = new Node();
                    $nodeModel->updateKits($model->kit_id);
                }
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    //删除脚本
    public function del($id){
        $model = NodeKitScript::findOne($id);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $del = $model->delete();
        $nodeModel = new Node();
        $nodeModel->updateKits($model->kit_id);
        return $this->success('','操作成功');
    }


    public function getOne($id){
        $model = NodeKitScript::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        return $this->success($model);
    }

    /**
     * 判断套件中是否有该脚本
     * @param $data
     */
    public function exitsScript($data)
    {
        $model = NodeKitScript::find()->where(['kit_id'=>$data['kit_id'],'name'=>$data['name']]);
        if(!empty($data['id']))
            $model->andWhere(['<>','id',$data['id']]);
        $result = $model->count();
        return $result;
    }

}
