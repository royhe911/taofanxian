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
class SubjDataController extends InterceptController {

	public function index(){
		
		$subjid = I('get.subjid',0);

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
		$subj = M('subj')->field('id,name')->select();
		foreach($subj as $val)
		{
			$subj_name[$val['id']] = $val['name'];
		}
		$where = '';
		if($subjid)
		{
			$where = 'subj_id='.$subjid;
		}else{
			$where = 'subj_id>0';
		}
		if($where)
		{
			$where .= ' and ';
		}
		$where .= 'endtime>'.time();
		$totalCount = M('goods')->where($where)->count();
		//echo M('goods')->getLastSql();exit;
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);

		$data = M('goods')->where($where)->order('(price_jian/discount_price) DESC,subj_id DESC,subj_seat DESC,endtime')->limit($page*$page_size,$page_size)->select();


		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->assign('subj',$subj);
		$this->assign('subjid',$subjid);
		$this->assign('subj_name',$subj_name);
		$this->assign('category',$category);
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
						$data['subj_id'] = '';
						$data['subj_seat'] = '';
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
					$this->success('成功删除！',U('SubjData/index'));
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

	public function editSubjSeat(){
		if(IS_POST)
		{
			$goods_id = I('post.id','');
			if($goods_id)
			{
				$goods = M('goods')->where('id='.$goods_id)->find();
				if($goods)
				{
					$subj_seat = I('post.subj_seat','');
					if($subj_seat)
					{
						$where = 'subj_id='.$goods['subj_id'].' and subj_seat='.$subj_seat.' and endtime>'.time();
						$seat_nu = M('goods')->where($where)->find();
						if($seat_nu){
							echo $goods['subj_seat'];die();//排序已存在
						}
					}
					$data['subj_seat'] = $subj_seat;
					if( false === M('goods' )->where('id='.$goods_id)->save($data)){
						echo $goods['subj_seat'];
					}else{
						echo $subj_seat;
					}	
				}else
				{
					echo false;
				}
				
			}else{
				echo false;
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
				$this->success('操作成功！',U('SubjData/index'));
		  }
	}

}