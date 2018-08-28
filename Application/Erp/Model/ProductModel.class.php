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

  Class ProductModel extends Model
  {

      //自定义字段
      protected $fields = array('id', 'goods_title', 'goods_url','goods_attr', 'goods_pic', 'goods_thumb', 'addtime', 'status','goods_keyword','goods_num','goods_sku','goods_price','goods_totalnum','goods_totalprice','goods_totalcost','checktime','endtime','goods_zeng','reason','sku','empty_cost','pub_time','shop_id','order','childacc','childacc_pass','label','p','n','s','k','empty_cost','category');


    //钩子，实现图片上传,自动创建缩略图，添加时间戳
      public function _before_insert(&$data)
      {

          $goods_title=trim($data['goods_title']);  //产品标题
          $goods_url=trim($data['goods_url']);      //产品链接
          $goods_pic=trim($data['goods_pic']);         //产品图片
          $shop_id=intval($data['shop_id']);        //商品id
          if( empty($goods_title)) {$this->error='产品标题不能为空';return false;}
          if( empty($goods_url)) {$this->error='产品链接不能为空';return false;}
          if( empty($shop_id)) {$this->error='店铺名不能为空';return false;}
         foreach ( $data['k'] as $key =>$value){
              if (empty($value)){
                  $this->error='关键字不能为空';return false;
              }
              if($data['p'][$key] <=0 || $data['p'][$key] >500){
                  $this->error='产品价格不得高于500元';return false;
              }
              if($data['n'][$key] <=0){
                  $this->error='产品数量有误';return false;
              }
         }

            /**************************************************/
//          $data['empty_cost']=4;//空包费用
          /******************************************************/
          date_default_timezone_set("PRC");
          //到期时间  当天23点59分59秒
          //当天中午12点的时间
          $shierdian=mktime(12, 00, 00, date("n"), date("d"), date("Y"));


          if(time() < $shierdian){
              //在当天十二点前发布的产品，结束时间为当天23:59:59
              $data['addtime']=time();  //当前时间  开始时间为当前
              $data['endtime']= mktime(23, 59, 59, date("n"), date("d"), date("Y"));  //结束时间为当天23:59:59

          }else{
              //在当天十二点之后发布的产品，结束时间为第二天23:59:59
              //86399 一天的时间戳
              $data['addtime'] =mktime(00, 00, 00, date("n"), date("d")+1, date("Y"));  //结束时间  第二天凌晨
              $data['endtime'] = $data['addtime']  + 86399 ;
          }

//
          //产品发布时间，不是开始时间

          $data['status']=1; //默认为待付款
          $data['pub_time']=time();

          $name=$_SESSION['user']['name'];
          if(!$name){
              $this->error='商家不存在';
          }
          $userId=intval($_SESSION['user']['id']);

          $data['user_id']=$userId;



      }

      public function _after_insert($data)
      {
          $id=$data['id'];
          foreach ( $data['s'] as $key => $value){
              $data['s'][$key] = implode(',',$value);
          }

          $goods_keywords=array();
          foreach ($data['k'] as $k =>$v){
                $goods_keywords[$k]['k']         =   $v;                   //关键字
                $goods_keywords[$k]['goods_id']  =   $id;                     //产品id product表
                $goods_keywords[$k]['p']         =   $data['p'][$k];       //产品单价
                $goods_keywords[$k]['cost']      =   cost($data['p'][$k]) * $data['n'][$k]; //产品费用
                $goods_keywords[$k]['n']         =   $data['n'][$k];       //产品数量
                $goods_keywords[$k]['order']     =   $data['order'][$k];   //产品其他备注
                $goods_keywords[$k]['s']         =   $data['s'][$k];          //搜索关键字
          }

          $data['goods_keywords']=$goods_keywords;
          //总费用计算
          $sun=$count=$totalcost=0;//sun元
          foreach ( $goods_keywords as $key =>$value){
              $totalcost += $value['cost'];                    //总费用
              $sun       += ($value['p'] * $value['n'] );      //总价格
              $count     += $value['n'];                       //总份数
          }

          $field=array(
              'goods_totalnum'   => $count,
              'goods_totalprice' => $sun,
              'goods_totalcost'  => $totalcost,
          );
          $user_id = $_SESSION['user']['id'];
          $product=D('product')->where('id='.$id)->setField($field);
          if (!$product)   {
              $this->error='系统错误';
              return false;
          }
          //加入到任务中
          $info=D('User')->field('tid')->where('uid='.$user_id)->find();
          foreach ( $goods_keywords as $key => $value){
              $msg=D('ProductAttr')->add($value);
              if (!$msg)   {
                  $this->error='系统错误';
                  return false;
              }
              $num=$value['n'];
              $goods_id=$value['goods_id'];
              $arr=array(
                  'sid'=>$msg,
                  'gid'=>$goods_id,
                  'price'=>$value['p'],
                  'num'=>1,
                  'sku'=>$value['s'].','.$value['order'],
                  'keyword'=>$value['k'],
                  'addtime'=>$data['addtime'],
                  'endtime'=>$data['endtime'],
                  'user_id'=>$user_id,
                  'tid'    =>$info['tid'],
                  'shop_id'=>$data['shop_id'],
                  'cost'   =>$value['cost'] / $value['n'],//xxx
                  'empty_cost'=>$data['empty_cost'] ? $data['empty_cost'] : 0,
              );

              for($i=0;$i<$num;$i++) {
                  $task=D('Task')->add($arr);
                  if (!$task)   {
                      $this->error='系统错误';
                      return false;
                  }

              }
          }
          addLog(LogModel::TYPE_PRODUCT_ADD,'商家新增商品任务，商品id:'.$id);

          //扣款
          $user_id=$_SESSION['user']['id'];

          //$sun=产品价格  $totalcost=总费用
          if($data['empty_cost']){
              $totalmoney=$sun + $totalcost +$data['empty_cost'] * $count;

          }else{
              $totalmoney=$sun + $totalcost;   //  总价格  费用和总价格
          }
          $where="user_id=".$user_id." and status =3 or status=0";
          $res=D('product')->where($where)->order('pub_time')->select();

          //有发布
          $starttime=date('Ymd',$res[0]['pub_time']);
          //当前时间
          $now=date('Ymd',time());

          $user_info=D('user')->where(array('uid'=> $user_id))->find();
          $credit_money=$user_info['credit_money'];   //透支额度
          $credit_status=$user_info['credit_status'];   //透支申请状态
          //开启事务
          M()->startTrans();
          if( $starttime == $now || empty($res)){
              //判断今天的任务总金额
              // $xuyao=$user_info['yufujin']+$totalmoney-$user_info['money'];
              $credit_money= ( $credit_status==2) ? $credit_money : 1000;
              $xuyao=$totalmoney-$credit_money-$user_info['money'];
              if( $xuyao > 0 ){   //1000的信誉额度
                  $cha  = abs($xuyao);//-$credit_money;
                  $info = '余额不足,还需要'.$cha.'元';
                  $this->error=$info;
                  return false;
              }else{
                  //end
                  //收支明细记录
                  $balances_status=save_available($user_id,$totalmoney,$id,3,1);
                  if (!$balances_status) {
                      M()->rollback();
                      $this->error='系统错误';
                      return false;
                  }
                  $res=D('User')->where('uid='.$user_id)->setInc('yufujin',$totalmoney);
                  $resm=D('User')->where('uid='.$user_id)->setDec('money',$totalmoney);

                  $status=D('product')->where('id='.$id)->setField('status',0);
                  if ($res===false || $resm===false || $status===false) {
                      M()->rollback();
                      $this->error='系统错误';
                      return false;
                  }
                  addLog(LogModel::TYPE_PRODUCT_PAY,'商家对商品任务进行付款，付款金额：'.$totalmoney.'，商品id:'.$id);
                  M()->commit();
                  $this->success='发布成功';
              }
          }else{
              $result=$this->chajia($user_id,$totalmoney);
              if($result > 0){
                  //余额不足以发布产品
                  //
                  $info='余额不足,还需要'.$result.'元';
                  $this->error=$info;
                  return false;
              }else{
                  //end
                  //收支明细记录
                  $balances_status=save_available($user_id,$totalmoney,$id,3,1);
                  if (!$balances_status) {
                      M()->rollback();
                      $this->error='系统错误';
                      return false;
                  }
                  $res=D('User')->where('uid='.$user_id)->setInc('yufujin',$totalmoney);
                  $resm=D('User')->where('uid='.$user_id)->setDec('money',$totalmoney);

                  $status=D('product')->where('id='.$id)->setField('status',0);
                  addLog(LogModel::TYPE_PRODUCT_PAY,'商家对商品任务进行付款，付款金额：'.$totalmoney.'，商品id:'.$id);
                  if ($res===false || $resm===false || $status===false) {
                      M()->rollback();
                      $this->error='系统错误';
                      return false;
                  }
                  M()->commit();
                  $this->success='发布成功';
              }
          }
      }

 //产品删除 删除基本属性
      public function del($id)
      {
          //查询到对应的相册地址
          $goods_info=$this->where("id=$id")->find();
          //删除相册
//          unlink($goods_info['goods_pic']);
//          unlink($goods_info['goods_thumb']);

          //删除基本属性
          $res=$this->where("id=$id")->delete();
          if(!$res){
              $this->error='删除基本属性失败';
          }
          //删除附加属性
          $attrRes=M('ProductAttr')->where("goods_id=$id")->delete();
          if(!$attrRes){
              $this->error='删除附加属性失败';
              return false;
          }
          //删除任务列表
          $task=D('Task')->where('gid='.$id)->delete();
          if(!$task){
              $this->error='删除任务失败';
              return false;
          }
          return true;
      }
      //修改产品



      public function update($data)
      {
          if(empty($data['k'])){
              //属性
              $this->error='修改失败，属性不能为空';
              return false;
          }


          $goods_title=trim($data['goods_title']);  //产品标题
          $goods_url=trim($data['goods_url']);      //产品链接

          $shop_id=intval($data['shop_id']);        //商品id
          if( empty($goods_title)) {$this->error='产品标题不能为空';return false;}
          if( empty($goods_url)) {$this->error='产品链接不能为空';return false;}
          if( empty($shop_id)) {$this->error='店铺名不能为空';return false;}
          foreach ( $data['k'] as $key =>$value){
              if (empty($value)){
                  $this->error='关键字不能为空';return false;
              }
              if($data['p'][$key] <=0 || $data['p'][$key] >500){
                  $this->error='产品价格不得高于500元';return false;
              }
              if($data['n'][$key] <=0){
                  $this->error='产品数量有误';return false;
              }
          }


          $id=$data['id'];
          //添加时间
          date_default_timezone_set("PRC");
          //到期时间  当天23点59分59秒
          //当天中午12点的时间
          $shierdian=mktime(12, 00, 00, date("n"), date("d"), date("Y"));


          if(time() < $shierdian){
              //在当天十二点前发布的产品，结束时间为当天23:59:59
              $data['addtime']=time();  //当前时间  开始时间为当前
              $data['endtime']= mktime(23, 59, 59, date("n"), date("d"), date("Y"));  //结束时间为当天23:59:59

          }else{
              //在当天十二点之后发布的产品，结束时间为第二天23:59:59
              //86399 一天的时间戳
              $data['addtime'] =mktime(00, 00, 00, date("n"), date("d")+1, date("Y"));  //结束时间  第二天凌晨
              $data['endtime'] = $data['addtime']  + 86399 ;
          }
          //先获取原有信息
          $goods_info=$this->where('id='.$id)->find();

//          $yufujin=$goods_info['goods_totalprice'] + $goods_info['goods_totalcost'];
//
//          $res=D('User')->where('uid='.$_SESSION['user']['id'])->setDec('yufujin',$yufujin);


          //判断原有的图片是否和现在的一样
          if(!$data['goods_pic']){
              //不一样，删除原有的，加入新有的
              //有新照片上传，先删除原有相片
                unset($data['goods_pic']);
//              unlink($goods_info['goods_pic']);
//              unlink($goods_info['goods_thumb']);

          }

          if(!isset($data['empty_cost'])){
              $data['empty_cost']= 0;
          }

          if(!isset($data['goods_zeng'])){
              $data['goods_zeng']= null;
          }


          $data['pub_time']=time();
          $data['status']=1;
          $data['reason']=null;  //清除拒绝理由
          //添加基本信息
          $res=$this->where('id='.$id)->save($data);
          if($res === false){
              $this->error='修改失败';
              return false;
          }

          //更新属性attr
            //删除原有任务列表  （任务发布后无法修改）
          D('Task')->where('gid='.$id)->delete();
          //先删除原有属性
          D('ProductAttr')->where('goods_id='.$id)->delete();



          foreach ( $data['s'] as $key => $value){
              $data['s'][$key] = implode(',',$value);
          }

          $goods_keywords=array();
          foreach ($data['k'] as $k =>$v){
              $goods_keywords[$k]['k']         =   $v;                   //关键字
              $goods_keywords[$k]['goods_id']  =   $id;                     //产品id product表
              $goods_keywords[$k]['p']         =   $data['p'][$k];       //产品单价
              $goods_keywords[$k]['cost']      =   cost($data['p'][$k]) * $data['n'][$k]; //产品费用
              $goods_keywords[$k]['n']         =   $data['n'][$k];       //产品数量
              $goods_keywords[$k]['order']     =   $data['order'][$k];   //产品其他备注
              $goods_keywords[$k]['s']         =   $data['s'][$k];          //搜索关键字

          }


          $data['goods_keywords']=$goods_keywords;
          //总费用计算
          $sun=$count=$totalcost=0;//sun元
          foreach ( $goods_keywords as $key =>$value){
              $totalcost += $value['cost'];                    //总费用
              $sun       += ($value['p'] * $value['n'] );      //总价格
              $count     += $value['n'];                       //总份数

          }

          $field=array(
              'goods_totalnum'   => $count,
              'goods_totalprice' => $sun,
              'goods_totalcost'  => $totalcost,
          );

          $product=D('product')->where('id='.$id)->setField($field);
//          if (!$product)   $this->error='系统错误';
          //加入到任务中
          $info=D('User')->field('tid')->where('uid='.$_SESSION['user']['id'])->find();
          foreach ( $goods_keywords as $key => $value){
              $msg=D('ProductAttr')->add($value);
              if (!$msg)  {
                  $this->error='系统错误1';
                  return false;
              }
              $num=$value['n'];
              $goods_id=$value['goods_id'];
              $arr=array(
                  'sid'=>$msg,
                  'gid'=>$goods_id,
                  'price'=>$value['p'],
                  'num'=>1,
                  'sku'=>$value['s'].','.$value['order'],
                  'keyword'=>$value['k'],
                  'addtime'=>$data['addtime'],
                  'endtime'=>$data['endtime'],
                  'user_id'=>$_SESSION['user']['id'],
                  'tid'    =>$info['tid'],
                  'shop_id'=>$data['shop_id'],
                  'cost'   =>$value['cost'] / $value['n'],//xxx
                  'empty_cost'=>$data['empty_cost'],
              );

              for($i=0;$i<$num;$i++) {
                  $task=D('Task')->add($arr);
                  if (!$task)   {
                      $this->error='系统错误2';
                      return false;
                  }
              }
          }
          addLog(LogModel::TYPE_PRODUCT_UPDATE,'商家修改商品任务，商品id:'.$id);

          //扣款
          $user_id=$_SESSION['user']['id'];

          //$sun=产品价格  $totalcost=总费用
          if($data['empty_cost']){
              $totalmoney=$sun + $totalcost +$data['empty_cost'] * $count;

          }else{
              $totalmoney=$sun + $totalcost;   //  总价格  费用和总价格
          }


          $where="user_id=".$user_id." and status =3 or status=0";
          $res=D('product')->where($where)->order('pub_time')->select();

          //有发布
          $starttime=date('Ymd',$res[0]['pub_time']);
          //当前时间
          $now=date('Ymd',time());
          $user_info=D('user')->where(array('uid'=> $user_id))->find();
          $credit_money=$user_info['credit_money'];   //透支额度
          $credit_status=$user_info['credit_status'];   //透支申请状态
          //开启事务
          M()->startTrans();
          if( $starttime == $now || empty($res)){
              //判断今天的任务总金额

              $xuyao=$user_info['yufujin']+$totalmoney-$user_info['money'];
              //判断可透支额度 透支状态不为2 取1000  为2取自身
              $credit_money= ( $credit_status==2) ? $credit_money : 1000;

              if( $xuyao > $credit_money ){   //1000的信誉额度
                  $cha  = $xuyao-$credit_money;
                  $info = '余额不足,还需要'.f_round($cha).'元';
                  $this->error=$info;
                  return false;

              }else{
                  //end
                  //收支明细记录
                  $balances_status=save_available($_SESSION['user']['id'],$totalmoney,$id,3,1);
                  if (!$balances_status) {
                      M()->rollback();
                      $this->error='系统错误4';
                      return false;
                  }
                  $res=D('User')->where('uid='.$user_id)->setInc('yufujin',$totalmoney);
                  $res=D('User')->where('uid='.$user_id)->setDec('money',$totalmoney);
                  $status=D('product')->where('id='.$id)->setField('status',0);
                  addLog(LogModel::TYPE_PRODUCT_PAY,'商家对商品任务进行付款，付款金额：'.$totalmoney.'，商品id:'.$id);
                  if ( !$res || !$status) {
                      M()->rollback();
                      $this->error='系统错误3';
                      return false;
                  }
                  M()->commit();
                  $this->success='发布成功';

              }
          }else{
              $result=$this->chajia($user_id,$totalmoney);
              if($result > 0){
                  //余额不足以发布产品
                  //
                  //                            $this->where('id='.$id)->setField('status',1);
                  $info='余额不足,还需要'.f_round($result).'元';
                  $this->error=$info;
                  return false;


              }else{
                  //end
                  //收支明细记录
                  $balances_status=save_available($_SESSION['user']['id'],$totalmoney,$id,3,1);
                  if (!$balances_status) {
                      M()->rollback();
                      $this->error='系统错误6';
                      return false;
                  }
                  $res=D('User')->where('uid='.$user_id)->setInc('yufujin',$totalmoney);
                  $res=D('User')->where('uid='.$user_id)->setDec('money',$totalmoney);
                  $status=D('product')->where('id='.$id)->setField('status',0);
                  addLog(LogModel::TYPE_PRODUCT_PAY,'商家对商品任务进行付款，付款金额：'.$totalmoney.'，商品id:'.$id);
                  if ( !$res || !$status) {
                      M()->rollback();
                      $this->error='系统错误5';
                      return false;
                  }
                  M()->commit();
                  $this->success='发布成功';
              }
          }
          return true;

      }

      //计算余额
      /**
       * @param $user_id  商家id
       * @param $sun  发布商品总价格
       * @param $cost  发布商品总费用
       * @return mixed 返回差价
       */
      public function chajia( $user_id, $totalmoney){
          //查询用户余额
          $user_info    = D('User')->where('uid='.$user_id)->find();
          $money        = $user_info['money'];   //用户余额
          $yufujin      = $user_info['yufujin'];   //冻结金
          $credit_money = $user_info['credit_money'];  //可透支余额
          $credit_status= $user_info['credit_status'];   //透支申请状态
          if($credit_status ==2){
              // $res      = $yufujin + $totalmoney - $money - $credit_money;
              $res      = $totalmoney - $money - $credit_money;
          }else{
              // $res      = $yufujin + $totalmoney - $money;
              $res      = $totalmoney - $money;
          }
          return $res;
      }
  }