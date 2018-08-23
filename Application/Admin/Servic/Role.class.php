<?php
namespace Admin\Servic;

class Role {
    public function getList(){
    	return $data = M('role')->where()->order('level')->select();
    }
  
    public function getRole($id){
    	
		
		return $data = M('role')->where(array('id'=>$id))->find();
		
    }
    
}
