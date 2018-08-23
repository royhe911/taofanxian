<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;

class IndexController extends Controller {
	
	public function index(){

		$this->display();
	}
	public function getqq(){
	    if(IS_AJAX){

        $qq=D('account')->field('qq,info')->where('role = 6 and msg =0 ')->select();
        $res=json_encode($qq);
        $this->ajaxReturn(array('msg' => 1, 'info' => $res));

	    }
    }
}
?>