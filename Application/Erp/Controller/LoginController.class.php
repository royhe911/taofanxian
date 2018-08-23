<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */

namespace Erp\Controller;
use Think\Controller;

class LoginController extends Controller {

	//登陆
	public function index(){
		if (IS_AJAX){
			$name = I('post.name');
			$code = I('post.code');
			$online = I('post.online',0);
			$password = I('post.password','','md5');

			if (empty($name)) $this->ajaxReturn(array('msg' => 2, 'info' => '请填写用户名!'));
			if (empty($password)) $this->ajaxReturn(array('msg' => 2, 'info' => '请填写密码!'));
			//if (empty($code)) $this->error('请填写验证码');
			//if (!check_verify($code)) $this->error('验证码错误！');

			$where = array(array('name' => $name, 'password' => $password), array('qq' => $name, 'password' => $password), '_logic' => 'or');
			$msg = D('account')->field('id,role,name,msg,check,md5(password) as password')->where($where)->find();
			if (!empty($msg)){
				if (1 == $msg['msg'] ) $this->ajaxReturn(array('msg' => 3, 'info' => '该帐号已禁用!'));
                if ( $msg['check'] !=1) $this->ajaxReturn(array('msg' => 3, 'info' => '该帐号异常!'));
				session('user', $msg);
				if (1 == $online) {
                    cookie('userId', $msg['id'], 2592000);//登录保持
                    cookie('password', $msg['password'], 2592000);//登录保持
                }
				$msg = D('account')->where(array('id' => $msg['id']))->save(array('edittime' => time(), 'ip' => get_client_ip()));//记录最近登录时间和IP
				$this->ajaxReturn(array('msg' => 1, 'info' => '登录成功!', 'url' => U('Welcome/index')));
			} else {
				$this->ajaxReturn(array('msg' => 4, 'info' => '用户名或密码错误!'));
			}
		} else {
			if (!empty($_SESSION['user']['id'])||!empty($_COOKIE['userId'])) $this ->redirect('Welcome/index');
			$this->display();
		}
	}
	
