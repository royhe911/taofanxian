<?php
/**
 * 试用管理
 * User: hubing
 * Date: 2018/1/13
 * Time: 10:00
 */
  namespace Supporter\Model;
  use Think\Model\ViewModel;
  
  class TradeViewModel extends ViewModel{
  	public $viewFields = array(
  	                     'tbgoods_activity'=>array('id', 'cid', 'type' => 'msg', 'gname', 'url', 'image', 'unit_price', 'tag', 'days', 'format','scosts', 'money', 'num', 'state', 'starttime', 'endtime', '_as'=>'g', '_type'=>'LEFT'),
  	                     'tbsellerinfo'=>array('shopname', 'type', '_as'=>'s', '_on'=>'g.sid=s.id')
  	);
  }
 ?>