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

class MessageController extends BaseController
{
    public function index()
    {
    	$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
        $res = M('article');
        $data = $res->where(' (cid=10018 or cid=10017) and del=0')->order('addtime desc')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('tbcash')->where($where)->order('uptime DESC')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
        $this->assign('data', $data);
        $this->display();
    }
    //消息详情
    public function news_detail()
    {
        $id = I('get.id', '');
        if ($id) {
            $res = M('article');
            $new_detail = $res->where(' id=' . $id . ' and del=0')->find();
            $this->assign('new_detail', $new_detail);
            $this->display();
        } else {
            $this->error("新闻数据拉取失败，请刷新页面");
        }
    }
}