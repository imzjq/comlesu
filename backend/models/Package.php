<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;

use common\models\Package as CommonPackage;
use common\models\PackageUser;
use Yii;
use yii\helpers\ArrayHelper;

class Package extends CommonPackage
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
            $datas = $list->asArray()->all();
            $nodeGroup = new NodeGroup();
            $groups = $nodeGroup->idToName();
            $defenceIp = new DefenceIp();
            $defenceIp = $defenceIp->idToCname();
            foreach ($datas as $key => $value)
            {
                $datas[$key]['group_id'] =  isset($groups[$value['group_id']]) ? $groups[$value['group_id']] : "" ;
                $datas[$key]['defence_group_id'] =  isset($groups[$value['defence_group_id']]) ? $groups[$value['defence_group_id']] : "" ;
                $datas[$key]['defence_ip_id'] =  isset($defenceIp[$value['defence_ip_id']]) ? $defenceIp[$value['defence_ip_id']] : "" ;

            }
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
        $model = new Package();
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
        $model = $this::findOne($data['id']);
        $omodel = $model->getOldAttributes();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        unset($data['id']);
        if($data && $model->load($data,'')){
            if ($model->save()) {
                $this->updateInfoConfig($omodel,$model);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }

        return $this->error('参数错误');
    }

    /**
     * 修改套餐后，更新加速和高防的配置
     */
    public function updateInfoConfig($omodel,$nmodel)
    {
        if($omodel['group_id'] != $nmodel->group_id )
        {
            Domain::updateAll(['node_group'=>$nmodel->group_id],['package_id'=>$nmodel->id,'stype'=>\common\models\Domain::STYPE_DOMAIN]);
            Defence::updateAll(['node_group'=>$nmodel->group_id],['package_id'=>$nmodel->id,'stype'=>\common\models\Domain::STYPE_DOMAIN]);
        }

        if($omodel['defence_group_id'] != $nmodel->defence_group_id )
        {
            Domain::updateAll(['node_group'=>$nmodel->defence_group_id],['package_id'=>$nmodel->id,'stype'=>\common\models\Domain::STYPE_DEFENCE]);
            Defence::updateAll(['node_group'=>$nmodel->defence_group_id],['package_id'=>$nmodel->id,'stype'=>\common\models\Domain::STYPE_DEFENCE]);
        }
        if($omodel['defence_ip_id'] != $nmodel->defence_ip_id )
        {
            Domain::updateAll(['high_anti'=>$nmodel->defence_ip_id],['package_id'=>$nmodel->id]);
            Defence::updateAll(['high_anti'=>$nmodel->defence_ip_id],['package_id'=>$nmodel->id]);
        }

    }

    //删除分组
    public function del($id){
        $model = $this::findOne($id);

        if(!$model){
            return $this->error('未找到相应数据');
        }

        $result = PackageUser::find()->where(['package_id'=>$id])->count();
        if($result>0){
            return $this->error('该套餐有在使用');
        }
        $del = $model->delete();

        return $this->success('','操作成功');
    }



    public function getOne($id){
        $model = $this::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['group_id'] =  (int)$model['group_id'];
        $model['defence_ip_id'] = (int)$model['defence_ip_id'];
        $model['defence_group_id'] =  (int)$model['defence_group_id'];
       //$model['node_id'] = explode(',',$model['node_ids']);
        return $this->success($model);
    }

    public function getUserPack($id)
    {
        $res = User::find()->where(['id'=>$id])->asArray()->one();
        $model = new User();
        $result =  $model->getPackInfo($res);
        return $this->success($result);
    }


    public function idToName()
    {
        $res = Package::find()->asArray()->all();
        $arr = ArrayHelper::map($res,'id','name');
        return $arr;
    }

}
