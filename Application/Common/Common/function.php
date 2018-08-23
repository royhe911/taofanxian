<?php
//获取随机数
function RandStr($len)
	{
		$chars = array(
				"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
				"l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
				"w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
				"H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
				"S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
				"3", "4", "5", "6", "7", "8", "9"
		);
		$charsLen = count($chars) - 1;
		shuffle($chars);
		$output = "";
		for ($i=0; $i<$len; $i++)
		{
			$output .= $chars[mt_rand(0, $charsLen)];
		}
		return $output;
	}
	
	
	
	/**
 * TODO 基础分页的相同代码封装，使前台的代码更少
 * @param $count 要分页的总记录数
 * @param int $pagesize 每页查询条数
 * @return \Think\Page
 */
function getpage($count, $pagesize = 10) {
    $p = new Think\Page($count, $pagesize);
    $p->setConfig('header', '<span style="border: 0;">共 %TOTAL_PAGE% 页</span>');
    $p->setConfig('prev', '<');//上一页
    $p->setConfig('next', '>');//下一页
    $p->setConfig('last', '>>');//末页
    $p->setConfig('first', '<<');//首页
    $p->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');
    $p->lastSuffix = false;//最后一页不显示为总页数
    return $p;
}

function apply_num($id){ 
	$Cate=M('tbusertrade'); 
	$count=$Cate->where('gid='.$id)->count(); 
	/*$where['id'] = $id;
	$where['role_id'] = 1;
	$cid = M('tbgoods')->where($where)->getField('cid');
    if(in_array($cid, array(10001,10002,10005))){
		$count +=96 ;
	}elseif(in_array($cid, array(10008,10015))){
		$count +=230 ;
	}elseif(in_array($cid, array(10009,10011))){
		$count +=156 ;
	}elseif(in_array($cid, array(10007,10013,10012,10014))){
		$count +=72 ;
	}elseif(in_array($cid, array(10006))){
		$count +=87 ;
	}elseif(in_array($cid, array(10003,10010))){
		$count +=128 ;
	}elseif(in_array($cid, array(10004))){
		$count +=62;
	}*/
	$goods =  M('tbgoods')->where('id='.$id)->find();
	if($goods['click_alter']<=5&&$goods['click_alter']>0)
	{
		$count += $goods['click_alter']+ceil((time()-strtotime($goods['confirmdate']))/(60*60*3));//3个小时增加一人
	}elseif($goods['click_alter']>5 && $goods['click_alter']<=10 )
	{
		$count += $goods['click_alter']+ceil((time()-strtotime($goods['confirmdate']))/(60*60*2));//2个小时增加一人
	}elseif($goods['click_alter']>10 && $goods['click_alter']<=15 )
	{
		$count += $goods['click_alter']+ceil((time()-strtotime($goods['confirmdate']))/(60*60));//1个小时增加一人
	}elseif($goods['click_alter']>15 && $goods['click_alter']<=20 )
	{
		$count += $goods['click_alter']+ceil((time()-strtotime($goods['confirmdate']))/(30*60));//半小时增加一人
	}elseif($goods['click_alter']>20)
	{
		$count += $goods['click_alter']+ceil((time()-strtotime($goods['confirmdate']))/(20*60));//1小时增加3人
	}
	return $count; 
} 


 //试用成功人数
    function stock_num($id){
    	$z=M('tbusertrade')->where('gid='.$id.'  and subconfirm=2')->count(); 
		$goods =  M('tbgoods')->where('id='.$id)->find();
    	if($goods['click_alter']>0)
		{
			if(((strtotime($goods['etime'])-time())/(60*60*24))<=1)
			{
				return 1;
			}
			if($goods['kucun']>2)
			{
				$days = ceil((time()-strtotime($goods['confirmdate']))/(60*60*24));
				$count = $days;
				if($goods['kucun']+$goods['sell_number']>20)
				{
					$count = $days*2;
				}
			}else
			{
				$count = $goods['sell_number'];
			} 
			$tol = $goods['kucun']+$goods['sell_number'];
			$count = $count>=$tol?2:$count;
			$count = $count>$z?$count:$z;
		}else{
			$count = $z;
		}
        return $count;
    }

    function  wxSendAll($arr,$next_openid=''){
           $Msg = new \Org\Net\Wechat($arr);
          return $Msg-> getUserList($next_openid);
        }

        /**
         * @param $openid
         * @param $gid
         * @return array|bool
         */
        function wxSendMsg($openid,$gid){
            $arr=array();
            $arr['token']='taofanxian';
            $arr['encodingaeskey']='CNzl6hKTXP6KTawFhPWp8pTYsgxxldcFUMLAIJzIcWL';
            $arr['appid']='wx4414de37fbdc9a3f';
            $arr['appsecret']='f58f1297c1f1e3386803373514a8ef41';
            $arr['debug']=true;
            $Msg = new \Org\Net\Wechat($arr);

            $data='{
                                      "touser":"' . $openid. '",
                                      "template_id":"-zJAEG8uOUl6w_A3wwpZnGaJ2W81wsuqT-eBAzWmRaI",
                                      "url":"http://www.taofanxian.com/m/index.php?mod=tborders&act=order&flag=1",
                                      "miniprogram":{
                                        "appid":"",
                                        "pagepath":""
                                      },
                                      "data":{
                                              "first": {
                                                  "value":"亲，您申请的商品ID:'.$gid.'免费试用品已通过审核，请务必24小时内下单",
                                                  "color":"#333"
                                              },
                                               "keyword1": {
                                                  "value":"审核通过！",
                                                  "color":"#333"
                                              },
                                              "keyword2":{
                                                  "value":"'.date("Y-m-d H:i",time()).'",
                                                  "color":"#333"
                                              },
                                              "remark":{
                                                  "value":"立即参与活动",
                                                  "color":"#333"
                                              }
                                      }
                                  }';

