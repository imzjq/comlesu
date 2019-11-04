<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;

use common\models\NodeKit as CommonNodeKit;
use yii\helpers\ArrayHelper;
use Yii;
class NodeKit extends CommonNodeKit
{
    use ApiTrait;
    protected $fileType = ['zip'];
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
        if($csdatas)
        {
            foreach ($csdatas as $key=>$val)
            $csdatas[$key]['is_default'] = ($val->is_default) ? '是':'否';
        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    public function add($data){
        $model = new NodeKit();
        if($data['is_default'] == 1)
        {
            $count = NodeKit::find()->where(['is_default'=>1])->count();
            if($count>0)
                return $this->error('只能有一个默认套件');
        }
        if($data && $model->load($data,'')){
            if($model->save() && $model->validate()){
                return $this->success();
            }else{
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }


        }
        return $this->error('参数错误');
    }

    public function updateInfo($data){
        $model = NodeKit::findOne($data['id']);
        if(!$model){
            return $this->error('未找到相应数据');
        }
        if($data['is_default'] ==1)
        {
            $count = NodeKit::find()->where("is_default = 1 AND id != :id",[':id'=>$data['id']])->count();
            if($count>0)
                return $this->error('只能有一个默认套件');
        }
        unset($data['id']);
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
    public function del($id){
        $model = NodeKit::findOne($id);

        if(!$model){
            return $this->error('未找到相应数据');
        }

        $node = Node::find()->where(['kit_id'=>$id])->one();
        if($node){
            return $this->error('该套件有在使用，节点ID:'.$node->id);
        }
        $del = $model->delete();
        if($del){
            NodeKitScript::deleteAll(['kit_id'=>$id]);
            if($model->is_default == 1)
            {
                $list = Node::find()->where(['>', 'kit_id', 0])->asArray()->select('id')->all();
                if(!empty($list))
                {
                    foreach ($list as $val)
                    {
                        $nodeModel = new Node();
                        $nodeModel->updateKit($val['id']);
                    }
                }

            }
        }
        return $this->success('','操作成功');
    }

    //获取分组数据 id=>name   ID=>名称
    public function idToName(){
        $res = NodeKit::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','name');
        return $arr;
    }


    //文件上传
    public function upload($file='file'){
        $path = Yii::getAlias('@dns_file')  . '/nodeKit/';
        if (!is_dir($path) || !is_writable($path)) {
            \yii\helpers\FileHelper::createDirectory($path, 0777, true);
        }
        if ($_FILES[$file]["error"] > 0) {
            $err =  $_FILES[$file]["error"];
            return $this->error($err);
        } else {
            $filename =  $_FILES[$file]["name"];
            $tmp = $_FILES[$file]["tmp_name"];
            $extension = pathinfo($filename)['extension'];
            if(!in_array($extension,$this->fileType)){
                $err = '格式错误';
                return $this->error($err);
            }else{
                $filename = time().$filename;
                $full_filename = $path.$filename;
                move_uploaded_file($tmp, $full_filename);
            }
        }
        return $this->success($filename);
    }

    public function getOne($id){
        $model = NodeKit::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        return $this->success($model);
    }

}
