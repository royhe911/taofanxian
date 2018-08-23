<?php
namespace Admin\Servic;

class User {
    public function getList(){
    	
		
		$rid = $this->getCurRole();
		if($rid == 1){
			return $data = M('user')->where()->select();	
		}
		$data =  M('user')->where( array( 'user_relation.user_pid'=> $this->getCurUid() ) )->join('LEFT JOIN `user_relation` ON user.id = user_relation.user_id')
		->Field(array('user_relation.id'=>'re_id','user.id'=>'id','username','nickname','phone'))->select();
		return $data;
    }
    public function login($username,$password){
     	if(empty($username) || empty($password) ){
    		throw new \Exception("用户名或密码不能为空！");
    	}
    	if( 4 > strlen($username) || 6 > strlen($password) ){
    		throw new \Exception("用户名或密码长度错误！");
    	}
		$user = M('user')->where(['username'=>$username])->find();
    	if( !$user ){
    		 throw new \Exception("用户名不存在！"); 
		}
    	switch($user['style']){
    		case 0:
    			throw new \Exception("用户不存在！"); 
    		 break;
    		case 2:
    			throw new \Exception("该用户暂未激活！"); 
    		 break;
    		case 3:
    			throw new \Exception("该用户已被禁用，如有疑问，请联系管理员！"); 
    		 break;
    	}
    	if( $user['password'] !== md5 ( md5($password) . $user['keycode'] )){
    		throw new \Exception("密码错误！"); 
    	}
		
		session('User',$user);
//		dump(session('User'));die;
    	return true;
    }
	
	
	public function logout(){
		session(null);
    	return true;
    }
	
	public function getNickName(){
		$user = session('User');
		return $user ? $user['nickname'] : null;
	}
	
	public function getCurUid(){
		$user = session('User');
		return $user ? $user['id'] : null;
	}
	
	public function getCurRole(){
		$user = session('User');
		return $user ? $user['role_id'] : null;
	}
	
    public function register($username , $password , $phone ,$nickname=''){
    	if(empty($username) || empty($password) ){
    		throw new \Exception("用户名或密码不能为空！");
    	}
    	if( 4 > strlen($username) || 6 > strlen($password) ){
    		throw new \Exception("用户名或密码长度错误！");
    	}
    	if(empty($nickname)){$nickname = $username;}
    	$keyCode =  \Common\Util\Rand::RandStr(6);
    	$role_id=2;
    	$update_time = $creation_time= time();
    	$style = 2;
    	$data = array(
		    'username' 		=>	$username,
		    'nickname' 		=>	$nickname,
		    'password' 		=>	md5 ( md5($password) . $keyCode ) ,
		    'keycode'  		=>	$keyCode,
		    'role_id'  		=>   $role_id,
		    'update_time'	=>	$update_time,
		    'creation_time'	=>	$creation_time,
		    'phone'			=>  $phone,
		    'style' 		=>	$style
    	);
    	if ( M('user')->where(array( 'username' =>	$username  ))->count()){throw new \Exception("用户名已存在！");}
    	if ( M('user')->where(array( 'nickname' =>	$username  ))->count()){throw new \Exception("昵称已存在！");}
    	if($useid = M('user')->add($data)){
    		$puid = $this->getCurUid();
			if( M( 'user_relation' )->add(array('user_id'=>$useid,'user_pid'=>$puid )) ){	
				return true;
			}else{
				M('user')->where(array('id'=>$useid))->delete();
				throw new \Exception("系统错误，用户创建失败！");
			}
    	}else{
    		throw new \Exception("系统错误，用户创建失败！");
    	}
    
    }
    
}
