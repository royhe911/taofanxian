<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
class InterceptController extends Controller {
    function _initialize(){
	   $this->checkLogin();

    }
	function checkLogin(){

		$manager = $_SESSION['admin_info'] ;
		if(!$manager){
			redirect(U('User/login'));
		}

	}

	
	
	
	
}
