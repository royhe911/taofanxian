<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;
use Common\Util\Util;

class BusinessController extends BaseController {

	public function index(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
	    if(IS_GET){
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            $where="a.id is not null";
            $shopname=trim(I('get.shopname'));   //店铺名
            $wangwang=trim(I('get.wangwang'));     //旺旺名
            $phone   =trim(I('get.phone'));
            if( $wangwang )     $where .= " and a.wangwang like '%{$wangwang}%'";
            if( $phone )        $where .= " and a.iphone='$phone'";
            if( $shopname )     $where .= " and a.shopname like '%{$shopname}%'";

            if (6 == $_SESSION['user']['role']) $where .= " and a.tid={$_SESSION['user']['id']}";//若用户为导师则只查看其指导的用户
            $count = D('user a')->where($where)->join('left join erp_account b on a.tid=b.id')->count();

            $data  = D('user a')
                ->field('a.*,b.info')
                ->where($where)
                ->join('left join erp_account b on a.tid=b.id')
                ->order('a.addtime DESC')
                ->limit($page*$page_size,$page_size)
                ->select();

            //浮点型 后面是0不显示
            foreach ($data as $key => $value) {
                $data[$key]['money']=floatval($value['money']);
            }
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('shopname', $shopname);
            $this->assign('wangwang', $wangwang);
            $this->assign('phone', $phone);
            $this->assign('count', $count);
            $this->assign('data', $data);
            $this->display();
        }

	}

	//状态修改
	public function state(){

		if (IS_AJAX){
			$id  = I('post.id',0,'intval');//信息ID
			$msg = I('post.msg',0,'intval');//修改值
			if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));

