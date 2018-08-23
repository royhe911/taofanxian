<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 10:00
 */
namespace Supporter\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;
use Think\Model;
class OrderController extends BaseController
{    
    public function index(){   
        $this->display();
    }
    public function ordermanage(){
        $start = date('Y-m-d 00:00:00');
        $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);
        $shop_list = M('tbsellerinfo')->where($where)->select();//获得商家所遇店铺信息
        foreach ($shop_list as $k => $v) {
            $where = array('comfirm' => array('in','0,12'),'shop_id'=>$v['id']);
            $where['apply_time'] = array('egt' , $start);
            $shop_list[$k]['count0'] =M('tbtrialorders')->where($where)->count(); //商品已开奖，待试客领取下单总数
            $where = array('comfirm' => 10,'shop_id'=>$v['id']);
            $shop_list[$k]['count10'] =M('tbtrialorders')->where($where)->count(); //试客已下单，待发货订单总数 
            $where = array('comfirm' => 20,'shop_id'=>$v['id']);
            $shop_list[$k]['count20'] =M('tbtrialorders')->where($where)->count(); //试客已在试客巴评价完成，待审核评价订单总数
            $where = array('comfirm' => 30,'shop_id'=>$v['id']);
            $shop_list[$k]['count30'] =M('tbtrialorders')->where($where)->count(); //试客已在淘宝评价，待确认评价订单总数
            $where = array('comfirm' => array('in','11,22'),'shop_id'=>$v['id']);
            $shop_list[$k]['count11'] =M('tbtrialorders')->where($where)->count(); //试客收货评论
            $where = array('comfirm' => array('in','21,32'),'shop_id'=>$v['id']);
            $shop_list[$k]['count21'] =M('tbtrialorders')->where($where)->count(); //淘宝评论
            $where = array('comfirm' => 31,'shop_id'=>$v['id']);
            $shop_list[$k]['count31'] =M('tbtrialorders')->where($where)->count(); //完成
            $where = array('comfirm' => 12,'shop_id'=>$v['id']);
            $shop_list[$k]['count12'] =M('tbtrialorders')->where($where)->count(); //订单号驳回
            $where = array('comfirm' => 22,'shop_id'=>$v['id']);
            $shop_list[$k]['count22'] =M('tbtrialorders')->where($where)->count(); //试客评论驳回
            $where = array('comfirm' => 32,'shop_id'=>$v['id']);
            $shop_list[$k]['count32'] =M('tbtrialorders')->where($where)->count(); //淘宝评论驳回
            $map['comfirm'] =array('in','10,20,30');
            $map['shop_id'] = $v['id'];
            $shop_list[$k]['wait'] = M('tbtrialorders')->where($map)->count(); //商家处理订单
            $map['comfirm'] =array('in','11,22,21,32');
            $shop_list[$k]['sk_going'] = M('tbtrialorders')->where($map)->count()+$shop_list[$k]['count0']; //试客进行中
            $map['comfirm'] =array('in','31');
            $shop_list[$k]['order_finish'] = M('tbtrialorders')->where($map)->count(); //完成的
            //过期订单
            $maps = array('comfirm' => 0,'shop_id'=>$v['id']);
            $maps['apply_time'] = array('lt' , $start);
            $shop_list[$k]['order_overdue'] = M('tbtrialorders')->where($maps)->count();//过期订单
        }
        //获得商家处理订单总数
        $tallcount = array();
        foreach($shop_list as $k=>$v){
            $tallcount['wait']+=$v['wait'];
        }
        //获得试客进行中总数
        $tallcount_skgoing = array();
        foreach($shop_list as $k=>$v){
            $tallcount_skgoing['sk_going']+=$v['sk_going'];
        }
        //过期订单总数
        $tallcount_overdue=array();
        foreach($shop_list as $k=>$v){
            $tallcount_overdue['order_overdue']+=$v['order_overdue'];
        }
        //已完成订单总数
        $tallcount_finish=array();
        foreach($shop_list as $k=>$v){
            $tallcount_finish['order_finish']+=$v['order_finish'];
        }
        //已结束订单总数
        $total_orderend = $tallcount_finish['order_finish']+$tallcount_overdue['order_overdue'];      
        $this->assign('total_orderend',$total_orderend);
        $this->assign('tallcount_wait',$tallcount['wait']);
        $this->assign('tallcount_going',$tallcount_skgoing['sk_going']);
        $this->assign('shop_list',$shop_list);
        $this->display();
    }
    public function orderquery(){
        $page = I('get.page',1);
        $page = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',20);
        $tb_id  = I('get.taobao_id','');
        $tb_item  = I('get.outer_orderid','');
        if($tb_id || $tb_item){
            if($tb_id){$where['tb_id'] = $tb_id;}
            if($tb_item){$where['tb_item'] = $tb_item;}
            $where['role_id'] = $_SESSION['ext_user']['id'];
            $where['comfirm'] = array('between','10,32');
            $data = M('tbtrialorders')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->alias('g')//获得搜索数据
                ->join('LEFT JOIN tfx_tbsellerinfo AS s ON g.shop_id=s.id')
                ->join('LEFT JOIN tfx_tbgoods_activity AS t ON g.gid=t.id')
                ->field('g.*,s.shopname,t.gname,t.unit_price,t.image,t.type')
                ->select();
            foreach ($data as $k => $v) { //订单状态
                if ($v['comfirm'] == 98){$data[$k]['comfirm_note'] = '未中奖订单';}
                if ($v['comfirm'] == 99){$data[$k]['comfirm_note'] = '正在申请订单';}
                if ($v['comfirm'] == 10){$data[$k]['comfirm_note'] = '待发货';}
                if ($v['comfirm'] == 20){$data[$k]['comfirm_note'] = '试客评价待审核';}
                if ($v['comfirm'] == 30){$data[$k]['comfirm_note'] = '淘宝评价待审核';}
                if ($v['comfirm'] == 0){$data[$k]['comfirm_note'] = '商品开奖待试客下单';}
                if ($v['comfirm'] == 31){$data[$k]['comfirm_note'] = '订单完成已返款！';}
                if ($v['comfirm'] == 12){$data[$k]['comfirm_note'] = '订单号审核驳回';}
                if ($v['comfirm'] == 22){$data[$k]['comfirm_note'] = '平台评价驳回';}
                if ($v['comfirm'] == 32){$data[$k]['comfirm_note'] = '淘宝评价驳回';}
                if ($v['comfirm'] == 11){$data[$k]['comfirm_note'] = '商家确认发货';}
                if ($v['comfirm'] == 21){$data[$k]['comfirm_note'] = '收货评价通过';}
                if ($v['comfirm'] == 1 || $v['comfirm'] == 2){$data[$k]['comfirm_note'] = '无状态';}
            }
                $totalCount= count($data); 
        }else{

                $data = array();
        }
        $this->assign('tb_id',$tb_id);
        $this->assign('tb_item',$tb_item);
        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
        $this->assign('data',$data);
        $this->display();
    }
    public function ordersearch(){
        $this->display();
    }
    // 待发货
    public function orderlist(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        if($type == 10 || $type ==0){ //判断GET参数
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $data= $this->searchdata($shop_id,$tb_id,$tb_item,$type);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('shop_list', $shop_list);
            $this->assign('shop_id', $shop_id);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display(); 
        }else{
            $this->error('非法操作', U('order/ordermanage')); 
        }        
    }

    //待审核
    public function orderlistcheck(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        $page = I('get.page',1);
        if($type == 20 || $type ==0){ //判断GET参数   
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $where['role_id'] = $_SESSION['ext_user']['id'];
            $where['comfirm'] = $type;
            $data= $this->searchdata($shop_id,$tb_id,$tb_item,$type);//获得列表数据
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('shop_list', $shop_list);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('shop_id', $shop_id);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        }
    }

    //待确认
    public function orderlistsure(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        if($type == 30 || $type ==0){  //判断GET参数
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $data= $this->searchdata($shop_id,$tb_id,$tb_item,$type);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('shop_list', $shop_list);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('shop_id', $shop_id);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        }
    }

    //追评
    public function orderlistadd(){
        $this->display();
    }

    //待领取
    public function orderlistwaitget(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        $type1 = I('get.type1','recharge');
        $page = I('get.page',1);
        if($type == 0 && $type1 == 1){ //判断GET参数
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $data= $this->searchdata1($shop_id,$tb_id,$tb_item,$type,$type1);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('shop_list', $shop_list);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('shop_id', $shop_id);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        }
    }
    //待收货评价
    public function orderlistwaiteva(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        if($type == 11 || $type ==0){//判断GET参数
            $page = I('get.page',1); 
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $data= $this->searchdata($shop_id,$tb_id,$tb_item,$type);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $this->assign('shop_list', $shop_list);
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('shop_id', $shop_id);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        } 
    }
    //待复制评价订单
    public function orderlistwaitcopy(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        $page = I('get.page',1);
        if($type == 21 || $type ==0){//判断GET参数
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $data= $this->searchdata($shop_id,$tb_id,$tb_item,$type);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $this->assign('shop_list', $shop_list);
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('shop_id', $shop_id);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        } 

    }
    //待追加评价订单
    public function orderlistwaitadd(){
        $this->display();
    }
    //已取消订单
    public function orderlistcancel(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        $type1 = I('get.type1','recharge');
        if($type == 0 && $type1 == 2){ //判断GET参数
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');     
            $data= $this->searchdata1($shop_id,$tb_id,$tb_item,$type,$type1);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('shop_list', $shop_list);
            $this->assign('data', $data);
            $this->assign('shop_id', $shop_id);
            $this->assign('type', $type);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        } 
    }
    //已完成订单
    public function orderlistcomp(){
        $shop_id = I('get.shop_id','recharge');
        $type = I('get.type','recharge');
        if($type == 31 || $type ==0){ //判断GET参数
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',20);
            $tb_id = I('get.taobao_id','');
            $tb_item = I('get.outer_id','');
            $data= $this->searchdata($shop_id,$tb_id,$tb_item,$type);//获得列表数据
            $totalCount= count($data);
            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
            $where = array('sid' => $_SESSION['ext_user']['id'], 'confirm' => 2);//店铺列表
            $shop_list = M('tbsellerinfo')->where($where)->select();
            $shop_current = M('tbsellerinfo')->find($shop_id);
            $this->assign('shop_current', $shop_current);
            $this->assign('shop_list', $shop_list);
            $this->assign('shop_id', $shop_id);
            $this->assign('data', $data);
            $this->assign('type', $type);
            $this->assign('tb_id', $tb_id);
            $this->assign('tb_item', $tb_item);
            $this->display();
        }else{
            $this->error('非法操作', U('order/ordermanage'));   
        } 
    }

   //商家订单状态改变
    public function comfirm_chanage($id='',$comfirm='')
    {
        if (IS_POST)
        {	
        	$model = new Model();
	        $model->startTrans();
	        $tbtrialorders = $model->table(C('DB_PREFIX').'tbtrialorders');
            $id = I('post.id');
            $arr['comfirm'] = I('post.comfirm');
            //验证
            $comfirm = $arr['comfirm']-1;
            $order_data = M('tbtrialorders')->where('id='.$id.' and role_id='.$_SESSION['ext_user']['id'].' and comfirm='.$comfirm);
            if(empty($order_data))
            {
            	$data['status'] =0;
            	$this->ajaxReturn($data);  die(); 
            }
            $data=array();
            $t = $tbtrialorders->where('id=' . $id)->save($arr);
            $i = 0;
            if($t)
			{
                $quota = $this->quota_refund($id);
                $uarr['f_money'] = $quota['f_money'];
                if($quota['f_money']>0)
                {
	                $ur = $model->table(C('DB_PREFIX').'user')->where('id='.$quota['uid'])->save($uarr);//给用户增加预提金
	                if(!$ur)
	                {
	                	$i++;
	                }
                }                  
			}else{
				$i++;
            }  
			if($i>0)
			{
				$model->rollback();
				$data['status'] =0;
			}else{
				$model->commit();   
                $data['status'] =1;
			}
            $this->ajaxReturn($data);   
        }     
    }

   //商家订单驳回原因
    public function order_back($id,$note_order){
        $tbtrialorders = M('tbtrialorders');
        if (IS_POST){
            $type = I('post.type');
            $id = I('post.id');
            if($type == 10){
                $arr['note_order'] = I('post.note_order');//订单驳回内容
                $arr['comfirm'] = 12;//订单状态改变
            }
            if($type == 20){
                $arr['note_skcomments'] = I('post.note_order');//试客评价内容
                $arr['comfirm'] = 22;//订单状态改变
            }
            if($type == 30){
                $arr['note_tbcomments'] = I('post.note_order');//淘宝评价驳回
                $arr['comfirm'] = 32;//订单状态改变
            }
            $user_id = $_SESSION['ext_user']['id'];
            $data_id = $tbtrialorders->find($id);
            $role_id = $data_id['role_id'];
            $data=array();
            if(!empty($arr)){//数据验证
                if($user_id == $role_id){//权限验证
                    $t = $tbtrialorders->where('id=' . $id)->save($arr);
                    if($t){
                      $data['status'] =1;             
                    }else{
                      $data['status'] =0; 
                    } 
                }else{
                      $data['status'] =0;
                } 
            }else{
                $data['status'] =0;
            } 

            $this->ajaxReturn($data);   
        }     
    }
   //批量操作 
   public function order_batch()
   {
   		$model = new Model();
        $model->startTrans();
    	$tbtrialorders = $model->table(C('DB_PREFIX').'tbtrialorders');
	    if (IS_POST){
	        $arr = I('post.arr');
	        $type = I('post.type');
	        $where['id'] = array('in',$arr);
	        $i = 0;
	        foreach($arr as $id)
	        {
	        	$order_data = M('tbtrialorders')->where('id='.$id.' and role_id='.$_SESSION['ext_user']['id'].' and comfirm='.$type);
	            if(empty($order_data))
	            {
	            	$data['status'] =0;
	            	$this->ajaxReturn($data);  die(); 
	            }
	        }
	        if($type==10)
	        {
	        	$comfirm = 11;
	        	if(!empty($arr))
	        	{
		        	foreach($arr as $val)
		        	{
			        	$quota = $this->quota_refund($val);
		                $uarr['f_money'] = $quota['f_money'];
		                if($quota['f_money']>0)
		                {
			                $ur = $model->table(C('DB_PREFIX').'user')->where('id='.$quota['uid'])->save($uarr);//给用户增加预提金
			                if(!$ur)
			                {
			                	$i++;break;
			                }
		                }
		        	}
	        	}
	        }
	        if($type==20){$comfirm = 21;}
	        if($type==30){$comfirm = 31;}
            $t = $model->table(C('DB_PREFIX').'tbtrialorders')->where($where)->setField('comfirm',$comfirm);
	    	if(!$t){
                $i++; 
            }
            if($i>0)
            {
				$model->rollback();
                $data['status'] = 0; 
            }else{
				$model->commit(); 
                $data['status'] = 1; 
            }
	    }
    	$this->ajaxReturn($data);
   }

    /**
     * 未领订单和过期订单数据
     */
    private function searchdata1($shop_id,$tb_id,$tb_item,$type,$type1){
        $where['role_id'] = $_SESSION['ext_user']['id'];
        if($shop_id){$where['shop_id'] = $shop_id;}//根据店铺查询
        if($tb_id){$where['tb_id'] = array('like','%'.$tb_id.'%');}//根据试客淘宝帐号查询  
        $start = date('Y-m-d 00:00:00');
        if($type1 == 2){
           $where['apply_time'] = array('lt' , $start);//失效订单
           $where['comfirm'] = $type;
        }else{
          $where['apply_time'] = array('egt' , $start);//待领订单
          $where['comfirm'] = array('in','0,12');
        }
        if($tb_item !=''){
           $user_name =$tb_item;
           $map['ddusername'] = array('like','%'.$user_name.'%');
           $user_array = M('user')->where($map)->select();
           $ids = array(); 
           foreach ($user_array as $k => $v) {$ids[] = $v['id'];}
           $where['uid'] = array('in',$ids); //用户名搜索
        }        
        $data = M('tbtrialorders')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->select();
        $totalCount = M('tbtrialorders')->where($where)->order('addtime')->count();
        //获得商品信息
        foreach ($data as $k => $v) {
            $data[$k]['goods']=M('tbgoods_activity')->field('gname,unit_price,image')->find($v['gid']);
            $data[$k]['sk']=M('user')->field('ddusername')->find($v['uid']);
            $data[$k]['seller']=M('tbsellerinfo')->field('shopname')->find($v['shop_id']);
        }
        return $data;
    }
    /**
     * 获得订单列表数据
     */
    private function searchdata($shop_id,$tb_id,$tb_item,$type){
        $where['role_id'] = $_SESSION['ext_user']['id'];
        if($type == 11){
            $where['comfirm'] = array('in','11,22'); //试客收货评论未审核
        }elseif($type == 21){
            $where['comfirm'] = array('in','21,32'); //淘宝评论未审核
        }else{
            $where['comfirm'] =$type;
        }
        if($shop_id){$where['shop_id'] = $shop_id;}//根据店铺查询
        if($tb_id){$where['tb_id'] = array('like','%'.$tb_id.'%');}//根据试客淘宝帐号查询 
        if($tb_item){$where['tb_item'] = array('like','%'.$tb_item.'%');}//根据淘宝订单号查询
        $data = M('tbtrialorders')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->alias('g')
                ->join('LEFT JOIN tfx_tbsellerinfo AS s ON g.shop_id=s.id')
                ->join('LEFT JOIN tfx_tbgoods_activity AS t ON g.gid=t.id')
                ->field('g.*,s.shopname,t.gname,t.unit_price,t.image,t.type')
                ->select();
        $totalCount = M('tbtrialorders')->where($where)->order('addtime')->count();
        return $data; 
    }
	/**
	 * 订单结束-扣除押金扣(批量处理)
	 */
	public function auditAll(){
		if (IS_POST){
			$arr = I('post.arr');//订单ID集合
			if (!empty($arr)){
				$ids = implode(',', $arr);
				$uid = intval ( $_SESSION ['ext_user'] ['id'] );//用户ID
				$data = D('tbtrialorders')->where('id in ('.$ids.') and comfirm = 30')->field('id,gid')->select();
				if (count($data) == count($arr)){//数据正常误
					foreach ($data as $key => $val){
						$msg[$val['id']] = $val['gid'];
					}
					$this->ajaxReturn(array('msg' => $this->pay($msg)));//批量支付
				} else {//数据有误
					$this->ajaxReturn(array('msg' => 3));
				}
			} else {
				$this->ajaxReturn(array('msg' => 2));//无数据
			}
		} else {
			$this->ajaxReturn(array('msg' => 0));
		}
	}
	
	/**
	 * 订单结束-扣除押金扣
	 */
	public function audit() {
		$id = I ( 'id', 0, 'intval' );//订单ID
		$uid = intval ( $_SESSION ['ext_user'] ['id'] );//用户ID
		if (!empty($id)) {
			$gid = D('tbtrialorders')->where(array('id' => $id, 'comfirm' => 30))->getField('gid');//商品ID
			if (empty($gid)) $this->ajaxReturn(array('msg' => 2, 'message' => '审核失败,该试用商品不存在'));
			
			$msg = $this->pay(array($id => $gid));//支付后订单完成
			if (1 == $msg){
				$this->ajaxReturn(array('msg' => 1));
			} else {
				$this->ajaxReturn(array('msg' => 2, 'message' => '审核失败'.$msg));
			}
		} else {
			$this->ajaxReturn(array('msg' => 2, 'message' => '审核失败'));
        }
    }
	
	/**
	 * 基础服务费
	 * @param array(订单ID => 试用商品ID) $ids
	 */
    private function basePay($ids){
    	if (!empty($ids)) {
	 		$msg = array_values($ids);//返回所有的商品ID集合
	 		$msg = array_unique($msg);//去除重复数据
	 		
	 		$price = array();
	 		$data = D('tbgoods_activity')->where ('id in ('.implode(',', $msg).')')->field ('id,unit_price')->select();
	 		if (!empty($data)) {
	 			foreach($data as $key => $val) {
	 				$price[$val['id']] = getPrice($val['unit_price']);//每单价
	 			}unset($data);//清空无用数据	 			
	 			foreach($ids as $key => $val){
	 				$data[$key] = $price[$val];
	 			}unset($price);//清空无用数
	 			return $data;
	 		}
	 	}
	}
	
	/**
	 * 给用户返现事件(订单完成后)
	 * @param array(订单ID) $key
	 */
	private function cash($key){
		if (!empty($key)){
			$data = D('OrderView')->where('o.id in ('.implode(',', $key).') and comfirm = 31')->field('id,gid,uid,unit_price')->select();
			if (!empty($data)){
				foreach ($data as $k => $v){
					$unit_price = intval($v['unit_price']);//试用商品价格
					$val = D('tbrecord')->where(array('uid' => $v['uid'], 'gid' => $v['gid'], 'statu' => 1, 'msg' => 1))->getField('id');//查看用户是否已经返款
					if (empty($val)){//没有返款则返款
						$where = array('uid' => $v['uid'], 'gid' => $v['gid'], 'statu' => 1, 'msg' => 1, 'money' => $unit_price,'detailtime' => date("Y-m-d H:i:s", time()));
						$val = D('tbrecord')->add($where);//添加记录
						if (!empty($val)){
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
				$msg = D('tbrecord')->add(array('uid' => $uid, 'gid' => $gid, 'money' => $money, 'detailtime' => date("Y-m-d H:i:s", time()), 'statu' => 6, 'msg' => 1));//添加记录
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
				$msg = D('tbrecord')->add(array('uid' => $fid, 'xid' => $uid, 'num' => $num, 'money' => $money, 'gid' => $gid, 'detailtime' => date("Y-m-d H:i:s", time()), 'statu' => 4, 'msg' => 1));
				if (false === $msg) return 12;//执行出错
				$msg = D('user')->where('id = '.$fid)->setInc('txmoney', $money);//试用返款给用户增加余额(上家)
				if (false === $msg) return 12;//执行出错
				
				if (1 == $num){//上上家，有则返利
					$ffid = M('user')->where('id='.$fid)->getField('uid');//上上家用户ID
					if (!empty($ffid)){
						$msg = D('tbrecord')->add(array('uid' => $ffid, 'xid' => $fid, 'gid' => $gid, 'money' => 1, 'detailtime' => date("Y-m-d H:i:s", time()), 'statu' => 5, 'msg' => 1));
						if (false === $msg) return 13;//执行出错
						$msg = D('user')->where('id = '.$ffid)->setInc('txmoney');//试用返款给用户增加余额(上上家)
						if (false === $msg) return 13;//执行出错
					}
				}
			}
		}
		return 1;//执行成功
	}
	
	/***
	 * 支付事件
	 * @param array(订单ID => 试用商品ID) $ids
	 */
	private function pay($ids){
		if (!empty($ids)){
			$data = $this->basePay($ids);//费用
			if (!empty($data)){
				$money = array_sum($data);//总金额
				$key = array_keys($data);//获取所有订单ID
				$uid = intval($_SESSION['ext_user']['id']);//用户ID
				
				$msg = D('duoduo2010');
				$msg->startTrans (); // 开启事务
				$info = D('duoduo2010')->where ('id = '.$uid )->field ('deposit')->find();//查询押金余额
				if ($info['deposit'] >= $money){//余额充足
					$val = D('duoduo2010')->where('id = '.$uid)->setDec('deposit', $money);//扣除押金
					if (!empty($val)){
						$val = D('tbtrialorders')->where('id in ('.implode(',', $key).')')->setField('comfirm', 31);//审核通过
						if (!empty($val)){//扣款成功
							$val = $this->cash($key);//用户返现(1为成功其他值为失败)
							if (1 != $val) $msg->rollback();//返现失败则回滚
						} else {
							$msg->rollback();//出错回滚
						}
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
		} else {
			return 3;
		}
	}
         
	/**
	* 给用户返款(100立返)
	* 订单ID
	*/      
	private function quota_refund($id)
	{
		$order = M('tbtrialorders')->where('id='.$id)->find();
		$goods = M('tbgoods_activity')->where('id='.$order['gid'])->find();
		$uid = $order['uid'];
		$ggwhere['uid'] = $uid;
        $ggwhere['statu'] = 1;
        $result = M('tbrecord')->where($ggwhere)->find();
        $f_money = 0;
        if(empty($result))
        {
            $t_money=M('user')->where('id='.$uid)->getField('f_money');
            if($t_money==100)
            {
            	$quota['uid'] = $uid;
		        $quota['f_money'] = 0;
		        return $quota;
            }else{
	            $total_money=$t_money+$goods['unit_price'];
		        if($total_money>=100){
		            $f_money=100;
		        }else{
		            $f_money=$total_money;
		        }
            }
        }
        $quota['uid'] = $uid;
        $quota['f_money'] = $f_money;
        return $quota;	
	}
}
?>