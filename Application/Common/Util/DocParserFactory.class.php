<?php
namespace Common\Util;
use  Common\Util\DocParser;
class DocParserFactory{
	public $controllers = array();	
	function __construct($rPath,$module){
		
	
		$controllers  =  $this->getControllers($rPath,$module);
		foreach($controllers as $v){
			$this->parser($v);
		}
	}	
		
	protected function getControllers($rPath , $module){

        $module_path = $rPath .'/'. $module . '/Controller/';  //控制器路径
        //dump($module_path);die;
        if(!is_dir($module_path)) return null;
        $module_path .= '/*.class.php';
        $ary_files = glob($module_path);
        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            }else {
                $files[] = '\\'. $module . '\\Controller\\' . basename($file, '.class.php');
            }
        }
        return $files;
    }	
	
	protected function parser($controller){
		

		$reflection = new \ReflectionClass ( $controller ); 
		
		$doc = $reflection->getDocComment();
		if(!$doc){return false;}
		$cdoc =(new DocParser() )->parse($doc);
		if( !isset($cdoc['auth']) ){return false;}
		if( $cdoc['auth'] !== true &&   strtolower( trim($cdoc['auth']) ) !== 'true' ){return false;}
		$controlleDital = array(
			'authsort' =>  isset($cdoc['authsort']) ? $cdoc['authsort'] : '10000',
			'fullname'=>$controller,
			'controller'=>$reflection->getShortName(),
			'name'=> isset($cdoc['name']) ? $cdoc['name'] : $reflection->getShortName()
		);
		
		$methodes = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
		
		$actions = array();
		foreach($methodes as $v){
			if($v->class == $reflection->getName()){			
				$doc = $v->getDocComment();
				if(!$doc){ continue ; }
				$mdoc =(new DocParser() )->parse($doc);
				if( !isset($mdoc['auth']) ){ continue ; }
				if( $mdoc['auth'] !== true &&   strtolower( trim($mdoc['auth']) ) !== 'true' ){ continue ; }
				$actionDital = array(
					'authsort' =>  isset($mdoc['authsort']) ? $mdoc['authsort'] : '10000',
					'actionname'=>$v->name,
					'name'=>isset($mdoc['name']) ? $mdoc['name'] : $v->name,
					'rule'=>isset($mdoc['rule']) ? json_decode( $mdoc['rule'],true) : json_decode( '{"use":{"title":"允许","type":"checkbox"}}' , true )
				);
				array_push($actions,$actionDital);
				usort($actions, function($a , $b){
					if( $a['authsort'] == $b['authsort']){
						return 0;
					}
					return $a['authsort']>$b['authsort'];
				});
			}
		}
		$controlleDital['actions'] = $actions;
//		$controllers
		array_push($this->controllers ,$controlleDital);
		
		usort($this->controllers, function($a , $b){
			if( $a['authsort'] == $b['authsort']){
				return 0;
			}
			return $a['authsort']>$b['authsort'];
		});
	}

	public function getDetail(){
		return $this->controllers;
	}	
		
}
