<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Exception;
use Common\Util\Util;

/**
 * @auth TRUE
 * @name 首页控制器
 * */

class IndexController extends InterceptController {
    public function index(){

        $this->redirect("Subj/index");


        $this->display('index');
    }





}