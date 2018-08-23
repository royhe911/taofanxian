<?php
namespace Supporter\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
       $this->display('login');

    }

    public function _initialize(){
        //判断用户是否已经登录
        if(!empty($_COOKIE['ext_user']))
        {
        	$id = $_COOKIE['ext_user'];
            $t = M('duoduo2010')->where("id=".$id)->find();
            if(!empty($t))
            {
            	session("ext_user", $t);
            	$this ->redirect('Usercenter/index');
            }
        }
    }

    public function login(){
        if (IS_POST){
           $username=trim($_POST['username']);
           $password=deep_jm(trim($_POST['password']));
           $rember = I('post.remember',0);
          /*$code=trim($_POST['code']);
          if (!check_verify($code)){
              $this->error('验证码错误！');
          }*/
            $User = D("duoduo2010");
          $t=  $User->where(" adminname='".$username."' and adminpass='".$password."' ")->find();
            if ($t){
                unset($t["adminpass"]);
                $data['lastlogintime']=$t['logintime'];
                $data['lastloginip']=$t['loginip'];
                $data['loginnum']=$t['loginnum']+1;
                $data['logintime']=time();
                $data['loginip']=get_client_ip();
                $User->where(" adminname='".$username."' and adminpass='".$password."' ")->save($data);
                if($rember==1)
                {
                	cookie('ext_user',$t['id'],2592000);
                }
                session("ext_user", $t);
                createToken();
                $this ->redirect('Usercenter/index');
                //$this->success('登录成功', U('User/index'),0);
            }else{
                $this->error('账号密码错误');
            }
        }
        $this->display();
    }

    public function Verify(){
        ob_clean();
        $config =    array(
            'fontSize'    =>    80,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify =     new \Think\Verify($config);
        $Verify->useZh = false;
        $Verify->entry();
    }
    public function check_verify($code, $id = '')
    {
        $verify = new \Think\Verify();
        $res = $verify->check($code, $id);
        $this->ajaxReturn($res, 'json');
    }
    
}
