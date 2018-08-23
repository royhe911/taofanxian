<?php
/**
 * Created by PhpStorm.
 * User: hubing
 * Date: 2018/3/31
 * Time: 15:00
 */
namespace Erp\Controller;
use Think\Controller;

class RoleController extends BaseController {
	
	public function index(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
		
		$count = D('role')->count();
		$data = D('column')->where('fid != 0 and msg = 0')->field('id,name')->select();
		if (!empty($data)){
			foreach ($data as $key => $val){
				$column[$val['id']] = $val['name'];
			}
		}

		$data = D('role')->select();
		if (!empty($data)){
			foreach ($data as $key => $val){
				if (!empty($val['status'])) $data[$key]['status'] = explode(',', $val['status']);
			}
		}

		$this->assign('column', $column);
		$this->assign('count', $count);
		$this->assign('data', $data);
		$this->display();
	}
	
	//状态修改
	public function state(){
		
		if (IS_AJAX){
			$id = I('post.id',0,'intval');//信息ID
			if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
			
			$where = array('id' => $id);
			$msg = D('role')->where($where)->getField('msg');
			$msg = (1 == $msg) ? 0 : 1;
			$return = D('role')->where($where)->save(array('msg' => $msg));
			if ($return){
				$this->ajaxReturn(array('msg' => 1, 'info' => $msg));
			} else {
				$this->ajaxReturn(array('msg' => 3, 'info' => '操作失败'));
			}
		}
	}
	
	//删除
	public function del(){
		
		if (IS_AJAX){
			$id = I('post.id',0,'intval');//信息ID
			if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));
			
			$msg = D('role')->where('id = '.$id)->delete();
			if ($msg){
				$this->ajaxReturn(array('msg' => 1, 'info' => '删除成功'));
			} else {
				$this->ajaxReturn(array('msg' => 3, 'info' => '删除失败'));
			}
		}
	}
	
	//批量删除
	public function delAll(){
		
		if (IS_AJAX){
			$ids = I('post.arr');
			if (!empty($ids)) {
				$msg = D('role')->where(array('id' => array('IN', $this->paramId($ids))))->delete();
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
	
	//权限
	public function authority(){
		
		$id = I('id',0,'intval');//信息ID
		if (IS_POST){
			$name = I('post.name');//角色名
			$ids  = I('post.ids');//权限ID集合
			
			$status = $this->getRole($ids);//返回完整权限值
			if (empty($status)) $this->error('数据异常');
			
			$data = array('name' => $name, 'status' => $status, 'addtime' => time());
			$role = D('role');
			if (!$role->autoCheckToken($_POST)) $this->error('令牌验证错误');//防止重复提交
			if (!empty($id)){
				$msg  = $role->where(array('id' => $id))->save($data);
			} else {
				$msg  = $role->add($data);
			}
			
			if ($msg){
				$this->success('操作成功!', 'javascript:parent.location.reload();');
			} else {
				$this->error('操作失败!');
			}
		} else {
			$data = D('column')->field('id,fid,msg,name')->select();
			if (!empty($data)){//栏目列表
				foreach ($data as $key => $val){
					$column[$val['fid']][] = $val;
				}unset($data);
			}
			
			if (!empty($id)){//角色信息
				$data = D('role')->where(array('id' => $id))->field('id,name,status')->find();
				$this->assign('data',$data);
			}
			$this->assign('column',$column);
			$this->display();
		}
	}
	
	//ID过滤
	private function paramId($ids){
		return implode(',', array_map(array($this,'idFilter'),$ids));
	}
	
	//回调函数
	private function idFilter($key){
		return intval($key);
	}
	
	//返回完整权限值
	private function getRole($ids){
		
		$msg = D('column')->where('id in ('.implode(',', $ids).')')->field('fid')->select();
		if (!empty($msg)){
			$msg = my_array_column($msg, 'fid');
			$msg = array_unique($msg);
			$ids = implode(',', $msg).','.implode(',', $ids);
			return $ids;
		}
	}
}
?>