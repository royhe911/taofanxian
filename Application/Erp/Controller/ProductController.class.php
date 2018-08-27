<?php
/**
 * Created by PhpStorm.
 * user: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;

use Common\Util\Util;
use Think\Controller;

class ProductController extends BaseController
{
    //产品展示
    public function index()
    {
        //判断当前权限
        if (check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            $status    = I('status', '4', 'int');
            $shopname  = trim(I('get.shopname'));
            $sjname    = trim(I('get.sjname'));
            $starttime = I('get.time');
            $endtime   = I('get.endtime');
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);
            $yw        = D('account')->field('id,info')->where('role = 6 and msg = 0')->select();
            $this->assign('yw', $yw);
            $choiceyw = I('get.choiceyw');
            if (!in_array($status, array(0, 1, 2, 3))) {
                $status = 4;
            }

            //4 代表全部 4代表已发布 1代表已付款 0 代表待审核 2代表已拒绝
            $where   = " a.id is not null";
            $where_a = ' a.tb_item is not null'; //数据汇总条件
            if ($status != 4) {
                $where .= " and a.status =" . $status;
            }

            //权限判断
            /******************************************************************************************/
            if ($_SESSION['user']['role'] == 5) {
                //商家
                $where .= " and a.user_id=" . intval($_SESSION['user']['id']);
            } elseif ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 4) {
                //管理员和财务
            } elseif ($_SESSION['user']['role'] == 6) {
                //业务员
                $where .= " and c.tid=" . intval($_SESSION['user']['id']);
                $where_a .= ' and a.tid=' . intval($_SESSION['user']['id']);
            }
            /******************************************************************************************/
            if (!empty($starttime)) {
                $starttime = strtotime($starttime . "00:00:00");
                $where .= " and a.addtime >= {$starttime}";
                $where_a .= " and a.addtime >= {$starttime}";
            }
            if (!empty($endtime)) {
                $endtime = strtotime($endtime . "23:59:59");
                $where .= " and a.addtime <= {$endtime}";
                $where_a .= " and a.addtime <= {$endtime}";
            }

            if (!empty($shopname)) {
                $where .= " and b.shopname like '%$shopname%'";
                $where_a .= " and b.shopname like '%$shopname%'";
            }
            if (!empty($sjname)) {
                $where .= " and c.shopname like '%$sjname%'";
                $where_a .= " and c.shopname like '%$sjname%'";
            }
            if (!empty($choiceyw)) {
                $where .= " and c.tid=" . $choiceyw;
                $where_a .= " and c.tid=" . $choiceyw;
            }
            $count_a = D('product a')
                ->field('a.*,b.shopname,b.url,c.shopname as sjname,d.realname as yw_realname')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->join('left join erp_account d on c.tid=d.id')
                ->where($where)
                ->count();
            $data = D('product a')
                ->field('a.*,b.shopname,b.url,c.shopname as sjname,d.realname as yw_realname,d.info as yw_info')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->join('left join erp_account d on c.tid=d.id')
                ->where($where)
                ->limit($page * $page_size, $page_size)
                ->order('a.id desc')
                ->select();
            foreach ($data as $key => $value) {
                if ($value['addtime'] > time()) {
                    $data[$key]['shangjia'] = true;
                }
                $data[$key]['miid'] = urlencode(encrypt($value['id'], 'E'));
                //预估费用
                $data[$key]['goods_totalprice'] = floatval($value['goods_totalprice']); //产品预估总本金
                $data[$key]['goods_totalcost']  = floatval($value['goods_totalcost']); //产品预估费用
                $data[$key]['goods_order']      = f_round($value['empty_cost'] * $value['goods_totalnum']); //产品预估其他费用
                $data[$key]['goods_total']      = f_round($data[$key]['goods_totalprice'] + $data[$key]['goods_totalcost'] + $data[$key]['goods_order']); //产品预估总费用

                //已完成费用
                $where_s = "gid={$value['id']} and tb_item is not null";

                $task_info = D('task')->where($where_s)->select();

                $count                       = count($task_info);
                $data[$key]['count']         = $count; //已完成份数
                $success_money               = D('task a')->field('sum(IFNULL(a.actual_price,0)) as success_price,sum(a.cost)as actual_cost,sum(IFNULL(if(a.empty_cost>0,a.empty_cost,a.redbag),0)) as order_s,sum(IFNULL(a.price,0)) as success_ben')->where($where_s)->select();
                $success_price               = $success_money[0]['success_price']; //完成金额
                $actual_cost                 = $success_money[0]['actual_cost']; //已扣服务费
                $order                       = $success_money[0]['order_s']; //其他
                $success_ben                 = $success_money[0]['success_ben']; //已完成任务的原始本金
                $data[$key]['success_price'] = f_round($success_price);
                $data[$key]['actual_cost']   = f_round($actual_cost);
                $data[$key]['order']         = f_round($order);
                $data[$key]['total']         = f_round($data[$key]['success_price'] + $data[$key]['actual_cost'] + $data[$key]['order']);
                //未完成任务统计
                $data[$key]['error_num']   = $data[$key]['goods_totalnum'] - $count; //未完成数量
                $data[$key]['error_price'] = f_round($data[$key]['goods_totalprice'] - $success_ben); //未完成金额  总金额-已完成的原金额

            }

            //统计当天未完成订单费用
            $where_e = 'a.addtime <' . time() . ' and a.endtime >' . time() . ' and a.status = 1 and a.xiajia = 0 and a.tb_item is null';
            if ($_SESSION['user']['role'] == 5) {
                //商家
                $where_e .= ' and a.user_id=' . intval($_SESSION['user']['id']);
            } elseif ($_SESSION['user']['role'] == 6) {
                $where_e .= ' and a.tid=' . intval($_SESSION['user']['id']);
            }
            $money = D('task')->field('sum(actual_price+cost+empty_cost+redbag) total')->where("uid!='' and tb_item!='' and user_id={$_SESSION['user']['id']}")->find();
            // $money = D('task a')
            //     ->field('sum(a.price) as totalprice,sum(a.cost) as totalcost,sum(a.empty_cost) as total_empty_cost')
            //     ->where($where_e)
            //     ->select();
            // $total_e_money = f_round($money[0]['totalprice'] + $money[0]['totalcost'] + $money[0]['total_empty_cost']);
            if (!empty($money['total'])) {
                $total_e_money = $money['total'];
            }else{
                $total_e_money = 0;
            }

            //数据汇总

            //任务完成总数
            $task_total = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a)
                ->count();
            //小额单
            $task_total_s = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.actual_price < 100')
                ->count();
            //中额单
            $task_total_m = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.actual_price >= 100 and a.actual_price < 300')
                ->count();
            //大额单
            $task_total_l = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.actual_price >= 300')
                ->count();
            //退款订单总数
            $task_refund = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 2 and a.abn_status = 4')
                ->count();
            //小额单
            $task_refund_s = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 2 and a.abn_status = 4 and a.actual_price < 100')
                ->count();
            //中额单
            $task_refund_m = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 2 and a.abn_status = 4 and a.actual_price >= 100 and a.actual_price < 300')
                ->count();
            //大额单
            $task_refund_l = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 2 and a.abn_status = 4 and a.actual_price >= 300 ')
                ->count();
            //异常订单总数
            $task_abn = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 1')
                ->count();
            //小额单
            $task_abn_s = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 1 and a.actual_price < 100')
                ->count();
            //中额单
            $task_abn_m = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 1 and a.actual_price >= 100 and a.actual_price < 300')
                ->count();
            //大额单
            $task_abn_l = D('task a')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_user c on a.user_id=c.uid')
                ->where($where_a . ' and a.abn = 1 and a.actual_price >= 300')
                ->count();
            $this->assign('task_total', $task_total); //任务完成总数
            $this->assign('task_total_s', $task_total_s); //小额单
            $this->assign('task_total_m', $task_total_m); //中额单
            $this->assign('task_total_l', $task_total_l); //大额单
            $this->assign('task_refund', $task_refund); //退款订单总量
            $this->assign('task_refund_s', $task_refund_s); //小额单
            $this->assign('task_refund_m', $task_refund_m); //中额单
            $this->assign('task_refund_l', $task_refund_l); //大额单
            $this->assign('task_abn', $task_abn); //异常订单总量
            $this->assign('task_abn_s', $task_abn_s); //小额单
            $this->assign('task_abn_m', $task_abn_m); //中额单
            $this->assign('task_abn_l', $task_abn_l); //大额单
            //end

            $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count_a));

            if ($starttime) {
                $this->assign('starttime', date("Y-m-d", $starttime));
            }

            if ($endtime) {
                $this->assign('endtime', date("Y-m-d", $endtime));
            }

            $this->assign('count_a', $count_a);
            $this->assign('shopname', $shopname);
            $this->assign('status', $status);
            $this->assign('sjname', $sjname);
            $this->assign('data', $data);
            $this->assign('total_e_money', $total_e_money);
            $this->display();

        }
    }

    //产品添加
    public function addproduct()
    {
        $this->redirect("product/addproductnew");
    }

    //新的产品添加页面
    public function addproductnew()
    {
        if ($_SESSION['user']['role'] != 5) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {

            $resadd = $this->check_money(123);
            if ($resadd === false) {
                $this->error('余额不足，请充值', U('index'));
            }

            $where = "uid=" . intval($_SESSION['user']['id']) . " and status = 2";
            $shop  = D('shop')->field('id,shopname')->where($where)->select();
            if (empty($shop)) {
                $this->error('没有可选店铺');
                return false;
            }
            $this->assign('shop', $shop);
            $this->display();
        } elseif (IS_POST) {
            //验证
            $model = D('product');
            $data  = $model->create();

            //添加到数据
            $res = $model->add($data);
            if (!$res) {
                $this->error($model->getError(), U('index'));
            }
            if ($model->getError() != '') {
                $this->error($model->getError(), U('index'));
            }

            $this->success('添加成功，待审核完成后发布', U('index'));
        }

    }
    public function editproductnew()
    {
        if ($_SESSION['user']['role'] != 5) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {

            $goods_id = urldecode(encrypt(I('get.id'), 'D')); //商品id
            if (!is_numeric($goods_id)) {
                $this->error('信息不正确');
                return false;
            }
            $where = "uid=" . intval($_SESSION['user']['id']) . " and status = 2";
            $shop  = D('shop')->field('id,shopname')->where($where)->select(); //商家店铺
            if (empty($shop)) {
                $this->error('没有可选店铺');
                return false;
            }
            $this->assign('shop', $shop);
            $data = D('product')->where('id=' . $goods_id)->find(); //产品基本信息
            $this->assign('data', $data);
            $info = D('ProductAttr')->where('goods_id=' . $goods_id)->select(); //商品关键字信息

            foreach ($info as $key => $value) {
                $info[$key]['s'] = explode(',', $value['s']);
            }

            $this->assign('i', count($info));
            $this->assign('info', $info);
            $this->display();

        } else {

            $id = I('post.id', 0, 'intval');
            if ($id <= 0) {
                $this->error('参数错误', U('index'));
            }

            $model = D('Product');
            $data  = $model->create();
// print_r($data);exit;
            $res = $model->update($data);
// var_dump($model->getError());exit;
            if ($model->getError() != '') {
                $this->error($model->getError(), U('index'));
            }
            if (!$res) {
                $this->error($model->getError(), U('index'));
            }

            $this->success('修改成功，等待审核', U('Product/index'));

        }
    }
    //产品复制功能
    public function copyproduct()
    {
        if ($_SESSION['user']['role'] != 5) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {

            $goods_id = urldecode(encrypt(I('get.id'), 'D')); //商品id
            if (!is_numeric($goods_id)) {
                $this->error('信息不正确');
                return false;
            }
            $where = "uid=" . intval($_SESSION['user']['id']) . " and status = 2";
            $shop  = D('shop')->field('id,shopname')->where($where)->select(); //商家店铺
            if (empty($shop)) {
                $this->error('没有可选店铺');
                return false;
            }
            $this->assign('shop', $shop);
            $data = D('product')->where('id=' . $goods_id)->find(); //产品基本信息
            $this->assign('data', $data);
            $info = D('ProductAttr')->where('goods_id=' . $goods_id)->select(); //商品关键字信息
            foreach ($info as $key => $value) {
                $info[$key]['s'] = explode(',', $value['s']);

            }

            $this->assign('i', count($info));
            $this->assign('info', $info);
            $this->display();
        } else {
            $model = D('product');
            $data  = $model->create();

            //添加到数据
            $res = $model->add($data);

            if ($model->getError() != '') {
                $this->error($model->getError(), U('index'));
            }
            if (!$res) {
                $this->error('添加失败', U('index'));
            }

            $this->success('添加成功，待审核完成后发布', U('index'));
        }
    }
    //产品删除
    public function delproduct()
    {
        //产品id
        $goods_id = intval($_POST['id']);
        if (empty($goods_id)) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息不存在'));
        }

        $goods = D('product')->field('user_id,status')->where('id=' . $goods_id)->find();
        if ($goods['user_id'] != intval($_SESSION['user']['id']) || intval($goods['status']) != 1) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '系统错误'));
        }

        $model = D('product');

        $res = $model->del($goods_id);
        if (!$res) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '删除失败'));
        }
        $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功'));

    }

    //产品编辑
    public function editproduct()
    {
        $this->redirect("welcome/index");
//        if(IS_GET){
        //
        //
        //            $id=intval($_SESSION['user']['id']);
        //
        //            //查询是否有绑定店铺
        //            $info=M('user')->where("uid=$id")->find();
        //
        //            if( empty($info['url']) || $info['msg'] !=1){
        //                $this->error('店铺未审核',U('index'));
        //            }
        //
        //
        //            $goods_id=urldecode(encrypt(I('get.id'),'D'));
        //
        //            //获取对应的信息
        //            $goodsInfo=D('Product')->where("id=$goods_id")->find();
        //            //显示基本信息
        //            $this->assign('info',$goodsInfo);
        //            //获取属性信息
        //            $attr=M('ProductAttr')->where("goods_id=$goods_id")->select();
        //            //显示属性信息
        //            foreach ( $attr as $key => $value){
        //                $attr[$key]['p']=floatval($value['p']);
        //                $attr[$key]['cost']=floatval($value['cost']);
        //            }
        //            $this->assign('attr',$attr);
        //            //获取最后一个id
        //            $lastId=array_pop($attr);
        //            $this->assign('lastId',$lastId['id']);
        //            $this->display();
        //        }else{
        //
        //            $id=I('post.id',0,'intval');
        //            if($id <=0 )   $this->error('参数错误',U('index'));
        //
        //
        //            $model = D('Product');
        //            $data  = $model->create();
        //
        //
        //            $res   = $model->update($data);
        //
        //            if($model->getError() != ''){
        //                $this->error($model->getError(),U('index'));
        //            }
        //            if(!$res){
        //                $this->error($model->getError());
        //            }
        ////            D('user')->where('uid='.$_SESSION['user']['id'])->setInc('firstadd');
        //            $this->success('修改成功，等待审核',U('Product/index'));
        //
        //        }

    }
    //产品详情查看
    public function prodetail($id)
    {

        $id = urldecode(encrypt($id, 'D'));
        if (!is_numeric($id)) {
            $this->error('信息不存在');
            return false;
        }
        $data = M('ProductAttr')->where('goods_id=' . $id)->select();
        foreach ($data as $key => $value) {
            $data[$key]['p']    = floatval($value['p']);
            $data[$key]['cost'] = floatval($value['cost']);
        }
        $this->assign('data', $data);
        $this->display();
    }

    //产品审核
    public function check()
    {
        //判断当前权限
        if (check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        //分页
        $page      = I('get.page', 1);
        $page      = $page < 1 ? 0 : $page - 1;
        $page_size = I('get.pagesize', 100);

        //管理员可以审核所有商家发布的产品
        //业务员只能看到自己商家的产品 roleid = 6

        $where = "a.status = 0";
        if ($_SESSION['user']['role'] == 6) {
            $where .= " and c.tid=" . intval($_SESSION['user']['id']);
        }
        $count = D('product a')
            ->field('a.*,b.shopname,d.realname,d.info as yw_info')
            ->join('left join erp_shop b on a.shop_id=b.id')
            ->join('left join erp_user c on a.user_id=c.uid')
            ->join('left join erp_account d on c.tid=d.id')
            ->where($where)
            ->count();
        $data = D('product a')
            ->field('a.*,b.shopname,d.realname,d.info as yw_info')
            ->join('left join erp_shop b on a.shop_id=b.id')
            ->join('left join erp_user c on a.user_id=c.uid')
            ->join('left join erp_account d on c.tid=d.id')
            ->where($where)
            ->limit($page * $page_size, $page_size)
            ->order('a.id desc')
            ->select();

        foreach ($data as $key => $v) {

            //将id加密并附加到miid中   miid 加密后的id
            $data[$key]['miid'] = urlencode(encrypt($data[$key]['id'], 'E'));

            $data[$key]['goods_totalprice'] = floatval($v['goods_totalprice']);
            $data[$key]['goods_totalcost']  = floatval($v['goods_totalcost']);
            $data[$key]['empty_costall']    = f_round($v['goods_totalnum'] * $v['empty_cost']); //空包总费用
        }

        $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count));
        $this->assign('count', $count);
        //dump($data);exit;
        $this->assign('data', $data);
        $this->display();
    }
    //审核通过
    public function tongguo()
    {
        $id = intval($_POST['id']);
        if (empty($id)) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息不存在'));
        }

        //查询这条任务是否为过期未审核任务
        $product = D('product')->where('id=' . $id)->find();
        M()->startTrans();
        try {
            $user = D('user')->where("uid={$product['user_id']}")->field('money,id')->find();
            if (!empty($user)) {
                if (time() > $product['endtime']) {
                    //吧这条任务改为过期未审核
                    $res = D('product')->where('id=' . $id)->setField('status', 4);
                    if (!$res) {
                        $this->ajaxReturn(array('status' => 0, 'msg' => '系统错误'));
                    }

                    $this->ajaxReturn(array('status' => 3, 'msg' => '任务过期未审核'));
                }
                if ($product['status'] != 0) {
                    $this->ajaxReturn(array('status' => 0, 'msg' => '信息错误'));
                }

                $time  = time();
                $array = array(
                    'status'    => 3,
                    'checktime' => $time, //审核通过时间
                );
                $res  = D('Product')->where('id=' . $id)->setField($array);
                $task = D('task')->where('gid=' . $id)->setField('status', 1);
                if (!$res || !$task) {
                    M()->rollback();
                    //修改失败
                    $this->ajaxReturn(array('status' => 0, 'msg' => '拒绝'));
                }
                M()->commit();
                $this->ajaxReturn(array('status' => 1, 'msg' => '审核通过'));
            } else {
                M()->rollback();
            }
        } catch (\Exception $e) {
            M()->rollback();
        }
        $this->ajaxReturn(array('status' => 0, 'msg' => '审核出错'));
    }

    //审核不通过
    /*
     * part1修改status字段 更改为2 代表不通过
     * part2把钱还给用户
     */
    public function jujue()
    {
        $id      = intval($_POST['id']); //商品id
        $reason  = trim(I('post.reason'));
        $product = D('product')->where('id=' . $id)->find();
        if ($product['status'] != 0) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息错误'));
        }

        //开启事务
        M()->startTrans();
        //part1修改status字段 更改为2 代表不通过

        //表示拒绝
        $array = array(
            'status' => 2,
            'reason' => $reason,
        );
        $res = D('Product')->where('id=' . $id)->setField($array);

        //part2把钱还给用户
        $user_id = $_POST['user_id']; //用户id
        //获取扣款金额
        $goods = D('Product')->field('goods_totalprice,goods_totalcost,empty_cost,goods_totalnum')->where('id=' . $id)->find();

        $money = $goods['goods_totalprice'];

        $cost = $goods['goods_totalcost'];

        //还款金额
        $total = $money + $cost + ($goods['goods_totalnum'] * $goods['empty_cost']);
        //将扣款返还给用户预付金
        $balances_status = save_available($user_id, $total, $id, 7, 2);
        if (!$balances_status) {
            M()->rollback();
            $this->ajaxReturn(array('status' => 0, 'msg' => '系统错误'));
        }
        $yufujinres      = D('user')->where('uid=' . $user_id)->setDec('yufujin', $total);
        $moneyres        = D('user')->where('uid=' . $user_id)->setInc('money', $total);
        if (!$yufujinres) {
            M()->rollback();
            $this->ajaxReturn(array('status' => 0, 'msg' => '系统错误'));
        }

        //删除任务列表
        $task = D('task')->where('gid=' . $id)->delete();
        if (!$res || !$task) {
            //修改不成功
            M()->rollback();
            $this->ajaxReturn(array('status' => 0, 'msg' => '系统错误2'));
        }

        M()->commit();
        $this->ajaxReturn(array('status' => 1, 'msg' => '操作成功'));
    }
