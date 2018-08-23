<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;
use Common\Util\Util;

class TaskController extends BaseController {

    public function index(){
        //取任务
        if(IS_AJAX){
            //判断用户是当前时间是否可以领单
            $if_go = $this->if_gain(3);
            if(!$if_go)
            {
                $this->ajaxReturn(array('status'=>0,'msg'=>'今日本额度任务配额已领完，请联系主管！'));die();
            }
            
            //偏移量 越大 小数量做的越慢
            $offset = 4;
            
            $where = 'price>=100 and price<300';
            
            $last_user = M('Task')->where('addtime < '.time())->order('starttime desc')->getField('shop_id');
            $query = 'SELECT count(shop_id) as count,shop_id FROM erp_task where uid is not null and status =1 and xiajia =0 and '.$where.' and addtime <'.time().' GROUP BY shop_id';
            $ful_data = M()->Query($query);
            $query = 'SELECT count(shop_id) as count,shop_id FROM erp_task where status =1 and xiajia =0';
            $query .= ' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().') and '.$where.' and addtime <'.time().' GROUP BY shop_id';
            $all_data = M()->Query($query);
            foreach ($ful_data as $val)
            {
                $user_data[$val['shop_id']] = $val['count'];
            }
            
            foreach ($all_data as $key=>$val)
            {
                $ful_cunt = isset($user_data[$val['shop_id']])?$user_data[$val['shop_id']]:0;
                if($val['count']==$ful_cunt) continue;
                if($val['count']<90)
                {
                    $val['count'] = round(10*$val['count']/$offset);
                }
                $user_next[$val['shop_id']] = round($ful_cunt/$val['count'],2);
            }
            if(empty($user_next))
            {
                $this->ajaxReturn(array('status'=>0,'msg'=>'订单已领完'));die();
            }
            asort($user_next);
            $shop_id = key($user_next);//店铺ID
            //最后取到店铺的商品
            $lastgid = M('Task')->where('status =1 and xiajia =0 and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is not null')->order('starttime desc')->getField('gid');
            //店铺商品总数
            $query = 'SELECT gid FROM erp_task where status =1 and xiajia =0 and uid is null and shop_id='.$shop_id.' and '.$where;
            $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and addtime < '.time().' GROUP BY gid';
            $gid_data = M()->Query($query);
            $gid_count = count($gid_data);
            $thisgid = '';//应该取的商品ID
            if($gid_count>1)
            {

                $query = 'SELECT count(id) as a,count(IF(uid,1,NULL)) as b,gid FROM erp_task WHERE status =1 and xiajia =0 and shop_id='.$shop_id;
                $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and '.$where.' and addtime < '.time().' GROUP BY gid';
                $task_data = M()->Query($query);
                foreach($task_data as $val)
                {
                    
                    $ful_cunt = isset($val['b'])?$val['b']:0;//已完成量
                    if($val['a']==$ful_cunt) continue;
                    if($val['a']<30)
                    {
                        $val['a'] = round(10*$val['a']/$offset);
                    }
                    $task_datas[$val['gid']] = round(($val['a']-$ful_cunt)/$val['a'],2);

                }
                arsort($task_datas);
                $thisgid = key($task_datas);
            }
            $gidwhere = '';
            if(!empty($thisgid))
            {
                $gidwhere = ' and gid=\''.$thisgid.'\'';
            }
            //最后取到的关键字
            $lastkeyword = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is not null')->order('starttime desc')->getField('keyword');
            //商品关键字总数
            $query = 'SELECT keyword FROM erp_task where status =1 and xiajia =0 and uid is null '.$gidwhere;
            $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' GROUP BY keyword';
            $keyword_data = M()->Query($query);
            $keyword_count = count($keyword_data);
            if(empty($lastkeyword) || $keyword_count==1)
            {

                $id = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is null')->getField('id');
            }else {
                foreach($keyword_data as $val)
                {
                    $keyword_all[] = $val['keyword'];
                }
                $lastkeyword_data[] = $lastkeyword;
                $result = array_diff($keyword_all,$lastkeyword_data);
                $thiskey = array_rand($result);
                $thiskeyword = $result[$thiskey];
                $id = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is null and keyword=\''.$thiskeyword.'\'')->getField('id');

            }

