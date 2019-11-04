<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 21:10
 * 节点lc_node
 */

namespace backend\models;
use common\models\NodeRemark as CommonRemark;
use yii\helpers\ArrayHelper;
class NodeRemark extends CommonRemark
{
    use ApiTrait;


    public static $groupName;

    public function getList($page=1,$pagenum='',$where=[]){
        if(empty($pagenum)){
            $pagenum = $this->pagenum;
        }
        $pagenum = 1000;
        //设置起始位置
        $offset = 0;
        if(!empty($page) && is_numeric($page) && $page > 1){
            $offset = ($page-1) * $pagenum;
        }else{
            $page = 1;
        }
        $list = $this->find()->alias('r')->innerJoin("{{%node}} as n",'n.id = r.node_id')->select('r.id,r.other_ip,r.remark,r.password,n.zabbix_ip,n.tag_ids');

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
        $allpage = 1;
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->asArray()->all();

            if($datas){
                $tagList = NodeTag::find()->asArray()->all();
                $tagList = ArrayHelper::map($tagList,'id','name');

                foreach($datas as $data){
                    $arr['id'] = $data['id'];
                    $arr['other_ip']  = $data['other_ip'];
                    $arr['remark'] =  $data['remark'];
                    $arr['password']  = $data['password'];
                    $arr['zabbix_ip'] =  $data['zabbix_ip'];
                    $arr['tag_ids'] =  $data['tag_ids'];
                    $lable = '';
                    if($data['tag_ids'])
                    {
                        $tags = explode(',',$data['tag_ids']);
                        foreach ($tags as $tagVal) {
                            if (isset($tagList[$tagVal])){
                                $lable .= $tagList[$tagVal].",";
                            }
                        }
                    }
                    $arr['lable'] = $lable;//标签
                    $csdatas[] = $arr;
                }
            }
        }

        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        //var_dump($result);die;
        return $this->success($result);
    }


    public function getOne($id){
        $model = $this::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success($model);
    }

    public function updateRemark($data){
        $model = $this::findOne($data['id']);
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



}
