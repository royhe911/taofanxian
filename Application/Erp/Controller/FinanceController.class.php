<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Erp\Model\LogModel;
use Think\Controller;
use Common\Util\Util;

class FinanceController extends BaseController {
	
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

			$where = 'a.id is not null';
            $starttime_s=I('get.time');
            $endtime  =I('get.endtime');
            $shopname=trim(I('get.shopname'));
			if($_SESSION['user']['role'] ==6 )
			{
				$where .= ' and a.uid in (select id from erp_user where tid='.$_SESSION['user']['id'].')';
			}

            if(!empty($starttime_s)) {
                $starttime=strtotime($starttime_s. "12:00:00") - 86400;
                $where .= " and a.addtime >= {$starttime}";
            }
            if(!empty($endtime))   {
                $endtime=strtotime($endtime. "12:00:00");
                $where .= " and a.addtime <= {$endtime}";
            }
            //默认为前一天中午12点到今天中午12点
            if(empty($starttime_s) && empty($endtime) && empty($shopname)){
			    $starttime_a = strtotime(date('Ymd 12:0:0',time())) - 86400;
			    $endtime_a   = strtotime(date('Ymd 12:0:0',time()));
                $where_money  = $where.' and a.addtime >= '.$starttime_a.' and a.addtime <= '.$endtime_a;

                
                //今日任务金额
                $field = 'sum(a.cost)as comm';
                $field .= ',sum(IFNULL(if(a.actual_price,a.actual_price,a.price),0)) as capital,sum(IFNULL(if(a.empty_cost,a.empty_cost,a.redbag),0)) as other';
                $where_a='a.tb_item is not null and a.addtime >='.strtotime(date('Ymd 00:00:00',time())).' and a.addtime <='.strtotime(date('Ymd 23:59:59',time()));
                if($_SESSION['user']['role'] == 6){
                    $where_a .= ' and a.tid='.$_SESSION['user']['id'];
                }
                $taska=D('Task a')->field($field)->where($where_a)->select();
                
                $finish_money = $capital = $comm = $other =0;

                $capital = $taska[0]['capital'];
                $comm    = $taska[0]['comm'];
                $other   = $taska[0]['other'];
                $finish_money   = $capital+$comm+$other;
                //今日充值金额
                $money_today = D('Recharge')
                    ->alias('a')
                    ->field('sum(a.money) as money')
                    ->join('left join erp_user b on a.uid=b.id')
                    ->where($where_money.' and a.msg=1 and a.status = 0')
                    ->select();
                $this->assign('today',true);
                $this->assign('money_today',$money_today[0]['money']);
                $this->assign('finish_money',$finish_money);
            }

//            if(!$where_money){
//                $where_money  =  $where;
//            }
            if( $shopname ) $where .= " and b.shopname = '{$shopname}'";
            $count= D('Recharge a')
                ->field('a.*,b.shopname')
                ->join('left join erp_user b on a.uid=b.id')
                ->where($where.' and a.status=0')
                ->count();
            $data  = D('Recharge a')
                ->field('a.*,b.shopname,c.realname,c.info as yw_info')
                ->join('left join erp_user b on a.uid=b.id')
                ->join('left join erp_account c on b.tid=c.id')
                ->where($where.' and a.status=0')
                ->order('a.id desc')
                ->limit($page*$page_size,$page_size)
                ->select();


            $money = D('Recharge')
                ->alias('a')
                ->field('sum(a.money) as money')
                ->join('left join erp_user b on a.uid=b.id')
                ->join('left join erp_account c on b.tid=c.id')
                ->where($where.' and a.msg=1 and a.status = 0')
                ->select();
            $money = $money[0]['money'];  //充值金额
            $where_amendment='a.status = 1';
            if($_SESSION['user']['role'] == 6){
                $where_amendment .= ' and b.tid='.$_SESSION['user']['id'];
            }
            if( $shopname ) $where_amendment .= " and b.shopname = '{$shopname}'";
            $abnormal=D('Recharge')
                ->alias('a')
                ->field('sum(a.money) as money')
                ->join('left join erp_user b on a.uid=b.id')
                ->where($where_amendment)
                ->select();

            $abnormal = $abnormal[0]['money'];  //异常充值金额
            $this->assign('abnormal', $abnormal);
            $this->assign('money', $money);

