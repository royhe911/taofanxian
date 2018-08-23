<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;
use Think\Upload\Driver\Bcs\BCS_Exception;

/**
 * @auth TRUE
 * @authsort 30
 * @name 管理员管理//
 * */
class UserController extends InterceptController {

	function _initialize(){
		if('login' == ACTION_NAME){return;}
	   // Parent::_initialize();
    }
    public function login(){
		if(IS_POST){
			$username = I('post.username','');
			$password = I('post.password','');


			if(!$username || !$password){
				 //throw new \Exception('用户名或密码不能为空');
				 $this->error('用户名或密码不能为空！');
			}
			if($username=='master')
			{
				$info = M('duoduo2010')->where(  array( 'adminname'=>$username ) )->find();
			}else
			{
				$info='';
			}
			
			if(!$info){
				$this->error('用户不存在！');
			}
			$key = '218&p70y03';
			if( $info['adminpass'] !== md5(md5($key.$password).$key)){
				 $this->error('密码错误！');
			}
			$_SESSION['admin_info'] = $info;

			$this->redirect("Subj/index");
		}else{
			$this->display();	
		}

    }
	

	public function logout(){
		unset($_SESSION['admin_info']);
		$this->redirect("User/login");
	}
    
}