<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/10
 * Time: 9:03
 */

namespace Supporter\Controller;


use Think\Controller;

class CheckAdminController extends  Controller
{
    public function _initialize(){
        //判断用户是否已经登录
        if ($_SESSION['ext_user']['id']!=1) {
            $this->error('对不起,您的访问权限不够！', U('Index/login'), 1);
        }
    }
}