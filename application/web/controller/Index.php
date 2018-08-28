<?php
namespace app\web\controller;
use think\Controller;
use think\Session;
use think\Loader;
Loader::import('appts.autoload');
use JPush\Client as JPush;
class Index extends Common
{
	//回调结果处理
    public function payresult($ordernum){
        $orderinstall = model("orderinstall")->where("ordernum",$ordernum)->find();
        if($orderinstall){
            $oupdate["paystat"] = $update["paystat"] = 1;
            $oupdate["paytime"] = $update["paytime"] = date("Y-m-d H:i:s");
            // model("orderinstall")->insert(["ordernum"=>$ordernum,"paytime"=>date("Y-m-d H:i:s")]);   
            $result = model("orderinstall")->where("ordernum",$ordernum)->update($update);
            if($result){
                if($orderinstall["type"]==1){
                    $result = model("order")->where("rs",$orderinstall["rs"])->update($oupdate);
                    // model("Base")->CreateUserLog("全款支付成功","用户[".$orderinstall["phone"]."]对订单[".$orderinstall["rs"]."]进行全款支付操作");
                }else{
                    // model("Base")->CreateUserLog("还款成功","用户[".$orderinstall["phone"]."]支付订单[".$orderinstall["rs"]."]第[".$orderinstall["qishu"]."]期");
                }
                $ordermoney = model("orderinstall")->where("ordernum",$ordernum)->sum("money");
                $order = model("userorder")->getbyrs($orderinstall["rs"]);
                $temp = explode("_",$orderinstall["rs"]);//获取保单编号  
                $ordernum = $temp[0].hexdec($temp[1]);//获取保单时间                   
                $tempdata = array(
                    $order["apply_phone"],//手机号码0
                    $order["car_name"],//姓名1
                    $order["car_license"], //车牌号码2
                    $ordernum,//订单号3
                    $ordermoney,//分期金额4
                );	
                $tresult = model("message")->getmsg("orderpay",$tempdata);//消息相关处理  
            }  
        }else{
            $recharge = model("userrecharge")->where("ordernum",$ordernum)->find();
            if($recharge){
                $update = array();
                $update["paystat"] = 1;
                $update["paytime"] = date("Y-m-d H:i:s");
                $update["payclient"] = $_SERVER['HTTP_USER_AGENT'];
                $update["payip"] = request()->ip();
                $result = model("userrecharge")->where("ordernum",$ordernum)->update($update);
                if($result){
                    $update = array();
                    $user = model("User")->getbyid($recharge["uid"]);
                    $update["money_free"] = $user["money_free"] + $recharge["money"];
                    $result = model("User")->where("id",$recharge["uid"])->update($update);
                    return $result;
                }
            }else{
                $agentorder = model("agentorder")->where("order_id",$ordernum)->find();
                $update["paystat"] = 1;
                $update["paytime"] = date("Y-m-d H:i:s");
                $update["stat"] = 4;
                $result = model("agentorder")->where("order_id",$ordernum)->update($update);
                return $result;                
            }
        }
        return $result;
    }   
    public function index()
    {
        $code = input("get.code");
        $oauth = & load_wechat('Oauth');
        $result = $oauth->getWebOauthAccessToken($code);
        $unionid = $result["unionid"];
        $text = file_get_contents("php://input");     
        if($text){
            // model("orderinstall")->insert(["test"=>$text]);        
            $array = model("Wechatpay")->XMLDataParse($text);
            $sign = $array["sign"];
            unset($array["sign"]);
            if($array["result_code"]=="SUCCESS"&&$array["return_code"]=="SUCCESS"){
                // $checksign = model("Wechatpay")->produceWeChatSign($array);
                // if($checksign==$sign){
                    $result = $this->payresult($array["out_trade_no"]);   
                    if($result){
                        echo exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>'); 
                    }                    
                // }
            }
        }
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        //合作单位
        $cooperation = model("partner")->where('status',1)->order('sort,id desc')->select();
        //咨询中心
        $consult =  model("consultmain")
                ->alias('a')->order('a.id desc')
                ->where('b.status = 1')
                ->join('__CONSULT__ b','a.rid = b.id')
                ->field('a.*')
                ->limit(5)
                ->select();
        foreach ($consult as $key => &$val) {
            $val['addtime'] = strtotime($val['addtime']);
            $val['main'] = cutstr_html($val['main']);
           
        }
        //公司简介
        $abstract = model('aboutus')->where('title','公司简介')->find();
        $abstract->main = cutstr_html($abstract->main);
        return view('index/index',["user" => $user,'cooperation'=>$cooperation,'consult'=>$consult,'abstract'=>$abstract , 'unionid'=>$unionid]);
    }
    public function test(){
        //极光推送测试
        // echo config('JG_app_key')."<br>";
        // echo config('JG_master_secret')."<br>";
		// 	$app_key = config('JG_app_key');
		// 	$master_secret = config('JG_master_secret');
        // $client = new JPush($app_key, $master_secret);
        // // echo $app_key;exit;
        // $pusher = $client->push();
        // $pusher->setPlatform(array('ios', 'android'));
        // $phone = "18850221022";
        // $pusher->addAlias(array($phone));
        // // $pusher->addAllAudience();
        // // $pusher->setNotificationAlert('Hello, JPush');
        // $pusher->iosNotification('Hello IOS', array(
        //     'badge' => '0'
        // ));
        // $pusher->androidNotification('Hello Android');
        // $pusher->options(array(
        //      'apns_production' => true,
        // ));
        // try {
        //     $pusher->send();
        // } catch (\JPush\Exceptions\JPushException $e) {
        //     // try something else here
        //     print $e;
        // }
        //微信红包测试
        $arr['openid']='oV_Xfvw0_dtdbQ9T6PkERSmViuOY';
        $arr['hbname']="提现申请";
        $arr['body']="您的提现申请已经成功";
        $arr['fee']=0;
        $re = model("wechatred")->sendhongbaoto($arr);
        var_dump($re);        
    }
    public function head()
    {
        return view('index/head');
    }
    //报价
    public function inquiry()
    {
        $post = input("post.");
        if(!$post){
            $post = array("apply_phone"=>'',"car_license"=>'',"isNewcar"=>'',"car_name"=>'',"insurerid"=>'');
        }
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $config = model("config")->find();
        $oredrset = json_decode($config["orderset_value"],true);
        return view('index/inquiry',["user" => $user , "post" => $post , "oredrset" => $oredrset ]);
    }
    //分期身份认证
    public function buy()
    {
        $id = input("get.id");
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        return view('index/buy',["user" => $user,"id" => $id]);
    }
    //分期运营商认证
    public function phoneauth()
    {
        $id = input("get.id");
        $config = model("config")->find();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        return view('index/phoneauth',["user" => $user,"id" => $id,"config" => $config]);
    }

}
