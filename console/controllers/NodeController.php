<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/19
 * Time: 19:45
 */
namespace console\controllers;
use common\lib\Utils;
use common\models\NodeUpdate;


class NodeController extends BaseController
{

    /**
     * 更新节点文件
     */
    public function actionUpdateFile()
    {
        $one = NodeUpdate::find()->orderBy('id DESC')->one();
        if($one)
        {
            $data = NodeUpdate::find()->where(['<=','id',$one->id])->groupBy('node_id')->select('node_id')->asArray()->all();
            $ids = [];
            foreach ($data as $val)
            {
                $ids[] = $val['node_id'];
            }
            $node = new \backend\models\Node();
            $node->exportNode($ids);

            NodeUpdate::deleteAll(['<=','id',$one->id]);
        }
    }


    public function rulesContent($url,$content_remap)
    {
        $url = Utils::getUrlHost($url) ;
        $content_remap [] = "cond %{CLIENT-HEADER:Host}  /{$url}/\nset-header Strict-Transport-Security  \"max-age=10886400\"\ncond %{CLIENT-HEADER:Host}  /(.*).{$url}/\nset-header Strict-Transport-Security  \"max-age=10886400\"\n\n";
        return $content_remap;
    }

}
