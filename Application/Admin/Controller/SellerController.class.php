<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;



class SellerController extends InterceptController {
	
	public $category = array('10001' => '女装','10002' => '男装','10003' => '鞋包','10004' => '母婴','10005' => '内衣','10006' => '美食','10007' => '数码','10008' => '家居','10009' => '美妆','10010' => '户外','10011' => '配饰','10012' => '家装','10013' => '家电','10014' => '车用','10015' => '图书','10016' => '其他');
	
	public function index()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$adminname = I('get.adminname','');
		$tel = I('get.tel','');
		if($adminname)
		{
			$where .= " adminname like '%".trim($adminname)."%'";
		}
		if($tel)
		{
			$where = empty($where)?'':$where.'and';
			$where .= " tel like '%".trim($tel)."%'";
		}
		if(!empty($where))
		{
			$data = M('duoduo2010')->where($where)->order('id desc')->limit($page*$page_size,$page_size)->select();
			$totalCount = M('duoduo2010')->where($where)->count();
		}else
		{
			$data = M('duoduo2010')->order('id desc')->limit($page*$page_size,$page_size)->select();
			$totalCount = M('duoduo2010')->count();
		}
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->assign('adminname',$adminname);
		$this->assign('tel',$tel);
		$this->display();
	}
	public function finance_record()
	{
		$uid = I('get.id','');
		if(empty($uid))$this->error('商家信息错误！', U('Seller/index'));
		$seller = M('duoduo2010')->where('id='.$uid)->find();
		if(empty($seller))$this->error('商家信息错误！', U('Seller/index'));
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$data = M('tborderlist')->where('userid='.$uid)->order('uptime DESC')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('tborderlist')->where('userid='.$uid)->count();
		
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('seller',$seller);
		$this->assign('list',$data);
		$this->display();
	}
	public function draw_record()
	{
		$uid = I('get.id','');
		if(empty($uid))$this->error('商家信息错误！', U('Seller/index'));
		$seller = M('duoduo2010')->where('id='.$uid)->find();
		if(empty($seller))$this->error('商家信息错误！', U('Seller/index'));
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$data = M('tbcash')->where('uid='.$uid)->order('uptime DESC')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('tbcash')->where('uid='.$uid)->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('seller',$seller);
		$this->assign('list',$data);
		$this->display();
	}
	
	public function goods(){
		$uid = I('id',0,'intval');//用户ID
		if (!empty($uid)){
			$seller = M('duoduo2010')->where('id='.$uid)->find();
			if(empty($seller))$this->error('该商家不存在！');
			$page = I('get.page',1);
			$page = $page < 1 ? 0 : $page - 1;
			$page_size = I('get.pagesize',20);	
			$where = array('uid' => $uid);
			$count = D('tbgoods_activity')->where($where)->count();
            $query = "SELECT a.id,a.cid,a.unit_price,a.gname,a.num,a.image,a.state,a.money,a.starttime,a.endtime,a.addtime,b.count FROM tfx_tbgoods_activity a LEFT JOIN (SELECT count(id)as count,gid FROM tfx_tbtrialorders WHERE comfirm between 10 and 31 GROUP BY gid ) b on a.id=b.gid ";
		    $query .= " where a.uid =".$uid;
		    $query .= " order by a.addtime desc limit ".($page*$page_size).",".$page_size;
            $data = M()->Query($query);
            //dump($data);
			//$data = D('tbgoods_activity')->order('starttime DESC, id DESC')->field('id,cid,unit_price,gname,image,state,money,starttime,endtime,addtime')->limit($page*$page_size,$page_size)->where($where)->select();
			$this->assign('pagination',  Util::getInstance('Pagination')->create($page+1,$page_size,$count));
			$this->assign('category',$this->category);
			$this->assign('seller',$seller);
			$this->assign('data',$data);
			$this->assign('id', $uid);
			$this->display();
		} else {
			$this->error('商家信息错误！');
		}
	}
	
	public function customer(){
		$uid = I('uid',0,'intval');//用户ID
		$gid = I('gid',0,'intval');//产品ID
		if (!empty($gid)){
			$seller = M('duoduo2010')->where('id='.$uid)->find();
			if(empty($seller))$this->error('该商家不存在！');
			$page = I('get.page',1);
			$page = $page < 1 ? 0 : $page - 1;
			$page_size = I('get.pagesize',20);
			$where = array('gid' => $gid);
			$count = D('tbtrialorders')->where($where)->count();
			$data = D('CustomerView')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->select();
			$this->assign('pagination',  Util::getInstance('Pagination')->create($page+1,$page_size,$count));
			$this->assign('seller',$seller);
			$this->assign('data',$data);
			$this->assign('uid',$uid);
			$this->display();
		} else {
			$this->error('非法操作！');
		}
	}
	
	public function info(){
		
		$uid = I('uid',0,'intval');//用户ID
		$gid = I('gid',0,'intval');//产品ID
		if (!empty($gid)){
			$seller = M('duoduo2010')->where('id='.$uid)->find();
			if(empty($seller))$this->error('该商家不存在！');
			
			$where = array('id' => $gid);
			$info = D('tbgoods_activity')->where($where)->find();
			if (!empty($info['format'])) $info['format'] = json_decode($info['format'], true);//商品规格
			if (!empty($info['tag'])) $info['tag'] = json_decode($info['tag'], true);//商品搜索设置
			if (!empty($info['task'])) $info['task'] = json_decode($info['task'], true);//商品任务
			if (!empty($info['service'])) $info['service'] = json_decode($info['service'], true);//增值服务
			if (!empty($info['days'])) $info['days'] = json_decode($info['days'], true);
			
			$this->assign('category', $this->category[$info['cid']]);
			$this->assign('seller', $seller);
			$this->assign('info', $info);
			$this->assign('id', $uid);
			$this->display();
		} else {
			$this->error('非法操作');
		}
	}
}
?>