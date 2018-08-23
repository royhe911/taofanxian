<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 11:42
 */

namespace Erp\Controller;
use Think\Controller;

class BaseController extends Controller{

	public function _initialize(){

		$this->check();//判断用户是否已经登录
    	$this->menu();//当前菜单
    	$this->column();//左边栏
    	$this->shop();//检测商家
        $this->check_error_order();
//        $this->check_password();
    }

    //判断用户是否已经登录
    private function check(){

    	if (!empty($_SESSION['user']['id'])||!empty($_COOKIE['userId'])){
    		if(!empty($_SESSION['user']['id'])){
    			$id = $_SESSION['user']['id'];
    			$password_mi=$_SESSION['user']['password'];

    		} else {
    			$id = $_COOKIE['userId'];
    			$password_mi=$_COOKIE['password'];

    		}
            $password=M('account')->where('id='.intval($id))->getField('password');
    		//dump($_SESSION);exit;
            if(empty($password) || empty($id) || ($password_mi != md5($password))){
                $this->loguot();//销毁用户登陆数据
                $this->error('登入过期，请重新登录', U('Login/index'));
                exit;
            }
    		$msg = D('account')->alias('a')->field('a.id,a.role,a.name,b.wangwang,a.realname,md5(a.password) as password')->join('left join erp_user b on a.id=b.uid')->where(array('a.id' => $id, 'a.msg' => 0))->find();
    		if (!empty($msg)){
                $now = (date('a') == 'am') ? '上午' : '下午';
                $msg['now']=$now;
    			session("user", $msg);
    			//dump($_COOKIE);exit;
    			/*******************************************************/
                //商家登入，商家登入，下架过期任务
                if( $_SESSION['user']['role'] == 5 ){
                    $time = time();
                    $user_id=intval($_SESSION['user']['id']);
                      $where = "isnull(a.tb_item) and (a.xiajia = 1 or a.xiajia = 0) and a.endtime < ".$time." and a.user_id=".$user_id." and (b.status=0 or b.status=3)";
                      $task = M('task a')->field('a.*,b.status')->join('left join erp_product b on a.gid=b.id')->where($where)->select();
//                  $where = "isnull(tb_item) and xiajia <>2 and endtime < ".$time."  and gid in (select id from erp_product where  user_id=".$user_id." and (status=0 or status =3))";
//                  $task = M('task a')->where($where)->select();


                    if(!empty($task))
                    {

                        foreach ($task as $key =>$value){
                            M()->startTrans();
                            $yufujin= $value['price'] + $value['cost'] + $value['empty_cost'];
                            $task_status=D('task')->where("id =".$value['id'])->setField('xiajia',3);

                            $balances_status=save_available($user_id,$yufujin,$value['id'],5,2);
                            if (!$balances_status) {
                                M()->rollback();
                                die('系统错误');
                            }

                            $yufujin_status=D('user')->where('uid='.$user_id)->setDec('yufujin',$yufujin);
                            $money_status=D('user')->where('uid='.$user_id)->setInc('money',$yufujin);
                            if($task_status===false || $yufujin_status===false || $money_status===false){
                                M()->rollback();
                                die('系统错误');
                            } else {
                                M()->commit();
                            }
                        }
                    }
                    $where="endtime <".time()." and status=0 and user_id=".intval($_SESSION['user']['id']);
                    $data=M('product')->field('id')->where($where)->select();

                    if(!empty($data))
                    {
                        $id = '';
                        foreach ($data as $key =>$value){
                            $id .= $value['id'].',';
                        }
                        $id = rtrim($id, ',');

                        //开启事务
                        M()->startTrans();
                        $product_status=D('product')->where("id in ({$id})")->setField('status',4);
                        if(!$product_status){
                            M()->rollback();
                            die('系统错误');
                        }else{
                            M()->commit();
                        }
                    }

                }
    			/******************************************************/

    		} else {//异常
    			$this->loguot();//销毁用户登陆数据
    			$this->error('登录信息异常或帐号已禁用,请重新登录', U('Login/index'));
    		}
    	} else {
    		$this->error('请先登录', U('Login/index'));
    	}
    }

