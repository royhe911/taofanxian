<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/3
 * Time: 10:00
 */

namespace Supporter\Controller;


use Think\Controller;

class UsercenterController extends BaseController
{
    public function index()
    {
    	$userdata = M('duoduo2010')->where('id='.$_SESSION['ext_user']['id'])->find();
    	$newsdata = M('article')->where(' (cid=10018 or cid=10017) and del=0')->order('addtime desc')->limit(0,12)->select();
    	//试客已下单
    	$query = "SELECT b.count,a.id,a.shopname FROM tfx_tbsellerinfo a LEFT JOIN (SELECT count(id) as count ,shop_id from tfx_tbtrialorders where comfirm=10 GROUP BY shop_id) b on a.id=b.shop_id where a.confirm=2 and a.sid=" . $_SESSION['ext_user']['id'] . " order by a.id";
    	$total_order = M()->Query($query);

    	//试客平台评论
    	$query = "SELECT b.count,a.id,a.shopname FROM tfx_tbsellerinfo a LEFT JOIN (SELECT count(id) as count ,shop_id from tfx_tbtrialorders where comfirm=20 GROUP BY shop_id) b on a.id=b.shop_id where a.confirm=2 and a.sid=" . $_SESSION['ext_user']['id'] . " order by a.id";
    	$total_comment = M()->Query($query);

    	//试客淘宝评价截图
    	$query = "SELECT b.count,a.id,a.shopname FROM tfx_tbsellerinfo a LEFT JOIN (SELECT count(id) as count ,shop_id from tfx_tbtrialorders where comfirm=10 GROUP BY shop_id) b on a.id=b.shop_id where a.confirm=2 and a.sid=" . $_SESSION['ext_user']['id'] . " order by a.id";
    	$total_end = M()->Query($query);

        $this->assign('total_order',$total_order);
        $this->assign('total_comment',$total_comment);
        $this->assign('total_end',$total_end);
        $this->assign('newsdata',$newsdata);
        $this->assign('userdata',$userdata);
        $this->display();
    }
	//退出
	public function unlogin()
	{
        unset($_SESSION['ext_user']);
        cookie('ext_user',null);
        $this ->redirect('Index/login');
    }
	public function bindshop()
	{
        $rr = M('tbsellerinfo')->where(' sid=' . $_SESSION['ext_user']['id'])->select();
        $this->rr = $rr;
        if (IS_POST) {
            	$data['shopurl'] = I('post.shopurl', '');
            	$data['shopname'] = I('post.shopname', '');
            	$data['zhanggui'] = I('post.zhanggui', '');
            	$data['qq'] = I('post.qq', '');
            	$data['wechat'] = I('post.wechat', '');
            	$data['phone'] = I('post.phone', '');
            	if(empty($data['shopurl'])) $this->error("店铺链接不能为空", U('Usercenter/bindshop'));
            	if(empty($data['shopname'])) $this->error("店铺名称不能为空", U('Usercenter/bindshop'));
            	if(empty($data['zhanggui'])) $this->error("旺旺不能为空", U('Usercenter/bindshop'));
            	if(empty($data['qq'])) $this->error("QQ不能为空", U('Usercenter/bindshop'));
            	if(empty($data['wechat'])) $this->error("微信不能为空", U('Usercenter/bindshop'));
            	if(empty($data['phone'])) $this->error("电话不能为空", U('Usercenter/bindshop'));
                $data['sid'] = $_SESSION['ext_user']['id'];
                $data['addtime'] = date("Y-m-d H:i:s", time());
	            if(strpos($data['shopurl'], 'tmall.com'))
	            {
	            	$data['type'] = 2;
	            }else{
	            	$data['type'] = 1;
	            }
	            $action = I('post.action', '');
	            //修改店铺信息
				if($action==1)
				{
					$id = I('post.id', '');
			        if($id>0)
			        {
				        $shop = M('tbsellerinfo')->where(' sid=' . $_SESSION['ext_user']['id'].' and id='.$id)->find();
				        if(!empty($shop))
				        {
					        $data['confirm'] = 0;
							$result = M('tbsellerinfo')->where("id=" . $id)->save($data);
				            if($result)
				            {
				            	echo "<script>alert('提交成功');</script>";
				                $this->redirect('Usercenter/bindshop');
				            }else{
				                echo "<script>alert('编辑失败，请重新编辑！');</script>";
				                $this->redirect('Usercenter/bindshop');
				            }
				        }else{
				        	echo "<script>alert('店铺信息不存在！');</script>";
				            $this->redirect('Usercenter/bindshop');
				        }
			        }else{
			        	echo "<script>alert('店铺信息不存在！');</script>";
			            $this->redirect('Usercenter/bindshop');
		        	}
				}else{//新增
					$count = count($rr);
			        $status = strtotime($_SESSION['ext_user']['etime']) > time() ? 1 : 0;
			        $do_count = $status ? 3 - $count : 1 - $count;
			        $do_count = $do_count > 0 ? $do_count : 0;
            		if ($do_count > 0)
            		{
						$result = M('tbsellerinfo')->add($data);
						if($result)
						{
	                    	echo "<script>alert('提交成功');</script>";
			                $this->redirect('Usercenter/bindshop');
	                	}else{
			                echo "<script>alert('保存失败，请重新提交！');</script>";
			                $this->redirect('Usercenter/bindshop');
			            }
		            }else{
		                $this->error("绑定店铺已达上限！", U('Usercenter/bindshop'));
		            }
				}
        }
        $this->do_count = $do_count;
        $this->status = $status;
        $this->display();
    }
	public function editshop()
	{
        $id = I('post.id', '');
        if($id>0)
        {
	        $shop = M('tbsellerinfo')->where(' sid=' . $_SESSION['ext_user']['id'].' and id='.$id)->find();
	        if(!empty($shop))
	        {
		        $data['qq'] = I('post.qq', '');
		        $data['wechat'] = I('post.wechat', '');
		        $data['phone'] = I('post.phone', '');
		        if(empty($data['qq'])) $this->error("QQ不能为空", U('Usercenter/bindshop'));
		        if(empty($data['wechat'])) $this->error("微信不能为空", U('Usercenter/bindshop'));
		        if(empty($data['phone'])) $this->error("电话不能为空", U('Usercenter/bindshop'));
				$result = M('tbsellerinfo')->where("id=".$id)->save($data);
		        if($result)
		        {
		            echo "<script>alert('修改成功');</script>";
		            $this->redirect('Usercenter/bindshop');
		        }else{
		            echo "<script>alert('修改失败，请重试！');</script>";
		            $this->redirect('Usercenter/bindshop');
		        }
	        }
        }

	}
	//个人设置
	public function basicset()
	{
        $res = M('duoduo2010')->where("id=".$_SESSION['ext_user']['id'])->find();
        $tel = substr($res['tel'],0,3).'****'.substr($res['tel'],7,4);
        $this->assign('qq',$res['qq']);
        $this->assign('tel',$tel);
		$this->display();
    }

