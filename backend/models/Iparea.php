<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/8
 * Time: 22:07
 */

namespace backend\models;

use common\models\Iparea as CommonIparea;
use yii\helpers\ArrayHelper;

class Iparea extends CommonIparea
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
        $model = new Iparea();
        if($data && $model->load($data,'')){
            if ($model->save()) {
                return $this->success();
            }
            $msg = ($this->getModelError($model));
            return $this->error($msg);
        }
        return $this->error('参数错误');
    }

    public function getOne($id){
        $model = Iparea::find()->where(['id'=>$id])->asArray()->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        return $this->success($model);
    }

    //修改
    public function updateInfo($data){
        $id = isset($data['id'])?$data['id']:'';
        $model = Iparea::findOne($id);
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

    public function del($data){
        $ids = $data['id'];
        if(empty($ids) || !is_array($ids))
            return $this->error('参数错误');

       // return $this->error('暂时不允许删除');
        $model = Iparea::deleteAll(['in','id',$ids]);

        return $this->success();

    }


    //province_id=>province
    public function getProvinceMap(){
        $res = Iparea::find()->where(['country_id'=>'CN'])->asArray()->all();
        $arr = ArrayHelper::map($res,'province_id','province');
        return $arr;
    }

    //service_id=>service
    public function getServiceMap(){
        $res = Iparea::find()->where(['country_id'=>'CN'])->asArray()->all();
        $arr = ArrayHelper::map($res,'service_id','service');
        return $arr;
    }

    //通过province_id 获取id
    /**
     * @param $province_ids
     * @return array
     */
    public function getIdByProvinceId($province_ids){
        if(is_string($province_ids)){
            $province_ids = explode('',$province_ids);
        }
        $data = [];
        if(is_array($province_ids)){
            $data = Iparea::find()->where(['in','province_id',$province_ids])->asArray()->all();

        }
        return $data;
    }

    /**
     * @param $service_ids
     * @return array
     */
    public function getIdByServiceId($service_ids){
        if(is_string($service_ids)){
            $service_ids = explode('',$service_ids);
        }
        $data = [];
        if(is_array($service_ids)){
            $data = Iparea::find()->where(['in','service_id',$service_ids])->asArray()->all();
        }
        return $data;
    }

    /**
     * @param array $where
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getALl($where=[]){
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
        }else{
            $list->where(['id'=>0]);
        }
        $data = $list->all();
        return $this->success($data);
    }

    //淘宝IP查询
    public function checkArea($ip){
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip=$ip";
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
        $res = curl_exec($ch);
        $res = json_decode($res,true);

        return $res;
    }

    public function idToName()
    {
        $res =  $this::find()->where(['<>','country_id','CN'])->select('id,country')->asArray()->all();
        $arr = ArrayHelper::map($res,'id','country');
        return $arr;
    }
}
