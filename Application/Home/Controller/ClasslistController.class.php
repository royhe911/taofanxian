<?php
namespace Home\Controller;
use Think\Controller;
use Common\Util\Util;
/**
 * @auth TRUE
 * @name 首页控制器
 * */

class ClasslistController extends Controller {
	/**
	 * @auth TRUE
	 * @name 查看首页
	 * */
    public function index()
    {
    	$keywords = I('get.keywords','');
		$nowpage = I('get.nowpage','');
    	if($keywords)
    	{
    		$code = I('get.code','new');
    		$this->redirect('Classlist/keyclick', array('keywords' => $keywords,'code'=>$code,'nowpage'=>$nowpage));exit;
    	}
    	$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();

    	$cid = I('get.cid',0);
		if(in_array($cid,array(10001,10002,10005))){
			$where = 'cid in(10001,10002,10005) ';
		}elseif(in_array($cid,array(10008,10015))){
			$where = 'cid in(10008,10015)';
		}elseif(in_array($cid,array(10009,10011))){
			$where = 'cid in(10009,10011)';
		}elseif(in_array($cid,array(10007,10013,10012,10014))){
			$where = 'cid in(10007,10013,10012,10014)';
		}elseif(in_array($cid,array(10006))){
			$where = 'cid in(10006) and confirm=2 and addtime<now()';
		}elseif(in_array($cid,array(10003,10010))){
			$where = 'cid in(10003,10010)';
		}elseif(in_array($cid,array(10004))){
			$where = 'cid in(10004)';
		}else{
			$cid = 10016;
			$where = '';
		}
		$code = I('get.code','new');
		if($code=='new')
		{
			$order = 'addtime desc';
		}elseif($code=='price'){
			$order = 'real_price desc';
		}elseif($code=='seller'){
			$order = 'sell_number desc';
		}elseif($code=='jinping'){
			$order = 'price desc';
		}else{
			$order = 'addtime desc';
		}
		if($where)
		{
			$where .=' and ';
		}
		$where .="confirm=2 and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$listrows= 50;
		$totalCount = M('tbgoods')->where($where)->count();
        $p = getpage($totalCount,$listrows);
		$data = M('tbgoods')->where($where)->order($order)->limit($p->firstRow,$p->listRows)->select();
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
		$this->assign('nowpage',$nowpage);
		$this->assign('category',$category);
		$this->assign('cid',$cid);
		$this->assign('code',$code);
		$this->assign('page', $p->show()); 
		$this->assign('tuijian', $tuijian); 
		$this->assign('data', $data); 
		$this->display();
    	
    }

public function keyclick()
    {
    	$where = "confirm=2 and addtime<now() and kucun>0 and UNIX_TIMESTAMP(etime)>".time();
		$tuijian = M('tbgoods')->where($where)->order('price DESC')->limit(0,5)->select();

    	$keywords= I('get.keywords',0);
    	if($keywords)
    	{
    		$where = "goods_name like '%".$_GET['keywords']."%'";
    	}
			$cid = 10016;
			
		$code = I('get.code','new');
		$order = 'addtime desc';
		if($where)
		{
			$where .=' and ';
		}
		$where .="confirm=2 and UNIX_TIMESTAMP(addtime)<".time()." and UNIX_TIMESTAMP(etime)>".time()." and kucun>0";
		$listrows= 50 ;
		$totalCount = M('tbgoods')->where($where)->count();
        $p = getpage($totalCount,$listrows);
		$data = M('tbgoods')->where($where)->order($order)->limit($p->firstRow,$p->listRows)->select();
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
						'10016' => '全部商品'
						);
		$nowpage = I('get.nowpage','');
		$this->assign('nowpage',$nowpage);
		$this->assign('category',$category);
		$this->assign('cid',$cid);
		$this->assign('keywords',$keywords);
		$this->assign('code',$code);
		$this->assign('page', $p->show()); 
		$this->assign('tuijian', $tuijian); 
		$this->assign('data', $data); 
		$this->display('index');
    	
    }
	

}

