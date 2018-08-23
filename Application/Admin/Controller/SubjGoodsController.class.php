<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;
/**
 * @auth TRUE
 * @name 专题管理
 * @authsort 20
 * */
class SubjGoodsController extends InterceptController {

	public function index(){
		
		$subjid = I('get.subjid',0);
		$keywords = I('get.keywords','');
		$cid = I('get.cid','');
		$range = I('get.range','');
		$price = I('get.price','');
		$timenum = I('get.timenum','');
		if(IS_POST)
		{
			$keywords = I('post.keywords','');
			$cid = I('post.cid','');
			$range = I('post.range','');
			$price = I('post.price','');
			$timenum = I('post.timenum','');
		}
		
		$category = array(
						'10001' => '女装',
						'10002' => '男装',
						'10003' => '鞋包',
						'10004' => '母婴',
						'10005' => '内衣',
						'10006' => '美食',
						'10007' => '数码',
						'10008' => '家居',
						'10009' => '美妆',
						'10010' => '户外',
						'10011' => '配饰',
						'10012' => '家装',
						'10013' => '家电',
						'10014' => '车用',
						'10015' => '图书',
						'10016' => '其他'
						);
		$ranges = array(
						'20' => '20%-40%',
						'40' => '40%-60%',
						'60' => '60%-80%',
						'80' => '80%-100%'
						);
		$discount_price = array(
						'0,10' => '1-10元',
						'10,20' => '10-20元',
						'20,50' => '20-50元',
						'50,100' => '50-100元',
						'100,150' => '100-150元',
						'150,200' => '150-200元',
						'200,300' => '200-300元'
						);
						
		$effetime = array(
						'1' => '今天',
						'2' => '1-3天',
						'3' => '大于3天',
						'5' => '大于5天'
						);
		$subj = M('subj')->field('id,name')->select();
		foreach($subj as $val)
		{
			$subj_name[$val['id']] = $val['name'];
		}
		$where = '';
		if($keywords)
		{
			$where = "title like '%".$keywords."%'".' and ';

		}
		
		if($cid)
		{
			$where .= 'cid='.$cid.' and ';

		}
		if($range)
		{
			$end_range = $range+20;
			$where .= '(price_jian/discount_price)*100>='.$range.' and (price_jian/discount_price)*100<'.$end_range.' and ';

		}
	
		if($price)
		{
			$price_all = explode(',',$price);
			if(count($price_all)==2)
			{
				$where .= '(discount_price-price_jian)>='.$price_all[0].' and (discount_price-price_jian)<'.$price_all[1].' and ';
			}
			

		}
		if($timenum)
		{
			if($timenum==1)
			{
				$to = date('Y-m-d');
				$time_end = strtotime($to." 23:59:59");
				$where .= 'endtime>='.time().' and endtime<='.$time_end;
			}elseif($timenum==2)
			{
				$to = date('Y-m-d');
				$time_star = date('Y-m-d',strtotime('+1 day'));
				$time_star = strtotime($time_star);
				$time_end = date('Y-m-d',strtotime('+3 day'));
				$time_end = strtotime($time_end." 23:59:59");
				$where .= 'endtime>='.$time_star.' and endtime<'.$time_end;
			}elseif($timenum==3)
			{
				$time_star = date('Y-m-d',strtotime('+4 day'));
				$time_star = strtotime($time_star);
				$where .= 'endtime>='.$time_star;
			}elseif($timenum==5)
			{
				$time_star = date('Y-m-d',strtotime('+6 day'));
				$time_star = strtotime($time_star);
				$where .= 'endtime>='.$time_star;
			}
			

		}else{
			$where .= 'endtime>'.time();
		}
		$totalCount = M('goods')->where($where)->count();
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		
		$data = M('goods')->where($where)->order('(discount_price-price_jian),(price_jian/discount_price) DESC,subj_seat DESC,endtime')->limit($page*$page_size,$page_size)->select();
	
		//echo M('goods')->getLastSql();exit;	
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->assign('subj',$subj);
		$this->assign('subjid',$subjid);
		$this->assign('keywords',$keywords);
		$this->assign('subj_name',$subj_name);
		$this->assign('cid',$cid);
		$this->assign('category',$category);
		$this->assign('range',$range);
		$this->assign('ranges',$ranges);
		$this->assign('price',$price);
		$this->assign('discount_price',$discount_price);
		$this->assign('timenum',$timenum);
		$this->assign('effetime',$effetime);
		$this->display();
		
		
	}

	public function editSubjGoods(){
		if(IS_POST)
		{
			$goods_id = I('post.goods_id','');
			if($goods_id)
			{
				$count = $count_no = 0;
				foreach($goods_id as $val)
				{
					$goods = M('goods')->where('id='.$val)->find();
					if($goods)
					{
						$subjid = I('post.subjid','');
						$data = array(
								'subj_id'=>$subjid
						);
						if( false === M('goods' )->where('id='.$val)->save($data)){
							$count++;
						}	
					}else
					{
						$count_no++;
						continue;
					}
				}
				if($count==0)
				{
					$this->success('添加成功！',U('SubjGoods/index'));
				}elseif($count_no>0){
					$this->error('商品不存在！');
				}else{
					$this->error('系统错误，保存失败！');
				}
			}else{
				$this->error('请选择商品！');
			}
		}
	}

	public function del(){
            $id = I('get.id');
		    if(!is_numeric($id) || empty($id)){
				 $this->error('商品不存在！');
			}
			$data['subj_id'] = '';
			$data['subj_seat'] = '';
		    if( false === M('goods')->where('id='.$id)->save($data)){
				$this->error('系统错误，删除失败！');
			}else{
				$this->success('操作成功！',U('SubjGoods/index'));
		  }
	}

}