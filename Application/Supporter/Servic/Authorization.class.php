<?php
namespace Admin\Servic;

class Authorization {
    public function getList(){
    	return $data = M('authorization')->where()->order('id')->select();
    }
  
    
}