            if ( $starttime_s )  $this->assign('starttime',date("Y-m-d",strtotime($starttime_s. "12:00:00")));
            if ( $endtime )    $this->assign('endtime',date("Y-m-d",$endtime));
            $this->assign('shopname',$shopname);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('data', $data);
            $this->display();
        }
	}
	
	//状态修改
	public function status(){
		//充值审核通过
		if (IS_AJAX){
			$id  = I('post.id',0,'intval');//信息ID
			$msg = I('post.msg',0,'intval');//修改值
			$reason=trim(I('post.reason'));
			$where = array('id' => $id,'msg' => 0);
			$info = D('recharge')->where($where)->field('uid,money,msg')->find();
			if($info['msg'] != 0 ) $this->ajaxReturn(array('msg' => 2, 'info' => '请重试'));
			//开启事务
            M()->startTrans();

			if (empty($info)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
           // D('recharge')->where(array('id' => $id))->setField('reason',$reason);
			$return = D('recharge')->where($where)->save(array('msg' => $msg,'reason'=>$reason,'edittime'=>time(),'user_id'=>$_SESSION['user']['id']));
			if ($return){
			    $user_id=D('user')->where('id='.$info['uid'])->getField('uid');
                $balances_status=save_available($user_id,$info['money'],$id,2,2);
				$msg  = D('user')->where(array('id' => $info['uid']))->setInc('money',$info['money']);//给用户充值
                if(!$msg){
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 3, 'info' => '系统错误'));
                }else{
                    if ($balances_status) {
                        M()->commit();
                        $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
                    }else{
                        M()->rollback();
                        $this->ajaxReturn(array('msg' => 3, 'info' => '系统错误'));
                    }
                }

			} else {
                M()->rollback();
				$this->ajaxReturn(array('msg' => 3, 'info' => '操作失败'));
			}
		}
	}
    //充值拒绝
    public function jujue(){
        $id  = I('post.id',0,'intval');//信息ID
        $msg = I('post.msg',0,'intval');//修改值
        $reason=trim(I('post.reason'));
        $info = D('recharge')->where(array('id' => $id))->field('uid,money,msg')->find();
        if (empty($info)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
        if($info['msg'] != 0 ) $this->ajaxReturn(array('msg' => 2, 'info' => '请重试'));
        $where = array('id' => $id,'msg' => 0);
//        D('recharge')->where(array('id' => $id))->setField('reason',$reason);
        $return = D('recharge')->where($where)->save(array('msg' => $msg,'reason'=>$reason,'edittime'=>time(),'user_id'=>$_SESSION['user']['id']));
        if ($return){
            $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
        } else {
            $this->ajaxReturn(array('msg' => 3, 'info' => '操作失败'));
        }
    }

	//充值记录
	public function record(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        $page = I('get.page',1);
        $page = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',10);
        $starttime = I('get.time');
        $endtime   = I('get.endtime');
        $status    = I('get.status',0,'int');
        if(!in_array($status,array(0,1,2,3))) $status  = 0;

        $info = D('user')->where(array('uid' => $_SESSION['user']['id']))->field('id')->find();
        if (empty($info['id'])) $this->error('该用户不存在!');

        $where ="uid=".$info['id'].' and status=0';
        switch ($status){
            //0代表全部，1代表待审核，2代表通过，3代表拒绝
            case 1 :
                $where .= " and msg = 0";
                break;
            case 2 :
                $where .= " and msg = 1";
                break;
            case 3 :
                $where .= " and msg = 2";
                break;
        }
        if(!empty($starttime)) {
            $starttime=strtotime($starttime. "00:00:00");
            $where .= " and addtime >= {$starttime}";
        }
        if(!empty($endtime))   {
            $endtime=strtotime($endtime. "23:59:59");
            $where .= " and addtime <= {$endtime}";
        }
        $count = D('recharge')->where($where)->count();
        $data  = M('recharge')->order('msg,addtime desc')->where($where)->limit($page*$page_size,$page_size)->select();

        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->assign('count', $count);
        if ( $starttime )  $this->assign('starttime',date("Y-m-d",$starttime));
        if ( $endtime )    $this->assign('endtime',date("Y-m-d",$endtime));
        $this->assign('data', $data);
        $this->assign('status', $status);

        $this->display();

    }


    //扣款记录
    public function chargebacks(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        $page = I('get.page',1);
        $page = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',10);

        $user_Id=$_SESSION['user']['id'];
        $where="user_id={$user_Id} and status=3";
        $count=D('Product')->where($where)->count();
        $data=D('Product')->where($where)->limit($page*$page_size,$page_size)->order('id desc')->select();

        foreach ( $data as $key => $value){
            $data[$key]['miid']         = urlencode(encrypt($data[$key]['id'],'E'));
            $data[$key]['goods_order']  = f_round($value['empty_cost'] * $value['goods_totalnum']);
            $where_s               = "gid={$value['id']} and tb_item is not null";
            $task_info             = D('Task')->where($where_s)->select();
            $count_a               = count($task_info);
            $data[$key]['count_a'] = $count_a;   //已完成份数
            $success_money       = D('Task a')->field('sum(IFNULL(a.actual_price,0)) as success_price,sum(a.cost)as actual_cost,sum(IFNULL(if(a.empty_cost>0,a.empty_cost,a.redbag),0)) as order_s,sum(IFNULL(a.price,0)) as success_ben')->where($where_s)->select();
            $success_price = $success_money[0]['success_price'];   //完成金额
            $actual_cost   = $success_money[0]['actual_cost'];     //已扣服务费
            $order         = $success_money[0]['order_s'];         //其他
            $success_ben   = $success_money[0]['success_ben'];     //已完成任务的原始本金
            $data[$key]['success_price']=f_round($success_price);
            $data[$key]['actual_cost']=f_round($actual_cost);
            $data[$key]['order']=f_round($order);
            $data[$key]['total']=f_round($data[$key]['success_price'] + $data[$key]['actual_cost'] + $data[$key]['order']);
            //未完成任务统计
            $data[$key]['error_num']=$data[$key]['goods_totalnum'] - $count_a;  //未完成数量
            $data[$key]['error_price']=f_round($data[$key]['goods_totalprice'] - $success_ben);     //未完成金额  总金额-已完成的原金额
        }
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->assign('count', $count);
        $this->assign('data',$data);
	    $this->display();
    } 
    //申请佣金
    public function comrecord(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            $user_id = $_SESSION['user']['id'];
            $status = I('get.status',0);//0代表审核中，1代表审核通过，2代表拒绝,3代表确认收款
            $money = D('account')->where('id='.$user_id)->getField('money');//余额
            
            $data=D('Application')->where('user_id='.$user_id.' and status='.$status)->order('id desc')->select();

            $this->assign('money',$money);
            $this->assign('data',$data);
            $this->display();
        }

    }
    //刷单员申请佣金
    public function comrecordz(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            $user_id=$_SESSION['user']['id'];
            $user_info=D('account')->where('id='.$user_id)->find();
            $this->assign('money',$user_info['money']);
            $data=D('Application')->where('user_id='.$user_id)->order('id desc')->select();
            $this->assign('data',$data);
            $this->display();
        }
    }
    
    //站长申请资金
    public function application(){
        if ( IS_AJAX ) {

            $id = $_SESSION['user']['id'];

            $money=trim(I('post.money'));
            if($money <=0) $this->ajaxReturn(array('msg' => 0, 'info' => '金额错误'));
            if (empty($id)) $this->ajaxReturn(array('msg' => 0, 'info' => '该信息不存在'));
            //余额大于1000不让申请
            $user_info=D('account')->field('money')->where('id='.$id)->find();

            //if ( $user_info['money'] >= 20000) $this->ajaxReturn(array('msg' => 3, 'info' => '账户余额充足，不允许申请'));


            $where="user_id={$id} and status in (0,1,2)";
            $res=D('Application')->where($where)->find();
            //if( $res )  $this->ajaxReturn(array('msg' => 2, 'info' => '资金申请审核中，无法继续申请'));

            $data=array(
                'role_id'=>$_SESSION['user']['role'],
                'user_id'=>$id,
                'money'=>$money,
                'addtime'=>time(),
                'now_money'=>$user_info['money'],
            );
            $res=D('application')->add($data);
            if ($res) {
                $this->ajaxReturn(array('msg' => 1, 'info' => '已成功提交申请，请等待！'));
            }else{
                $this->ajaxReturn(array('msg' => 0, 'info' => '申请错误，请重新申请'));
            }

        }

    }

    //刷单员申请资金

    public function applicationz(){
        if ( IS_AJAX ) {

            $id = $_SESSION['user']['id'];//I('post.id',0,'intval');

            $money = (int)(I('post.money'));
            if($money <=0) $this->ajaxReturn(array('msg' => 0, 'info' => '金额错误'));
            if (empty($id)) $this->ajaxReturn(array('msg' => 0, 'info' => '该信息不存在'));
            //余额大于1000不让申请
            $user_info=D('account')->field('money')->where('id='.$id)->find();

            //if ( $user_info['money'] >= 1000) $this->ajaxReturn(array('msg' => 3, 'info' => '账户余额充足，不允许申请'));

            $where="user_id={$id} and status in (0,1,2)";//0代表审核中，1代表审核通过，2代表拒绝,3代表确认收款
            $res=D('Application')->where($where)->find();
            
            //if( $res )  $this->ajaxReturn(array('msg' => 2, 'info' => '资金申请审核中，无法继续申请'));
            
            //查询自己的站长
            $user_info=D('account')->field('user_id,money')->where(array('id'=>$_SESSION['user']['id']))->find();
            $z_id=$user_info['user_id'];//站长id

            $data=array(
                'role_id'=>$_SESSION['user']['role'],
                'user_id'=>$id,
                'money'=>$money,
                'addtime'=>time(),
                'now_money'=>$user_info['money'],
                'z_id'=>$z_id,
            );
            $res=D('application')->add($data);
            if ($res) {
                $this->ajaxReturn(array('msg' => 1, 'info' => '已成功提交申请，请等待！'));
            }else{
                $this->ajaxReturn(array('msg' => 0, 'info' => '申请错误，请重新申请'));
            }

        }
    }

    //佣金审核列表
    public function comcheck(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        if(IS_GET){
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            $applicant=trim(I('get.applicant'));
            $starttime=I('get.time');
            $endtime  =I('get.endtime');
            $status = I('get.status',0);//0代表审核中，1代表审核通过，2代表拒绝,3代表确认收款 4代表异常
            $where = "a.status=".$status;
            
            if($applicant)  $where .= " and b.realname like '%".$applicant."%'";
            $role = $_SESSION['user']['role'];
            $userid = $_SESSION['user']['id'];
            if($role==3)
            {
            	$userdata = M('account')->where('role=2 and user_id='.$userid)->select();
            	$where .= " and a.z_id=".$userid;
            }elseif($role==1 or $role==4)
            {
            	$userdata = M('account')->where('role=3')->select();
            	$where .= " and b.role=3";
            }
			$starttime_s = 0;
            if(!empty($starttime)) {
                $starttime=strtotime($starttime. "00:00:00");
                $where .= " and a.addtime >= {$starttime}";
            }
            if(!empty($endtime))   {
                $endtime=strtotime($endtime. "23:59:59");
                $where .= " and a.addtime <= {$endtime}";
            }
//            if(!empty($timeSearch) || $timeSearch!=0)
//            {
//                $starttime=strtotime($timeSearch." 00:00:00");
//                $endtime=$starttime + 86399;
//            }
            if(empty($starttime) && empty($endtime)){

            	$starttime_s = strtotime(date('Y-m-d 0:0:0'));
                $endtime_s = $starttime_s + 86399;

            }

            $allmoney=D('application')
                ->alias('a')
                ->field('sum(a.money) as money')
                ->where($where)
                ->join('left join erp_account b on a.user_id=b.id')
                ->select();
            $allmoney = $allmoney[0]['money'];
            
//            if($starttime) $where .= " and a.addtime  between {$starttime} and {$endtime}";
            if($starttime_s)
            { 
            	$where_s = $where." and a.addtime  between {$starttime_s} and {$endtime_s}";
            	$nowmoney=D('application')
                ->alias('a')
                ->field('sum(a.money) as money')
                ->where($where_s)
                ->join('left join erp_account b on a.user_id=b.id')
                ->select();
            	$nowmoney = $nowmoney[0]['money'];
            }else{
            	$nowmoney=D('application')
                ->alias('a')
                ->field('sum(a.money) as money')
                ->where($where)
                ->join('left join erp_account b on a.user_id=b.id')
                ->select();
            	$nowmoney = $nowmoney[0]['money'];
            }
            

            $count=D('application')
                ->alias('a')
                ->field('a.*,b.money b_money,b.realname,b.wechat')
                ->join('left join erp_account b on a.user_id=b.id')
                ->where($where)
                ->count();

            $data=D('application')
                ->alias('a')
                ->field('a.*,b.money b_money,b.realname,b.wechat')
                ->where($where)
                ->join('left join erp_account b on a.user_id=b.id')
                ->order('a.id desc')
                ->limit($page*$page_size,$page_size)
                ->select();
            foreach ( $data as $key => $value) {
                $data[$key]['wechat']= json_decode($value['wechat'],true);

            }
            
            $ear_time = M('application')->where('status=3')->order('addtime')->getField('addtime');
            $day = round((time() - $ear_time)/86400);
            $this->assign('day',$day);
            $this->assign('allmoney',$allmoney);
            $this->assign('nowmoney',$nowmoney);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('applicant',$applicant);
            $this->assign('userdata',$userdata);
            if ( $starttime )  $this->assign('starttime',date("Y-m-d",$starttime));
            if ( $endtime )  $this->assign('endtime',date("Y-m-d",$endtime));
            $this->assign('status',$status);
            $this->assign('data',$data);
            $this->display();
        }

    }

    public function comcheckz(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        if(IS_GET){
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            $applicant=trim(I('get.applicant'));
            $where="a.z_id=".intval($_SESSION['user']['id']);
            if($applicant)  $where.=" and b.realname like '%".$applicant."%'";
            $count=D('application')
                ->alias('a')
                ->field('a.*,b.money b_money,b.realname,b.wechat')
                ->where($where)
                ->join('left join erp_account b on a.user_id=b.id')
                ->count();
            $data=D('application')
                ->alias('a')
                ->field('a.*,b.money b_money,b.realname,b.wechat')
                ->where($where)
                ->join('left join erp_account b on a.user_id=b.id')
                ->order('a.addtime desc')
                ->limit($page*$page_size,$page_size)
                ->select();

            foreach ( $data as $key => $value) {
                $data[$key]['wechat']= json_decode($value['wechat'],true);

            }
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('applicant',$applicant);
            $this->assign('data',$data);
            $this->display();
        }

    }

    //财务打款给站长
    public function refer(){

        if(IS_GET){
            $id =intval($_GET['id']);

            $info=D('Application')
                ->alias('a')
                ->field('a.id,a.money,b.wechat')
                ->join('left join erp_account b on a.user_id=b.id')
                ->where('a.id='.$id)
                ->find();
            $info['wechat']=json_decode($info['wechat'],true);

            $this->assign('info',$info);
            $this->display();
        }else{
        	$id=I('post.id');
        	$status = I('post.status',0,'intval');//1通过  2不通过

            $application=D('application')->where('id='.$id)->find();
            if($application['status'] !=0 ) {
                $this->error('信息错误');
                exit;
            }
        	//0代表审核中，1代表审核通过，2代表拒绝,3代表确认收款
         	//开启事务
	        M()->startTrans();
	        if($status == 1)//通过
	        {
	        	$img = $_POST['img'];
        		if(empty($img)) $this->error('上传转款截图');
	            //1代表通过
	            
	            $result['status'] = 3;
	            $result['endtime'] = time();
	            $result['card'] = $_POST['card'];
	            $result['money_pic'] = $img;
	            $result['note'] = I('post.note');
	            $res=D('application')->where('id='.$id)->save($result);  //通过
                //站长余额增加
                $shoukuan=D('account')->where('id='.$application['user_id'])->setInc('money',$application['money']);
	            if($res && $shoukuan)
	            {
	                M()->commit();
	                $this->success('审核通过','javascript:parent.location.reload();');
	            }else{
	                M()->rollback();
	                $this->error('审核失败');
	            }
	
	        }elseif($status == 2){//拒绝
	        	$reason = $_POST['reason'];
        		if(empty($reason)) $this->error('填写拒绝原因');
	            $data=array(
	                'status'=>2,
	                'reason'=>$reason,
	            	'endtime'=>time()
	            );
	            $res = D('application')->where('id='.$id)->save($data);
	            if($res){
	            	M()->commit();
	                $this->success('审核完成','javascript:parent.location.reload();');
	            }else{
	                M()->rollback();
	                $this->error('审核失败');
	            }
	        }else{
	        	$this->error('审核失败');
	        }
        }

    }

    //站长打款给刷单员
    public function referz(){

        if(IS_GET){
            $id =intval($_GET['id']);
            $info=D('Application')
                ->alias('a')
                ->field('a.id,a.money,b.wechat')
                ->join('left join erp_account b on a.user_id=b.id')
                ->where('a.id='.$id)
                ->find();
            $info['wechat']=json_decode($info['wechat'],true);
            $this->assign('info',$info);
            $this->display();
        }else{
            $id=I('post.id',0,'intval');
        	$status = I('post.status',0,'intval');//1通过  2不通过
        	if(empty($id) || empty($status)) {
                $this->error('信息错误');
                exit;
            }
            $application=D('application')->field('money,user_id,status')->where(array('id'=>$id))->find();
            if($application['status'] !=0 ) {
                $this->error('信息错误');
                exit;
            }
        	//0代表审核中，1代表审核通过，2代表拒绝,3代表确认收款
         	//开启事务
	        M()->startTrans();
	        if($status == 1)//通过
	        {
	        	$img = $_POST['img'];
        		if(empty($img)) $this->error('上传转款截图');

	            //1代表通过
	            $user = D('account')->field('money')->where(array('id'=>intval($_SESSION['user']['id'])))->find();
	            //站长获取所申请的金额

	            if ( $user['money'] < $application['money']) $this->error('账户余额不足');//站长余额不足，无法审核通过
	            
	            $result['status'] = 3;
	            $result['endtime'] = time();
	            $result['card'] = $_POST['card'];
	            $result['money_pic'] = $img;
	            $result['note'] = I('post.note');
	            $res=D('application')->where('id='.$id)->save($result);  //通过
	            
	            //通过后站长余额减少
	            $info=D('account')->where(array('id'=>intval($_SESSION['user']['id'])))->setDec('money',$application['money']);
	            //业务员余额增加
                $shuadanyuan=D('account')->where('id='.intval($application['user_id']))->setInc('money',$application['money']);
	            if($res && $info && $shuadanyuan)
	            {
	                M()->commit();
	                $this->success('审核通过','javascript:parent.location.reload();');
	            }else{
	                M()->rollback();
	                $this->error('审核失败');
	            }
	
	        }elseif($status == 2){//拒绝
	        	$reason = $_POST['reason'];
        		if(empty($reason)) $this->error('填写拒绝原因');
	            $data=array(
	                'status'=>2,
	                'reason'=>$reason,
	            	'endtime'=>time()
	            );
	            $res=D('application')->where('id='.$id)->save($data);
	            if($res){
	            	M()->commit();
	                $this->success('操作成功','javascript:parent.location.reload();');
	            }else{
	                M()->rollback();
	                $this->error('审核失败');
	            }
	        }else{
	        	$this->error('审核失败');
	        }
        }

    }
    


    //站长确认收款
