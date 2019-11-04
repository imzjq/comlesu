<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/7
 * Time: 21:48
 */

namespace frontend\models;


use common\models\Defence as CommonDefence;


class Cache
{
    use ApiTrait;

    /**
     * 清除缓存
     * 追加文件
     * @param $url
     * @return bool|int|string
     */
    public function clear($data,$userInfo)
    {

        $session_name = 'clear_cache_'.$userInfo['uid'];
        $session = \Yii::$app->getSession();

        $urls = $data['checkedUrl'];
        if($urls)
        {
            if($session->has($session_name))
            {
                $end_time = $session->get($session_name);

                if($end_time > time() )
                {
                    return $this->error("每30秒只能操作一次");
                }else{
                    $session->set($session_name,time()+30);
                }
            }else{
                $session->set($session_name,time()+30);
            }
            $path = \Yii::getAlias('@dns_file').'/conf/cache.txt';
            $content_arr = [];
            foreach ($urls  as $val)
            {
                $content_arr[] = $val."\n";
            }
            file_put_contents($path,$content_arr,FILE_APPEND);
        }
        return $this->success();
    }




}
