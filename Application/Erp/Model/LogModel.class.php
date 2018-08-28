<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/4/9
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model;

  // 2018-08-287
    Class LogModel extends Model{
        const TYPE_TASK_DOWN_ALL = 1;       // 日志类型 - 批量下架
        const TYPE_TASK_APPLY_DOWN_ALL = 2; // 日志类型 - 申请批量下架
        const TYPE_PRODUCT_ADD = 3;         // 日志类型 - 发布任务
        const TYPE_PRODUCT_UPDATE = 4;         // 日志类型 - 修改任务
        const TYPE_PRODUCT_PAY = 5;         // 日志类型 - 任务付款
        const TYPE_CONFIRM_PRODUCT_PASS = 6;    // 日志类型 - 商品审核通过
        const TYPE_CONFIRM_PRODUCT_FAIL = 7;    // 日志类型 - 商品审核不通过
        const TYPE_TASK_APPLY_DOWN = 8; // 日志类型 - 申请下架
        const TYPE_TASK_APPLY_DOWN_PASS = 9; // 日志类型 - 申请下架通过
        const TYPE_TASK_APPLY_DOWN_FAIL = 10; // 日志类型 - 申请下架拒绝
        const TYPE_TASK_APPLY_DOWN_ALL_PASS = 11; // 日志类型 - 申请批量下架通过
        const TYPE_TASK_APPLY_DOWN_ALL_FAIL = 12; // 日志类型 - 申请批量下架拒绝
        const TYPE_APPLY_CASH = 13; // 日志类型 - 申请提现
        const TYPE_APPLY_CASH_PASS = 14; // 日志类型 - 申请提现审核通过
        const TYPE_APPLY_CASH_FAIL = 15; // 日志类型 - 申请提现审核不通过

        /**
         * 新增日志
         * @param integer   $type       日志类型
         * @param string    $content    日志内容
         */
        public function createLog($type, $content){
            $data = array(
                'create_time' => time(),
                'user_id' => $_SESSION['user']['id'],
                'user_name' => $_SESSION['user']['name'],
                'type' => $type,
                'content' => $content
            );
            D('Log')->add($data);
        }
    }
 ?>