<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/21
 * Time: 15:00
 */
namespace Supporter\Controller;
use Think\Controller;
use Common\Util\Util;

class CouponorderController extends BaseController {
	
	//优惠券订单管理
    public function ordermanage(){
    	$field = 'g.shopname, g.laiyuan, g.sid,
                  count(case when etime > "'.date('Y-m-d H:i:s', time()).'" and state = 1 then state end) AS `sq`,
                  count(case when etime > "'.date('Y-m-d H:i:s', time()).'" and state = 4 then state end) AS `xd`,
                  count(case when etime > "'.date('Y-m-d H:i:s', time()).'" and state = 7 then state end) AS `pj`,
                  count(case when etime > "'.date('Y-m-d H:i:s', time()).'" and state = 3 then state end) AS `dxd`,
                  count(case when etime > "'.date('Y-m-d H:i:s', time()).'" and state = 6 then state end) AS `dpj`,
                  count(case when state = 10 then state end) AS `fk`,
    	          count(case when etime <= "'.date('Y-m-d H:i:s', time()).'" and state < 10 then state end) AS `qx`';
    	$where = 'u.role_id = '.$_SESSION['ext_user']['id'].' and g.id is not null';
    	$data = D('tbusertrade')->where($where)->alias('u')->field($field)->join('LEFT JOIN tfx_tbgoods as g ON u.gid = g.id')->group('sid')->select();
    	if (!empty($data)){
    		foreach ($data as $key => $val){
    			$msg['sq'] += $val['sq'];
    			$msg['xd'] += $val['xd'];
    			$msg['pj'] += $val['pj'];
    			$msg['dxd'] += $val['dxd'];
    			$msg['dpj'] += $val['dpj'];
    			$msg['fk'] += $val['fk'];
    			$msg['qx'] += $val['qx'];
    		}
    	}
    	$this->assign('data', $data);
    	$this->assign('msg', $msg);
        $this->display();
	}
	
	//试客申请待审核
    public function order(){
    	
    	$page = I('get.page',1);
    	$page = $page < 1 ? 0 : $page - 1;
    	$page_size = I('get.pagesize',10);
    	
    	$qq = I('qq');
    	$sid = I('sid',0,'intval');
    	$state = I('state',0,'intval');
    	$tb_id = I('tb_id','','test_input');
    	
    	$where = array('u.role_id' => $_SESSION['ext_user']['id']);
    	if (!empty($sid)) $where['g.sid'] = $sid;
    	if (!empty($state)) $where['u.state'] = $state;
    	if (!empty($qq)) $where['i.qq'] = array('LIKE', '%'.intval($qq).'%');
    	if (!empty($tb_id)) $where['i.tb_id'] = array('LIKE', '%'.$tb_id.'%');
    	if (!empty($state)){//未完成,未取消
    		if (9 >= $state) $where['g.etime'] = array('GT', date('Y-m-d H:i:s', time()));
    	} else {//已取消
    		$where['g.etime'] = array('ELT', date('Y-m-d H:i:s', time()));
    		$where['u.state'] = array('LT', 10);
    	}
    	
    	$subQuery  = D('UsertradeView')->limit($page*$page_size,$page_size)->where($where)->order('id DESC')->buildSql();
    	$data =  D()->table($subQuery.' o')->field('o.*,i.qq AS qq,i.img2 AS img2,i.tb_id AS tb_id ')->join('LEFT JOIN tfx_tbinfo i ON i.uid = o.uid')->select();
    	$count = D('UsertradeView')->where($where)->count();
    	$shop  = D('tbsellerinfo')->field('id,shopname')->order('type asc')->where(array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2))->select();
    	$display = array(0 => 'ordercancel', 1 => 'orderapply', 3 => 'orderplace', 4 => 'ordernumber', 6 => 'orderwaiteva', 7 => 'ordereva', 10 => 'ordercomp');
    	
