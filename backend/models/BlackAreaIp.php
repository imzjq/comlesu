<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace backend\models;
use common\models\BlackAreaIp as CommonBlackAreaIp;
use Yii;
class BlackAreaIp extends CommonBlackAreaIp
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
            if($datas)
            {
                $user = new User();
                $users = $user->getIdToUsername();
                $pack = new Package();
                $packages =  $pack->idToName();

                $ipareaModel = new \backend\models\Iparea();
                $iparea =  $ipareaModel->idToName();
                foreach ($datas as $key =>$value)
                {
                    $arr['id'] = $value['id'];
                    $arr['create_time'] = $value['create_time'];
                    $arr['username'] = isset($users[$value['user_id']]) ? $users[$value['user_id']] : "" ;
                    $arr['package_name'] = isset($packages[$value['package_id']]) ? $packages[$value['package_id']] : "";
                    $arr['home_id'] ="";
                    $arr['abroad_id'] ="";
                    if($value['home_id'])
                    {
                        $domain = unserialize($value['home_id']);
                        $arr['home_id'] =implode(",",$domain);
                    }
                    if($value['abroad_id'])
                    {
                        $domain = unserialize($value['abroad_id']);
                        foreach ($domain as $dval)
                        {
                            if(isset($iparea[$dval]))
                                $arr['abroad_id'] .=$iparea[$dval].",";
                        }

                    }
                    $csdatas[] = $arr;
                }
            }
        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    public function add($data){
        $model = new $this();
        if(empty($data['home_id'])) {
            $data['home_id'] = "";
        }else{
            $data['home_id'] = serialize($data['home_id']);
        }

        if(empty($data['abroad_id'])) {
            $data['abroad_id'] = "";
        }else{
            $data['abroad_id'] = serialize($data['abroad_id']);
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
        $model = $this::find()->where(['id'=>$data['id']])->one();

        if(!$model){
            return $this->error('未找到相应数据');
        }
        unset($data['id']);
        if(empty($data['home_id'])) {
            $data['home_id'] = "";
        }else{
            $data['home_id'] = serialize($data['home_id']);
        }
        if(empty($data['abroad_id'])) {
            $data['abroad_id'] = "";
        }else{
            $data['abroad_id'] = serialize($data['abroad_id']);
        }
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
    public function del($data){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        $count = $this::find()->select('domain,package_id')->where(['in','id',$ids])->count();
        if($count != count($ids))
            return $this->error('操作错误');
        $this::deleteAll(['in','id',$ids]);
        return $this->success('','操作成功');
    }



    public function getOne($id){
        $model = $this::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['package_id'] = (int)$model['package_id'];
        if(empty($model['home_id']))
        {
            $model['home_id'] = [];
        }else{
            $model['home_id'] =unserialize($model['home_id']);
        }
        if(empty($model['abroad_id']))
        {
            $model['abroad_id'] = [];
        }else{
            $model['abroad_id'] =unserialize($model['abroad_id']);
        }
        return $this->success($model);
    }

    /**
     * 获取国内的地区
     */
    public function getHomeIparea()
    {
       $list = \common\models\Iparea::find()->where(['country_id'=>'CN'])->select('province')->groupBy('province')->all();
        $data = [];
        if($list)
        {
            foreach ($list as $value)
                $data[]=['id'=>$value['province'],'label'=>$value['province'],'children'=>[]];
        }
        return $this->success($data);
    }


    /**
     * 获取国外的地区
     */
    public function getAbroadIparea()
    {
        $list = \common\models\Iparea::find()->where(['and',['<>','country_id','CN'],['<>','country_id','-1']])->select('id,country')->all();
        $data = [];
        if($list)
        {
            foreach ($list as $value)
                $data[]=['id'=>$value['id'],'label'=>$value['country'],'children'=>[]];
        }
        return $this->success($data);
    }


}
