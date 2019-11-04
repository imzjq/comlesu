<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/12
 * Time: 21:32
 */

namespace frontend\models;

use common\models\Drsd as CommonDrsd;
class Drsd extends CommonDrsd
{
    public static $switchMap = [
        1=>'开',
        0=>'关'
    ];

    public static $statusMap = [
        1=>'已审核',
        0=>'审核中'
    ];
    use ApiTrait;
    public function getList($page=1,$pagenum='',$where=[],$uid){
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
            $datas = $list->asArray()->all();
            /*
          * 数据处理,
           */
            $brandModel = new Brand();
            $brands = $brandModel->idToName($uid);
            $pack = new Package();
            $packages = $pack->idToName($uid);
            foreach($datas as $data){
                $arr['id'] = $data['id'];
                $arr['dname'] = $data['dname'];
                $arr['intime']  = date('Y-m-d H:i:s',$data['intime']);
                $arr['white_switch'] = self::$switchMap[$data['white_switch']];
                $arr['status'] = self::$statusMap[$data['status']];
                $arr['brand_name'] = isset($brands[$data['brand_id']]) ? $brands[$data['brand_id']] : "";
                $arr['package_name'] = isset($packages[$data['package_id']]) ? $packages[$data['package_id']] : "";
                $csdatas[] = $arr;
            }
        }
        //数据列表
        $result['page'] = (int)$page; //当前页码
        $result['count']= intval($count); //总条数
        $result['allpage'] = (int)$allpage ;
        $result['datas'] = $csdatas;
        return $this->success($result);
    }

    //add
    public function add($data,$userInfo){
        $model = new Drsd();
        $data['user_id'] = $userInfo['uid'];
        $data['username'] = $userInfo['username'];
        $data['status'] = 1;

        $limit =  $this->packageLimit($data,1);
        if($limit['code'] != 200)
        {
            return $limit;
        }
        if($data && $model->load($data,'')){
            $model->intime = time();
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
    public function updateInfo($data,$userInfo){
        $id = isset($data['id'])?$data['id']:'';
        //$model = Drsd::findOne($id);
        $model = Drsd::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
        if(!$model){
            return $this->error('未找到相应信息');
        }

        $package_id = $model->getOldAttribute('package_id');
        if($package_id == $data['package_id'])
            $num = 0 ;
        else
            $num = 1;
        $limit =  $this->packageLimit($data,$num);
        if($limit['code'] != 200)
        {
            return $limit;
        }

        unset($data['id']);
        if($model->load($data,'')){
            if( $model->save()){
                Drs::updateAll(['did'=>$model->id],['dname'=>$model->dname]);
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function del($data,$userInfo){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');
        $count = Drsd::find()->where(['and',['user_id'=>$userInfo['uid']],['in','id',$ids]])->count();
        if($count != count($ids))
            return $this->error('操作错误');
        $result = Drsd::deleteAll(['in','id',$ids]);
        if($result)
            Drs::deleteAll(['in','did',$ids]);
        return $this->success();
    }



    public function getOne($id,$userInfo){
        $model = Drsd::find()->where(['id'=>$id,'user_id'=>$userInfo['uid']])->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }

        $model['icp'] = (int)$model['icp'] ;
        $model['brand_id'] = (int)$model['brand_id'] ;
        $model['package_id'] = (int)$model['package_id'] ;
        if(empty($model['brand_id']))
            $model['brand_id'] = "";
        $model['white_start_time'] = date('Y-m-d',strtotime($model['white_start_time']));
        $model['white_end_time'] = date('Y-m-d',strtotime($model['white_end_time']));
        return $this->success($model);
    }


    /**
     * 批量添加
     * @param $data
     * @return array
     * @throws \yii\db\Exception
     */
    public function addBatch($data,$userInfo)
    {
        $dnames = $data['dnames'];
        if (!$dnames)
            return $this->error('请填写域名');
        $arr = explode("\n", $dnames);
        if(empty($arr))
            return $this->error('请填写域名');
        $arr = array_filter($arr);
        $transaction = \Yii::$app->db->beginTransaction();

        $data['user_id'] = $userInfo['uid'];
        $limit =  $this->packageLimit($data,count($arr));
        if($limit['code'] != 200)
        {
            $transaction->rollBack();
            return $limit;
        }
        foreach ($arr as $dnameArr) {
            $modelDrsd = new Drsd();
            $modelDrsd->user_id = $userInfo['uid'];
            $modelDrsd->username = $userInfo['username'];
            $modelDrsd->dname = $dnameArr;
            $modelDrsd->status = 1;
            $modelDrsd->icp = 1;
            if($modelDrsd->load($data,''))
            {
                $modelDrsd->intime = time();
                if($modelDrsd->save()== false)
                {
                    $transaction->rollBack();
                    $msg = ($this->getModelError($modelDrsd));
                    return $this->error($msg);
                }
                $drs = new Drs();
                $drs->did = $modelDrsd->id;
                $drs->dname = $dnameArr;
                $drs->intime = time();
                $drs->route = 'default';
                if($drs->load($data,''))
                {
                    if($drs->save() == false)
                    {
                        $transaction->rollBack();
                        $msg = ($this->getModelError($drs));
                        return $this->error($msg);
                    }
                }else{
                    $transaction->rollBack();
                    $msg = ($this->getModelError($drs));
                    return $this->error($msg);
                }

            }else{
                $transaction->rollBack();
                $msg = ($this->getModelError($modelDrsd));
                return $this->error($msg);
            }
        }
        $transaction->commit();
        return $this->success();
    }


    /**
     * 批量修改
     * @param $data
     * @return array
     * @throws \yii\db\Exception
     */
    public function updateBatch($data,$userInfo)
    {

        $checkedDrsd = $data['checkedDrsd'];
        if(empty($checkedDrsd) || !is_array($checkedDrsd))
            return $this->error("请选择域名");

        $transaction = \Yii::$app->db->beginTransaction();
        foreach ($checkedDrsd as $dnameArr) {

            $model =Drsd::find()->where(['user_id'=>$userInfo['uid'],'id'=>$dnameArr])->one();
            if(!$model) {
                return $this->error('找不到信息');
            }
            $model->remarks = $data['remarks'];
            if($model->save() == false)
            {
                $transaction->rollBack();
                $msg = ($this->getModelError($model));
                return $this->error($msg);
            }
            Drs::deleteAll(['did'=>$model->id]);
            $drs = new Drs();
            $drs->did = $model->id;
            $drs->dname = $model->dname;
            $drs->intime = time();
            $drs->route = 'default';
            if($drs->load($data,''))
            {
                if($drs->save() == false)
                {
                    $transaction->rollBack();
                    $msg = ($this->getModelError($drs));
                    return $this->error($msg);
                }
            }else{
                $transaction->rollBack();
                $msg = ($this->getModelError($drs));
                return $this->error($msg);
            }
        }
        $transaction->commit();
        return $this->success('操作成功');
    }

    public function packageLimit($data,$num)
    {
        $user_id = $data['user_id'];
        $package_id = $data['package_id'];
        $res = Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');
       $count = Drsd::find()->where(['user_id'=>$user_id,'package_id'=>$package_id])->count();
       $count_all =$count + $num;
       if($count_all>$res['drsd_quantity'])
       {
           return $this->error('您选择的套餐最多创建'.$res['drsd_quantity']."个,已创建".$count."个");
       }
       return $this->success();
    }

}
