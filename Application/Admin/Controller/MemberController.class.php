<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;



class MemberController extends InterceptController {
	public function index()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$query = "select a.card_name,a.card_num,a.card_img,a.sex,a.age,a.card_status,b.id,b.ddusername from tfx_tbinfo as a ";
		$query .= "left join tfx_user as b on b.id=a.uid ";
		$query .= " where a.card_status>0 order by a.card_status limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		$totalCount = M('tbinfo')->where('card_status>0')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('data',$data);
		$this->display();
	}
	//身份证审核
	public function sfz_verify()
	{
		$id = I('get.id',0);
		$record = M('tbinfo')->where('uid='.$id)->find();
		if($record)
		{
			$data['card_status'] = I('get.status',1);
		    M('tbinfo')->where('uid='.$id)->save($data);
		}
		$this ->redirect('Member/index');
	}
	public function play_money()
	{
		$id = I('get.id',0);
		if($id>0)
		{
			$record = M('tborderlist')->where('id='.$id)->find();
			if($record['ordstatus']==0)
			{
				$data['ordstatus'] = 1;
				$data['uptime'] = date('Y-m-d H:i:s');
			    $Ord=M('tborderlist');
			    $Ord->where('id='.$id)->save($data);
			    //更新个人余额信息
   	 			M('duoduo2010')->where('id='.$record['userid'])->setInc('fund',$record['ordfee']);
			}
		}
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$where = array('payment_type'=>2);
		$data = M('tborderlist')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('tborderlist')->where($where)->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	
	//下单待审
	public function audit_pass()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		//$data = M('tbusertrade')->where(' subconfirm=0 and comfirm=1 and UNIX_TIMESTAMP(subtime)<=(UNIX_TIMESTAMP(now())-48*60*60)')->order('subtime desc')->limit($page*$page_size,$page_size)->select();
		
		$query = "select a.id,a.gid,a.uid,a.addtime,a.tb_id,a.tb_item,a.subtime,b.img2,b.img,b.goods_name,c.ddusername,d.qq ";
		$query .= "from tfx_tbusertrade as a left join tfx_tbgoods as b on a.gid=b.id LEFT JOIN tfx_user as c on a.uid=c.id LEFT JOIN tfx_tbinfo as d on a.uid=d.uid ";
		$query .= "where a.subconfirm=0 and a.comfirm=1 and UNIX_TIMESTAMP(a.subtime)<=(UNIX_TIMESTAMP(now())-48*60*60)";
		$query .= " order by a.subtime DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
		
		$totalCount = M('tbusertrade')->where(' subconfirm=0 and comfirm=1 and UNIX_TIMESTAMP(subtime)<=(UNIX_TIMESTAMP(now())+48*60*60)')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	//订单完成返款
	public function finish()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		//$data = M('tbusertrade')->where(' subconfirm=0 and comfirm=1 and UNIX_TIMESTAMP(subtime)<=(UNIX_TIMESTAMP(now())-48*60*60)')->order('subtime desc')->limit($page*$page_size,$page_size)->select();
		
		$query = "select a.id,a.gid,a.uid,a.addtime,a.tb_id,a.tb_item,a.subtime,a.four_pic,b.img2,b.img,b.goods_name,c.ddusername,d.qq ";
		$query .= "from tfx_tbusertrade as a left join tfx_tbgoods as b on a.gid=b.id LEFT JOIN tfx_user as c on a.uid=c.id LEFT JOIN tfx_tbinfo as d on a.uid=d.uid ";
		$query .= "where a.subconfirm=2 and a.confirm_tixian=0 and a.comfirm=1 and UNIX_TIMESTAMP(a.four_time)<=(UNIX_TIMESTAMP(now())-6*60*60)";
		$query .= " order by a.subtime DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
		
		$totalCount = M('tbusertrade')->where(' subconfirm=2 and confirm_tixian=0 and comfirm=1 and UNIX_TIMESTAMP(four_time)<=(UNIX_TIMESTAMP(now())+48*60*60)')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	public function AjaxOrders()
	{
        $data = M('tbusertrade');
        if (IS_POST){
            $arr['subconfirm']=2;
            $arr['subcondate']=date('Y-m-d H:i:s');
            $arr['subconpp']=$_SESSION['ext_user']['adminname'];
            $t=  $data->where('id='.$_POST['id'])->save($arr);
            if($t){
                $this->ajaxReturn(array('code'=>1,'msg'=>'订单确认成功'));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'订单确认失败'));
            }
        }
    }
    //填写订单号
	public function AjaxEorders()
	{
        $data = M('tbusertrade');
        if (IS_POST){
            $arr['tb_item'] = I('post.tb_item', '');
            $id = I('post.id', '');
            $arr['subtime'] = date('Y-m-d H:i:s');
            
            $order = M('tbusertrade')->where('tb_item='.$arr['tb_item'])->getField('id');
            if(!empty($order))
            {
            	$this->ajaxReturn(array('code'=>0,'msg'=>'订单号已存在'));
            }
            $gid = M('tbusertrade')->where('id='.$id)->getField('gid');
            $goods = M('tbgoods')->where('id='.$gid.' and etime>now() and kucun>0')->find();
            if(!empty($goods))
            {
	            $t=  $data->where('id='.$id)->save($arr);
	            if($t){
	            	M('tbgoods')->where('id=' . $gid)->setInc('sell_number', 1);
	                M('tbgoods')->where('id=' . $gid)->setDec('kucun', 1);
	                $this->ajaxReturn(array('code'=>1,'msg'=>'订单号填写成功'));
	            }else{
	                $this->ajaxReturn(array('code'=>0,'msg'=>'订单号填写失败'));
	            }
            }else{
            	$this->ajaxReturn(array('code'=>0,'msg'=>'该商品已下架请勿填写订单号！'));
            }
            
        }
    }
	//确认返款
	public function Ajaxback_sure()
	{
		$arr=array();
		$aarr=array();
		$arr_tt=array();
		$arr['confirm_txren']=$_SESSION['ext_user']['adminname'];
		$arr['confirm_tixian']=1;
		$arr['tixian_comdate']=date("Y-m-d H:i:s",time());
	    //$row= $duoduo->select('tbusertrade','*',' id='.$_POST['id']);
	    $row = M('tbusertrade')->where('id='.$_POST['id'])->find();
	    $aarr['uid']=$row['uid'];
	    $aarr['gid']=$row['gid'];
	    $aarr['statu']=1;
	    //$gg=$duoduo->select('tbrecord','id',' uid='.$aarr['uid'].' and gid='. $aarr['gid'].' and statu=1');
	    $ggwhere = array(
	    				'uid'  =>$aarr['uid'],
	    				'gid'  =>$aarr['gid'],
	    				'statu'=>1
	    			);
	    $gg = M('tbrecord')->where($ggwhere)->getField('id');
	    $goods =  M('tbgoods')->where('id='.$row['gid'])->find();//$duoduo->tbreal($row['gid']);
	    if ($gg>0){
	        //$this->ajaxReturn(array('code'=>2,'msg'=>'请勿重复提交！'));
	    }else{
	    	$aarr['detailtime']=date("Y-m-d H:i:s",time());
		   
		    $aarr['money'] = $goods['real_price'];
		    //$duoduo->insert1('tbrecord',$aarr);//金额记录
		    M('tbrecord')->add($aarr);
		    /*$sql="update " . $duoduo->BIAOTOU . "user  set txmoney=txmoney+".$aarr['money']."  where id='" .$aarr['uid']."'";
		    $duoduo->query($sql);//返款加入可提现金额中*/
		    
		    M('user')->where('id='.$aarr['uid'])->setInc('txmoney',$aarr['money']);//给用户返款
	    }
	    
	    
		//扣除手续费
        if($aarr['money']<=100)
        {
        	$charge = 2;
        }elseif($aarr['money']<=300 && $aarr['money']>100)
        {
        	$charge = 4;
        }elseif($aarr['money']<=600 && $aarr['money']>300)
        {
        	$charge = 6;
        }elseif($aarr['money']<=1000 && $aarr['money']>600)
        {
        	$charge = 8;
        }elseif($aarr['money']>1000)
        {
        	$charge = 10;
        }
	    //判断试用商品上传时是否缴纳过手续费
		if($goods['vip_rank']==2)
        {
        	$total = ($charge*0.5)+$aarr['money'];
        }else{
        	$total = $charge+$aarr['money'];
        }
	    M('duoduo2010')->where('id='.$goods['role_id'])->setDec('deposit',$total);//确认完成 扣除保证金和手续费

	    //$bb =  $duoduo->update1('tbusertrade',$arr,' id='.$_POST['id']);
	    $bb = M('tbusertrade')->where('id='.$_POST['id'])->save($arr);
	    
	    //$uid=$duoduo->select('user','uid',' id='.$row['uid']);
	    $uid = M('user')->where('id='.$row['uid'])->getField('uid');
	    if($uid)
	    {
	   		//上家返利红包
	        //$n=$duoduo->select('tbrecord','count(id)',' statu=1 and uid='.$row['uid']);
	        $nwhere = array('statu'=>1,'uid'=>$row['uid']);
	        $n = M('tbrecord')->where($nwhere)->count('id');
	        if ($n==1)
	        {//上家返3元，上上家返2元
		        $arr_tt['uid'] = $uid;
		        $arr_tt['statu'] = 4;//上家家返利状态标志
		        $arr_tt['xid'] = $row['uid'];//下家用户id
		        $arr_tt['detailtime'] = date("Y-m-d H:i:s", time());
		        $arr_tt['money'] = 5;//上家返5元
		        $arr_tt['num'] = 1;//记录第几次操作返红包
		        //$duoduo->insert1('tbrecord', $arr_tt);//金额记录
		        M('tbrecord')->add($arr_tt);
		        //$sql_tt = "update " . $duoduo->BIAOTOU . "user  set txmoney=txmoney+" . $arr_tt['money'] . "  where id='" . $arr_tt['uid'] . "'";
		        //$duoduo->query($sql_tt);//返利加入可提现金额中
		        M('user')->where('id='.$arr_tt['uid'])->setInc('txmoney',$arr_tt['money']);//试用返款给用户增加余额(上家)
		        //$uid1= $duoduo->select('user','uid',' id='.$uid);
		        $uid1 = M('user')->where('id='.$uid)->getField('uid');
	          	if ($uid1)
	          	{
		            $arr_tt1['uid'] = $uid1;
		            $arr_tt1['statu'] = 5;//上上家返利状态标志
		            $arr_tt1['xxid'] = $row['uid'];//下下家用户id
		            $arr_tt1['detailtime'] = date("Y-m-d H:i:s", time());
		            $arr_tt1['money'] = 2;//上上家返2元
		            //$duoduo->insert1('tbrecord', $arr_tt1);//金额记录
		            M('tbrecord')->add($arr_tt1);
		            //$sql_tt1 = "update " . $duoduo->BIAOTOU . "user  set txmoney=txmoney+" . $arr_tt1['money'] . "  where id='" . $arr_tt1['uid'] . "'";
		            //$duoduo->query($sql_tt1);//返利加入可提现金额中
		            M('user')->where('id='.$arr_tt1['uid'])->setInc('txmoney',$arr_tt1['money']);//试用返款给用户增加余额（上上家）
	          	}

		     }elseif($n==3){//上家返3元
		           $arr_tt['uid'] = $uid;
		           $arr_tt['statu'] = 4;//上家家返利状态标志
		           $arr_tt['xid'] = $row['uid'];//下家用户id
		           $arr_tt['detailtime'] = date("Y-m-d H:i:s", time());
		           $arr_tt['money'] = 3;//上家返3元
		           $arr_tt['num'] = 3;//记录第几次操作返红包
		           //$duoduo->insert1('tbrecord', $arr_tt);//金额记录
		           M('tbrecord')->add($arr_tt);
		           //$sql_tt = "update " . $duoduo->BIAOTOU . "user  set txmoney=txmoney+" . $arr_tt['money'] . "  where id='" . $arr_tt['uid'] . "'";
		           M('user')->where('id='.$arr_tt['uid'])->setInc('txmoney',$arr_tt['money']);//试用返款给用户增加余额(上家)
		       }
	   }
	   if($bb>0){
		    $this->ajaxReturn(array('code'=>1,'msg'=>'确认成功'));
	   }else{
		    $this->ajaxReturn(array('code'=>0,'msg'=>'操作失败，请重新操作！'));
	   }
    }
    
    
    //
	//人气、库存
	public function alter_data()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		//$data = M('tbusertrade')->where(' subconfirm=0 and comfirm=1 and UNIX_TIMESTAMP(subtime)<=(UNIX_TIMESTAMP(now())-48*60*60)')->order('subtime desc')->limit($page*$page_size,$page_size)->select();
		
		$query = "select a.*,b.adminname from tfx_tbgoods as a left join tfx_duoduo2010 as b on a.role_id=b.id ";
		$query .= "where UNIX_TIMESTAMP(a.etime)>=(UNIX_TIMESTAMP(now())) and a.confirm = 2 and a.kucun>0";
		$query .= " order by a.addtime desc limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
		//$data = M('tbgoods')->where('UNIX_TIMESTAMP(etime)>=UNIX_TIMESTAMP(now()) and confirm = 2')->order('etime DESC')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('tbgoods')->where('UNIX_TIMESTAMP(etime)>=UNIX_TIMESTAMP(now()) and confirm = 2 and kucun>0')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	public function Ajaxalter_data()
	{
        $data = M('tbgoods');
        if (IS_POST){
            $arr['click_alter'] = $_POST['click_alter'];
            $arr['stock_alter'] = $_POST['stock_alter'];
            $t=  $data->where('id='.$_POST['id'])->save($arr);
            if($t){
                $this->ajaxReturn(array('code'=>1,'msg'=>'优化成功'));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'优化失败'));
            }
        }
    }
	//添加黑名单
	public function blacklist()
	{
		$id = I('post.id','');
		$uid = I('get.id','');
		if(!empty($uid))
		{
			$type = I('get.type','');
			$user = M('user')->where('id='.$uid)->find();
			if($user)
			{
				$data['del'] = $type;
		    	M('user')->where('id='.$uid)->save($data);
			}
			$id = $uid;
		}
		if(!empty($id))
		{
			$user = M('user')->where('id='.$id)->find();
			$this->assign('user',$user);
			$this->assign('id',$id);
		}
		$this ->display();
	}
	
	//试客列表
	public function customer(){
		
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		
		$ddusername = I('get.ddusername','','trim');
		$mobile = I('get.mobile','','intval');
		$id = I('get.id','','intval');
		$where = 'id > 0';
		$datawhere = 'a.id > 0';
		
		if (!empty($ddusername)){
			$where .= ' and ddusername LIKE "%'.$ddusername.'%"'; 
			$datawhere .= ' and a.ddusername LIKE "%'.$ddusername.'%"'; 
		} elseif (!empty($mobile)){
			$where .=  ' and mobile = '.trim($mobile);
			$datawhere .= ' and a.mobile = '.trim($mobile);
		} elseif (!empty($id)){
			$where .=  ' and id = '.$id;
			$datawhere .=  ' and a.id = '.$id;
		}
		
		$totalCount = D('user')->where($where)->count();
        //$data = D('user')->field('id, ddusername, qq, mobile, regtime')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->select();
		$query = "select a.id, a.ddusername, a.mobile, a.regtime,b.qq from tfx_user as a left join tfx_tbinfo as b on a.id=b.uid ";
		$query .= "where ".$datawhere;
		$query .= " order by a.id desc limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount));
        $this->assign('ddusername',$ddusername);
        $this->assign('mobile',$mobile);
        $this->assign('id',$id);
        $this->assign('data',$data);
		$this ->display();
	}
	
	//试用记录
	public function usertrade(){
		
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		
		$id = I('get.id','','intval');//用户ID
		$user = D('user')->field('id, ddusername, mobile')->where('id = '.$id)->find();//用户信息
		
		$where = 'uid = '.$id;
		$totalCount = D('TradeView')->where($where)->count();
		$data = D('TradeView')->where($where)->order('id desc')->limit($page*$page_size,$page_size)->select();//用户试用记录

		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount));
		$this->assign('data',$data);
		$this->assign('user',$user);
		$this ->display();
	}
	
	//提现记录
	public function record(){
		
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		
		$id = I('get.id','','intval');//用户ID
		$user = D('user')->field('id, ddusername, mobile')->where('id = '.$id)->find();//用户信息
		
		$where = 'uid = '.$id.' and statu in (2,3)';
		$totalCount = D('tbrecord')->where($where)->count();
		$data = D('tbrecord')->field('id, money, statu, detailtime')->where($where)->order('id desc')->limit($page*$page_size,$page_size)->select();//用户提现记录

		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount));
		$this->assign('data',$data);
		$this->assign('user',$user);
		$this ->display();
	}
}
?>