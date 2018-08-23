<?php
return array(
	//'配置项'=>'配置值'
	'DEFAULT_MODULE'=>'Home',
	'URL_MODEL'=>0,
    //'TMPL_EXCEPTION_FILE' => '',
	'SHOW_PAGE_TRACE'=>false,
	'LOG_TYPE'              =>  'File', // 日志记录类型 默认为文件方式
	'LOAD_EXT_CONFIG' => 'db_config',
         // 添加下面一行定义即可
    'TOKEN_ON'      =>    true,  // 是否开启令牌验证 默认关闭
    'TOKEN_NAME'    =>    '__hash__',    // 令牌验证的表单隐藏字段名称，默认为__hash__
    'TOKEN_TYPE'    =>    'md5',  //令牌哈希验证规则 默认为MD5
    'TOKEN_RESET'   =>    false,  //令牌验证出错后是否重置令牌 默认为true
	'alipay_config'=>array(
	        'partner' =>'2088621978950645',   //这里是你在成功申请支付宝接口后获取到的PID；
		    'key'=>'qqek7vb33k5wqljp1wayawig2ge1wzhp',//这里是你在成功申请支付宝接口后获取到的Key
		    'sign_type'=>strtoupper('MD5'),
		    'input_charset'=> strtolower('utf-8'),
		    'cacert'=> getcwd().'\\cacert.pem',
		    'transport'=> 'http',
	      ),
	     //以上配置项，是从接口包中alipay.config.php 文件中复制过来，进行配置；
	    
	'alipay'   =>array(
	 //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
	'seller_email'=>'money@ling8.com',
	//这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
	'notify_url'=>'http://www.taofanxian.com/admin.php?m=Home&c=Recharge&a=notifyurl', 
	//这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
	'return_url'=>'http://www.taofanxian.com/admin.php?m=Home&c=Recharge&a=returnurl',
	//支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
	'successpage'=>'User/myorder?ordtype=payed',   
	//支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
	'errorpage'=>'User/myorder?ordtype=unpay', 
	),


);