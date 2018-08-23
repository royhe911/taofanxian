<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/4/9
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;
use Common\Util\Util;

class BrushController extends BaseController {

	public function index(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        $zz=D('account')->field('id,realname')->where('role = 3 and msg = 0')->select();
        $this->assign('zz',$zz);
        $sd_realname = trim(I('get.sd_realname'));
        $zhanz       = I('get.zhanz',0,'int');
        $page = I('get.page',1);
        $page = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',10);
        $num = $l_num = $num_s = 0;
        if($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 4){
            //超级管理员看到所有的刷单员
            $where='a.role =2';

        }elseif($_SESSION['user']['role'] == 3){
        	$num = M('account')->where('id='.$_SESSION['user']['id'])->getField('num');
        	$l_num = M('account')->where('id='.$_SESSION['user']['id'])->getField('l_num');
        	$m_num = M('account')->where('id='.$_SESSION['user']['id'])->getField('m_num');
        	
        	$query = 'SELECT count(id) as count FROM erp_task WHERE uid in(SELECT id FROM erp_account WHERE user_id='.$_SESSION['user']['id'];
        	$query .= ' ) and addtime <UNIX_TIMESTAMP() and endtime > UNIX_TIMESTAMP() and tb_item is not null and price <100';
        	$data = M()->Query($query);
        	$num_ye = $data[0]['count'];
        	$num_s = $num-$num_ye;
        	
        	$query = 'SELECT count(id) as count FROM erp_task WHERE uid in(SELECT id FROM erp_account WHERE user_id='.$_SESSION['user']['id'];
        	$query .= ' ) and addtime <UNIX_TIMESTAMP() and endtime > UNIX_TIMESTAMP() and tb_item is not null and price >=300';
        	$data = M()->Query($query);
        	$lum_ye = $data[0]['count'];
        	$l_num_s = $l_num-$lum_ye;  //剩余大额单总量
        	
        	$query = 'SELECT count(id) as count FROM erp_task WHERE uid in(SELECT id FROM erp_account WHERE user_id='.$_SESSION['user']['id'];
        	$query .= ' ) and addtime <UNIX_TIMESTAMP() and endtime > UNIX_TIMESTAMP() and tb_item is not null and price >=100 and price<300';
        	$data = M()->Query($query);
        	$mum_ye = $data[0]['count'];
        	$mum_s = $m_num-$mum_ye;
            //站长看到所属自己的刷单员
            $where='a.role=2 and a.user_id ='.$_SESSION['user']['id'];

        }
        if(!empty($sd_realname)) $where .= " and a.realname like '%$sd_realname%'";
        if(!empty($zhanz))       $where .= " and a.user_id=".$zhanz;
        $count=D('Account a')->where($where)->count();
	    $shuadanyuan=D('Account')
            ->alias('a')
            ->field('a.*,b.realname as user_name')
            ->join('left join erp_account b on a.user_id=b.id')
            ->where($where)
            ->order('a.msg,a.id desc')
            ->limit($page*$page_size,$page_size)
            ->select();
	    foreach ( $shuadanyuan as $key =>$value)
	    {
	    	$num_f += $value['num'];
	    	$mum_f += $value['m_num'];
	    	$lum_f += $value['l_num'];
	        $shuadanyuan[$key]['wechat'] = json_decode($value['wechat'],true);
	        $nex_num = M('task')->where("is_spe=1 and addtime < ".time()." and endtime >".time()." and uid =".$value['id'])->count();
	        $shuadanyuan[$key]['nex_num'] = $nex_num;
	        $lex_num = M('task')->where("is_spe=2 and addtime < ".time()." and endtime >".time()." and uid =".$value['id'])->count();
	        $shuadanyuan[$key]['lex_num']= $lex_num;
	        $mex_num = M('task')->where(" price >=100 and price<300 and addtime < ".time()." and endtime >".time()." and uid =".$value['id'])->count();
	        $shuadanyuan[$key]['mex_num']= $mex_num;
        }
        $num_f = M('account')->field('sum(num) as num')->where('user_id='.$_SESSION['user']['id'])->select();
        $num_f = $num_f[0]['num'];
        $mum_f = M('account')->field('sum(m_num) as num')->where('user_id='.$_SESSION['user']['id'])->select();
        $mum_f = $mum_f[0]['num'];
        $num_f = $num - $num_f;
        $mum_f = $m_num - $mum_f;
        $num_l = M('account')->field('sum(l_num) as num')->where('user_id='.$_SESSION['user']['id'])->select();
        $num_l = $num_l[0]['num'];
        $lum_f = $l_num - $num_l;
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->assign('count', $count);
        $this->assign('num', $num);//小额单总额
        $this->assign('num_s', $num_s);//剩余小额单总额
        $this->assign('l_num', $l_num);// 大额单总量
        $this->assign('mum_s', $mum_s);//剩余中额单
        $this->assign('l_num_s', $l_num_s);//剩余大额单
        $this->assign('m_num', $m_num);//中额单
        $this->assign('num_f', $num_f);//小额单已分配数量
        $this->assign('mum_f', $mum_f);//中额单已分配数量
        $this->assign('lum_f', $lum_f);//大额单已分配数量
        $this->assign('sd_realname', $sd_realname);
	    $this->assign('info',$shuadanyuan);
		$this->display();
	}

