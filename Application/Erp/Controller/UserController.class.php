<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/4/9
 * Time: 15:00
 */
namespace Erp\Controller;
use Erp\Model\LogModel;
use Think\Controller;
use Common\Util\Util;

class UserController extends BaseController {

	public function index(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
		$info = D('user')->where(array('uid' => $_SESSION['user']['id']))->find();
		$this->assign('info',$info);
		$this->display();
	}

	//绑定店铺
	public function shop(){

		if (IS_POST){
			$url = I('post.url');
			$id  = I('post.id',0,'intval');
			$wangwang = trim(I('post.wangwang'));
            $shopname= trim(I('post.shopname'));
            
			if (empty($id)) {$this->error('非法操作！');die();}
			if (empty($url)) {$this->error('请填写店铺地址！');die();}
			if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url)) $this->error('无效的 URL！');
			if (empty($wangwang)) {$this->error('请填写旺旺号！');die();}
			
			$info = D('user')->where(array('id' => $id))->field('msg,uid')->find();
			if (1 == $info['msg']) {$this->error('店铺已绑定,不可修改');die();}
			
			$res = D('shop')->where('wangwang=\''.$wangwang.'\' and uid!='.$info['uid'])->find();
            if ($res) {
                $this->error('旺旺名存在，请更换');
                return false;
            }
			$res_shopname = D('shop')->where('shopname=\''.$shopname.'\' and uid!='.$info['uid'])->find();
            if ($res_shopname) {
                $this->error('店铺存在，请更换');
                return false;
            }
			$res_url = D('shop')->where('url=\''.$url.'\' and uid!='.$info['uid'])->find();
            if ($res_url) {
                $this->error('店铺链接存在，请更换');
                return false;
            }

			
			
