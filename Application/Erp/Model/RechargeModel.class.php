<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/4/9
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model;

  Class RechargeModel extends Model{  
  	
	  	protected $_map = array(
	  			'rechargenum' => 'money'
	  	);
	  	
	  	protected $_validate = array(
	  			array('money','require','请填写充值金额！'),
	  			array('money','checkMoney', '金额必须为正数！',1,'callback'),
	  			array('img','require','请上传充值截图！')
	  	);
	  	
	  	protected $_auto = array(
	  			array('uid','getUid',3,'callback'),
//	  			array('money','getMoney',3,'callback'),
	  			array('addtime','time',1,'function')
	  	);
	  	
	  	//金额检测
	  	protected function checkMoney($money){
	  		if (!empty($money) && $money >0){
	  			return true;
	  		} else {
	  			return false;
	  		}
	  	}
	  	
	  	//单位转换
//	  	protected function getMoney($money){
//	  		$money = intval($money);
//	  		return $money*100;
//	  	}
	  	
	  	//获取用户ID
	  	protected function getUid(){
	  		$info = D('user')->where(array('uid' => $_SESSION['user']['id']))->field('id')->find();
	  		return $info['id'];
	  	}
  }
 ?>