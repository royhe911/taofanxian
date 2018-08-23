<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/3
 * Time: 10:00
 */
namespace Supporter\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;

class ActcenterController extends BaseController{
	
	public $category = array('10001' => '女装','10002' => '男装','10003' => '鞋包','10004' => '母婴','10005' => '内衣','10006' => '美食','10007' => '数码','10008' => '家居','10009' => '美妆','10010' => '户外','10011' => '配饰','10012' => '家装','10013' => '家电','10014' => '车用','10015' => '图书','10016' => '其他');    
	/**
	 * 试用商品管理
	 *
	 */
	public function index(){ 
    	
    	$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',10);
		
		$state = I('state',0,'intval');
		$gname = I('gname','','strip_tags');
		$where = array('uid' => $_SESSION['ext_user']['id'], 'trash' => 0);
		if (!empty($state)){
			if (20 == $state){//已结束试用
				$where['endtime'] = array('ELT', time());
				$where['state'] = array('EQ', 11);
			} elseif (11 == $state){//活动中试用
				$where['endtime'] = array('GT', time());
				$where['state'] = array('EQ', 11);
			} else {
				$where['state'] = $state;
			}
		} elseif (!empty($gname)){//搜索
			$where['gname'] = array('LIKE', '%'.$gname.'%');
		}
    	
		$totalCount[0] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'trash' => 0))->count();//所有
		$totalCount[2] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'state' => 2, 'trash' => 0))->count();//待完善
		$totalCount[7] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'state' => 7, 'trash' => 0))->count();//待支付
		$totalCount[10] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'state' => 10, 'trash' => 0))->count();//待发布
		$totalCount[12] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'state' => 12, 'trash' => 0))->count();//已经驳回
		$totalCount[11] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'state' => 11, 'trash' => 0, 'endtime' => array('GT', time())))->count();//活动中
		$totalCount[20] = D('tbgoods_activity')->where(array('uid' => $_SESSION['ext_user']['id'], 'state' => 11, 'trash' => 0, 'endtime' => array('ELT', time())))->count();//已结束
		$count = D('tbgoods_activity')->where($where)->count();//当前
		
		$subQuery = 'select gid, count(tb.id) as count, count(case when comfirm < 98 then comfirm end) as zj, count(case when comfirm = 31 then comfirm end) as lq from tfx_tbtrialorders as tb ';
		$subQuery .='LEFT JOIN tfx_tbgoods_activity as ta on ta.id = tb.gid WHERE ta.uid = '.intval($_SESSION['ext_user']['id']).' GROUP BY gid';
		$data = D('tbgoods_activity')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->alias('g')
		                             ->field(array('g.id', 'g.cid', 'g.uid','g.gname', 'g.image', 'g.money', 'g.num', 'g.state', 'g.note', 'g.starttime', 'g.endtime', 'g.type' => 'species','t.count', 't.zj', 't.lq', 's.shopname', 's.type'))
		                             ->join('LEFT JOIN ('.$subQuery.') AS t ON t.gid=g.id')
		                             ->join('LEFT JOIN tfx_tbsellerinfo AS s ON g.sid=s.id')
		                             ->select();
		if ($totalCount[20] >= 1) $this->refund();//退款(活动结束)
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->assign('category', $this->category);
        $this->assign('totalCount', $totalCount);
        $this->assign('gname', $gname);
        $this->assign('state', $state);
        $this->assign('data', $data);
        $this->display();
    }
    
    /**
     * 进展详情
     *
     */
    public function processdetail(){
    	$t = I('t',1,'intval');//栏目ID
    	$id = I('id',0,'intval');//试用商品ID
    	if (!empty($id)){
    		$where = array('id' => $id, 'trash' => 0);
            $info = D('TradeView')->where($where)->find();
            if (!empty($info)){
            	if (!empty($info['format'])) $info['format'] = json_decode($info['format'], true);//商品规格
            	if (4 == $t){//费用信息
            		$price = $this->totalPrice($id);//账单明细
            		$this->assign('price', $price);
            	} elseif (1 == $t){//试用详情
            		$info['count'] = $this->applyCount($id);//申请详情
            		$today = strtotime(date('Y-m-d', time()));//今日凌晨零点的时间戳
            		if (!empty($info['days'])) $info['days'] = json_decode($info['days'], true);//活动时间
            		$today = array_values(array_filter($info['days'], function($t) use($today){return $t['time'] == $today;}));//今日发放试用情况
            		$this->assign('today', $today[0]);
            		
            		if (!empty($info['tag'])){//淘口令
            			$info['tag'] = json_decode($info['tag'], true);
            			$info['tag'] = $info['tag']['app']['password'];
            		}
            	} elseif (2 == $t){//试用进展
            		$tbname = I('tbname','','strip_tags');//淘宝账号
            		
            		$page = I('get.page',1);
            		$page = $page < 1 ? 0 : $page - 1;
            		$page_size = I('get.pagesize',20);
            		
            		$where = array('gid' => $id);
            		$apply = $this->applyInfo($id);//申请人数详情
            		
            		if (!empty($tbname)) $where['tb_id'] = array('LIKE', '%'.$tbname.'%');//模糊查询
            		if (!empty($info['days'])) $info['days'] = json_decode($info['days'], true);//活动时间
            		
            		$count = D('tbtrialorders')->where($where)->count();//分页时的总数
            		$data = D('CustomerView')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->select();
            		
            		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));	
            		$this->assign('tbname', $tbname);
            		$this->assign('apply', $apply);
            		$this->assign('data', $data);
            	} elseif (3 == $t){//中奖进度
            		$page = I('get.page',1);
            		$page = $page < 1 ? 0 : $page - 1;
            		$page_size = I('get.pagesize',20);
            		
            		$order = I('order');
            		$search_type = I('search_type');
            		$search_content = I('search_content');//检索内容
            		
            		$where = array('gid' => $id, 'comfirm' => array('LT',98));
            		if (!empty($search_content)) $where[$search_type] =array('LIKE', '%'.$search_content.'%');//检索条件
            		empty($order) ? $order = 'apply_time' : $order = 'id';//排序
            		
            		$count = D('tbtrialorders')->where($where)->count();
            		$msg = D('CustomerView')->order($order.' DESC')->where($where)->limit($page*$page_size,$page_size)->select();
            		
            		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            		$this->assign('search_content', $search_content);
            		$this->assign('search_type', $search_type);
            		$this->assign('msg', $msg);
            	}
            } else {
            	$this->error('该商品不存在');
            }
            
            $this->assign('category', $this->category[$info['cid']]);
            $this->assign('info', $info);
            $this->assign('id', $id);
            $this->assign('t', $t);
    		$this->display();
    	} else {
    		$this->error('操作失败', U('Actcenter/index'));
    	}
    }

    /**
     * 回收站管理
     *
     */
    public function trash(){
    	
    	$id = I('id',0,'intval');//商品ID
    	if (!empty($id)){//回收处理
    		$info = D('tbgoods_activity')->where(array('id' => $id))->field('trash,state')->find();
    		if (!empty($info)){
    			if ($info['state'] >= 10) $this->error('操作失败,该商品不可放入回收站');
    			empty($info['trash']) ? $msg = 1 : $msg = 0;
    			$msg = D('tbgoods_activity')->where(array('id' => $id))->setField('trash',$msg);
    			if (!empty($msg)){
    				$this->success('操作成功');
    			} else {
    				$this->error('操作失败,请稍后重试');
    			}
    		} else {
    			$this->error('该商品不存在');
    		}
    	} else {//垃圾列表
    		$page = I('get.page',1);
    		$page = $page < 1 ? 0 : $page - 1;
    		$page_size = I('get.pagesize',10);
    		$gname = I('gname','','strip_tags');
    		$where = array('uid' => $_SESSION['ext_user']['id'], 'trash' => 1);
    		if (!empty($gname)) $where['gname'] = array('LIKE', '%'.$gname.'%');
    		
    		$count = D('tbgoods_activity')->where($where)->count();
    		$data = D('TradeView')->order('id DESC')->where($where)->limit($page*$page_size,$page_size)->select();
    		
    		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
    		$this->assign('category', $this->category);
    		$this->assign('gname', $gname);
    		$this->assign('data', $data);
    		$this->display();
    	}
    }
    
    /**
     * 订单详情
     *
     */
    public function orderInfo(){
    	
    	$id = I('id',0,'intval');//订单ID
    	if (!empty($id)){
    		$info = D('tbtrialorders')->limit(1)->field('id,uid,gid,is_talk,addtime,baby_link,colleshop_pic,collebaby_pic,service_pic,tb_id,tb_item,order_pic,real_price,evaluation_goods,baby_link,ship_name,shiporders,evaluation_pic,evaluation_good,evaluationtb_time,evaluationsk_time,comfirm')->where(array('id' => $id))->find();
    		if (!empty($info)){
    			$goods = D('TradeView')->field('gname,url,type,image,shopname,scosts,format,unit_price')->where(array('id' => $info['gid']))->find();
    			if (!empty($goods['format'])) $goods['format'] = json_decode($goods['format'], true);//商品规格
    			if (!empty($info['baby_link'])) $info['baby_link'] = explode(",", $info['baby_link']);//浏览店铺链接
                $name = D('user')->where(array('id' => $info['uid']))->getField('ddusername');//用户信息
    			
    			$this->assign('goods', $goods);
    			$this->assign('name', $name);
    			$this->assign('info', $info);
    		    $this->display();
    		} else {
    			$this->error('该订单不存在');
    		}
    	} else {
    		$this->error('非法操作');
    	}
    }
    
    /**
     * 申请详情
     * 
     */
    public function apply(){
    	
    	$id = I('id',0,'intval');//订单ID
    	if (!empty($id)){
    		$info = D('CustomerView')->field('id,gid,ddusername,tb_id,apply_time,addtime,baby_link,cart_pic,colleshop_pic,collebaby_pic,won_type,comfirm')->where(array('id' => $id))->find();//订单信息
    		$goods = D('tbgoods_activity')->field('gname,url,endtime')->where(array('id' => $info['gid']))->find();
    		if (!empty($info['baby_link'])) $info['baby_link'] = explode(",", $info['baby_link']);//浏览店铺链接
    		
    		$this->assign('goods', $goods);
    		$this->assign('info', $info);
    		$this->display();
    	} else {
    		$this->error('非法操作');
    	}
    }
    
    public function ordermanage(){
        $where= array();
        $where['role_id'] = $_SESSION['ext_user']['id'];
         //商家待处理订单
        $where['comfirm'] = array('in','10,20,30');
        $Count_Waiting = M('tbtrialorders')->where($where)->count();//商家待处理总数量
        $where['comfirm'] = array('in','10');
        $Count_Wait1 = M('tbtrialorders')->where($where)->count();//试客已下单，待发货 
        $where['comfirm'] = array('in','20');
        $Count_Wait2 = M('tbtrialorders')->where($where)->count();//试客已在试客巴评价完成，待审核评价  
        $where['comfirm'] = array('in','30');
        $Count_Wait3 = M('tbtrialorders')->where($where)->count();//试客已在淘宝评价，待确认评价 
         //试客进行中
        $where['comfirm'] = array('in','11,21,0');
        $Count_going = M('tbtrialorders')->where($where)->count();//试客正在进行总数量
        $where['comfirm'] = array('in','0');
        $Count_Wait = M('tbtrialorders')->where($where)->count();//商品已开奖，待试客领取下单
        $where['comfirm'] = array('in','11');
        $Count_skevaluation = M('tbtrialorders')->where($where)->count();//商品已发货，待试客收货评价
        //已完成的订单
        $where['comfirm'] = array('in','31');
        $Count_complete = M('tbtrialorders')->where($where)->count();//已经完成的订单
        //echo $Count_Waiting;
        $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);
        $shop_list = M('tbsellerinfo')->where($where)->select();
        $this->assign('shop_list',$shop_list);
        $this->assign('Count_Waiting',$Count_Waiting);
        $this->assign('Count_going',$Count_going);
        $this->assign('Count_complete',$Count_complete);
        $this->assign('Count_Wait1',$Count_Wait1);
        $this->assign('Count_Wait2',$Count_Wait2);
        $this->assign('Count_Wait3',$Count_Wait1);
        $this->assign('Count_Wait',$Count_Wait);
        $this->assign('Count_skevaluation',$Count_skevaluation);
        $this->display();
    }
    public function orderquery(){
        $this->display();
    }
    // 待发货
    public function orderlist(){
        $type = I('get.type','recharge');
        $page = I('get.page',1);
        $page = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',20);
        $where = array('role_id' => $_SESSION['ext_user']['id'],'comfirm' =>$type);
        $data = M('tbtrialorders')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->select();
        $totalCount = M('tbtrialorders')->where($where)->order('addtime')->count();
        //获得商品信息
        foreach ($data as $k => $v) {
            $goods_array=M('tbgoods')->where('id=' . $v['gid'])->limit(1)->select();
            $data[$k]['goods']=$goods_array[0];
        }
        //获得试客淘宝信息
        foreach ($data as $k => $v) {
            $sk_array=M('user')->where('id=' . $v['uid'])->limit(1)->select();
            $data[$k]['sk']=$sk_array[0];
        }
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
        $this->assign('data', $data);
        $this->assign('type', $type);
        $this->display();
    }

    //待审核
    public function orderlistcheck(){
        $this->display();
    }

    //待确认
    public function orderlistsure(){
        $this->display();
    }

    //追评
    public function orderlistadd(){
        $this->display();
    }
    //待领取
    public function orderlistwaitget(){
        $this->display();
    }
    //待收货评价
    public function orderlistwaiteva(){
        $this->display();
    }
    //待复制评价订单
    public function orderlistwaitcopy(){
        $this->display();
    }
    //待追加评价订单
    public function orderlistwaitadd(){
        $this->display();
    }
    //已取消订单
    public function orderlistcancel(){
        $this->display();
    }
    //已完成订单
    public function orderlistcomp(){
        $this->display();
    }

    public function publish1(){
    	
    	$id = I('id',0,'intval');//试用商品ID
    	$data = D('tbsellerinfo')->field('id,shopname,type')->order('type asc')->where(array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2))->select();//店铺信息
    	if (!empty($data)){//有店铺
    		if (!empty($id)){
    			if (IS_POST){
    				$msg = D('tbgoods_activity')->where(array('id' => $id))->save(array('type' => I('type',1,'intval'), 'sid' => I('sid',0,'intval')));
    				$this ->redirect('Actcenter/publish2/',  array('id' => $id));
    			} else {
    				$goods = D('tbgoods_activity')->field('sid,type')->where(array('id' => $id))->find();//商品信息
    				if (empty($goods)) $this->redirect('Actcenter/publish1');//不存在则跳转
    				$type = my_array_column($data, 'type', 'id');//平台集合
    				
    				$this->assign('type', $type[$goods['sid']]);//当前平台
    				$this->assign('goods', $goods);
    				$this->assign('id', $id);
    			}
    		}
    		$this->assign('data', $data);
    		$this->display();
    	} else {
    		$this->error('请先绑定店铺', U('Usercenter/bindshop'));//无店铺,去添加
    	}
    }
    
    public function publish2(){
    	
    	$id = I('id',0,'intval');//试用商品ID
    	$this->assign('category', $this->category);//分类
    	if (!empty($id)){//试用商品已经存在
    		if (IS_POST){
                $state = $this->getState($id);//检测试用商品的状态
    			
    			$goods = D('tbgoods_activity');
    			if (!$goods->create()){
    				exit($goods->getError());
    			} else {
    				$msg = $goods->save();
    				$this ->redirect('Actcenter/publish3/',  array('id' => $id));
    			}
    		} else {
    			$goods = D('tbgoods_activity')->field('id,sid,type,cid,gname,title,url,image,unit_price,format,scosts,huabei,remark')->where(array('id' => $id))->find();
    			if (!empty($goods)){
    				$format = json_decode($goods['format'],TRUE);//商品规格
    				
    				$platform = $this->platform($goods['sid']);//店铺平台信息
    				$this->assign('platform', $platform);
    			} else {
    				$this->error('该试用商品不存在', U('Actcenter/publish1'));
    			}
    			
    		    $this->assign('format', $format);
    		    $this->assign('goods', $goods);
    		    $this->assign('type', $goods['type']);
    			$this->assign('sid', $goods['sid']);
    		    $this->display();
    		}
    	} else {//试用商品不存在
    		$sid = I('sid', 0);//店铺ID
    	    $type = I('type', 0);//试用类型
    		if (!empty($sid) && !empty($type)){
    			if (IS_POST){
    				$goods = D('tbgoods_activity');
    				if (!$goods->create()){
    					exit($goods->getError());//创建失败,验证未通过 输出错误提示信息
    				} else {
    					$msg = $goods->add();
    					$this ->redirect('Actcenter/publish3/', array('id' => $msg));
    				}
    			} else {
    				$platform = $this->platform($sid);//店铺平台信息
    				$this->assign('platform', $platform);
    				$this->assign('sid', $sid);
    				$this->assign('type', $type);
    				$this->display();
    			}
    		} else {
    			$this ->redirect('Actcenter/publish1');
    		}
    	}
    }
    
    public function publish3(){
    	$id = I('id',0,'intval');//试用商品ID
    	if (!empty($id)){
    		if (IS_POST){
    			$tag = I('post.tag');
    			$state = $this->getState($id);//检测试用商品的状态
                
                if (!empty($tag['pc']['attr'])) $tag['pc']['attr'] = array_filter($tag['pc']['attr'],array($this,'delEmpty'));//过滤PC关键词
                if (!empty($tag['app']['attr'])) $tag['app']['attr'] = array_filter($tag['app']['attr'],array($this,'delEmpty'));//过滤APP关键词
                if (!empty($tag['app']['password'])) $tag['app']['password'] = array_filter($tag['app']['password'],array($this,'delEmpty'));//过滤淘口令
                
                if (empty($tag['pc']['attr']) && empty($tag['app']['attr']) && empty($tag['app']['password'])){
                	$this->error('请至少选择PC淘宝自然搜索或者手机淘宝APP其中一种方案');
                } 
                
                if (!empty($tag['pc']['price1'])) $tag['pc']['price1'] = is_numeric($tag['pc']['price1'])?$tag['pc']['price1']:'';//pc价格1
                if (!empty($tag['pc']['price2'])) $tag['pc']['price2'] = is_numeric($tag['pc']['price2'])?$tag['pc']['price2']:'';//pc价格2
                if (!empty($tag['pc']['address'])) $tag['pc']['address'] = test_input($tag['pc']['address']);//pc发货地
                
                if (!empty($tag['app']['price1'])) $tag['app']['price1'] = is_numeric($tag['app']['price1'])?$tag['app']['price1']:'';//app价格1
                if (!empty($tag['app']['price2'])) $tag['app']['price2'] = is_numeric($tag['app']['price2'])?$tag['app']['price2']:'';//app价格2
                if (!empty($tag['app']['address'])) $tag['app']['address'] = test_input($tag['app']['address']);//app发货地
                $goods = D('tbgoods_activity')->where(array('id' => $id))->setField('tag',json_encode($tag));
                $this ->redirect('Actcenter/publish4/', array('id' => $id));
    		} else {
    			$goods = D('tbgoods_activity')->field('tag')->where(array('id' => $id))->find();
    			if (!empty($goods)){
    				$msg = json_decode($goods['tag'],true);unset($goods);
    			} else {
    				$this->error('该试用商品不存在', U('Actcenter/publish1'));
    			}
                
    			$this->assign('msg', $msg);
    			$this->assign('id', $id);
    			$this->display();
    		}
    	} else {
    		$this ->redirect('Actcenter/publish1');
    	}
    }
    
    public function publish4(){
    	$id = I('id',0,'intval');//试用商品ID
    	if (!empty($id)){
    		if (IS_POST){
    			$starttime = I('post.starttime',0,'strtotime');//开始时间
    			$rate = I('post.rate');//概率集合
    			$days = I('post.day');//日期集合
    			$num = I('post.num');//数量集合
    			$myYear = intval(date('Y',$starttime));//开始的年份
    			$myMonth = intval(date('n',$starttime));//开始的月份
    			$myDay = intval(date('d',$starttime));//开始的月份
    			
    			$state = $this->getState($id);//检测试用商品的状态

    			if (array_sum($num) < 10){//总单数不得小于10单
    				$this->error('总计发放总单数不得低于10单', U('Actcenter/publish4', array('id' => $id)));
    			} else {
    				foreach ($num as $key => $val){
    					if (!empty($val)){
    						$info[$key]['day'] = intval($days[$key]);//日期
    						$info[$key]['num'] = intval($val);//当天单数
    						if (!empty($rate)) $info[$key]['rate'] = is_numeric($rate[$key])?$rate[$key]:'';//概率

    						if ($days[$key] < $myDay){//此处跨月
    							$info[$key]['time'] = strtotime($myYear.sprintf("%02d", ($myMonth+1)).sprintf("%02d", intval($days[$key])));
    						} else {
    							$info[$key]['time'] = strtotime($myYear.sprintf("%02d", $myMonth).sprintf("%02d", intval($days[$key])));
    						}
    						
    						$endtime = $info[$key]['time'] + 24*60*60 - 1;//结束时间
    					}
    				}
    				$goods = D('tbgoods_activity')->where(array('id' => $id))->save(array('num' => array_sum($num),'days' => json_encode($info), 'starttime' => $starttime, 'endtime' => $endtime));//更新数据
    				$this ->redirect('Actcenter/publish5/', array('id' => $id));
    			}
    		} else {
    			$goods = D('tbgoods_activity')->field('id,sid,cid,type,gname,image,url,scosts,unit_price,format,days,starttime')->where(array('id' => $id))->find();//商品信息
    			if (!empty($goods)){
    				$goods['format'] = json_decode($goods['format'],true);//规格
    				$goods['days'] = json_decode($goods['days'],true);
    			} else {
    				$this->error('该试用商品不存在', U('Actcenter/publish1'));
    			}

    			$data = D('tbsellerinfo')->field('shopname,type')->where(array('id' => $goods['sid'], 'confirm' => 2))->find();//店铺信息
    			$this->assign('starttime', $goods['starttime'] ? $goods['starttime'] : time());
    			$this->assign('category', $this->category[$goods['cid']]);//分类
    			$this->assign('goods', $goods);
    			$this->assign('data', $data);
    			$this->assign('id', $id);
    			$this->display();
    		}
    	} else {
    		$this ->redirect('Actcenter/publish1');
    	}
    }
    public function publish5(){
    	$id = I('id',0,'intval');//试用商品ID
    	if (!empty($id)){
    		if (IS_POST){
                $info = I('post.info','','strip_tags');//任务补充信息
                $chat = I('post.chat',0,'intval');//客服聊天
                $per_chatlog = I('post.per_chatlog',0,'intval');//获取客服聊天的概率
                
                $state = $this->getState($id);//检测试用商品的状态
                
                $task = json_encode(array('chat' => $chat*$per_chatlog*0.01)); 
                $goods = D('tbgoods_activity')->where(array('id' => $id))->setField(array('task' => $task, 'info' => $info));
                $this ->redirect('Actcenter/publish6/', array('id' => $id));
    		} else {
    			$goods = D('tbgoods_activity')->field('task,info')->where(array('id' => $id))->find();//商品信息
    			if (!empty($goods)){
    				$goods['task'] = json_decode($goods['task'],true);
    				$goods['per_chatlog'] = $goods['task']['chat']*100;
    				$goods['per_chatlog'] = intval($goods['per_chatlog']);
    				$this->assign('goods', $goods);
    			} else {
    				$this->error('该试用商品不存在', U('Actcenter/publish1'));
    			}
    			$this->assign('id', $id);
    			$this->display();
    		}
    	} else {
    		$this ->redirect('Actcenter/publish1');
    	}
    }
    public function publish6(){
    	$id = I('id',0,'intval');//试用商品ID
    	if (!empty($id)){
    		if (IS_POST){
    			$state = $this->getState($id);//检测试用商品的状态
                $reward = I('post.reward');//红包金额
    			$service = I('post.service');//增值服务信息
    			if (!empty($service['good'])) $msg['good'] = intval($service['good']);
    			if (!empty($service['good_num'])) $msg['good_num'] = intval($service['good_num']);
    			if (!empty($service['safe_day'])) $msg['safe_day'] = intval($service['safe_day']);
    			if (!empty($service['people'])) $msg['people'] = intval($service['people']);
    			if (!empty($service['keyword'])){
    				foreach ($service['keyword'] as $val){
    					$msg['keyword'][] = strip_tags($val);
    				}
    			}unset($service);
    			$goods = D('tbgoods_activity')->where(array('id' => $id))->setField(array('service' => json_encode($msg), 'reward' => $reward, 'state' => 7));
    			$this ->redirect('Actcenter/publish7/', array('id' => $id));
    		} else {
    			$goods = D('tbgoods_activity')->field('service,reward,num')->where(array('id' => $id))->find();//商品信息
    			if (!empty($goods)){
    				$goods['service'] = json_decode($goods['service'],true);
    				$this->assign('num', $goods['num']);
    				$this->assign('reward', $goods['reward']);
    				$this->assign('service', $goods['service']);
    			} else {
    				$this->error('该试用商品不存在', U('Actcenter/publish1'));
    			}
    			$this->assign('id', $id);
    			$this->display();
    		}
    	} else {
    		$this ->redirect('Actcenter/publish1');
    	}
    }
    
    public function publish7(){
    	$id = I('id',0,'intval');//试用商品ID
    	if (!empty($id)){
            $price = $this->totalPrice($id);//账单明细(包括单数)
            
            $info = D('duoduo2010')->field('fund,deposit')->where(array('id' => $_SESSION['ext_user']['id']))->find();//账户余额
            ($info['fund'] - $price['total']) >= 0 ? $msg = 1 : $msg = 0;//余额是否足够 

            $this->assign('money', $price['total']);
            $this->assign('price', $price);
            $this->assign('info', $info);
            $this->assign('msg', $msg);
    		$this->assign('id', $id);
    		$this->display();
    	} else {
    		$this ->redirect('Actcenter/publish1');
    	}
    }
    
    /**
     * 试用付款
     *
     */
    public function payment(){
    	$id = I('id');//试用商品ID
    	if (!empty($id)){
    		$uid = $_SESSION['ext_user']['id'];//用户ID
    		$price = $this->totalPrice($id);//账单明细
    		$now_time = strtotime(date('Y-m-d', time()));
    		
    		if (10 < $price['state']) $this->error('非法操法', U('Actcenter/index'));
    		if ($price['starttime'] < $now_time) $this->error('付款失败,您设置的试用时间已经过期,请重新设置', U('Actcenter/publish4', array('id' => $id)));
    		
    		if (10 == $price['state']){
    			$this->error('您已经付过款了！', U('Actcenter/index'));
    		} else {
    			$msg = D('duoduo2010');
    			$info = $msg->field('fund,deposit')->where(array('id' => $uid))->find();//账户余额
    			if ($info['fund'] < $price['total']) $this->error('余额不足，请充值', U('Actcenter/publish7', array('id' => $id)));
    			
    			$msg->startTrans ();//开启事务
    			      $param =  D('duoduo2010')->where(array('id' => $uid))->save(array('fund' => ($info['fund'] - $price['total']), 'deposit' => ($info['deposit'] + $price['total'])));
    			      if (!empty($param)){//付款成功
    			      	  $param = D('tbgoods_activity')->where(array('id' => $id))->setField(array('addtime' => time(), 'money' => $price['total'], 'state' => 10));//修改为已支付状态
    			      	  if (false === $param) $msg->rollback();//出错回滚
    			      } else {//付款失败
    			      	  $msg->rollback();//出错回滚
    			      }
    			$msg->commit();//事务完成
    			
    			if ($param){
    				$this->success('付款成功', U('Actcenter/index'));
    			} else {
    				$this->error('付款失败', U('Actcenter/index'));
    			}
    		}
    	} else {
    		$this->error('非法操作', U('Actcenter/publish1'));
    	}
    }
    
    /**
     * 图片上传
     *
     */
    public function AjaxUpload(){
    	$config = array( 
	        'maxSize'    =>   3145728,
	        'rootPath' => '../upload/',
	        'savePath' => date("Ymd"),
	        'saveName'   =>    array('uniqid',''),    
	        'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),    
	        'autoSub'    =>    true,    
	        'subName'    =>    array('',''),
        );
    	
    	$upload = new \Think\Upload($config);// 实例化上传类
    	$info = $upload->upload();
    	if (!$info) {
    		echo json_encode(array('status' => 2, 'msg' => $upload->getError()));
    	} else {
    		foreach ($info as $file) {
    			$url = '/upload/'.$file['savepath'].$file['savename'];
    		}
    		echo json_encode(array('status' => 1, 'msg' => '上传成功！', "url" => $url));
    	}
    }
    
    /**
     * 获取试用商品的状态
     *
     * @param int $id  试用商品ID
     * @return int $state 状态值
     */
    private function getState($id){
    	if ($id){
    		$state = D('tbgoods_activity')->field('state')->where(array('id' => $id))->find();
    		if (10 <= $state['state']){
    			$this->error('该试用商品不可修改', U('Actcenter/index'));
    		}
    	}
    }
    
    /**
     * 回调函数(过滤无用数据)
     * @param unknown $v
     * @return boolean|string
     */
    private function delEmpty($v){
    	if (!empty($v)){
    		if (is_array($v)){
    			return empty($v['keyword']) ? false:true;
    		} else {
    			return true;
    		} 
    	} else {
    		return false;
    	}
    }
    
    private function platform($sid){
    	if (!empty($sid)){
    		$platformName = array(1 => '淘宝', 2 => '天猫');
    		$data = D('tbsellerinfo')->field('type')->where(array('id' => $sid))->find();//店铺信息

    		if (!empty($data)){
    			$msg = array('id' => $data['type'], 'name' => $platformName[$data['type']]);
    			return $msg;
    		} else {
    			return '';
    		}
    	}
    }
    
    /**
     * 账单明细(包括单数)
     *
     * @param int $id  试用商品ID
     * @return array $price 返回账单明细包括总价格total、单数num、服务天数safe_day
     */
    private function totalPrice($id){
    	if (!empty($id)){
    		$goods = D('tbgoods_activity')->field('days,service,unit_price,reward,state,starttime')->where(array('id' => $id))->find();//商品信息
    		if (!empty($goods)){
    			$price = array();//初始化
    			$price['state'] = $goods['state'];//状态
    			$price['unit_price'] = $goods['unit_price'];//试客下单价格
    			$price['starttime'] = $goods['starttime'];//试用开启时间
    			if (!empty($goods['days'])){//统计商品单数
    				$price['num'] = 0;//商品单数,初始值
    				$goods['days'] = json_decode($goods['days'],true);
    				foreach ($goods['days'] as $key => $val){
    					$price['num'] += $val['num'];//统计试用商品单数
    				}
    				$price['deposit'] = $price['unit_price']*$price['num'];//商品押金
    				$price['security'] = $price['deposit']*0.05;//试用担保金
    				$price['one'] = $price['deposit']*0.02;//快速返款
    				$price['two'] = $price['num']*2;//系统自动抽奖
    			}

    			if (!empty($goods['service'])){//增值服务
    				$goods['service'] = json_decode($goods['service'],true);
   
    				$price['good'] = $goods['service']['good'];//晒图好评(单数)
    				$price['good_num'] = $goods['service']['good_num'];//设置好评关键词(单数)
    				$price['safe_day'] = $goods['service']['safe_day'];//禁止老用户中奖(服务天数)
    				$price['reward'] = $goods['reward'];//试用红包加赏(单价)
    				
    				if (!empty($goods['reward'])) $price['reward_price'] = $price['reward']*$price['num'];//试用红包加赏(价格)
    				if (45 == $goods['service']['safe_day']) $price['safe'] = 10;//禁止老用户中奖(价格)
    				if (!empty($goods['service']['people'])) $price['people'] = 20;//精准投放
    				if (!empty($goods['service']['good'])) $price['good_price'] = $price['good']*2;//晒图好评(价格)
    				if (!empty($goods['service']['good_num'])) $price['good_num_price'] = $price['good_num']*1;//设置好评关键词(价格)
    			}
    			
    			$price['total'] = 0;//总费用
    			if (!empty($price['deposit'])) $price['total'] = $price['total'] + $price['deposit'];
    			if (!empty($price['security'])) $price['total'] = $price['total'] + $price['security'];
    			if (!empty($price['one'])) $price['total'] = $price['total'] + $price['one'];
    			if (!empty($price['two'])) $price['total'] = $price['total'] + $price['two'];
    			
    			if (!empty($price['reward_price'])) $price['total'] = $price['total'] + $price['reward_price'];
    			if (!empty($price['safe'])) $price['total'] = $price['total'] + $price['safe'];
    			if (!empty($price['people'])) $price['total'] = $price['total'] + $price['people'];
    			if (!empty($price['good_price'])) $price['total'] = $price['total'] + $price['good_price'];
    			if (!empty($price['good_num_price'])) $price['total'] = $price['total'] + $price['good_num_price'];
    			
    			return $price;
    		} else {
    			$this->error('该试用商品不存在', U('Actcenter/publish1'));
    		}
    	} else {
    		$this->error('非法操作', U('Actcenter/publish1'));
    	}
    }
    
    /**
     * 申请试用人数详情
     * @param int gid 试用商品ID
     * @return array data
     */
    private function applyInfo($gid){
    	$gid = intval($gid);
    	if(!empty($gid)){
    		$count = array();
    		$data = D('tbtrialorders')->field('apply_time,addtime')->where(array('gid' => $gid))->select();
    		if (!empty($data)){
    			$count['total'] = count($data);//统计总申请人数
    			foreach ($data as $key => $val){
    				if (!empty($val['addtime'])){//申请统计
    					$msg = strtotime(date('Y-m-d', strtotime($val['addtime'])));//将申请时间转换为当天凌晨时分的时间戳
    					if (!empty($count[$msg])){//存在则统计(申请)
    						$count[$msg] += 1;
    					} else {//不存在则添加并设置初始值(申请)
    						$count[$msg] = 1;
    					}
    				}
    				
    				if (!empty($val['apply_time'])){//回访统计
    					$app = strtotime(date('Y-m-d', strtotime($val['apply_time'])));//将回访时间转换为当天凌晨时分的时间戳
    					if (!empty($count['apply'][$app])){//存在则统计(回访)
    						$count['apply'][$app] += 1;
    					} else {//不存在则添加并设置初始值(回访)
    						$count['apply'][$app] = 1;
    					}
    				}
    			}unset($data);
    		}
    		return $count;
    	}
    }
    
    /**
     * 试用申请统计详情
     * @param int gid 试用商品ID
     * @return array  data
     */
    private function applyCount($gid){
    	$gid = intval($gid);
    	if (!empty($gid)){
    		$field['count(id)'] = 'count';//总申请人数
    		$field['count(case when comfirm in (10,11,12,20,21,30,31,32) then comfirm end)'] = 'tb';//已经下订单人数
    		$field['count(case when comfirm < 98 then comfirm end)'] = 'zj';//中奖人数
    		$data = D('tbtrialorders')->field($field)->where(array('gid' => $gid))->find();
    		return $data;
    	}
    }
    
    /**
     * 退款(活动结束后)款
     */
    private function refund(){	
    	$where = array('uid' => intval($_SESSION['ext_user']['id']), 'trash' => 0, 'msg' => 0);
    	$where['endtime'] = array('ELT', time());
    	$where['state'] = array('EQ', 11);
    	$info = D('tbgoods_activity')->where($where)->field('id,num,unit_price')->select();
    	if (!empty($info)){
    		foreach ($info as $k => $v){//转换数组结构为array(试用商品ID => array(总单数，每单基础价格))转
    			$msg[$v['id']] = array('num' => $v['num'], 'money' => getPrice($v['unit_price']));
    		}unset($info);//去掉无用数据		
    		$key = array_keys($msg);//获取试用商品IDD
    		$info = D('tbtrialorders')->where('comfirm = 31 and gid in ('.implode(',', $key).')')->field('gid, count("id") as count')->group('gid')->select();//获取试用商品已完成的订单数
    		foreach ($info as $k => $v){//整理数组结构为array(试用商品ID，已经完成订单总数)
    			$param[$v['gid']] = $v['count'];
    		}
    		
    		foreach ($msg as $k => $v){//整理数组结构为array(试用商品ID，应退金额)
    			$msg[$k] = ($v['num'] - (!empty($k) ? $param[$k] : 0))*$v['money'];
    		}
    		
    		$money = array_sum($msg);//应退总金额
    		$msg = D('duoduo2010');
    		$msg->startTrans (); // 开启事务
    		$param = $msg->where('id = '.intval($_SESSION['ext_user']['id']))->setInc('fund', $money);//退款
    		      if (!empty($param)){//退款成功
    		      	  $param = D('tbgoods_activity')->where('id in ('.implode(',', $key).')')->save(array('msg' => 1));
    		      	  if (false === $param) $msg->rollback();//出错回滚
    		      } else {//退款失败
    		      	  $msg->rollback();//出错回滚
    		      }
    		$msg->commit();//事务完成
    	}
    }
}
?>