	//注册
	public function register(){
		
		if (IS_AJAX){
			//$username = I('post.userName');
			$phoneNumber = I('post.phoneNumber');
			$password = I('post.password');
			$repeat_password = I('post.repeat_password');
			$qqorWechat = I('post.QQorWechat');
			$selectProv = I('post.selectProv');
			$selectCity = I('post.selectCity');
			$teacher = I('post.teacher');
			$phoneCode = I('post.phoneCode');
			
			//if (empty($username)) $this->ajaxReturn(array('msg' => 2, 'info' => '请填写用户名!'));
			//if (strlen($username) < 6) $this->ajaxReturn(array('msg' => 2, 'info' => '请输入6~18位数字字母组合!'));
			//if (strlen($username) > 18) $this->ajaxReturn(array('msg' => 2, 'info' => '请输入6~18位数字字母组合!'));
			//if (!preg_match('/^[0-9a-zA-Z]*$/',$username)) $this->ajaxReturn(array('msg' => 2, 'info' => '请输入6~18位数字字母组合!'));
			if (empty($password)) $this->ajaxReturn(array('msg' => 3, 'info' => '请填写密码!'));
			if (empty($qqorWechat)) $this->ajaxReturn(array('msg' => 2, 'info' => '请填写QQ或微信号!'));
			if (!preg_match('/^[0-9a-zA-Z]*$/',$password)) $this->ajaxReturn(array('msg' => 3, 'info' => '密码只能为数字字母组合!'));
			if (empty($repeat_password)) $this->ajaxReturn(array('msg' => 3, 'info' => '请再次填写密码!'));
			if ($password != $repeat_password) $this->ajaxReturn(array('msg' => 3, 'info' => '两次输入密码不一致!'));
			if (strlen($password) < 6) $this->ajaxReturn(array('msg' => 6, 'info' => '密码不能少于6位数!'));
			if (empty($phoneNumber)) $this->ajaxReturn(array('msg' => 7, 'info' => '请填写手机号!'));
			if ($_SESSION['code_reg'] != $phoneCode) $this->ajaxReturn(array('msg' => 12, 'info' => '手机验证码不正确!'));
			if ((time() - $_SESSION['last_access_reg']) > 60) $this->ajaxReturn(array('msg' => 12, 'info' => '手机验证码已过期!'));
			
			//$info = D('account')->where(array('name' => $username))->find();
			//if (!empty($info)) $this->ajaxReturn(array('msg' => 10, 'info' => '用户名已存在'));
			
			$info   = D('user')   ->where(array('iphone' => $phoneNumber))->find();
			if (!empty($info))    $this->ajaxReturn(array('msg' => 11, 'info' => '手机号已存在'));
			$account= D('account')->where(array('phone'  => $phoneNumber))->find();
            if (!empty($account)) $this->ajaxReturn(array('msg' => 11, 'info' => '手机号已存在'));
			$info = D('user')->where(array('qq' => $qqorWechat))->find();
			if (!empty($info)) $this->ajaxReturn(array('msg' => 2, 'info' => 'QQ/微信已存在'));
			
			$msg = D('account')->add(array('role' => 5, 'name' => $phoneNumber, 'qq' => $qqorWechat, 'password' => md5($password), 'addtime' => time()));//添加帐号
			if ($msg){
				$return = D('user')->add(array('uid' => $msg, 'nickname' => $username, 'iphone' => $phoneNumber, 'qq' => $qqorWechat, 'tutor' => $teacher, 'address' => $selectProv.$selectCity, 'addtime' => time()));
				if ($return){
					$_SESSION['user']['id'] = $msg;
					$this->ajaxReturn(array('msg' => 1, 'info' => '注册成功!', 'url' => U('Login/index')));
				} else {
					$return = D('account')->where('id = '.$msg)->delete();//注册失败,删除垃圾数据
					$this->ajaxReturn(array('msg' => 9, 'info' => '注册失败,请稍候重试!'));
				}
			} else {
				$this->ajaxReturn(array('msg' => 8, 'info' => '注册失败,请稍候重试!'));
			}
		}
	}
	
	//登出
	public function loguot(){
        if($_SESSION['user']['role'] == 5 ){
            //退出到主页面
            unset($_SESSION['user']);
            cookie('userId',null);

            $this ->redirect('index/index');
        }else{
            //除去商家登入，其他登入到后台
            unset($_SESSION['user']);
            cookie('userId',null);

            $this ->redirect('Login/index');
        }
        //商家退出  退出到

	}
	
	//验证码
	public function verify(){
		ob_clean();
		$verify = new \Think\Verify();
		$verify->entry();
	}
	
	//短信验证码
	public function code(){
		
		if (IS_AJAX){
			$iphone = I('post.number');
			$info = D('user')->where(array('iphone' => $iphone))->find();
			if (!empty($info)) $this->ajaxReturn(array('msg' => 12, 'info' => '手机号已存在'));
			
			if (!isset($_SESSION['last_access_reg']) || (time() - $_SESSION['last_access_reg']) > 60) {
				$code = rand(1000, 9999);
				sendTemplateSMS($iphone, array($code,'30'), 247563, '8a216da85e6fff2b015e79280b69054c');//手机号码，替换内容数组，模板ID
				$_SESSION['code_reg'] = $code;
				$_SESSION['last_access_reg'] = time();
                $this->ajaxReturn(array('msg' => 1, 'info' => '验证码已发送！'));
            } else {
                $this->ajaxReturn(array('msg' => 3, 'info' => '请勿重复发送'));
			}
		}
	}
	
	//验证码检测
	private function check_verify($code,$id=''){
		$verify = new \Think\Verify();
		$res = $verify->check($code, $id);
		$this->ajaxReturn($res, 'json');
	}

