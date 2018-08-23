<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/12
 * Time: 11:40
 */

namespace Supporter\Controller;


use Think\Controller;

class BusinesswriteController extends BaseController
{
    public  function index(){

        $this->display();
    }

	public  function shiyong(){

        $this->display();
    }


    public function goods(){

	    $this->display();
    }

    //未通过
    public function refuse(){
        $data= M('tbgoods');
        $row1=$data->where(' role_id='.$_SESSION['ext_user']['id'].' and confirm=1 ')->order('addtime desc')->select();
        $this->row2=$row1;
        $this->display();
    }

    //进行中
    public function  orderIng(){
        $res= M('tbusertrade');
        //试用资格待审核
        $n1= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and comfirm=0')->count();
        //待下单
        $n2= $res->where(' role_id='.$_SESSION['ext_user']['id'].'  and shutconfirm=0 and comfirm=1 and ISNULL(subtime)')->count();
        //驳回
        $n3= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and comfirm=2')->count();
        //订单号待审核
        $n4= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=0 and comfirm=1 and subtime>0')->count();
        //截图待审
        $n5= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and comfirm=1 and confirm_tixian=0 and  four_time>0 and confirm2=0')->count();
        //已完成
        $n6= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and comfirm=1 and  confirm_tixian=0 and  four_time is null')->count();
        //已终止
        $n7= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and shutconfirm=1')->count();

        $this->n1=$n1;
        $this->n2=$n2;
        $this->n3=$n3;
        $this->n4=$n4;
        $this->n5=$n5;
        $this->n6=$n6;
        $this->n7=$n7;

        $this->display();
    }

    public function daijiesuan(){
        $res= M('tbusertrade');
        if ($_GET['order']) {
            $where = " and tb_item like '" . $_GET['order'] . "%'";
            $list4 = $res->where(' role_id=' . $_SESSION['ext_user']['id'] . ' and subconfirm=2 and confirm_tixian=0 and  four_time>0 and confirm2=2'.$where)->select();
        }else{
            $list4 = $res->where(' role_id=' . $_SESSION['ext_user']['id'] . ' and subconfirm=2 and confirm_tixian=0 and  four_time>0 and confirm2=2')->select();

        }
        $this->daijie=$list4;
        $this->display();
    }

    public function yijiesuan(){
        $res= M('tbusertrade');
        if ($_GET['order']) {
            $where = " and tb_item like '" . $_GET['order'] . "%'";
            $yijie = $res->where(' role_id=' . $_SESSION['ext_user']['id'] . ' and confirm_tixian=1'.$where)->select();
        }else{
            $yijie = $res->where(' role_id=' . $_SESSION['ext_user']['id'] . ' and confirm_tixian=1')->select();
        }
        $this->yijie=$yijie;
        $this->display();
    }

    //上传商品待审核
    public function daishenhe(){
        $data= M('tbgoods');
        $row1=$data->where(' role_id='.$_SESSION['ext_user']['id'].' and confirm=0 ')->order('addtime desc')->select();
        $this->row1=$row1;
        $this->display();
    }


    //已审核商品
    public function row2(){
        $data= M('tbgoods');

        $row2=$data->where(' role_id='.$_SESSION['ext_user']['id'].' and confirm=2 ')->order('addtime desc')->select();
        $this->row2=$row2;
        $this->display();
    }

    public  function Ajaxrow2(){
        $data= M('tbgoods');
        if (IS_POST){
            $data->applyconfirm=1;
            $data->applydate=date("Y-m-d H:i:s");
            $data->apnote=$_POST['apnote'];
          $w=  $data->where(' role_id='.$_SESSION['ext_user']['id'].' and id='.$_POST['id'])->save();
          if ($w){
              $this->ajaxReturn(array('code'=>1,'msg'=>'申请信息提交成功'));
          }else{
              $this->ajaxReturn(array('code'=>0,'msg'=>'申请提交失败！'));
          }
        }
    }



    //试用资格待审核
    public function list1(){
        $res= M('tbusertrade');
        $list1=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and comfirm=0')->order('addtime desc')->select();
        $this->list1=$list1;
        $this->display();
    }

    //待发布
    public function list2(){
        $res= M('tbusertrade');
        $list2=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and shutconfirm=0 and comfirm=1 and ISNULL(subtime)')->order('addtime desc')->select();
        $this->list2=$list2;
        $this->display();
    }

