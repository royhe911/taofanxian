<?php
namespace Home\Controller;
use Think\Controller;
use Common\Util\Util;
/**
 * @auth TRUE
 * @name 首页控制器
 * */

class IndexController extends Controller {
	/**
	 * @auth TRUE
	 * @name 查看首页
	 * */
    public function index(){

    	$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		
		//服装精选
		$where = "cid in(10001,10002,10005) and confirm=2 and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data1 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		//家居日用
		$where = "cid in(10008,10015) and confirm=2 and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data2 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		//美妆配饰
		$where = "cid in(10009,10011) and confirm=2 and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data3 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		//数码家电
		$where = "cid in(10007,10013,10012,10014) and confirm=2 and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data4 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		//美食特产
		$where = "cid in(10006) and confirm=2 and addtime<now() and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data5 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		//鞋帽箱包
		$where = "cid in(10003,10010) and confirm=2 and addtime<now() and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data6 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		//母婴用品
		$where = "cid in(10004) and confirm=2 and addtime<now() and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$data7 = M('tbgoods')->where($where)->order('addtime DESC')->limit(0,8)->select();
		
		$this->assign('tuijian',$tuijian);
		$this->assign('data1',$data1);
		$this->assign('data2',$data2);
		$this->assign('data3',$data3);
		$this->assign('data4',$data4);
		$this->assign('data5',$data5);
		$this->assign('data6',$data6);
		$this->assign('data7',$data7);
		$this->display();
    }


	public function detail(){
		$category = array(
						'10001' => '服装精选',
						'10002' => '服装精选',
						'10003' => '鞋帽箱包',
						'10004' => '母婴用品',
						'10005' => '服装精选',
						'10006' => '美食特产',
						'10007' => '数码家电',
						'10008' => '家居日用',
						'10009' => '美妆配饰',
						'10010' => '鞋帽箱包',
						'10011' => '美妆配饰',
						'10012' => '数码家电',
						'10013' => '数码家电',
						'10014' => '数码家电',
						'10015' => '家居日用',
						'10016' => '其他'
						);
		$id = I('get.id',0);
		$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		$goods = M('tbgoods')->where('id='.$id)->find();
		if($goods)
		{
			$publish = M('tbgoods')->where('role_id='.$goods['role_id'])->count();
			$member_type = M('duoduo2010')->where('id='.$goods['role_id'])->getField('member_type');
			$this->assign('member_type',$member_type);
			$this->assign('publish',$publish);
			$this->assign('category',$category);
			$this->assign('goods',$goods);
			$this->assign('tuijian',$tuijian);
			$this->display();
		}else{
			$this->error('商品不存在！');
		}
	}

	public function classify()
	{
		$cid = I('get.cid',0);
		if($cid==1){
			$where = ' cid in(10001,10002,10005) and confirm=2 and addtime<now()';
		}elseif($cid==2){
			$where = ' cid in(10008,10015) and confirm=2 and addtime<now()';
		}elseif($cid==3){
			$where = ' cid in(10009,10011) and confirm=2 and addtime<now()';
		}elseif($cid==4){
			$where = ' cid in(10007,10013,10012,10014) and confirm=2 and addtime<now()';
		}elseif($cid==5){
			$where = ' cid in(10006) and confirm=2 and addtime<now()';
		}elseif($cid==6){
			$where = ' cid in(10003,10010) and confirm=2 and addtime<now()';
		}elseif($cid==7){
			$where = ' cid in(10004) and confirm=2 and addtime<now()';
		}else{
			$where = ' confirm=2 and addtime<now()';
			$cid==0;
		}
		$this->display();
	}
	public function business()
	{
		$this->display();
	}
	public function businessC()
	{
		$this->display();
	}
	public function guide()
	{
		$this->assign('code','guide');
		$this->display();
	}

	public function norm()
	{
		$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		$this->assign('tuijian',$tuijian);
		$this->display();
	}
	public function yaoqing()
	{
		$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		$this->assign('tuijian',$tuijian);
		$this->display();
	}
	public function holiday()
	{
		$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		$this->assign('tuijian',$tuijian);
		$this->display();
	}
	public function ban()
	{
		$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		$this->assign('tuijian',$tuijian);
		$this->display();
	}
	public function suggest()
	{
		$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();
		$this->assign('tuijian',$tuijian);
		$this->display();
	}
}