    public function uppassword()
    {
    	$data=  M('duoduo2010');
      	if (IS_POST)
      	{
        	array_map('trim',$_POST);
            $adminpass = I('post.adminpass', '');
            $prepassword = I('post.repassword', '');
            $tel = I('post.tel', '');
      		$vercode = I('post.code', '');
      		if(empty($adminpass)||empty($prepassword))
            {
            	$this->error('密码不能为空，请重新输入!',U('uppassword'));
            }
            if($adminpass!=$prepassword)
            {
            	$this->error('两次输入密码不同，请重新输入!',U('uppassword'));
            }
          	if(time()-$_SESSION['last_access_reg']>30*60)
          	{
             	$this->error('验证码已经失效，请重新获取',U('uppassword'));
          	}
          	if ($_SESSION['code_reg']!=$vercode)
          	{
             	$this->error('验证码错误!',U('uppassword'));
          	}
      		$phone = $data->where('tel='.$tel.' and id='.$_SESSION['ext_user']['id'])->find();
          	if(empty($phone))
          	{
              	$this->error("手机号不正确",U('uppassword'));
          	}
            $arr['adminpass'] = deep_jm($adminpass);
          	$data->where("id=".$_SESSION['ext_user']['id'])->save($arr);
          	$this ->redirect('Usercenter/basicset');
      	}
		$this->display();
	}
	public function upphone()
    {
    	$data=  M('duoduo2010');
      	if (IS_POST)
      	{
      		$tel = I('post.tel', '');
      		$vercode = I('post.code', '');
          	if(time()-$_SESSION['last_access_reg']>30*60)
          	{
             	$this->error('验证码已经失效，请重新获取',U('upphone'));
          	}
          	if ($_SESSION['code_reg']!=$vercode)
          	{
             	$this->error('验证码错误!',U('upphone'));
          	}
      		$phone = $data->where('tel='.$tel.' and id='.$_SESSION['ext_user']['id'])->find();
          	if(empty($phone))
          	{
              	$this->error("手机号不正确",U('upphone'));
          	}
          	$this ->redirect('Usercenter/newphone');
      	}
		$this->display();
	}
	public function newphone()
    {
    	$data=  M('duoduo2010');
      	if (IS_POST)
      	{
        	array_map('trim',$_POST);
            $tel = I('post.tel', '');
      		$vercode = I('post.code', '');
          	if(time()-$_SESSION['last_access_tel']>30*60)
          	{
             	$this->error('验证码已经失效，请重新获取',U('newphone'));
          	}
          	if ($_SESSION['code_tel']!=$vercode)
          	{
             	$this->error('验证码错误!',U('newphone'));
          	}
      		$phone = $data->where('tel='.$tel)->find();
          	if(!empty($phone))
          	{
              	$this->error("号码已存在",U('newphone'));
          	}
            $arr['tel'] = $tel;
          	M('duoduo2010')->where("id=".$_SESSION['ext_user']['id'])->save($arr);
          	$this ->redirect('Usercenter/basicset');
      	}
		$this->display();
	}
	public function upqq()
    {
    	$data=  M('duoduo2010');
      	if (IS_POST)
      	{
        	array_map('trim',$_POST);
            $qq = I('post.qq', '');
            if(empty($qq))
            {
            	$this->error('QQ号不能为空，请重新输入!',U('upqq'));
            }

      		$qqdata = $data->where('qq='.$qq)->find();
          	if(!empty($qqdata))
          	{
              	$this->error("QQ号已存在，请重新输入!",U('upqq'));
          	}
            $arr['qq'] = $qq;
          	M('duoduo2010')->where("id=".$_SESSION['ext_user']['id'])->save($arr);
          	$this ->redirect('Usercenter/basicset');
      	}
		$this->display();
	}
	public function getpassword()
	{
	 	$data=  M('duoduo2010');
      	if (IS_POST)
      	{

        	array_map('trim',$_POST);
            $vercode=$_POST['code'];
            $phone=$_POST['tel'];

          	if(time()-$_SESSION['last_access_reg']>30*60)
          	{
             	$this->error('验证码已经失效，请重新获取',U('getpassword'));
          	}
          	if ($_SESSION['code_reg']!=$vercode)
          	{
             	$this->error('验证码错误!',U('getpassword'));
          	}
          	$phone = $data->where('tel='.$_POST['tel'].' and id='.$_SESSION['ext_user']['id'])->find();
          	if(empty($phone))
          	{
              	$this->error("手机号不正确",U('getpassword'));
          	}
          	$_SESSION['uid'] = $z['id'];
          	$this ->redirect('Regist/uppassword');
      	}
      	$this->display();
	}
	//手机号验证
	public function  AjaxChecktel()
	{
		$type = I('post.type', '');
        $data=M('duoduo2010');
        if (IS_POST){
            $tel = I('post.tel', '');
            if($type=='newphone')
            {
            	$res=$data->where("tel=".$tel)->find();
	            if ($res){
	                $this->ajaxReturn(array('code'=>0,'msg'=>"号码已注册"));
	            }else{
	                $this->ajaxReturn(array('code'=>1,'msg'=>"号码可用"));
	            }
            }else{
            	$res=$data->where("tel=".$tel.' and id='.$_SESSION['ext_user']['id'])->find();
	            if ($res){
	                $this->ajaxReturn(array('code'=>1,'msg'=>"号码正确"));
	            }else{
	                $this->ajaxReturn(array('code'=>0,'msg'=>"号码不存在"));
	            }
            }
        }
    }
	//发送验证码
    public function  AjaxGetcode()
    {
        $data = M("duoduo2010");
        $type = I('post.type', '');
        if($type=='newphone')
        {
	        $z = $data->where('tel=' . $_POST['tel'])->select();
	        if ($z) {
	            $this->ajaxReturn(array('code' => 0, 'msg' => '号码已被使用'));
	        } else {
	            if (!isset($_SESSION['last_access_reg']) || (time() - $_SESSION['last_access_reg']) > 60) {
	                $code = rand(1000, 9999);
	                sendTemplateSMS($_POST['tel'], array($code, '30'), "221424",'8aaf07086010a0eb016019e9b4f001cf');//手机号码，替换内容数组，模板ID
	                $_SESSION['code_tel'] = $code;
	                $_SESSION['last_access_tel'] = time();
	                $this->ajaxReturn(array('code' => 1, 'msg' => '验证码已发送！'));
	            } else {
	                $this->ajaxReturn(array('code' => 0, 'msg' => '请勿重复发送'));
	            }
	        }
        }else{
	        $z = $data->where('tel='.$_POST['tel'].' and id='.$_SESSION['ext_user']['id'])->select();
	        if(empty($z))
	        {
	            $this->ajaxReturn(array('code' => 0, 'msg' => '手机号码不正确'));
	        }else{
	            if (!isset($_SESSION['last_access_reg']) || (time() - $_SESSION['last_access_reg']) > 60) {
	                $code = rand(1000, 9999);
	                sendTemplateSMS($_POST['tel'], array($code, '30'), "221424",'8aaf07086010a0eb016019e9b4f001cf');//手机号码，替换内容数组，模板ID
	                $_SESSION['code_reg'] = $code;
	                $_SESSION['last_access_reg'] = time();
	                $this->ajaxReturn(array('code' => 1, 'msg' => '验证码已发送！'));
	            } else {
	                $this->ajaxReturn(array('code' => 0, 'msg' => '请勿重复发送'));
	            }
	        }
        }

    }

