<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Common\Util\Redis;
use Think\Controller;
use Common\Util\Util;

class ShopupController extends Controller {

	public function index()
	{
		//店铺拆分
		/*$shopdata = M('user')->where('shopname is not null')->select();

		foreach($shopdata as $val)
		{
		$status = $val['status']>0?2:$val['status'];
		$data['uid'] =  $val['uid'];
		$data['shopname'] =  $val['shopname'];
		$data['url'] =  $val['url'];
		$data['wangwang'] =  $val['wangwang'];
		$data['status'] =  $status;
		$data['addtime'] =  $val['addtime'];
		$data['suretime'] =  $val['addtime'];
		$shopid = M('shop')->add($data);
		$iddata['shop_id'] =  $shopid;
		M('task')->where('user_id='.$data['uid'])->save($iddata);
		M('product')->where('user_id='.$data['uid'])->save($iddata);
		}*/

		//商家财务
		/*$shopdata = M('user')->where('shopname is not null')->select();
		 foreach($shopdata as $key=>$val)
		 {
			$query = 'SELECT (sum(IFNULL(if(actual_price,actual_price,price),0) + IFNULL(if(empty_cost,empty_cost,redbag),0))) as total FROM erp_task WHERE xiajia<>2 and successtime is null and tb_item is not null and ';
			$query .= 'gid in(SELECT id FROM erp_product WHERE (status=0 or status=3) and user_id='.$val['uid'].')';
			$total_price = M()->Query($query);
			$total_price = $total_price[0]['total'];//总本金

			$query = 'SELECT if(actual_price,actual_price,price)as price FROM erp_task WHERE xiajia<>2 and successtime is null and tb_item is not null and ';
			$query .= 'gid in(SELECT id FROM erp_product WHERE (status=0 or status=3) and user_id='.$val['uid'].')';
			$data = M()->Query($query);
			$fuwufei = 0;
			foreach($data as $v)
			{
				if(empty($v['price'])) continue;
				$fuwufei += cost($v['price']);//总服务费
			}
			$total = $total_price+$fuwufei;

			$query = 'SELECT (sum(IFNULL(if(actual_price,actual_price,price),0) + IFNULL(if(empty_cost,empty_cost,redbag),0))) as total FROM erp_task WHERE xiajia<>2 and successtime is null and tb_item is null and ';
			$query .= 'gid in(SELECT id FROM erp_product WHERE (status=0 or status=3) and user_id='.$val['uid'].')';

			$total_yufu = M()->Query($query);
			$total_yufu = $total_yufu[0]['total'];//预付金

			$query = 'SELECT  price FROM erp_task WHERE xiajia<>2 and successtime is null and tb_item is null and ';
			$query .= 'gid in(SELECT id FROM erp_product WHERE (status=0 or status=3) and user_id='.$val['uid'].')';
			$data = M()->Query($query);
			$yu_fuwufei = 0;
			foreach($data as $vv)
			{
				if(empty($vv['price'])) continue;
				$yu_fuwufei += cost($vv['price']);//预付金服务费
			}
			$total_yufu = $total_yufu+$yu_fuwufei;//总预付金

			$query = 'SELECT sum(money)as money FROM erp_recharge WHERE msg=1 and status=0 and uid='.$val['id'];
			$total_chon = M()->Query($query);
			$total_chon = $total_chon[0]['money'];//总充值额

			$query = 'SELECT sum(money)as money FROM erp_recharge WHERE status=1 and uid='.$val['id'];
			$total_kou = M()->Query($query);
			$total_kou = $total_kou[0]['money'];//总扣款额

			$query = 'SELECT sum(money)as money FROM erp_cash WHERE (status=1 or status=0) and uid='.$val['uid'];
			$total_tixian = M()->Query($query);
			$total_tixian = $total_tixian[0]['money'];//提现额


			$arr[$key]['id'] = $val['uid'];
			$arr[$key]['total'] = $total;
			$arr[$key]['fuwufei'] = $fuwufei;
			$arr[$key]['total_yufu'] = $total_yufu;
			$arr[$key]['total_chon'] = $total_chon;
			$arr[$key]['yu'] = $total_chon-$total-$total_tixian-$total_kou;
			$yu = empty($arr[$key]['yu'])?0:$arr[$key]['yu'];

			$total_yufu = empty($total_yufu)?0:$total_yufu;
			$updata = array(
			'money'=>$total_chon-$total-$total_tixian-$total_kou,
			'yufujin'=>$total_yufu
			);
			//break;
			M('user')->where('id='.$val['id'])->save($updata);
			}
		dump($arr);*/
		//刷单员
		$userdata = M('account')->where('role=2')->select();
		foreach($userdata as $key=>$val)
		{
			$query = 'SELECT (sum(IFNULL(if(actual_price,actual_price,price),0) + IFNULL(redbag,0) + IFNULL(commision,0))) as total FROM erp_task WHERE xiajia<>2 and repay<>1 and tb_item is not null and ';
			$query .= ' addtime >= 1526400000 and gid in(SELECT id FROM erp_product WHERE status=0 or status=3) and uid='.$val['id'];
			//echo $query;exit;
			$total_price = M()->Query($query);
			$total_price = $total_price[0]['total'];//总支出
			$all += $total_price;

			$query = 'SELECT sum(money) as sum FROM erp_application WHERE role_id=2 and status=3 and user_id='.$val['id'];
			//echo $query;exit;
			$total_get = M()->Query($query);
			$total_get = $total_get[0]['sum'];//总获得
			$total_get = empty($total_get)?0:$total_get;
			dump($total_get);
			
			$data[$val['id']] = $total_get-$total_price;
			$updata['money'] = $data[$val['id']];
			
			M('account')->where('id='.$val['id'])->save($updata);
		}
		echo $all;
		dump($data);
		
		/*//站长
		$userdata = M('account')->where('role=3')->select();
		foreach($userdata as $key=>$val)
		{
			$query = 'SELECT sum(money) as sum FROM erp_application WHERE role_id=2 and status=3 and z_id='.$val['id'];
			$total_price = M()->Query($query);
			$total_price = $total_price[0]['sum'];//总支出
			//$all += $total_price;
	 		dump($total_price);echo '+++';
			$query = 'SELECT sum(money) as sum FROM erp_application WHERE status=3 and user_id='.$val['id'];
			//echo $query;exit;
			$total_get = M()->Query($query);
			$total_get = $total_get[0]['sum'];//总获得
			$total_get = empty($total_get)?0:$total_get;
			dump($total_get);
			
			$data[$val['id']] = $total_get-$total_price;
			$updata['money'] = $data[$val['id']];
			
			M('account')->where('id='.$val['id'])->save($updata);
		}
		echo $all;
		dump($data);*/
		
		
	}

}
?>