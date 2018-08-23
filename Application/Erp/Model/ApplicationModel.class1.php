<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model;

  Class ApplicationModel extends Model{

      //自定义字段
      protected $fields = array('id', 'user_id', 'money','addtime', 'status', 'money_pic', 'reason', 'endtime');





  }
 ?>