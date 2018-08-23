<?php
  namespace Admin\Model;
  use Think\Model\ViewModel;

  class TradeViewModel extends ViewModel{
  	public $viewFields = array(
  	                           'tbusertrade'=>array('id', 'gid', 'uid', 'tb_id', 'tb_item', 'addtime', 'tixian_comdate', '_as'=>'ut', '_type'=>'LEFT'),
  	                           'tbgoods'=>array('goods_name', 'img2', 'real_price', 'red_price','_as'=>'g', '_on'=>'g.id=ut.gid')
  	);
  }
 ?>