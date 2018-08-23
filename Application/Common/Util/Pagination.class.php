<?php
namespace Common\Util;

class Pagination {
	
	public function create( $page , $size , $count ){
		
		 $totalPages = ceil($count / $size);
		 $totalPages = $totalPages < $page ? $page : $totalPages;
		
		 $startRecord = ($page - 1)  * $size + 1;
		 $endRecord = ($page - 1)  * $size + $size ;
		 if($endRecord > $count){$endRecord = $count;}
		 $pagination = array(
		    'baseUrl' =>U('','',false),
		 	'startPage' => $page,
		 	'totalPages' =>$totalPages,
		 	'startRecord'=>$startRecord,
		 	'endRecord'=>$endRecord,
		 	'totalCount'=>$count,
		    'pageSize'  =>$size,
		    'getParams' => I('get.'),
		    'first'=>'<<',
			'last'=>'>>',
			'prev'=>'<',
			'next'=>'>',
			'visiblePages'=>10,
			'initiateStartPageClick'=>false,	
		 );
		return $pagination;
		
	}
	
	
	
}

?>