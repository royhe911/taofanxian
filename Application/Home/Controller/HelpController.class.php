<?php
namespace Home\Controller;
use Think\Controller;
use Common\Util\Util;
/**
 * @auth TRUE
 * @name 首页控制器
 * */

class HelpController extends Controller {
	/**
	 * @auth TRUE
	 * @name 查看首页
	 * */
    public function userhelp()
    {
    	$page = I('get.page','userhelp');
    	$this->assign('page',$page);
		$this->display($page);
    }


	public function sellerhelp()
	{
		$page = I('get.page','sellerlogin');
    	$this->assign('page',$page);
		$this->display($page);
	}


}

