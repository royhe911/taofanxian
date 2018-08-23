<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/10
 * Time: 9:00
 */

namespace Supporter\Controller;



class WechatController extends CheckAdminController
{
    function index(){
        $this->display();
    }

    function chatOne()
    {
        if ($_POST['statu'] == 2) {
            $arr = array(
                'token' => 'taofanxian',
                'encodingaeskey' => 'CNzl6hKTXP6KTawFhPWp8pTYsgxxldcFUMLAIJzIcWL',
                'appid' => 'wx4414de37fbdc9a3f',
                'appsecret' => 'f58f1297c1f1e3386803373514a8ef41',
            );

            $openid_arr = array();
            $next = '';
            while (true) {
                $rr = wxSendAll($arr, $next);
                $openid_arr = array_merge($openid_arr, $rr['data']['openid']);
                $next = $rr['next_openid'];
                if ($rr['count'] < 10000) {
                    break;
                }
                unset($rr);
            }
            $openid_arr = array_unique(array_values($openid_arr));

            M()->query(" CREATE  TABLE  IF NOT EXISTS `tfx_table` (
                        `id` int(11) NOT NULL auto_increment,
                       `value` VARCHAR(50) NOT NULL,
                       PRIMARY KEY (`id`)
                    )ENGINE=MEMORY DEFAULT CHARSET=utf8;");
            $new_arr = array();
            foreach ($openid_arr as $k1 => $v1) {
                $new_arr[]['value'] = $v1;
            }
            M('table')->query('TRUNCATE table `tfx_table`');
            M('table')->addAll($new_arr);
            $zz = M('table')->count();
//            echo $zz;
            $this->ajaxReturn(array('code' => 1, 'num' => $zz));
        }
    }


    function Ajaxhttp(){
        if (IS_POST){
        $arr=array(
            'token'=>'taofanxian',
            'encodingaeskey'=>'CNzl6hKTXP6KTawFhPWp8pTYsgxxldcFUMLAIJzIcWL',
            'appid'=>'wx4414de37fbdc9a3f',
            'appsecret'=>'f58f1297c1f1e3386803373514a8ef41',
        );
        $Msg = new \Org\Net\Wechat($arr);
        $bn=$_POST['num'];
        $en=$_POST['num']+30;
       $open= M('table')->limit($bn.','.$en)->select();
        foreach ($open as $v){
            $data='{
                                      "touser":"' . $v['value']. '",
                                      "template_id":"8suFBy7ZCLuZOZ7mtmoDbIADbiP9otyt1ZqrOZKG1x4",
                                      "url":"http://www.taofanxian.com/m/index.php?mod=miandangoods&act=index",
                                      "miniprogram":{
                                        "appid":"",
                                        "pagepath":""
                                      },
                                      "data":{
                                              "first": {
                                                  "value":"测试",
                                                  "color":"#333"
                                              },
                                               "keyword1": {
                                                  "value":"测试",
                                                  "color":"#333"
                                              },
                                              "keyword2":{
                                                  "value":"测试",
                                                  "color":"#333"
                                              },
                                              "remark":{
                                                  "value":"立即参与活动",
                                                  "color":"#333"
                                              }
                                      }
                                  }';
            $Msg->sendTemplateMessage($data);
        }
        if (count($open)<30){
            $this->ajaxReturn(array('code'=>0,'num'=>$en));
        }else{
            $this->ajaxReturn(array('code'=>1,'num'=>$en));
        }
        }
    }
}