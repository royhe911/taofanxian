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
use Common\Util\Snoopy;

class ChecktbController extends BaseController {

	public function index()
	{
		 $this->display();
	}
	
	public function checkuser($nick=false)
	{
		
		$nick = empty($nick)?I('post.nick',''):$nick;
		if (!empty($nick))
		{
			
			$snoopy = new Snoopy();
			$snoopy->agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.27 Safari/537.36';
			
			$token = file_get_contents("Application/Erp/Common/cookie.txt");

			
			$snoopy->rawheaders["COOKIE"] = "topic=1; cna=g/Y/E3m4sCICAX15Ie2W0tgs; nickname=%5Cu5355%5Cu513F%5Cu54EA%5Cu91CC%5Cu8DD1; token=".$token."; role=HIGH_VIP"; 
			$snoopy->referer = 'http://www.bashenling.com'; //伪造来源地址
		    $list['nick'] = $nick;
			$posturl = 'http://www.bashenling.com/jsyh.htm';
			$snoopy->submit($posturl,$list);
			
			$userdata = $snoopy->results;
			$user = json_decode($userdata,true);
			if($user['error_message']=='未登录或者登陆超时了,请先登陆哦!')
			{
				$this->getcookie();
				$this->checkuser($nick);
			}elseif(empty($user['error_message'])){
				//保存数据到数据表$user
				$data=array(
				    //基本信息
				    'pic'                   =>  $user['sellerInfo']['headUrl'],                        //头像
				    'nick'                  =>  $user['sellerInfo']['nick'],                           //昵称
                    'sex'                   =>  $user['sellerInfo']['sex'],                            //性别
                    'age'                   =>  $user['sellerInfo']['sellerAge'],                      //年龄
                    'ip_time'               =>  $user['sellerInfo']['ipTimes'],                        //ip次数
                    'pic_url'               =>  $user['sellerInfo']['honorPicUrl'],                    //信用图
                    'huabei'                =>  $user['sellerInfo']['huabei'],                         //花呗
                    'address'               =>  $user['sellerInfo']['registerAddr'],                   //登入地址
                    'shiming'               =>  $user['sellerInfo']['shiMing'] == '实名认证' ? 1 : 0,   //实名制
                    'addtime'               =>  date('Y-m-d H:i:s',time()),                            //添加时间
                    //积分等级
                    'tmallPoint'            =>  $user['sellerInfo']['tmallPoint'],                     //天猫积分
                    'vipLevl'               =>  $user['sellerInfo']['vipLevl'],                        //会员等级
                    'reputationDesc'        =>  $user['sellerInfo']['reputationDesc'],                 //信誉评级
                    'honorPoint'            =>  $user['sellerInfo']['honorPoint'],                     //买家信用累计积分
                    //喜好数据
                    'collectGoods'          =>  $user['sellerInfo']['collectGoods'],                   //收藏店铺
                    'collectShop'           =>  $user['sellerInfo']['collectShop'],                    //收藏宝贝
                    'everGo'                =>  $user['sellerInfo']['everGo'],                         //足迹
                    //购物数据
                    'waitPay'               =>  $user['sellerInfo']['waitPay'],                        //待付款
                    'waitConsign'           =>  $user['sellerInfo']['waitConsign'],                    //代发货
                    'waitSign'              =>  $user['sellerInfo']['waitSign'],                       //待收货
                    'waitRate'              =>  $user['sellerInfo']['waitRate'],                       //待评价
                    'inRefund'              =>  $user['sellerInfo']['inRefund'],                       //退款中
                    'refundRate'            =>  $user['sellerInfo']['refundRate'],                     // 近3月售中退款率
                    'threeMonthOrder'       =>  $user['sellerInfo']['threeMonthOrder'],                // 近3月售中总单量
                    //淘龄&挥霍明细
                    'taoAge'                =>  $user['sellerInfo']['taoAge'],                         //淘龄
                    'startDate'             =>  $user['sellerInfo']['startDate'],                      //开号
                    'useAccount'            =>  $user['sellerInfo']['useAccount'],                     //挥霍金额
                    'orderNum'              =>  $user['sellerInfo']['orderNum'],                       //笔数
                    'countDays'             =>  $user['sellerInfo']['countDays'],                      //散财天数
                    'countLikes'            =>  $user['sellerInfo']['countLikes'],                     //点赞总数
                    'countCity'             =>  $user['sellerInfo']['countCity'],                      //占领城市
                    'averagePay'            =>  $user['sellerInfo']['averagePay'],                     //客单价
                    //淘气值
                    'score'                 =>  $user['sellerInfo']['score'],                          //淘气值
                    'tradeScore'            =>  $user['sellerInfo']['tradeScore'],                     //淘气值
                    'bonusScore'            =>  $user['sellerInfo']['bonusScore'],                     //奖励分
                    'basicScoreLast'        =>  $user['sellerInfo']['basicScoreLast'],                 //基础分
                    'buyMoneyConvertCur'    =>  $user['sellerInfo']['buyMoneyConvertCur'],             //本期
                    'tradeBonusScore'       =>  $user['sellerInfo']['tradeBonusScore'],                //购物奖励分
                    'buyMoneyConvertLast'   =>  $user['sellerInfo']['buyMoneyConvertLast'],            //上期
                    'interactBonusScoreLast'=>  $user['sellerInfo']['interactBonusScoreLast'],         //互动奖励分
                    'qqhBonusScore'         =>  $user['sellerInfo']['qqhBonusScore'],                  //亲情奖励分

                );
				//最近购买
                $purchase       =   array_slice($user['sellerInfo']['buyedGoodsList'],0,10);
                //信誉明细  评价
                //好评
                $goodsJudgeVO   =   $user['sellerInfo']['goodsJudgeVO'];

                //中评
                $normalJudgeVO  =   $user['sellerInfo']['normalJudgeVO'];
                //差评
                $badJudgeVO     =   $user['sellerInfo']['badJudgeVO'];
                //总评
                $totalJudgeVO   =   $user['sellerInfo']['totalJudgeVO'];

				$buy=D('BuyInfo')->where(array('nick'=>$user['sellerInfo']['nick']))->find();
				if($buy){
                    $res=D('BuyInfo')->where(array('nick'=>$user['sellerInfo']['nick']))->setField($data);
                         D('BuyPurchase')->where(array('buy_info_id'=>$buy['id']))->delete();
                         D('BuyCredit')->where(array('buy_info_id'=>$buy['id']))->delete();

                }else{
                    $res=D('BuyInfo')->add($data);
                }
                $buy_info_id=$buy ? $buy['id'] : $res;
                $buy_credit=array(
                    //好评
                    array(
                        'judgeVO'=>1,
                        'week'=>$goodsJudgeVO['week'],
                        'oneMonth'=>$goodsJudgeVO['oneMonth'],
                        'sixMonth'=>$goodsJudgeVO['sixMonth'],
                        'beforSixMonth'=>$goodsJudgeVO['beforSixMonth'],
                        'total'=>$goodsJudgeVO['total'],
                        'buy_info_id'=>$buy_info_id,
                    ),
                    //中评
                    array(
                        'judgeVO'=>2,
                        'week'=>$normalJudgeVO['week'],
                        'oneMonth'=>$normalJudgeVO['oneMonth'],
                        'sixMonth'=>$normalJudgeVO['sixMonth'],
                        'beforSixMonth'=>$normalJudgeVO['beforSixMonth'],
                        'total'=>$normalJudgeVO['total'],
                        'buy_info_id'=>$buy_info_id,
                    ),
                    //差评
                    array(
                        'judgeVO'=>3,
                        'week'=>$badJudgeVO['week'],
                        'oneMonth'=>$badJudgeVO['oneMonth'],
                        'sixMonth'=>$badJudgeVO['sixMonth'],
                        'beforSixMonth'=>$badJudgeVO['beforSixMonth'],
                        'total'=>$badJudgeVO['total'],
                        'buy_info_id'=>$buy_info_id,
                    ),
                    //总共
                    array(
                        'judgeVO'=>4,
                        'week'=>$totalJudgeVO['week'],
                        'oneMonth'=>$totalJudgeVO['oneMonth'],
                        'sixMonth'=>$totalJudgeVO['sixMonth'],
                        'beforSixMonth'=>$totalJudgeVO['beforSixMonth'],
                        'total'=>$totalJudgeVO['total'],
                        'buy_info_id'=>$buy_info_id,
                    ),

                );


                foreach ($purchase as $key =>$value){
                    $purchase[$key]['buy_info_id'] = $buy_info_id;
                }
                $purchase_res = D('BuyPurchase')->addAll($purchase);
                $credit_res   = D('BuyCredit')->addAll($buy_credit);

			}
			echo $userdata;
			
		}
		
		
	  
   
	}
	
	public function getcookie()
	{
		$snoopy = new Snoopy();
		$snoopy->agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.27 Safari/537.36';
	    $params['userName'] = '18138241222';
		$params['password'] = 'maoke123456';
		$data['data'] = json_encode($params);
		$posturl = 'http://www.bashenling.com/login.htm';
		$snoopy->submit($posturl,$data);
		//print_r($snoopy->results);//获取结果
		$Cookie = $snoopy->setcookies();
		$myfile = fopen("Application/Erp/Common/cookie.txt", "w+") or die("Unable to open file!");
		fwrite($myfile, $Cookie->cookies['token']);
	}
}
?>