//    public function taskprogress($id){
    //
    //        $goods_id  = urldecode(encrypt($id,'D'));
    //        $goodsInfo = D('ProductAttr')
    //                    ->where('goods_id='.$goods_id)
    //                    ->select();
    //        foreach ($goodsInfo as $key => $value){
    //            $goods_id=$value['goods_id'];
    //            $id=$value['id'];
    //            $where="gid={$goods_id} and tb_item is not null and sid={$id}";
    //
    //            $task_info=D('task')->where($where)->select();
    //
    //            $count=count($task_info);
    //            $goodsInfo[$key]['count']=$count;
    //            //已完成任务的费用
    //            $cost=cost($value['p'],$task_info['user_id'],$task_info['addtime']);
    //            $totalcost=f_round($cost * $count);
    //
    //            $goodsInfo[$key]['totalcost']=floatval($totalcost);
    //
    //
    //            $goodsInfo[$key]['count']=floatval($value['cost']);
    //
    //            $goodsInfo[$key]['p']=floatval($value['p']);
    //        }
    //        $this->assign('info',$goodsInfo);
    //
    //        $this->display();
    //    }

    //产品付款

    public function fukuan()
    {
        $id = encrypt(urldecode($_POST['id']), 'D');
        if (!is_numeric($id)) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息错误'));
        }

        $goods = D('product')->where('id=' . $id)->find();
        if (empty($goods)) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息错误'));
        }

        if ($goods['status'] != 1) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息错误'));
        }

        if ($goods['user_id'] != $_SESSION['user']['id']) {
            $this->ajaxReturn(array('status' => 0, 'msg' => '信息错误'));
        }

        $sun        = $goods['goods_totalprice'];
        $cost       = $goods['goods_totalcost'];
        $totalmoney = $sun + $cost + ($goods['empty_cost'] * $goods['goods_totalnum']);
        $user_id    = $_SESSION['user']['id'];
        //查询用户余额 $money

        //$sun=产品价格  $totalcost=总费用

        $shierdian = mktime(12, 00, 00, date("n"), date("d"), date("Y"));

        if (time() < $shierdian) {
            //在当天十二点前发布的产品，结束时间为当天23:59:59
            $data['addtime'] = time(); //当前时间  开始时间为当前
            $data['endtime'] = mktime(23, 59, 59, date("n"), date("d"), date("Y")); //结束时间为当天23:59:59

        } else {
            //在当天十二点之后发布的产品，结束时间为第二天23:59:59
            //86399 一天的时间戳
            $data['addtime'] = mktime(00, 00, 00, date("n"), date("d") + 1, date("Y")); //结束时间  第二天凌晨
            $data['endtime'] = $data['addtime'] + 86399;
        }

        $data['pub_time'] = time();
        $array            = array(
            'addtime' => $data['addtime'],
            'endtime' => $data['endtime'],
        );

        //开启事务
        M()->startTrans();
        D('task')->where('gid=' . $id)->setField($array);

        D('product')->where('id=' . $id)->save($data);
        $where = "user_id=" . $user_id . " and status =3 or status=0";
        $res   = D('product')->where($where)->order('pub_time')->select();