//            $data='{
//                                      "touser":"' . $openid. '",
//                                      "template_id":"yp62pBwtnCvwumHkYuw4YuY4M3stOOAttIm_J9zyR2A",
//                                      "url":"http://www.taofanxian.com/m/index.php?mod=tborders&act=order&flag=1",
//                                      "miniprogram":{
//                                        "appid":"",
//                                        "pagepath":""
//                                      },
//                                      "data":{
//                                              "first": {
//                                                  "value":"亲爱的demo，你的好友招纳了新的好友",
//                                                  "color":"#333"
//                                              },
//                                              "keyword1":{
//                                                  "value":"'.date("Y-m-d H:i",time()).'",
//                                                  "color":"#ff5050"
//                                              },
//                                              "keyword2": {
//                                                  "value":"5积分",
//                                                  "color":"#333"
//                                              },
//                                              "keyword3": {
//                                                  "value":"您的好友jjjjj招纳了新的好友",
//                                                  "color":"#333"
//                                              },
//                                              "keyword4": {
//                                                  "value":"6",
//                                                  "color":"#333"
//                                              },
//                                              "remark":{
//                                                  "value":"立即参与活动",
//                                                  "color":"#333"
//                                              }
//                                      }
//                                  }';
         return   $Msg->sendTemplateMessage($data);
        }



        //获取ip归属地
        function getaddr($app_ip){
//            import('ORG.Net.IpLocation');// 导入IpLocation类
            if ($app_ip){
                $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
                $area = $Ip->getlocation($app_ip); // 获取某个IP地址所在的位置
                return $area['country'];
            }else{
                return '';
            }

        }

        /**
         * 发送模板短信
         * @param to 手机号码集合,用英文逗号分开
         * @param datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
         * @param $tempId 模板Id,测试应用和未上线应用使用测试模板请填写1，正式应用上线后填写已申请审核通过的模板ID
         */
        function sendTemplateSMS($to,$datas,$tempId,$appId='8a216da85e6fff2b015e79280b69054c')
        {
//            import("ORG.Net.Rest");
            // 初始化REST SDK
            //主帐号,对应开官网发者主账号下的 ACCOUNT SID
            $accountSid= '8a216da85e6fff2b015e79280b1a0547';

            //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            $accountToken= '993ee1fa02e7418d8d48f07a79b0eab3';

            //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
            //在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
            //$appId='8a216da85e6fff2b015e79280b69054c';
			//$appId='8aaf07086010a0eb016019e9b4f001cf';
            //请求地址
            //沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
            //生产环境（用户应用上线使用）：app.cloopen.com
            $serverIP='app.cloopen.com';


            //请求端口，生产环境和沙盒环境一致
            $serverPort='8883';

            //REST版本号，在官网文档REST介绍中获得。
            $softVersion='2013-12-26';
            $rest = new \Org\Net\REST($serverIP,$serverPort,$softVersion);
            $rest->setAccount($accountSid,$accountToken);
            $rest->setAppId($appId);

            // 发送模板短信
//            echo "Sending TemplateSMS to $to <br/>";
            $result = $rest->sendTemplateSMS($to,$datas,$tempId);
//            if($result == NULL ) {
//                echo "result error!";
//                break;
//            }
//            if($result->statusCode!=0) {
//                echo "error code :" . $result->statusCode . "<br>";
//                echo "error msg :" . $result->statusMsg . "<br>";
                //TODO 添加错误处理逻辑
//            }else{
//                echo "Sendind TemplateSMS success!<br/>";
                // 获取返回信息
//                $smsmessage = $result->TemplateSMS;
//                echo "dateCreated:".$smsmessage->dateCreated."<br/>";
//                echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
                //TODO 添加成功处理逻辑
//            }
        }

    // 检测输入的验证码是否正确，$code为用户输入的验证码字符串
    function check_verify($code, $id = ''){
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }
    //用户登录密码加密方式
    function deep_jm($val,$key=DDKEY){
        return md5(md5($key.$val).$key);
    }


    //创建TOKEN
    function createToken() {
        $code = chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE)) .       chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE)) . chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE));
        session('TOKEN', authcode($code));
    }
    //判断TOKEN
    function checkToken($token) {
        if ($token == session('TOKEN')) {
            session('TOKEN', NULL);
            return TRUE;
        } else {
            return FALSE;
        }
    }
    /* 加密TOKEN */
    function authcode($str) {
        $key = "YOURKEY";
        $str = substr(md5($str), 8, 10);
        return md5($key . $str);
    }
    //获取商品名
    function getgoodsname($gid){
        $data=M('tbgoods');
       return $data->where(' id='.$gid)->getField('goods_name');
    }
    //获取用户名
    function getusername($uid){
        $data=M('user');
        return $data->where(' id='.$uid)->getField('ddusername');
    }
    //获取qq
    function getqq($uid){
        $data=M('tbinfo');
        return $data->where(' uid='.$uid)->getField('qq');
    }

    //获取淘气值截图
    function getimg2($uid){
        $data=M('tbinfo');
        return $data->where(' uid='.$uid)->getField('img2');
    }

    //获取赠品截图
    function getpimg($gid){
        $data=M('tbgoods');
        return $data->where(' id='.$gid)->getField('img');
    }

    //获取商品主图
    function getimg($gid){
        $data=M('tbgoods');
        return $data->where(' id='.$gid)->getField('img2');
    }
    //获取商品上传日期
    function getaddtime($gid){
        $data=M('tbgoods');
        return $data->where(' id='.$gid)->getField('addtime');
    }
        
    
 //在线交易订单支付处理函数
