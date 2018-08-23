<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/12
 * Time: 11:40
 */

namespace Supporter\Controller;
use Think\Controller;

class RechargeController extends BaseController
{
	
	       //在类初始化方法中，引入相关类库    
       public function _initialize() {
	        vendor('Alipay.Corefunction');
	        vendor('Alipay.Md5function');
	        vendor('Alipay.Notify');
	        vendor('Alipay.Submit');    
	    }
	    
	    //doalipay方法
	        /*该方法其实就是将接口文件包下alipayapi.php的内容复制过来
	          然后进行相关处理
	        */
	    public function doalipay(){


	       
	       //这里我们通过TP的C函数把配置项参数读出，赋给$alipay_config；
	       $alipay_config=C('alipay_config');  
	 
	        /**************************请求参数**************************/
	        $payment_type = "1"; //支付类型 //必填，不能修改
	        $notify_url = C('alipay.notify_url'); //服务器异步通知页面路径
	        $return_url = C('alipay.return_url'); //页面跳转同步通知页面路径
	        $seller_email = C('alipay.seller_email');//卖家支付宝帐户必填
	        $out_trade_no = getordcode();//$_POST['trade_no'];//商户订单号 通过支付页面的表单进行传递，注意要唯一！
	        if($_POST['zfb'])
	        {
		        $subject = '支付宝帐号绑定';//$_POST['ordsubject'];  //订单名称 //必填 通过支付页面的表单进行传递
		        $total_fee = '0.1';   //付款金额  //必填 通过支付页面的表单进行传递
		        $payment_type = 99;		//支付类型 1在线即时支付、2打款支付、99提现认证
		        
		        //更新商户支付宝信息
			    $data['zfb'] = $_POST['zfb'];
			    $Ord=M('duoduo2010');
			    $Ord->where('id='.$_SESSION['ext_user']['id'])->save($data);
			    $_SESSION['ext_user']['zfb'] = $_POST['zfb'];
		        
	        }else {
		        $subject = '商户充值';//$_POST['ordsubject'];  //订单名称 //必填 通过支付页面的表单进行传递
		        $total_fee = $_POST['amount'];   //付款金额  //必填 通过支付页面的表单进行传递
		        $payment_type = 1;		//支付类型 1在线即时支付、2打款支付
	        }
	        
	       /* $body = $_POST['ordbody'];  //订单描述 通过支付页面的表单进行传递
	        $show_url = $_POST['ordshow_url'];  //商品展示地址 通过支付页面的表单进行传递
	        $anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
	        $exter_invoke_ip = get_client_ip(); //客户端的IP地址 */
	        /************************************************************/
			
	        $data = array(
					'userid'=>$_SESSION['ext_user']['id'],
					'username'=>$_SESSION['ext_user']['adminname'],
					'ordid'=>$out_trade_no,
					'productid'=>0,
					'ordtitle'=>$subject,
					'ordbuynum'=>1,
					'ordprice'=>$total_fee,
					'ordfee'=>$total_fee,
					'ordstatus'=>0,
					'payment_type'=>$payment_type,			//支付类型 1在线即时支付、2打款支付
					'addtime'=>date('Y-m-d H:i:s'),
					'uptime'=>date('Y-m-d H:i:s'),
			);
			if(!M('tborderlist')->add($data)){
				$this->error('系统错误，提交失败！');
			}else{
				$parameter = array(
		        "service" => "create_direct_pay_by_user",
		        "partner" => trim($alipay_config['partner']),
		        "payment_type"    => $payment_type,
		        "notify_url"    => $notify_url,
		        "return_url"    => $return_url,
		        "seller_email"    => $seller_email,
		        "out_trade_no"    => $out_trade_no,
		        "subject"    => $subject,
		        "total_fee"    => $total_fee,
		       /* "body"            => $body,
		        "show_url"    => $show_url,
		        "anti_phishing_key"    => $anti_phishing_key,
		        "exter_invoke_ip"    => $exter_invoke_ip,*/
		        "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
		        );
		        //建立请求
		        $alipaySubmit = new \AlipaySubmit($alipay_config);
		        $html_text = $alipaySubmit->buildRequestForm($parameter,"post", "确认");
		        echo $html_text;
			}
	        //构造要请求的参数数组，无需改动
	    
	    }
	    
