<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/16
 * Time: 15:00
 */
namespace Supporter\Controller;
use Think\Controller;
use Common\Util\Util;

class CouponController extends BaseController {

	public $category = array('10001' => '女装','10002' => '男装','10003' => '鞋包','10004' => '母婴','10005' => '内衣','10006' => '美食','10007' => '数码','10008' => '家居','10009' => '美妆','10010' => '户外','10011' => '配饰','10012' => '家装','10013' => '家电','10014' => '车用','10015' => '图书','10016' => '其他');
	
	//优惠券管理
	public function index(){
		
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',10);
		
		$confirm = I('confirm',0,'intval');
		$gname = I('gname','','strip_tags');
		$where = array('role_id' => $_SESSION['ext_user']['id'], 'trash' => 0);
		if (!empty($confirm)){
			$state = array(1 => 1, 2 => 2, 3 => 2, 4 => 0, 5 => 99, 6 => 100);
			if (3 == $confirm){//已结束试用
				$where['etime'] = array('ELT', date('Y-m-d H:i:s', time()));
				$where['confirm'] = array('EQ', 2);
			} elseif (2 == $confirm){//活动中试用
				$where['etime'] = array('GT', date('Y-m-d H:i:s', time()));
				$where['confirm'] = array('EQ', 2);
			} else {
				$where['confirm'] = $state[$confirm];
			}
		} elseif (!empty($gname)){//搜索
			$where['goods_name'] = array('LIKE', '%'.$gname.'%');
			$this->assign('gname', $gname);
		}
		
		$totalCount[0] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'trash' => 0))->count();//所有
		$totalCount[6] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'confirm' => 100, 'trash' => 0))->count();//待完善
		$totalCount[5] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'confirm' => 99, 'trash' => 0))->count();//待支付
		$totalCount[4] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'confirm' => 0, 'trash' => 0))->count();//待审核
		$totalCount[1] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'confirm' => 1, 'trash' => 0))->count();//已驳回
		$totalCount[2] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'confirm' => 2, 'trash' => 0, 'etime' => array('GT', date('Y-m-d H:i:s', time()))))->count();//活动中
		$totalCount[3] = D('tbgoods')->where(array('role_id' => $_SESSION['ext_user']['id'], 'confirm' => 2, 'trash' => 0, 'etime' => array('ELT', date('Y-m-d H:i:s', time()))))->count();//已结束
		
		$count = D('tbgoods')->where($where)->count();//当前
		$subQuery = 'select gid, count(case when state >= 1 then state end) as sq, count(case when state >= 6 then state end) as lq from tfx_tbusertrade as tb LEFT JOIN tfx_tbgoods as tbg ON tbg.id = tb.gid GROUP BY gid';
		$data = D('tbgoods')->order('id DESC')->limit($page*$page_size,$page_size)->where($where)->alias('g')
		                    ->field(array('id', 'cid', 'laiyuan', 'kucun', 'goods_name', 'shopname', 'img', 'real_price', 'red_price', 'url', 'etime', 'confirm', 'confirmnote', 'addtime', 'sq', 'lq'))
		                    ->join('LEFT JOIN ('.$subQuery.') AS t ON t.gid=g.id')
		                    ->select();
		if ($totalCount[3] >= 1) $this->refund();//退款(活动结束)
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
		$this->assign('category', $this->category);
		$this->assign('totalCount', $totalCount);
		$this->assign('confirm', $confirm);
		$this->assign('data', $data);
		$this->display();
	}
	
	/**
	 * 回收站管理
	 *
	 */
	public function trash(){
		
		$id = I('id',0,'intval');//商品ID
		if (!empty($id)){//回收处理
			$info = D('tbgoods')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->field('trash,confirm')->find();
			if (!empty($info)){
				if (2 >= $info['confirm']) $this->error('操作失败,该优惠券不可放入回收站');
				empty($info['trash']) ? $msg = 1 : $msg = 0;
				$msg = D('tbgoods')->where(array('id' => $id))->setField('trash',$msg);
				if (!empty($msg)){
					$this->success('操作成功');
				} else {
					$this->error('操作失败,请稍后重试');
				}
			} else {
				$this->error('该优惠券不存在');
			}
		} else {//垃圾列表
			$page = I('get.page',1);
			$page = $page < 1 ? 0 : $page - 1;
			$page_size = I('get.pagesize',10);
			$gname = I('gname','','strip_tags');
			$where = array('uid' => $_SESSION['ext_user']['id'], 'trash' => 1);
			if (!empty($gname)) $where['goods_name'] = array('LIKE', '%'.$gname.'%');
			
			$count = D('tbgoods')->where($where)->count();
			$data = D('tbgoods')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->select();
			
			$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
			$this->assign('category', $this->category);
			$this->assign('gname', $gname);
			$this->assign('data', $data);
			$this->display();
		}
	}
	
	//发布优惠券第一步
	public function publish1(){
		
		$id = I('id',0,'intval');//试用商品ID
		if (IS_POST){
			$tbgoods = D('tbgoods');
			if (!$tbgoods->autoCheckToken($_POST)) $this->error('令牌验证错误');//防止重复提交
			$msg = array('role_id' => $_SESSION['ext_user']['id'], 'laiyuan' => I('type',0,'intval'), 'cid' => I('commodity_classify',0,'intval'),
					     'goods_name' => I('commodity_name','','test_input'), 'title' => I('goods_name','','test_input'), 'url' => I('shop_url','','test_input'), 
					     'real_price' => I('unit_price','','intval'), 'addtime' => date('Y-m-d H:i:s', time()), 'etime' => date('Y-m-d H:i:s', time()),'sid' => I('sid',0,'intval'),
					     'qq' => I('qq','','test_input'), 'note' => I('note','','test_input'), 'shopname' => I('shopname','','test_input'),
					     'img' => I('mypic','','test_input'), 'confirm' => 100
			);
			
			if (empty($msg['sid'])) $this->error('请选择店铺');
			if (empty($msg['goods_name'])) $this->error('请填写平台展示名称');
			if (empty($msg['title'])) $this->error('请填写淘宝商品标题');
			if (empty($msg['url'])) $this->error('请填写商品链接');
			if (empty($msg['real_price'])) $this->error('请填写试客下单价格');
			if (0 == $this->isUrl($msg['url'])) $this->error('请填写正确的商品链接');
			
			$store = D('tbsellerinfo')->field('id,shopname,type,zhanggui,qq')->where(array('id' => $msg['sid'], 'sid' => $_SESSION['ext_user']['id'], 'confirm' => 2))->find();
			if (empty($store)) $this->error('非法操作');
			$msg['qq'] = $store['qq'];
			$msg['note'] = $store['zhanggui'];
			$msg['shopname'] = $store['shopname'];
			$msg['laiyuan'] = $store['type'];
			$msg['real_price'] = number_format($msg['real_price'],2);
			
			
			if (!empty($id)){//修改
				$result = D('tbgoods')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->save($msg);
				if (0 == $result) $this->error('非法操作');
				$result = $id;
			} else {//添加
				$result = D('tbgoods')->add($msg);
			}
			
			if (false === $result) $this->error('操作失败，请稍候重试');
			$this ->redirect('Coupon/publish2/', array('id' => $result));
		} else {			
			if (!empty($id)){
				$goods = D('tbgoods')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->find();//商品信息
				if (empty($goods)) $this->error('非法操作', U('Coupon/publish1'));
				if (99 > $goods['confirm']) $this->error('操作失败,该活动不可修改！', U('Coupon/index'));
			}
			
			$data = D('tbsellerinfo')->field('id,shopname,type,zhanggui,qq')->order('type asc')->where(array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2))->select();//店铺信息
			if (empty($data)) $this->error('请先绑定店铺', U('Usercenter/bindshop'));//无店铺,去添加
			
			$this->assign('category', $this->category);//分类
			$this->assign('goods', $goods);
			$this->assign('data', $data);
			$this->display();
		}
	}   
	     
	//发布优惠券第二步
	public function publish2(){
		$id = I('id',0,'intval');//试用商品ID
		if (empty($id)) $this ->redirect('Coupon/publish1/');//无ID则跳转
		
		if (IS_POST){
			$tbgoods = D('tbgoods');
			if (!$tbgoods->autoCheckToken($_POST)) $this->error('令牌验证错误');//防止重复提交
			
			$type = I('type',0,'intval');//商品渠道
			if (1 == $type) $keyword = I('keyword');//关键字
			if (2 == $type) $keyword = I('password');//口令
			$msg = array('img2' => I('img','','test_input'), 'keyword' => $keyword, 'qudao' => $type, 'addtime' => date('Y-m-d H:i:s', time()));
			
			if (empty($msg['img2'])) $this->error('请上传商品图片');
			if (empty($msg['keyword'])) $this->error('关键词或淘口令请至少选择其中一种');
			$msg['keyword'] = implode('@', array_filter($msg['keyword']));
			$result = D('tbgoods')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->save($msg);
			if (1 == $result){
				$this ->redirect('Coupon/publish3/', array('id' => $id));
			} else {
				$this->error('操作失败,请稍候重试');
			}
		} else {
			$goods = D('tbgoods')->field('img2,keyword,qudao,confirm')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->find();//商品信息
			if (99 > $goods['confirm']) $this->error('操作失败,该活动不可修改！', U('Coupon/index'));
			if (!empty($goods['keyword'])){
				if (1 == $goods['qudao']) $goods['keyword'] = explode('@', $goods['keyword']);
				if (2 == $goods['qudao']) {
					$goods['password'] = explode('@', $goods['keyword']);
					unset($goods['keyword']);
				}
			}
			
			$this->assign('goods', $goods);
			$this->assign('id', $id);
			$this->display();
		}
	}
	//发布优惠券第三步
	public function publish3(){
		$id = I('id',0,'intval');//试用商品ID
		if (empty($id)) $this ->redirect('Coupon/publish1/');//无ID则跳转
		
		if (IS_POST){
			$tbgoods = D('tbgoods');
			if (!$tbgoods->autoCheckToken($_POST)) $this->error('令牌验证错误');//防止重复提交
			
			$msg = array('confirm' => 99, 'red_price' => I('coupon_value','','intval'), 'kucun' => I('goods_num',0,'intval'), 'etime' => I('endtime','','test_input'), 'step_one' => I('info','','test_input'), 'addtime' => date('Y-m-d H:i:s', time()));
			if (empty($msg['red_price'])) $this->error('请填写优惠券金额');
			if (empty($msg['kucun'])) $this->error('请填写发放份数');
			if (empty($msg['etime'])) $this->error('请填写结束时间');
			
			if (1 > $msg['red_price']) $this->error('优惠卷金额不得小于0');
			if (1 > $msg['kucun']) $this->error('发放份数不得小于0');
			$result = D('tbgoods')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->save($msg);
			if (1 == $result){
				$this ->redirect('Coupon/publish4/', array('id' => $id));
			} else {
				$this->error('操作失败,请稍候重试');
			}
		} else {
			$goods = D('tbgoods')->field('red_price,kucun,etime,step_one,confirm')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->find();//商品信息
			if (99 > $goods['confirm']) $this->error('操作失败,该活动不可修改！', U('Coupon/index'));
			$this->assign('goods', $goods);
			$this->assign('id', $id);
			$this->display();
		}
	}
	//发布优惠券第四步
	public function publish4(){
		$id = I('id',0,'intval');//试用商品ID
		if (empty($id)) $this ->redirect('Coupon/publish1/');//无ID则跳转
		
		$goods = D('tbgoods')->field('kucun,real_price,red_price,confirm')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->find();//商品信息
		if (empty($goods)) $this->error('非法操作');
		if (99 > $goods['confirm']) $this->error('操作失败,该活动不可修改！', U('Coupon/index'));
		
		$goods['money'] = ($goods['real_price'] + $goods['red_price'] + 1)*$goods['kucun'];//收费
		$info = D('duoduo2010')->field('fund,deposit')->where(array('id' => $_SESSION['ext_user']['id']))->find();//账户余额
		($info['fund'] - $goods['money']) >= 0 ? $msg = 1 : $msg = 0;//余额是否足够 
		$this->assign('goods', $goods);
		$this->assign('info', $info);
		$this->assign('msg', $msg);
		$this->assign('id', $id);
		$this->display();
	}

	//付款
	public function payment(){
		$id = I('id',0,'intval');//试用商品ID
		if (empty($id)) $this ->redirect('Coupon/publish1/');//无ID则跳转
		
		$goods = D('tbgoods')->field('kucun,real_price,red_price,etime,confirm')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->find();//商品信息
		$goods['etime'] = strtotime($goods['etime']);
		
		if (empty($goods)) $this->error('非法操作');
		if (99 > $goods['confirm']) $this->error('操作失败,该活动不可修改！', U('Coupon/index'));
		if ($goods['etime'] <= time()) $this->error('付款失败,您设置的试用时间已经过期,请重新设置', U('Coupon/publish3', array('id' => $id)));
		
		$goods['money'] = ($goods['real_price'] + $goods['red_price'] + 1)*$goods['kucun'];//收费
		$info = D('duoduo2010')->field('fund,deposit')->where(array('id' => $_SESSION['ext_user']['id']))->find();//账户余额
		if ($info['fund'] < $goods['money']) $this->error('余额不足，请充值', U('Coupon/publish4', array('id' => $id)));
		
		$msg = D('duoduo2010');
		$msg->startTrans ();//开启事务
		$param =  D('duoduo2010')->where(array('id' => $_SESSION['ext_user']['id']))->save(array('fund' => ($info['fund'] - $goods['money']), 'deposit' => ($info['deposit'] + $goods['money'])));
		if (!empty($param)){//付款成功
			$param = D('tbgoods')->where(array('id' => $id))->setField(array('addtime' => date('Y-m-d H:s:i', time()), 'money' => $goods['money'], 'confirm' => 0));//修改为已支付状态
			if (false === $param) $msg->rollback();//出错回滚
		} else {//付款失败
			$msg->rollback();//出错回滚
		}
		$msg->commit();//事务完成
		
		if ($param){
			$this->success('付款成功', U('Coupon/index'));
		} else {
			$this->error('付款失败', U('Coupon/index'));
		}
	}
	
	//进展详情
	public function processdetail(){
		$t = I('t',1,'intval');
		$id = I('id',0,'intval');//优惠券ID
		
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		
		$info = D('tbgoods')->order('id DESC')->where(array('id' => $id, 'role_id' => $_SESSION['ext_user']['id']))->find();
		if (empty($info)) $this->error('该优惠券不存在', U('Coupon/index'));
		if (!empty($info['keyword'])) $info['keyword'] = strtr($info['keyword'], array('@' => '、'));
		if (!empty($info['cid'])) $info['category'] = $this->category[$info['cid']];
		
		if (1 == $t){
			$field = 'count(case when state >= 1 then state end) as sq, count(case when state >= 3 then state end) as tg, count(case when state >= 4 then state end) as xd,etime';
			$data = D('tbusertrade')->where('g.id = '.$id)->field($field)->join('LEFT JOIN tfx_tbgoods AS g ON u.gid=g.id')->alias('u')->find();
			
			$this->assign('data', $data);
		} elseif (2 == $t){
			$tb_id = I('tb_id','','test_input');//淘宝账号
			$where = array('gid' => $id, 'role_id' => $_SESSION['ext_user']['id'], 'state' => array('in', '0,1,2,3'));
			if (!empty($tb_id)) $where['tb_id'] = array('LIKE', '%'.$tb_id.'%');
			
			$count = D('tbusertrade')->where($where)->count();
			$data = D('tbusertrade')->order('id DESC')->field('id,tb_id,state,addtime')->where($where)->select();
			
			$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
			$this->assign('tb_id', $tb_id);
			$this->assign('data', $data);
		} elseif (3 ==  $t){
			$search_type = I('search_type','','test_input');
			$search_content = I('search_content','','test_input');
			$where = array('gid' => $id, 'role_id' => $_SESSION['ext_user']['id'], 'state' => array('in', '4,5,6,7,8,9,10'));
			if (!empty($search_type) && !empty($search_content)) $where[$search_type] = array('LIKE', '%'.$search_content.'%');

			$count = D('tbusertrade')->where($where)->count();
			$data = D('tbusertrade')->order('id DESC')->field('id,tb_id,tb_item,real_price,subcondate,state,addtime')->where($where)->select();
			
			$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
			$this->assign('search_content', $search_content);
			$this->assign('search_type', $search_type);
			$this->assign('data', $data);
		}
		
		$this->assign('etime', strtotime($info['etime']));
        $this->assign('info', $info);
        $this->assign('t', $t);
		$this->display();
	}
	
	//活动进展详情
	public function orderInfo(){
		$id = I('id',0,'intval');//试客ID
		if (empty($id)) $this->error('该订单不存在', U('Coupon/index'));
		$info = D('tbusertrade')->where(array('id' => $id))->find();
		$goods = D('tbgoods')->where(array('id' => $info['gid'], 'role_id' => $_SESSION['ext_user']['id']))->find();
		if (strtotime($goods['etime']) <= time()) $this->error('该商品已过期', U('couponorder/ordermanage'));
		$goods['category'] = $this->category[$goods['cid']];

		$this->assign('info', $info);
		$this->assign('goods', $goods);
        $this->display();
	}
	
	/**
	 * 检测URL是否有效
	 * @param unknown $url
	 * @return number 0否 1是
	 */
	private function isUrl($url){
		if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url)) {
			return 0;
		} else {
			return 1;
		}
	}
	
   /**
	* 退款(活动结束后)款
	*/
	private function refund(){
		$where = array('role_id' => $_SESSION['ext_user']['id'], 'trash' => 0);
		$where['etime'] = array('ELT', date('Y-m-d H:i:s', time()));
		$where['confirm'] = array('EQ', 2);
		$info = D('tbgoods')->where($where)->field('id,kucun,real_price,red_price')->select();
		if (!empty($info)){
			foreach ($info as $k => $v){//转换数组结构为array(试用商品ID => array(总单数，每单基础价格))转
				$msg[$v['id']] = array('kucun' => $v['kucun'], 'money' => ($v['real_price'] + $v['red_price']));
			}unset($info);//去掉无用数据
			
			$key = array_keys($msg);//获取试用商品ID
			$info = D('tbusertrade')->where('state = 10 and gid in ('.implode(',', $key).')')->field('gid, count("id") as count')->group('gid')->select();//获取试用商品已完成的订单数
			if (!empty($info)) {
				foreach ($info as $k => $v){//整理数组结构为array(试用商品ID，已经完成订单总数)
					$param[$v['gid']] = $v['count'];
				}
			}
			
			foreach ($msg as $k => $v){//整理数组结构为array(试用商品ID，应退金额)
				$msg[$k] = ($v['kucun'] - (!empty($k) ? (!empty($param[$k]) ? $param[$k] : 0) : 0))*$v['money'];
			}
			
			$money = array_sum($msg);//应退总金额

			$msg = D('duoduo2010');
			$msg->startTrans (); // 开启事务
			$param = $msg->where('id = '.intval($_SESSION['ext_user']['id']))->setInc('fund', $money);//退款
			if (!empty($param)){//退款成功
				$param = D('tbgoods')->where('id in ('.implode(',', $key).')')->save(array('refund' => 1));
				if (false === $param) $msg->rollback();//出错回滚
			} else {//退款失败
				$msg->rollback();//出错回滚
			}
			$msg->commit();//事务完成
		}
	}
}