            if($id)
            {
                $array=array(
                        'uid'=>$_SESSION['user']['id'],
                        'starttime'=>time(),
                        );
            }else
            {
                $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));die();
            }
            $res=D('Task')->where('id='.$id)->setField($array);
            if($res)
            {
                $this->ajaxReturn(array('status'=>1,'msg'=>'领取任务成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));
            }
        }else{
            //判断当前权限
            if( check_action() === false) {
                $this->error('无此权限');
                return false;
            }

            $user_id=$_SESSION['user']['id'];
            $where="a.uid=".$user_id." and isnull(a.tb_item) and a.addtime < ".time();
            $info=D('Task')
                ->alias('a')
                ->field('a.id,a.remark,a.price,a.keyword,a.sku,a.empty_cost,a.is_short,a.short_url,b.goods_title,b.goods_url,b.goods_pic,b.goods_thumb,b.goods_zeng,d.shopname,e.realname,e.info as yw_info')
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop d on a.shop_id=d.id')
                ->join('left join erp_account e on a.tid=e.id')
                ->where($where)
                ->order('starttime DESC')
                ->select();
            foreach ($info as $key => $value){
                $info[$key]['price']=floatval($value['price']);
                if (strpos($value['goods_pic'], 'http://') === false) {
                    $info[$key]['goods_pic'] = 'http://www.taofanxian.com/www/'.$value['goods_pic'];
                }
            }

            $nex_num = M('task')->where("is_spe=1 and addtime>=".strtotime(date('Y-m-d'))." and addtime < ".time()." and uid =".$_SESSION['user']['id'])->count();
            $num = M('account')->where('id='.$_SESSION['user']['id'])->getField('num');
            $num_now = $num-$nex_num;

            $l_num = M('task')->where("is_spe=2 and addtime>=".strtotime(date('Y-m-d'))." and addtime < ".time()." and uid =".$_SESSION['user']['id'])->count();
            $lnum = M('account')->where('id='.$_SESSION['user']['id'])->getField('l_num');
            $lnum_now = $lnum-$l_num;
            $lnum_now = $lnum_now<0?0:$lnum_now;

            $m_num = M('task')->where("price<300 and price >=100 and addtime>=".strtotime(date('Y-m-d'))." and addtime < ".time()." and uid =".$_SESSION['user']['id'])->count();
            $mnum = M('account')->where('id='.$_SESSION['user']['id'])->getField('m_num');
            $mnum_now = $mnum-$m_num;
            $mnum_now = $mnum_now<0?0:$mnum_now;

            $user_id = $_SESSION['user']['id'];
            $where = "a.uid=$user_id and a.tb_item !='' and a.addtime>=".strtotime(date('Y-m-d'))." and a.addtime < ".time();
            $count = D('Task')->alias('a')->where($where)->count();
            $task = D('Task')->alias('a')->where($where)->select();
            $price = $commision = 0;
            foreach ($task as $key => $value){
                $price += $value['actual_price'];
                $commision += $value['commision'];
            }

            $where = "a.uid=$user_id and a.addtime>=".strtotime(date('Y-m-d'))." and a.addtime < ".time();
            $ha_count = D('Task')->alias('a')->where($where)->count();

            $to_time = strtotime(date('Y-m-d 0:0:0'))+63000;
            if($to_time<time())
            {
                $type = 1;
            }else{
                $type = 2;
            }
            $this->assign('ha_count',$ha_count);
            $this->assign('count',$count);
            $this->assign('price',$price);
            $this->assign('commision',$commision);

            $this->assign('type',$type);
            $this->assign('num_now',$num_now);
            $this->assign('lnum_now',$lnum_now);
            $this->assign('mnum_now',$mnum_now);
            $this->assign('info',$info);
            $this->display();
        }
    }




    //分配任务
    public function op(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        $page = I('get.page',1);
        $page = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize',4);
        $count=D('product')
            ->where(array('status' => 3))
            ->field('id,goods_title')
            ->select();

        $count=count($count);
        $data  = D('account')
            ->where(array('msg' => 0, 'role' => 2))
            ->field('id,name')
            ->select();

        $goods = D('product')
            ->where(array('status' => 3))
            ->field('id,goods_title')
            ->order('id DESC')
            ->limit($page*$page_size,$page_size)
            ->select();

        $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
        $this->assign('goods', $goods);
        $this->assign('data', $data);
        $this->display();
    }


    //我的任务
    public function mytask(){

        $id=intval(I('get.id'));
//        $data=D('ProductAttr')->alias('a')->field('a.k,a.goods_id,a.p,a.n,a.s,b.* ')->join('erp_task b on a.id=b.sid')->where('goods_id='.$id)->select();
        //dump($id);exit;
        $data=D('Task')->where('gid='.$id)->order('edittime')->select();

        $this->assign('data',$data);
        //dump($data);exit;
        $this->display();
    }



    //订单
    public function myOrder(){

        if (IS_AJAX){
            $data=array();
            $id              = I('post.id',0,'intval');     //任务ID
            $tb_item         = trim(I('post.tb_item'));           //任务的订单号
            $wangwang        = trim(I('post.wangwang'));    //业务员的旺旺
            $commision       = intval(I('post.commision'));         //佣金
            $type            = I('post.type');              //0是新增  1是修改
            $actual_price    = floatval(I('post.actual_price'));       //刷单员实际输入的价格]
            $redbag          = intval(I('post.redbag'));

            $redbag = $redbag ? $redbag : 0;

            $reg = '/^[0-9][0-9]*$/';
            if( !preg_match($reg,$redbag)) $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额必须为正整数！'));

            $task_info=D('task a')->field('a.*,b.empty_cost as p_empty_cost')->join('left join erp_product b on a.gid=b.id')->where('a.id='.$id)->find();  //当前任务信息


            //佣金区间
            if($type == 0 || ($type == 1 && ($commision != intval($task_info['commision'])))){
                if(!in_array($commision,cost_commision($actual_price)))
                    $this->ajaxReturn(array('msg' => 0, 'info' => '佣金应该为'.implode(',',cost_commision($actual_price)).'元'));

            }

            if($type == 1){
                if(date('Y-m-d',time()) != date('Y-m-d',$task_info['addtime'])){
                    //修改的不是今天的订单。佣金和实际下单价无法修改
                    if($commision != intval($task_info['commision']) ){
                        $this->ajaxReturn(array('msg' => 0, 'info' => '无法修改以前的佣金'));
                    }
                }
            }

            if(empty($task_info))
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误1'));
            if($task_info['uid'] != $_SESSION['user']['id'])
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));

            if($task_info['abn'] == 2 && ($task_info['abn_status'] !=2 && $task_info['abn_status'] != 3))
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务处于申请退单中，不允许修改'));

            if($task_info['empty_cost'] > 0){  //空包存在 e
                if($redbag < 0 || ($redbag > $task_info['empty_cost'] || $redbag > 9))
                    $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额不能大于空包费用'));
            }else{
                //空包不存在
                if( $redbag < 0 || $redbag > 9 )
                    $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额不能大于9元'));
                if( $redbag >= cost_redbag($actual_price))
                    $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额不能大于'.cost_redbag($actual_price).'元'));
            }

            if ($actual_price >f_round($task_info['price'] * 2) || $actual_price <=0)
                $this->ajaxReturn(array('msg' => 0, 'info' => '实际下单价错误'));
            if(strlen($tb_item) != 18)
                $this->ajaxReturn(array('msg' => 0, 'info' => '订单号有误'));

            $now=time();

            if($type == 0){
                // if($task_info['endtime'] <$now )  {
                //     D('Task')->where('id='.$id)->setField('uid',null);
                //     $this->ajaxReturn(array('msg' => 0, 'info' => '任务过期'));
                // }
            }

            if ( empty($id) || empty($tb_item) || empty($commision)  || empty($actual_price))
                $this->ajaxReturn(array('msg' => 0, 'info' => '参数不对'));

            $data['tb_item'] = $tb_item;
            //$this->ajaxReturn(array('msg' => 2, 'info' => $tb_item));
            //查询订单号是否重复
             M()->startTrans();
            if($type == 0 ){
                //开启事务
                //新增
                $where='tb_item='.$tb_item;
                $task=D('Task')->where($where)->find();
                if( $task ) {
                    $this->ajaxReturn(array('msg' => 2, 'info' => '订单号重复'));
                }
            }else{
                //修改
                $where='tb_item='.$tb_item.' and id !='.$id;
                $task=D('Task')->where($where)->find();
                if( $task ) {
                    $this->ajaxReturn(array('msg' => 2, 'info' => '订单号重复'));
                    exit;
                }
            }
            $pub_time=D('product')->where(array('id'=>$task_info['gid']))->getField('pub_time');
            $data['wangwang']     = $wangwang;
            $data['commision']    = $commision;
            $data['edittime']     = time();
            $data['uid']          = $_SESSION['user']['id'];
            $data['name']         = $_SESSION['user']['name'];
            $data['actual_price'] = $actual_price;
            $data['cost']         = cost($task_info['price']);
            $data['redbag']       = $redbag;

            //实际扣款
            if($type ==0){
                //预付金安实际扣除
                $cost    = $task_info['price'] + $task_info['cost'] + $task_info['empty_cost'];
                $yufujin = D('User')->where('uid='.$task_info['user_id'])->setDec('yufujin',$cost);
                if(!$yufujin)  {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 2, 'info' => '预付金扣款操作失败'));
                }
            }
            //商家扣费
            // if($type == 0){
                //新增
                $actual_cost = $actual_price + cost($task_info['price']) + $task_info['empty_cost'];

                $cost = $task_info['cost'];
                if ($cost == 0) {
                    $cost = cost($task_info['price']);
                }
                $cha = $actual_price + cost($actual_price) - $cost- $task_info['price'];
                if(f_round($cha) > 0 ){
                    $balances_status=save_available($task_info['user_id'],abs($cha),$id,8,1);
                }elseif(f_round($cha) < 0){
                    $balances_status=save_available($task_info['user_id'],abs($cha),$id,8,2);
                }
            // } elseif ($type == 1){
            //     $actual_cost = $actual_price + cost($task_info['price']) - $task_info['actual_price'] - $task_info['cost'];
            //     $cha = $actual_cost;
            //     if(f_round($cha) > 0 ){
            //         $balances_status=save_available($task_info['user_id'],abs($cha),$id,8,1);
            //     }elseif(f_round($cha) < 0){
            //         $balances_status=save_available($task_info['user_id'],abs($cha),$id,8,2);
            //     }
            //     if($task_info['p_empty_cost'] == 0){
            //         if( $redbag > 0 && $task_info['empty_cost'] == 0){
            //             $cost_redbag=cost_redbag($actual_price);
            //             $balances_status=save_available($task_info['user_id'],$cost_redbag,$id,9,1);
            //             $actual_cost += $cost_redbag;
            //             $data['empty_cost']= $cost_redbag;
            //         }elseif($redbag > 0 && $task_info['empty_cost'] > 0){
            //             $cha = cost_redbag($actual_price) - $task_info['empty_cost'];
            //             $actual_cost += $cha;
            //             $data['empty_cost'] = cost_redbag($actual_price);
            //             if(f_round($cha) > 0){
            //                 $balances_status=save_available($task_info['user_id'],abs($cha),$id,9,1,$actual_cost * -1 + $cha);
            //             }elseif (f_round($cha) < 0 ){
            //                 $balances_status=save_available($task_info['user_id'],abs($cha),$id,9,2,$actual_cost * -1 + $cha);
            //             }
            //         }elseif ($redbag == 0 && $task_info['empty_cost'] >0){
            //             $cost_redbag = $task_info['empty_cost'];
            //             $balances_status=save_available($task_info['user_id'],$cost_redbag,$id,9,2,$actual_cost * -1);
            //             $actual_cost -= $cost_redbag;
            //             $data['empty_cost'] = 0;
            //         }
            //     }
            // }
            if(abs($cha) != 0){
                //商家扣费
                $shop_money=D('user')->where('uid='.$task_info['user_id'])->setDec('money',$cha);

                if($shop_money===false){
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 2, 'info' => '操作失败1'));
                }
            }
            $totalcost=$actual_price + $commision + $redbag - $task_info['actual_price'] - $task_info['commision'] - $task_info['redbag'];
            if($totalcost != 0){
                //刷单员扣费
                //扣除每单价格和佣金+红包费
                $shuadanyuan_money=D('account')->where('id='.$_SESSION['user']['id'])->setDec('money',$totalcost);

                if($shuadanyuan_money === false){
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));
                }
            }


            //判断修改的是否为用户提交的异常单
            if($task['abn'] ==1){
                //是用户提交的异常单
                $data['abn_status']=5;
            }

            if($task_info['redbag'] == 0 && $redbag > 0 ){
                $data['redbagtime']=time();
            }
            $msg=D('Task')->where('id='.$id)->save($data);
            if ($msg){

                M()->commit();
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('msg' => 2, 'info' => '数据插入操作失败'));
            }
        }
    }

    //关键字
    public function keyword(){

        $gid   = I('post.gid',0,'intval');//产品ID
        $data = D('product_attr')->where(array('goods_id' => $gid))->field('id,k,s,n')->select();
        if (!empty($data)) $this->ajaxReturn(array('msg' => 1, 'info' => json_encode($data)));
    }

    //添加任务
    public function add(){

        if (IS_POST){
            $uid = I('post.uid',0,'intval');//业务员ID
            $gid = I('post.gid',0,'intval');//产品ID
            $num = I('post.num',0,'intval');//份数(默认至少一个)
            $sid = I('post.sid');//产品SKU的ID

            if (empty($gid)) $this->ajaxReturn(array('msg' => 2, 'info' => '请至少选择一个任务!'));
            if (empty($uid)) $this->ajaxReturn(array('msg' => 2, 'info' => '请选择业务员!'));

            $info = D('account')->where(array('id' => $uid))->field('id,name')->find();
            $name = $info['name'];//业务员
            if (empty($info)) $this->ajaxReturn(array('msg' => 2, 'info' => '该业务员不存在!'));

            if ('all' == $sid){//全部
                $data = D('product_attr')->where(array('goods_id' => $gid))->select();
                $count = D('task')->where(array('goods_id' => $gid))->count();//已经分配的任务
                if ($count >= 1) $this->ajaxReturn(array('msg' => 2, 'info' => '此产品部分任务已分配!'));
                $data = $this->getTask($data);//数据整理
                if (!empty($data)) $msg = D('task')->addAll($data);//批量添加
            } else {
                if (empty($sid)) $this->ajaxReturn(array('msg' => 2, 'info' => '请至少选择一个任务!'));
                if (empty($num)) $this->ajaxReturn(array('msg' => 2, 'info' => '请填写份数!'));
                $data  = D('product_attr')->where(array('id' => $sid, 'goods_id' => $gid))->find();//任务信息
                $count = D('task')->where(array('sid' => $sid, 'goods_id' => $gid))->sum('num');//已经分配的任务
                $count = $data['n'] - $count;
                if ($num > $count) $this->ajaxReturn(array('msg' => 2, 'info' => '此任务只剩下'.$count.'份!'));
                if (!empty($data)){
                    $data = array('uid' => $uid, 'gid' => $gid, 'sid' => $sid, 'price' => $data['p'], 'num' => $num, 'sku' => $data['s'], 'keyword' => $data['k'], 'name' => $name, 'addtime' => time());
                    $msg = D('task')->add($data);//添加任务
                }
            }
            if (empty($data)) $this->ajaxReturn(array('msg' => 2, 'info' => '任务不存在!'));
            if ($msg){
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功!'));
            } else {
                $this->ajaxReturn(array('msg' => 3, 'info' => '操作失败,请联系管理员!'));
            }
        }
    }

    //数据整理
    private function getTask($data){
        if (!empty($data)){
            foreach ($data as $key => $val){
                $msg[$key] = array('uid' => $uid, 'gid' => $gid, 'sid' => $val['id'], 'price' => $val['p'], 'num' => $val['n'], 'sku' => $val['s'], 'name' => $name, 'keyword' => $val['k'], 'addtime' => time());
            }
            return $msg;
        }
    }
    //任务详细
    public function allot(){

        $this->display();
    }

    //分配任务
    public function alltotask(){
        $this->display();
    }
    //输入订单号
    public function orderentry(){
        if(IS_GET){

            $this->display();
        }else{
            //dump($_REQUEST);exit;
            $id=intval(I('get.id'));
            $goods_id=intval(I('get.goods_id'));
            //dump($id);exit;
            $data=$_POST;
            $data['name']=$_SESSION['user']['name'];
            $data['edittime']=time();
            $data['uid']=$_SESSION['user']['id'];
            //$goods_info=D('ProductAttr')->where('id='.$id)->find();
            $data['sid']=$id;

            $res=D('Task')->where('id='.$id)->save($data);
            if(!$res){
                //失败
                $this->error('添加失败');
            }
            $this->success('添加成功');


        }

    }
    public function completed(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }

        if(IS_GET){
            $page = I('get.page',1);
            $page = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize',10);
            $ordernum=trim(I('get.ordernum'));
            $shopname=trim(I('get.shopname'));
            $wangwang=trim(I('get.wangwang'));
            $starttime=I('get.time');
            $endtime  =I('get.endtime');
            $where="a.id is not null";
            if(!empty($starttime)) {
                $starttime=strtotime($starttime. "00:00:00");
                $where .= " and a.addtime >= {$starttime}";
            }
            // if(!empty($endtime))   {
            //     $endtime=strtotime($endtime. "23:59:59");
            //     $where .= " and a.addtime <= {$endtime}";
            // }
            // if(empty($starttime) && empty($endtime)){
            //     $time=timeInterval(time());
            //     $starttime=$time['starttime'];
            //     $endtime  =$time['endtime'];
            //     $where .= " and a.addtime  between {$starttime} and {$endtime}";
            // }

            if($shopname || $wangwang || $ordernum){
                unset($starttime);
                // unset($endtime);
                $where="a.id is not null";
            }


            if( $shopname ) $where .= " and c.shopname like '%{$shopname}%'";
            if( $wangwang ) $where .= " and a.wangwang like '%{$wangwang}%'";
            if( $ordernum ) $where .= " and a.tb_item=".$ordernum;

            $user_id=$_SESSION['user']['id'];
            $where .=" and a.uid=$user_id and a.tb_item is not null";
//            if($starttime)
//            {
//                $where .= " and a.addtime  between {$starttime} and {$endtime}";
//            }
            $count=D('Task')
                ->alias('a')
                ->field('a.*,b.goods_title,b.goods_url,b.goods_pic,b.goods_thumb,c.shopname')
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on b.shop_id=c.id')
                ->where($where)
                ->count();
            $task=D('Task')
                ->alias('a')
                ->field('a.*,b.goods_title,b.goods_url,b.goods_pic,b.goods_thumb,c.shopname')
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on b.shop_id=c.id')
                ->where($where)
                ->order('a.id DESC')
                ->select();
            $price=$commision=$redbag=0;

            foreach ($task as $key => $value){
                $info[$key]['price']=floatval($value['price']);
                if($value['abn_status'] !=4 ){
                    $price     += $value['actual_price'];
                    $commision += $value['commision'];
                    $redbag    += $value['redbag'];
                }


            }


            $info=D('Task')
                ->alias('a')
                ->field('a.*,b.goods_title,b.goods_url,b.goods_pic,b.goods_thumb,c.shopname')
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on b.shop_id=c.id')
                ->where($where)
                ->limit($page*$page_size,$page_size)
                ->order('starttime DESC')

                ->select();

            foreach ($info as $key => $value){
                $info[$key]['price']=floatval($value['price']);
            }
            if ( $starttime )  $this->assign('starttime',date("Y-m-d",$starttime));
            if ( $endtime )    $this->assign('endtime',date("Y-m-d",$endtime));

            $this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
            $this->assign('count',$count);
            $this->assign('shopname',$shopname);
            $this->assign('wangwang',$wangwang);
            $this->assign('ordernum',$ordernum);
            $this->assign('price',$price);
            $this->assign('redbag',$redbag);
            $this->assign('commision',$commision);
            $this->assign('info',$info);
            $this->display();
        }
    }
    //刷单员下载表格
    public function excel(){

        $starttime=I('get.time');
        $endtime  =I('get.endtime');
        $shopname = trim(I('get.shopname'));
        $wangwang = trim(I('get.wangwang'));
        $tb_item  = trim(I('get.tb_item'));
        $user_id=$_SESSION['user']['id'];
        $where="a.uid=$user_id and a.tb_item is not null";
        if(!empty($starttime)) {
            $starttime=strtotime($starttime. "00:00:00");
            $where .= " and a.addtime >= {$starttime}";
        }
        // if(!empty($endtime))   {
        //     $endtime=strtotime($endtime. "23:59:59");
        //     $where .= " and a.endtime <= {$endtime}";
        // }
        // if(empty($starttime) && empty($endtime)){
        //     $time=timeInterval(time());
        //     $starttime=$time['starttime'];
        //     $endtime  =$time['endtime'];
        //     $where .= " and a.addtime  between {$starttime} and {$endtime}";
        // }


        if( $shopname ) $where .= " and c.shopname like '%{$shopname}%'";
        if( $wangwang ) $where .= " and a.wangwang like '%{$wangwang}%'";
        if( $tb_item ) $where .= " and a.tb_item='{$tb_item}'";
        if( $starttime ) $where .= " and a.edittime>={$starttime}";
        $info=D('Task')
            ->alias('a')
            ->field('FROM_UNIXTIME(a.edittime) as time,c.shopname,a.keyword,a.price,a.actual_price,a.tb_item,a.wangwang,a.commision')
            ->join('left join erp_product b on a.gid=b.id')
            ->join('left join erp_shop c on b.shop_id=c.id')
            ->where($where)
            ->order('a.starttime DESC')
            ->select();
//        foreach ( $info as $key => $value){
//            $info[$key]['edittime']=date('Y-m-d H:i:s',$value['edittime']);
//        }
        $title=array('完成时间','店铺名','关键字','单价','下单价','订单号','旺旺号','佣金');
        $fileName='订单详情';
        $this->down($title,$info,$fileName);
    }


    public function revoke(){
        if(IS_AJAX){

            $task_id=intval($_POST['task_id']);
            //放弃任务
            $task=D('task')->field('uid,tb_item')->where('id='.$task_id)->find();
            if(intval($task['uid']) != intval($_SESSION['user']['id']) || !empty($task['tb_item'])) $this->ajaxReturn(array('status'=>0,'msg'=>'撤销失败'));

            $array=array('uid'=>null,'starttime'=>null,'is_spe'=>0,'remark'=>null,'is_short'=>0,'short_url'=>null);
            $res=D('Task')->where('id='.$task_id)->setField($array);

            if(!$res) $this->ajaxReturn(array('status'=>0,'msg'=>'撤销失败'));

            $this->ajaxReturn(array('status'=>1,'msg'=>'撤销成功'));

        }
    }



    //增加备注信息
    public function add_remark(){
        if(IS_AJAX){
            $task_id = I('post.task_id',0,'intval');  //任务id

            $remark  = trim(I('post.remark'));   //标记
            if(empty($task_id)) $this->ajaxReturn(array('msg'=>0,'info'=>'信息不存在'));
            $res=D('Task')->where('id='.$task_id)->setField('remark',$remark);
            if( !$res )  $this->ajaxReturn(array('msg'=>0,'info'=>'失败'));
            $this->ajaxReturn(array('msg'=>1,'info'=>'成功'));

        }
    }
    /**
    * 根据条件取得订单
    */
    public function get_task()
    {
        $type = I('post.type');
        switch ($type) {
            case 2:
                $where = "price>=300";
                break;
            case 1:
                $where = "price<100";
                break;
            default:
                $where = "price>=100 and price<300";
                break;
        }
        $sql_no = "select gid,count(*) c from erp_task where addtime>=1533830400 and uid is null and `status`=1 and xiajia=0 and {$where} group by gid";
        // 获取每个商家未领取的任务数量
        $list_no = M()->Query($sql_no);
        $gids = "0";
        $uid_pri_no = array();
        foreach ($list_no as $item_no) {
            $gids .= ",{$item_no['gid']}";
            $uid_pri_no[$item_no['gid']] = $item_no['c'];
        }
        $sql_all = "select gid,count(*) c from erp_task where addtime>=1533830400 and {$where} and gid in ($gids) group by gid";

        // 获取每个商家当天总的任务数量
        $list_all = M()->Query($sql_all);
        $uid_pri_all = array();
        $list_all2 = array();
        // 多少小时领完
        $cs = 10;
        foreach ($list_all as $item_all) {
            $list_all2[$item_all['gid']] = $item_all['c'];
            $pj = floor(intval($item_all['c'])/$cs);
            if ($pj == 0) {
                $pj = 1;
            }
            $uid_pri_all[$item_all['gid']] = $pj;
        }
        $uid_pri_no = $this->shuffle_assoc($uid_pri_no);
        $uid_pri_no = $this->shuffle_assoc($uid_pri_no);
        $uid_pri_no = $this->shuffle_assoc($uid_pri_no);
        $h = intval(date('H'));
        if ($h < 19) {
            $cs = 19 - $h;
        }
        $gid = 0;
        $id = 0;
        if ($h > 8 || ($h >= 0 && $h < 3)) {
            foreach ($uid_pri_no as $key => $cc) {
                if ($cs > 1 && $h >=3 && $h < 18) {
                    if ($cc > 0) {
                        if ($cc > (intval($uid_pri_all[$key]) * (18 - $h))) {
                            $gid = $key;
                        }else{
                            // 如果任务量小于 $cs
                            $dj = intval($list_all2[$key]) - intval($h) + 9;
                            if ($cc > $dj) {
                                $gid = $key;
                            }
                        }
                    }
                }else{
                    $gid = $key;
                }
                if ($gid !== 0) {
                    $id = M('Task')->where('`status`=1 and xiajia =0 and addtime < '.time().' and uid is null and gid='.$gid)->getField('id');
                    if (!empty($id)) {
                        break;
                    }else{
                        $gid = 0;
                        continue;
                    }
                }
            }
        }
        // if ($_SESSION['user']['id']==534) {
        //     var_dump($gid,$cs,$h,$id);
        //     print_r($list_all2);
        //     print_r($uid_pri_all);
        //     print_r($uid_pri_no);exit;
        // }
        if ($gid === 0) {
            $this->ajaxReturn(array('status'=>0, 'msg'=>'此时间段任务已领完'));
        }
        // $id = M('Task')->where('`status`=1 and xiajia =0 and addtime < '.time().' and endtime >'.time().' and uid is null and gid='.$gid)->getField('id');
        if($id){
            $array=array(
                    'uid'=>$_SESSION['user']['id'],
                    'starttime'=>time(),
                    'is_spe'=>$is_spe
                    );
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));die();
        }
        $res=D('Task')->where('id='.$id)->setField($array);
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>'领取任务成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));
        }
    }
    // 打乱二维数组
    private function shuffle_assoc($list)
    {
        if (!is_array($list)) return $list; 
        $keys = array_keys($list); 
        shuffle($keys); 
        $random = array(); 
        foreach ($keys as $key) 
        $random[$key] = $list[$key]; 
        return $random; 
    }
    //得到小额，大额单
    public function get_task2()
    {
        $offset = 4;
        $type = I('post.type');
        $if_go = $this->if_gain($type);
        if (!$if_go) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'此时间段任务已领完'));die();
        }
        if($type==2){
            $where = 'price>=300';
        }elseif($type==1){
            $where = 'price<100';
        }else{
            $where = 'price>=100 and price<300';
        }
        $last_user = M('Task')->where('addtime < '.time())->order('starttime desc')->getField('shop_id');
        $query = 'SELECT count(shop_id) as count,shop_id FROM erp_task where uid is not null and status =1 and xiajia =0 and '.$where.' and addtime <'.time().' GROUP BY shop_id';
        $ful_data = M()->Query($query);
        $query = 'SELECT count(shop_id) as count,shop_id FROM erp_task where status =1 and xiajia =0';
        $query .= ' and gid in(SELECT id FROM erp_product WHERE status in (0,3) and addtime <'.time().') and '.$where.' and addtime <'.time().' GROUP BY shop_id';
        $all_data = M()->Query($query);
        foreach ($ful_data as $val)
        {
            $user_data[$val['shop_id']] = $val['count'];
        }

        foreach ($all_data as $key=>$val)
        {
            $ful_cunt = isset($user_data[$val['shop_id']])?$user_data[$val['shop_id']]:0;
            if($val['count']==$ful_cunt) continue;
            if($val['count']<90)
            {
                $val['count'] = round(10*$val['count']/$offset);
            }
            $user_next[$val['shop_id']] = round($ful_cunt/$val['count'],2);
        }
        if(empty($user_next))
        {
            $this->ajaxReturn(array('status'=>0,'msg'=>'订单已领完'));die();
        }
        asort($user_next);
        $shop_id = key($user_next);//店铺ID
        

        $lastgid = M('Task')->where('status =1 and xiajia =0 and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is not null')->order('starttime desc')->getField('gid');
        //店铺商品总数
        $query = 'SELECT gid FROM erp_task where status =1 and xiajia =0 and uid is null and shop_id='.$shop_id.' and '.$where;
        $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and addtime < '.time().' GROUP BY gid';
        $gid_data = M()->Query($query);
        $gid_count = count($gid_data);
        $thisgid = '';//应该取的商品ID
        if($gid_count>1)
        {

            $query = 'SELECT count(id) as a,count(IF(uid,1,NULL)) as b,gid FROM erp_task WHERE status =1 and xiajia =0 and shop_id='.$shop_id;
            $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and '.$where.' and addtime < '.time().' GROUP BY gid';
            $task_data = M()->Query($query);
            foreach($task_data as $val)
            {
                $ful_cunt = isset($val['b'])?$val['b']:0;
                if($val['a']==$ful_cunt) continue;
                if($val['a']<30)
                {
                    $val['a'] = round(10*$val['a']/$offset);
                }
                $task_datas[$val['gid']] = round(($val['a']-$ful_cunt)/$val['a'],2);
            }
            arsort($task_datas);
            $thisgid = key($task_datas);
        }
        $gidwhere = '';
        if(!empty($thisgid))
        {
            $gidwhere = ' and gid=\''.$thisgid.'\'';
        }
        $lastkeyword = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is not null')->order('starttime desc')->getField('keyword');
        
        $query = 'SELECT keyword FROM erp_task where status =1 and xiajia =0 and uid is null '.$gidwhere;
        $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' GROUP BY keyword';
        $keyword_data = M()->Query($query);
        $keyword_count = count($keyword_data);
        if(empty($lastkeyword) || $keyword_count==1)
        {

            $id = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is null')->getField('id');
        }else {
            foreach($keyword_data as $val)
            {
                $keyword_all[] = $val['keyword'];
            }
            $lastkeyword_data[] = $lastkeyword;
            $result = array_diff($keyword_all,$lastkeyword_data);
            $thiskey = array_rand($result);
            $thiskeyword = $result[$thiskey];
            $id = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is null and keyword=\''.$thiskeyword.'\'')->getField('id');

        }
        if($id)
        {
            $array=array(
                    'uid'=>$_SESSION['user']['id'],
                    'starttime'=>time(),
                    'is_spe'=>$is_spe
                    );
        }else
        {
            $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));die();
        }
        $res=D('Task')->where('id='.$id)->setField($array);
        if($res)
        {
            $this->ajaxReturn(array('status'=>1,'msg'=>'领取任务成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));
        }
    }



    public function text_list()
    {
            $offset = 6;
        $type = I('post.type',2);
        if($type==2)
        {
            $if_go = $this->if_gain(2);
            if(!$if_go) $this->ajaxReturn(array('status'=>0,'msg'=>'今日本额度任务配额已领完，请联系主管！'));die();
            $where = 'price>=300';
            $is_spe = 2;
        }else
        {
            $if_go = $this->if_gain(1);
            if(!$if_go) $this->ajaxReturn(array('status'=>0,'msg'=>'今日本额度任务配额已领完，请联系主管！'));die();
            $where = 'price<100';
            $is_spe = 1;
        }
        $last_user = M('Task')->where('addtime < '.time())->order('starttime desc')->getField('shop_id');
        $query = 'SELECT count(shop_id) as count,shop_id FROM erp_task where uid is not null and status =1 and xiajia =0 and '.$where.' and addtime <'.time().' GROUP BY shop_id';
        $ful_data = M()->Query($query);
        $query = 'SELECT count(shop_id) as count,shop_id FROM erp_task where status =1 and xiajia =0';
        $query .= ' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().') and '.$where.' and addtime <'.time().' GROUP BY shop_id';
        $all_data = M()->Query($query);
        foreach ($ful_data as $val)
        {
            $user_data[$val['shop_id']] = $val['count'];
        }

        foreach ($all_data as $key=>$val)
        {
            $ful_cunt = isset($user_data[$val['shop_id']])?$user_data[$val['shop_id']]:0;
            if($val['count']==$ful_cunt) continue;
            if($val['count']<30)
            {
                $val['count'] = round(10*$val['count']/$offset);
            }
            //$all_data[$key]['a_count'] = $val['count'];
            //$all_data[$key]['ful_cunt'] = $ful_cunt;
            //$all_data[$key]['ratio'] = round($ful_cunt/$val['count'],2);
            $user_next[$val['shop_id']] = round($ful_cunt/$val['count'],2);
        }
        if(empty($user_next))
        {
            $this->ajaxReturn(array('status'=>0,'msg'=>'订单已领完'));die();
        }
        asort($user_next);
        $shop_id = key($user_next);//店铺ID
        $end = strtotime(date('Y-m-d 00:00:00'))+50400;
        $time = time();
        if($time>$end)
        {
            if(isset($user_next[291]))//291
            {

                $arr2 = array(291);
                foreach ($arr2 as $key=>$value)
                {
                    if (!isset($user_next[$value]))
                        unset($arr2[$key]);
                }
                $thiskey = array_rand($arr2);
                $shop_id = $arr2[$thiskey];
            }else{
                $shop_id = key($user_next);//商家ID
            }
        }
        else{
            $shop_id = key($user_next);//商家ID
        }


        //最后取到店铺的商品
        $lastgid = M('Task')->where('status =1 and xiajia =0 and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is not null')->order('starttime desc')->getField('gid');
        //店铺商品总数
        $query = 'SELECT gid FROM erp_task where status =1 and xiajia =0 and uid is null and shop_id='.$shop_id.' and '.$where;
        $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and addtime < '.time().' GROUP BY gid';
        $gid_data = M()->Query($query);
        $gid_count = count($gid_data);
        $thisgid = '';//应该取的商品ID
        if($gid_count>1)
        {

            $query = 'SELECT count(id) as a,count(IF(uid,1,NULL)) as b,gid FROM erp_task WHERE status =1 and xiajia =0 and shop_id='.$shop_id;
            $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and '.$where.' and addtime < '.time().' GROUP BY gid';
            $task_data = M()->Query($query);
            foreach($task_data as $val)
            {
                $ful_cunt = isset($val['b'])?$val['b']:0;//已完成量
                if($val['a']==$ful_cunt) continue;
                if($val['a']<30)
                {
                    $val['a'] = round(10*$val['a']/$offset);
                }
                $task_datas[$val['gid']] = round(($val['a']-$ful_cunt)/$val['a'],2);
            }
            arsort($task_datas);
            $thisgid = key($task_datas);
            /*foreach($gid_data as $val)
            {
                $gid_all[] = $val['gid'];
            }
            $lastgid_data[] = $lastgid;
            $result = array_diff($gid_all,$lastgid_data);
            $thiskey = array_rand($result);
            $thisgid = $result[$thiskey];*/

        }
        $gidwhere = '';
        if(!empty($thisgid))
        {
            $gidwhere = ' and gid=\''.$thisgid.'\'';
        }
        //最后取到的关键字
        $lastkeyword = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is not null')->order('starttime desc')->getField('keyword');
        //商品关键字总数
        $query = 'SELECT keyword FROM erp_task where status =1 and xiajia =0 and uid is null '.$gidwhere;
        $query .=' and gid in(SELECT id FROM erp_product WHERE status=0 or status=3 and addtime <'.time().')  and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' GROUP BY keyword';
        $keyword_data = M()->Query($query);
        $keyword_count = count($keyword_data);
        if(empty($lastkeyword) || $keyword_count==1)
        {

            $id = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is null')->getField('id');
        }else {
            foreach($keyword_data as $val)
            {
                $keyword_all[] = $val['keyword'];
            }
            $lastkeyword_data[] = $lastkeyword;
            $result = array_diff($keyword_all,$lastkeyword_data);
            $thiskey = array_rand($result);
            $thiskeyword = $result[$thiskey];
            $id = M('Task')->where('status =1 and xiajia =0'.$gidwhere.' and shop_id='.$shop_id.' and '.$where.' and addtime < '.time().' and uid is null and keyword=\''.$thiskeyword.'\'')->getField('id');

        }
        if($id)
        {
            $array=array(
                    'uid'=>$_SESSION['user']['id'],
                    'starttime'=>time(),
                    'is_spe'=>$is_spe
                    );
        }else
        {
            $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));die();
        }
        $res=D('Task')->where('id='.$id)->setField($array);
        if($res)
        {
            $this->ajaxReturn(array('status'=>1,'msg'=>'领取任务成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'领取任务失败'));
        }
    }
    //申请短链接
    public function short_url()
    {
        $task_id = I('post.task_id','');//任务ID
        if(empty($task_id)) $this->ajaxReturn(array('msg' => 2, 'info' => '任务不存在'));
        $array = array('is_short'=>1);
        $taskinfo = M('task')->where('id='.$task_id.' and uid='.$_SESSION['user']['id'])->setField($array);
        if($taskinfo)
        {
            $this->ajaxReturn(array('msg' => 1, 'info' => '申请成功，等待审核'));
        }else
        {
            $this->ajaxReturn(array('msg' => 2, 'info' => '申请失败'));    
        }
    }
    
    //判断当前是否可以领取单
    public function if_gain($type)
    {
        $l_price = 300;
        $s_price = 100;
        $star = strtotime(date('Ymd H:0:0'));

        $total     = D('task')->where('status=1 and xiajia=0 and addtime <'.time())->count(); 
        $total_get = D('task')->where('status=1 and xiajia=0 and uid!=\'\' and addtime <'.time())->count();  
        //当天14点
        $time=strtotime(date('Ymd 0:0:0'))+50400;
        if( (f_round($total_get / $total) < 0.6 )){
            // $offset=37800;
            return true;
        }elseif (time() >= $time) {
            return true;
        }else{
            return false;
        }
        $uid=$_SESSION['user']['id'];
        if($type==1)//小额
        {
            // $where = 'status=1 and xiajia=0 and uid is null and price<'.$s_price.' and addtime <'.time();
            // $count = M('task')->where($where)->count();//
            $user_num = M('account')->where('id='.$uid)->getField('num');//
            $where = 'status=1 and xiajia=0 and price<'.$s_price.' and starttime >='.$star.' and uid='.$uid;
            $use_count = M('task')->where($where)->count();//
            
        }elseif($type==2)//大额
        {
            // $where = 'status=1 and xiajia=0 and uid is null and price>='.$l_price.' and addtime <'.time();
            // $count = M('task')->where($where)->count();//
            $user_num = M('account')->where('id='.$uid)->getField('l_num');
            $where = 'status=1 and xiajia=0 and price>='.$l_price.' and starttime >='.$star.' and uid='.$uid;
            $use_count = M('task')->where($where)->count();//
        }else {
            // $where = 'status=1 and xiajia=0 and uid is null and price>='.$s_price.' and price<'.$l_price.' and addtime <'.time();
            // $count = M('task')->where($where)->count();// 
            $user_num = M('account')->where('id='.$uid)->getField('m_num');//
            $where = 'status=1 and xiajia=0 and price>='.$s_price.' and price<'.$l_price.' and starttime >='.$star.' and uid='.$uid;
            $use_count = M('task')->where($where)->count();//
        }
        if ($use_count < $user_num) {
            return true;
        }else{
            return false;
        }
        // $user_num = empty($user_num)?20:$user_num;
        // $use_count = empty($use_count)?0:$use_count;
        // $endtime = strtotime(date('Ymd 0:0:0'))+64800;
        // $hour = ceil(($endtime-time())/3600);//
        // $aver = ceil($count/($user_num*$hour));// 
        // if($use_count>=$aver)
        // {
        //     return false;
        // }else
        // {
        //     return true;
        // }
    }
    // 短连接申请审核
    public function is_sure()
    {
        $task_id = I('post.task_id','');//任务ID
        $type = I('post.type',1);//默认拒绝
        if(empty($task_id)) $this->ajaxReturn(array('msg' => 2, 'info' => '任务不存在'));
        if($type==2)
        {
            $array = array('is_short'=>3);
            $res = M('task')->where('id='.$task_id)->setField($array);
            if($res)
            {
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
            }else
            {
                $this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));    
            }
        }elseif($type==1)
        {
            $gid = M('task')->where('id='.$task_id)->getField('gid');
            $goods_url =  htmlspecialchars_decode(M('product')->where('id='.$gid)->getField('goods_url'));
            
            if(strpos($goods_url,'&id=') !==false)
            {
                 $str = substr($goods_url,(strpos($goods_url,'&id=')+4));
                 if(strpos($str,'&') !==false)
                {
                    $tb_gid = substr($str,0,strpos($str, '&'));
                }else {
                    $tb_gid = $str;
                }
            }else{
                $str = substr($goods_url,(strpos($goods_url,'id=')+3));
                if(strpos($str,'&') !==false)
                {
                    $tb_gid = substr($str,0,strpos($str, '&'));
                }else {
                    $tb_gid = $str;
                }
            }
            
            
            $keyword = M('task')->where('id='.$task_id)->getField('keyword');
            
            
            $re = http_build_query(array('q'=>$keyword,'nid'=>$tb_gid));
            $url = 'https://s.m.taobao.com/h5?'.$re;
            $url = urlencode($url);
            $url = "http://api.c7.gg/api.php?format=json&url=".$url;
            
            $ch = curl_init();

            //设置选项，包括URL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
            //执行并获取HTML文档内容
            $output = curl_exec($ch);
            
            //释放curl句柄
            curl_close($ch);
            
            $short_url = trim($output, "\xEF\xBB\xBF");
            $re = json_decode($short_url,true);
            
            
            $array = array('is_short'=>2,'short_url'=>$re['url']);
            $res = M('task')->where('id='.$task_id)->setField($array);
            
            if($res!==false)
            {
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功','url' => $re['url']));
            }else
            {
                $this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));    
            }
            
        }   
    }
 

}
?>