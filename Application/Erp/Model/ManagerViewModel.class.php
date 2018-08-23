<?php
/**
 * 帐号管理
 * User: hubing
 * Date: 2018/3/31
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model\ViewModel;
  
  class ManagerViewModel extends ViewModel{
  	public $viewFields = array(
  	                     'account' => array('id', 'name', 'realname','role' ,'wechat', 'info', 'qq', 'msg', 'addtime', '_as' => 'a', '_type'=>'LEFT'),
  	                     'role' => array('name' => 'role', '_as'=>'r', '_on' => 'r.id = a.role')
  	);
  }
 ?>