    public function AjaxShutdown(){
       $data= M('tbusertrade');
       $goods=M('tbgoods');
        if (IS_POST){
            $arr['shutconfirm']=1;
            $arr['shutcondate']=date('Y-m-d H:i:s');
            $arr['shutconpeople']=$_SESSION['ext_user']['adminname'];
            $t=  $data->where('id='.$_POST['id'])->save($arr);
            $gid=$data->where('id='.$_POST['id'])->getField('gid');
            if ($t){
                $goods->where('id='.$gid)->setInc('kucun',1);
                $goods->where('id='.$gid)->setDec('sell_number',1);
                $this->ajaxReturn(array('code'=>1,'msg'=>'关闭订单成功'));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'关闭订单失败'));
            }
        }
    }

    //订单号待审核
    public function list3(){
        $res= M('tbusertrade');
        if ($_GET['order']){
            $where=" and tb_item like '".$_GET['order']."%'";
            $list3=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=0 and comfirm=1  and subtime>0'.$where)->order('addtime desc')->select();
        }else{
            $list3=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=0  and subtime>0')->order('addtime desc')->select();
        }
        $this->list3=$list3;
        $this->display();

    }

    public function AjaxOrders(){
        $data= M('tbusertrade');
        if (IS_POST){
            $arr['subconfirm']=2;
            $arr['subcondate']=date('Y-m-d H:i:s');
            $arr['subconpp']=$_SESSION['ext_user']['adminname'];
            $t=  $data->where('id='.$_POST['id'])->save($arr);
            if ($t){
                $this->ajaxReturn(array('code'=>1,'msg'=>'订单确认成功'));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'订单确认失败'));
            }
        }
    }

    public function AjaxReOrders(){
        $data= M('tbusertrade');
        if (IS_POST){
            $arr['note']=$_POST['note'];
            $arr['comfirm']=2;
            $arr['comfirm_date']=date('Y-m-d H:i:s');
            $arr['comfirm_people']=$_SESSION['ext_user']['adminname'];
            $t=  $data->where('id='.$_POST['id'])->save($arr);
            if ($t){
                $this->ajaxReturn(array('code'=>1,'msg'=>'订单驳回成功'));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'订单驳回失败'));
            }
        }
    }


    //截图待审
    public function list4(){
        $res= M('tbusertrade');
        if ($_GET['order']){
            $where=" and tb_item like '".$_GET['order']."%'";
            $list4=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and confirm_tixian=0 and  four_time>0'.$where)->order('addtime desc')->select();
        }else{
            $list4=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and confirm_tixian=0 and  four_time>0')->order('addtime desc')->select();

        }
        $this->list4=$list4;
        $this->display();
    }

    public function AjaxUpimg(){
        $data= M('tbusertrade');
        $arr=array();
        if (IS_POST){
            $arr['confirm2_people']=$_SESSION['ext_user']['adminname'];
            $arr['confirm2']=2;
            $arr['img']=$_POST['img'];
            $arr['confirm2_date']=date("Y-m-d H:i:s",time());;
          $zz= $data->where('id='.$_POST['id'])->save($arr);
          if ($zz){
              $this->ajaxReturn(array('code'=>1,'msg'=>'确认成功！'));
          }else{
              $this->ajaxReturn(array('code'=>0,'msg'=>'确认失败！'));
          }
        }
    }

    //已完成
    public function list5(){
        $res= M('tbusertrade');
        if ($_GET['order']) {
            $where = " and tb_item like '" . $_GET['order'] . "%'";
            $list5 = $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and confirm_tixian=0 and  four_time is null'.$where)->order('addtime desc')->select();
        }else{
            $list5 = $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and confirm_tixian=0 and  four_time is null')->order('addtime desc')->select();

        }
        $this->list5=$list5;
        $this->display();

    }

    //驳回
    public function list6(){
        $res= M('tbusertrade');
        $list6=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and comfirm=2')->order('addtime desc')->select();
        $this->list6=$list6;
        $this->display();

    }

    //已终止
    public function list7(){
        $res= M('tbusertrade');
        $list7=$res->where(' role_id='.$_SESSION['ext_user']['id'].' and shutconfirm=1')->order('addtime desc')->select();
        $this->list7=$list7;
        $this->display();
    }

    //试用产品发布
    public  function pro(){

    	$userdata = M('tbsellerinfo')->where('sid='.$_SESSION['ext_user']['id'])->find();
    	if($userdata)
    	{
    		if($userdata['confirm']==2)
    		{
    			if (IS_POST){
		            $post_token = I('post.TOKEN');
		            if(!checkToken($post_token)){
		                $this->error('请不要重复提交页面',U('User/goods'));
		            }
		          $img= $this->upload();
		          array_map('trim',$_POST);
		         $zz= array_keys($_POST,"");//检测空数据
		         if (count($zz)>0){
		             $this->error("请提交完整信息",U('User/pro'));
		         }
		         $data= M('tbgoods');
		        $data->create();
		        $data->img2=$img[0];
		        $data->img=$img[1];
		        $data->addtime=date("Y-m-d H:i:s");
		        $data->role_id=$_SESSION['ext_user']['id'];
		         if ($data->add()){
		             $this->success("提交成功！",U('User/goods'));
		         }
		        }
		        $category = array(
								'10001' => '女装',
								'10002' => '男装',
								'10003' => '鞋包',
								'10004' => '母婴',
								'10005' => '内衣',
								'10006' => '美食',
								'10007' => '数码',
								'10008' => '家居',
								'10009' => '美妆',
								'10010' => '户外',
								'10011' => '配饰',
								'10012' => '家装',
								'10013' => '家电',
								'10014' => '车用',
								'10015' => '图书',
								'10016' => '其他'
								);
		        createToken();
		        $this->assign('category',$category);
		        $this->display();
    		}else{
    			echo "<script>alert('商铺绑定审核通过后才能上传商品');</script>";
    			$this ->redirect('User/bindShop',Null,1, '自动前往店铺绑定，页面跳转中...');
    		}
    	}else{
    		echo "<script>alert('商铺绑定审核通过后才能上传商品');</script>";
    		$this ->redirect('User/bindShop',Null,1, '自动前往店铺绑定，页面跳转中...');
    	}

    }

    public function upPro(){
        if ($_GET['id']>0){
            $data=M('tbgoods');
          $res=  $data->where('id='.$_GET['id'])->find();
          $this->res=$res;
        }
        if (IS_POST){
            $post_token = I('post.TOKEN');
            if(!checkToken($post_token)){
                $this->error('请不要重复提交页面',U('User/goods'));
            }

            array_map('trim',$_POST);
            $zz= array_keys($_POST,"");//检测空数据
            if (count($zz)>0){
                $this->error("请提交完整信息",U('User/upPro'));
            }
            $data= M('tbgoods');
            $arr=array();
            $arr= $data->create();
            if ($_FILES['img2']['name']&&$_FILES['img']['name']){
                $img= $this->upload();
                $arr['img2']=$img[0];
                $arr['img']=$img[1];
            }else if ($_FILES['img2']['name']&&$_FILES['img']['name']==''){
                $img= $this->upload();
                $arr['img2']=$img[0];
            }else if ($_FILES['img2']['name']==''&&$_FILES['img']['name']){
                $img= $this->upload();
                $arr['img']=$img[0];
            }
            $arr['addtime']=date("Y-m-d H:i:s");
            $arr['role_id']=$_SESSION['ext_user']['id'];
           $zz= $data->where("id=".$_POST['iid'])->save($arr);
            if ($zz){
                $this->success("修改成功！",U('User/goods'));
            }else{
                $this->error("修改失败！",U('User/goods'));
            }
        }
         $category = array(
						'10001' => '女装',
						'10002' => '男装',
						'10003' => '鞋包',
						'10004' => '母婴',
						'10005' => '内衣',
						'10006' => '美食',
						'10007' => '数码',
						'10008' => '家居',
						'10009' => '美妆',
						'10010' => '户外',
						'10011' => '配饰',
						'10012' => '家装',
						'10013' => '家电',
						'10014' => '车用',
						'10015' => '图书',
						'10016' => '其他'
						);
        createToken();
        $this->assign('category',$category);
        $this->display();
    }


    public  function bindUser(){
     $data= M("duoduo2010");
       $tel= $data->where('id='.$_SESSION['ext_user']['id'])->getField('tel');
        $this->tel=$tel;

        $this->display();
    }

    public function AjaxBinduser(){
        $data= M("duoduo2010");
        $vercode=$_POST['code'];
        $phone=$_POST['tel'];
        if(time()-$_SESSION['last_access']>120){
            $this->ajaxReturn(array('code'=>0,'msg'=>'验证码已经失效，请重新获取'));
        }
        if ($_SESSION['code']!=$vercode){
            $this->ajaxReturn(array('code'=>0,'msg'=>'验证码错误！'));
        }
        $z=$data->where('tel='.$phone)->select();
        if ($z>0){
            $this->ajaxReturn(array('code'=>0,'msg'=>'号码已被使用'));
        }
        $data->tel=$phone;
          $zz=  $data->where('id='.$_SESSION['ext_user']['id'])->save();
          if ($zz){
               $this->ajaxReturn(array('code'=>1,'msg'=>'绑定成功'));
          }else{
              $this->ajaxReturn(array('code'=>0,'msg'=>'绑定失败'));
          }

    }

    public function  AjaxCode(){
        ini_set('memory_limit','128M');
        $data=M("duoduo2010");
            $z=$data->where('tel='.$_POST['tel'])->select();
            if ($z){
                $this->ajaxReturn(array('code'=>0,'msg'=>'号码已被使用'));
            }else{
                if(!isset($_SESSION['last_access'])||(time()-$_SESSION['last_access'])>120){
                    $code=rand(1000,9999);
                    sendTemplateSMS($_POST['tel'],array($code,'2'),"221424");//手机号码，替换内容数组，模板ID
                    $_SESSION['code']=$code;
                    $_SESSION['last_access'] = time();
                    $this->ajaxReturn(array('code'=>1,'msg'=>'验证码已发送！'));
                }else{
                    $this->ajaxReturn(array('code'=>0,'msg'=>'请勿重复发送'));
                }
            }

    }

    public  function bindShop(){
      $data=M('tbsellerinfo');
     $rr= $data->where(' sid='.$_SESSION['ext_user']['id'])->find();
     $this->rr=$rr;
        if (IS_POST){
            $data->create();
            $data->sid=$_SESSION['ext_user']['id'];
            $data->addtime=date("Y-m-d H:i:s",time());
           $zz= $data->add();
           if ($zz){
               $this->success("保存成功！",U('User/bindShop'));
           }else{
           }
        }
        $this->display();
    }
	public  function editshop(){

		$id = I('get.id','');
        $data=M('tbsellerinfo');
     	$rr= $data->where(' id='.$id)->find();

        if (IS_POST && $rr){
        	$shopname = I('post.shopname','');
			$zhanggui = I('post.zhanggui','');
			$shopurl = I('post.shopurl','');
            $addtime = date("Y-m-d H:i:s",time());
            $arr = array(
            		'shopname' => $shopname,
            		'zhanggui' => $zhanggui,
            		'shopurl' => $shopurl,
            		'addtime' => $addtime,
            		'confirm' => '',
            		'confirmpeople' => '',
            		'confirmnote' => '',
            		'confirmdate' => ''
            		);
           	$zz= $data->where("id=".$id)->save($arr);
           	if ($zz){
               	$this->success("保存成功！",U('User/bindShop'));
           	}else{
           	}
        }
        $this->assign('rr',$rr);
        $this->display();
    }



    public  function headUp(){
       $data= M('duoduo2010');
       if ($_FILES){
           $img= $this->upload();
           $data->headimg=$img[0];
          $zz= $data->where("id=".$_SESSION['ext_user']['id'])->save();
          if ($zz){
              $_SESSION['ext_user']['headimg']=$img[0];
              $this->success("提交成功！",U('User/headUp'));
          }else{
              error("更新失败");
          }
       }
        $this->display();
    }


    public  function passwordUp(){

        $this->display();
    }

    public function  AjaxPass(){
       $data= M('duoduo2010');
       if (IS_POST){
         $oldpass= $_POST['oldpass'];
         $pass=$_POST['pass'];
       $res=  $data->where("id=".$_SESSION['ext_user']['id']." and adminpass='".deep_jm($oldpass)."'")->select();
       if ($res){
           $data->adminpass=deep_jm($pass);
       $zz=$data->where('id='.$_SESSION['ext_user']['id'])->save();
       if ($zz){
           $this->ajaxReturn(array('code'=>1,'msg'=>'密码修改成功'));
       }else{
           $this->ajaxReturn(array('code'=>0,'msg'=>'密码修改失败'));
       }
       }else{
           $this->ajaxReturn(array('code'=>0,'msg'=>'原密码错误'));
       }
       }

    }


    public  function sellerHelp(){

        $this->display();
    }



    public  function sellerNews(){

        $this->display();
    }



    public function upload(){
        $arr=array();
        $config = array(
            'maxSize'    =>    3145728,
            'rootPath'   =>    './upload/',
            'savePath'   =>    '',
            'saveName'   =>    array('uniqid',''),
            'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
            'autoSub'    =>    true,
            'subName'    =>    array('date','Ymd'),
        );
        $upload = new \Think\Upload($config);
        // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            echo "{\"error\":\"".$upload->getError()."\"}";
        }else{// 上传成功 获取上传文件信息
            foreach($info as $file){
                $arr[]= '/upload/'.$file['savepath'].$file['savename'];
            }
            return $arr;
        }
    }


    public function AjaxUpload(){
        $arr=array();
        $config = array(
            'maxSize'    =>    3145728,
            'rootPath'   =>    './upload/',
            'savePath'   =>    '',
            'saveName'   =>    array('uniqid',''),
            'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
            'autoSub'    =>    true,
            'subName'    =>    array('date','Ymd'),
        );
        $upload = new \Think\Upload($config);
        // 上传文件
        $info   =  $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            echo "{\"error\":\"".$upload->getError()."\"}";
        }else{// 上传成功 获取上传文件信息
            foreach($info as $file){
                $arr[]= '/upload/'.$file['savepath'].$file['savename'];
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>'上传成功！',"url"=>$arr[0]));
        }
    }

    public function main(){
        $res= M('tbusertrade');
        //试用资格待审核
        $n1= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and comfirm=0')->count();
        //待下单
        $n2= $res->where(' role_id='.$_SESSION['ext_user']['id'].'  and shutconfirm=0 and comfirm=1 and ISNULL(subtime)')->count();
        //驳回
        $n3= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and comfirm=2')->count();
        //订单号待审核
        $n4= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=0 and comfirm=1 and subtime>0')->count();
        //截图待审
        $n5= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and confirm_tixian=0 and  four_time>0 and confirm2=0')->count();
        //已完成
        $n6= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and subconfirm=2 and confirm_tixian=0 and  four_time is null')->count();
        //已终止
        $n7= $res->where(' role_id='.$_SESSION['ext_user']['id'].' and shutconfirm=1')->count();

        $this->n1=$n1;
        $this->n2=$n2;
        $this->n3=$n3;
        $this->n4=$n4;
        $this->n5=$n5;
        $this->n6=$n6;
        $this->n7=$n7;
        $data= M('tbgoods');
        //待审核
        $m1=$data->where(' role_id='.$_SESSION['ext_user']['id'].' and confirm=0 ')->count();
        //已上架
        $m2=$data->where(' role_id='.$_SESSION['ext_user']['id'].' and confirm=2 ')->count();
        //未通过
        $m3=$data->where(' role_id='.$_SESSION['ext_user']['id'].' and confirm=1 ')->count();
        $this->m1=$m1;
        $this->m2=$m2;
        $this->m3=$m3;
        $info = array(
            '系统版本'=>'V1.0',
            '作者'=>"阿君",
            '主机名'=>$_SERVER['SERVER_NAME'],
            '浏览器信息'=>substr($_SERVER['HTTP_USER_AGENT'], 0, 40),
            '通信协议'=>$_SERVER['SERVER_PROTOCOL'],
            '上传附件限制'=>ini_get('upload_max_filesize'),
            '执行时间限制'=>ini_get('max_execution_time').'秒',
            '服务器时间'=>date("Y年n月j日 H:i:s"),
            '北京时间'=>gmdate("Y年n月j日 H:i:s",time()+8*3600),
            '用户的IP地址'=>$_SERVER['REMOTE_ADDR'],
        );
        $this->info=$info;
        $this->display();
    }



    public function unlogin(){
        unset($_SESSION['ext_user']);
        $this->success('退出成功',U('Index/login'));
    }


    public function AjaxAbc(){
        if (IS_POST){
            $goods=M('tbgoods');
            $data= M('tbusertrade');
            $uid=  $data->where('id='.$_POST['id'])->getField('uid');
            $gid=  $data->where('id='.$_POST['id'])->getField('gid');
            $res=M('tbinfo');
            $tel=$res->where('uid='.$uid)->getField('phone');
            $rrr=M('apilogin');
            $openid=$rrr->where('uid='.$uid)->getField('webid');
            $username=getusername($uid);
            $arr['comfirm']=$_POST['statu'];
            $arr['comfirm_date']=date('Y-m-d H:i:s');
            $arr['comfirm_people']=$_SESSION['ext_user']['adminname'];
            $t=  $data->where('id='.$_POST['id'])->save($arr);
            if ($t){
                if ( $arr['comfirm']==1){//通过
                    $goods->where('id='.$gid)->setDec('kucun',1);
                    $goods->where('id='.$gid)->setInc('sell_number',1);
                    wxSendMsg($openid,$gid);
                    if ($tel){
                        sendTemplateSMS($tel,array($username,$gid),"206716");
                    }
                }
                $this->ajaxReturn(array('code'=>1,'msg'=>'操作成功！'));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'操作失败！'));
            }

        }

    }
        public function demo (){
//            $goods=M('tbgoods');
//            $gid=11;
//            $goods->where('id='.$gid)->setDec('kucun',1);
//            $goods->where('id='.$gid)->setInc('sell_number',1);
        }

}