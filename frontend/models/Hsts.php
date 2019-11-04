<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/28
 * Time: 21:25
 */

namespace frontend\models;
use common\lib\Utils;
use common\models\Hsts as CommonHsts;
use Yii;
class Hsts extends CommonHsts
{
    use ApiTrait;
    protected $fileType = ['zip'];
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
       // var_dump($list->createCommand()->getRawSql());die;
        //总页数
        $allpage = 1;
        if($count > $pagenum){
            $allpage = ceil($count/$pagenum);
        }
        $csdatas = [];
        if($page <= $allpage){
            $list->offset($offset)->limit($pagenum)->orderBy('id DESC');
            $datas = $list->asArray()->all();
            if($datas)
            {
                $pack = new Package();
                $packages = $pack->idToName($uid);
                foreach ($datas as $key =>$value)
                {
                    $datas[$key]['create_time'] = date('Y-m-d H:i',$value['create_time']);
                    $datas[$key]['package_name'] = $arr['package_name'] = isset($packages[$value['package_id']]) ? $packages[$value['package_id']] : "";
                }
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

    public function add($data,$uid){
        //$model = new $this();
        $data['user_id'] = $uid;
        $urls = $data['checkedDefence'];
        if(empty($urls))
            return $this->error("请选择域名");
       $db =  Yii::$app->db;
        $error ="";
        foreach ($urls as $url)
        {
            $count = $this::find()->where(['user_id'=>$uid,'url'=>$url])->count();
            if($count > 0)
                continue ;
            $sql = "select `domain` from {{%user_cer_domain}} where user_id = {$uid} and `domain` like '%{$url}' and cer_end_time > ".time();
            $list= $db->createCommand($sql)->queryAll();
            if(count($list) ==0) {
                $error .= $url."没有上传证书,";
                continue;
            }
            $temp = false;
            foreach ($list as $domain)
            {
                $host = Utils::getUrlHost($domain['domain']);
                if($host == $url)
                {
                    $temp = true;
                    continue ;
                }
            }
            if($temp == false) {
                $error .= $url."没有上传证书,";
                continue;
            }
            $limit =  $this->packageLimit($uid,$data['package_id2'],1);
            if($limit['code'] != 200)
            {
                return $limit;
            }
            $model = new $this();
            $model->url = $url;
            $model->user_id = $uid;
            $model->package_id = $data['package_id2'];
            $model->create_time = time();
            $model->save();
        }
        $result = ['error'=>$error] ;
        return $this->success($result);
    }

    public function packageLimit($user_id,$package_id,$num)
    {
        $res = Package::getPackInfo($user_id,$package_id);
        if(!$res)
            return $this->error('找不到套餐信息');
        $count = $this::find()->where(['user_id'=>$user_id,'package_id'=>$package_id])->count();
        $count_all = $count + $num;
        if($count_all>$res['hijack_quantity'])
        {
            return $this->error('您选择的套餐最多创建'.$res['hijack_quantity']."个,已创建".$count."个");
        }
        return $this->success();
    }

    //删除分组
    public function del($id,$uid){
        $model = $this::find()->where(['id'=>$id,'user_id'=>$uid])->one();
        if(!$model){
            return $this->error('未找到相应数据');
        }
        $del = $model->delete();
        return $this->success('','操作成功');
    }



}
