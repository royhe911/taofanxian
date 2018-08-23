<?php
namespace Common\Util;

class Util {

    static public function getInstance( $classname ){
    	$class = '\\' . __NAMESPACE__ . '\\' . $classname ;
		if(class_exists($class)){
        	return  new $class($options);
        }else{
            E('没有这个类');
		}
    }

}