	//密码找回
    public function retrievePassword(){
        //$username = I('post.userName');
        $password        = I('post.password');  //  密码
        $repeat_password = I('post.repeat_password');   //重复密码
        $phoneCode       = I('post.phoneCode');  //验证码
        $iphone          = I('post.number');   //用户名手机号码
        if (empty($password)) $this->ajaxReturn(array('msg' => 3, 'info' => '请填写密码!'));
        if (!preg_match('/^[0-9a-zA-Z]*$/',$password)) $this->ajaxReturn(array('msg' => 3, 'info' => '密码只能为数字字母组合!'));
        if (empty($repeat_password)) $this->ajaxReturn(array('msg' => 3, 'info' => '请再次填写密码!'));
        if ($password != $repeat_password) $this->ajaxReturn(array('msg' => 3, 'info' => '两次输入密码不一致!'));
        if (strlen($password) < 6) $this->ajaxReturn(array('msg' => 6, 'info' => '密码不能少于6位数!'));
        if (empty($iphone)) $this->ajaxReturn(array('msg' => 7, 'info' => '请填写手机号!'));
        if ($_SESSION['code_reg'] != $phoneCode) $this->ajaxReturn(array('msg' => 12, 'info' => '手机验证码不正确!'));
        if ((time() - $_SESSION['last_access_reg']) > 60) $this->ajaxReturn(array('msg' => 12, 'info' => '手机验证码已过期!'));
        $info = D('user')->where(array('iphone' => $iphone))->find();
        if (empty($info)) $this->ajaxReturn(array('msg' => 11, 'info' => '手机号不存在'));
        $user = D('Account')->where("name='{$iphone}'")->find();
        if($user['password'] == md5($password))   $this->ajaxReturn(array('msg' => 13, 'info' => '密码不能和原来一样'));
        $res  = D('Account')->where("name='{$iphone}'")->setField('password',md5($password));
        if( !$res ) $this->ajaxReturn(array('msg' => 0, 'info' => '密码修改失败成功'));
        $this->ajaxReturn(array('msg' => 1, 'info' => '密码修改成功','url' => U('Login/index')));
    }
    //密码找回短信验证
    public function receiveCode(){

        if (IS_AJAX){
            $iphone = I('post.number');
            $info = D('user')->where(array('iphone' => $iphone))->find();
            if (empty($info)) $this->ajaxReturn(array('msg' => 12, 'info' => '手机号不存在'));

            if (!isset($_SESSION['last_access_reg']) || (time() - $_SESSION['last_access_reg']) > 60) {
                $code = rand(1000, 9999);
                sendTemplateSMS($iphone, array($code,'30'), 221424, '8aaf07086010a0eb016019e9b4f001cf');//手机号码，替换内容数组，模板ID
                $_SESSION['code_reg'] = $code;
                $_SESSION['last_access_reg'] = time();
                $this->ajaxReturn(array('msg' => 1, 'info' => '验证码已发送！'));
            } else {
                $this->ajaxReturn(array('msg' => 3, 'info' => '请勿重复发送'));
            }
        }
    }

    //拼返返短信接口
    public function pff_code(){
        header("ACCESS-CONTROL-ALLOW-ORIGIN:*");
       //if (IS_AJAX){
            $iphone = $_POST['number'];
            //$info = D('user')->where(array('iphone' => $iphone))->find();
            //if (!empty($info)) $this->ajaxReturn(array('msg' => 12, 'info' => '手机号已存在'));

           // if (!isset($_SESSION['last_access_reg']) || (time() - $_SESSION['last_access_reg']) > 60) {
                $code = rand(1000, 9999);
                sendTemplateSMS($iphone, array($code,'30'), 247563, '8a216da85e6fff2b015e79280b69054c');//手机号码，替换内容数组，模板ID
//                $_SESSION['code_reg'] = $code;
//                $_SESSION['last_access_reg'] = time();
            echo json_encode(array('msg'=>1,'info'=>'发送成功','code'=>$code));



        }
   // }
}
?>