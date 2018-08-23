<?php
//加解密算法

/**
 * @param $string               //需要加密的内容
 * @param $operation            //两个参数 E 表示加密 D表示解密 不可省略
 * @param string $key           // 钥匙
 * @return bool|mixed|string
 */
 
function encrypt($string, $operation, $key='maoke'){
    $key=md5($key);
    $key_length=strlen($key);
    $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for($i=0;$i<=255;$i++){
        $rndkey[$i]=ord($key[$i%$key_length]);
        $box[$i]=$i;
    }
    for($j=$i=0;$i<256;$i++){
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++){
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='D'){
        if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){
            return substr($result,8);
        }else{
            return'';
        }
    }else{
        return str_replace('=','',base64_encode($result));
    }

}
//商品费用计算
/**
 * @param $price 商品价格
 * @return int  费用
 */
function cost($price){
    if($price<=0)          $cost=0;
    elseif( $price < 100 ) $cost=16;
    elseif( $price < 200 ) $cost=18;
    elseif( $price < 300 ) $cost=22;
    elseif( $price < 400 ) $cost=25;
    elseif ($price < 500) $cost=30;
    else $cost=floor($price * 0.10);
    return $cost;

}


/**
 * @param $time   传入时间戳
 * @return array  返回当天的 00:00:00 和23:59:59时的时间戳
 */
function timeInterval($time){
    $starttime=strtotime(date('Y-m-d 00:00:00',$time));
    $endtime  =strtotime(date('Y-m-d 23:59:59',$time));
    return array(
        'starttime'=>$starttime,
        'endtime'  =>$endtime,
    );
}
//function emptyCost($value){
//    if (empty($value)) return $cost=0;
//    if( $value = 4 ) $cost=4;
//    elseif( $price = 5 ) $cost=18;
//    elseif( $price < 300 ) $cost=22;
//    elseif( $price < 400 ) $cost=25;
//    else $cost=floor($price * 0.10);
//    return $cost;
//}
function f_round($num){
    return round($num,2);
}

//检查是否有权限访问当前方法
/**
 * @return bool 存在权限。返回true 不存在返回false
 */
function check_action(){
    $cname  = CONTROLLER_NAME;//当前控制器名
    $action = ACTION_NAME;
    $roleId = D('column')->where(array('a' => strtolower($action),'c'=>strtolower($cname)))->getField('id');
    $status = D('role')->where(array('id' => $_SESSION['user']['role'], 'msg' => 0))->getField('status');
    if(!in_array($roleId,explode(',', $status))){
        return false;
    }
    return true;

}


/**
 * 返回用户可用余额
 * @param $uid  用户id
 * @return bool 返回可用余额
 */
function GetUserMoney($uid){
    if(!$uid) return false;
    // $user_info=D('user')->field('money,yufujin')->where('uid='.$uid)->find();
    $user_info=D('user')->field('money')->where('uid='.$uid)->find();
    if(!$user_info) return false;
    // $available=$user_info['money'] - $user_info['yufujin'];
    $available=$user_info['money'];
    return $available;
}

/**
 * @param $uid 用户id
 * @param $money 此次变动金额
 * @param $tran_id  事务id
 * @param $msg 事件类型
 * @param $status 收/支
 * @return bool 返回bool值
 */
function save_available($uid, $money, $tran_id, $msg=0, $status=0,$now_money=0){
    $before_money=GetUserMoney($uid);
    if($before_money === false) return false;
    // if($now_money != 0) $before_money = $before_money + $now_money;
    if(!is_numeric($tran_id) || !is_numeric($msg) || ($status != 1 && $status != 2))  return false;
    if($status == 1){
        $after_money=$before_money - $money;
    }elseif($status == 2){
        $after_money=$before_money + $money;
    }
    $balances=array(
        'uid'=>$uid,
        'before_money'=>$before_money,
        'after_money' =>$after_money,
        'change_money'=>$money,
        'msg'=>$msg,
        'status'=>$status,
        'tran_id'=>$tran_id,
        'addtime'=>date('Y-m-d H:i:s',time()),
    );
    $balances_status=D('balances')->add($balances);
    if(!$balances_status) return false;
    return true;
}

function cost_redbag($actual_price){
    if($actual_price <= 0 )                              $cost=0;
    elseif($actual_price >    0 && $actual_price < 100)  $cost=4;
    elseif($actual_price >= 100 && $actual_price < 200)  $cost=5;
    elseif($actual_price >= 200 && $actual_price < 300)  $cost=6;
    elseif($actual_price >= 300 && $actual_price < 400)  $cost=8;
    elseif($actual_price >= 400                       )  $cost=10;
    return $cost;
}

//佣金计算
/**
 * @param $actual_price 实际下单价
 * @return array        返回佣金区间
 */
function cost_commision($actual_price){
    if($actual_price < 300)                               $cost=array(5);
    elseif ($actual_price >=300)                          $cost=array(5,6,7);
    return $cost;
}

function get_mi(){
    $mi=(file_get_contents("Application/Erp/Common/mi.txt")) or die('系统文件缺失！请联系管理员');
    //解密 $mi
    $mi=intval(encrypt($mi,'D','jishubu'));
    if(!is_numeric($mi) || time() > $mi) return false;
    return true;

}