	        /******************************
	        服务器异步通知页面方法
	        
	        *******************************/
	    function notifyurl(){
	
	        $alipay_config=C('alipay_config');
	        //计算得出通知验证结果
	        $alipayNotify = new \AlipayNotify($alipay_config);
	        $verify_result = $alipayNotify->verifyNotify();
	        if($verify_result) {
	               //验证成功
	                   //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	           $out_trade_no   = $_POST['out_trade_no'];      //商户订单号
	           $trade_no       = $_POST['trade_no'];          //支付宝交易号
	           $trade_status   = $_POST['trade_status'];      //交易状态
	           $total_fee      = $_POST['total_fee'];         //交易金额
	           $notify_id      = $_POST['notify_id'];         //通知校验ID。
	           $notify_time    = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
	           $buyer_email    = $_POST['buyer_email'];       //买家支付宝帐号；
	           $parameter = array(
	             "out_trade_no"     => $out_trade_no, //商户订单编号；
	             "trade_no"     => $trade_no,     //支付宝交易号；
	             "total_fee"     => $total_fee,    //交易金额；
	             "trade_status"     => $trade_status, //交易状态
	             "notify_id"     => $notify_id,    //通知校验ID。
	             "notify_time"   => $notify_time,  //通知的发送时间。
	             "buyer_email"   => $buyer_email,  //买家支付宝帐号；
	           );
	           if($_POST['trade_status'] == 'TRADE_FINISHED') {
	                       //
	           }else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {    
           			$checkordertype = checkordertype($out_trade_no);  
            		$parameter['id'] = $checkordertype['userid'];  
            		$parameter['payment_type'] = $checkordertype['payment_type'];                   
		           	if(!checkorderstatus($out_trade_no)){
		               orderhandle($parameter); 
		                           //进行订单处理，并传送从支付宝返回的参数；
		            }
		            //更新支付宝信息
		            if($checkordertype['payment_type']==99){
		            	$email = $buyer_email;
            			$id = $checkordertype['userid']; 
		            	update_zfb($id,$email); 
		            }
	            }
	                echo "success";        //请不要修改或删除
	         }else {
	                //验证失败
	                echo "fail";
	        }    
	    }
	    
	    /*
	        页面跳转处理方法；
	        */
	    function returnurl(){
	        $alipay_config=C('alipay_config');
	        $alipayNotify = new \AlipayNotify($alipay_config);//计算得出通知验证结果
	        $verify_result = $alipayNotify->verifyReturn();
	        if($verify_result) {
	            //验证成功
	            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
	        $out_trade_no   = $_GET['out_trade_no'];      //商户订单号
	        $trade_no       = $_GET['trade_no'];          //支付宝交易号
	        $trade_status   = $_GET['trade_status'];      //交易状态
	        $total_fee      = $_GET['total_fee'];         //交易金额
	        $notify_id      = $_GET['notify_id'];         //通知校验ID。
	        $notify_time    = $_GET['notify_time'];       //通知的发送时间。
	        $buyer_email    = $_GET['buyer_email'];       //买家支付宝帐号；
	            
	        $parameter = array(
	            "out_trade_no"     => $out_trade_no,      //商户订单编号；
	            "trade_no"     => $trade_no,          //支付宝交易号；
	            "total_fee"      => $total_fee,         //交易金额；
	            "trade_status"     => $trade_status,      //交易状态
	            "notify_id"      => $notify_id,         //通知校验ID。
	            "notify_time"    => $notify_time,       //通知的发送时间。
	            "buyer_email"    => $buyer_email,       //买家支付宝帐号
	        );
	        
	if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
            $checkordertype = checkordertype($out_trade_no);
            $parameter['id'] = $checkordertype['userid'];
            $parameter['payment_type'] = $checkordertype['payment_type']; 
	        if(!checkorderstatus($out_trade_no)){
	             orderhandle($parameter);  //进行订单处理，并传送从支付宝返回的参数；
	    	}
			//更新支付宝信息
            if($checkordertype['payment_type']==99){
            	$email = $buyer_email;
            	$id = $checkordertype['userid']; 
            	update_zfb($id,$email); 
            }
	        $this ->redirect('Usercenter/index',Null,1, '支付成功，页面跳转中...');
	    }else {
	        $this ->redirect('Usercenter/index',Null,1, '支付失败，页面跳转中...');
	    }
	}else {
	    //验证失败
	    //如要调试，请看alipay_notify.php页面的verifyReturn函数
	    echo "支付失败！";
	    }
	}
}