//        print_r($res);exit;
        //有发布
        $starttime = date('Ymd', $res[0]['pub_time']);
        //当前时间
        $now           = date('Ymd', time());
        $user_info     = D('user')->where(array('uid' => $user_id))->find();
        $credit_money  = $user_info['credit_money']; //透支额度
        $credit_status = $user_info['credit_status']; //申请透支额度状态
        if ($starttime == $now || empty($res)) {
            //判断今天的任务总金额
            $user_info = D('user')->where(array('uid' => $user_id))->find();
            // print_r($user_info);exit;
            // $xuyao = $user_info['yufujin'] + $totalmoney - $user_info['money'];
            $xuyao = $totalmoney - $user_info['money'];

            $credit_money = ($credit_status == 2) ? $credit_money : 1000;
            if ($xuyao > $credit_money) {
                //1000的信誉额度
                $cha  = $xuyao - $credit_money;
                $info = '余额不足,还需要' . $cha . '元';
                $this->ajaxReturn(array('status' => 0, 'msg' => $info));
            } else {
                $balances_status = save_available($_SESSION['user']['id'], $totalmoney, $id, 3, 1);
                if (!$balances_status) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 0, 'info' => '系统错误'));
                }
                $res             = D('user')->where('uid=' . $user_id)->setInc('yufujin', $totalmoney);
                $resm            = D('user')->where('uid=' . $user_id)->setDec('money', $totalmoney);
                $status          = D('product')->where('id=' . $id)->setField('status', 0);
                if (!$res || !$resm || !$status) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 0, 'info' => '系统错误'));
                }
                M()->commit();
                $this->ajaxReturn(array('status' => 1, 'msg' => '发布成功'));
            }
        } else {
            $result = $this->chajia($user_id, $totalmoney);
            if ($result > 0) {
                $info = '余额不足,还需要' . $result . '元';
                $this->ajaxReturn(array('status' => 0, 'msg' => $info));
            } else {
                $balances_status = save_available($_SESSION['user']['id'], $totalmoney, $id, 3, 1);
                if (!$balances_status) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 0, 'info' => '系统错误'));
                }
                $res             = D('user')->where('uid=' . $user_id)->setInc('yufujin', $totalmoney);
                $resm            = D('user')->where('uid=' . $user_id)->setDec('money', $totalmoney);
                $status          = D('product')->where('id=' . $id)->setField('status', 0);

                if (!$res || !$resm || !$status) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 0, 'info' => '系统错误'));
                }
                M()->commit();
                $this->ajaxReturn(array('status' => 1, 'msg' => '发布成功'));
            }
        }
    }

    //图片上传
    public function ajaxUpload()
    {

        $config = array(
            'maxSize'  => 10485760, //10M
            'rootPath' => './upload/',
            'savePath' => date("Ymd"),
            'saveName' => array('uniqid', ''),
            'exts'     => array('jpg', 'gif', 'png', 'jpeg'),
            'autoSub'  => true,
            'subName'  => array('', ''),
        );

        $upload    = new \Think\Upload($config); // 实例化上传类
        $info      = $upload->upload();
        $goods_pic = 'upload/' . $info['fileToUpload']['savepath'] . $info['fileToUpload']['savename'];
        $img       = new \Think\Image();
        //打开图片
        $img->open($goods_pic);
        //制作缩略图
        $goods_thumb = 'upload/' . $info['fileToUpload']['savepath'] . 'thumb_' . $info['fileToUpload']['savename'];

        $img->thumb(88, 88)->save($goods_thumb);
        if (!$info) {
            echo json_encode(array('status' => 2, 'msg' => $upload->getError()));
        } else {
            echo json_encode(array('status' => 1, 'msg' => '上传成功！', 'name' => $info['fileToUpload']['savename'], 'savepath' => $info['fileToUpload']['savepath'], 'thumb' => $goods_thumb));
        }
    }

    //查看订单
    public function order()
    {

        $id = intval(I('get.id'));

        $where = "sid={$id} and tb_item is not null";
        $task  = D('task a')
            ->field('a.*,b.qq')
            ->join('left join erp_account b on a.uid=b.id')
            ->where($where)
            ->select();

        $this->assign('data', $task);
        $this->display();
    }
    public function mytask()
    {
        if (IS_GET) {
            $ordernum = trim(I('get.ordernum'));
            $wangwang = trim(I('get.wangwang'));
            $goods_id = urldecode(encrypt(I('get.id'), 'D'));
            $endtime  = D('product')->where('id=' . $goods_id)->getField('endtime');
            $user_id  = D('product')->where('id=' . $goods_id)->getField('user_id');
            $shop_id  = D('product')->where('id=' . $goods_id)->getField('shop_id');
            if ($endtime < time()) {
                $this->assign('finish', 'finish');
            }
            $where = "a.gid={$goods_id} ";
            if (!empty($wangwang)) {
                $where .= " and a.wangwang='$wangwang'";
            }

            if (!empty($ordernum)) {
                $where .= " and a.tb_item='$ordernum'";
            }

            $now = time() - 2592000; // 2592000一个月的秒
            //任务发布时间
            $pub_time = D('product')->where(array('id' => $goods_id))->getField('pub_time');
            $data     = D('task a')
                ->field("a.*,b.qq,b.realname,if(a.empty_cost > 0 ,a.empty_cost,a.redbag) as empty_cost,c.eval_pic,c.remark,c.eval_status,c.del,if(a.addtime < UNIX_TIMESTAMP() - 2592000, 1 , 2 ) as overdue")
                ->join('left join erp_account b on a.uid=b.id')
                ->join('left join erp_task_eval c on a.id=c.task_id')
                ->where($where)
                ->order('a.edittime desc')
                ->select();
//            $total=D('task')->where('gid='.$goods_id.' and tb_item is not null')->count();
            //
            //            //查询填过评价的任务
            //            $eval =D('task_eval')->where('goods_id='.$goods_id)->count();
            //
            //            //取10%  可上传图的数量
            //            //$allow=intval(floor($total * 0.1)-$eval);
            //评价任务 取完成的任务总量的1/4
            $total = D('task')->where('shop_id=' . $shop_id . ' and addtime >= 1528560000 and tb_item is not null and (abn_status != 4 or abn_status is null)')->count();
            $eval  = D('task_eval')->where('shop_id=' . $shop_id)->count();
            //取1/10
            $allow = intval(floor($total * 0.1) - $eval);
//            foreach ( $data as $key => $value){
            //                $data[$key]['cost']= cost($value['actual_price'],$value['user_id'],$pub_time);
            //            }

            //查询今天有没有发布过任务
            //            $where_t='shop_id='.$shop_id.' and uid is not null and addtime <='.time().' and endtime >='.time();
            //            $count_t=D('task')->where($where_t)->count();
            //            $time=strtotime(date('Y-m-d 00:00:00',time()));
            //            $eval_now=D('task_eval a')->where('a.addtime >= '.$time.' and a.shop_id='.$shop_id)->count();

            $this->assign('goods_id', $goods_id);
            $this->assign('ordernum', $ordernum);
            $this->assign('data', $data);
            $this->assign('allow', $allow);
//            $this->assign('eval_now',$eval_now);
            //            $this->assign('count_t',$count_t);
            $this->assign('wangwang', $wangwang);
            $this->display();
        }

    }
    public function orderdetail()
    {
        //判断当前权限
        if (check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            //所有的站长名字
            $zz = D('account')->field('id,realname')->where('role=3 and msg=0')->select(); //站长
            $yw = D('account')->field('id,info')->where('role=6 and msg=0')->select(); //业务员
            $this->assign('zz', $zz);
            $this->assign('yw', $yw);
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);
            $ordernum  = trim(I('get.ordernum')); //订单
            $wangwang  = trim(I('get.wangwang')); //旺旺
            $starttime = I('get.time'); //开始时间
            $endtime   = I('get.endtime'); //结束时间
            $shopname  = trim(I('get.shopname')); //店铺名
            $sjname    = trim(I('get.sjname'));
            $type      = I('get.type');
            $choicezz  = I('get.choicezz', 0, 'intval');
            $choiceyw  = I('get.choiceyw', 0, 'intval');
            //价格筛选
            $choicepr = I('get.choicepr', 0, 'intval');
            if ($_SESSION['user']['role'] == 5) {
                //商家
                $where = 'a.user_id=' . $_SESSION['user']['id'] . " and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 6) {
                //业务员
                $where = "a.tid={$_SESSION['user']['id']} and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 4) {
                //管理员
                $where = "a.tb_item is not null ";
                if (!empty($choicezz)) {
                    $where .= " and d.user_id=" . $choicezz;
                }

                if (!empty($choiceyw)) {
                    $where .= " and a.tid=" . $choiceyw;
                }

            }
            /********************************************/

            if (!empty($starttime)) {
                $starttime = strtotime($starttime . "00:00:00");
                $where .= " and a.addtime >= {$starttime}";
            }
            if (!empty($endtime)) {
                $endtime = strtotime($endtime . "23:59:59");
                $where .= " and a.addtime <= {$endtime}";
            }
            if ($type != "all" && (empty($ordernum) && empty($wangwang))) {
                $time      = timeInterval(time());
                $starttime = $time['starttime'];
                $endtime   = $time['endtime'];
                $where .= " and a.addtime  between {$starttime} and {$endtime}";
            }

            if (!empty($wangwang)) {
                $where .= " and a.wangwang='$wangwang'";
            }

            if (!empty($ordernum)) {
                $where .= " and a.tb_item='$ordernum'";
            }

            if (!empty($shopname)) {
                $where .= " and c.shopname like '%$shopname%'";
            }

            if (!empty($sjname)) {
                $sid = D('user')->field('uid')->where("shopname like '%$sjname%'")->select();
                // if ($_SESSION['user']['id'] == 1) {
                //     echo $sid;exit;
                // }
//                 $where .= " and e.shopname like '%$sjname%'";
                if ($sid) {
                    $whereid = '';
                    foreach($sid as $tid){
                        $whereid.= $tid['uid'].',';
                    }
                    $where .= " and b.user_id in(".substr($whereid, 0, -1).")";
                }
            }

            switch ($choicepr) {
                // 1 100以下 2 100 - 200 3 200 - 300 4 300-400 5 400以上
                case 1:
                    $where .= " and a.actual_price < 100";
                    break;
                case 2:
                    $where .= " and a.actual_price >= 100 and a.actual_price < 200";
                    break;
                case 3:
                    $where .= " and a.actual_price >= 200 and a.actual_price < 300";
                    break;
                case 4:
                    $where .= " and a.actual_price >= 300 and a.actual_price < 400";
                    break;
                case 5:
                    $where .= " and a.actual_price >= 400 ";
                    break;
            }

            $count = D('task a')
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on a.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->join('left join erp_user e on a.user_id=e.uid')
                ->where($where)->count();
                if ($_SESSION['user']['id'] == 1) {
                    // echo M()->getLastSql();exit;
                }

            /*************************************************/
            $task = D('task')
                ->alias('a')
                ->field("a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,b.goods_pic,c.shopname,d.realname as sd_realname,d.wechat as sd_wechat,if(a.empty_cost>0,a.empty_cost,a.redbag) as empty_cost,e.shopname as sjname,f.realname as zz_realname,if(a.endtime < UNIX_TIMESTAMP(),'finish',null) as finish,g.remark,g.del,d.phone as sd_phone")
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on a.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->join('left join erp_user e on a.user_id=e.uid')
                ->join('left join erp_account f on d.user_id=f.id')
                ->join('left join erp_task_eval g on a.id=g.task_id')
                ->where($where)
                ->order('a.edittime desc')
                ->limit($page * $page_size, $page_size)
                ->select();
//            foreach ($task as $key => $value){
            //                $wechat=json_decode($value['sd_wechat'],true);
            //                $task[$key]['sd_wechat'] = $wechat[0]['num'];
            //            }
            $field = 'sum(a.cost)as comm';
            $field .= ',sum(IFNULL(if(a.actual_price,a.actual_price,a.price),0)) as capital,sum(IFNULL(if(a.empty_cost,a.empty_cost,a.redbag),0)) as other';
            $taska = D('task')
                ->alias('a')
                ->field($field) //'a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,goods_pic,c.shopname'
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on a.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->join('left join erp_user e on a.user_id=e.uid')
                ->where($where)
                ->select();
            $money   = $capital   = $comm   = $other   = 0;
            $capital = $taska[0]['capital'];
            $comm    = $taska[0]['comm'];
            $other   = $taska[0]['other'];

            $money = $capital + $comm + $other;

            $ret_count = D('task a')
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on a.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->join('left join erp_user e on a.user_id=e.uid')
                ->where($where . ' and a.abn=2 and a.abn_status=4')
                ->count();
            $taskr = D('task')
                ->alias('a')
                ->field($field) //'a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,goods_pic,c.shopname'
                ->join('left join erp_product b on a.gid=b.id')
                ->join('left join erp_shop c on a.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->join('left join erp_user e on a.user_id=e.uid')
                ->where($where . ' and a.abn=2 and a.abn_status=4')
                ->select();

            $ret['capital'] = $taskr[0]['capital'];
            $ret['comm']    = $taskr[0]['comm'];
            $ret['other']   = $taskr[0]['other'];

            $ret['money'] = $ret['capital'] + $ret['comm'] + $ret['other'];

            $this->assign('ret', $ret);

            if ($starttime) {
                $this->assign('starttime', date("Y-m-d", $starttime));
            }

            if ($endtime) {
                $this->assign('endtime', date("Y-m-d", $endtime));
            }

            $this->assign('wangwang', $wangwang);
            $this->assign('ordernum', $ordernum);
            $this->assign('shopname', $shopname);
            $this->assign('sjname', $sjname);
            $this->assign('money', $money);
            $this->assign('capital', $capital);
            $this->assign('comm', $comm);
            $this->assign('other', $other);
            $this->assign('ret_count', $ret_count);
            $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count));
            $this->assign('task', $task);
            $this->assign('count', $count);
            $this->assign('choicepr', $choicepr);
            $this->display();
        }

    }

    public function userdetail()
    {
        $id = intval(I('post.id'));
        if (empty($id)) {
            $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
        }

        $info = D('user')->field('iphone,wangwang,qq')->where('uid=' . $id)->find();
        $this->ajaxReturn(array('msg' => 1, 'data' => $info));
    }

    //判断余额是否充足，充足才可以发布任务
    //    public function isAdd()
    //    {
    //        $time=time();   //当前时间
    //        //当天22:00后禁止发布产品
    //
    //        //查询是否第一次发布产品
    //        $user_info=D('user')->field('firstadd,money')->where('uid='.$_SESSION['user']['id'])->find();
    //        $firstadd=$user_info['firstadd'];
    //        $secondadd=$user_info['secondadd'];
    //        //用于是否是第一次发布产品  可以进去
    //        if($firstadd == 0){
    ////            $money=$user_info['money'];
    ////            $msg=array(
    ////                'msg'=>1,
    ////                'money'=>$money,
    ////            );
    //            $this->ajaxReturn(array('msg'=>1,'info'=>'success'));
    //        }
    //        //第一次添加产品已过 审核中的产品，第二次进去不让进去
    //        if($firstadd == 1){
    //
    //            $this->ajaxReturn(array('msg'=>0,'info'=>'任务审核中'));
    //        }
    //        $this->ajaxReturn(array('msg'=>1,'info'=>'success'));
    //        //如果审核通过就可以判断余额
    //        //判断余额
    //
    //        //待定
    ////        $user_id=$_SESSION['user']['id'];
    //        //查询最近的结束时间  结束时间为前一天的晚上
    ////        $product=D('Product')->field('endtime')->where('user_id='.$user_id)->order('endtime desc')->limit(1)->find();
    ////        $endtime=$product['endtime'];
    ////        if($time > $endtime){
    ////            //判断为第二天
    ////            $user_info=D('user')->where('uid='.$user_id)->field('money')->find();
    ////            $money=$user_info['money'];
    ////            if( $money < 0 ){
    ////                //有欠款，必须补齐欠款
    ////                $this->ajaxReturn(array('msg'=>0,'info'=>'余额不足，至少充值'.abs($money).'元'));
    ////            }
    ////        }
    ////
    ////        $user_info=D('user')->where('uid='.$user_id)->field('money')->find();
    ////
    ////        D('Product')->where('user_id='.$user_id)->find();
    ////        if( $money < 0 ){
    ////            //有欠款，必须补齐欠款
    ////            $this->ajaxReturn(array('msg'=>0,'info'=>'余额不足，至少充值'.abs($money).'元'));
    ////        }
    ////
    ////        $this->ajaxReturn(array('msg'=>1,'info'=>'success'));
    //
    //    }
    ////    public function checkstatus(){
    ////
    ////        $total=I('post.total');
    ////        $user_info=D('user')->field('firstadd,money')->where('uid='.$_SESSION['user']['id'])->find();
    ////        $firstadd=$user_info['firstadd'];
    ////        $money=$user_info['money'];
    ////        //用于是否是第一次发布产品  firstadd 为0的时候代表是第一次，否则不是
    ////
    ////        if($money < 3000){
    ////            if($firstadd == 0){
    ////
    ////
    ////                if($total > 3000){
    ////                    $this->ajaxReturn(array('msg'=>0,'info'=>$money));
    ////
    ////
    ////                }else{
    ////                    D('user')->where('uid='.$_SESSION['user']['id'])->setInc('yufujin',$total);
    ////
    ////                    $this->ajaxReturn(array('msg'=>3,'info'=>'success'));
    ////                }
    ////
    ////            }
    ////        }else{
    ////            if($firstadd == 0){
    ////                if($money < $total){
    ////                    $this->ajaxReturn(array('msg'=>0,'info'=>$money));
    ////                }else{
    ////                    D('user')->where('uid='.$_SESSION['user']['id'])->setInc('yufujin',$total);
    ////                }
    ////            }
    ////        }
    ////        $this->ajaxReturn(array('msg'=>1,'info'=>'success'));
    ////    }

    //订单异常处理
    public function abn()
    {
        if ($_SESSION['user']['role'] != 5) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            $id      = I('get.id', 0, 'intval');
            $addtime = D('task')->where('id=' . $id)->getField('addtime');
            $now     = time() - 2592000; // 2592000一个月的秒
            //            if($addtime < $now){
            //
            //                $this->error('任务过期一个月，无法操作','javascript:parent.location.reload();');
            //                exit;
            //            }
            $this->display();
        } elseif (IS_POST) {
            $img = I('post.img');
            if (empty($img)) {
                $this->error('图片必须上传！');
                exit;
            }
            $data = array(
                'abn'         => I('post.abn', 0, 'intval'),
                'abn_info'    => trim(I('post.abn_info')),
                'img'         => 'upload/' . date("Ymd", time()) . '/' . trim(I('post.img')),
                'errortime'   => time(),
                'abn_status'  => 0,
                'successtime' => null,
                'reason'      => null,

            );

            if (empty($data['abn_info'])) {
                $this->error('理由必须填写');
                exit;
            }
            $id  = I('post.id', 0, 'intval');
            $res = D('task')->where('id=' . $id)->setField($data);
            if ($res) {
                $this->success('提交成功', 'javascript:parent.location.reload();');
            } else {
                $this->error('提交失败');
            }
        }

    }
    //异常订单撤回
    public function revoke()
    {
        if ($_SESSION['user']['role'] != 5) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '无此权限'));
        }

        $id = I('post.id', 0, 'intval');
        if (!$id) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        $abn_status = D('task')->where('id=' . $id)->getField('abn_status');
        if ($abn_status == 4) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '已经退款'));
        }

        $data = array(
            'abn'         => null,
            'abn_info'    => null,
            'img'         => null,
            'errortime'   => null,
            'abn_status'  => null,
            'successtime' => null,
            'reason'      => null,
        );
        $task = D('task')->where('id=' . $id)->find();
        unlink($task['img']);
        $res = D('task')->where('id=' . $id)->setField($data);
        if ($res) {
            $this->ajaxReturn(array('msg' => 1, 'info' => '撤销成功'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => '撤销失败'));
        }
    }
    //异常信息查看
    public function abninfo()
    {
        $id   = I('get.id', 0, 'intval');
        $info = D('task')->field('abn,abn_info,img')->where('id=' . $id)->find();

        $this->assign('info', $info);
        $this->display();
    }
    //站长审核
    public function abnCheck()
    {
        $id  = I('post.id', 0, 'intval');
        $val = I('post.val', 0, 'intval');
        //1为已经返回，2位未返回
        if (empty($id) || empty($val)) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        //通过
        $abn_status = D('task')->where(array('id' => $id))->getField('abn_status');
        if ($abn_status != 0) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
        }

        if ($val == 2) {
            $array = array('abn_status' => 1, 'repay' => $val);
        } elseif ($val == 1) {
            $array = array('abn_status' => 1, 'repay' => $val);
        }

        $res = D('task')->where('id=' . $id)->setField($array);
        if ($res) {
            $this->ajaxReturn(array('msg' => 1, 'info' => '审核通过'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
        }

    }
    //财务审核
    public function financeCheck()
    {
        $gid = I('post.id', 0, 'intval');
        //通过
        if (empty($gid)) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        $status = M('task')->where('id=' . $gid)->getField('abn_status');
        if ($status == 4) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '退款已完成，请勿重复提交'));
        }

        if ($status != 1) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
        }

        $task    = D('task')->where('id=' . $gid)->find();
        $user_id = $task['user_id']; //商家id
        $id      = $task['uid']; //刷单员id
        $price   = $task['actual_price']; //实际输入价格
        $repay   = $task['repay'];
        if (!$user_id) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '商家信息不存在'));
        }

        if (!$id) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '刷单员信息不存在'));
        }

        if (!$repay) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '刷单员返款出错'));
        }

        //通过   退费 退本金+费用+空包费
        //开启事务
        M()->startTrans();
        $pub_time = D('product')->where(array('id' => $task['gid']))->getField('pub_time');
        $money    = cost($price, $user_id, $pub_time) + $price + $task['empty_cost'];


        $balances_status = save_available($user_id, $money, $gid, 4, 2);
        if (!$balances_status) {
            M()->rollback();
            $this->ajaxReturn(array('msg' => 1, 'info' => '退款失败'));
        }

        $res             = D('user')->where('uid=' . $user_id)->setInc('money', $money);
        $cost            = $price;
        if(!$res) {
            M()->rollback();
            $this->error('退款失败');
            exit;
        }

        if ($repay == 1) {
            //已经返款 刷单员钱退回
            $result = D('Account')->where('id=' . $id)->setInc('money', $cost);
            if (!$result) {
                M()->rollback();
                $this->ajaxReturn(array('msg' => 0, 'info' => '退款失败'));
            }

        }
        $array = array(
            'successtime' => time(),
            'abn_status'  => 4,
        );
        $info = D('task')->where('id=' . $gid)->setField($array);
        if(!$info) {
            M()->rollback();
            $this->error('退款失败');
            exit;
        }
        M()->commit();
        $this->ajaxReturn(array('msg' => 0, 'info' => '退款失败'));
    }
    public function reject()
    {
        //驳回申请
        $id     = I('post.id', 0, 'intval');
        $reason = trim(I('post.reason'));

        if (empty($id)) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        if ($_SESSION['user']['role'] == 2) {
            $data = array(
//            'abn'         => null,
                //            'abn_info'    => null,
                //            'img'         => null,
                'errortime'   => null,
                'abn_status'  => 2,
                'successtime' => null,
                'reason'      => $reason,
            );
        } elseif ($_SESSION['user']['role'] == 4) {
            $data = array(
//            'abn'         => null,
                //            'abn_info'    => null,
                //            'img'         => null,
                'errortime'   => null,
                'abn_status'  => 3,
                'successtime' => null,
                'reason'      => $reason,
            );
        }

        $res = D('task')->where('id=' . $id)->setField($data);
        if ($res) {
            $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => '系统异常'));
        }
    }

    //产品下架
    public function xiajia()
    {
        $id = I('post.id', 0, 'intval');
        if (empty($id)) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        $task = D('task')->field('uid')->where('id=' . $id)->find();
        if (!empty($task['uid'])) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '任务已在进行中,请联系导师处理'));
        }

        $array = array(
            'xiajia'       => 1,
            'xiajiareason' => null,
        );
        $res = D('task')->where('id=' . $id)->setField($array); //申请下架
        if ($res) {
            $this->ajaxReturn(array('msg' => 1, 'info' => '申请成功'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
        }
    }
    //下架审核
    public function check_undercarriage()
    {
        $id = I('post.id', 0, 'intval');

        if (empty($id)) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        $task = D('task')->where('id=' . $id)->find();
        if ($task['xiajia'] != 1) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
        }

        //开启事务
        M()->startTrans();

        $res  = D('task')->where('id=' . $id)->setField('xiajia', 2); //同意下架
        $info = D('task')->field('user_id,price,empty_cost,cost')->where('id=' . $id)->find();
        //预付金减少
        $money           = $info['price'] + $info['cost'] + $info['empty_cost']; //商品价格加上费用
        $balances_status = save_available($info['user_id'], $money, $id, 5, 2);
        $yufujinres      = D('user')->where('   uid=' . $info['user_id'])->setDec('yufujin', $money);
        $moneyres        = D('user')->where('uid=' . $info['user_id'])->setInc('money', $money);
        if ($balances_status && $res && $moneyres && $yufujinres) {
            M()->commit();
            $this->ajaxReturn(array('msg' => 1, 'info' => '同意下架'));
        }
        M()->rollback();
        $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
    }
    //拒绝下架
    public function jujuexiajia()
    {
        $id     = I('post.id', 0, 'intval');
        $reason = trim(I('post.reason'));
        if (empty($id)) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息不存在'));
        }

        $task = D('task')->where('id=' . $id)->find();
        if ($task['xiajia'] != 1) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
        }

        $array = array(
            'xiajiareason' => $reason,
            'xiajia'       => 0,
        );
        $res = D('task')->where('id=' . $id)->setField($array);
        if ($res) {
            $this->ajaxReturn(array('msg' => 1, 'info' => 'success'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => 'error'));
        }
    }

    public function beizhu()
    {
        if (IS_AJAX) {
            $id     = I('post.task_id', 0, 'intval');
            $beizhu = trim(I('post.beizhu'));
            if (!$beizhu) {
                $this->ajaxReturn(array());
            }

            D('ProductAttr')->where(array('id' => $id))->setField('s', $beizhu);
            D('task')->where(array('sid' => $id))->setField('sku', $beizhu);
        }

    }

    //下载excel   商家下载 下载某个任务
    public function excel()
    {
        $id = intval(I('get.id', 0, 'intval'));
        if (empty($id)) {
            $this->error('数据错误');
            exit;
        }
        $where = "gid=" . $id . " and tb_item is not null";

        $task = D('task a')->where($where)->field('FROM_UNIXTIME(a.edittime) as time,a.keyword,a.price,a.actual_price,a.tb_item,a.wangwang,if(a.empty_cost>0,a.empty_cost,a.redbag) as empty_cost,a.cost')->select();
//        dump($task);exit;
        //        foreach ($task as $key =>$value){
        //            $task[$key]['cost']=cost($value['actual_price']);
        //            $task[$key]['edittime']=date('Y-m-d H:i:s',$value['edittime']);
        //        }

        $title    = array('完成时间', '关键字', '单价', '下单价', '订单号', '旺旺号', '其他', '服务费');
        $filename = '订单详情';
        $this->down($title, $task, $filename);

    }

    //订单详情下载
    public function down_excel()
    {
        $ordernum  = trim(I('get.ordernum'));
        $wangwang  = trim(I('get.wangwang'));
        $starttime = I('get.time');
        $endtime   = I('get.endtime');
        $shopname  = trim(I('get.shopname'));
        $choicezz  = I('get.choicezz', 0, 'intval');
        $choiceyw  = I('get.choiceyw', 0, 'intval');
        $type      = I('get.type');
        if (empty($starttime)) {
            $this->error('搜索开始时间必须选择！');
            exit;
        }
        //搜索下载日期，如果结束日期存在，取结束日期，不存在取当天
        $endtime_s = $endtime ? strtotime($endtime . "23:59:59") : strtotime(date('Y-m-d 23:59:59', time()));

        $time_difference = intval(ceil(($endtime_s - strtotime($starttime . "00:00:00")) / (60 * 60 * 24))); //天数
        if ($time_difference > 30) {
            $this->error('下载表格的时间区间不得超过30天！');
            exit;
        }

        if ($_SESSION['user']['role'] == 5) {
            //商家
            $where = 'a.user_id=' . $_SESSION['user']['id'] . " and a.tb_item is not null ";
        } elseif ($_SESSION['user']['role'] == 6) {
            //业务员
            $where = "a.tid={$_SESSION['user']['id']} and a.tb_item is not null ";
        } elseif ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 4) {
            //管理员
            $where = "a.tb_item is not null ";
            if (!empty($choicezz)) {
                $where .= " and d.user_id=" . $choicezz;
            }

            if (!empty($choiceyw)) {
                $where .= " and a.tid=" . $choiceyw;
            }

        }
        /********************************************/

        if (!empty($starttime)) {
            $starttime = strtotime($starttime . "00:00:00");
            $where .= " and a.addtime >= {$starttime}";
        }
        if (!empty($endtime)) {
            $endtime = strtotime($endtime . "23:59:59");
            $where .= " and a.addtime <= {$endtime}";
        }
        if ($type != "all" && (empty($ordernum) && empty($wangwang))) {
            $time      = timeInterval(time());
            $starttime = $time['starttime'];
            $endtime   = $time['endtime'];
            $where .= " and a.addtime  between {$starttime} and {$endtime}";
        }
        if (!empty($wangwang)) {
            $where .= " and a.wangwang='$wangwang'";
        }

        if (!empty($ordernum)) {
            $where .= " and a.tb_item='$ordernum'";
        }

        if (!empty($shopname)) {
            $where .= " and c.shopname like '%$shopname%'";
        }

        if (!empty($choicezz)) {
            $where .= " and d.user_id=" . $choicezz;
        }

        if (!empty($choiceyw)) {
            $where .= " and a.tid=" . $choiceyw;
        }

        /*************************************************/
        $task = D('task')
            ->alias('a')
            ->field('FROM_UNIXTIME(a.edittime) as time,c.shopname,a.keyword,a.price,a.actual_price,a.cost,if(a.empty_cost>0,a.empty_cost,a.redbag) as empty_cost,a.tb_item,a.wangwang')
            ->join('left join erp_product b on a.gid=b.id')
            ->join('left join erp_shop c on a.shop_id=c.id')
            ->join('left join erp_account d on a.uid=d.id')
            ->where($where)
            ->select();

