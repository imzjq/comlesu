<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/12
 * Time: 21:48
 */

namespace frontend\controllers;

use frontend\models\Package;

class PackageController extends AuthController
{
    protected $model;
    public function init(){
        parent::init();
        $this->model = new Package();
    }




}
