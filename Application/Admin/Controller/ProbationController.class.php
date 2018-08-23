<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;



class ProbationController extends InterceptController {
	public function index()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		//$data = M('tbcash')->limit($page*$page_size,$page_size)->select();
		$query = "select a.*,b.adminname from tfx_tbcash as a ";
		$query .= "left join tfx_duoduo2010 as b on a.uid=b.id ";
		$query .= " order by a.uptime DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		$totalCount = M('tbcash')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	//确认返款
	public function cash_sure()
	{
		$id = I('get.id',0);
		$record = M('tbcash')->where('id='.$id)->find();
		if($record)
		{
			$data['status'] = 2;
			$data['uptime'] = date('Y-m-d H:i:s');
		    $Ord=M('tbcash');
		    $Ord->where('id='.$id)->save($data);
		}
		$this ->redirect('Probation/index');
	}
	public function play_money()
	{
		$id = I('get.id',0);
		if($id>0)
		{
			$code = I('get.code',0);
			if($code==1)
			{
				$record = M('tborderlist')->where('id='.$id)->find();
				if($record['ordstatus']==0)
				{
					$data['ordstatus'] = 2;//关闭订单
					$data['uptime'] = date('Y-m-d H:i:s');
				    $Ord=M('tborderlist');
				    $Ord->where('id='.$id)->save($data);
				}
			}else{
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
			
		}
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$where = array('payment_type'=>1);
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
		
		$query = "select a.id,a.gid,a.uid,a.addtime,a.tb_id,a.tb_item,b.model,a.subtime,b.img2,b.img,b.goods_name,c.ddusername,d.qq ";
		$query .= "from tfx_tbusertrade as a left join tfx_tbgoods as b on a.gid=b.id LEFT JOIN tfx_user as c on a.uid=c.id LEFT JOIN tfx_tbinfo as d on a.uid=d.uid ";
		$query .= "where a.subconfirm=0 and a.comfirm=1 and UNIX_TIMESTAMP(a.subtime)<=(UNIX_TIMESTAMP(now())-12*60*60)";
		$query .= " order by a.subtime DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
		
		$totalCount = M('tbusertrade')->where(' subconfirm=0 and comfirm=1 and UNIX_TIMESTAMP(subtime)<=(UNIX_TIMESTAMP(now())-12*60*60)')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	//试用商品审核
	public function goods_list()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$data = M('tbgoods_activity')->where('state=10 and endtime>'.time())->order('addtime desc')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('tbgoods_activity')->where('state=10 and endtime>'.time())->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}
	//商品上架审核
	public function Ajaxgoods_verify()
	{
        $data = M('tbgoods_activity');
        if (IS_POST){
        	$status = I('post.status',2);
        	$id = I('post.id',2);
        	if($status==1)//确认通过
        	{
			    $arr['state'] = 11;
		        $arr['note'] = I('post.note','');
			    $arr['verify_man'] = $_SESSION['ddadmin']['id'];
			    $arr['verify_date'] = time();
        	}else{//驳回
		        $arr['note'] = I('post.note','');
			    $arr['state'] = 12;
			    $arr['verify_man'] = $_SESSION['ddadmin']['id'];
			    $arr['verify_date'] = time();
        	}
	        $t = $data->where('id='.$id)->save($arr);
	        if($t)
	        {
	        	if($status==2)//驳回返款给商户
        		{
        			$goods_data = M('tbgoods_activity')->where('id='.$id)->find();
        			//更新个人余额信息
	   	 			M('duoduo2010')->where('id='.$goods_data['uid'])->setInc('fund',$goods_data['money']);
	   	 			M('duoduo2010')->where('id='.$goods_data['uid'])->setDec('deposit',$goods_data['money']);
        		}
	        	$this->ajaxReturn(array('code'=>1,'msg'=>'审核完成'));
	        	
	        }else{
	        	 $this->ajaxReturn(array('code'=>0,'msg'=>'操作失败'));
	        }
        }
    }
	
	//订单完成返款
	public function finish()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		//$data = M('tbusertrade')->where(' subconfirm=0 and comfirm=1 and UNIX_TIMESTAMP(subtime)<=(UNIX_TIMESTAMP(now())-48*60*60)')->order('subtime desc')->limit($page*$page_size,$page_size)->select();
		
		$query = "select a.id,a.gid,a.uid,a.addtime,a.tb_id,a.tb_item,a.subtime,a.four_pic,b.model,b.img2,b.img,b.goods_name,c.ddusername,d.qq ";
		$query .= "from tfx_tbusertrade as a left join tfx_tbgoods as b on a.gid=b.id LEFT JOIN tfx_user as c on a.uid=c.id LEFT JOIN tfx_tbinfo as d on a.uid=d.uid ";
		$query .= "where a.subconfirm=2 and a.confirm_tixian=0 and a.comfirm=1 and UNIX_TIMESTAMP(four_time)<=(UNIX_TIMESTAMP(now())-6*60*60)";
		$query .= " order by a.subtime DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
		
		$totalCount = M('tbusertrade')->where(' subconfirm=2 and confirm_tixian=0 and comfirm=1 and UNIX_TIMESTAMP(four_time)<=(UNIX_TIMESTAMP(now())-6*60*60)')->count();
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
	    $goods =  M('tbgoods')->where('id='.$row['gid'])->find();//$duoduo->tbreal($row['gid']);
	    if($goods['model']!=4)
	    {
		    //$gg=$duoduo->select('tbrecord','id',' uid='.$aarr['uid'].' and gid='. $aarr['gid'].' and statu=1');
		    $ggwhere = array(
		    				'uid'  =>$aarr['uid'],
		    				'gid'  =>$aarr['gid'],
		    				'statu'=>1
		    			);
		    $gg = M('tbrecord')->where($ggwhere)->getField('id');
		    if ($gg>0){
		        //$this->ajaxReturn(array('code'=>2,'msg'=>'请勿重复提交！'));
		    }else{
		        //试客返款操作
		    	$aarr['detailtime']=date("Y-m-d H:i:s",time());
			    $aarr['money'] = $goods['real_price']+$goods['red_price'];
	            M('tbrecord')->add($aarr);
	                
	            $user_data = M('user')->where('id=' . $aarr['uid'])->find();
	            if($user_data['f_money_yi']>0)//已提取过预提金
	            {
	                if($aarr['money']>$user_data['f_money_yi'])
	                {
	                	$balance = $aarr['money'] - $user_data['f_money_yi'];
	                	M('user')->where('id=' . $aarr['uid'])->setInc('txmoney', $balance); //给用户返款
	                	$user_arr['f_money_yi'] = 0;
	            		M('user')->where('id=' . $aarr['uid'])->save($user_arr);//已提现金额为0
	                }else{
	                	M('user')->where('id=' . $aarr['uid'])->setDec('f_money_yi', $aarr['money']); //减少已提现金额
	                }
	                	
	             }else{
	                	M('user')->where('id=' . $aarr['uid'])->setInc('txmoney', $aarr['money']); //给用户返款
	             }
		    }
		    //新手第一次完成试用返现2元
	        $first_w = array(
	        					'uid' => $row['uid'],
	        					'statu' => 1
	        				);
	        
	        $record_count = M('tbrecord')->where($first_w)->count();
	        if($record_count==1)
	        {
	        	$fm['uid'] = $row['uid'];
	        	$fm['gid'] = $row['gid'];
	            $fm['statu'] = 6; //用户首次试用成功返现2元
	            $fm['detailtime'] = date("Y-m-d H:i:s", time());
	            $fm['money'] = 2; //用户首次试用成功返现2元
	            M('tbrecord')->add($fm);
	        	M('user')->where('id=' . $row['uid'])->setInc('txmoney', $fm['money']); //给用户返款
	        }
	    
	    }else{
	    	//$gg=$duoduo->select('tbrecord','id',' uid='.$aarr['uid'].' and gid='. $aarr['gid'].' and statu=1');
		    $ggwhere = array(
		    				'uid'  =>$aarr['uid'],
		    				'gid'  =>$aarr['gid'],
		    				'statu'=>7//返款单
		    			);
		    $gg = M('tbrecord')->where($ggwhere)->getField('id');
		    if ($gg>0){
		        //$this->ajaxReturn(array('code'=>2,'msg'=>'请勿重复提交！'));
		    }else{
		        //试客返款操作
		    	$aarr['detailtime']=date("Y-m-d H:i:s",time());
			    $aarr['money'] = $goods['red_price'];
			    $aarr['statu'] = 7;
	            M('tbrecord')->add($aarr);
	            M('user')->where('id=' . $aarr['uid'])->setInc('txmoney', $aarr['money']); //给用户返款
		    }
	    }
	    
	    
		
	    
		/*//扣除手续费
        if($goods['real_price']<=100)
        {
        	$charge = 2;
        }elseif($goods['real_price']<=300 && $goods['real_price']>100)
        {
        	$charge = 4;
        }elseif($goods['real_price']<=600 && $goods['real_price']>300)
        {
        	$charge = 6;
        }elseif($goods['real_price']<=1000 && $goods['real_price']>600)
        {
        	$charge = 8;
        }elseif($goods['real_price']>1000)
        {
        	$charge = 10;
        }
	    //判断试用商品上传时是否缴纳过手续费
		if($goods['vip_rank']==2)
        {
        	$total = ($charge*0.5)+$aarr['money'];
        }else{
        	$total = $charge+$aarr['money'];
        }*/
	    $charge = 1;
	    $total = $charge+$aarr['money'];
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
	        {//上家返2元，上上家返1元
		        $arr_tt['uid'] = $uid;
		        $arr_tt['statu'] = 4;//上家家返利状态标志
		        $arr_tt['xid'] = $row['uid'];//下家用户id
		        $arr_tt['detailtime'] = date("Y-m-d H:i:s", time());
		        $arr_tt['money'] = 2;//上家返2元
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
		            $arr_tt1['money'] = 1;//上上家返1元
		            //$duoduo->insert1('tbrecord', $arr_tt1);//金额记录
		            M('tbrecord')->add($arr_tt1);
		            //$sql_tt1 = "update " . $duoduo->BIAOTOU . "user  set txmoney=txmoney+" . $arr_tt1['money'] . "  where id='" . $arr_tt1['uid'] . "'";
		            //$duoduo->query($sql_tt1);//返利加入可提现金额中
		            M('user')->where('id='.$arr_tt1['uid'])->setInc('txmoney',$arr_tt1['money']);//试用返款给用户增加余额（上上家）
	          	}

		     }elseif($n==5){//上家返5元
		           $arr_tt['uid'] = $uid;
		           $arr_tt['statu'] = 4;//上家家返利状态标志
		           $arr_tt['xid'] = $row['uid'];//下家用户id
		           $arr_tt['detailtime'] = date("Y-m-d H:i:s", time());
		           $arr_tt['money'] = 5;//上家返5元
		           $arr_tt['num'] = 5;//记录第几次操作返红包
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
    //订单号驳回
	public function orderid_reject()
	{
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$query = "select a.id,a.gid,a.uid,a.comfirm_date,a.addtime,a.tb_id,a.tb_item,a.subtime,b.img2,b.img,b.goods_name,c.ddusername,d.qq ";
		$query .= "from tfx_tbusertrade as a left join tfx_tbgoods as b on a.gid=b.id LEFT JOIN tfx_user as c on a.uid=c.id LEFT JOIN tfx_tbinfo as d on a.uid=d.uid ";
		$query .= "where a.comfirm=2 and a.tb_item is not null and a.four_pic is null";
		$query .= " order by a.comfirm_date DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
        $totalCount = M('tbusertrade')->where('comfirm=2 and tb_item is not null and four_pic is null')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
        
    }
    //评价驳回
	public function eval_reject()
	{
        $page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$query = "select a.id,a.gid,a.uid,a.comfirm_date,a.addtime,a.tb_id,a.tb_item,a.subtime,a.four_pic,b.img2,b.img,b.goods_name,c.ddusername,d.qq ";
		$query .= "from tfx_tbusertrade as a left join tfx_tbgoods as b on a.gid=b.id LEFT JOIN tfx_user as c on a.uid=c.id LEFT JOIN tfx_tbinfo as d on a.uid=d.uid ";
		$query .= "where a.comfirm=2 and a.tb_item is not null and a.four_pic is not null";
		$query .= " order by a.comfirm_date DESC limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
        $totalCount = M('tbusertrade')->where('comfirm=2 and tb_item is not null and four_pic is not null')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
    }
    //等待开奖商品列表
    public function goods_lottery()
    {
    	$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		//$data = M('tbgoods_activity')->where('state=11 and endtime>'.time().' and starttime<'.time())->order('addtime desc')->limit($page*$page_size,$page_size)->select();
		$query = "SELECT a.*,b.count,c.count1 FROM tfx_tbgoods_activity a LEFT JOIN (SELECT count(id)as count,gid FROM tfx_tbtrialorders WHERE comfirm<98 and DATE_FORMAT(apply_time,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d') GROUP BY gid ) b on a.id=b.gid ";
		$query .= "LEFT JOIN (SELECT count(id)as count1,gid FROM tfx_tbtrialorders WHERE (comfirm in(101,102) and DATE_FORMAT(apply_time,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d')) or comfirm=99 GROUP BY gid ) c on a.id=c.gid ";
		$query .= " where a.state=11 and a.endtime>".time()." and a.starttime<".time();
		$query .= " order by a.addtime desc limit ".($page*$page_size).",".$page_size;
		$data = M()->Query($query);
		
		$totalCount = M('tbgoods_activity')->where('state=11 and endtime>'.time().' and starttime<'.time())->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
    }
    
	//商品开奖
    public function lottery_data(){
    	
    	$id = I('id',0,'intval');//信息ID
    	$num = I('post.num');//开奖人数
    	$gid = I('gid',0,'intval');//试用商品ID
    	$totalCount = D('tbtrialorders')->where('comfirm > 98 and gid='.$gid)->count();//申请总人数
    	$info = $this->lotteryTodayMessage($gid,$totalCount,$num);
    	
    	if(IS_POST){
    		if (empty($info['today'])) $this->error("今天开奖已达上限", U('Probation/goods_lottery'));
    		if (0 == $info['user']) $this->error("人数不够,无法开奖", U('Probation/goods_lottery'));
    		
    		$msg = $this->random($gid,$info['user']);//随机开奖
    		if (!empty($msg)){
    			$this->task($gid, $msg, $info['goods']['days']['num'], $info['goods']['task']);//分配任务
    			$this->success('操作成功');
    		} else {
    			$this->error('操作失败');
    		}
    	} else {
    		$page = I('get.page',1);
    		$page = $page < 1 ? 0 : $page - 1;
    		$page_size = I('get.pagesize',20);
    		
    		$query = "SELECT a.id,a.gid,a.uid,a.tb_id,a.addtime,a.comfirm,a.cart_pic,c.img2,c.qq,d.ddusername FROM tfx_tbtrialorders a ";
    		$query .= "LEFT JOIN tfx_tbinfo c on a.uid=c.uid left join tfx_user d on a.uid=d.id where (a.comfirm > 98) and a.gid=".$gid;
    		$query .= " order by a.addtime limit ".($page*$page_size).",".$page_size;
    		$data = M()->Query($query);
    		
    		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
    		$this->assign('today',$info['today']);
    		$this->assign('goods',$info['goods']);
    		$this->assign('list',$data);
    		$this->assign('gid',$gid);
    		$this->display();
    	}
    }
    //确认中奖
	public function Ajaxlottery()
	{
		$id = I('post.id','');
		$status = I('post.status',2);
		if($id>0)
		{
			$comfirm = M('tbtrialorders')->where('id=' . $id)->getField('comfirm');
			if($status==2)//未中奖
			{
				if($comfirm==101)
				{
					$arr['comfirm'] = 103;
				}elseif($comfirm==102)
				{
					$arr['comfirm'] = 104;
				}else{
					$arr['comfirm'] = 98;
				}
		        $arr['apply_time']=date('Y-m-d H:i:s');
		        $t=   M('tbtrialorders')->where('id='.$id)->save($arr);
				if($t)
		        {
		        	$this->ajaxReturn(array('code'=>1,'msg'=>'确认开奖成功'));
		        }else{
		        	$this->ajaxReturn(array('code'=>0,'msg'=>'确认开奖失败'));
		        }
		        die();
			}
			$gid = M('tbtrialorders')->where('id='.$id)->getField('gid');
	    	$goods = M('tbgoods_activity')->where('id='.$gid)->find();
	    	
	    	$days = json_decode($goods['days'],true);
	    	foreach($days as $val)
	    	{
	    		$now = (int)date('d');
	    		if($val['day']==$now)
	    		{
	    			$num = $val['num'];
	    			break;
	    		}
	    	}
	    	$lotteryCount = M('tbtrialorders')->where("comfirm=0 and DATE_FORMAT(apply_time,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d') and gid=".$gid)->count();
	    	if($lotteryCount>=$num)
	    	{
	    		$this->ajaxReturn(array('code'=>0,'msg'=>'今天开奖已达上限'));
	    	}
	    	
	    	$talk_per = json_decode($goods['task'],true);
	    	$talk_per = $talk_per['chat'];
	    	$talk_count = round($num*$talk_per);//需要聊天任务的个数
	    	$talkCount = M('tbtrialorders')->where("comfirm=0 and is_talk=1 and DATE_FORMAT(apply_time,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d') and gid=".$gid)->count();
	    	if($talk_count>$talkCount)
	    	{
	    		$arr['is_talk'] = 1;
	    	}
	        $arr['comfirm'] = 0;
			if($comfirm==101)
			{
				$arr['won_type'] = 1;
			}elseif($comfirm==102)
			{
				$arr['won_type'] = 2;
			}
	        $arr['apply_time']=date('Y-m-d H:i:s');
	        $t=   M('tbtrialorders')->where('id='.$id)->save($arr);
	        if($t)
	        {
	        	$uid = M('tbtrialorders')->where('id=' . $id)->getField('uid');
            	$gid = M('tbtrialorders')->where('id=' . $id)->getField('gid');
	            $tel = M('tbinfo')->where('uid=' . $uid)->getField('phone');
	            $openid = M('apilogin')->where('uid=' . $uid)->getField('webid');
                wxSendMsg($openid, $gid);
                if($tel){
                 	sendTemplateSMS($tel, array($gid), "213574",'8aaf07085ea24877015ecb8a606a0e1b');
               	}
	        	$this->ajaxReturn(array('code'=>1,'msg'=>'确认开奖成功'));
	        }else{
	        	$this->ajaxReturn(array('code'=>0,'msg'=>'确认开奖失败'));
	        }
		}else{
			$this->ajaxReturn(array('code'=>0,'msg'=>'信息错误'));
		}
		
    }
    
    /**
     * 随机开奖
     * @param int $gid 商品ID
     * @param int $num 需开奖数
     * @return array return array('ID' => 'comfirm')
     */
    private function random($gid,$num){
    	
    	if (!empty($gid) && $num >= 1){
    		$data = D('tbtrialorders')->order(array('comfirm' => 'DESC', 'id' => 'ASC'))->field(array('id', 'uid', 'comfirm'))
							    	  ->where(array('gid' => $gid, 'comfirm' => array('gt',98)))->limit($num)
							    	  ->select();
			if (!empty($data)){
				$won_type = array(99 => 0, 101 => 1, 102 => 2);
				foreach ($data as $val){$where .= ' WHEN '.$val['id'].' THEN '.$won_type[$val['comfirm']];}//第几次中奖
				
				$ids = implode(',', my_array_column($data, 'id'));
				$set = 'comfirm = 0, apply_time = "'.date('Y-m-d H:i:s', time()).'",';
				$msg = D('tbtrialorders')->execute('UPDATE tfx_tbtrialorders SET '.$set.' won_type = CASE id '.$where.' END WHERE id IN ('.$ids.')');
    			if (!empty($msg)) $msg = explode(',', $ids);
    			return $msg;//成功则返回中奖者ID和comfirm
    		}
    	}
    }
    
    /**
     * 指定中奖
     * @param int gid 商品ID
     * @param int id  试用申请ID
     */
    private function lotteryOne($gid,$id){
    	if (!empty($gid) && !empty($id)){
    		$where = array('gid' => $gid, 'id' => $id);
    		$data = D('tbtrialorders')->field(array('id', 'uid', 'comfirm'))->where($where)->find();
    		if (!empty($data)){
    			$won_type = array(99 => 0, 101 => 1, 102 => 2);
    			$msg = D('tbtrialorders')->where($where)->save(array('comfirm' => 0, 'apply_time' => date('Y-m-d H:i:s', time()), 'won_type' => $won_type[$data['comfirm']]));
    			if (!empty($msg)) $msg = array($data['id'] => $data['comfirm']);
    			return $msg;//成功则返回中奖者ID和comfirm
    		}
    	}
    }
    
    /**
     * 通过概率分配任务
     * @param int gid 商品ID
     * @param array data 需要分配id集合
     * @param int count 开奖总数(今天)
     * @param json rate 概率{"chat":0.5}
     */
    private function task($gid,$data,$count,$rate){
    	if (!empty($gid) && !empty($data) && !empty($count) && !empty($rate)){
    		if (!empty($rate)){//分配概率
    			$rate = json_decode($rate,true);
    			$rate = $rate['chat'];
    		} else {
    			$rate = 0;
    		}
    		$num = ceil($count*$rate);//今天需要的数量
    		$taskCount = D('tbtrialorders')->where("DATE_FORMAT(apply_time,'%Y-%m-%d') = ".strtotime(date('Y-m-d', time()))." and gid=".$gid)->count();//今天己分配过任务的数量
    		if ($taskCount < $num){//任务未完成
    			$num = $num - $taskCount;//任务需要的人数
    			$ids = array_slice($data,0,$num);//中奖ID
    			$msg = D('tbtrialorders')->where('id in ('.implode(',', $ids).')')->save(array('is_talk' => 1));
    			return $msg;
    		} else {
    			return 1;//任务完成
    		}
    	}
    }
    
    /**
     * 获取今天的开奖信息
     * @param int gid 试用商品ID
     * @param int count 申请总人数
     * @param int num 需要开奖的数量
     * @return array return array('今天剩余可开数','事实上能开奖的数量', '试用商品信息');
     */
    private function lotteryTodayMessage($gid=0,$count=1,$num){
    	$goods = D('tbgoods_activity')->where('id='.$gid)->find();//检测商品是否存在,若存在则返回相关信息
    	if (!empty($goods)){
    		$goods['days'] = json_decode($goods['days'],true);
    		$today = strtotime(date('Y-m-d', time()));//今日凌晨零点的时间戳
    		$today = array_filter($goods['days'], function($data) use ($today) {return ($data['time'] == $today);});
    		if (!empty($today)){//今开有奖可开
    			$today = array_shift($today);//获取今天的数据
    			$goods['days'] = $today;
    			$todayCount = D('tbtrialorders')->where("comfirm < 98 and DATE_FORMAT(apply_time,'%Y-%m-%d') = '".date('Y-m-d',time())."' and gid=".$gid)->count('id');//今天己开奖数
    			$today['num'] = $today['num'] - $todayCount;//今天剩余开数
    			if ($today['num'] < 0) $today['num'] = 0;//防止异常
    			
    			$rate = !empty($today['rate']) ? $today['rate']:100;//概率
    			$todayNum = ceil($count*($rate*0.01));//通过概率计算,可用于开奖的最大数(理伦值)
    			if ($todayNum > $today['num']) $todayNum = $today['num'];//事实上能用于开奖的数量
    			if (!empty($num) && $num <= $todayNum) $todayNum = $num;//允许用户开奖的数量(实际值)
    			return array('today' => $today['num'], 'user' => $todayNum, 'goods' => $goods);
    		}
    	}
    }
}
?>