//        foreach ( $task as $key =>$value){
        //            $task[$key]['edittime']=date('Y-m-d H:i:s',$value['edittime']);
        //            $task[$key]['cost']=cost($value['actual_price']);
        //        }

        $title    = array('完成时间', '店铺名', '关键字', '单价', '下单价', '服务费', '其他', '订单编号', '旺旺号');
        $filename = '订单详情';
        $this->down($title, $task, $filename);
    }

    //查看退款单
    public function refundorder()
    {
        //判断当前权限
        if (check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);

            $status = I('get.status');
//            $status_a = I('get.status_a');
            $zz = D('account')->field('id,realname')->where('role = 3')->select(); //站长
            $this->assign('zz', $zz);
            $yw = D('account')->field('id,info')->where('role = 6')->select();
            $this->assign('yw', $yw);
            $shopname = trim(I('get.shopname'));
            $ordernum = trim(I('get.ordernum'));
            $wangwang = trim(I('get.wangwang'));
            $choicezz = I('get.choicezz', 0, 'intval');
            $choiceyw = I('get.choiceyw', 0, 'intval');

            $search_time = I('get.search_time');
            $starttime   = I('get.time');
            $endtime     = I('get.endtime');
            if ($_SESSION['user']['role'] == 5) {
                //商家
                $where = 'a.user_id=' . $_SESSION['user']['id'] . " and a.abn=2 and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 6) {
                //业务员
                $where = "a.tid={$_SESSION['user']['id']} and a.abn=2 and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 2) {
                //刷单员
                $where = "a.uid={$_SESSION['user']['id']} and a.abn=2 and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 3) {
                //站长
                $user_id = M('account')->where('user_id=' . $_SESSION['user']['id'])->field('id')->select();
                if (empty($user_id)) {
                    $this->display();
                    exit;
                }
                foreach ($user_id as $val) {
                    $id_arr[] = $val['id'];
                }

                $id    = implode(",", $id_arr);
                $where = "a.uid in({$id}) and a.abn=2 and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 1) {
                //管理员
                $where = "a.tb_item is not null and a.abn=2 ";

            } elseif ($_SESSION['user']['role'] == 4) {
                //财务
                $where = "a.tb_item is not null and a.abn=2 ";
            }

//            if(!in_array($status,array(1,2,3)))  $status=4;

            if ($status != 4) {
                //1代表已拒绝 2代表已返款 3代表待审核 0代表全部
                switch ($status) {
                    case 1:
                        if ($_SESSION['user']['role'] == 4) {
                            $where .= ' and a.abn_status = 3';
                        } else {
                            $where .= " and (a.abn_status = 2 or a.abn_status = 3)";
                        }
                        break;
                    case 2:
                        $where .= " and a.abn_status = 4";
                        break;
                    case 3:
                        if ($_SESSION['user']['role'] == 4) {
                            $where .= ' and a.abn_status = 1';
                        } else {
                            $where .= " and (a.abn_status = 0 or a.abn_status = 1)";
                        }
                        break;
                }
            }
            if (empty($status) && $_SESSION['user']['role'] == 4) {
                //待审核
                $where .= ' and a.abn_status = 1';

            }
            if (!empty($starttime)) {
                $starttime = strtotime($starttime . "00:00:00");
                if ($search_time == 2) {
                    $where .= " and a.successtime >= {$starttime}";
                } elseif ($search_time == 1) {
                    $where .= " and a.addtime >= {$starttime}";
                }

            }
            if (!empty($endtime)) {
                $endtime = strtotime($endtime . "23:59:59");

                if ($search_time == 2) {
                    $where .= " and a.successtime <= {$endtime}";
                } elseif ($search_time == 1) {
                    $where .= " and a.addtime <= {$endtime}";
                }

            }
            if (!empty($wangwang)) {
                $where .= " and a.wangwang like '%$wangwang%'";
            }

            if (!empty($ordernum)) {
                $where .= " and a.tb_item='$ordernum'";
            }

            if (!empty($shopname)) {
                $where .= " and c.shopname like '%$shopname%'";
            }

            if (!empty($choicezz)) {
                $where .= " and d.user_id=" . $choicezz;
            }

            if (!empty($choiceyw)) {
                $where .= " and a.tid=" . $choiceyw;
            }

            $count = D('task a')
                ->field('a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,goods_pic,d.realname as realname_s,c.shopname,e.realname,f.info as f_info')
                ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                ->join('left join erp_account f on a.tid=f.id')
                ->where($where)
                ->count();

            $task = D('task a')
                ->field('a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,goods_pic,d.realname as realname_s,d.phone as sd_phone,c.shopname,e.realname,f.info as f_info,IFNULL(if(a.empty_cost>0,a.empty_cost,a.redbag),0) as order_cost')
                ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                ->join('left join erp_account f on a.tid=f.id')
                ->where($where)
                ->order('a.edittime desc')
                ->limit($page * $page_size, $page_size)
                ->select();
            if ($_SESSION['user']['role'] == 4 || $_SESSION['user']['role'] == 1) {
                //总退款
                $taska = D('task a')
                    ->field('sum(IFNULL(a.actual_price,0)) as totalprice,sum(IFNULL(a.redbag,0)) as totalredbag,sum(IFNULL(a.commision,0)) as totalcommision')
                    ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                    ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                    ->join('left join erp_account f on a.tid=f.id')
                    ->where($where . " and a.repay = 1")
                    ->select();

                $totalprice     = f_round($taska[0]['totalprice']);
                $totalredbag    = f_round($taska[0]['totalredbag']);
                $totalcommision = f_round($taska[0]['totalcommision']);
                $total          = f_round($totalprice + $totalredbag + $totalcommision);
                //已经追回
                $taskb = D('task a')
                    ->field('sum(IFNULL(a.actual_price,0)) as totalprice,sum(IFNULL(a.redbag,0)) as totalredbag,sum(IFNULL(a.commision,0)) as totalcommision')
                    ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                    ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                    ->join('left join erp_account f on a.tid=f.id')
                    ->where($where)
                    ->select();
                $total_price     = f_round($taskb[0]['totalprice']);
                $total_redbag    = f_round($taskb[0]['totalredbag']);
                $total_commision = f_round($taskb[0]['totalcommision']);
                $total_a         = f_round($total_price + $total_redbag + $total_commision);
            } elseif ($_SESSION['user']['role'] == 5) {

                $taska = D('task a')
                    ->field('sum(IFNULL(a.actual_price,0)) as totalprice,sum(a.cost) as cost,sum(IFNULL(if(a.empty_cost>0,a.empty_cost,a.redbag),0)) as order_cost')
                    ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                    ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                    ->join('left join erp_account f on a.tid=f.id')
                    ->where($where)
                    ->select();
                $totalprice = f_round($taska[0]['totalprice']);
                $totalcost  = f_round($taska[0]['cost']);
                $totalorder = f_round($taska[0]['order_cost']);
                $total      = f_round($totalprice + $totalcost + $totalorder);

            }

            $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count));
            $this->assign('shopname', $shopname);
            $this->assign('ordernum', $ordernum);
            $this->assign('wangwang', $wangwang);
            $this->assign('search_time', $search_time); //时间搜索
            $this->assign('totalprice', $totalprice); //已追回总价格
            $this->assign('totalredbag', $totalredbag); //已追回总红包费用
            $this->assign('totalcommision', $totalcommision); //已追回总佣金
            $this->assign('total', $total); //已追回总费用
            $this->assign('total_price', $total_price); //总价格
            $this->assign('total_redbag', $total_redbag); //总红包费用
            $this->assign('total_commision', $total_commision); //总佣金
            $this->assign('total_a', $total_a); //已追回总费用
            //商家显示
            $this->assign('totalcost', $totalcost); //商家看到总服务费
            $this->assign('totalorder', $totalorder); //总其他费用。有空包为空包，无空包为红包

            if ($starttime) {
                $this->assign('starttime', date("Y-m-d", $starttime));
            }

            if ($endtime) {
                $this->assign('endtime', date("Y-m-d", $endtime));
            }

            $this->assign('task', $task);
            //$this->assign('status_a',$status_a);
            $this->assign('status', $status);
            $this->assign('count', $count);
            $this->display();
        }

    }
    //数据汇总
    public function datatotal()
    {
        //判断当前权限
        if (check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        $l_price = 300;
        $s_price = 100;
        if (IS_GET) {
            $yewu = D('account')->field('id,info')->where('role=6')->select();
            $this->assign('yewu', $yewu);
            $str = ' and tid=' . intval($_SESSION['user']['id']);

            $time = time();
            //小额单
            $where = 'status=1 and xiajia=0 and price<' . $s_price . ' and addtime <' . time() . ' and endtime > ' . time();
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_s = M('task')->where($where)->count();
            $where   = 'status=1 and xiajia=0 and price<' . $s_price . ' and addtime <' . time() . ' and endtime > ' . time() . ' and uid is not null';
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_ys = M('task')->where($where)->count();
            $where    = 'status=1 and xiajia=0 and price<' . $s_price . ' and addtime <' . time() . ' and endtime > ' . time() . ' and tb_item is not null';
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_yso = M('task')->where($where)->count();
            //end
            //大额单
            $where = 'status=1 and xiajia=0 and price>=' . $l_price . ' and addtime <' . time() . ' and endtime > ' . time();
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_l = M('task')->where($where)->count();
            $where   = 'status=1 and xiajia=0 and price>=' . $l_price . ' and addtime <' . time() . ' and endtime > ' . time() . ' and uid is not null';
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_yl = M('task')->where($where)->count();
            $where    = 'status=1 and xiajia=0 and price>=' . $l_price . ' and addtime <' . time() . ' and endtime > ' . time() . ' and tb_item is not null';
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_ylo = M('task')->where($where)->count();
            //end
            //中额单
            $where = 'status=1 and xiajia=0 and price>=' . $s_price . ' and price <' . $l_price . ' and addtime <' . time() . ' and endtime > ' . time();
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_m = M('task')->where($where)->count();
            $where   = 'status=1 and xiajia=0 and price>=' . $s_price . ' and price <' . $l_price . ' and addtime <' . time() . ' and endtime > ' . time() . ' and uid is not null';
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_ym = M('task')->where($where)->count();
            $where    = 'status=1 and xiajia=0 and price>=' . $s_price . ' and price <' . $l_price . ' and addtime <' . time() . ' and endtime > ' . time() . ' and tb_item is not null';
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_ymo = M('task')->where($where)->count();
            //end
            //总量
            $where = 'status=1 and xiajia=0 and addtime <' . time() . ' and endtime > ' . time();
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count = M('task')->where($where)->count();
            $where = 'status=1 and xiajia=0 and uid is not null and addtime <' . time() . ' and endtime > ' . time();
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_ya = M('task')->where($where)->count();
            $where    = 'status=1 and xiajia=0 and tb_item is not null and addtime <' . time() . ' and endtime > ' . time();
            if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
            $count_yao = M('task')->where($where)->count();
            //end
            //已领取量
            $this->assign('get_percentum', f_round($count_ya * 100 / $count));
            //已完成量
            $this->assign('finished_percentum', f_round($count_yao * 100 / $count));
            //未完成量
            $this->assign('unfinished_percentum', f_round(($count - $count_yao) * 100 / $count));

            $res = M('account')->where('id=1')->field('num,l_num,m_num')->find();
            $this->assign('res', $res);
            $this->assign('count_s', $count_s);
            $this->assign('count_ys', $count_ys);
            $this->assign('count_yso', $count_yso);
            $this->assign('count_l', $count_l);
            $this->assign('count_yl', $count_yl);
            $this->assign('count_ylo', $count_ylo);
            $this->assign('count', $count);
            $this->assign('count_ya', $count_ya);
            $this->assign('count_yao', $count_yao);
            //中额单
            $this->assign('count_m', $count_m);
            $this->assign('count_ym', $count_ym);
            $this->assign('count_ymo', $count_ymo);
            //end
            $this->assign('time', $time);
            $this->display();
        } else {
            $yewu = D('account')->field('id,info')->where('role=6 and msg=0')->select();
            $this->assign('yewu', $yewu);
            $str = ' and tid=' . intval($_SESSION['user']['id']);
            $yw  = I('post.yw', 0, 'intval');
            if (!empty($_POST['time']) || $_POST['time'] != 0) {
                $time = strtotime(I('post.time')) + 43200;
            } else {
                $time = time();
            }
            $da = '';
            if (date('Ymd', $time) == date('Ymd')) {
                $da = ' and xiajia=0 ';
            }
            if ($_POST['type'] != 'all') {
                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price<' . $s_price . ' and addtime <' . $time . ' and endtime > ' . $time;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_s = M('task')->where($where)->count();
                $where   = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price<' . $s_price . ' and addtime <' . $time . ' and endtime > ' . $time . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ys = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price<' . $s_price . ' and addtime <' . $time . ' and endtime > ' . $time . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_yso = M('task')->where($where)->count();

                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $l_price . ' and addtime <' . $time . ' and endtime > ' . $time;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_l = M('task')->where($where)->count();
                $where   = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $l_price . ' and addtime <' . $time . ' and endtime > ' . $time . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_yl = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $l_price . ' and addtime <' . $time . ' and endtime > ' . $time . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ylo = M('task')->where($where)->count();

                //中额单
                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $s_price . ' and price<' . $l_price . ' and addtime <' . $time . ' and endtime > ' . $time;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_m = M('task')->where($where)->count();
                $where   = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $s_price . ' and price<' . $l_price . ' and addtime <' . $time . ' and endtime > ' . $time . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ym = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $s_price . ' and price<' . $l_price . ' and addtime <' . $time . ' and endtime > ' . $time . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ymo = M('task')->where($where)->count();

                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and addtime <' . $time . ' and endtime > ' . $time;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count = M('task')->where($where)->count();
                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and uid is not null and addtime <' . $time . ' and endtime > ' . $time;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ya = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and tb_item is not null and addtime <' . $time . ' and endtime > ' . $time;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_yao = M('task')->where($where)->count();

            } else {
                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price<' . $s_price;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_s = M('task')->where($where)->count();
                $where   = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price<' . $s_price . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ys = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price<' . $s_price . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_yso = M('task')->where($where)->count();

                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $l_price;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_l = M('task')->where($where)->count();
                $where   = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $l_price . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_yl = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $l_price . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ylo = M('task')->where($where)->count();

                //中额单
                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $s_price . ' and price <' . $l_price;
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_m = M('task')->where($where)->count();
                $where   = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $s_price . ' and price <' . $l_price . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ym = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and price>=' . $s_price . ' and price <' . $l_price . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ymo = M('task')->where($where)->count();

                $where = '(xiajia=0 or xiajia=3) and status=1';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count = M('task')->where($where)->count();
                $where = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and uid is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_ya = M('task')->where($where)->count();
                $where    = '(xiajia=0 or xiajia=3) and status=1 ' . $da . ' and tb_item is not null';
                if ($_SESSION['user']['role'] == 6) {$where .= $str;} //业务员
                if ($yw) {
                    $where .= " and tid=" . $yw;
                }

                $count_yao = M('task')->where($where)->count();
                $time      = '';
                $str       = 1;

            }

            //已领取量
            $this->assign('get_percentum', f_round($count_ya * 100 / $count));
            //已完成量
            $this->assign('finished_percentum', f_round($count_yao * 100 / $count));
            //未完成量
            $this->assign('unfinished_percentum', f_round(($count - $count_yao) * 100 / $count));

            $res = M('account')->where('id=1')->field('num,l_num,m_num')->find();
            $this->assign('res', $res);
            $this->assign('yw', $yw);
            $this->assign('count_s', $count_s);
            $this->assign('count_ys', $count_ys);
            $this->assign('count_yso', $count_yso);
            $this->assign('count_l', $count_l);
            $this->assign('count_yl', $count_yl);
            $this->assign('count_ylo', $count_ylo);
            $this->assign('count', $count);
            $this->assign('count_ya', $count_ya);
            $this->assign('count_yao', $count_yao);

            //中额单
            $this->assign('count_m', $count_m);
            $this->assign('count_ym', $count_ym);
            $this->assign('count_ymo', $count_ymo);

            $this->assign('time', $time);
            $this->assign('str', $str);
            $this->display();

        }

    }
    //大额单汇总明细
    public function bigorder()
    {
        $l_price = 300;
        $yw      = I('get.yw', 0, 'intval');
        if (!empty($_GET['time']) || $_GET['time'] != 0) {
            $time = strtotime(I('get.time')) + 43200;
        } else {
            $time = time();
        }
        if ($_GET['type'] != 'all') {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname,a.l_num FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.addtime <' . $time . ' and c.endtime > ' . $time . ' and c.price>=' . $l_price;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        } else {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname,a.l_num FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.price>=' . $l_price;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        }
        $this->assign('data', $data);
        $this->display();
    }
    //小额单汇总明细
    public function smorder()
    {
        $s_price = 100;
        $yw      = I('get.yw', 0, 'intval');
        if (!empty($_GET['time']) || $_GET['time'] != 0) {
            $time = strtotime(I('get.time')) + 43200;
        } else {
            $time = time();
        }
        if ($_GET['type'] != 'all') {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname,a.num FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.addtime <' . $time . ' and c.endtime > ' . $time . ' and c.price<' . $s_price;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        } else {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname,a.num FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.price<' . $s_price;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        }
        $this->assign('data', $data);
        $this->display();
    }
    //中额单明细
    public function middorder()
    {
        $s_price = 100;
        $l_price = 300;
        $yw      = I('get.yw', 0, 'intval');
        if (!empty($_GET['time']) || $_GET['time'] != 0) {
            $time = strtotime(I('get.time')) + 43200;
        } else {
            $time = time();
        }
        if ($_GET['type'] != 'all') {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname,a.m_num FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.addtime <' . $time . ' and c.endtime > ' . $time . ' and c.price>=' . $s_price . ' and c.price <' . $l_price;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        } else {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname,a.m_num FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.price>=' . $s_price . ' and c.price <' . $l_price;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        }
        $this->assign('data', $data);
        $this->display();
    }
    //全单汇总明细
    public function allorder()
    {
        $s_price = 100;
        $yw      = I('get.yw', 0, 'intval');
        if (!empty($_GET['time']) || $_GET['time'] != 0) {
            $time = strtotime(I('get.time')) + 43200;
        } else {
            $time = time();
        }
        if ($_GET['type'] != 'all') {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid and c.addtime <' . $time . ' and c.endtime > ' . $time;
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        } else {
            $query = 'SELECT count(c.id)as count,count(if(c.tb_item is null,null,1))as cou,a.id,a.realname FROM erp_account a ';
            $query .= 'LEFT JOIN erp_account b on a.id=b.user_id left join erp_task c on b.id=c.uid ';
            $query .= ' WHERE a.role=3 ';
            if ($yw) {
                $query .= " and c.tid=" . $yw;
            }

            $query .= ' GROUP BY a.id';
            $data = M()->Query($query);
        }
        $this->assign('data', $data);
        $this->display();
    }

    //计算余额
    /**
     * @param $user_id  商家id
     * @param $sun  发布商品总价格
     * @param $cost  发布商品总费用
     * @return mixed 返回差价
     */
    public function chajia($user_id, $totalmoney)
    {
        //查询用户余额
        $user_info     = D('user')->where('uid=' . $user_id)->find();
        $money         = $user_info['money']; //用户余额
        $yufujin       = $user_info['yufujin']; //冻结金
        $credit_money  = $user_info['credit_money']; //可透支余额
        $credit_status = $user_info['credit_status']; //透支申请状态
        if ($credit_status == 2) {
            $res = $totalmoney - $money - $credit_money;
        } else {
            $res = $totalmoney - $money;
        }
        return $res;
    }

    public function check_money($value = null)
    {

        $user_id = intval($_SESSION['user']['id']);

        $where = "user_id=" . $user_id . " and (status =3 or status=0)";
        $res   = D('product')->where($where)->order('pub_time')->select();

        $starttime = date('Ymd', $res[0]['pub_time']);
        //当前时间
        $now = date('Ymd', time());
        if ($starttime == $now || empty($res)) {
            //$this->ajaxReturn(array('msg'=>0,'info'=>''));
        } else {
            $user_info = D('user')->where('uid=' . $user_id)->find();
            if (!$user_id || !$user_info) {
                $this->ajaxReturn(array('msg' => 1, 'info' => '信息错误'));
            }

            $money         = $user_info['money'];
            $yufujin       = $user_info['yufujin'];
            $credit_money  = $user_info['credit_money']; //可透支额度
            $credit_status = $user_info['credit_status']; //申请透支状态

            $credit_money = ($credit_status == 2) ? $credit_money : 0;

            $where = "user_id=" . $user_id . " and (status =3 or status=0)";
            $res   = D('product')->where($where)->order('pub_time desc')->select();
            $id = I('id');
            $goodsprice = 0;
            if (!empty($id)) {
                $product_tj = D('product')->where("id=$id")->find();
                if (!empty($product_tj)) {
                    $goodsprice = $product_tj['goods_totalprice'] + $product_tj['goods_totalcost'];
                }
            }
            //有发布
            $starttime = date('Ymd', $res[0]['pub_time']);
            //当前时间
            $now = date('Ymd', time());
            if ($starttime == $now) {
                //当天有发布过
                $result = $money+$credit_money-$goodsprice;//f_round($money + $credit_money - $yufujin);
                if ($result < 0) {
                    if (!empty($value)) {
                        return false;
                    }
                    $this->ajaxReturn(array('msg' => 1, 'money' => $money, 'arrearage' => abs($result)));
                }

            } else {
                //当天没有发布过任务291,289,260

                $userid = $_SESSION['user']['id'];
                if ($userid == 654 || $userid == 291 || $userid == 289 || $userid == 260 || $userid == 78 || $userid == 382) {

                } else {
                    if ($money < 0) {
                        if (!empty($value)) {
                            return false;
                        }
                        $this->ajaxReturn(array('msg' => 1, 'money' => $money, 'arrearage' => abs($money)));
                    }
                }

            }
        }

    }

    //异常订单
    public function abnormalorder()
    {
        //判断当前权限
        if (check_action() === false) {
            $this->error('无此权限');
            return false;
        }
        if (IS_GET) {
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 5);

            $status = I('get.status', 0, 'intval');

            $shopname = trim(I('get.shopname'));
            $ordernum = trim(I('get.ordernum'));
            $wangwang = trim(I('get.wangwang'));

            if ($_SESSION['user']['role'] == 5) {
                //商家
                $where = 'a.user_id=' . $_SESSION['user']['id'] . " and a.abn=1 and a.tb_item is not null ";
            } elseif ($_SESSION['user']['role'] == 6) {
                //业务员
                $where = "a.tid={$_SESSION['user']['id']} and a.abn=1 and a.tb_item is not null and a.abn_status != 5";
            } elseif ($_SESSION['user']['role'] == 2) {
                //刷单员
                $where = "a.uid={$_SESSION['user']['id']} and a.abn=1 and a.tb_item is not null and a.abn_status != 5";
            } elseif ($_SESSION['user']['role'] == 3) {
                //站长
                $user_id = M('account')->where('user_id=' . $_SESSION['user']['id'])->field('id')->select();
                if (empty($user_id)) {
                    $this->display();
                    exit;
                }
                foreach ($user_id as $val) {
                    $id_arr[] = $val['id'];
                }

                $id    = implode(",", $id_arr);
                $where = "a.uid in({$id}) and a.abn=1 and a.tb_item is not null and a.abn_status != 5";
            } elseif ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 4) {
                //管理员
                $where = "a.tb_item is not null and a.abn=1 ";
            }

            if (!in_array($status, array(0, 1, 2))) {
                $status = 0;
            }

            //1代表已处理 2代表处理中 0代表全部
            switch ($status) {
                case 1:
                    $where .= " and a.abn_status = 0";
                    break;
                case 2:
                    $where .= " and a.abn_status = 5 ";
                    break;
            }
            if (!empty($wangwang)) {
                $where .= " and a.wangwang like '%$wangwang%'";
            }

            if (!empty($ordernum)) {
                $where .= " and a.tb_item='$ordernum'";
            }

            if (!empty($shopname)) {
                $where .= " and c.shopname like '%$shopname%'";
            }

            $count = D('task')
                ->alias('a')
                ->field('a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,goods_pic,d.realname as realname_s,c.shopname,e.realname,f.info as f_info')
                ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                ->join('left join erp_account f on a.tid=f.id')
                ->where($where)
                ->count();

            $task = D('task')
                ->alias('a')
                ->field('a.*,b.goods_thumb,b.goods_title,b.goods_url,b.goods_zeng,goods_pic,d.realname as realname_s,d.phone as sd_phone,c.shopname,e.realname,f.info as f_info,if(a.empty_cost > 0 ,a.empty_cost,a.redbag) as order_cost')
                ->join('left join erp_product b on a.gid=b.id left join erp_shop c on b.shop_id=c.id')
                ->join('left join erp_account d on a.uid=d.id left join erp_account e on d.user_id=e.id')
                ->join('left join erp_account f on a.tid=f.id')
                ->where($where)
                ->order('a.edittime desc')
                ->limit($page * $page_size, $page_size)
                ->select();

            $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count));
            $this->assign('shopname', $shopname);
            $this->assign('ordernum', $ordernum);
            $this->assign('wangwang', $wangwang);
            $this->assign('status', $status);
            $this->assign('task', $task);
            $this->assign('count', $count);
            $this->display();
        }
    }

    //订单异常修改
    public function myOrder()
    {

        if (IS_AJAX) {
            if ($_SESSION['user']['role'] != 2) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            $data         = array();
            $id           = I('post.id', 0, 'intval'); //任务ID
            $tb_item      = trim(I('post.tb_item')); //任务的订单号
            $wangwang     = trim(I('post.wangwang')); //业务员的旺旺
            $commision    = I('post.commision'); //佣金
            $type         = I('post.type'); //0是新增  1是修改
            $actual_price = floatval(I('post.actual_price')); //刷单员实际输入的价格]
            $redbag       = intval(I('post.redbag'));

            $redbag    = $redbag ? $redbag : 0;
            $task_info = D('task a')->field('a.*,b.empty_cost as p_empty_cost')->join('left join erp_product b on a.gid=b.id')->where('a.id=' . $id)->find(); //当前任务信息
            //            $reg='/^(([1-9][0-9]*)|(([0]\.\d{1,2}|[1-9][0-9]*\.\d{1,2})))$/';
            //            if($reg !=0 && (!preg_match($reg,$redbag) || !preg_match($reg,$actual_price))) $this->ajaxReturn(array('msg' => 0, 'info' => '金额最多输入两位小数'));
            $reg = '/^[0-9][0-9]*$/';
            if (!preg_match($reg, $redbag)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额必须为正整数！'));
            }

            //佣金区间
            if ($type == 0 || ($type == 1 && ($commision != intval($task_info['commision'])))) {

                if (!in_array($commision, cost_commision($actual_price))) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '佣金应该为' . implode(',', cost_commision($actual_price)) . '元'));
                }

            }

            if (date('Y-m-d', time()) != date('Y-m-d', $task_info['addtime'])) {
                //修改的不是今天的订单。佣金无法修改
                if ($commision != intval($task_info['commision'])) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '无法修改以前的佣金'));
                }
            }

            if (empty($task_info)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            if (empty($task_info['tb_item'])) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            if ($task_info['uid'] != $_SESSION['user']['id']) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            if ($task_info['abn'] == 2 && ($task_info['abn_status'] != 2 || $task_info['abn_status'] != 3)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务处于申请退单中，不允许修改'));
            }

            if ($task_info['empty_cost'] > 0) {
                //空包存在
                if ($redbag < 0 || ($redbag > $task_info['empty_cost'] || $redbag > 9)) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额不能大于空包费用'));
                }

            } else {
                //空包不存在
                if ($redbag < 0 || $redbag > 9) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额不能大于9元'));
                }

                if ($redbag >= cost_redbag($actual_price)) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '红包金额不能大于' . cost_redbag($actual_price) . '元'));
                }

            }

            if ($actual_price > f_round($task_info['price'] * 2) || $actual_price <= 0) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '实际下单价错误'));
            }

            if (strlen($tb_item) != 18) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '订单号有误'));
            }

            if (empty($id) || empty($tb_item) || empty($commision) || empty($actual_price)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '参数不对'));
            }

            $data['tb_item'] = $tb_item;

            //查询订单号是否重复
            M()->startTrans();
            //修改
            $where = 'tb_item=' . $tb_item . ' and id !=' . $id;
            $task  = D('task')->where($where)->find();
            if ($task) {
                $this->ajaxReturn(array('msg' => 2, 'info' => '修改订单号重复'));
                exit;
            }
            $pub_time             = D('product')->where(array('id' => $task_info['gid']))->getField('pub_time');
            $data['wangwang']     = $wangwang;
            $data['commision']    = $commision;
            $data['edittime']     = time();
            $data['uid']          = $_SESSION['user']['id'];
            $data['name']         = $_SESSION['user']['name'];
            $data['actual_price'] = $actual_price;
            $data['cost']         = cost($actual_price, $task_info['user_id'], $pub_time);
            $data['redbag']       = $redbag;

            //商家扣费

            $actual_cost = $actual_price + cost($actual_price, $task_info['user_id'], $pub_time) - $task_info['actual_price'] - $task_info['cost'];
            $cha         = $actual_cost;
            if (f_round($cha) > 0) {
                $balances_status = save_available($task_info['user_id'], abs($cha), $id, 8, 1);
            } elseif (f_round($cha) < 0) {
                $balances_status = save_available($task_info['user_id'], abs($cha), $id, 8, 2);
            }
            if ($task_info['p_empty_cost'] == 0) {
                if ($redbag > 0 && $task_info['empty_cost'] == 0) {
                    $cost_redbag     = cost_redbag($actual_price);
                    $balances_status = save_available($task_info['user_id'], $cost_redbag, $id, 9, 1);
                    $actual_cost += $cost_redbag;
                    $data['empty_cost'] = $cost_redbag;
                } elseif ($redbag > 0 && $task_info['empty_cost'] > 0) {
                    $cha = cost_redbag($actual_price) - $task_info['empty_cost'];
                    $actual_cost += $cha;
                    $data['empty_cost'] = cost_redbag($actual_price);
                    if (f_round($cha) > 0) {
                        $balances_status = save_available($task_info['user_id'], abs($cha), $id, 9, 1, $actual_cost * -1 + $cha);
                    } elseif (f_round($cha) < 0) {
                        $balances_status = save_available($task_info['user_id'], abs($cha), $id, 9, 2, $actual_cost * -1 + $cha);
                    }
                } elseif ($redbag == 0 && $task_info['empty_cost'] > 0) {
                    $cost_redbag     = $task_info['empty_cost'];
                    $balances_status = save_available($task_info['user_id'], $cost_redbag, $id, 9, 2, $actual_cost * -1);
                    $actual_cost -= $cost_redbag;
                    $data['empty_cost'] = 0;
                }
            }

            if (abs($actual_cost) != 0) {
                //商家扣费
                $shop_money = D('user')->where('uid=' . $task_info['user_id'])->setDec('money', $actual_cost);

                if (!$shop_money) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));
                }
            }
            $totalcost = $actual_price + $commision + $redbag - $task_info['actual_price'] - $task_info['commision'] - $task_info['redbag'];
            if ($totalcost != 0) {
                //刷单员扣费
                //扣除每单价格和佣金+红包费
                $shuadanyuan_money = D('account')->where('id=' . $_SESSION['user']['id'])->setDec('money', $totalcost);

                if (!$shuadanyuan_money) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 2, 'info' => '操作失败'));
                }
            }

            $data['abn_status'] = 5; //订单异常修改成功

            if ($task_info['redbag'] == 0 && $redbag > 0) {
                $data['redbagtime'] = time();
            }
            $msg = D('task')->where('id=' . $id)->save($data);
            if ($msg) {

                M()->commit();
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('msg' => 2, 'info' => '修改失败'));
            }
        }
    }
    //申请批量下架
    public function xiajia_all()
    {
        $type     = I('post.type', 0, 'intval');
        $goods_id = I('post.goods_id', 0, 'intval'); //产品id
        if (!$goods_id) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '产品不存在'));
        }

        $goods_status = D('product')->where('id=' . $goods_id)->getField('status');
        if ($goods_status != 3) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '该产品无法下架'));
        }

        if ($type != 1 && $type != 2) {
            $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
        }

        if ($type == 1) {
            //商家申请批量下架
            if ($_SESSION['user']['role'] != 5) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '非法操作'));
            }

            $user_id      = D('product')->where('id=' . $goods_id)->getField('user_id'); //产品的商家id
            $task_user_id = D('task')->where('gid=' . $goods_id)->getField('user_id');
            if (intval($_SESSION['user']['role']) == 5) {
                if ($user_id != $_SESSION['user']['id'] || $task_user_id != $_SESSION['user']['id']) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '信息不对'));
                }

            }
            $array = array(
                'xiajia'       => 1,
                'xiajiareason' => null,
            );
            $where  = "uid is null and tb_item is null and xiajia = 0 and gid=" . $goods_id;
            $status = D('task')->where($where)->setField($array);
            if (!$status) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '没有可下架任务'));
            }

            $this->ajaxReturn(array('msg' => 1, 'info' => '申请成功'));
        } elseif ($type == 2) {
            //业务员批量下架 管理员
            if ($_SESSION['user']['role'] == 6 || $_SESSION['user']['role'] == 1) {
                $where = "uid is null and tb_item is null and (xiajia = 0 or xiajia = 1) and gid=" . $goods_id;
                $task  = D('task')->where($where)->select();
                if (empty($task)) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '没有可下架的任务'));
                }

                $total_price = $total_cost = $total_empty_cost = 0;
                foreach ($task as $key => $value) {
                    $total_price += $value['price'];
                    $total_cost += $value['cost'];
                    $total_empty_cost += $value['empty_cost'];
                }
                $yufujin = $total_price + $total_cost + $total_empty_cost;
                M()->startTrans();
                $balances_status = save_available($task[0]['user_id'], $yufujin, $goods_id, 5, 2);
                $yufujin_status  = D('user')->where('uid=' . $task[0]['user_id'])->setDec('yufujin', $yufujin);
                $money_status  = D('user')->where('uid=' . $task[0]['user_id'])->setInc('money', $yufujin);
                $array           = array(
                    'xiajia'       => 2,
                    'xiajiareason' => null,
                );
                $task_status = D('task')->where($where)->setField($array);
                if (!$yufujin_status || !$money_status || !$task_status || !$balances_status) {
                    M()->rollback();
                    $this->ajaxReturn(array('msg' => 0, 'info' => '系统错误，审核失败'));
                }
                M()->commit();
                $this->ajaxReturn(array('msg' => 1, 'info' => '下架成功'));
            } else {
                $this->ajaxReturn(array('msg' => 0, 'info' => '非法操作'));
            }

        }

    }
    //业务员批量审核通过  超级管理员
    public function check_undercarriage_all()
    {
        if ($_SESSION['user']['role'] == 6 || $_SESSION['user']['role'] == 1) {
            $goods_id = I('post.goods_id', 0, 'intval'); //产品id
            if (!$goods_id) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '产品不存在'));
            }

            $goods_status = D('product')->where('id=' . $goods_id)->getField('status');
            if ($goods_status != 3) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '该产品无法下架'));
            }

            $where = "uid is null and tb_item is null and xiajia = 1 and gid=" . $goods_id;
            $task  = D('task')->where($where)->select();
            if (empty($task)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '没有可审核的申请'));
            }

            $total_price = $total_cost = $total_empty_cost = 0;
            foreach ($task as $key => $value) {
                $total_price += $value['price'];
                $total_cost += $value['cost'];
                $total_empty_cost += $value['empty_cost'];
            }

            $yufujin = $total_price + $total_cost + $total_empty_cost;
            M()->startTrans();
            $balances_status = save_available($task[0]['user_id'], $yufujin, $goods_id, 5, 2);
            $yufujin_status  = D('user')->where('uid=' . $task[0]['user_id'])->setDec('yufujin', $yufujin);
            $money_status  = D('user')->where('uid=' . $task[0]['user_id'])->setInc('money', $yufujin);
            $array           = array(
                'xiajia'       => 2,
                'xiajiareason' => null,
            );
            $task_status = D('task')->where($where)->setField($array);
            if (!$yufujin_status || !$money_status || !$task_status || !$balances_status) {
                M()->rollback();
                $this->ajaxReturn(array('msg' => 0, 'info' => '系统错误，审核失败'));
            }
            M()->commit();
            $this->ajaxReturn(array('msg' => 1, 'info' => '审核通过'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => '非法操作'));
        }

    }
    //业务员批量拒绝
    public function jujuexiajia_all()
    {
        if ($_SESSION['user']['role'] == 6 || $_SESSION['user']['role'] == 1) {
            $goods_id = I('post.goods_id', 0, 'intval'); //产品id
            $reason   = trim(I('post.reason'));
            if (empty($reason)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '理由必须填写'));
            }

            if (!$goods_id) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '产品不存在'));
            }

            $goods_status = D('product')->where('id=' . $goods_id)->getField('status');
            if ($goods_status != 3) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '该产品无法下架'));
            }

            $where = "uid is null and tb_item is null and xiajia = 1 and gid=" . $goods_id;
            $array = array(
                'xiajiareason' => $reason,
                'xiajia'       => 0,
            );
            $res = D('task')->where($where)->setField($array);
            if (!$res) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '没有可拒绝的任务'));
            }

            $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
        } else {
            $this->ajaxReturn(array('msg' => 0, 'info' => '非法操作'));
        }
    }
    //修改 刷单人数
    public function user_config()
    {
        if ($_SESSION['user']['role'] == 1) {
            $brusher_s = I('post.brusher_s', 0, 'intval'); //小额人数
            $brusher_m = I('post.brusher_m', 0, 'intval'); //中额人数
            $brusher_l = I('post.brusher_l', 0, 'intval'); //大额人数

            $array = array(
                'num'   => $brusher_s,
                'm_num' => $brusher_m,
                'l_num' => $brusher_l,
            );
            $res = M('account')->where('id=1')->save($array);
            if (!$res) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '提交失败，请重新提交'));
            } else {
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
            }

        }
    }
    //发布评价任务
    public function evalimg()
    {
        if (IS_GET) {
            $id = I('get.id');
            $this->display();
        } elseif (IS_POST) {
            $id     = I('post.id', 0, 'intval'); //任务id
            $img    = I('post.img');
            $remark = trim(I('post.remark'));
            if (empty($id)) {
                $this->error('信息有误', 'javascript:parent.location.reload();');
                exit;
            }
            if (count($img) > 3) {
                $this->error('最多上传3张图片');
                exit;
            }
            $task = D('task')->field('gid,shop_id,user_id,uid')->where('id=' . $id)->find();
            if (empty($task['gid'])) {
                $this->error('信息有误', 'javascript:parent.location.reload();');
                exit;
            }
            $total = D('task')->where('shop_id=' . $task['shop_id'] . ' and addtime >= 1528560000 and tb_item is not null and (abn_status != 4 or abn_status is null)')->count();
            $eval  = D('task_eval')->where('shop_id=' . $task['shop_id'])->count();

            //取1/10
            $allow = intval(floor($total * 0.1) - $eval);
            if ($allow > 0) {

                if (!empty($img)) {
                    $eval_pic = implode(',', $img);
                } else {
                    $eval_pic = null;
                }
                $data = array(
                    'eval_pic' => $eval_pic, //评价图
                    'task_id'  => $id, //任务id
                    'goods_id' => $task['gid'], //产品id
                    'addtime'  => time(), //添加时间
                    'tid'      => $_SESSION['user']['id'], //业务员id
                    'remark'   => $remark,
                    'sd_id'    => $task['uid'],
                    'user_id'  => $task['user_id'],
                    'shop_id'  => $task['shop_id'],

                );
                $task_eval = D('task_eval')->where('task_id=' . $id)->find();
                if (empty($task_eval)) {
                    $res = D('task_eval')->add($data);

                } else {
                    $data['del'] = 0;
                    $res         = D('task_eval')->where('task_id=' . $id)->setField($data);
                }
                if (!$res) {
                    $this->error('操作失败', 'javascript:parent.location.reload();');
                    exit;
                }
                $this->success('提交成功', 'javascript:parent.location.reload();');
            } else {
                $this->error('当前店铺只有' . $allow . '份可以发布', 'javascript:parent.location.reload();');
            }
//            $user_id=D('task')->where('id='.$id)->getField('user_id');
            //            //查询总任务
            //            $total=D('task')->where('gid='.$gid.' and tb_item is not null')->count();
            //            //取10%  可上传图的数量
            //            $allow=floor($total * 0.1);
            //            //查询填过评价的任务
            //            $eval =D('task_eval')->where('goods_id='.$gid)->count();
            //            $time=strtotime(date('Y-m-d 00:00:00',time()));
            //            $eval_now=D('task_eval a')->where('a.addtime >= '.$time.' and a.shop_id='.$shop_id)->count();
            //            $cha=($allow - $eval);
            //            $where_t='shop_id='.$shop_id.' and uid is not null and addtime <='.time().' and endtime >='.time();
            //            $count=D('task')->where($where_t)->count();
            ////            if($count <=0 ||  $eval_now > 0){
            ////                $this->error('今天暂未有任务被领取，无法发布评价任务','javascript:parent.location.reload();');
            ////                exit;
            ////            }
            //            if($cha > 0 || ($count > 0 && $eval_now < 1)) {
            //                $eval_pic=implode(',',$img);
            //                $uid=D('task')->where('id='.$id)->getField('uid');
            //                $data=array(
            //                    'eval_pic'=>$eval_pic,  //评价图
            //                    'task_id'=>$id,         //任务id
            //                    'goods_id'=>$gid,       //产品id
            //                    'addtime'=>time(),      //添加时间
            //                    'tid'=>$_SESSION['user']['id'], //业务员id
            //                    'remark'=>$remark,
            //                    'sd_id'=>$uid,
            //                    'user_id'=>$user_id,
            //                    'shop_id'=>$shop_id,
            //
            //                );
            //                $res=D('task_eval')->add($data);
            //                if(!$res){
            //                    $this->error('操作失败','javascript:parent.location.reload();');
            //                    exit;
            //                }
            //                $this->success('提交成功','javascript:parent.location.reload();');
            //
            //            }else{
            //                $this->error('当前任务无法发布评价任务','javascript:parent.location.reload();');
            //                exit;
            //            }

        }
    }
    //修改发布任务图片
    public function editevalimg()
    {
        if (IS_GET) {
            $id = I('get.id'); //任务id
            if (empty($id)) {
                $this->error('信息错误', 'javascript:parent.location.reload();');
            }
            $task_eval = D('task_eval')->field('eval_pic,remark')->where('task_id=' . $id)->find();
            $eval_pic  = explode(',', $task_eval['eval_pic']);
            $total     = count($eval_pic);
            $this->assign('task_eval', $task_eval);
            $this->assign('total', $total);
            $this->assign('eval_pic', $eval_pic);
            $this->display();
        } elseif (IS_POST) {
            $id     = I('post.id', 0, 'intval'); //任务id
            $img    = I('post.img');
            $remark = trim(I('post.remark'));
//            if(empty($img)){
            //                $this->error('图片有误');
            //                exit;
            //            }
            if (!empty($img)) {
                $eval_pic = implode(',', $img);
            } else {
                $eval_pic = null;
            }
            //$eval_pic=implode(',',$img);
            $data = array(
                'eval_pic' => $eval_pic,
                'remark'   => $remark,
                'addtime'  => time(),
            );
            $res = D('task_eval')->where('task_id=' . $id)->setField($data);
            if (!$res) {
                $this->error('操作失败', 'javascript:parent.location.reload();');
                exit;
            }
            $this->success('提交成功', 'javascript:parent.location.reload();');
        }
    }
    //评价任务列表
    public function evaltask()
    {
        if (IS_GET) {
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);
            $where     = "a.del = 0";
            $status    = I('get.status', 0, 'int');
            if (!in_array($status, array(0, 1, 2, 3))) {
                $status = 0;
            }
            switch ($status) {
                case 3:
                    $where .= ' and a.eval_status = 0';
                    break;
                case 1:
                    $where .= ' and a.eval_status = 1';
                    break;
                case 2:
                    $where .= ' and a.eval_status = 2';
                    break;
                case 0:
                    $where .= ' and a.eval_status = 3';
                    break;
            }
            if ($_SESSION['user']['role'] == 6) {
                //业务员
                $where .= ' and a.tid=' . $_SESSION['user']['id'];
            } elseif ($_SESSION['user']['role'] == 2) {
                $where .= ' and a.sd_id=' . $_SESSION['user']['id'];
            } elseif ($_SESSION['user']['role'] == 1) {

            } elseif ($_SESSION['user']['role'] == 3) {
                $where .= ' and e.user_id=' . $_SESSION['user']['id'];
            }
            $count = D('task_eval a')
                ->join('left join erp_product b on a.goods_id=b.id')
                ->join('left join erp_shop c on b.shop_id=c.id')
                ->join('left join erp_task d on a.task_id=d.id')
                ->join('left join erp_account e on a.sd_id=e.id')
                ->where($where)
                ->count();
            $eval_pic = D('task_eval a')
                ->field('a.*,b.goods_pic,b.goods_url,b.goods_title,c.shopname,d.tb_item,d.price,d.actual_price,d.wangwang,d.addtime as rw_addtime,e.phone as sd_phone,f.realname as zz_realname,g.realname as yw_realname,g.info as yw_info,e.realname as sd_realname')
                ->join('left join erp_product b on a.goods_id=b.id')
                ->join('left join erp_shop c on b.shop_id=c.id')
                ->join('left join erp_task d on a.task_id=d.id')
                ->join('left join erp_account e on a.sd_id=e.id')
                ->join('left join erp_account f on e.user_id=f.id')
                ->join('left join erp_account g on a.tid=g.id')
                ->where($where)
                ->order('a.addtime desc')
                ->limit($page * $page_size, $page_size)
                ->select();
            $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count));
            $this->assign('count', $count);
            $this->assign('eval_pic', $eval_pic);
            $this->display();

        }
    }

    //软删除评价任务
    public function del_eval()
    {
        if (IS_AJAX) {
            $id = I('post.id', 0, 'int');
            if (empty($id) || $id <= 0) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            $del = D('task_eval')->where('id=' . $id)->getField('del');
            if ($del != 0) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '刷新再试'));
            }

            $res = D('task_eval')->where('id=' . $id)->setField('del', 1);
            if (!$res) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '操作失败'));
            }

            $this->ajaxReturn(array('msg' => 1, 'info' => '删除成功'));
        }
    }
    //查看嗮图
    public function evalimgshow()
    {
        $id        = I('get.id', 0, 'intval');
        $task_eval = D('task_eval')->field('eval_pic,remark')->where('id=' . $id)->find();

        $eval_pic = explode(',', $task_eval['eval_pic']);
        $this->assign('eval_pic', $eval_pic);
        $this->assign('task_eval', $task_eval);
        $this->display();
    }
    //上传评价图
    public function evalimg_s()
    {
        if (IS_GET) {
            $this->display();
        } elseif (IS_POST) {
            $id  = I('post.id', 0, 'intval');
            $img = I('post.img');
            if (empty($id) || empty($img)) {
                $this->error('信息错误');
                exit;
            }
            $array = array('eval_screenshot' => $img, 'eval_status' => 0, 'checktime' => time());
            $res   = D('task_eval')->where('id=' . $id)->setField($array);
            if (!$res) {
                $this->error('上传错误');
                exit;
            }
            $this->success('提交成功', 'javascript:parent.location.reload();');

        }
    }
    //审核上传截图
    public function eval_check()
    {
        if (IS_AJAX) {
            $id     = I('post.id', 0, 'intval');
            $type   = I('post.type', 0, 'intval');
            $reason = trim(I('post.reason'));
            if (empty($id)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            if ($type != 1 && $type != 2) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            $eval_status = D('task_eval')->where('id=' . $id)->getField('eval_status');
            if ($eval_status != 0) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            //1为审核通过
            if ($type == 1) {
                $data = array('deduct' => 2, 'eval_status' => 1);
                $res  = D('task_eval')->where('id=' . $id)->setField($data);
                //刷单员扣款
                if (!$res) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '操作错误'));
                }

                $this->ajaxReturn(array('msg' => 1, 'info' => '提交成功'));
            } elseif ($type == 2) {
                if (empty($reason)) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '理由必须填写'));
                }

                $array = array('eval_status' => 2, 'eval_reason' => $reason);
                $res   = D('task_eval')->where('id=' . $id)->setField($array);
                if (!$res) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '操作错误'));
                }

                $this->ajaxReturn(array('msg' => 1, 'info' => '提交成功'));
            }
        }

    }
    //业务员填写任务反馈
    public function feedback()
    {
        if (IS_GET) {
            $this->display();
        } elseif (IS_POST) {
            $id       = I('post.id', 0, 'intval');
            $feedback = trim(I('post.feedback'));
            $img      = I('post.img');
            if ($_SESSION['user']['role'] != 6) {
                $this->error('权限不够');exit;
            }
            if (empty($id) || $id <= 0) {
                $this->error('信息错误');exit;
            }
            if (empty($feedback)) {
                $this->error('反馈信息必须填写');exit;
            }
            $data = array(
                'feedback_time' => time(),
                'feedback'      => $feedback,
                'feedimg'       => $img,
            );
            $res = D('task')->where('id=' . $id)->setField($data);
            if (!$res) {
                $this->error('提交失败');exit;
            }
            $this->success('提交成功', 'javascript:parent.location.reload();');

        }

    }
    //任务反馈
    public function taskfeedback()
    {
        if (IS_GET) {

            $zz = D('account')->field('id,realname')->where('role=3 and msg=0')->select(); //站长
            $yw = D('account')->field('id,info')->where('role=6 and msg=0')->select(); //业务员

            $this->assign('zz', $zz);
            $this->assign('yw', $yw);
            $page      = I('get.page', 1);
            $page      = $page < 1 ? 0 : $page - 1;
            $page_size = I('get.pagesize', 10);
            $choicezz  = I('get.choicezz', 0, 'intval');
            $choiceyw  = I('get.choiceyw', 0, 'intval');
            $starttime = I('get.time');
            $endtime   = I('get.endtime');

            $where = "a.feedback is not null";
            if ($_SESSION['user']['role'] == 6) {
                //业务员看到自己的反馈
                $where .= " and a.tid=" . $_SESSION['user']['id'];
                if (!empty($choicezz)) {
                    $where .= " and d.user_id=" . $choicezz;
                }

            } elseif ($_SESSION['user']['role'] == 3) {
                //站长 查看自己刷单员的信息
                $where .= " and d.user_id=" . $_SESSION['user']['id'];
                if (!empty($choiceyw)) {
                    $where .= " and a.tid=" . $choiceyw;
                }

            } elseif ($_SESSION['user']['role'] == 1) {
                if (!empty($choicezz)) {
                    $where .= " and d.user_id=" . $choicezz;
                }

                if (!empty($choiceyw)) {
                    $where .= " and a.tid=" . $choiceyw;
                }

            }

            if (!empty($starttime)) {
                $starttime = strtotime($starttime . "00:00:00");
                $where .= " and a.feedback_time >= {$starttime}";
            }
            if (!empty($endtime)) {
                $endtime = strtotime($endtime . "23:59:59");
                $where .= " and a.feedback_time <= {$endtime}";
            }

            $count = D('task a')
                ->field('a.*,b.shopname,c.goods_pic,c.goods_title,c.goods_url,d.realname as sd_realname')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_product c on a.gid=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->where($where)
                ->count();
            $feedback = D('task a')
                ->field('a.*,b.shopname,c.goods_pic,c.goods_title,c.goods_url,d.phone as sd_phone,d.realname as sd_realname,e.realname as zz_realname,f.realname as yw_realname,f.info as yw_info')
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_product c on a.gid=c.id')
                ->join('left join erp_account d on a.uid=d.id')
                ->join('left join erp_account e on d.user_id=e.id')
                ->join('left join erp_account f on a.tid=f.id')
                ->where($where)
                ->order('feedback_time desc')
                ->limit($page * $page_size, $page_size)
                ->select();
            $this->assign('pagination', Util::getInstance('Pagination')->create($page + 1, $page_size, $count));
            if ($starttime) {
                $this->assign('starttime', date("Y-m-d", $starttime));
            }

            if ($endtime) {
                $this->assign('endtime', date("Y-m-d", $endtime));
            }

            $this->assign('count', $count);
            $this->assign('data', $feedback);
            $this->display();
        }
    }
    //超级管理员操作 提前上架商品
    public function shangjia()
    {
        if (IS_AJAX) {
            if ($_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 6) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '权限不够'));
            }

            $id             = trim(I('post.id'));
            $where_now      = 'status=1 and xiajia=0 and addtime <' . time() . ' and endtime > ' . time();
            $task_count     = D('task')->where($where_now)->count();
            $goods_totalnum = D('product')->where('id=' . $id)->getField('goods_totalnum');
