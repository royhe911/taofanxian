<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;

class ColumnController extends BaseController {
	
	public function index(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
		
		$count = D('column')->where('msg = 0')->count();
		$data  = D('column')->where('msg = 0')->order('sort DESC')->select();
		if (!empty($data)){
			foreach ($data as $key => $val){
				$column[$val['fid']][] = $val;
			}unset($data);
		}
		
		$this->assign('count', $count);
		$this->assign('column', $column);
		$this->display();
	}
	
	
	public function op(){
		
		$id = I('id',0,'intval');//信息ID
		if (IS_POST){
			$column = D('column');
			if (!$column->create()){
				$this->error($column->getError());
			} else {
				!empty($id) ? $msg = $column->save() : $msg = $column->add();
				if ($msg){
					$this->success('操作成功!', 'javascript:parent.location.reload();');
				} else {
					$this->error('操作失败!');
				}
			}
		} else {
			if (!empty($id)) $info = D('column')->where(array('id' => $id))->find();
			$data = D('column')->where(array('fid' => 0, 'msg' => 0))->field(array('id', 'name'))->select();
			
			$this->assign('data', $data);
			$this->assign('info', $info);
			$this->display();
		}
	}
	
	//删除
	public function del(){
		
		if (IS_AJAX){
			$id = I('post.id',0,'intval');//信息ID
			if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
			
			$count = D('column')->where(array('fid' => $id))->count();
			if ($count){
				$this->ajaxReturn(array('msg' => 3, 'info' => '该栏目下有子栏目，不可删除'));
			} else {
				$msg = D('column')->where('id = '.$id)->delete();
				if ($msg){
					$this->ajaxReturn(array('msg' => 1, 'info' => '删除成功'));
				} else {
					$this->ajaxReturn(array('msg' => 3, 'info' => '删除失败'));
				}
			}
		}
	}
	
	//批量删除
	public function delAll(){
		
		if (IS_AJAX){
			$ids = I('post.arr');
			if (!empty($ids)) {
				$msg = D('column')->where(array('id' => array('IN', $this->paramId($ids))))->delete();
				if ($msg){
					$this->ajaxReturn(array('msg' => 1, 'info' => '删除成功'));
				} else {
					$this->ajaxReturn(array('msg' => 3, 'info' => '删除失败'));
				}
			} else {
				$this->ajaxReturn(array('msg' => 2, 'info' => '请勾选数据'));
			}
		}
	}
	
	//ID过滤
	private function paramId($ids){
		return implode(',', array_map(array($this,'idFilter'),$ids));
	}
	
	private function idFilter($key){
		return intval($key);
	}
}
?>