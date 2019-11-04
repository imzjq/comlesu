<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 21:09
 */

namespace backend\controllers;


use backend\models\DefenceIp;
use backend\models\Domain;
use backend\models\NodeGroup;
use backend\models\SpiderType;
use backend\models\User;
use common\lib\Utils;
use yii\helpers\ArrayHelper;

class DomainController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Domain();
    }

    public function actionIndex(){
        $page = $this->request->post('page',1);
        $pagenum = $this->request->post('limit',10);
        $dname = $this->request->post('dname','');
        $username = $this->request->post('username','');
        $countryType = $this->request->post('countryType','');
        $typeTrue = false;
        $where = [];
        if($dname){
            $where[]= ['like','{{%domain}}.dname',$dname];
        }
        if($username){
            $where[]= ['like','{{%domain}}.username',$username];
        }
        //状态
        $status = $this->request->post('status','');
        if(is_numeric($status)){
            $where[] = ['in','{{%domain}}.status',$status];
        }

        if($countryType){
            $where[]= ['{{%user}}.registsource'=>$countryType];
            $typeTrue = true;
        }

        $package_id = $this->request->post('package_id',''); //品牌名称
        if($package_id){
            $where[]= ['package_id'=>$package_id];
        }

        $result = $this->model->getList($page,$pagenum,$where,$typeTrue);
        return $result;
    }

    public function actionAdd(){
        $result = $this->model->add($this->request->post());
        return $result;
    }

    public function actionAddBatch(){
        $result = $this->model->addBatch($this->request->post());
        return $result;
    }

    public function actionDel(){
        $result = $this->model->del($this->request->post());
        return $result;
    }

    public function actionGetOne(){
        $id = $this->request->post('id',0);
        $result = $this->model->getOne($id);
        return $result;
    }
    public function actionUpdate(){
        $result = $this->model->updateInfo($this->request->post());
        return $result;
    }


    //状态修改
    public function actionChangeStatus(){
        $data = $this->request->post();
        $result = $this->model->changeStatus($data);
        return $result;
    }

    public function actionGetUsers()
    {
        //用户信息
        $userModel = User::find();
        $type =  \Yii::$app->request->post('type');
        if($type)
        $userModel->where(['registsource'=>$type]);
        $userData=  $userModel->asArray()->all();
        $userData = ArrayHelper::map($userData,'username','username');
        $u_d = [];
        if($userData){
            foreach ($userData as $k=>$v){
                $u_t = [];
                $u_t['value']=$v;
                $u_t['label'] = $v;
                $u_d[] = $u_t;
            }
        }
        return $this->success($u_d);
    }


    //远程数据
    public function actionGetInfo()
    {
        //用户信息
        $userModel = new User();
        $userData = $userModel->getUsernameToUsername();
        $u_d = [];
        if($userData){
            foreach ($userData as $k=>$v){
                $u_t = [];
                $u_t['value']=$k;
                $u_t['label'] = $v;
                $u_d[] = $u_t;
            }
        }

        //节点分组
        $nodeGroupModel = new NodeGroup();
        $groupData = $nodeGroupModel->idToName();

        $nix['value']=0;
        $nix['label'] = '不启用';
        $g_d[] = $nix;
        if($groupData){
            foreach ($groupData as $k=>$v){
                $g_t = [];
                $g_t['value']=$k;
                $g_t['label'] = $v;
                $g_d[] = $g_t;
            }
        }
        //高防分组
        $defeceipModel = new DefenceIp();
        $defenceipData = $defeceipModel->idToCname();

        $d_d = [];
        if($defenceipData){
            $fix['value']=0;
            $fix['label'] = '不启用';
            $d_d[] = $fix;
            foreach ($defenceipData as $k=>$v){
                $d_t = [];
                $d_t['value']=$k;
                $d_t['label'] = $v;
                $d_d[] = $d_t;
            }
        }


        //搜索引擎
        $spiderTypeModel = new SpiderType();
        $spiderTypeData = $spiderTypeModel->idToName();
        $s_d = [];
        if($spiderTypeData){
            foreach ($spiderTypeData as $k=>$v){
                $s_t = [];
                $s_t['value']=$k;
                $s_t['label'] = $v;
                $s_d[] = $s_t;
            }
        }

        $data = [
            'userData'=>$u_d,
            'groupData'=>$g_d,
            'defenceData'=>$d_d,
            'spiderData'=>$s_d
        ];

        return $this->success($data);

    }

    //添加加速 下一步检查
    public function actionNextCheck(){
       $post = $this->request->post();
        $post['step'] =1 ;
        $result = $this->model->generateCnames($post);
        return $result;
    }

    //批量添加加速 下一步检查
    public function actionBatchNextCheck(){
        $post = $this->request->post();
        $result = $this->model->generateBatchCnames($post);
        return $result;
    }

    //预览remap
    public function actionPreview(){
        $result = $this->model->generateRemap($this->request->post());
        return $this->success($result);
    }

    /**
     * 批量预览
     * @return array
     */
    public function actionBatchPreview(){
        $data = $this->request->post();
        $dnames = $data['dnames'] ;
        $remapData = $data['remapData'];
        $arr=explode("\n",$dnames);
        $arr = array_filter($arr);
        $result = [];
        if($arr)
        {
            foreach ($arr as $val)
                $result  = array_merge($this->model->generateRemap(['dname' => trim($val), 'remapData' => $remapData]),$result);
        }
        return $this->success($result);
    }




    public function actionCheckSsl(){
        $username = $this->request->post('username');
        $dname = $this->request->post('dname');
        if(empty($username) || empty($dname)){
            return $this->error('参数错误');
        }
        $result = $this->model->checkSsl($username,$dname);
        if($result){
            return $this->success();
        }
        return $this->error('请先上传'.$dname .'证书');
    }

    public function actionBatchCheckSsl(){
        $username = $this->request->post('username');
        $dname = $this->request->post('dnames');
        if(empty($username) || empty($dname)){
            return $this->error('参数错误');
        }
        $result = $this->model->batchCheckSsl($username,$dname);
        return $result;
    }

    /**
     * 批量修改高防
     * @return mixed
     */
    public function actionUpdateDefence()
    {
        $post = $this->request->post();
        $result = $this->model->updatedefence($post);
        return $result;
    }


    /**
     * 批量修改分组号
     * @return mixed
     */
    public function actionUpdateNodeGroup()
    {
        $post = $this->request->post();
        $result = $this->model->updatenodegroup($post);
        return $result;
    }
}