	//添加刷单员
    public function add(){
        if(IS_GET){

            $this->display();
        }else{
            $role=intval($_POST['role']);
            $realname=$_POST['realname'];
            $phone=$_POST['phone'];
            $password=md5($_POST['password']);
            $password2=md5($_POST['password2']);
            foreach ( $_POST['wechat'] as $key =>$value){
                if (preg_match("/[\x7f-\xff]/", $value['num'])) {
                    $this->error('微信号不能有汉字');
                }
            }
//            $wechat=implode(',',$_POST['wechat']);
            $wechat=json_encode(I('post.wechat'));

            $qq=$_POST['qq'];
            $info=$_POST['info'];
            if  ($role != 2 || !$role) $this->error('参数错误');
            if  ($password != $password2) $this->error('两次密码不一样');
            $res=D('account')->where("phone='{$phone}' or name='{$phone}'")->find();
            if  ($res) $this->error('手机号存在');
//            $qqcheck=D('account')->where("qq='{$qq}'")->find();
//            if($qqcheck) $this->error('QQ号存在');
            if  (!$wechat) $this->error('微信必须填写');
            if  (!preg_match('/^[0-9a-zA-Z]*$/',$password)) $this->error('密码只能为数字字母组合!');
            $data=array(
                'role'=>$role,
                'realname'=>$realname,
                'phone'=>$phone,
                'name'=>$phone,
                'password'=>$password,
                'wechat'=>$wechat,
                'addtime'=>time(),
                'qq'=>$qq,
                'info'=>$info,
                'ip'=>get_client_ip(),
                'user_id'=>$_SESSION['user']['id'],
                'check'  =>0,
            );
            $res=D('account')->add($data);
            if(!$res){
                $this->error('添加失败');

            }
            $this->success('添加成功,等待审核','javascript:parent.location.reload();');


        }

    }
    //刷单员编辑
    public function edit(){
        if(IS_GET){


            $id=intval(I('get.id'));
            if($id <=0 ) exit('参数错误');
            $user_info=D('account')->where('id='.$id)->find();
//            $user_info['wechat']=explode(',',$user_info['wechat']);
            $user_info['wechat']=json_decode($user_info['wechat'],true);
            $count=count($user_info['wechat']);
            $this->assign('count',$count);
            $this->assign('info',$user_info);
            $this->display();


        }else{

            $role=intval($_POST['role']);
            $realname=$_POST['realname'];
            $phone=$_POST['phone'];
            $password=$_POST['password'];
            $password2=$_POST['password2'];
//            $wechat=implode(',',$_POST['wechat']);
            $wechat=json_encode($_POST['wechat']);
            $qq=$_POST['qq'];
            $info=$_POST['info'];
            $id=$_POST['id'];

            if  ($id <= 0  || !$role) $this->error('参数错误');

            $user=D('Account')->where('id='.$id)->find();
            if  ($password != $password2) $this->error('两次密码不一样');

            if  ($role != 2 || !$role) $this->error('参数错误');
            if  ($password != $password2) $this->error('两次密码不一样');
            $res1=D('account')->where("phone='{$phone}' and id !='{$id}'")->find();
            $res2=D('account')->where("name='{$phone}' and id !='{$id}'")->find();
            if  ($res1 || $res2) $this->error('手机号存在');
            if  (!$wechat) $this->error('微信必须填写');
            if  (!preg_match('/^[0-9a-zA-Z]*$/',$password)) $this->error('密码只能为数字字母组合!');

            $data=array(
                'role'=>$role,
                'realname'=>$realname,
                'phone'=>$phone,
                'name'=>$phone,
                'wechat'=>$wechat,
                'addtime'=>time(),
                'qq'=>$qq,
                'info'=>$info,
                'ip'=>get_client_ip(),
                'user_id'=>$user['user_id'],
                'check'  =>0
            );
            if($password != $user['password']){
                //密码变化了
                $data['password']=md5($password);
            }
            $res=D('account')->where('id='.$id)->save($data);
            if(!$res){
                $this->error('修改失败');
            }
            $this->success('修改成功','javascript:parent.location.reload();');


        }
    }

