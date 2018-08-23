<?php
/**
 * 帐号管理
 * User: hubing
 * Date: 2018/3/31
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model\ViewModel;
  
  class RechargeViewModel extends ViewModel{
  	public $viewFields = array(
  			             'recharge' => array('id', 'money', 'img', 'msg', 'addtime', '_as' => 'r', '_type' => 'LEFT'),
  	                     'user' => array('nickname', '_as'=>'u', '_on' => 'u.id = r.uid')
  	);
  }
 ?>