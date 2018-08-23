<?php
namespace Common\Servic;

class Servic {
    protected $hasCahce = false;	
	protected $cacheHandler = null;
    static public function getInstance( $classname , $isCache){
    	$class = $isCache ? '\\' . __NAMESPACE__ . '\\Cache\\' . $classname : '\\' . __NAMESPACE__ . '\\' . $classname;

		/*echo $class;
		die;*/
		if(class_exists($class)){
			$options['hasCahce'] = $isCache;
        	return  new $class($options);
        }else{
            E('没有这个类');
		}
    }
	
	function __construct($options){
		if($options['hasCahce']){
			$this->hasCahce = true;
			$this->cacheHandler = new \Common\Util\Redis();
		}
		
	}


    
}