//            if($task_count + $goods_totalnum > 2300) $this->ajaxReturn(array('msg'=>0,'info'=>'继续上架，则总任务超过2300，无法继续上架'));
            if (!is_numeric($id)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '请输入产品ID号'));
            }

            $time = strtotime(date('Y-m-d 18:00:00', time()));
//            if($time <= time())   $this->ajaxReturn(array('msg'=>0,'info'=>'18点之后不允许提前上线任务'));
            $product = D('product')->where('id=' . intval($id))->find();
            if (empty($product)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务不存在'));
            }

            //判断是否为审核状态
            if ($product['status'] != 3) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务没有审核'));
            }

            if ($_SESSION['user']['role'] == 6) {
                //如果是业务员 判断当前任务是不是业务员下的
                $user = D('product a')->field('b.tid')->where('a.id=' . intval($id))->join('left join erp_user b on a.user_id=b.uid')->find();

                if ($user['tid'] != $_SESSION['user']['id']) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '不是自己的商家任务'));
                }

            }

            //判断是否是明天的任务
            $tomorrow = strtotime(date("Y-m-d"), time()) + 86400; //明天00:00:00时间戳
            //发布的任务一定是今天12点后 并且上架时间是第二天的00:00:00
            if ($product['addtime'] < time() || $product['addtime'] != $tomorrow) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务错误或者已经上架'));
            }

            $starttime = strtotime(date('Y-m-d 11:59:59', time()));
            $endtime   = strtotime(date('Y-m-d 23:59:59', time()));
