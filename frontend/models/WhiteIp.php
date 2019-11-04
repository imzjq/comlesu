<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace frontend\models;
use common\lib\Utils;
use common\models\WhiteIp as CommonWhiteIp;
use Yii;
class WhiteIp extends CommonWhiteIp
{
    use ApiTrait;
    public function getList($page,$pagenum='',$where=[],$uid){
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
                $brandModel = new Brand();
                $brands = $brandModel->idToName($uid);
                $pack = new Package();
                $packages = $pack->idToName($uid);
                foreach ($datas as $key =>$value)
                {
                    $arr['id'] = $value['id'];
                    $arr['ip'] = $value['ip'];
                    $arr['create_time'] = date('Y-m-d H:i',$value['create_time']);
                    $arr['brand_name'] = isset($brands[$value['brand_id']]) ? $brands[$value['brand_id']] : "";
                    $arr['package_name'] = isset($packages[$value['package_id']]) ? $packages[$value['package_id']] : "";
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

    public function add($data,$uid){
        $model = new $this();
        $data['user_id'] = $uid;
        if(!Utils::isIp($data['ip']))
            return $this->error('ip格式错误');

        $limit =  $this->packageLimit($uid,$data['package_id'],1);
        if($limit['code'] != 200)
        {
            return $limit;
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


    public function addBatch($data,$uid){
        $data['user_id'] = $uid;
        $ips = $data['ips'];
        if(!$ips)
            return $this->error('请填写ip');
        $transaction = \Yii::$app->db->beginTransaction();
        $arr=explode("\n",$ips);
        $arr = array_filter($arr);
        $count =  count($arr);
        if($count == 0)
            return $this->error('请填写ip');

        $limit =  $this->packageLimit($uid,$data['package_id'],$count);
        if($limit['code'] != 200)
        {
            $transaction->rollBack();
            return $limit;
        }

        foreach ($arr as $value) {
            if(!Utils::isIp($value)) {
                $transaction->rollBack();
                return $this->error('ip：'.$value.'格式错误');
            }
            $model =  new $this();
            $model->user_id = $uid;
            $model->ip = $value;
            $model->package_id = $data['package_id'];
            $model->brand_id = $data['brand_id'];
            if(!$model->save() && !$model->validate())
            {
                $transaction->rollBack();
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
        }

        $transaction->commit();
        return $this->success();
    }

    public function updateInfo($data,$uid){
        $model = $this::find()->where(['id'=>$data['id'],'user_id'=>$uid])->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        unset($data['id']);
        if(!Utils::isIp($data['ip']))
            return $this->error('ip格式错误');
        $package_id = $model->getOldAttribute('package_id');
        if($package_id != $data['package_id'])
        {
            $limit =  $this->packageLimit($uid,$data['package_id'],1);
            if($limit['code'] != 200)
            {
                return $limit;
            }
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
    public function del($data,$uid){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        $count = $this::find()->where(['and',['user_id'=>$uid],['in','id',$ids]])->count();
        if($count != count($ids))
            return $this->error('操作错误');
        $this::deleteAll(['and',['in','id',$ids],['user_id'=>$uid]]);
        return $this->success('','操作成功');
    }

    public function getOne($id,$uid){
        $model = $this::find()->where(['id'=>$id,'user_id'=>$uid])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $model['package_id'] = (int)$model['package_id'];
        if(empty($model['brand_id']))
            $model['brand_id'] = '';
            else
        $model['brand_id'] = (int)$model['brand_id'];
        return $this->success($model);
    }

    public function packageLimit($user_id,$package_id,$num)
    {
        $res = Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');
        $count = $this::find()->where(['user_id'=>$user_id,'package_id'=>$package_id])->count();
        $count_all = $count + $num;
        if($count_all>$res['white_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['white_quantity']."个,已创建".$count."个");
        }
        return $this->success();
    }

}
