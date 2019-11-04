<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/29
 * Time: 22:22
 */

namespace frontend\controllers;


class HomeController extends AuthController
{
    public function actionIndex(){
        echo 'home';
    }
}