    //禁用
    public function state(){
        if (IS_AJAX){
            $id = I('post.id',0,'intval');//信息ID
            $type=I('post.type',0,'intval');
            if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));

            if($type == 1){
                //禁用
                $res=D('Account')->where('id='.$id)->setField('msg',1);
            }else{
                //启用
                $res=D('Account')->where('id='.$id)->setField('msg',0);
            }

           if($res){
               $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
           }else{
               $this->ajaxReturn(array('msg' => 0, 'info' => '操作失败'));
           }
        }
    }
    //新增的刷单员审核
    public function shenhe(){
        if(IS_AJAX){
            $id = I('post.id',0,'intval');//信息ID
            $type=I('post.type',2,'intval');
            if (empty($id) || empty($type)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));

            if($type == 1){
                //1代表通过
                $res = D('account')->where('id='.$id)->setField('check',1);
                if($res){
                    $this->ajaxReturn(array('msg' => 1, 'info' => '审核通过'));
                }else{
                    $this->ajaxReturn(array('msg' => 0, 'info' => '操作失败'));
                }

            }elseif($type == 2){
                //0代表不通过
                $res = D('account')->where('id='.$id)->setField('check',2);
                if($res){
                    $this->ajaxReturn(array('msg' => 1, 'info' => '审核不通过'));
                }else{
                    $this->ajaxReturn(array('msg' => 0, 'info' => '操作失败'));
                }

            }
        }
    }

    //删除刷单员
    public function del(){
        $user_id=I('post.id',0,'intval');
        if (empty($user_id)) $this->ajaxReturn(array('msg' => 3, 'info' => '该信息不存在'));

        $res=D('Account')->where('id='.$user_id)->delete();

        if (!$res) $this->ajaxReturn(array('msg' => 0, 'info' => '删除失败'));

        $this->ajaxReturn(array('msg' => 1, 'info' => '删除成功'));
    }

    //刷单记录
    public function brushtotal(){
        $page      = I('get.page',1);
        $page      = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',10);
        $user_id   = I('get.id',0,'intval');
        $starttime = I('get.time');           //开始时间
        $endtime   = I('get.endtime');        //结束时间

        $where     = "a.uid=".$user_id." and a.tb_item is not null";

        if(!empty($starttime)) {
            $starttime=strtotime($starttime. "00:00:00");
            $where .= " and a.addtime >= {$starttime}";
        }
        if(!empty($endtime))   {
            $endtime=strtotime($endtime. "23:59:59");
            $where .= " and a.addtime <= {$endtime}";
        }
        $count=D('task a')
            ->where($where)
            ->count();
        $task=D('task a')
            ->field('a.*,b.realname,c.goods_title,c.goods_url,d.shopname,if(a.empty_cost > 0,a.empty_cost,a.redbag) as order_cost')
            ->join('left join erp_account b on a.uid=b.id left join erp_product c on a.gid=c.id left join erp_user d on c.user_id=d.uid')
            ->where($where)
            ->limit($page*$page_size,$page_size)
            ->order('a.id desc')
            ->select();
        //小额单
        $count_s=D('task a')
            ->where($where.' and a.actual_price < 100')
            ->count();
        //中额单
        $count_m=D('task a')
            ->where($where.' and a.actual_price >= 100 and a.actual_price < 300')
            ->count();
        //大额单
        $count_l=D('task a')
            ->where($where.' and a.actual_price >= 100 and a.actual_price >= 300')
            ->count();
        //退款订单
        //退款订单总量
        $count_refund=D('task a')
            ->where($where.' and a.abn = 2 and a.abn_status = 4')
            ->count();
        $count_refund_s=D('task a')
            ->where($where.' and a.abn = 2 and a.abn_status = 4 and a.actual_price <100')
            ->count();
        $count_refund_m=D('task a')
            ->where($where.' and a.abn = 2 and a.abn_status = 4 and a.actual_price >= 100 and a.actual_price < 300')
            ->count();
        $count_refund_l=D('task a')
            ->where($where.' and a.abn = 2 and a.abn_status = 4 and a.actual_price >= 300')
            ->count();
        //异常订单
        //异常订单总量
        $count_abn=D('task a')
            ->where($where.' and a.abn = 1 ')
            ->count();
        $count_abn_s=D('task a')
            ->where($where.' and a.abn = 1 and a.actual_price <100')
            ->count();
        $count_abn_m=D('task a')
            ->where($where.' and a.abn = 1 and a.actual_price >= 100 and a.actual_price < 300')
            ->count();
        $count_abn_l=D('task a')
            ->where($where.' and a.abn = 1 and a.actual_price >= 300')
            ->count();
        if ( $starttime )  $this->assign('starttime',date("Y-m-d",$starttime));
        if ( $endtime )    $this->assign('endtime',date("Y-m-d",$endtime));

        $this->assign('data',$task);
        $this->assign('count',$count);                      //任务总量
        $this->assign('count_s',$count_s);                  //小额单
        $this->assign('count_m',$count_m);                  //中额单
        $this->assign('count_l',$count_l);                  //大额单

        $this->assign('count_refund',$count_refund);        //退款单总量
        $this->assign('count_refund_s',$count_refund_s);    //退款小额单
        $this->assign('count_refund_m',$count_refund_m);    //退款中额单
        $this->assign('count_refund_l',$count_refund_l);    //退款大额单

        $this->assign('count_abn',$count_abn);              //异常单总量
        $this->assign('count_abn_s',$count_abn_s);          //异常小额单
        $this->assign('count_abn_m',$count_abn_m);          //异常中额单
        $this->assign('count_abn_l',$count_abn_l);          //异常大额单
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->display();

    }

    //刷单汇总
    public function ordertotal(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        if(IS_GET){

            if(intval($_SESSION['user']['role']) == 2) {
                $wangwang=trim(I('get.wangwang'));
                if(!empty($wangwang)){
                    $where = "a.wangwang like '%{$wangwang}%'";

                    $task=D('task')
                        ->alias('a')
                        ->field('a.*,b.realname,c.goods_title,c.goods_url,d.shopname')
                        ->join('left join erp_account b on a.uid=b.id left join erp_product c on a.gid=c.id left join erp_shop d on c.shop_id=d.id')
                        ->where($where)
                        ->select();
                    $count=count($task);
                    $this->assign('count', $count);
                    $this->assign('data',$task);
                    $this->assign('wangwang',$wangwang);
                }
                //刷单员页面 默认没有数据
                $this->display();exit;
            }

            //查询所有的站长
            $zhanzhang=D('account')->field('id,realname')->where('role=3')->select();
            //查询所有业务员
            $yw=D('account')->field('id,info')->where('role=6')->select();
            $this->assign('yw',$yw);
            $this->assign('zhanz',$zhanzhang);

            $page      = I('get.page',1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            $wangwang  = trim(I('get.wangwang'));
            $brushname = trim(I('get.brushname'));
            $starttime = I('get.time');
            $endtime   = I('get.endtime');
            $type      = I('get.type');
            if($_SESSION['user']['role'] == 3){
                //站长看到自己属下的刷单员汇总
                //查询当前站长所有的刷单员id
                $shuadanyuan=D('account')->field('id')->where('user_id='.$_SESSION['user']['id'])->select();
                if(empty($shuadanyuan)){
                    $this->display();
                    exit;
                }
                $array=array();
                foreach ($shuadanyuan as $key => $value){
                    $array[$key]=$value['id'];
                }
                $shuadanyuanid=implode(',',$array);
                $where="a.uid in ({$shuadanyuanid}) and a.tb_item is not null";
            }elseif ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 4){
                //管理员看到所有刷单员汇总
                $where="a.tb_item is not null";
                $zhanz=I('get.zhanz',0,'intval');  //选择站长
                $yw=I('get.choiceyw',0,'intval');  //选择业务员
                if(!empty($zhanz))  $where .= " and e.id='{$zhanz}'";
                if(!empty($yw))     $where .= " and a.tid=".$yw;
            }

            if( $brushname )   $where .= " and b.realname like '%{$brushname}%'";
            if( $wangwang )    $where .= " and a.wangwang like '%{$wangwang}%'";
            $where_b=$where;

            if(!empty($starttime)) {
                $starttime=strtotime($starttime. "00:00:00");
                $where .= " and a.addtime >= {$starttime}";
            }
            if(!empty($endtime))   {
                $endtime=strtotime($endtime. "23:59:59");
                $where .= " and a.addtime <= {$endtime}";
            }
            if($type != "all" && (empty($ordernum) && empty($wangwang))){
                $time=timeInterval(time());
                $starttime=$time['starttime'];
                $endtime  =$time['endtime'];
                $where .= " and a.addtime  between {$starttime} and {$endtime}";
            }
//            if(!empty($timeSearch) || $timeSearch!=0)
//            {
//                $starttime=strtotime($timeSearch." 00:00:00");
//                $endtime=$starttime + 86399;
//            }elseif($type != 'all' && (empty($wangwang) && empty($brushname))) {
//                $time=timeInterval(time());
//                $starttime=$time['starttime'];
//                $endtime  =$time['endtime'];
//            }
//            if($starttime)
//            {
//                $where .= " and a.addtime  between {$starttime} and {$endtime}";
//            }

            $count=D('task a')
                ->join('left join erp_account b on a.uid=b.id')
                ->join('left join erp_account e on b.user_id=e.id')
                ->where($where)
                ->count();




            $task=D('task')
                ->alias('a')
                ->field('a.*,b.realname,c.goods_title,c.goods_url,d.shopname,e.id zz_id,f.realname as zz_realname')
                ->join('left join erp_account b on a.uid=b.id ')
                ->join('left join erp_product c on a.gid=c.id ')
                ->join('left join erp_shop d on c.shop_id=d.id')
                ->join('left join erp_account e on b.user_id=e.id')
                ->join('left join erp_account f on e.id=f.id')
                ->where($where)
                ->order('a.edittime desc')
                ->limit($page*$page_size,$page_size)
                ->select();
            $money = $capital = $comm = $other =0;
            
             $taska=D('task')
                ->alias('a')
                 ->field('sum(IFNULL(if(a.actual_price,a.actual_price,price),0)) as actual_price ,sum(IFNULL(a.commision,0)) as commision,sum(IFNULL(a.empty_cost,0)) as empty_cost,sum(IFNULL(a.redbag,0)) as redbag')
                ->join('left join erp_account b on a.uid=b.id left join erp_product c on a.gid=c.id left join erp_shop d on c.shop_id=d.id')
                 ->join('left join erp_account e on b.user_id=e.id')
                ->where($where)
                ->select();

            if($type == 'all' && empty($timeSearch)){

                $redbag_b=$taska[0]['redbag'];

            }else{
                $taskb=D('task')
                    ->alias('a')
                    ->field('sum(IFNULL(a.redbag,0)) as redbag_b')
                    ->join('left join erp_account b on a.uid=b.id left join erp_product c on a.gid=c.id left join erp_shop d on c.shop_id=d.id')
                    ->join('left join erp_account e on b.user_id=e.id')
                    ->where($where_b." and redbagtime between {$starttime} and {$endtime}")
                    ->select();
                $redbag_b=$taskb[0]['redbag_b'];
            }
			/*foreach($taska as $v)
			{
				$capital += $v['actual_price'];// 本金
				$comm += $v['commision'];//佣金
				$other += $v['empty_cost'];//其他
			}*/

            $capital = $taska[0]['actual_price'];// 本金
			$comm = $taska[0]['commision'];//佣金
			$redbag = $taska[0]['redbag'];//红包
			
            $money = $capital+$comm+$redbag;
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('money',$money);
            $this->assign('capital',$capital);
            $this->assign('comm',$comm);
            $this->assign('redbag',$redbag);
            $this->assign('redbag_b',$redbag_b);
            $this->assign('data',$task);
            if ( $starttime )  $this->assign('starttime',date("Y-m-d",$starttime));
            if ( $endtime )  $this->assign('endtime',date("Y-m-d",$endtime));
            $this->assign('wangwang',$wangwang);
            $this->assign('brushname',$brushname);
            $this->display();
        }


    }


    public function excel(){
        $type=I('get.type',0,'intval');
       //按需求下载
        $starttime=I('get.time');
        $endtime  =I('get.endtime');
        $brushname=trim(I('get.brushname'));
        $wangwang=trim(I('get.wangwang'));
        $zhanz=I('get.zhanz');
        if(empty($starttime)){
            $this->error('搜索开始时间必须选择');
            exit;
        }
        //搜索下载日期，如果结束日期存在，取结束日期，不存在取当天
        $endtime_s= $endtime ? strtotime($endtime. "23:59:59") : strtotime(date('Y-m-d 23:59:59',time()));;

        $time_difference=intval(ceil(($endtime_s - strtotime($starttime. "00:00:00")) / (60 * 60 * 24)));  //天数
        if($time_difference > 30){
            $this->error('下载表格时间区间不得超过30天');
            exit;
        }
        /***************************/
        //权限判断
        if($_SESSION['user']['role'] == 1 || $_SESSION['user']['role']== 4){
            //超级管理员  下载所有的
            $where="a.tb_item is not null";
            if(!empty($zhanz))  $where .= " and e.id='{$zhanz}'";

        }elseif ($_SESSION['user']['role'] == 3){
            //站长下载
            $shuadanyuan=D('account')->field('id')->where('user_id='.$_SESSION['user']['id'])->select();
            if(empty($shuadanyuan)){
                $this->error('参数错误');
                return false;
            }
            $array=array();
            foreach ($shuadanyuan as $key => $value){
                $array[$key]=$value['id'];
            }
            $shuadanyuanid=implode(',',$array);
            $where="a.uid in ({$shuadanyuanid}) and a.tb_item is not null";
        }


        if(!empty($starttime)) {
            $starttime=strtotime($starttime. "00:00:00");
            $where .= " and a.addtime >= {$starttime}";
        }
        if(!empty($endtime))   {
            $endtime=strtotime($endtime. "23:59:59");
            $where .= " and a.endtime <= {$endtime}";
        }
//        if($type != 'all' &&(empty($starttime) && empty($endtime))){
//            $time=timeInterval(time());
//            $starttime=$time['starttime'];
//            $endtime  =$time['endtime'];
//            $where .= " and a.addtime  between {$starttime} and {$endtime}";
//        }


        if(!empty( $brushname))  $where .=" and b.realname like '%{$brushname}%'";
        if(!empty($wangwang))    $where .=" and a.wangwang like '%{$wangwang}%'";
//        if(!empty( $starttime))  $where .=" and a.edittime  between {$starttime} and {$endtime}";
        /***************************/
        $task=D('task a')
            ->field('FROM_UNIXTIME(a.edittime) as time,b.realname,d.shopname,a.keyword,a.price,a.actual_price,a.tb_item,a.wangwang,a.redbag,a.commision')
            ->join('left join erp_account b on a.uid=b.id ')
            ->join('left join erp_product c on a.gid=c.id ')
            ->join('left join erp_shop d on c.shop_id=d.id')
            ->join('left join erp_account e on b.user_id=e.id')
            ->where($where)
            ->order('a.id desc')
            ->select();

        $title=array('完成时间','刷单员','店铺名','关键字','单价','下单价','订单号','旺旺号','红包','佣金');
        $fileName='订单详情';
          $this->down($title,$task,$fileName);
    }

    //下载所有
    public function excelall(){

        /***************************/
        //权限判断
        if($_SESSION['user']['role'] == 1){
            //超级管理员  下载所有的
            $where="a.tb_item is not null";


        }elseif ($_SESSION['user']['role'] == 3){
            $shuadanyuan=D('account')->field('id')->where('user_id='.$_SESSION['user']['id'])->select();
            if(empty($shuadanyuan)){
                $this->error('参数错误');
                return false;
            }
            $array=array();
            foreach ($shuadanyuan as $key => $value){
                $array[$key]=$value['id'];
            }
            $shuadanyuanid=implode(',',$array);
            $where="a.uid in ({$shuadanyuanid}) and a.tb_item is not null";


        }
        /***************************/
        $task=D('task')
            ->alias('a')
            ->field('FROM_UNIXTIME(a.edittime) as time,b.realname,d.shopname,a.keyword,a.price,a.actual_price,a.tb_item,a.wangwang,a.redbag,a.commision')
            ->join('left join erp_account b on a.uid=b.id left join erp_product c on a.gid=c.id left join erp_shop d on c.shop_id=d.id')
            ->where($where)
            ->order('a.edittime desc')
            ->select();

//        $data=array();
//        foreach ($task as $key =>$value){
//            $data[$key]['edittime']=date('Y-m-d H:i:s',$value['edittime']);
//            $data[$key]['realname']=$value['realname'];
//            $data[$key]['shopname']=$value['shopname'];
//            $data[$key]['keyword']=$value['keyword'];
//            $data[$key]['price']=$value['price'];
//            $data[$key]['actual_price']=$value['actual_price'];
//            $data[$key]['tb_item']=$value['tb_item'];
//            $data[$key]['wangwang']=$value['wangwang'];
//            $data[$key]['commision']=$value['commision'];
//
//        }
        $title=array('完成时间','刷单员','店铺名','关键字','单价','下单价','订单号','旺旺号','红包','佣金');
        $fileName='所有订单详情';
        $this->down($title,$task,$fileName);
    }
    
    //管理员分配小额单数量
    public function allots(){
    	if(IS_AJAX)
    	{
    		$id = I('post.id','');
    		$limit = I('post.limit','');
    		$limit = $limit==0?'':$limit;
    		$limit_now = M('account')->where('id='.$id)->getField('num');
    		if(empty($id))
    		{
    			$this->ajaxReturn(array('msg' => 2, 'info' => '数据错误，请重试'));die();
    		}
    		$data = array('num'=>$limit);
    		if( false === M('account')->where('id='.$id)->save($data))
    		{
				$this->ajaxReturn(array('msg' => 2, 'info' => '保存失败，请重试'));
			}else
			{
				if($limit_now>$limit)
				{
					M('account')->where('user_id='.$id)->save(array('num'=>0));
				}
				$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
			}
			
    		
    	}
    	$price = 'and price<100';
    	$all_num = M('task')->where("status=1 and xiajia=0 ".$price." and addtime < ".time()." and endtime >".time())->count();
    	$data = M('account')->where('role=3')->select();
        $this->assign('data',$data);
        $this->assign('all_num',$all_num);
        $this->display();

    }
    
	//站长分配小额单数量
    public function allots_nex(){
    	if(IS_AJAX)
    	{
    		$id = I('post.id','');
    		$limit = I('post.limit','');
    		$limit = $limit==0?'':$limit;
    		if(empty($id))
    		{
    			$this->ajaxReturn(array('msg' => 2, 'info' => '数据错误，请重试'));
    		}
    		
    		$query = 'SELECT sum(num) as num FROM erp_account where user_id ='.$_SESSION['user']['id'].' and id!='.$id;
	    	$nex_num = M()->Query($query);
    		$nex_num = $nex_num[0]['num'];
    		
    		// $num = M('account')->where('id='.$_SESSION['user']['id'])->getField('num');
    		
    		// if($num<($nex_num+$limit))
    		// {
    		// 	$this->ajaxReturn(array('msg' => 2, 'info' => '所剩配额不足'));die();
    		// }
    		$data = array('num'=>$limit);
    		if( false === M('account' )->where('id='.$id)->save($data))
    		{
				$this->ajaxReturn(array('msg' => 2, 'info' => '保存失败，请重试'));
			}else
			{
				$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
			}
    	}

    }
    
	//管理员分配大额单数量
    public function allotl(){
    	if(IS_AJAX)
    	{
    		$id = I('post.id','');
    		$limit = I('post.limitbig','');
    		$limit = $limit==0?'':$limit;
    		if(empty($id))
    		{
    			$this->ajaxReturn(array('msg' => 2, 'info' => '数据错误，请重试'));die();
    		}
			$limit_now = M('account')->where('id='.$id)->getField('l_num');
    		$data = array('l_num'=>$limit);
    		if( false === M('account')->where('id='.$id)->save($data))
    		{
				$this->ajaxReturn(array('msg' => 2, 'info' => '保存失败，请重试'));
			}else
			{
				if($limit_now>$limit)
				{
					M('account')->where('user_id='.$id)->save(array('l_num'=>0));//清除已有的分配额
				}
				$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
			}
			
    		
    	}
    	$price = 'and price>=300';
    	$all_num = M('task')->where("status=1  and xiajia=0 ".$price." and addtime < ".time()." and endtime >".time())->count();
    	$data = M('account')->where('role=3')->select();
        $this->assign('data',$data);
        $this->assign('all_num',$all_num);
        $this->display();

    }
    
	//站长分配大 额单数量
    public function allotl_nex(){
    	if(IS_AJAX)
    	{
    		$id = I('post.id','');
    		$limit = I('post.limit','');
    		$limit = $limit==0?'':$limit;
    		if(empty($id))
    		{
    			$this->ajaxReturn(array('msg' => 2, 'info' => '数据错误，请重试'));
    		}
    		
    		$query = 'SELECT sum(l_num) as num FROM erp_account where user_id ='.$_SESSION['user']['id'].' and id!='.$id;
	    	$nex_num = M()->Query($query);
    		$nex_num = $nex_num[0]['num'];

    		$data = array('l_num'=>$limit);
    		if( false === M('account' )->where('id='.$id)->save($data))
    		{
				$this->ajaxReturn(array('msg' => 2, 'info' => '保存失败，请重试'));
			}else
			{
				$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
			}
    	}
    }
    
     //管理员分配中额单数量
    public function allotm(){
    	if(IS_AJAX)
    	{
    		$id = I('post.id','');
    		$limit = I('post.limit','');
    		$limit = $limit==0?'':$limit;
    		if(empty($id))
    		{
    			$this->ajaxReturn(array('msg' => 2, 'info' => '数据错误，请重试'));die();
    		}
    		$limit_now = M('account')->where('id='.$id)->getField('m_num');
    		$data = array('m_num'=>$limit);
    		if( false === M('account')->where('id='.$id)->save($data))
    		{
				$this->ajaxReturn(array('msg' => 2, 'info' => '保存失败，请重试'));
			}else
			{
				if($limit_now>$limit)
				{
					M('account')->where('user_id='.$id)->save(array('m_num'=>0));
				}
				$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
			}
			
    		
    	}
    	$price = 'and price>=100 and price<300';
    	$all_num = M('task')->where("status=1  and xiajia=0 ".$price." and addtime < ".time()." and endtime >".time())->count();
    	$data = M('account')->where('role=3')->select();
        $this->assign('data',$data);
        $this->assign('all_num',$all_num);
        $this->display();

    }
    
	//站长分配中额单数量
    public function allotm_nex()
    {
    	if(IS_AJAX)
    	{
    		$id = I('post.id','');
    		$limit = I('post.limit','');
    		$limit = $limit==0?'':$limit;
    		if(empty($id))
    		{
    			$this->ajaxReturn(array('msg' => 2, 'info' => '数据错误，请重试'));
    		}
    		
    		$query = 'SELECT sum(m_num) as num FROM erp_account where user_id ='.$_SESSION['user']['id'].' and id!='.$id;
	    	$nex_num = M()->Query($query);
    		$nex_num = $nex_num[0]['num'];
    		
    		// $num = M('account')->where('id='.$_SESSION['user']['id'])->getField('m_num');
    		
    		// if($num<($nex_num+$limit))
    		// {
    		// 	$this->ajaxReturn(array('msg' => 2, 'info' => '所剩配额不足'));die();
    		// }
    		$data = array('m_num'=>$limit);
    		if( false === M('account' )->where('id='.$id)->save($data))
    		{
				$this->ajaxReturn(array('msg' => 2, 'info' => '保存失败，请重试'));
			}else
			{
				$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
			}
    	}
    }
    // 短连接申请
	public function applylink(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
		if(IS_GET){
            //查询所有的站长
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            
            $where = 'a.is_short=1 and a.addtime<'.time().' and a.endtime>'.time();
            if($_SESSION['user']['role'] == 3)
            {
                //站长看到自己属下的刷单员汇总
                $where .= ' and b.user_id='.$_SESSION['user']['id'];
            }
            
            $count=D('task a')
                ->join('left join erp_account b on a.uid=b.id')
                ->where($where)
                ->count();


            $task=D('task')
                ->alias('a')
                ->field('a.*,b.realname,c.goods_title,c.goods_pic,c.goods_url,d.shopname')
                ->join('left join erp_account b on a.uid=b.id left join erp_product c on a.gid=c.id left join erp_shop d on c.shop_id=d.id')
                ->where($where)
                ->limit($page*$page_size,$page_size)
                ->select();
            
            
			/*foreach($taska as $v)
			{
				$capital += $v['actual_price'];// 本金
				$comm += $v['commision'];//佣金
				$other += $v['empty_cost'];//其他
			}*/

            $capital = $taska[0]['actual_price'];// 本金
			$comm = $taska[0]['commision'];//佣金
			$redbag = $taska[0]['redbag'];//红包
			
            $money = $capital+$comm+$redbag;
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count', $count);
            $this->assign('task', $task);
            $this->display();
        }
	} 	
	// 短连接申请审核
