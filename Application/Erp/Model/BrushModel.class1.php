<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model;

  Class BrushModel extends Model{

      //自定义字段
      protected $fields = array('id', 'role', 'name','phone', 'password', 'wechat', 'qq', 'info');


//      protected $_validate = array(
//          array('role','require','请选择角色！'),
//          array('iphone','require','请填写手机号！'),
//          array('wechat','checkWeChat','请填写微信号！',1,'callback'),
//          array('name','require','请填写真实姓名！'),
////          array('iphone','11','手机号为11位的数字！',0,'length'),
//          array('iphone','/^[0-9]*$/','手机号只能为数字！'),
//          array('password','require','请填写密码！'),
//          array('password','6,50','密码不能少于6位数！',0,'length'),
//          array('password','/^[0-9a-zA-Z]*$/','密码只能为字母或数字！'),
//          array('password2','password','两次输入密码不一致！',0,'confirm'),
//          array('name','unique','该手机号已经存在！',0,'unique'),
////     		array('qq','unique','该QQ已经存在！',0,'unique')
//      );



      protected function checkWeChat($data){
          $wechat = implode(';', $data);

          if (!empty($wechat)){
              return true;
          } else {
              return false;
          }
      }

      protected function getWechat($data){
          return $wechat = implode(';', $data);
      }
  }
 ?>