    	$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
    	$this->assign('tb_id', $tb_id);
    	$this->assign('state', $state);
    	$this->assign('shop', $shop);
    	$this->assign('data', $data);
    	$this->assign('sid', $sid);
    	$this->assign('qq', $qq);
    	$this->display($display[$state]);
	}
	
	//订单号待审核
    public function ordernumber(){
       $this->display();
	}
	//评价截图待确认
    public function ordereva(){
       $this->display();
	}
	//待试客下单
	public function orderplace(){
       $this->display();
	}
	//待试客评价
	public function orderwaiteva(){
       $this->display();
	}
	//已取消订单
	public function ordercancel(){
       $this->display();
	}
	//已完成订单
	public function ordercomp(){
       $this->display();
	}
	
	//审核通过
	public function agreed(){
		$ids = I('ids');//信息ID集合
		$id = I('id',0,'intval');//信息ID
		$state = I('state',0,'intval');//状态标识
		$type = array(1 => 3, 4 => 6, 7 => 9);
		if (empty($type[$state])) $this->error('非法操作');
		if (empty($ids) && empty($id)) $this->error('请勾选您要审核的订单');
		$data = $this->expired(!empty($id) ? $id : $ids,$state);//商品过期检测
		
		if (9 == $type[$state]){//返款
			if (!empty($data)) {
				foreach ($data as $key => $val){
					$money += $val['real_price'] + $val['red_price'];//总金额
					$data[$key]['unit_price'] = $val['real_price'] + $val['red_price'];
					unset($data[$key]['real_price']);
					unset($data[$key]['red_price']);
				}
				$msg = $this->pay($money, $data);//支付事件
				if (1 != $msg) $this->error('操作失败');
			}
		} else {//审核
			if (IS_POST){
				$tbusertrade = D('tbusertrade');
				if (!$tbusertrade->autoCheckToken($_POST)) $this->error('令牌验证错误');//防止重复提交
				$where = array('id' => array('IN', implode(',', $ids)), 'state' => $state, 'role_id' => $_SESSION['ext_user']['id']);
				$msg = D('tbusertrade')->where($where)->save(array('state' => $type[$state]));
			} else {
				$msg = D('tbusertrade')->where(array('id' => $id, 'state' => $state, 'role_id' => $_SESSION['ext_user']['id']))->save(array('state' => $type[$state]));
			}
		}
        
        if ($msg){
        	$this->success('操作成功');
        } else {
        	$this->error('操作失败');
        }
	}
	
	/**
	 * 支付事件
	 * @param unknown $money 总金额
	 * @param unknown $data  array(0 => array([id] => 订单ID [gid] => 商品ID [uid] => 试客ID [unit_price] => 返款金额));
	 * @return number
	 */
	private function pay($money, $data){
		if (!empty($data)){
			$uid = intval($_SESSION['ext_user']['id']);//用户ID
			
			$msg = D('duoduo2010');
			$msg->startTrans (); // 开启事务
			$info = D('duoduo2010')->where ('id = '.$uid )->field ('deposit')->find();//查询押金余额
			if ($info['deposit'] >= $money){//余额充足
				$val = D('duoduo2010')->where('id = '.$uid)->setDec('deposit', $money);//扣除押金
				if (!empty($val)){					
					$val = $this->cash($data);//用户返现(1为成功其他值为失败)
					if (1 != $val) $msg->rollback();//返现失败则回滚
				} else {
					$val = 2;//扣款失败
					$msg->rollback();//出错回滚
				}
			} else {//余额不足
				$val = 10;
			}
			$msg->commit();//事务完成
			return $val;
		} else {
			return 4;
		}
	}
	
	/**
	 * 给用户返现事件(订单完成后)
	 * @param array(0 => array([id] => 订单ID [gid] => 商品ID [uid] => 试客ID [unit_price] => 返款金额));
	 */
	private function cash($data){
		if (!empty($data)){
			foreach ($data as $k => $v){
				$unit_price = intval($v['unit_price']);//商品价格
				$val = D('tbrecord')->where(array('uid' => $v['uid'], 'gid' => $v['gid'], 'statu' => 1, 'msg' => 2))->getField('id');//查看用户是否已经返款
				if (empty($val)){//没有返款则返款
					$where = array('uid' => $v['uid'], 'gid' => $v['gid'], 'statu' => 1, 'msg' => 2, 'money' => $unit_price,'detailtime' => date("Y-m-d H:i:s", time()));
					$val = D('tbrecord')->add($where);//添加记录
					if (!empty($val)){
						$val = D('tbusertrade')->where(array('id' => $v['id']))->setField('state', 10);//完成订单
						if (false === $val) return 6;//执行出错
						$money = D('user')->where(array('id' => $v['uid']))->getField('F_money_yi');//获取用户预提金
						if ($money > 0){//已提取过预提金
							if ($unit_price > intval($money)){//预提金不够,则设已提现金额为0将余额返还
								$val = D('user')->where('id = '.$v['uid'])->save(array('F_money_yi' => 0, 'txmoney' => array('exp', 'txmoney+'.($unit_price-$money))));
								if (false === $val) return 8;//执行出错
							} else {//预提金足够则直接减少预提金则
								$val = D('user')->where('id = '.$v['uid'])->setDec('F_money_yi', $unit_price);//减少已提现金额
								if (false === $val) return 7;//执行出错
							}
						} else {
							$val = D('user')->where(array('id' => $v['uid']))->setInc('txmoney', $unit_price);//给用户返款
							if (false === $val) return 6;//执行出错
						}
						$val = $this->first($v['uid'], $v['gid']);//给新手奖励
						if (1 != $val) return $val;//执行出错
					} else {
						return 5;//用户返款失败
					}
				}
			}
			return 1;//用户返款成功户
		}
	}
	
	/**
	 * 新手第一次完成试用返现2元现
	 * @param int uid 用户ID
	 * @param int gid  试用商品ID
	 * @param int money 用户奖励金额
	 */
	private function first($uid,$gid,$money=2){
		if (!empty($uid) && !empty($gid)){
			$count = D('tbrecord')->where(array('uid' => $uid, 'statu' => 1))->count();
			if (1 == $count){
				$msg = D('tbrecord')->add(array('uid' => $uid, 'gid' => $gid, 'money' => $money, 'detailtime' => date("Y-m-d H:i:s", time()), 'statu' => 6, 'msg' => 2));//添加记录
				if (false === $msg) return 9;//执行出错
				$msg = D('user')->where('id = '.$uid)->setInc('txmoney', $money);//奖励新手用户
				if (false === $msg) return 9;//执行出错
			}
			
			$msg = $this->superior($uid,$count,$gid);//上家返利
			if (1 != $msg){
				return $msg;//执行出错
			} else {
				return 1;//执行成功
			}
		} else {
			return 11;//程序执行出错序
		}
	}
	
	/**
	 * 上家返(若有上家则仅第一、五次返利)
	 * @param int uid 用户ID
	 * @param int num 第n次现
	 * @param int gid 试用商品ID
	 */
	private function superior($uid,$num,$gid){
		if (1 == $num || 5 == $num){
			$fid = M('user')->where('id='.$uid)->getField('uid');//上家用户ID
			if (!empty($fid)){//有上家,返利
				$money = (1 == $num) ? 2:5;
				$msg = D('tbrecord')->add(array('uid' => $fid, 'xid' => $uid, 'num' => $num, 'money' => $money, 'gid' => $gid, 'detailtime' => date("Y-m-d H:i:s", time()), 'statu' => 4, 'msg' => 2));
				if (false === $msg) return 12;//执行出错
				$msg = D('user')->where('id = '.$fid)->setInc('txmoney', $money);//试用返款给用户增加余额(上家)
				if (false === $msg) return 12;//执行出错
				
				if (1 == $num){//上上家，有则返利
					$ffid = M('user')->where('id='.$fid)->getField('uid');//上上家用户ID
					if (!empty($ffid)){
						$msg = D('tbrecord')->add(array('uid' => $ffid, 'xid' => $fid, 'gid' => $gid, 'money' => 1, 'detailtime' => date("Y-m-d H:i:s", time()), 'statu' => 5, 'msg' => 2));
						if (false === $msg) return 13;//执行出错
						$msg = D('user')->where('id = '.$ffid)->setInc('txmoney');//试用返款给用户增加余额(上上家)
						if (false === $msg) return 13;//执行出错
					}
				}
			}
		}
		return 1;//执行成功
	}
	
	//驳回
	public function refuse(){
		$id = I('id',0,'intval');//信息ID
		$state = I('state',0,'intval');//状态标识
		$type = array(1 => 2, 4 => 5, 7 => 8);
		if (empty($type[$state])) $this->error('非法操作');
		
		$msg = D('tbusertrade')->where(array('id' => $id, 'state' => $state, 'role_id' => $_SESSION['ext_user']['id']))->save(array('state' => $type[$state]));
    	if ($msg){
   			$this->success('操作成功');
   		} else {
   			$this->error('操作失败');	
   	    }
    }
    
    //驳回(ajax方式)
    public function ajaxRefuse(){
    	$id = I('id',0,'intval');//信息ID
    	$note = I('note','','test_input');//驳回原因
    	$state = I('state',0,'intval');//状态标识
    	$type = array(1 => 2, 4 => 5, 7 => 8);
    	if (empty($type[$state])) $this->ajaxReturn(array('msg' => 2, 'message' => '非法操作'));
    	
    	$msg = D('tbusertrade')->where(array('id' => $id, 'state' => $state, 'role_id' => $_SESSION['ext_user']['id']))->save(array('state' => $type[$state], 'note' => $note));
    	if ($msg){
    		$this->ajaxReturn(array('msg' => 1, 'message' => '操作成功'));
    	} else {
    		$this->ajaxReturn(array('msg' => 2, 'message' => '非法操作'));
    	}
    }
    
    //检测商品是否过期
    private function expired($id,$state){
    	if (is_integer($id)){
    		$where = array('u.id' => $id, 'u.state' => $state, 'g.etime' => array('GT', date('Y-m-d H:i:s', time())));
    	} elseif (is_array($id)){
    		$where = array('u.id' => array('IN', implode(',', $id)), 'u.state' => $state, 'g.etime' => array('GT', date('Y-m-d H:i:s', time())));
    	}
    	
    	$data = D('tbusertrade')->field('u.id,u.gid,u.uid,g.real_price,g.red_price')->where($where)->join('LEFT JOIN tfx_tbgoods as g ON u.gid = g.id')->alias('u')->select();
    	if (count($data) != count($id)){
    		$this->error('非法操作', U('couponorder/ordermanage'));
    	} else {
    		return $data;
    	}
    }
}
?>