//	public function is_sure()
//	{
//		$task_id = I('post.task_id','');//任务ID
//		$type = I('post.type',1);//默认拒绝
//    	if(empty($task_id)) $this->ajaxReturn(array('msg' => 2, 'info' => '任务不存在'));
//    	if($type==2)
//    	{
//    		$array = array('is_short'=>3);
//    		$res = M('task')->where('id='.$task_id)->setField($array);
//    		if($res)
//	    	{
//	    		$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
//	    	}else
//	    	{
//	    		$this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));
//	    	}
//    	}elseif($type==1)
//    	{
//    		$gid = M('task')->where('id='.$task_id)->getField('gid');
//    		$goods_url =  htmlspecialchars_decode(M('product')->where('id='.$gid)->getField('goods_url'));
//
//    		if(strpos($goods_url,'&id=') !==false)
//    		{
//			 	$str = substr($goods_url,(strpos($goods_url,'&id=')+4));
//			 	if(strpos($str,'&') !==false)
//	    		{
//	    			$tb_gid = substr($str,0,strpos($str, '&'));
//	    		}else {
//	    			$tb_gid = $str;
//	    		}
//			}else{
//				$str = substr($goods_url,(strpos($goods_url,'id=')+3));
//				if(strpos($str,'&') !==false)
//	    		{
//	    			$tb_gid = substr($str,0,strpos($str, '&'));
//	    		}else {
//	    			$tb_gid = $str;
//	    		}
//			}
//
//
//    		$keyword = M('task')->where('id='.$task_id)->getField('keyword');
//
//
//    		$re = http_build_query(array('q'=>$keyword,'nid'=>$tb_gid));
//    		$url = 'https://s.m.taobao.com/h5?'.$re;
//    		$url = urlencode($url);
//    		$url = "http://api.c7.gg/api.php?format=json&url=".$url;
//
//    		$ch = curl_init();
//
//		    //设置选项，包括URL
//		    curl_setopt($ch, CURLOPT_URL, $url);
//		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//		    curl_setopt($ch, CURLOPT_HEADER, 0);
//		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
//		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//
//		    //执行并获取HTML文档内容
//		    $output = curl_exec($ch);
//
//		    //释放curl句柄
//		    curl_close($ch);
//
//		    $short_url = trim($output, "\xEF\xBB\xBF");
//		    $re = json_decode($short_url,true);
//
//
//    		$array = array('is_short'=>2,'short_url'=>$re['url']);
//    		$res = M('task')->where('id='.$task_id)->setField($array);
//
//    		if($res)
//	    	{
//	    		$this->ajaxReturn(array('msg' => 1, 'info' => '操作成功','url' => $re['url']));
//	    	}else
//	    	{
//	    		$this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));
//	    	}
//
//    	}
//	}
	
	
	
}
?>