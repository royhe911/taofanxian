<?php
namespace Common\Util;

class String{
	
	
	public function replace( $str , $bind){
	
		foreach($bind as $key=>$v){
			
			$str = str_replace($key,$v,$str);
		}
		return $str;
	}
	
	
	
}



?>