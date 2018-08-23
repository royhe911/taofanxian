<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;
/**
 * @auth TRUE
 * @name 专题管理
 * @authsort 20
 * */
class AlimamadataController extends InterceptController {

	public function index()
	{
	    $order_status = array('订单创建','订单付款','订单成功','订单结算','订单失效');
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$data = M('alimamadata')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('alimamadata')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->assign('order_status',$order_status);
		$this->display();
	}
	
	public function order_load()
	{
		import('Org.Util.PHPExcel');
		
	 	if (! empty ( $_FILES ['file_stu'] ['name'] ))
		{
		    $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
		    $file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
		    $file_type = $file_types [count ( $file_types ) - 1];
		     /*判别是不是.xls文件，判别是不是excel文件*/
		    if (strtolower ( $file_type ) != "xls")              
		    {
		    	$this->error ( '不是Excel文件，重新上传' );
		    }
		    /*设置上传路径*/
		    $img_url = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
			$img_url = str_replace('\\','/',$img_url);
		    $savePath = $img_url . '/maoke/Public/upfile/Excel/';
		    /*以时间来命名上传的文件*/
		    $str = date ( '1' ); 
		    $file_name = $str . "." . $file_type;
		     /*是否上传成功*/
		    if (! copy ( $tmp_file, $savePath . $file_name )) 
		    {
		     	$this->error ( '上传失败' );
		    }
		    $filename = $savePath.$file_name;
		    import("Org.Util.PHPExcel");   // 这里不能漏掉
        	import("Org.Util.PHPExcel.IOFactory");
			$type = pathinfo($filename);
    		$type = strtolower($type["extension"]);
    		$type=$type==='csv' ? $type : 'Excel5';
		    ini_set('max_execution_time', '0');
		    Vendor('PHPExcel.PHPExcel');
		    // 判断使用哪种格式
		    $objReader = \PHPExcel_IOFactory::createReader($type);
		    $objPHPExcel = $objReader->load($filename);
		    
			$objWorksheet = $objPHPExcel->getActiveSheet();
			$highestRow = $objWorksheet->getHighestRow();//总行数
			$highestColumn = $objWorksheet->getHighestColumn();
			$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn); //总列数
			$excelData = array(); 
			for ($row = 1; $row <= $highestRow; $row++)
			{
				for ($col = 0; $col < $highestColumnIndex; $col++)
				{
					$data[$row][] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
				}
			} 
		    unset($data[1]);
		   
			$tmpArr = $res = $result = array();
			foreach($data as $value){
			    if(!in_array($value[24],$tmpArr)){
			        $tmpArr[] = $value[24];
			    }else{
			        $res[] = $value[24];
			    }
			}
			//分离订单中订单号相同的数据
			$i = $j = 0;
			foreach($data as $key => $value){
			    if(in_array($value[24],$res)){
			    	unset($data[$key]);
			    	if(in_array($value[24],$result[$i][$j-1]))
			    	{
			       	 	$result[$i][$j++] = $value;
			    	}else
			    	{
			    		$j=0;
			    		$result[++$i][$j++] = $value;
			    	}
			    }
			}
			//var_dump($result);exit;
		    $i=0;
		    //重复数据处理
		    foreach($result as $val)
		    {
		    	$order = M('alimamadata')->where('order_number='.$val[0][24])->find();
		    	if($order)//订单存在则全部更新否则全部新增
		    	{
			    	foreach($val as $k=>$v)
			    	{
				    	$i += $this->order_action($v, 'upd',$k);
			    	}
		    	}else
		    	{	foreach($val as $v)
			    	{
			    		$i += $this->order_action($v, 'add');
			    	}
		    	}
		    }
		    //单条数据处理
		    foreach($data as $val)
		    {
		    	$order = M('alimamadata')->where('order_number='.$val[24])->find();
		    	if($order)
		    	{
		    		$i += $this->order_action($val, 'upd');
		    	}else
		    	{
		    		$i += $this->order_action($val, 'add');
		    	}
		    }
			
		    $total = $highestRow-1;
	        if($i)
	        {
	        	echo "<script>alert('共{$total}条记录，失败{$i}条数据')</script>";
	        }else
	        {
	        	echo "<script>alert('共{$total}条记录，导入成功')</script>";
	        }
		   /*
		        重要代码 解决Thinkphp M、D方法不能调用的问题  
		        如果在thinkphp中遇到M 、D方法失效时就加入下面一句代码
		    */
		   //spl_autoload_register ( array ('Think', 'autoload' ) );
		}
		$this->display();
	}
	function order_action($val,$action,$k=false)
	{
		if(empty($val[24]))
    	{
    		return 1;
    	}
	    if($val[8]=='订单付款')
	    {
	    	$order_status = 1;
	    }elseif($val[8]=='订单成功')
	    {
	    	$order_status = 2;
	    }elseif($val[8]=='订单结算')
	    {
	    	$order_status = 3;
	    }elseif($val[8]=='订单失效')
	    {
	    	$order_status = 4;
	    }else{
	    	$order_status = 0;
	    }
    	$res = array(
    			'order_number' => $val[24],
    			'goods_title' => $val[2],
    			'goods_id' => $val[3],
    			'shop_name' => $val[5],
    			'order_status' => $order_status,
    			'pay_amount' => $val[12],
    			'expect_income' => $val[13],
    			'checkout_amount' => $val[14],
    			'commission_rate' => floatval($val[17]),
    			'commission_amount' => $val[19],
    			'addtime' => $val[0],
    			'clicktime' => $val[1],
    			'checkouttime' => $val[16],
    			'type' => 0,
    			'uptime' => date('Y-m-d H:i:s')
    			);
    	if($action=='upd')
    	{
    		if($k!==false)
    		{
    			$orderdata = M('alimamadata')->where('order_number='.$val[24])->select();
    			if( false === M('alimamadata')->where('id='.$orderdata[$k]['id'])->save($res))
	    		{
	    			return 1;
	    		}
    		}else
    		{
	    		if( false === M('alimamadata')->where('order_number='.$val[24])->save($res))
	    		{
	    			return 1;
	    		}
    		}
    		
    	}else
    	{
	    	$result = M('alimamadata')->add($res);
	        if(!$result) 
	        {
	        	return 1;
	        }
    	}
    	return 0;
	}
	
}