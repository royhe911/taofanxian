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

class ManagerController extends BaseController {

	public function index(){
        //判断当前权限
        if( check_action() === false) {
            $this->error('无此权限');
            return false;
        }
		$page       = I('get.page',1);
		$page       = $page < 1 ? 0 : $page - 1;
		$page_size  = I('get.pagesize',10);
		$status     = I('get.status');
		$phone      = trim(I('get.phone'));
		$qq         = trim(I('get.qq'));
        $where      = 'a.id is not null';
        if( $status != 0){
            //显示所有用户
            $where  .=' and role = '.$status;
        }
        if(!empty($phone)){
            if($status == 5){
                $where .= " and b.iphone='{$phone}'";
            }else{
                $where .= " and a.name='{$phone}'";
            }

        }
        if(!empty($qq))      $where .= " and a.qq='{$qq}'";
		$count = D('ManagerView')->join('left join erp_user b on a.id=b.uid')->where($where)->count();
		$data  = D('ManagerView')->join('left join erp_user b on a.id=b.uid')->where($where)->order('addtime DESC')->limit($page*$page_size,$page_size)->select();
        //管理员商家列表
        foreach ( $data as $key => $value) {
            $data[$key]['wechat'] = json_decode($value['wechat'],true);
        }

        $role=D('role')->select();
        $this->assign('role',$role);
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $count));
		$this->assign('count', $count);
		$this->assign('phone', $phone);
		$this->assign('qq', $qq);
		$this->assign('status', $status);
		$this->assign('data', $data);
		$this->display();
	}

	//状态修改
    public function state(){
        if (IS_AJAX){
            $id = I('post.id',0,'intval');//信息ID
            $type=I('post.type',0,'intval');
            if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));

            if($type == 1){
                //禁用
                $res=D('Account')->where('id='.$id)->setField('msg',1);
            }else{
                //启用
                $res=D('Account')->where('id='.$id)->setField('msg',0);
            }

            if($res){
                $this->ajaxReturn(array('msg' => 1, 'info' => '操作成功'));
            }else{
                $this->ajaxReturn(array('msg' => 0, 'info' => '操作失败'));
            }
        }
    }

	public function del(){

		if (IS_AJAX){
			$id = I('post.id',0,'intval');//信息ID
			if (empty($id)) $this->ajaxReturn(array('msg' => 2, 'info' => '该信息不存在'));

			$msg = D('account')->where('id = '.$id)->delete();
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
				$msg = D('account')->where(array('id' => array('IN', $this->paramId($ids))))->delete();
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

	public function add(){

		if (IS_POST){
			$account = D('account');
            $data=$account->create();
			if(intval(I('post.adminRole') == 3)){
                $data['wechat']=json_encode(I('post.wechat'));
            }
            if(intval(I('post.adminRole') == 6)){
			    $data['wechat']=$_POST['wechat'][0];
            }
			if (!$data){
				$this->error($account->getError());
			}else{
				$msg = $account->add($data);
				if ($msg){
					$this->success('操作成功!', 'javascript:parent.location.reload();');
				} else {
					$this->error('操作失败!');
				}
			}
		} else {
			$data = D('role')->field('id, name')->where('msg = 0')->select();

			$this->assign('data', $data);
			$this->display();
		}
	}

	public function edit(){

		$id = I('id',0,'intval');//信息ID
		if (IS_POST){

			$account = D('account');
			$password=I('post.password');
            $data=$account->create();
            $user=$account->where('id='.$id)->find();
//            dump($_POST);exit;
            //判断密码
            if(strlen(I('post.password')) < 6) $this->error('密码不能少于6位数！');
            if ( I('post.password') == $user['password']) {
                //密码没有修改过
                $data['password']=I('post.password');
            }else{
                $data['password']=md5(I('post.password'));
            }

            if(intval(I('post.adminRole') == 3)){
                $data['wechat']=json_encode(I('post.wechat'));
            }
            if(intval(I('post.adminRole') == 6)){
                $data['wechat']=$_POST['wechat'][0];
            }
			if (!$data){
				$this->error($account->getError());
			}else{

				$msg = $account->save($data);
				if ($msg){
					$this->success('操作成功!', 'javascript:parent.location.reload();');
				} else {
				    $user_info=D('Account')->field('password')->where('id='.$id)->find();
				    if(md5($password) == $user_info['password']) $this->error('密码不能和以前一样');

					$this->error('操作失败!');
				}
			}
		} else {
			$data = D('role')->field('id, name')->where('msg = 0')->select();
			$info = D('account')->where(array('id' => $id))->find();
			if ( intval($info['role']) ==3 ) {
			    $info['wechat'] = json_decode($info['wechat'],true);
                $count=count($info['wechat']);

                $this->assign('count',$count);
                $this->assign('info', $info);
                $this->assign('data', $data);
                $this->display('editz');
			}else  {
			    $info['wechat'] = explode(';', $info['wechat']);

                $this->assign('info', $info);
                $this->assign('data', $data);
                $this->display();
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