<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/27
 * Time: 15:25
 */

namespace Supporter\Controller;


use Think\Controller;

class RegistphoneController extends Controller
{
    public function index(){
        $this->display();
    }

    public  function regist(){
      $data=  M('duoduo2010');
      if (IS_POST){
          array_map('trim',$_POST);
          $zz= array_keys($_POST,"");//检测空数据
          if (count($zz)>0){
              $this->error("请提交完整信息",U('index'));
          }
          $vercode=$_POST['code'];
          $phone=$_POST['tel'];
          if(time()-$_SESSION['last_access_reg']>30*60){
              $this->error('验证码已经失效，请重新获取',U('index'));
          }
          if ($_SESSION['code_reg']!=$vercode){
              $this->error('验证码错误!',U('index'));
          }
          $z = $data->where('tel=' . $_POST['tel'])->find();
          if ($z){
              $this->error("手机号已被使用",U('index'));
          }
          $name=$_POST['adminname'];
          $res=$data->where("adminname='".$name."'")->find();
          if ($res){
              $this->error("用户名已注册",U('index'));
          }
          $data->create();
          $data->adminpass=deep_jm($_POST['adminpass']);
          $data->role=3;
          $data->addtime=time();
          $pp=$data->add();
          if ($pp){
              $this->success("注册成功！",U('successpage'));
          }else{
              $this->error("注册失败！",U('index'));
          }

      }
    }

 	public function  successpage()
 	{
 		$this->display();
    }
    public function  AjaxCheckname(){
        $data=M('duoduo2010');
        if (IS_POST){
            $name=$_POST['adminname'];
            $res=$data->where("adminname='".$name."'")->find();
            if ($res){
                $this->ajaxReturn(array('code'=>0,'msg'=>"用户名已注册"));
            }else{
                $this->ajaxReturn(array('code'=>1,'msg'=>"用户名可用"));
            }
        }


    }

    public function  AjaxChecktel(){
        $data=M('duoduo2010');
        if (IS_POST){
            $tel=$_POST['tel'];
            $res=$data->where("tel=".$tel)->find();
            if ($res){
                $this->ajaxReturn(array('code'=>0,'msg'=>"号码已注册"));
            }else{
                $this->ajaxReturn(array('code'=>1,'msg'=>"号码可用"));
            }
        }


    }

    public function  AjaxGetcode()
    {
        $data = M("duoduo2010");
        $z = $data->where('tel=' . $_POST['tel'])->select();
        if ($z) {
            $this->ajaxReturn(array('code' => 0, 'msg' => '号码已被使用'));
        } else {
            if (!isset($_SESSION['last_access_reg']) || (time() - $_SESSION['last_access_reg']) > 60) {
                $code = rand(1000, 9999);
                sendTemplateSMS($_POST['tel'], array($code, '1'), "221424");//手机号码，替换内容数组，模板ID
                $_SESSION['code_reg'] = $code;
                $_SESSION['last_access_reg'] = time();
                $this->ajaxReturn(array('code' => 1, 'msg' => '验证码已发送！'));
            } else {
                $this->ajaxReturn(array('code' => 0, 'msg' => '请勿重复发送'));
            }
        }
    }

}