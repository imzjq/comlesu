<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;
use common\models\NodeTag as CommonNodeTag;
use common\models\NodeTagId;
use yii\helpers\ArrayHelper;
use Yii;
class NodeTag extends CommonNodeTag
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

        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    public function add($data){
        $model = new NodeTag();
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
        $model = NodeTag::findOne($data['id']);
        if(!$model){
            return $this->error('未找到相应数据');
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
        $model = NodeTag::findOne($id);

        if(!$model){
            return $this->error('未找到相应数据');
        }
        $del = $model->delete();
        if($del)
        {
            NodeTagId::deleteAll(['tag_id'=>$id]);
        }
        return $this->success('','操作成功');
    }

    //获取分组数据 id=>name   ID=>名称
    public function idToName(){
        $res = NodeTag::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','name');
        return $arr;
    }




    public function getOne($id){
        $model = NodeTag::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        return $this->success($model);
    }

}