//            $checktime = $starttime + 120;
            M()->startTrans();
            $data_p = array(
                'addtime' => $starttime,
//                'checktime' => $checktime,
                'endtime' => $endtime,
                //'pub_time'  => $starttime,
            );
            $product_time = D('product')->where('id=' . $id)->save($data_p);
            $data_t       = array(
                'addtime' => $starttime,
                'endtime' => $endtime,
            );
            $task_time = D('task')->where('gid=' . $id)->save($data_t);
            if (!$product_time || !$task_time) {
                M()->rollback();
                $this->ajaxReturn(array('msg' => 0, 'info' => '操作错误'));
            }
            M()->commit();
            $this->ajaxReturn(array('msg' => 1, 'info' => '上架成功'));

        }
    }
    //退款完成订单下载
    public function excel_abn()
    {
        $shopname = trim(I('get.shopname'));
        $ordernum = trim(I('get.ordernum'));
        $wangwang = trim(I('get.wangwang'));
        //商家
        $where = "a.tb_item is not null and a.abn = 2 and a.abn_status = 4";
        if (!empty($shopname)) {
            $where .= " and b.shopname like '%{$shopname}%'";
        }

        if (!empty($ordernum)) {
            $where .= " and a.tb_item = {$ordernum}";
        }

        if (!empty($wangwang)) {
            $where .= " and a.wangwang like '%{$wangwang}%'";
        }

        if ($_SESSION['user']['role'] == 5) {
            $where .= " and a.user_id=" . $_SESSION['user']['id'];

            $task = D('task a')
                ->field('FROM_UNIXTIME(a.edittime) as time,b.shopname,a.keyword,a.actual_price,a.cost,if(a.empty_cost>0,a.empty_cost,a.redbag) as order_cost,a.tb_item,a.wangwang')
                ->join('left join erp_shop b on a.shop_id=b.id')
//                ->join('left join erp_account c on a.uid=c.id') //刷单员
                //                ->join('left join erp_account d on c.user_id=d.id')  //站长
                ->where($where)
                ->select();
            $title    = array('完成时间', '店铺名', '关键字', '下单价', '服务费', '其他', '订单号', '旺旺号');
            $filename = '订单详情';
            $this->down($title, $task, $filename);
            exit;
        } elseif ($_SESSION['user']['role'] == 4 || $_SESSION['user']['role'] == 1) {
            //财务
            $starttime = I('get.time');
            $endtime   = I('get.endtime');
            $choicezz  = I('get.choicezz');
            $choiceyw  = I('get.choiceyw');
            if (empty($starttime)) {
                $this->error('搜索开始时间必须选择');
                exit;
            }
            //搜索下载日期，如果结束日期存在，取结束日期，不存在取当天
            $endtime_s = $endtime ? strtotime($endtime . "23:59:59") : strtotime(date('Y-m-d 23:59:59', time()));

            $time_difference = intval(ceil(($endtime_s - strtotime($starttime . "00:00:00")) / (60 * 60 * 24))); //天数
            if ($time_difference > 30) {
                $this->error('下载表格时间区间不得超过30天');
                exit;
            }
            if (!empty($choicezz)) {
                $where .= " and d.id=" . $choicezz;
            }

            if (!empty($choiceyw)) {
                $where .= " and a.tid=" . $choiceyw;
            }

            if (!empty($starttime)) {
                $starttime = strtotime($starttime . "00:00:00");
                $where .= " and a.successtime >= {$starttime}";
            }
            if (!empty($endtime)) {
                $endtime = strtotime($endtime . "23:59:59");
                $where .= " and a.successtime <= {$endtime}";
            }
            $task = D('task a')
                ->field("FROM_UNIXTIME(a.edittime) as time,b.shopname,a.keyword,if(a.repay=1,'是','否') as repay,a.actual_price,a.cost,if(a.empty_cost>0,a.empty_cost,a.redbag) as order_cost,a.commision,a.tb_item,a.wangwang,e.realname as realname_yw,d.realname as realname_zz")
                ->join('left join erp_shop b on a.shop_id=b.id')
                ->join('left join erp_account c on a.uid=c.id') //刷单员
                ->join('left join erp_account d on c.user_id=d.id') //站长
                ->join('left join erp_account e on a.tid=e.id')
                ->where($where)
                ->select();
            $title    = array('完成时间', '店铺名', '关键字', '是否追回', '下单价', '服务费', '其他', '佣金', '订单编号', '旺旺号', '业务员', '站长');
            $filename = '订单详情';
            $this->down($title, $task, $filename);
            exit;
        }

    }

    //产品提前上架提示
    public function get_message()
    {
        //今日全部任务
        $where      = 'status=1 and xiajia=0 and addtime <' . time() . ' and endtime > ' . time();
        $task_count = D('task')->where($where)->count();
        //已经领取量
        $where    = 'status=1 and xiajia=0 and uid is not null and addtime <' . time() . ' and endtime > ' . time();
        $task_get = D('task')->where($where)->count();
        $task_get = f_round($task_get * 100 / $task_count);
        //已完成量
        $where         = 'status=1 and xiajia=0 and tb_item is not null and addtime <' . time() . ' and endtime > ' . time();
        $task_finished = D('task')->where($where)->count();
        $task_finished = f_round($task_finished * 100 / $task_count);

        $this->ajaxReturn(array('total' => $task_count, 'receice' => $task_get, 'complete' => $task_finished));
    }

    //异常订单拒绝
    public function refuseAbn()
    {
        if (IS_GET) {
            $this->display();
        } elseif (IS_POST) {
            $id     = I('post.id', 0, 'int');
            $reason = trim(I('post.refuse_info'));
            $img    = I('post.img');
            if (empty($id) || $id <= 0) {
                $this->error('信息不存在', 'javascript:parent.location.reload();');
                exit;
            }
            $abn_status = D('task')->where('id=' . $id)->getField('abn_status');
            if ($abn_status != 0) {
                $this->error('信息错误', 'javascript:parent.location.reload();');
                exit;
            }
            if (empty($reason)) {
                $this->error('拒绝理由必须填写');
                exit;
            }
            $data = array(
                'reason'     => $reason,
                'abn_img'    => $img,
                'abn_reason' => $reason,
                'abn_status' => 6,
            );
            $res = D('task')->where('id=' . $id)->setField($data);
            if (!$res) {
                $this->error('添加失败');
                exit;
            }
            $this->success('添加成功', 'javascript:parent.location.reload();');

        }

    }

    //异常订单拒绝详情
    public function refuseinfo()
    {
        if (IS_GET) {
            $id = I('get.id', 0, 'int');
            if (empty($id) || $id <= 0) {
                $this->error('信息不存在', 'javascript:parent.location.reload();');
                exit;
            }
            $info = D('task')->where('id=' . $id)->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    //自用
    public function shangjia_a()
    {
        if (IS_GET) {
            $this->display();
        } elseif (IS_AJAX) {
            if ($_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 6) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '权限不够'));
            }

            $id = trim(I('post.id'));
            if (!is_numeric($id)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '请输入产品ID号'));
            }

            //$time = strtotime(date('Y-m-d 18:00:00',time()));
            //if($time <= time())   $this->ajaxReturn(array('msg'=>0,'info'=>'18点之后不允许提前上线任务'));
            $product = D('product')->where('id=' . intval($id))->find();
            if (empty($product)) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务不存在'));
            }

            //判断是否为审核状态
            if ($product['status'] != 3) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务没有审核'));
            }

            if ($_SESSION['user']['role'] == 6) {
                //如果是业务员 判断当前任务是不是业务员下的
                $user = D('product a')->field('b.tid')->where('a.id=' . intval($id))->join('left join erp_user b on a.user_id=b.uid')->find();

                if ($user['tid'] != $_SESSION['user']['id']) {
                    $this->ajaxReturn(array('msg' => 0, 'info' => '不是自己的商家任务'));
                }

            }

            //判断是否是明天的任务
            $tomorrow = strtotime(date("Y-m-d"), time()) + 86400; //明天00:00:00时间戳
            //发布的任务一定是今天12点后 并且上架时间是第二天的00:00:00
            if ($product['addtime'] < time() || $product['addtime'] != $tomorrow) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '任务错误或者已经上架'));
            }

            $starttime = strtotime(date('Y-m-d 11:59:59', time()));
            $endtime   = strtotime(date('Y-m-d 23:59:59', time()));
