<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/4/9
 * Time: 10:00
 */
  namespace Erp\Model;
  use Think\Model;

  // 2018-08-27
  Class TaskModel extends Model{
      // 获取任务服务费，这里要处理一下是因为尚未找到原因，为什么会出现任务服务费突然间为0的情况
  	static public function getCost($task){
        $taskCost = $task['cost'];
        if($taskCost == 0){ // 验证是否存在服务费
            $proAttr = D('ProductAttr')->where('id = ' . $task['sid'])->find();
            if(!empty($proAttr) && $proAttr['cost'] > 0){
                $taskCost = cost($task['price']);
            }
        }
        return $taskCost;
    }
  }
 ?>