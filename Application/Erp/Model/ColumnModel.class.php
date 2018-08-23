<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model;

  Class ColumnModel extends Model{  

  	 protected $_map = array(
  	 		'adminRole' => 'fid',
  	 		'myname' => 'name',
  	 		'cname'  => 'c',
  	 		'aname'  => 'a',
  	 		'num'    => 'sort'
  	 );
  	
     protected $_validate = array(
     		array('name','require','请填写栏目名称！'),
     		array('c','require','请填写控制器名！'),
     		array('a,fid','checkAction','请填写方法名！',1,'callback'),
     		array('c','/^[A-Za-z]+$/', '控制器名必须为英文字母！'),
     		array('a','/^[A-Za-z]+$/', '方法名必须为英文字母！',2)
     );
        
     protected $_auto = array(
     		array('c','strtolower',3,'function'),
     		array('a','strtolower',3,'function'),
     		array('addtime','time',1,'function')
     );
     
     
     protected function checkAction($data){
     	
     	if ($data['fid'] >= 1){
     		if (!empty($data['a'])){
     			return true;
     		} else {
     			return false;
     		}
     	} else {
     		return true;
     	}
     }
  }
 ?>