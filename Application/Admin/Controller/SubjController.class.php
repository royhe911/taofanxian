<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Servic\Servic;
use Common\Util\Util;
/**
 * @auth TRUE
 * @name 专题管理
 * @authsort 20
 * */
class SubjController extends InterceptController {

	public function index(){
		$page = I('get.page',1);
		$page = $page < 1 ? 0 : $page - 1;
		$page_size = I('get.pagesize',20);
		$data = M('subj')->limit($page*$page_size,$page_size)->select();
		$totalCount = M('subj')->count();
		$this->assign('pagination',  Util::getInstance('Pagination')->create( $page +1 , $page_size , $totalCount) );
		$this->assign('list',$data);
		$this->display();
	}

	public function editSubj(){
		$id = I('get.id',0);
		$news = M('subj')->where('id='.$id)->find();
		if(IS_POST){
			$name = I('post.name','');
			$seat_id = I('post.seat_id','');
			if(empty($name)){
				$this->error('专题名称不能为空！');
			}
			if(empty($seat_id)){
				$this->error('位置不能为空！');
			}
			$subj_name = M('subj')->where("name='".$name."'")->find();
			if($subj_name && $subj_name['name']!=$news['name']){
				$this->error('专题名称已存在！');
			}
			$subj_seat = M('subj')->where("seat_id='".$seat_id."'")->find();
			if($subj_seat && $subj_seat['seat_id']!=$news['seat_id']){
				$this->error('位置已经被占用！');
			}
			$data = array(
					'name'=>$name,
					'seat_id'=>$seat_id,
					'time'=>date('Y-m-d H:i:s')
			);
			$img_url = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
			$img_url = str_replace('\\','/',$img_url);
			if($_FILES['subj_img']['size']>0)
			{
				$subj_img = $this->upload($_FILES['subj_img'],'subj/',10240000,'','');
				if(empty($subj_img))
				{
					$this->error('上传失败！');
				}else {
					if(!empty($news['subj_img'])){
						unlink($img_url.'/subj/'.$news['subj_img']);
					}
					$data['subj_img'] = $subj_img['savepath'].$subj_img['savename'];
				}
				
				
			}
			
		   //广告图
		    $banner_img = $this->upload($_FILES['banner_img'],'subj/',10240000,$exts,'banner_img');
		    if($banner_img)
		    {
			    foreach($banner_img as $val)
			    {
			    	$data['banner_img'] .= $val['savepath'].$val['savename'].',';
			    }
			    if(!empty($news['banner_img']))
			    {
			    	$bannerimg = explode(',',$news['banner_img']); 
					$bannerimg = array_filter($bannerimg);
					foreach($bannerimg as $val)
					{
						unlink($img_url.'/subj/'.$val);
					}
			    }
		    }
			if( false === M('subj')->where('id='.$id)->save($data)){
				$this->error('系统错误，保存失败！');
			}else{
				$this->success('编辑成功！',U('Subj/index'));
			}
			
		}else{
			if(!$id){
				$this->error('系统错误');
			}
			$banner_img = explode(',',$news['banner_img']); 
			$banner_img = array_filter($banner_img);
			$this->assign('news',$news);;
			$this->assign('banner_img',$banner_img);
			$this->display();
		}
	}


	public function addSubj(){
			if(IS_POST){
			$name = I('post.name','');
			$seat_id = I('post.seat_id','');
			if(empty($name)){
				$this->error('专题名称不能为空！');
			}
			if(empty($seat_id)){
				$this->error('位置不能为空！');
			}
			$subj_name = M('subj')->where("name='".$name."'")->find();
			if($subj_name){
				$this->error('专题名称已存在！');
			}
			$subj_seat = M('subj')->where("seat_id='".$seat_id."'")->find();
			if($subj_seat){
				$this->error('位置已经被占用！');
			}
			$data = array(
					'name'=>$name,
					'seat_id'=>$seat_id,
					'time'=>date('Y-m-d H:i:s')
			);
			if($_FILES['subj_img']['size']>0)
			{
				$subj_img = $this->upload($_FILES['subj_img'],'subj/',10240000,'','');
				if(empty($subj_img))
				{
					$this->error('上传失败！');
				}
				$data['subj_img'] = $subj_img['savepath'].$subj_img['savename'];
				
			}
			
			//广告图
		    $banner_img = $this->upload($_FILES['banner_img'],'subj/',10240000,'','banner_img');
		    if($banner_img)
		    {
			    foreach($banner_img as $val)
			    {
			    	$data['banner_img'] .= $val['savepath'].$val['savename'].',';
			    }
		    }			
			
			
			if(!M('subj')->add($data)){
				$this->error('系统错误，添加失败！');
			}else{
				$this->success('添加成功！',U('Subj/index'));
			}
			}else{
				$this->display();
			}
	}
	
	//上传图片
	public function fab_upload($files,$savePath = '',$maxSize = 0,$exts = null)
	{
	        //判定文件信息是否为空
	    if(empty($files)){
	        return false;
	    }
	    if($exts === null){
	        $exts = array('jpg', 'gif', 'png', 'jpeg');
	    }else{
	        $exts = 0;
	    }
	    $tmp = array();
	    //将文件信息（数组）用foreach循环遍历，
	    foreach($files as $k => $v){
	                //判定文件大于0之后，将遍历value作为参数传入upload方法
	        if($v['size'] > 0){
	 
	            $res = $this->upload($v,$savePath,$maxSize,$exts,$k);
	                            //如果传入成功就会将文件存储路径传入数组$tmp[]之中
	            if($res){
	                $tmp[$k] = $res;
	            }
	        }
	    }
	            //将存储传入文件路径的数组return回去
	    return $tmp;
	}
	//文件上传类（可以设置多个参数）
	public function upload($file=null,$savePath='',$maxSize=0,$exts=0,$k)
	{
	    //调用
	    $upload = new \Think\Upload();// 实例化上传类
	    $upload->maxSize   = $maxSize;// 设置附件上传大小
	    $upload->exts      = $exts; //array('jpg', 'gif', 'png', 'jpeg'); 设置附件上传类型
	    $upload->savePath  = $savePath; // 设置附件上传目录
	    // 上传文件
	    //如果单个文件还是多个文件
	    if(!is_array($file['name'])){
	      $info = $upload->uploadOne($file);
	    }else{
	    	$upfile[$k]=$file;
	    	$info = $upload->upload($upfile);
	    }
	    //判定是否文件上传成功de
	    if(!$info) {
	        return false;
	    }else{
	    // 上传成功,
	        return $info;
	    }
	}	

	public function del(){
            $id = I('get.id');
		    if(!is_numeric($id) || empty($id)){
				 return '';
			}
			$data['status'] = '1';
		    if( false === M('news')->where('id='.$id)->save($data)){
				$this->error('系统错误，删除失败！');
			}else{
				$this->success('删除成功！',U('News/index'));
		  }
	}

}