//函数功能：根据支付接口传回的数据判断该订单是否已经支付成功；
//返回值：如果订单已经成功支付，返回true，否则返回false；   
function checkorderstatus($ordid){
    $Ord=M('tborderlist');
    $ordstatus=$Ord->where('ordid='.$ordid)->getField('ordstatus');
    if($ordstatus==1){
        return true;
    }else{
        return false;    
    }
 }
  
 //获取交易类型
 function checkordertype($ordid){
    $Ord=M('tborderlist');
    $payment_type=$Ord->where('ordid='.$ordid)->find();
    return $payment_type;  
 }
 
 //处理订单函数
 //更新订单状态，写入订单支付后返回的数据
 function orderhandle($parameter){
    $ordid=$parameter['out_trade_no'];
    $data['payment_trade_no']      =$parameter['trade_no'];
    $data['payment_trade_status']  =$parameter['trade_status'];
    $data['payment_notify_id']     =$parameter['notify_id'];
    $data['payment_notify_time']   =$parameter['notify_time'];
    $data['payment_buyer_email']   =$parameter['buyer_email'];
    $data['ordstatus']             =1;
    $data['uptime']                =date('Y-m-d H:i:s');
    $Ord=M('tborderlist');
    $Ord->where('ordid='.$ordid)->save($data);
    
    if($parameter['payment_type']!=99)
    {
    	//更新个人余额信息
   	 	M('duoduo2010')->where('id='.$parameter['id'])->setInc('fund',$parameter['total_fee']);
    	$_SESSION['ext_user']['fund'] = $_SESSION['ext_user']['fund']+$parameter['total_fee'];
    }
 } 
  
   //完善商户支付宝信息
 function update_zfb($id,$email){
    $data['zfb_accounts'] = $email;
    $Ord=M('duoduo2010');
    $Ord->where('id='.$id)->save($data);
    $_SESSION['ext_user']['zfb_accounts'] = $email;
 }
  
  
 //获取一个随机且唯一的订单号；
 function getordcode(){
    $Ord=M('tborderlist');
    $numbers = range (10,99);
    shuffle ($numbers); 
    $code=array_slice($numbers,0,4); 
    $ordcode=$code[0].$code[1].$code[2].$code[3];
    $oldcode=$Ord->where("ordid='".$ordcode."'")->getField('id');
    if($oldcode){
        getordcode();
    }else{
        return $ordcode;
    }
 }

  //获取今天所需开奖人数；
 function getdaynum($gid)
 {
    $goodsdays = M('tbgoods_activity')->where('id='.$gid)->getField('days');
    $days = json_decode($goodsdays,true);
    foreach($days as $val)
    {
    	$now = (int)date('d');
    	if($val['day']==$now)
    	{
    		$num = $val['num'];
    		break;
    	}
    }
    return $num;
 }
 
 function create( $page , $size , $count ){
		
		 $totalPages = ceil($count / $size);
		 $totalPages = $totalPages < $page ? $page : $totalPages;
		
		 $startRecord = ($page - 1)  * $size + 1;
		 $endRecord = ($page - 1)  * $size + $size ;
		 if($endRecord > $count){$endRecord = $count;}
		 $pagination = array(
		    'baseUrl' =>U('','',false),
		 	'startPage' => $page,
		 	'totalPages' =>$totalPages,
		 	'startRecord'=>$startRecord,
		 	'endRecord'=>$endRecord,
		 	'totalCount'=>$count,
		    'pageSize'  =>$size,
		    'getParams' => I('get.'),
		    'first'=>'<<',
			'last'=>'>>',
			'prev'=>'<',
			'next'=>'>',
			'visiblePages'=>10,
			'initiateStartPageClick'=>false,	
		 );
		return $pagination;
		
	}

 /**
  * 获取分页数
  *
  * @param array $data
  * @param int $length
  * @return int 页数
  */
 function myPage($data,$length=1){
 	$length = intval($length);
 	if (!empty($length) && is_array($data)){
 		return ceil(count($data)/$length);
 	} else {
 		return 0;
 	}
 }	

 /**
  * 将正整数转换为中文数字
  *
  * @param int $num
  */
 function integerToch($num){
 	
 	if(16 >= strlen($num) && is_numeric($num)){
 		$param = str_split($num);
 		$unit = array(0 => '', 1 => '十', 2 => '百', 3 => '千', 4 => '万', 5 => '亿', 6 => '兆');
 		$char = array(0 => '零', 1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '七', 8 => '八', 9 => '九');
 		$param = str_split($num);
 		$param = array_reverse($param);//翻转数组
 		
 		foreach($param as $key => $val){
 			$k = $key%4;//取余确定单位
 			if(0 == $k && $key > 0) $k = 3 + ($key/4);//特殊单位
 			
 			if(0 == $val && $key != 0){
 				$param[$key] = ($k >= 4) ? $unit[$k] : $char[$val];//特殊单位保留
 				if($param[($key-1)] == $param[$key]){//相邻两个皆为零则去掉无用的零
 					$param[($key-1)] = '';
 				}
 			} else {
 				$param[$key] = $char[$val].$unit[$k];
 			}
 		}//print_r($param);exit;
 		$param = array_filter($param);//去掉空值
 		$param = array_reverse($param);//翻转数组
 		if('一十' == $param[0]) $param[0] = '十';
 		if('零' == end($param)) array_pop($param);//去掉末位的零
 		$param = implode('', $param);
 	}
 	return !empty($param) ? $param : $num;
 }
 
 /**
  * 试用商品每单价格(元/单)
  * @param float price 试客下单价格(元)
  */
 function getPrice($price){
 	if (!empty($price)){
 		return ($price + $price*0.05 + $price* 0.02 + 2);
 	} else {
 		return 0;
 	}
 }
 
 /**
  * 对array_column的低版本兼容
  * @param unknown $array
  * @param unknown $columnKey
  * @param unknown $indexKey
  * @return unknown[]
  */ 
 function my_array_column($array, $columnKey, $indexKey = null){
 	if(!function_exists("array_column")){
 		$result = array();
 		foreach ($array as $subArray) {
 			if (is_null($indexKey) && array_key_exists($columnKey, $subArray)) {
 				$result[] = is_object($subArray)?$subArray->$columnKey: $subArray[$columnKey];
 			} elseif (array_key_exists($indexKey, $subArray)) {
 				if (is_null($columnKey)) {
 					$index = is_object($subArray)?$subArray->$indexKey: $subArray[$indexKey];
 					$result[$index] = $subArray;
 				} elseif (array_key_exists($columnKey, $subArray)) {
 					$index = is_object($subArray)?$subArray->$indexKey: $subArray[$indexKey];
 					$result[$index] = is_object($subArray)?$subArray->$columnKey: $subArray[$columnKey];
 				}
 			}
 		}
 	} else {
 		$result = array_column($array, $columnKey, $indexKey);
 	}
 	return $result;
 }
 
 /**
  * 试用商品类型
  * @param number $msg
  */
 function getSpecies($msg=1){
 	$species = array(1 => '新品提升综合权重', 2 => '爆款打造/维护');
 	return $species[$msg];
 }
 
 /**
  * 过滤提交的信息
  * @param unknown $data
  * @return string
  */
 function test_input($data) {
 	$data = trim($data);
 	$data = stripslashes($data);
 	$data = htmlspecialchars($data);
 	return $data;
 }
 
 /**
  * 获取商品类型
  * @param unknown $id
  * @return string
  */
 function getPlatformName($id){
 	$id = intval($id);
 	$msg = array(1 => '淘宝', 2 => '天猫');
 	return $msg[$id];
 }
 
 //单位转换(分转为元)
 function getMoney($money){
 	return $money*0.01;
 }
 
 