			$msg = D('user')->where(array('id' => $id))->save(array('msg' => 0 ,'url' => $url, 'shopname' => $shopname, 'wangwang' => $wangwang, 'edittime' => time()));
			if($msg)
			{
				$data['uid'] =  $info['uid'];
				$data['shopname'] =  $shopname;
				$data['url'] =  $url;
				$data['wangwang'] =  $wangwang;
				$data['status'] =  0;
				$data['addtime'] =  time();
				$data['suretime'] =  time();
				$shopid = M('shop')->add($data);
				$this->success('操作成功!');
			} else {
				$this->error('操作失败!');
			}
		} else {
			$info = D('user')->where(array('uid' => $_SESSION['user']['id']))->field('id')->find();

			$this->assign('info', $info);
			$this->display();
		}
	}

	//充值
	public function recharge(){

		if (IS_POST){
			$recharge = D('recharge');
            $serialnum=I('post.serialnum');
            $rechargenum=floatval(I('post.rechargenum'));
            if($rechargenum < 50.00) {$this->error('充值金额不得小于50');exit;}
            $method   =I('post.method',0,'int');
            if($method != 1 && $method != 2){
                {$this->error('充值方式错误');exit;}
            }
			if($method == 1){
			    if(empty($serialnum)){
                    $this->error('交易号必须填写');
                }
            }

			if (!$recharge->create()){
				$this->error($recharge->getError());
			}else{
				$msg = $recharge->add();

				if ($msg){
					$this->success('操作成功!', 'javascript:parent.location.reload();');
				} else {
					$this->error('操作失败!');
				}
			}
		} else {
			$this->check();//检测店铺
			$this->display();
		}
	}

	//修改拒绝的充值单
    public function uprecharge(){
	    if(IS_GET){
            $id = I('get.id',0,'int');
            if(empty($id) || $id <=0 ){
                $this->error('信息错误');exit;
            }
            $info=D('recharge')->where(array('id'=>$id))->find();
            $info['img_a']='upload/'.date("Ymd",$info['addtime']).'/'.$info['img'];
            $this->assign('info',$info);
	        $this->display();
        }elseif(IS_POST){
            $id = I('post.id',0,'int');
            $money=I('post.rechargenum');
            $serialnum=I('post.serialnum');
            $img      =I('post.img');
            $method   =I('post.method');

            if(empty($id) || $id <=0 ){
                $this->error('信息错误');exit;
            }
            $recharge=D('recharge a')->field('a.*,b.uid as u_uid')->join('left join erp_user b on a.uid = b.id')->where(array('a.id'=>$id))->find();
            if($recharge['u_uid'] != $_SESSION['user']['id'] || $recharge['msg'] != 2){
                $this->error('信息错误,请刷新再试');exit;
            }
            if(empty($img)){
                $this->error('图片必须上传');exit;
            }
            if($method != 1 && $method != 2){
                $this->error('支付方式错误');exit;
            }
            if($method == 1 && empty($serialnum)){
                $this->error('支付宝支付必须填写交易号');exit;
            }
            if(f_round($money) < 50 ){
                $this->error('充值金额最低不得小于50');exit;
            }
            $data=array(
                'money'=>$money,
                'method'=>$method,
                'img'=>$img,
                'msg'=>0,
                'addtime'=>time(),

            );
            if($method == 2){
                $data['serialnum'] ='';
            }elseif ($method == 1){
                $data['serialnum'] = $serialnum;
            }

            $res=D('recharge')->where(array('id'=>$id))->setField($data);
            if(!$res){
                $this->error('修改失败，请重试');exit;
            }
            $this->success('操作成功!', 'javascript:parent.location.reload();');
        }

    }

	//充值记录
	public function record(){

		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',10);

		$info = D('user')->where(array('uid' => $_SESSION['user']['id']))->field('id')->find();
		if (empty($info['id'])) $this->error('该用户不存在!');

		$where = array('uid' => $info['id']);
		$count = D('recharge')->where($where)->count();
		$data  = D('recharge')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->select();

		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
		$this->assign('count', $count);
		$this->assign('data', $data);
		$this->display();
	}

	//图片上传
	public function ajaxUpload(){

		$config = array(
				'maxSize'    =>   3145728,
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

	//检测店铺
	private function check(){

		$msg = 0;
		$info = D('user')->where(array('uid' => $_SESSION['user']['id']))->field('url,msg')->find();
		if (empty($info['url'])) $this->error('未绑定店铺', 'javascript:parent.location.reload();');//未绑定店铺
		if (1 != $info['msg']) $this->error('店铺审核中...', 'javascript:parent.location.reload();');//店铺未审核通过
	}

	//用户提现
    public function cash(){
	    if(IS_GET){
	        $user_info=D('user')->where(array('uid'=>intval($_SESSION['user']['id'])))->find();
            $av_balance=$user_info['money'] - $user_info['yufujin'];
            $this->assign('info',$user_info);
            $this->assign('av_balance',$av_balance);
	        $this->display();
        }elseif(IS_AJAX){
	        $money=floatval(I('post.money'));
	        $card=trim(I('post.card'));
	        $name=trim(I('post.name'));
	        if(empty($card)) $this->ajaxReturn(array('msg'=>0,'info'=>'银行卡必须填写'));
	        if(empty($name)) $this->ajaxReturn(array('msg'=>0,'info'=>'姓名必须填写'));
	        if ( empty($money) || $money<=0 )  $this->ajaxReturn(array('msg'=>0,'info'=>'金额必须填写'));
            $user_info=D('user')->where(array('uid'=>intval($_SESSION['user']['id'])))->find();
            $av_balance=$user_info['money'] - $user_info['yufujin'];
            if($money > $av_balance) $this->ajaxReturn(array('msg'=>0,'info'=>'提现金额不可大于可用余额'));
            M()->startTrans();
            //可用提现
            $data=array(
                'uid'=>intval($_SESSION['user']['id']),
                'money'=>$money,
                'addtime'=>time(),
                'status'=>0,
                'cost'=>round($money * 0.001,2),  //费率 0.1%
                'card'=>$card,
                'name'=>$name
            );
            $res=D('cash')->add($data);
            $cashid = D('cash')->getLastInsID();
            $user_card=array(
                'card'=>$card,
                'name'=>$name
            );
            D('user')->where('uid='.intval($_SESSION['user']['id']))->setField($user_card);
            // 扣減用戶余额，增加用户冻结金额   20180828
            // D('user')->where('uid='.intval($_SESSION['user']['id']))->setDec('money', $money);
            D('user')->where('uid='.intval($_SESSION['user']['id']))->setInc('freeze_free', $money);
            addLog(LogModel::TYPE_APPLY_CASH, '商家申请提现，提现金额：'.$money.'，提现记录ID：'. $cashid);

            $balances_status=save_available($_SESSION['user']['id'],$money,0,1,1);
            if (!$balances_status) {
                M()->rollback();
                $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误，请重新申请'));
            }
            $cash=D('user')->where(array('uid'=>intval($_SESSION['user']['id'])))->setDec('money',$money);
            if(!$res || !$cash) {
                M()->rollback();
                $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误，请重新申请'));
            }
            M()->commit();
            $this->ajaxReturn(array('msg'=>1,'info'=>'申请成功，等待审核'));
        }
    }
//添加店铺
	public function shopmanage(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
		if (IS_POST)
		{
			$url = I('post.url');
			$id  = $_SESSION['user']['id'];
			$wangwang = I('post.wangwang');
            $shopname= I('post.shopname');
            
			if (empty($id)) $this->error('非法操作！');
			if (empty($url)) $this->error('请填写店铺地址！');
			if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url)) $this->error('无效的 URL！');
			if (empty($wangwang)) $this->error('请填写旺旺号！');
			$res = D('shop')->where(array('wangwang'=>$wangwang))->find();
            if ($res) {
                $this->error('旺旺名存在，请更换');
                return false;
            }
			$res_shopname = D('shop')->where(array('shopname'=>$shopname))->find();
            if ($res_shopname) {
                $this->error('店铺名存在，请更换');
                return false;
            }
            $res_shop = D('shop')->where(array('url'=>$url))->find();
            if ($res_shop) {
                $this->error('店铺连接存在，请更换');
                return false;
            }
			$data['uid'] =  $id;
			$data['shopname'] =  $shopname;
			$data['url'] =  $url;
			$data['wangwang'] =  $wangwang;
			$data['status'] =  0;
			$data['addtime'] =  time();
			$data['suretime'] =  time();
			$shopid = M('shop')->add($data);
			if ($shopid){
				$this->success('提交成功','javascript:parent.location.reload();');
			} else {
				$this->error('操作失败!');
			}
		}else{
			$info = D('shop')->where(array('uid' => $_SESSION['user']['id']))->select();
			$this->assign('data', $info);
			$this->display();
		}
			
	}
	
