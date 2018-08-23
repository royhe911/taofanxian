<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/3
 * Time: 10:00
 */

namespace Supporter\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;

class MerchantController extends BaseController
{
    public function index()
    {
        $this->display();
    }
	public function finance()
	{
    	$money = I('get.money','');
    	$fund = M('duoduo2010')->where('id='.$_SESSION['ext_user']['id'])->getField('fund');
        $this->assign('fund',$fund);
		$this->assign('money', $money);
        $this->display();
    }
	public function zfb()
	{
        $out_trade_no = getordcode(); //$_POST['trade_no'];//商户订单号 通过支付页面的表单进行传递，注意要唯一！
        $subject = '商户充值'; //$_POST['ordsubject'];  //订单名称 //必填 通过支付页面的表单进行传递
        $total_fee = $_POST['amount'];
        $id = $_SESSION['ext_user']['id'];
        $this->assign('id', $id);
        $this->assign('total_fee', $total_fee);
        $this->assign('out_trade_no', $out_trade_no);
        if ($total_fee) {
            $data = array(
                'userid' => $_SESSION['ext_user']['id'],
                'username' => $_SESSION['ext_user']['adminname'],
                'ordid' => $out_trade_no,
                'productid' => 0, //产品ID
                'ordtitle' => '商户充值',
                'ordbuynum' => 1,
                'ordprice' => $total_fee,
                'ordfee' => $total_fee,
                'ordstatus' => 0,
                'payment_type' => 2, //支付类型 1在线即时支付、2打款支付
                'addtime' => date('Y-m-d H:i:s'),
                'uptime' => date('Y-m-d H:i:s'),
            );
            if (!M('tborderlist')->add($data)) {
                $this->error('系统错误，提交失败！');
            } else {
                $this->display();
            }
        } else {
            $this->display();
        }
    }
    public function draw(){
    	$userdata = M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->find();
    	$zfb = $userdata['zfb'];
    	$zfb_accounts = $userdata['zfb_accounts'];
    	if(!empty($zfb)&&!empty($zfb_accounts))
    	{
    		$fund = $userdata['fund'];
	        $zfb_accounts = $userdata['zfb_accounts'];
	        if(IS_POST)
	        {
	        	$amount = I('post.amount', '');
	            if ($amount <= $fund) {
	                $amount_s = strtotime($_SESSION['ext_user']['etime']) > time() ? $amount : $amount * 0.99;
	                $data = array(
	                    'uid' => $_SESSION['ext_user']['id'],
	                    'amount' => $amount_s,
	                    'zfb' => $userdata['zfb'],
	                    'zfb_accounts' => $userdata['zfb_accounts'],
	                    'status' => 1, //1申请，2通过，3驳回
	                    'addtime' => date('Y-m-d H:i:s'),
	                    'uptime' => date('Y-m-d H:i:s'),
	                );
	                if (!M('tbcash')->add($data)) {
	                    $this->error('系统错误，提交失败！', U('Merchant/draw'));
	                } else {
	                    M('duoduo2010')->where('id=' . $_SESSION['ext_user']['id'])->setDec('fund', $amount); //减余额
	                    $this->redirect('Merchant/record', Null, 1, '申请成功，页面跳转中...');
	                }
	            } else {
	                $this->error('申请提现金额错误，提交失败！', U('Merchant/draw'));
	            }
	        }
	        $this->assign('fund',$fund);
	        $this->assign('zfb_accounts', $zfb_accounts);
	        $this->display();
    	}else{
    		$this->display('bindZfb');
    	}
        
    }
    public function record()
    {
    	$type = I('get.type','draw');
    	$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
    	if($type=='recharge')
    	{
    		$where = array('uid' => $_SESSION['ext_user']['id']);
			$data = M('tbcash')->where($where)->order('uptime DESC')->limit($page*$page_size,$page_size)->select();
			$totalCount = M('tbcash')->where($where)->order('uptime DESC')->count();
    	}elseif($type=='draw')
    	{
    		$where = array('userid' => $_SESSION['ext_user']['id']);
        	$data = M('tborderlist')->where($where)->order('uptime DESC')->limit($page*$page_size,$page_size)->select();
			$totalCount = M('tborderlist')->where($where)->order('uptime DESC')->count();
    	}
        
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
        $this->assign('data', $data);
        $this->assign('type', $type);
        $this->display();
    }
    //活动明细
    public function actlist(){
        $this->display();
    }
    //活动详情
    public function actdetail(){
        $this->display();
    }
}