    //左边栏
    private function column(){

    	$status = D('role')->where(array('id' => $_SESSION['user']['role'], 'msg' => 0))->getField('status');
    	if (empty($status)){
    		$this->loguot();//销毁用户登陆数据
    		$this->error('该帐号未分配权限或已禁用,请联系管理员!', U('Login/index'));
    	} else {
    		$this->checkRole($status);//权限测检
    		$column = D('column')->order('sort DESC')->where('id in ('.$status.') and msg = 0')->field(array('id', 'a', 'c', 'fid', 'name'))->select();
    		if (!empty($column)){
    			foreach ($column as $key => $val){
    				$tree[$val['fid']][] = $val;
    			}unset($column);
    			$_SESSION['user']['column'] = $tree;
    		} else {
    			$this->loguot();//销毁用户登陆数据
    			$this->error('该角色权限异常,请联系管理员!', U('Login/index'));
    		}
    	}
    }

    //当前控制器
    private function menu(){

    	$cname = CONTROLLER_NAME;//当前控制器名
    	$aname = ACTION_NAME;//当前方法名
    	$column = D('column')->where(array('c' => strtolower($cname), 'a' => strtolower($aname)))->field('id,fid')->find();
    	if (!empty($column)) $_SESSION['user']['menu'] = $column;
    }

    //权限测检
    private function checkRole($status){

    	$cname  = CONTROLLER_NAME;//当前控制器名
    	$roleId = D('column')->where(array('c' => strtolower($cname)))->getField('id');
    	if (!in_array($roleId, explode(',', $status))){
    		$this->error('无此权限');
    	}
    }

    //销毁用户登陆数据
    private function loguot(){
    	unset($_SESSION['user']);
    	cookie('userId',null);
    	cookie('password',null);
    }

    //检测商家
    private function shop(){

    	if (5 == $_SESSION['user']['role']){//只检测商家
    		$info = D('user')->where(array('uid' => $_SESSION['user']['id']))->find();
    		if (empty($info)){//不存在则增加
    			$tutor = $this->teacher();//分配导师
    			$msg = D('user')->add(array('uid' => $_SESSION['user']['id'], 'nickname' => $_SESSION['user']['name'], 'tid' => $tutor['id'], 'tutor' => $tutor['qq'], 'addtime' => time()));//只增加基础信息
    			if (false === $msg){
    				$this->loguot();//销毁用户登陆数据
    				$this->error('系统异常,请联系管理员!', U('Login/index'));
    			}
    		} else {//存在则检测该导师是否存在，无则分配，有则检测导师信息是否完善
    			if (empty($info['tutor'])){//无导师则分配
    				$tutor = $this->teacher();//分配导师
    			} else {//有导师则检测导师是否完善
    				$msg = D('account')->field('id,qq,phone,img,info,addtime')->where(array('qq' => $info['tutor'] ,'role' => 6, 'msg' => 0))->find();//导师
    				if (empty($info['tid'])){//检测导师是否完善,存在则䃼全信息
    					if (!empty($msg)){//该导师存在则䃼全信息
    						$tutor = $msg;
    					} else {//不存在则分配一个
    						$tutor = $this->teacher();//分配导师
    					}
    				} else {//导师信息完善
    					$_SESSION['user']['info'] = $msg['info'];//添加导师info到session
    					$_SESSION['user']['tutor'] = $msg['qq'];//添加导师QQ到session
    					$_SESSION['user']['phone'] = $msg['phone'];//添加导师phone到session
    					$_SESSION['user']['img']   = date('Ymd',$msg['addtime']).'/'.$msg['img'];//添加导师微信图片到session
    				}
    			}

    			if (!empty($tutor)){
    				$_SESSION['user']['info'] = $msg['info'];//添加导师info到session
    				$_SESSION['user']['tutor'] = $tutor['qq'];//添加导师QQ到session
    				$_SESSION['user']['phone'] = $tutor['phone'];//添加导师phone到session
    				$_SESSION['user']['img']   = date('Ymd',$tutor['addtime']).'/'.$tutor['img'];//添加导师微信图片到session
    				$msg = D('user')->where(array('uid' => $_SESSION['user']['id']))->save(array('tid' => $tutor['id'], 'tutor' => $tutor['qq']));//䃼全导师信息
    			}
    		}
    	}
    }