			$where = array('id' => $id);
			$return = D('user')->where($where)->save(array('msg' => $msg));
			if ($return){
				$this->ajaxReturn(array('msg' => 1, 'info' => $msg));
			} else {
				$this->ajaxReturn(array('msg' => 3, 'info' => '操作失败'));
			}
		}
	}

	//删除
	public function del(){

		if (IS_AJAX){
			$id = I('post.id',0,'intval');//信息ID
            $type=I('post.type',0,'int');
            if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
            if($type == 0){
                //type为0表示冻结账户操作
                $msg = D('user')->where('id = '.$id)->setField('status',0);
                if ($msg){
                    $this->ajaxReturn(array('msg' => 1, 'info' => '冻结成功'));
                } else {
                    $this->ajaxReturn(array('msg' => 3, 'info' => '冻结失败'));
                }
            }else{
                //表示解冻操作
                $msg = D('user')->where('id = '.$id)->setField('status',1);
                if ($msg){
                    $this->ajaxReturn(array('msg' => 1, 'info' => '解冻成功'));
                } else {
                    $this->ajaxReturn(array('msg' => 3, 'info' => '解冻失败'));
                }
            }





		}
	}

	//批量删除
	public function delAll(){

		if (IS_AJAX){
			$ids = I('post.arr');
			if (!empty($ids)) {
				$msg = D('user')->where(array('id' => array('IN', $this->paramId($ids))))->delete();
				if ($msg){
					$this->ajaxReturn(array('msg' => 1, 'info' => '删除成功'));
				} else {
					$this->ajaxReturn(array('msg' => 3, 'info' => '删除失败'));
				}
			} else {
				$this->ajaxReturn(array('msg' => 2, 'info' => '请勾选数据'));
			}
		}
	}

	//ID过滤
	private function paramId($ids){
		return implode(',', array_map(array($this,'idFilter'),$ids));
	}

	private function idFilter($key){
		return intval($key);
	}

	public function tasklist(){
        $page      = I('get.page',1);
        $page      = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',10);
        $starttime = I('get.time');
        $endtime   = I('get.endtime');

	    $id=intval(I('get.id'));
	    $where="a.user_id={$id} and a.status = 3";
        $where_money='b.user_id='.$id.' and a.tb_item is not null';  //计算全部任务的where条件
        $where_count='user_id='.$id.' and tb_item is not null';  //计算全部任务的where条件

        if(!empty($starttime)) {
            $starttime=strtotime($starttime. "00:00:00");
            $where .= " and a.addtime >= {$starttime}";
            $where_money .= " and a.addtime >= {$starttime}";
            $where_count .= " and addtime >= {$starttime}";
        }
        if(!empty($endtime))   {
            $endtime=strtotime($endtime. "23:59:59");
            $where .= " and a.addtime <= {$endtime}";
            $where_money .=  " and a.addtime <= {$endtime}";
            $where_count .=  " and addtime <= {$endtime}";
        }

        $count_a=D('product a')
            ->field('a.*,b.shopname')
            ->join('left join erp_shop b on a.shop_id=b.id')
            ->where($where)
            ->order('a.addtime desc')
            ->count();
        $data=D('product a')
            ->field('a.*,b.shopname')
            ->join('left join erp_shop b on a.shop_id=b.id')
            ->where($where)
            ->limit($page*$page_size,$page_size)
            ->order('a.addtime desc')
            ->select();


        foreach ($data as $key =>$value){
            //预估费用
            $data[$key]['goods_totalprice']=f_round(floatval($value['goods_totalprice']));//产品预估总本金
            $data[$key]['goods_totalcost']=f_round(floatval($value['goods_totalcost']));  //产品预估费用
            $data[$key]['goods_order'] = f_round($value['empty_cost'] * $value['goods_totalnum']);  //产品预估其他费用
            $data[$key]['goods_total'] =f_round( $data[$key]['goods_totalprice']+$data[$key]['goods_totalcost']+$data[$key]['goods_order']);   //产品预估总费用

            //已完成费用
            $where="gid={$value['id']} and tb_item is not null";

            $task_info=D('Task')->where($where)->select();
            $count=count($task_info);
            $data[$key]['count']=$count;   //已完成份数

            $success_price   = 0;//完成金额
            $actual_cost     = 0;//已扣服务费
            $order           = 0;//其他
            $total           = 0;//已完成的总费用
            $success_ben     = 0; //已完成任务的原始本金
            foreach ( $task_info as $k =>$v){
                $success_price += f_round($v['actual_price']);
                $actual_cost   += f_round($v['cost']);
                $success_ben   += f_round($v['price']);
                if(floatval($v['empty_cost']) >0){
                    $order += $v['empty_cost'];
                }else{
                    $order += $v['redbag'];
                }
            }
            $data[$key]['success_price']=$success_price;
            $data[$key]['actual_cost']=$actual_cost;
            $data[$key]['order']=$order;
            $data[$key]['total']=$data[$key]['success_price'] + $data[$key]['actual_cost'] + $data[$key]['order'];
            //未完成任务统计
            $data[$key]['error_num']=$data[$key]['goods_totalnum'] - $count;  //未完成数量
            $data[$key]['error_price']=f_round($data[$key]['goods_totalprice'] - $success_ben);     //未完成金额  总金额-已完成的原金额
            $data[$key]['miid']=urlencode(encrypt($data[$key]['id'],'E'));

        }
        //计算总费用


        $field='sum(IFNULL(a.actual_price,0)) as totalprice,sum(a.cost)as cost,sum(IFNULL(if(a.empty_cost>0,a.empty_cost,a.redbag),0)) as order_cost';
        $task_money=D('task a')
            ->join('left join erp_product b on a.gid=b.id')
            ->join('left join erp_shop c on a.shop_id=c.id')
            ->join('left join erp_account d on a.uid=d.id')
            ->join('left join erp_user e on a.user_id=e.uid')
            ->field($field)
            ->where($where_money)
            ->select();
            if ($_SESSION['user']['id'] == 1) {
                // echo M()->getLastSql();exit;
            }
        $total_cost=f_round($task_money[0]['totalprice']) + f_round($task_money[0]['cost']) + f_round($task_money[0]['order_cost']);  //总费用
        $task_count=D('Task')->where($where_count)->count();

        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count_a));
        if ( $starttime )  $this->assign('starttime',date("Y-m-d",$starttime));
        if ( $endtime )    $this->assign('endtime',date("Y-m-d",$endtime));
        $this->assign('data',$data);
        $this->assign('count_a',$task_count);
        $this->assign('total_cost',$total_cost);
		$this->display();
	}
	//添加和修改微信备注
	public function wechat(){
	    if(IS_AJAX){
            $id = I('post.id',0,'intval');//信息ID
            $type=I('post.type',0,'intval');
            $remarks=trim(I('post.remarks'));
            if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));

            if($type == 0){
                //0是添加
                 $res=D('User')->where('id='.$id)->setField('remarks',$remarks);
                if($res){
                    $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
                }else{
                    $this->ajaxReturn(array('msg' => 0, 'info' => '操作失败'));
                }
            }
        }
	}
	
	//店铺审核
	public function shopcheck(){
		if (IS_AJAX){
			$id  = I('post.id',0,'intval');//信息ID
			$msg = I('post.type',0,'intval');//修改值
			if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
			$where['id'] = $id;
			$uid = M('shop')->where($where)->getField('uid');
			if($msg==1)
			{
				$data['reject'] = I('post.reject','');//驳回原因
				$status = M('user')->where('uid='.$uid)->getField('msg');
				if($status!=1)
				{
					D('user')->where('uid='.$uid)->save(array('msg' => 2));
				}
			}else 
			{
				D('user')->where('uid='.$uid)->save(array('msg' => 1));
			}
			$data['status'] = $msg;
			$return = D('shop')->where($where)->save($data);
			if ($return){
				
				$this->ajaxReturn(array('msg' => 1, 'info' => $msg));
			} else {
				$this->ajaxReturn(array('msg' => 3, 'info' => '操作失败'));
			}
		}else{
            //判断当前权限
            if( check_action() === false) {
                $this->error('无此权限');
                return false;
            }

			$roleId    = intval($_SESSION['user']['role']);
            $user_id   = intval($_SESSION['user']['id']);
            $page      = I('get.page',1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',15);
            $main_shop = trim(I('get.main_shop'));
            $shopname  = trim(I('get.shopname'));
//            if( !empty($main_shop) && !empty($shopname)){
//                unset($shopname);
//            }
            $where="a.id is not null";
            //权限判定
            if( $roleId == 1)
            {
                if(!empty($main_shop)) $where .= " and b.shopname like '%$main_shop%'";
                if(!empty($shopname))  $where .= " and a.shopname like '%$shopname%'";
            	$count = D('shop a')->join('left join erp_user b on a.uid=b.uid')->where($where)->count();
	            $info = D('shop a')->field('a.*,b.shopname as b_shopname')->join('left join erp_user b on a.uid=b.uid')->where($where)->limit($page*$page_size,$page_size)->order('a.status,a.addtime desc')->select();
            }elseif ( $roleId == 6){
                //业务员
                //$where = "uid=".$user_id;
                $query = 'SELECT a.*,b.shopname as b_shopname FROM erp_shop a LEFT JOIN erp_user b on a.uid=b.uid where a.uid in(select uid from erp_user where tid='.$user_id.')';
                if(!empty($main_shop)) $query .= " and b.shopname like '%$main_shop%'";
                if(!empty($shopname))  $query .= " and a.shopname like '%$shopname%'";
    			$info = M()->Query($query);
    			$count = count($info);
    			
            	$query = 'SELECT a.*,b.shopname as b_shopname FROM erp_shop a LEFT JOIN erp_user b on a.uid=b.uid where a.uid in(select uid from erp_user where tid='.$user_id.')';
                if(!empty($main_shop)) $query .= " and b.shopname like '%$main_shop%'";
                if(!empty($shopname))  $query .= " and a.shopname like '%$shopname%'";
            	$query .='order by a.status , a.addtime desc limit '.$page*$page_size.', '.$page_size;
    			$info = M()->Query($query);

                //$count = D('shop')->where($where)->count();
                //$info = D('shop')->where($where)->limit($page*$page_size,$page_size)->order('status')->select();
            }
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count',$count);
            $this->assign('main_shop',$main_shop);
            $this->assign('shopname',$shopname);
			$this->assign('data', $info);
			$this->display();
		}
			
	}


	//设置透支额度
    public function set_overdraft(){
	    $id=I('post.id',0,'intval');   //商家id  user表的id
	    $overdraft_type=I('post.overdraft',0,'intval');  //透支额度
        if(!in_array($overdraft_type,array(0,1,2,3,4,5))) $this->ajaxReturn(array('msg'=>0,'info'=>'参数不对'));
        // 1 8000 2 15000 3 30000 4 50000 5 100000   0为清零
        switch ($overdraft_type)
        {
            case 0:
                $overdraft = 0;
                break;
            case 1:
                $overdraft = 8000;
                break;
            case 2:
                $overdraft = 15000;
                break;
            case 3:
                $overdraft = 30000;
                break;
            case 4:
                $overdraft = 50000;
                break;
            case 5:
                $overdraft = 100000;
                break;
        }
	    if( empty($id) || $id <0)  $this->ajaxReturn(array('msg'=>0,'info'=>'商家信息不存在'));
	    $user_info=D('user')->where('id='.$id)->find();
	    if($user_info['msg'] !=1 || $user_info['status'] !=1) $this->ajaxReturn(array('msg'=>0,'info'=>'商家状态异常'));
//	    if($overdraft <0) $this->ajaxReturn(array('msg'=>0,'info'=>'透支余额设置错误'));

	    //验证通过
        if( $overdraft == 0) {
            $array=array(
                'credit_status'=>0,
                'credit_money' =>$overdraft,
            );
            //清除透支
            $set_overdraft=D('user')->where('id='.$id)->setField($array);
            if(!$set_overdraft) $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误'));
            $this->ajaxReturn(array('msg'=>1,'info'=>'设置成功'));
        }
        //修改状态为审核
        if($_SESSION['user']['role'] == 1){
            $data=array(
                'credit_status'=>2,  //1为待审核
                'credit_money' =>$overdraft,
            );
            $info='设置成功';
        }elseif($_SESSION['user']['role'] == 6){
            $data=array(
                'credit_status'=>1,  //1为待审核
                'credit_money' =>$overdraft,
            );
            $info='设置成功，待审核';
        }

        //增加透支额度
        $set_overdraft=D('user')->where('id='.$id)->setField($data);
        if(!$set_overdraft) $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误'));
        $this->ajaxReturn(array('msg'=>1,'info'=>$info));
    }
    //透支列表
    public function overdraft(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        $page      = I('get.page',1);
        $page      = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',15);
        $count=D('user')->where('credit_status > 0')->count();
        $data=D('user')->where('credit_status > 0')->limit($page*$page_size,$page_size)->select();
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->assign('data',$data);
        $this->assign('count',$count);
        $this->display();
    }

    //透支审核状态
    public function check_overdraft(){
        $id=I('post.id',0,'intval');   //商家id  user表的id
        if( empty($id) || $id <0)  $this->ajaxReturn(array('msg'=>0,'info'=>'商家信息不存在'));
        //查询商家信息是否正常
        $user_info=D('user')->where('id='.$id)->find();
        if($user_info['msg'] !=1 || $user_info['status'] !=1) $this->ajaxReturn(array('msg'=>0,'info'=>'商家状态异常'));

        $type=I('post.type',0,'intval');   //状态值
        if( empty($type)) $this->ajaxReturn(array('msg'=>0,'info'=>'状态错误'));

        if($type == 1){
            //通过
            $res=D('user')->where('id='.$id)->setField('credit_status',2);
            if( !$res ) $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误'));
            $this->ajaxReturn(array('msg'=>1,'info'=>'审核通过'));

        }elseif ($type ==2){
            //拒绝
            $reason=trim(I('post.reason'));
            if(empty($reason))   $this->ajaxReturn(array('msg'=>0,'info'=>'拒绝理由必须填写'));
            $array=array(
                'credit_status'=>3,
                'reason'=>$reason,
            );
            $res=D('user')->where('id='.$id)->setField($array);
            if(!$res) $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误'));
            $this->ajaxReturn(array('msg'=>1,'info'=>'已提交'));
        }
    }

public function outstanding(){
    //判断当前权限
    if( check_action() === false) {
        $this->error('无此权限');
        return false;
    }

            $shopname  = trim(I('get.shopname'));   //店铺名
            $wangwang  = trim(I('get.wangwang'));     //旺旺名
            $phone     = trim(I('get.phone'));        //手机号
            $status    = I('get.status',0,'int');
            $where     = "a.id is not null";
            $page      = I('get.page',1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);

            if (6 == $_SESSION['user']['role']) $where .= " and a.tid={$_SESSION['user']['id']}";//若用户为导师则只查看其指导的用户
            if($status != 0 && $status != 1) $status =0;
            if( $wangwang )     $where .= " and a.wangwang like '%{$wangwang}%'";
            if( $phone )        $where .= " and a.iphone='$phone'";
            if( $shopname )     $where .= " and a.shopname like '%{$shopname}%'";

            if($status == 0) {
                $where .= ' and a.money < 0';
                $order ='a.money';
            }elseif ($status == 1){
                $where .= ' and a.money > 0';
                $order ='a.money desc';
            }
            $count = D('user a')->where($where)->count();
            $data = D('user a')
                ->field('a.*,a.money as a,b.realname as yw_realname,b.info as yw_info')
                ->join('left join erp_account b on a.tid=b.id')
                ->where($where)
                ->order($order)
                ->limit($page * $page_size, $page_size)
                ->select();
            $totalmoney=D('user a')->field('sum(a.money - a.yufujin) as totalmoney ')->where($where)->select();
            $totalmoney=abs($totalmoney[0]['totalmoney']);

            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('shopname', $shopname);
            $this->assign('totalmoney', $totalmoney);
            $this->assign('status', $status);
            $this->assign('wangwang', $wangwang);
            $this->assign('phone', $phone);
            $this->assign('data', $data);
            $this->display();

	} 
	//添加和修改微信备注
	public function withhold(){
		$u = $_GET['u'];
		if($u!='user'){
			return;
		}
	    if(IS_GET){
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);

            if (6 == $_SESSION['user']['role']) $where = "tid={$_SESSION['user']['id']}";//若用户为导师则只查看其指导的用户
            $count = D('user')->where($where)->count();
            $data  = D('user')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->select();

            //浮点型 后面是0不显示
            foreach ($data as $key => $value) {
                $data[$key]['money']=floatval($value['money']);
            }
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('data', $data);
            $this->display();
        }
        else{
            $shopname=trim(I('post.shopname'));   //店铺名
            $wangwang=trim(I('post.wangwang'));     //旺旺名
            $phone   =trim(I('post.phone'));        //手机号
            $where="id is not null";
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);

            if (6 == $_SESSION['user']['role']) $where .= " and tid={$_SESSION['user']['id']}";//若用户为导师则只查看其指导的用户

            if( $wangwang )     $where .= " and wangwang like '%{$wangwang}'";
            if( $phone )        $where .= " and iphone='$phone'";
            if( $shopname )     $where .= " and shopname like '%{$shopname}%'";


            $count = D('user')->where($where)->count();
            $data  = D('user')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->select();

            //浮点型 后面是0不显示
            foreach ($data as $key => $value) {
                $data[$key]['money']=floatval($value['money']);
            }
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('shopname', $shopname);
            $this->assign('wangwang', $wangwang);
            $this->assign('phone', $phone);
            $this->assign('data', $data);
            $this->display();


        }
		
	}
	
	public function cut_money()
	{
		if(IS_POST)
		{
			$money = floatval(I('post.money'));
			$reason = I('post.info');
			$uid = I('post.id');	
			$id = M('user')->where('uid='.$uid)->getField('id');
			if(empty($id))  echo "<script>window.history.go(-1);</script>";
			
			//可用提现
	        $data=array(
	                'uid'=>$id,
	                'money'=>$money,
	                'addtime'=>time(),
	                'status'=>1,
	                'reason'=>$reason  //
	            );
	        D('recharge')->add($data);
	        D('user')->where(array('uid'=>$uid))->setDec('money',$money);
	        echo "<script>window.history.go(-1);</script>";
		}
		if(IS_GET)
		{
			$id = I('get.id');
			$this->assign('id', $id);
			$this->display();
		}
	}

	//欠款商家下载
    public function down_shop(){
	    if($_SESSION['user']['role'] != 1 &&  $_SESSION['user']['role'] != 4 && $_SESSION['user']['role'] != 6){
	        $this->error('无此权限');
	        exit;
        }
        $shopname  =  trim(I('get.shopname'));
        $phone     =  trim(I('get.phone'));
        $wangwang  =  trim(I('get.wangwang'));
        $status  =I('get.status',0,'int');
        $where = " a.id is not null";
        if (6 == $_SESSION['user']['role'])  $where .= " and a.tid={$_SESSION['user']['id']}";//若用户为导师则只查看其指导的用户
        if( $wangwang )     $where .= " and a.wangwang like '%{$wangwang}%'";
        if( $phone )        $where .= " and a.iphone='$phone'";
        if( $shopname )     $where .= " and a.shopname like '%{$shopname}%'";
        if($status == 0) {
            $where .= ' and a.money - a.yufujin < 0';
            $order ='(a.money - a.yufujin)';
        }elseif ($status == 1){
            $where .= ' and a.money - a.yufujin > 0';
            $order ='(a.money - a.yufujin) desc';
        }
        $data = D('user a')
            ->field('FROM_UNIXTIME(a.addtime)as time,a.shopname,a.wangwang,a.iphone,a.address,a.tutor,b.realname as yw_realname,(a.money - a.yufujin) as balance')
            ->join('left join erp_account b on a.tid=b.id')
            ->where($where)
            ->order($order)
            ->select();
        $title=array('加入时间','主店铺名','旺旺','手机号','所在地','业务员QQ','业务员名字','余额');
        $fileName='商家信息';
        $this->down($title,$data,$fileName);
    }

    //更换导师  隐藏用
    public function change_t(){
        if(IS_GET){
            $this->display();
        }elseif (IS_POST){
            $shop_id=intval(I('post.shop_id'));
            $data=D('user a')->field('a.*,b.realname,b.info')->join('left join erp_account b on a.tid=b.id')->where(array('a.uid'=>$shop_id))->find();
            if(empty($data)){
                $this->error('商家不存在');
            }
            $teacher=D('account')->where(array('role'=>6,'msg'=>0))->select();
            $this->assign('data',$data);
            $this->assign('teacher',$teacher);
            $this->assign('shop_id',$shop_id);
            $this->display();
        }
    }

    //更换导师
    public function change_teacher(){
        if(IS_AJAX){
            $tid = intval(I('post.tid',0,'int'));
            $uid = intval(I('post.uid',0,'int'));
            if($tid <= 0)  $this->ajaxReturn(array('msg'=>0,'info'=>'请选择导师！'));

            $user=D('user')->where(array('uid'=>$uid))->find();
            if(!$user)  $this->ajaxReturn(array('msg'=>0,'info'=>'商家不存在'));

            $teacher=D('account')->field('qq')->where(array('id'=>$tid,'msg'=>0))->find();
            if(!$teacher)  $this->ajaxReturn(array('msg'=>0,'info'=>'导师不存在'));
            if($user['tid'] == $tid) $this->ajaxReturn(array('msg'=>0,'info'=>'导师没有变化'));

            //更改商家导师
            $res=D('user')->where(array('uid'=>$uid))->setField(array('tid'=>$tid,'tutor'=>$teacher['qq']));
            if(!$res) $this->ajaxReturn(array('msg'=>0,'info'=>'导师更换失败'));
            $this->ajaxReturn(array('msg'=>1,'info'=>'更改成功'));
        }
    }
    //设置服务费
    public function setservice(){
        if(IS_GET){
            $id=I('get.id',0,'int');
            if(empty($id) || $id<=0){
                $this->error('信息错误','javascript:parent.location.reload();');
                exit;
            }
            $uid=D('user')->where(array('id'=>$id))->getField('uid');
            if(empty($id) || $id<=0){
                $this->error('信息错误','javascript:parent.location.reload();');
                exit;
            }
            $data=D('cover')->where(array('user_id'=>$uid,'status'=>1))->order('addtime desc')->find();
            $this->assign('data',$data);
            $this->display();
        }elseif(IS_POST){
            $id=I('post.id',0,'int');
            $price_1=I('post.price_1');
            $price_2=I('post.price_2');
            $price_3=I('post.price_3');
            $price_4=I('post.price_4');
            $price_5=I('post.price_5');
            if(empty($id) || $id <= 0){
                $this->error('信息错误','javascript:parent.location.reload();');
                exit;
            }
            if(($price_1 > 0 && $price_1 < 13) || $price_1 < 0 || $price_1 > 16){
                $this->error('0~100服务费填写错误');
                exit;
            }
            if(($price_2 > 0 && $price_2 < 15) || $price_2 < 0 || $price_2 > 18){
                $this->error('100~200服务费填写错误');
                exit;
            }
            if(($price_3 > 0 && $price_3 < 16) || $price_3 < 0 || $price_3 > 22){
                $this->error('200~300服务费填写错误');
                exit;
            }
            if(($price_4 > 0 && $price_4 < 20) || $price_4 < 0 || $price_4 > 25){
                $this->error('300~400服务费填写错误');
                exit;
            }
            if(($price_5 > 0 && $price_5 < 25) || $price_5 < 0){
                $this->error('400服务费填写错误');
                exit;
            }

            $uid=D('user')->where(array('id'=>$id))->getField('uid');
            if(empty($uid) || $uid <= 0){
                $this->error('信息错误','javascript:parent.location.reload();');
                exit;
            }
            $data=array(
                'user_id'  =>  $uid,
                'price_1'  =>  $price_1,
                'price_2'  =>  $price_2,
                'price_3'  =>  $price_3,
                'price_4'  =>  $price_4,
                'price_5'  =>  $price_5,
                'status'   =>  0,
                'addtime'  =>  time(),
                'tid'      =>  $_SESSION['user']['id'],
            );
            $res=D('cover')->add($data);
            D('cover')->where('user_id='.$uid.' and addtime <'.time().' and status = 0')->setField('status',2);
            if(!$res){
                $this->error('设置失败');
                exit;
            }
            $this->success('设置成功','javascript:parent.location.reload();');
        }
    }
    //查看设置的服务费
    public function checkserv(){
        if(IS_GET){
            $uid=I('get.id');
            $cover=D('cover')->where('user_id='.$uid.' and (status = 0 or status = 1)')->order('addtime')->find();
            $this->assign('data',$cover);
            $this->display();
        }
    }
    //服务费
    public function service()
    {
        if(IS_GET){
            $where      = 'a.id is not null';
            $shop_name  = trim(I('get.shop_name'));
            $page       = I('get.page',1);
            $page       = $page < 1 ? 0 : $page - 1;
            $page_size  = I('get.pagesize',10);
            $yw         = D('account')->field('id,realname')->where(array('role'=>6,'msg'=>0))->select();
            $this->assign('yw',$yw);
            $choice_yw  = I('get.choice_yw',0,'int');
            if($_SESSION['user']['role'] == 6){
                $where .= ' and a.tid='.$_SESSION['user']['id'];
            }
            if(!empty($shop_name))  $where .= " and b.shopname like '%$shop_name%'";
            if($choice_yw)          $where .= " and a.tid=".$choice_yw;
            $count=D('cover a')
                ->join('left join erp_user b on a.user_id=b.uid')
                ->join('left join erp_account c on a.tid=c.id')
                ->where($where)
                ->count();
            $data=D('cover a')
                ->field('a.*,b.shopname,c.realname')
                ->join('left join erp_user b on a.user_id=b.uid')
                ->join('left join erp_account c on a.tid=c.id')
                ->where($where)
                ->order('addtime desc')
                ->limit($page*$page_size,$page_size)
                ->select();

            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('data',$data);
            $this->assign('data',$data);
            $this->assign('count',$count);
            $this->assign('shop_name',$shop_name);
            $this->display();
        }
    }
    //服务费审核
    public function checkOverDraft(){
        if($_SESSION['user']['role'] != 1 ) $this->ajaxReturn(array('msg'=>0,'info'=>'权限不够'));
        $id=I('post.id',0,'int');
        $type=I('post.type',0,'int');
        if($type != 1 && $type != 2 ) $this->ajaxReturn(array('msg'=>0,'info'=>'信息错误'));
        if(empty($id) || $id <=0 ) $this->ajaxReturn(array('msg'=>0,'info'=>'信息错误'));
        $status=D('cover')->where(array('id'=>$id))->getField('status');
        if($status != 0) $this->ajaxReturn(array('msg'=>0,'info'=>'请刷新再试'));

        //判断type
        if($type == 1){
            //审核通过
            $res=D('cover')->where(array('id'=>$id))->setField(array('status'=>1,'checktime'=>time()));

        }elseif($type == 2){
            $reason=trim(I('post.reason'));
            if(empty($reason)) $this->ajaxReturn(array('msg'=>0,'info'=>'拒绝理由必须填写'));
            $res=D('cover')->where(array('id'=>$id))->setField(array('status'=>2,'checktime'=>time(),'reason'=>$reason));
        }
        if(!$res)   $this->ajaxReturn(array('msg'=>0,'info'=>'操作失败'));
        $this->ajaxReturn(array('msg'=>1,'info'=>'操作成功'));

    }
}
?>