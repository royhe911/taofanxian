<?php
/**
 * Created by PhpStorm.
 * User: xcb91@qq.com
 * Date: 2018/6/11
 * Time: 17:04
 */

namespace Erp\Controller;


use Erp\Model\TaskModel;
use Think\Controller;

class CliController extends Controller
{
    //自动下架
    public function auto_sold_out(){

        //任务下架
        $where_t = "a.tb_item is null and (a.xiajia = 1 or a.xiajia = 0) and a.endtime < ".time()." and (b.status=0 or b.status=3)";
        $task = M('task a')->field('a.*,b.status')->join('left join erp_product b on a.gid=b.id')->where($where_t)->select();

        // 初始化商家用户
        $user[] = array();
        foreach ( $task as $key =>$value){
            M()->startTrans();
            $taskCost = TaskModel::getCost($value);
            $yufujin= $value['price'] + $taskCost + $value['empty_cost'];
//            $yufujin= $value['price'] + $value['cost'] + $value['empty_cost'];        // 20180827

            // 金额处理
            if(!isset($user[$value['user_id']])){
                $user_info=D('user')->field('money')->where('uid='.$value['user_id'])->find();
                $user[$value['user_id']] = $user_info['money'];
            }
            $before_money = $user[$value['user_id']];
            $after_money=$before_money + $yufujin;
            $user[$value['user_id']] = $after_money;

            $balances=array(
                'uid'=>$value['user_id'],
                'before_money'=>$before_money,
                'after_money' =>$after_money,
                'change_money'=>$yufujin,
                'msg'=>5,
                'status'=>2,
                'tran_id'=>$value['id'],
                'addtime'=>date('Y-m-d H:i:s',time()),
            );
            $balances_status=D('balances')->add($balances);
            if(!$balances_status){
                M()->rollback();
                exit;
            }

            $yufujin_status=D('user')->where('uid='.$value['user_id'])->setDec('yufujin',$yufujin);
            $money_status=D('user')->where('uid='.$value['user_id'])->setInc('money',$yufujin);
            $task_status=D('task')->where("id =".$value['id'])->setField('xiajia',3);
            if($task_status===false || $money_status===false || $yufujin_status===false){
                M()->rollback();
                exit;
            }else{
                M()->commit();
            }
        }
        //产品下架
        $where_p="endtime <".time()." and status=0";
        $data=M('product')->where($where_p)->setField('status',4);
        echo "success".date('YmdHis').'\n';exit;

    }
    public function test_a(){
        D('test_a')->add(array('addtime'=>date('Y-m-d H:i:s',time())));
    }
}