    //分配导师
    private function teacher($tutor){

    	$count  = D('account')->where(array('role' => 6, 'msg' => 0))->count();
    	if (!empty($count)){
    		$offset = mt_rand(1,$count) - 1;//获取随机数
    		$msg    = D('account')->field('id,qq,phone,img,info,addtime')->limit($offset,1)->where(array('role' => 6, 'msg' => 0))->select();//获取导师
    		return $msg[0];
    	}
    }



    /**
     * @param array $title 表头信息 一维数组
     * @param array $data 插入数据信息 二维数组，并且此二维数组每个数组的长度和表头信息数组长度一致
     * @param $fileName  下载下来的文件名
     */
    public function down($title=array(), $data=array(), $fileName='Excel'){
        if(count($data[0]) != count($title) || empty($title) || empty($data)) $this->error('当前查询范围无数据');//要求两个数组长度一致
        ob_end_clean();
        import("Org.Util.PHPExcel");
        import("Org.Util.PHPExcel.Worksheet.Drawing");
        // 如果excel文件后缀名为.xls
        //import("Org.Util.PHPExcel.Writer.Excel5");
        //
        // 如果excel文件后缀名为.xlsx
        import("Org.Util.PHPExcel.Writer.Excel2007");
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objActSheet = $objPHPExcel->getActiveSheet();
        set_time_limit(0);
        ini_set('memory_limit','512M');
        //设置表头
        $key = ord("A");
        foreach ($title as $value){
            $colum = chr($key);
            $objActSheet->setCellValue($colum.'1',$value);
            $objPHPExcel->getActiveSheet()->getColumnDimension($colum)->setWidth(20);  //设置宽度
            $key += 1;
        }

        //数据写入
        $column = 2;
        foreach($data as $key => $rows){ //行写入
            $span = ord("A");
            foreach($rows as $k=> $value){// 列写入
                $j = chr($span);
                if($k == 'tb_item'){
                    $objActSheet->setCellValue($j.$column,' '.$value); //避免默认使用科学计数法
                }else{
                    $objActSheet->setCellValue($j.$column,$value);
                }

                $span++;
            }
            $column++;
        }

        $date = date("Y-m-d",time());
        $fileName .= "_{$date}.xlsx";

        $fileName = iconv("utf-8", "gb2312", $fileName);
        //只有一个活动单元
        $objPHPExcel->setActiveSheetIndex(0);
        ob_start();
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');
        // 如果excel文件后缀名为.xls
        //$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //
        // 如果excel文件后缀名为.xlsx
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output'); //文件通过浏览器下载
        exit;
    }

    //检查是否有异常订单
    public function check_error_order(){

        $uid=intval($_SESSION['user']['id']);   //用户id
        $role=intval($_SESSION['user']['role']);  //权限id
        $where="a.abn =2";  //退款单
        $where_e="a.abn=1";
        //刷单员


        if ( $role == 3){
            //站长
            $where .=" and a.abn_status=0 and b.user_id=".$uid;
            $tuikuan=D('task a')
                ->field('a.id')
                ->join('left join erp_account b on a.uid=b.id')
                ->where($where)
                ->select();
            $where_e .=" and a.abn_status=0 and b.user_id=".$uid;
            $yichang=D('task a')
                ->field('a.id')
                ->join('left join erp_account b on a.uid=b.id')
                ->where($where_e)
                ->select();

        }

        if($tuikuan){
            //如果有异常信息
            session('error',1);
        }else{
            session('error',null);
        }

        if($yichang){
            //如果有异常信息
            session('error_abn',1);
        }else{
            session('error_abn',null);
        }

    }
    //检查当前账户密码和session登入时账户密码是否一致
//    private function check_password(){
//        if(!empty($_SESSION['user'])){
//            $password=D('account')->where('id='.intval($_SESSION['user']['id']))->getField('password');
//            if(md5($password) != $_SESSION['user']['mi_password']){
//                $this->loguot();//销毁用户登陆数据
//                $this->error('用户信息异常或帐号已禁用,请重新登录', U('Login/index'));
//                exit;
//
//            }
//        }else{
//            $password=D('account')->where('id='.intval($_COOKIE['userId']))->getField('password');
//            if(md5($password) != $_COOKIE['password']){
//                $this->loguot();//销毁用户登陆数据
//                $this->error('用户信息异常或帐号已禁用,请重新登录', U('Login/index'));
//                exit;
//
//            }
//        }
//
//
//    }
}