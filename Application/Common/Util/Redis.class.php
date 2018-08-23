<?php
namespace Common\Util;
class Redis extends \Think\Cache\Driver\Redis{
	
	
	
	
	
	public function setToJson($name, $value, $expire = null) {
		return $this->set($name, json_encode($value), $expire);
	}
	
	public function getToJson($name) {
		return  json_decode( $this->get( $name ) ,true);
	}

	 /**
     * 写入hash缓存
     * @access public
     * @param string $name 缓存变量名
	 * @param string $key 缓存键名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function hSet($name ,  $key ,  $value ) {
    	$name   =   $this->options['prefix'].$name;
    	return  $this->handler->hSet($name, $key, $value);
    }

	public function hGet($name, $key ) {
    	$name   =   $this->options['prefix'].$name;
    	return  $this->handler->hGet($name, $key );
    }
	public function hLen( $name ){
		$name   =   $this->options['prefix'].$name;
		return  $this->handler->hLen($name );
	}
	public function hGetAll( $name ){
		$name   =   $this->options['prefix'].$name;
    	return  $this->handler->hGetAll($name );
	}
	public function hGetAllToJson( $name ){
		$name   =   $this->options['prefix'].$name;
    	$result = $this->handler->hGetAll($name );
		
		$data = array();
		
		foreach($result as $key=>$v){
			$data[$key] = json_decode($v , true);
		}
		return empty($data) ? null : $data; 		
		
	}
	
	
	public function hDel( $name , $key){
		$name   =   $this->options['prefix'].$name;
		return $this->handler->hDel($name, $key );
	}
	public function hSetToJson($name ,  $key ,  $value ) {
    	return  $this->hSet($name, $key, json_encode($value) );
    }
	public function hGetToJson($name, $key ) {
    	return  json_decode( $this->hGet($name, $key ) ,true);
    }
	
	public function keys($name){
		$name = $this->options['prefix'].$name;
		return $this->handler->keys( $name );
		
	}
	public function zAdd($name , $key , $value){
			$name   =   $this->options['prefix'].$name;
			return $this->handler->zAdd($name, $key , $value);		
	}

	public function zRange($name , $start , $end ){
			$name   =   $this->options['prefix'].$name;
			return $this->handler->zRange($name , $start , $end );		
	}
	
}
