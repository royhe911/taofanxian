<?php
namespace Common\Util;
use Common\Util\Util;
class Send {
	
	
	public function post($url , $data ){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_HEADER, 0 );
		// post数据
		curl_setopt($ch, CURLOPT_POST, 1);
		// post的变量
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		
		$output = curl_exec($ch);
		
		
		if($output === false)
		{
//		    echo 'Curl error: ' . curl_error($ch);
		}
		curl_close($ch);
		
		return $output;
	}

}

?>