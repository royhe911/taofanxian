<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/1/6
 * Time: 10:00
 */
  namespace Supporter\Model;
  use Think\Model;

  Class TbgoodsActivityModel extends Model{  

     protected $_validate = array(
         array('commodity_name','require','请填写平台展示名称！'),
         array('goods_name','require','请填写淘宝商品标题！'),
         array('shop_url','require','请填写商品链接！'),
         //array('shop_url','url','请填写正确的链接'),
         array('mypic','require','请填写平台展示图！'),
         array('unit_price','require','请填写试客下单价格！'),
         array('iphone_unit_price','require','请填写淘宝搜索价格！'),
         array('buy_sum','require','请填写试客每单拍！'),
     );
        
     protected $_auto = array(
         array('uid','getUid',1,'callback'),//用户ID
         array('sid','getSid',1,'callback'),//店铺ID
         array('cid','getCid',3,'callback'),//商品分类ID
         array('type','getType',1,'callback'),//试用类型
         array('image','getPic',3,'callback'),//平台展示图
         array('gname','getName',3,'callback'),//平台展示名称
         array('url','getUrl',3,'callback'),//商品链接
         array('title','getTitle',3,'callback'),//淘宝商品标题
         array('remark','getRemark',3,'callback'),//备注
         array('huabei','getHuaBei',3,'callback'),//是否可以使用花呗或信用卡付款
         array('format','getFormat',3,'callback'),//商品规格
         array('unit_price','getUnitPrice',3,'callback'),//试客下单价格
         array('scosts','getScosts',3,'callback'),//运费
         array('addtime','time',1,'function'),//添加时间
         array('state','2'),//状态(新增的时候添加)
     	 array('trash','0')//状态(新增的时候添加)
     );
     
     protected function getSid(){
     	return I('post.sid',0,'intval');
     }
     
     protected function getCid(){
     	return I('post.commodity_classify',0,'intval');
     }
     
     protected function getType(){
     	return I('post.type',0,'intval');
     }
     
     protected function getPic(){
     	return I('post.mypic');
     }
     
     protected function getUid(){
     	return session('ext_user.id');
     }

     protected function getRemark(){
     	return I('post.notice_remark','','strip_tags');
     }
     
     protected function getTitle(){
     	return I('post.goods_name','','strip_tags');
     }
     
     protected function getName(){
     	return I('post.commodity_name','','strip_tags');
     }
     
     protected function getHuaBei(){
     	return I('post.huabei',0,'intval');
     }
     
     protected function getScosts(){
     	return I('post.is_baoyou',0,'intval') ? I('post.freight',0,'intval') : 0;
     }
     
     protected function getUrl(){
     	return I('post.shop_url','','strip_tags');
     }
     
     protected function getFormat(){
     	$iphone_unit_price = I('post.iphone_unit_price',0,'intval');
     	$buy_sum = I('post.buy_sum',0,'intval');
     	$thecolor = I('post.thecolor','','strip_tags');
     	
     	$format = array();
     	$format['buy_sum'] = !empty($buy_sum) ? intval($buy_sum) : 1;//试客每单拍(件)
     	if (!empty($thecolor)) $format['thecolor'] = $thecolor;//商品规格
     	if (!empty($iphone_unit_price)) $format['iphone_unit_price'] = number_format($iphone_unit_price,2);//淘宝搜索价格(元)
     	return json_encode($format);
     }
     
     protected function getUnitPrice(){
     	return I('post.unit_price',0,'strip_tags');
     }
  }
 ?>