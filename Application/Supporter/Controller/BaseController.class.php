<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/12
 * Time: 11:42
 */

namespace Supporter\Controller;


use Think\Controller;

class BaseController extends Controller
{
    public function _initialize(){
        //判断用户是否已经登录
        if (isset($_SESSION['ext_user']['id'])||isset($_COOKIE['ext_user']))
        {
        	if(!empty($_SESSION['ext_user']['id']))
        	{
        		$id = $_SESSION['ext_user']['id'];
        	}else{
        		$id = $_COOKIE['ext_user'];
        	}
            $t = M('duoduo2010')->where("id=".$id)->find();
            if(!empty($t))
            {
            	session("ext_user", $t);
            }else{
            	$this->error('对不起,您还没有登录!请先登录再进行浏览1', U('Index/login'), 1);
            }
        	
        }else{
        	$this->error('对不起,您还没有登录!请先登录再进行浏览', U('Index/login'), 1);
        }
    }
}