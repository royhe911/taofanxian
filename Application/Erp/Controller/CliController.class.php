<?php
/**
 * Created by PhpStorm.
 * User: xcb91@qq.com
 * Date: 2018/6/11
 * Time: 17:04
 */

namespace Erp\Controller;


use Erp\Model\LogModel;
use Erp\Model\TaskModel;
use Think\Controller;
use Think\Exception;

class CliController extends Controller
{
    //自动下架
    public function auto_sold_out(){
        //任务下架
        $where_t = "a.tb_item is null and (a.xiajia = 1 or a.xiajia = 0) and a.endtime < ".time()." and (b.status=0 or b.status=3)";
        $taskAll = M('task a')->field('a.*,b.status')->join('left join erp_product b on a.gid=b.id')->where($where_t)->select();

        // 初始化商家用户
        $user[] = array();
        $success = array();
        $successGid = array();
        $fail = array();
        $failGid = array();
        $failMsg = array();

        if(!empty($taskAll)) {
            // 将任务按照产品进行分组
            foreach($taskAll as $val){
                $newTask[$val['user_id'].'-'.$val['gid']][] = $val;
            }

            foreach ($newTask as $userGid => $task) {
                M()->startTrans();
                $tmp = explode('-', $userGid);
                $userId = $tmp[0];          // 商家id
                $productId = $tmp[1];       // 产品id
                $balancesAll = array();     // 所有变动记录
                $totalChangeMoney = 0;      // 所有变更金额
                $taskId = array();          // 计算下架的任务id
                try{
                    // 一次性对一个产品的所有任务进行处理
                    foreach($task as $value){
                        $taskCost = TaskModel::getCost($value);
                        $yufujin = $value['price'] + $taskCost + $value['empty_cost'];

                        // 金额处理
                        if (!isset($user[$value['user_id']])) {
                            $user_info = D('user')->field('money')->where('uid=' . $value['user_id'])->find();
                            if(empty($user_info))throw new Exception('该任务用户未找到，任务id:'.$value['id'].',用户id：'.$value['user_id']);
                            $user[$value['user_id']] = $user_info['money'];
                        }
                        $before_money = $user[$value['user_id']];
                        $after_money = $before_money + $yufujin;
                        $user[$value['user_id']] = $after_money;

                        // 插入变动记录
                        $balancesAll[] = array(
                            'uid' => $value['user_id'],
                            'before_money' => $before_money,
                            'after_money' => $after_money,
                            'change_money' => $yufujin,
                            'msg' => 5,
                            'status' => 2,
                            'tran_id' => $value['id'],
                            'addtime' => date('Y-m-d H:i:s', time()),
                        );

                        $totalChangeMoney+= $yufujin;       // 计算本产品下架总金额
                        $taskId[] = $value['id'];
                    }
                    $balances_status = D('balances')->addAll($balancesAll);
                    if (!$balances_status) throw new Exception('新增变更记录失败');

                    $yufujin_status = D('user')->where('uid=' . $userId)->setDec('yufujin', $totalChangeMoney);
                    if ($yufujin_status == false) throw new Exception('扣减预付金失败');

                    $money_status = D('user')->where('uid=' . $userId)->setInc('money', $totalChangeMoney);
                    if ($money_status == false) throw new Exception('增加余额失败');

                    $task_status = D('task')->where("id in (" . implode(',', $taskId) .")")->setField('xiajia', 3);
                    if ($task_status === false) throw new Exception('修改任务为下架失败');

                    $rs = M('product')->where('id = ' . $productId)->setField('status', 4);
                    if (!$rs) throw new Exception('商品下架失败');

                    M()->commit();
                    $success = array_merge($success, $taskId);
                    $successGid[] = $productId;
                }catch (Exception $e){
                    M()->rollback();
                    $fail = array_merge($fail, $taskId);
                    $failGid[] = $productId;
                    $failMsg[] = $e->getMessage();
                }
            }
            $content = '脚本下架任务，总任务数：' . count($taskAll) . '。';
            $content .= '成功下架任务数：' . count($success) . '个(' . implode(',', $success) . ')';
//            $content .= '，成功商品数量：' . count($successGid) . '个(' . implode(',', $successGid) . ')。';
            if(!empty($failGid)){
                $content .= '下架失败任务数：' . count($fail) . '个(' . implode(',', $fail) . ')';
//                $content .= '，失败商品数量：' . count($failGid) . '个(' . implode(',', $failGid) . ')。';
                $content .= '失败明细：';
                foreach ($failGid as $k => $v) {
                    $content .= '(商品：' . $v . ',错误：' . $failMsg[$k] . '),';
                }
                $content = substr($content, 0, -1);
            }
        } else {
            $content = '脚本下架任务，未找到可下架任务。';
        }
        addLog(LogModel::TYPE_TASK_AUTO_DOWN_ALL, $content);
        echo "|success".date('Y-m-d H:i:s');
        exit;
    }
    public function test_a(){
        D('test_a')->add(array('addtime'=>date('Y-m-d H:i:s',time())));
    }
}