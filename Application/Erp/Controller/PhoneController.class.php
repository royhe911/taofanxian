<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 11:42
 */

namespace Erp\Controller;
use Think\Controller;

class PhoneController extends Controller{

    public function get_message(){
        if(IS_AJAX){
            $ip     = trim(I('get.ip'));
            $imei   = trim(I('get.imei'));
            if(empty($ip) && empty($imei)){
                $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
            }
            $where  = "ip='{$ip}' and imei='{$imei}'";
            $info   = D('phoneinfo')->where($where)->find();
            if($info) $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
            $data=array(
                'ip'        => $ip,
                'imei'      => $imei,
                'addtime'   => date('Y-m-d H:i:s',time()),
            );
            $res=D('phoneinfo')->add($data);
            if(!$res) $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
            $this->ajaxReturn(array('msg' => 1, 'info' => 'success'));
        }
    }

    public function index(){
        $file_path = "phone.txt";
        if(file_exists($file_path)){
            $fp = fopen($file_path,"r");
            $str = fread($fp,filesize($file_path));//指定读取大小，这里把整个文件内容读取出来
            $res=explode("\r\n",$str);
            foreach ($res as $key=>$value){
                $res=D('phone')->where(array('phone'=>$value))->find();
                if(!$res){
                    M('phone')->add(array('phone'=>$value));
                }else{
                    continue;
                }

            }
        }
    }

}