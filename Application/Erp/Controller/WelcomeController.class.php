<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;

class WelcomeController extends BaseController {

	public function index(){
		$now = (date('a') == 'am') ? '上午' : '下午';
		$msg = D('account')->where(array('id' => $_SESSION['user']['id']))->field('ip,role,edittime')->find();

		if (5 == $msg['role']){//商家行为
			$info = D('user a')
                ->where(array('uid' => $_SESSION['user']['id']))
                ->join('left join erp_account b on a.tid = b.id')
                ->field('a.*,b.qq,b.info,b.phone,b.wechat')
                ->find();
			if (1 != $info['msg']) unset($_SESSION['user']['column']);//店铺未通过审核的商家不显示左边栏
			if (1 != $info['status']) unset($_SESSION['user']['column']);//冻结的商家不显示左边栏
            $this->assign('wangwang',$info['wangwang']);
			$this->assign('iphone',$info['iphone']);
			$this->assign('info', $info);

		}

		$this->assign('msg', $msg);
		$this->assign('now', $now);
		$this->display();
	}

	//修改密码
	public function uppassword(){

		if (IS_POST){
			$newpassword  = I('post.newpassword');
			$newpassword2 = I('post.newpassword2');

			if (empty($newpassword)) $this->error('请输入新密码');
			if (empty($newpassword2)) $this->error('请输入再次新密码');
			if ($newpassword != $newpassword2) $this->error('两次输入密码不一致');
			if (strlen($newpassword) < 6) $this->error('密码不能少于6位数');

			$msg = D('account')->where(array('id' => $_SESSION['user']['id']))->save(array('password' => md5($newpassword)));
			if ($msg){
				$this->success('修改成功', 'javascript:parent.location.reload();');
			} else {
				$this->error('修改失败');
			}
		} else {
			$this->display();
		}
	}

	//图片上传
	public function ajaxUpload(){

		$config = array(
				'maxSize'    =>   10485760,  //10M
				'rootPath' => './upload/',
				'savePath' => date("Ymd"),
				'saveName'   =>    array('uniqid',''),
				'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
				'autoSub'    =>    true,
				'subName'    =>    array('',''),
		);

		$upload = new \Think\Upload($config);// 实例化上传类
		$info = $upload->upload();
		if (!$info) {
			echo json_encode(array('status' => 2, 'msg' => $upload->getError()));
		} else {
			echo json_encode(array('status' => 1, 'msg' => '上传成功！', 'name' => $info['fileToUpload']['savename'], 'savepath' => $info['fileToUpload']['savepath']));
		}
	}

	//修改资料
    public function message(){
	    if(IS_GET){

	        $id=$_SESSION['user']['id'];
	        $user_info=D('account')->where('id='.$id)->find();
	        $userInfo=D('user')->where(array('uid'=>$id))->find();
	        $this->assign('user_info',$user_info);
	        $this->assign('userInfo',$userInfo);
	        $this->display();
        }else{

	        $id=intval($_POST['id']);
	        $qq=trim($_POST['qq']);
	        $card=trim(I('post.card'));
	        if( $_SESSION['user']['role'] == 6 )  if(empty($qq)) $this->error('业务员QQ不能为空');
            $where="qq = {$qq} and id !={$id}";
            $res=D('account')->where($where)->find();
            if($res) $this->error('qq存在');
            if( $_SESSION['user']['role'] == 6 ) $update_qq=D('user')->where(array('tid'=>$_SESSION['user']['id']))->setField('tutor',$qq);
	        D('Account')->where('id='.$id)->setField('qq',$qq);
	        D('user')->where('uid='.$id)->setField('card',$card);
//	        if(!$res){
//	            $this->error('修改失败');
//            }
	        $this->success('修改成功','javascript:parent.location.reload();');

        }
    }


}
?>