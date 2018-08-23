<?php
/**
 * 订单管理
 * User: hubing
 * Date: 2018/1/24
 * Time: 10:00
 */
  namespace Supporter\Model;
  use Think\Model\ViewModel;
  
  class OrderViewModel extends ViewModel{
  	public $viewFields = array(
  	                     'tbtrialorders' => array('id','gid','uid','comfirm', '_as'=>'o', '_type'=>'LEFT'),
  	                     'tbgoods_activity' => array('unit_price', '_as'=>'g', '_on'=>'g.id=o.gid')
  	);
  }
 ?>