//    public function receipt(){
//        //确认收款
//        if( IS_AJAX){
//
//            if($_SESSION['user']['role'] == 2)  $this->ajaxReturn(array('msg'=>0,'info'=>'信息错误'));
//
//            $id=intval(I('post.id'));
//            if(empty($id))  $this->ajaxReturn(array('msg'=>0,'info'=>'信息不存在'));
//            $data=D('application')->field('user_id,money,status')->where(array('id'=>$id))->find();
//            if($data['status']  != 1) $this->ajaxReturn(array('msg'=>0,'info'=>'信息错误'));
//            //开启事务
//            M()->startTrans();
//            $res=D('account')->where(array('id'=>$data['user_id']))->setInc('money',$data['money']); //站长余额增加
//            $status=D('application')->where(array('id'=>$id))->setField('status',3);
//            if(!$res || !$status){
//                M()->rollback();
//                $this->ajaxReturn(array('msg'=>0,'info'=>'error'));
//            }
//            M()->commit();
//            $this->ajaxReturn(array('msg'=>1,'info'=>'操作成功'));
//
//        }
//    }
    //刷单员确认收款
//    public function receiptz(){
//        //确认收款
//        if( IS_AJAX){
//            $id=intval(I('post.id'));
//            if(empty($id))  $this->ajaxReturn(array('msg'=>0,'info'=>'信息不存在'));
//            $data=D('application')->field('user_id,z_id,money')->where(array('id'=>$id))->find();
//            $user_id=intval($data['user_id']);
//            //$z_id=intval($data['z_id']);
//
//            //开启事务
//            M()->startTrans();
//            $res   = D('account')->where(array('id'=>$user_id))->setInc('money',$data['money']); //刷单员余额增加
//
//
//            $status=D('application')->where(array('id'=>$id))->setField('status',3);
//            if(!$res  || !$status){
//                M()->rollback();
//                $this->ajaxReturn(array('msg'=>0,'info'=>'error'));
//            }
//            M()->commit();
//            $this->ajaxReturn(array('msg'=>1,'info'=>'操作成功'));
//
//        }
//    }
    
    //获取站点信息
     public function getuserdata()
     {
     	$id  = I('get.id',0,'intval');
     	if(empty($id))$this->error('信息不存在');
     	$money = M('account')->where('id='.$id)->getField('money');
     	if(empty($money))$this->error('信息不存在');
     	//站点余额
     	$allmoney = M('account')->where('user_id='.$id)->getField('sum(money)');
     	$allmoney = $allmoney+$money;
     	
     	$userdata = M('account')->where('id='.$id.' or user_id='.$id)->select();
        $this->assign('userdata',$userdata);
        $this->assign('allmoney',$allmoney);
        $this->display();
     }
    
    //提现记录
    public function cashrecord(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if(IS_GET)
        {
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            $count=D('cash')->where(array('uid'=>intval($_SESSION['user']['id'])))->count();
            $cash=D('cash')->where(array('uid'=>intval($_SESSION['user']['id'])))->order('id desc')->select();
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('data',$cash);
            $this->display();
        }
    }
    //提现审核
    public function cashcheck(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if(IS_GET)
        {

            $status=I('get.status',4,'intval');
            $where = "a.id is not null";
            if(!in_array($status,array(0,1,2))) $status=3;
            //0待审核 1是通过 2是拒绝
            if($status !=3) {
                switch ($status){
                    case 0:
                        $where .= ' and (a.status = 0 or a.status = 4)';
                        break;
                    case 1:
                        $where .= ' and a.status = 1';
                        break;
                    case 2:
                        $where .= ' and (a.status = 2 or a.status = 3)';
                        break;
                }

            }

            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            //添加分页。待写
            if($_SESSION['user']['role'] == 6){
                $where .= " and b.tid=".intval($_SESSION['user']['id']);
            }
            $count=D('cash a')
                ->join('left join erp_user b on a.uid=b.uid')
                ->join('left join erp_account c on b.tid=c.id')
                ->where($where)
                ->count();
            $data=D('cash a')
                ->field('a.*,b.shopname,c.info as yw_info,truncate(a.money - a.cost,2) as av_money')
                ->join('left join erp_user b on a.uid=b.uid')
                ->join('left join erp_account c on b.tid=c.id')
                ->where($where)
                ->order('a.id desc')
                ->limit($page*$page_size,$page_size)
                ->select();
//            foreach ($data as $key =>$value){
//                $data[$key]['av_money']=$value['money'] - $value['cost'];
//            }

            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('data',$data);
            $this->display();
        }
    }

    //业务员审核提现
    public function check(){

        if(IS_AJAX){

            $id  = I('post.id',0,'intval');
            $msg = I('post.msg',0,'intval');
            $reason=trim(I('post.reason'));
            if($id <=0 || ($msg !=1 && $msg !=2))  $this->ajaxReturn(array('msg'=>0,'info'=>'参数错误'));
            if($msg == 1){
                //通过
                $res=D('cash')->where(array('id'=>$id))->setField('status',4);
                addLog(LogModel::TYPE_APPLY_CASH_PASS, '业务员审核通过商家申请提现，提现记录ID：'. $id);
                if(!$res)  $this->ajaxReturn(array('msg'=>0,'info'=>'审核错误'));
                $this->ajaxReturn(array('msg'=>1,'info'=>'审核通过'));
            }elseif($msg ==2){
                //不通过
                if(empty($reason))  $this->ajaxReturn(array('msg'=>0,'info'=>'拒绝理由必须填写'));
                //开启事务
                M()->startTrans();
                $array=array('status'=>3,'reason'=>$reason);
                $res=D('cash')->where(array('id'=>$id))->setField($array);
                if( !$res )  {
                    M()->rollback();
                    $this->ajaxReturn(array('msg'=>0,'info'=>'系统错误'));
                }
                //把钱返给用户
                $cash=D('cash')->where(array('id'=>$id))->find();
                $balances_status=save_available($cash['uid'],$cash['money'],$id,1,2);
                if (!$balances_status) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg'=>0,'info'=>'返款错误'));
                }
                $re_money=D('user')->where(array('uid'=>$cash['uid']))->setInc('money',$cash['money']);
                $free = D('user')->where(array('uid'=>$cash['uid']))->setDec('freeze_free',$cash['money']);
                addLog(LogModel::TYPE_APPLY_CASH_FAIL, '业务员拒绝商家申请提现，拒绝原因：'.$reason.'，提现记录ID：'. $id);
                if (!$re_money || !$free) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg'=>0,'info'=>'返款错误'));
                }
                M()->commit();
                $this->ajaxReturn(array('msg'=>1,'info'=>'操作成功'));
            }
        }
    }
    //财务审核提现
    public function cashenter(){
        if(IS_GET){
            $this->display();
        }else{

            $id=I('post.id',0,'intval');
            if($id <=0) $this->error('信息不存在');
            $status=I('post.status',0,'intval');
            if(empty($status)) $this->error('信息不存在');
            if ( $status == 1 ){
                //通过
                $img=I('post.img');
                if(empty($img)) $this->error('图片不存在');
                $money_pic="upload/".date('Ymd').'/'.I('post.img');
                $array=array(
                    'status'=>1,
                    'money_pic'=>$money_pic,
                );

                M()->startTrans();
                $cash=D('cash')->where(array('id'=>$id))->find();
                $res=D('cash')->where('id='.$id)->setField($array);
                $res_free = D('user')->where('uid = ' . $cash['uid'])->setDec('freeze_free', $cash['money']);   // 扣减用户冻结提现余额
                if(!$res || !$res_free) {
                    M()->rollback();
                    $this->error('提交失败');
                    exit;
                }
                addLog(LogModel::TYPE_APPLY_CASH_PASS, getUserType().'审核商家申请提现，提现记录ID：'. $id);
                M()->commit();
                $this->success('提交成功','javascript:parent.location.reload();');
            }elseif($status ==2){
                //拒绝
                //开启事务
                M()->startTrans();
                $reason=trim(I('post.reason'));
                if( empty($reason)) $this->error('理由必须填写');
                $array=array(
                    'status'=>2,
                    'reason'=>$reason,
                );
                $res=D('cash')->where('id='.$id)->setField($array);
                //把钱还给用户
                $cash=D('cash')->where(array('id'=>$id))->find();
                $balances_status=save_available($cash['uid'],$cash['money'],$id,3,1);
                if (!$balances_status) {
                    M()->rollback();
                    $this->error('提交失败');
                    exit;
                }
                $re_money=D('user')->where(array('uid'=>$cash['uid']))->setInc('money',$cash['money']);
                $res_free = D('user')->where('uid = ' . $cash['uid'])->setDec('freeze_free', $cash['money']);   // 扣减用户冻结余额
                addLog(LogModel::TYPE_APPLY_CASH_FAIL, getUserType().'拒绝商家申请提现，拒绝原因：'.$reason.'，提现记录ID：'. $id);
                if(!$res || !$re_money || !$res_free) {
                    M()->rollback();
                    $this->error('提交失败');
                    exit;
                }
                M()->commit();
                $this->success('提交成功','javascript:parent.location.reload();');
            }


        }
    }
    public function test(){
        $data=D('application')->where('role_id=2 and status = 1')->select();

        foreach ($data as $key=>$value){
            D('account')->where('id='.$value['user_id'])->setInc('money',$value['money']);
        }
        D('application')->where('role_id=2 and status = 1')->setField('status',3);
    }

    //站长收款异常申请
    public function gatheringabn(){
        if(IS_GET){
            $this->display();
        }elseif(IS_POST){
            $id=I('post.id',0,'intval');
            if(empty($id)){
                $this->error('信息错误');
                exit;
            }
            $application=D('application')->where('id='.$id)->find();
            if( $application['status'] != 3 ){
                $this->error('信息错误');
                exit;
            }
            if( $application['user_id'] != $_SESSION['user']['id']){
                $this->error('信息错误');
                exit;
            }
            $abn_info=trim(I('post.abn_info'));
            if(empty($abn_info)){
                $this->error('异常说明必须填写');
                exit;
            }
            $img=I('post.img');
            $data=array(
                'status'=>4,
                'abn_pic'=>$img,
                'abn_info'=>$abn_info,
                'abntime'=>time()
            );
            $res=D('application')->where('id='.$id)->save($data);
            if(!$res){
                $this->error('提交失败');
                exit;
            }
            $this->success('提交成功','javascript:parent.location.reload();');

        }
    }
    //充值修正数据显示
    public function erroracount(){
        if(IS_GET) {
            $shopname  = trim(I('get.shopname'));
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);
            $where     = "a.status = 1";
            if($_SESSION['user']['role'] == 6){
                $where .= ' and b.tid='.$_SESSION['user']['id'];
            }
            if(!empty($shopname)) $where .= " and b.shopname='{$shopname}'";
            $count= D('recharge a')
                ->join('left join erp_user b on a.uid=b.id')
                ->where($where)
                ->count();
            $amendment = D('recharge a')
                ->field('a.id,a.money,a.addtime,a.reason,b.shopname')
                ->join('left join erp_user b on a.uid=b.id')
                ->where($where)
                ->order('a.id desc')
                ->limit($page*$page_size,$page_size)
                ->select();
            $this->assign('data', $amendment);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->display();
        }
    }

    //收支明细
    public function cashflow()
    {
        if(IS_GET){
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);
            $where     = "a.id is not null";
            $status    = I('get.status',0,'int');
            $starttime = I('get.time');
            $endtime   = I('get.endtime');
            $shopname  = trim(I('get.shopname'));

            if(!empty($starttime)) {
                $starttime_a = $starttime.' 00:00:00';
                $where .= ' and unix_timestamp(a.addtime) >='.strtotime($starttime_a);

            }
            if(!empty($endtime))   {
                $endtime_a = $endtime.' 23:59:59';
                $where .= ' and unix_timestamp(a.addtime) <='.strtotime($endtime_a);
            }

            if(!in_array($status,array(0,1,2))){
                $status = 0;
            }

            //if($status == 0) $where .= ' and a.status = 2';
            if($status == 1) $where .= ' and a.status = 2';
            if($status == 2) $where .= ' and a.status = 1';
            //管理员和财务
            if($_SESSION['user']['role'] == 1 or $_SESSION['user']['role']==4){

            }
            if($_SESSION['user']['role'] == 5){
                //商家
                $where .= ' and a.uid='.$_SESSION['user']['id'];
            }
            if($_SESSION['user']['role'] == 6){
                $where .= ' and b.tid='.$_SESSION['user']['id'];
            }
            if(!empty($shopname)) $where .= " and b.shopname='{$shopname}'";
            $count=D('balances a')
                ->join('left join erp_user b on a.uid=b.uid')
                ->where($where)
                ->count();
            $data=D('balances a')
                ->field('a.*,b.shopname')
                ->join('left join erp_user b on a.uid=b.uid')
                ->where($where)
                ->order('a.id desc')
                ->limit($page*$page_size,$page_size)
                ->select();
            if($starttime)  $this->assign('starttime',$starttime);
            if($endtime)  $this->assign('endtime',$endtime);
            $this->assign('data',$data);
            $this->assign('status',$status);
            $this->assign('shopname',$shopname);
            $this->assign('count',$count);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->display();
        }
    }
}
?>