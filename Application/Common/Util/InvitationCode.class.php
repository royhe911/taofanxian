<?php
namespace Common\Util;
use  Common\Util\Util;
class InvitationCode {
	const BaseCode = "4000015000";
	public function create($num){
		$num = SELF::BaseCode . $num;
		return  $this->to62($num);	
	}
	
	public function parse($code){
		
		return substr( $this->from62($code) , strlen(SELF::BaseCode) );
	}
	
	function to62($num) {
		$to = 62;
		$dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$ret = '';
		do {
			$ret = $dict[bcmod($num, $to)] . $ret;
			$num = bcdiv($num, $to);
		} while ($num > 0);
		return $ret;
	}
	function from62($num) {
		$from = 62;
		$num = strval($num);
		$dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$len = strlen($num);
		$dec = 0;
		for($i = 0; $i < $len; $i++) {
			$pos = strpos($dict, $num[$i]);
			$dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
		}
		return $dec;
	}
}
?>