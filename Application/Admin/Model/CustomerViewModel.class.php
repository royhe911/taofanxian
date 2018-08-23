<?php
/**
 * 顾客名单(参与过某试用)
 * User: hubing
 * Date: 2018/1/15
 * Time: 10:00
 */
  namespace Admin\Model;
  use Think\Model\ViewModel;
  
  class CustomerViewModel extends ViewModel{
  	public $viewFields = array(
  	                     'tbtrialorders'=>array('id', 'gid', 'tb_id','tb_item', 'comfirm', 'real_price','apply_time', 'addtime', '_as'=>'t', '_type'=>'LEFT'),
  	                     'user'=>array('ddusername', '_as'=>'u', '_on'=>'u.id=t.uid')
  	);
  }
 ?>