//修改店铺
	public function editshop(){
		if (IS_POST)
		{
			$url = I('post.url');
			$id  = I('post.id');
			$wangwang = I('post.wangwang');
            $shopname= I('post.shopname');
			if (empty($id)) $this->error('非法操作！');
			if (empty($url)) $this->error('请填写店铺地址！');
			if (empty($wangwang)) $this->error('请填写旺旺号！');
			if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url)) $this->error('无效的 URL！');
			
		
            $res=D('shop')->where('wangwang=\''.$wangwang.'\' and id!='.$id)->find();
            if ($res) {
                $this->error('旺旺名存在，请更换');
                return false;
            }
			$res_shopname = D('shop')->where('shopname=\''.$shopname.'\' and id!='.$id)->find();
            if ($res_shopname) {
                $this->error('店铺存在，请更换');
                return false;
            }
			$res_url = D('shop')->where('url=\''.$url.'\' and id!='.$id)->find();
            if ($res_url) {
                $this->error('店铺链接存在，请更换');
                return false;
            }
            
			$data['shopname'] =  $shopname;
			$data['url'] =  $url;
			$data['wangwang'] =  $wangwang;
			$data['status'] =  0;
			$data['addtime'] =  time();
			$shopid = M('shop')->where('id='.$id)->save($data);
			if ($shopid){
				$this->success('提交成功','javascript:parent.location.reload();');
			} else {
				$this->error('操作失败!');
			}
		}else{
			$id = I('get.id','');
			if (empty($id)) $this->error('非法操作！');
			$info = D('shop')->where(array('id' => $id))->find();
			$this->assign('info', $info);
			$this->assign('id', $id);
			$this->display();
		}
			
	}	
	
}
?>