	//会员充值
    public function  viprecharge()
	{
		$useretime = M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->getField('etime');
		$etime = strtotime($useretime) > time() ? date("Y-m-d", strtotime($useretime)) : date("Y-m-d");
		$fund = M('duoduo2010')->where('id='.$_SESSION['ext_user']['id'])->getField('fund');
        $this->assign('etime',$etime);
        $this->assign('fund',$fund);
		$this->display();
	}
	public function vipmanage()
	{
        $list = M(tbviprecord)->where('uid=' . $_SESSION['ext_user']['id'])->select();
        $pay_type = array(1 => '30天', 2 => '季付', 3 => '365天');
        $this->pay_type = $pay_type;
        $this->list = $list;
        $this->display();
    }
	//开通会员
    public function Ajaxvip()
	{
		if(IS_POST)
		{
            $user = M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->find();
            $time_type = $_POST['time'];
            if($time_type=='month')
            {
            	$price = 99;
            	$type = 1;
            }elseif($time_type=='year')
            {
            	$price = 999;
            	$type = 3;
            }
            $fund = M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->getField('fund');
            if ($fund >= $price) {
                if($time_type=='month')
                {
                    $time = 30 * 24 * 60 * 60;
                }elseif($time_type=='year')
                {
                    $time = 365 * 24 * 60 * 60;
                }
                M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->setDec('fund', $price); //确认充值VIP
                $add['uid'] = $_SESSION['ext_user']['id'];
                $add['username'] = $user['adminname'];
                $add['price'] = $price;
                $add['vip_type'] = 1;
                $add['pay_type'] = $type;
                $add['addtime'] = date("Y-m-d H:i:s");
                M('tbviprecord')->add($add);
                $etime = strtotime($user['etime']) > time() ? $user['etime'] : date("Y-m-d H:i:s");
                $arr['etime'] = date("Y-m-d H:i:s", strtotime($etime) + $time);
                $arr['member_type'] = 1;
                M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->save($arr);
                $this->ajaxReturn(array('code' => 1, 'msg' => '开通成功！'));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '余额不足，请充值！'));
            }
        }
	}
}

