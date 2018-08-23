<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Image;
  use Think\Model;
  use Think\Upload;

  Class ProductAttrModel extends Model
  {

      //自定义字段
      //protected $fields = array('id', 'goods_id', 'k','p', 'n', 's');
      //定义自动验证
//      protected $_validate = array(
//          array('goods_title', 'require', '产品名称不能为空', 1),
//          array('goods_url', 'require', '产品链接必须填写', 1),
//
//      );
        //添加关键字
        public function addAttr($data)
        {
           // dump($data);exit;
            //$res=$this->addAll($data);
            //dump($data);exit;
            //时间戳

            date_default_timezone_set("PRC");
            //到期时间  当天23点59分59秒
            //当天中午12点的时间
            $shierdian=mktime(12, 00, 00, date("n"), date("d"), date("Y"));


            if(time() < $shierdian){
                //在当天十二点前发布的产品，结束时间为当天23:59:59
                $addtime = time();  //当前时间  开始时间为当前
                $endtime = mktime(23, 59, 59, date("n"), date("d"), date("Y"));  //结束时间为当天23:59:59
            }else{
                //在当天十二点之后发布的产品，结束时间为第二天23:59:59
                //86399 一天的时间戳
                $addtime = mktime(00, 00, 00, date("n"), date("d")+1, date("Y"));  //结束时间  第二天凌晨
                $endtime = $addtime  + 86399 ;
            }


            $info=D('User')->field('tid')->where('uid='.$_SESSION['user']['id'])->find();
            foreach ($data as $key => $value){
//                dump($data);exit;
               $msg=$this->add($value);

                $num=$value['n'];
                $goods_id=$value['goods_id'];
                $arr=array(
                    'sid'=>$msg,
                    'gid'=>$goods_id,
                    'price'=>$value['p'],
                    'num'=>1,
                    'sku'=>$value['s'],
                    'keyword'=>$value['k'],
                    'addtime'=>$addtime,
                    'endtime'=>$endtime,
                    'user_id'=>$_SESSION['user']['id'],
                    'tid'    =>$info['tid'],
                    );
                for($i=0;$i<$num;$i++) {
                    D('Task')->add($arr);
                }
            }
            return true;

        }



  }