//            $checktime = $starttime + 120;
            M()->startTrans();
            $data_p = array(
                'addtime'  => $starttime,
//                'checktime' => $checktime,
                'endtime'  => $endtime,
                'pub_time' => $starttime,
            );
            $product_time = D('product')->where('id=' . $id)->save($data_p);
            $data_t       = array(
                'addtime' => $starttime,
                'endtime' => $endtime,
            );
            $task_time = D('task')->where('gid=' . $id)->save($data_t);
            if (!$product_time || !$task_time) {
                M()->rollback();
                $this->ajaxReturn(array('msg' => 0, 'info' => '操作错误'));
            }
            M()->commit();
            $this->ajaxReturn(array('msg' => 1, 'info' => '上架成功'));
        }

    }

    //计算允许发布的评价任务
    public function allow()
    {
        if (IS_AJAX) {
            $id = I('post.id', 0, 'int');
            if (empty($id) || $id <= 0) {
                $this->ajaxReturn(array('msg' => 0, 'info' => '信息错误'));
            }

            $task  = D('task')->where('id=' . $id)->find();
            $total = D('task')->where('shop_id=' . $task['shop_id'] . ' and addtime >= 1528560000 and tb_item is not null and (abn_status != 4 or abn_status is null)')->count();
            $eval  = D('task_eval')->where('shop_id=' . $task['shop_id'])->count();
            //取1/10
            $allow = intval(floor($total * 0.1) - $eval);
            $this->ajaxReturn(array('allow' => $allow));
        }
    }
}
