<?php

namespace app\api\controller;
use \think\Request;
use think\Session;
use think\Loader;
class Index
{
    public function __construct()
    {
        $action = request()->action();
        if($action != "phonecallback" && $action != "getphonedata" && $action != "test" && $action != "qrcode"){
            if(!request()->isPost()){
                header("Content-type: application/json; charset=utf-8");
                $json["error"] =  "非法登录";
                echo json_encode($json);
                exit;
            }         
        }
    }

    public function test(){
        // $html = "12312312";
		// Loader::import('tcpdf.tcpdf');
		// $pdf = new \tcpdf('A4-L');
		// $pdf->setfont('stsongstdlight','', 10);
		// $pdf->AddPage();
		// $pdf->writeHTML($html, true, false, true, false, '');
		// $path = ROOT_PATH . 'public' . DS . 'uploads/contract/1.pdf';
		// $pdf->Output($path, 'f');
    }
	
     public function qrcode($phone,$level=3,$size=4){
		 $url = "http://".config('DOMAIN_URL')."/wechat/?phone=".$phone;
		  Vendor('phpqrcode.phpqrcode');
		  $errorCorrectionLevel =intval($level) ;//容错级别 
		  $matrixPointSize = intval($size);//生成图片大小 
			//生成二维码图片 
		  //echo $_SERVER['REQUEST_URI'];
		  $object = new \QRcode();
		  $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);   
		  exit();
     }	

    public function _empty()
    {
        return 'API错误，请核对参数';
    }

    //获取配置文件
    public function getconfig(){
		$sessionphone = Session::get('user_phone');
        $config = model("config")->find();
        if($config){
			$config = $config->toArray();
			if($sessionphone){
				$temp = json_decode($config["share_value"],true);
				$temp["wxurl"] = $temp["wxurl"]."/?phone=".$sessionphone;
				$config["share_value"] = json_encode($temp);
			}
            return json_encode($config);
        }else{
            return 0;
        }
    }
        
    //个人中心
    public function index()
    {
        $Base_model = model("Base");
        $express = $Base_model->getexress("888888");//
        $express = json_decode($express,true);
        $info = model("User")->where("phone",Session::get('user_phone'))->field('username,wxavatar,realname,phone,carlicense,wxopenid,wxnickname,bdwx,qqopenid,qqnickname,bdqq,qqavatar,money_free')->find();
        return json_encode($info);
    }


    //微信支付回调
    public function wxpayresult(){
        $text = file_get_contents("php://input");
        // model("orderinstall")->insert(["test"=>$text]);
        $array = model("Wechatpay")->XMLDataParse($text);
        $sign = $array["sign"];
        unset($array["sign"]);
        if($array["result_code"]=="SUCCESS"&&$array["return_code"]=="SUCCESS"){
            $checksign = model("Wechatpay")->produceWeChatSign($array);
            if($checksign==$sign){
                $result = $this->payresult($array["out_trade_no"]);   
                if($result){
                    echo "success";
                }                    
            }
        }
    }    
    //支付宝支付回调
    public function alipayresult(){
		$post = input("post.");	
        if($post["trade_status"]=='TRADE_FINISHED' || $post['trade_status'] == 'TRADE_SUCCESS'){
            $result = $this->payresult($post["out_trade_no"]);  
            if($result){
                echo "success";
            }
        }     
    }

	//回调结果处理
    public function payresult($ordernum){
        $orderinstall = model("orderinstall")->where("ordernum",$ordernum)->find();
        if($orderinstall){
            $oupdate["paystat"] = $update["paystat"] = 1;
            $oupdate["paytime"] = $update["paytime"] = date("Y-m-d H:i:s");
            $update["payclient"] = $_SERVER['HTTP_USER_AGENT'];
            $update["payip"] = request()->ip();
            $result = model("orderinstall")->where("ordernum",$ordernum)->update($update);
            if($result){
                if($orderinstall["type"]==1){
                    $result = model("order")->where("rs",$orderinstall["rs"])->update($oupdate);
                    // model("Base")->CreateUserLog("全款支付成功","用户[".$orderinstall["phone"]."]对订单[".$orderinstall["rs"]."]进行全款支付操作");
                }else{
                    // model("Base")->CreateUserLog("还款成功","用户[".$orderinstall["phone"]."]支付订单[".$orderinstall["rs"]."]第[".$orderinstall["qishu"]."]期");
                }
                $order = model("userorder")->getbyrs($orderinstall["rs"]);
                $temp = explode("_",$orderinstall["rs"]);//获取保单编号  
                $ordernum = $temp[0].hexdec($temp[1]);//获取保单时间    
                $ordermoney = model("orderinstall")->where("ordernum",$ordernum)->sum("money");               
                $tempdata = array(
                    $order["apply_phone"],//手机号码0
                    $order["car_name"],//姓名1
                    $order["car_license"], //车牌号码2
                    $ordernum,//订单号3
                    $ordermoney,//分期金额4
                    $order["id"]
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

    //登录
    public function login(){
        $phone = input("post.phone");
        $openid = input("post.unionid");
        $type = input("?post.type")?input("post.type"):0;
        if($type==1){
            $where["qqopenid"] = $openid;   
        }else{
            $where["wxunionid"] = $openid;
        }
        if($openid){
            $user = model("User")->where($where)->find();
            if($user){

                session('user_phone',$user["phone"]);
                session('user_name',$user["username"]);
                model("Base")->CreateUserLog("用户登录","用户[".$user["phone"]."][".$user["username"]."]登录用户中心");
                //更新用户登录状态
                $update["logincount"] = $user["logincount"] + 1;
                $update["last_ip"] = request()->ip();
                $update["last_client"] = $_SERVER['HTTP_USER_AGENT'];
                $update["last_logintime"] = date("Y-m-d H:i:s");
                $result = model("User")->where("phone",$phone)->update($update);                
                return 1;
            }
        }
        $password = input("post.password");
        $issession = input("post.issession");
        $user = model("User")->where("phone",$phone)->find();
        if($user){
            if(md5($password)==$user["password"]||$issession==1){
                session('user_phone',$phone);
                session('user_name',$user["username"]);
                model("Base")->CreateUserLog("用户登录","用户[".$user["phone"]."][".$user["username"]."]登录用户中心");
                //更新用户登录状态
                $update["logincount"] = $user["logincount"] + 1;
                $update["last_ip"] = request()->ip();
                $update["last_client"] = $_SERVER['HTTP_USER_AGENT'];
                $update["last_logintime"] = date("Y-m-d H:i:s");
                $result = model("User")->where("phone",$phone)->update($update);
                return 1;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    //获取物流信息
    public function getexpress(){
        if (request()->isPost()){
            $express_id = input("post.express_id");
            return model("Base")->getexpress($express_id);
        }
    }    
	
    //微信登录
    public function checkopenid(){
        $openid = input("post.unionid");
        $type = input("?post.type")?input("post.type"):0;
        if($type==1){
            $where["qqopenid"] = $openid;   
        }else{
            $where["wxunionid"] = $openid;
        }
        $user = model("User")->where($where)->find();
        if($user){
            return $user["phone"];
        }else{
            return 0;
        }
    }	
    
    //注销 
    public function loginout(){
        session_unset();
        Session::clear();
    }
    

    //获取短信验证码
    public function sms_sendcode(){
        $post = input("post.");
        $phone = $post["apply_phone"];
        $tos = $post["apply_phone"];
        if(!$tos||!$phone){
            $returntxt["msg"] = "请填写手机号码";
            return $returntxt;
        }
        $randcode = mt_rand(100000,999999);
        $where = "UNIX_TIMESTAMP(expiredtime)>UNIX_TIMESTAMP(now()) and stat=1 and phone='".$phone."'";
        $coderesult = model("smscode")->where($where)->order("id desc")->find();
        if($coderesult){
            $randcode = $coderesult["smscode"];
        }else{
            $result = model("smscode")->where("phone",$phone)->update(["stat"=>0]);
            $result = model("Base")->CreateSmscode($randcode,$phone);
        }
        $msg = "您的验证码是".$randcode.",5分钟内有效.如非本人操作请忽略本短信";
        $returntxt  = model("Base")->smssend($tos,$msg);
        //$returntxt = '{"code":"10000","charge":false,"msg":"查询成功,扣费","result":""}';
        $returntxt = json_decode($returntxt,true);  
        // print_r($returntxt); 
        if($returntxt["code"]=="10000"){
            return 1;
        }else{
            return $returntxt;
        }  
    }   

    
    //注册
    public function reg()
    {
		// echo 1;exit;
        $post = input("post.");
        $phone = $post["phone"] = $post["reg_phone"] = $post["apply_phone"];
        $post["reg_client"] = $_SERVER['HTTP_USER_AGENT'];
        if(input("?post.type")){
            $type = input("?post.type")?$post["type"]:0;
            if($type==1){
                $post["qqopenid"] = $post["wxopenid"];
                $post["qqunionid"] = $post["wxunionid"];
                $post["qqnickname"] = $post["wxnickname"];
                $post["qqavatar"] = $post["wxavatar"];
                $post["bdqq"] = 1;
                $post["bdqqtime"] = date("Y-m-d H:i:s");
                unset($post["wxopenid"],$post["wxunionid"],$post["wxnickname"],$post["wxavatar"]);
            }else{
                $post["bdwx"] = 1;
                $post["bdwxtime"] = date("Y-m-d H:i:s");
            }
        }
        //验证短信验证码
        $smscode = $post["smscode"];
        $result = model("Base")->smscheck($phone,$smscode);
        if($result!=1){return $result;}
        //数据处理
        $post["password"] = md5(trim($post["password"]));
        $post["addtime"] = date("Y-m-d H:i:s");
        $post["username"] = $phone;
        unset($post["apply_phone"],$post["smscode"],$post["type"]);        

        $Base_model = model("Base");
        $info = model("User")->getByPhone($post["phone"]);
        if(!$info){
            $post["phone_core"] = $Base_model->getProvince($post["phone"])."省";
            $result = model("User")->insert($post);   
            $uid = model("User")->getLastInsID();
            session('user_phone',$phone);
            session('user_name',$phone);
            //注册成功判断是否有推荐人
            $config = model("config")->find();
			// print_r($config['red_value']);exit;
            $red_vaule = $config["red_value"];
            // $red_vaule = json_decode($red_vaule,true);
            //注册奖励
            $redary = array(
                "redtype" => 0,
                "money" => $red_vaule[0],
                "uid" => $uid,
                "comphone" => "",
                "content" => ""                
            );
            // $result = model("Wechatred")->sendred($redary);
            //发送推荐红包
            if(isset($post["reco_phone"])){
                $user = model("User")->getbyphone($post["reco_phone"];
                $redary = array(
                    "redtype" => 1,
                    "money" => $red_vaule[1],
                    "uid" => $user["id"],
                    "comphone" => $phone,
                    "content" => ""                
                );
                // $result = model("Wechatred")->sendred($redary);
                // if($result!=1){return $result;}
            }
            //注册成功后相关操作,信息提醒
            model("Base")->CreateUserLog("用户注册","用户[".$phone."]注册成功");//写入日志
            $result = model("message")->getmsg("regsuccess",[$phone]);//消息相关处理
            $code = 1;
        }else{   
            $code = "该手机号已注册过，请用该手机登录或者重新注册！";
        }
        return $code;
    }

    //绑定微信
    public function bdwx(){
        $phone = input("post.phone");
        $post = input("post.");
        unset($post["phone"]);
        $post["bdwx"] = 1;
        $post["bdwxtime"] = date("Y-m-d H:i:s");
        $result = model("User")->where("phone",$phone)->update($post);
        return $result;
    }

    //忘记密码
    public function resetpwd(){
        $post = input("post.");
        $sessionphone = Session::get('user_phone');
        $phone = $post["phone"] = $post["apply_phone"];
        if($sessionphone){
            if($phone!=$sessionphone){return "请输入您正确的手机号码";}
        }
        //验证短信验证码
        $smscode = $post["smscode"];
        $result = model("Base")->smscheck($phone,$smscode);
        if($result!=1){return $result;}
        //数据处理
        unset($post["apply_phone"],$post["smscode"]); 

        if($post["password"]!=$post["repassword"]){
            return "两次输入的密码不一致，请重新输入！";
        }

        // print_r($post);
        $password = md5(trim($post["password"]));
        $result = model("User")->where("phone",$phone)->update(["password"=>$password]);
        if($result){
            model("Base")->CreateUserLog("修改密码","用户[".$phone."]重新设置了密码");
            return $result;
        }else{
            return "请勿重复提交！";
        }
    }

    //获取列表
    public function getlist(){  
        $tableary = array(1=>"insurancecompany",2=>"partner",3=>"agent",4=>"banner",5=>"news");
        $t = input("post.t");
        $table = $tableary[$t];
        $where = " where 1 ";
        if(input("?post.v")){
            $where .= " and servicetype=".input("post.v");
        }
        $sql = "select * from scy_".$table.$where;
        $list = model("Base")->query($sql);
        if($t==5){
            foreach($list as $key=>&$val){
                $val["info"] = str_replace("/uploads/","http://chexian.302s.cn/uploads/",$val["info"]);
            }
        }
        return json_encode($list);
    }

    //获取数据分析tocken
    public function phonecallback(){
        $token = input("get.token");
        $id = input("get.id");
        $w = input("get.w");
        $where["id"] = $id;
        $userorder = model("userorder")->field('rs,phone')->where($where)->find();      
        $setinfo["token"] = $token;
        $setinfo["rs"] = $userorder["rs"];
        $setinfo["phone"] = $userorder["phone"];   
        $tokeninfo = model("orderphonedata")->where("rs",$userorder["rs"])->find();  
        if(!$tokeninfo){
            $tokeninfo = model("orderphonedata")->insert($setinfo);  
        }
        if($w == 1){
            return view('index/index');
        }elseif($w == 2){
            echo "<script>window.location = '/web/index/phoneauth';</script>";
            exit;
        }
    }

    public function getphonetoken(){
        $id = input("post.id");
        if($id){
            $userorder = model("userorder")->where("id",$id)->field('rs')->find();
            $tokeninfo = model("orderphonedata")->where(["rs"=>$userorder["rs"]])->field('token')->find();
            return $tokeninfo["token"];
        }else{
            return 0;
        }
    }


    //获取分析数据
    public function getphonedata(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        //获取询价订单信息
        $token = input("get.token");
        if($token){
            $tokeninfo = model("orderphonedata")->where("token",$token)->find();
            $phone = $tokeninfo["phone"];
            $inset = model("userorder")
            ->field('rs,apply_phone')
            ->where("rs",$tokeninfo["rs"])->find()->toArray();     
        }else{
            $inset = model("userorder")
            ->field('rs,apply_phone')
            ->where("id",$post["id"])->find()->toArray();        
        }
        $returntxt = $this->checkphonedata($token);
        $tempary = json_decode($returntxt,true);
        if($tempary["code"]=="10000"){
            $result = model("order")->where("rs",$inset["rs"])->update(["phoneresult"=>"SAME"]); 
            $tempary = $tempary["result"];
            if(isset($tempary["msg"])){
                return 0;
            }
            //数据分析结果存入
            $setinfo["result"] = json_encode($tempary);
            $setinfo["token"] = $token;
            $setinfo["rs"] = $inset["rs"];
            $setinfo["phone"] = $phone;
            $tokeninfo = model("orderphonedata")->where("rs",$inset["rs"])->find();
            if($tokeninfo){
                $result = model("orderphonedata")->where("rs",$inset["rs"])->update($setinfo); 
            }else{
                $result = model("orderphonedata")->insert($setinfo); 
                if(!$result){
                    return "error";
                }
            }
            if(isset($tempary["errorCode"])){
                return 0;
            }
            if($tempary["deceitRisk"]["calledByCourtNo"]!="unKnow"){
                return "有法院呼叫记录，请联系管理员认证";
            }
            if($tempary["deceitRisk"]["inBlacklist"]!="unKnow"){
                return "信息命中网贷黑名单，请联系管理员认证";
            }
            if($tempary["deceitRisk"]["samePeople"]!="True"){
                return "手机姓名与申请人姓名不一致";
            }
            if($tempary["deceitRisk"]["phoneIsAuth"]!="True"){
                return "手机号码未实名认证";
            }
            if(!$tempary["deceitRisk"]["certNoIsValid"]){
                return "身份证号码无效";
            }
            if($inset["apply_phone"]==$phone){
                $result = model("user")->where("phone",$phone)->update(["phone_stat"=>1]);
            }  
            return 1;         
        }
        //return $returntxt;
    }

    //获取页面地址
    public function getphoneurl(){
        $post = input("post.");
        $w = isset($post["w"])?$post["w"]:0;
        $returntxt = $this->checkphoneurl($post["apply_phone"],$post["car_name"],$post["id_code"],$post["id"],$w);
        return $returntxt;
    }

    //运营商数据分析接口-获取数据
    public function checkphonedata($token){
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'query_code' => $token,
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/APIX/mobile_carrier_query';
        return model("Base")->wx_http_request($url, $params );
    }  

    //运营商数据分析接口-获取页面
    public function checkphoneurl($phone,$name,$code,$id,$w){
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'callback_url' => 'http://chexian.302s.cn/api/index/phonecallback.do?id='.$id."&w=".$w,
            'success_url' => 'http://chexian.302s.cn/api/index/phonecallback.do?id='.$id."&w=".$w,
            'failed_url' => 'http://chexian.302s.cn/api/index/phonecallback.do?id='.$id."&w=".$w,
            'name' => $name,
            'cert_no' => $code,
            'contact_list' => '',
            'show_nav_bar' => '',
            'phone_no' => $phone,
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/APIX/mobile_carrier_page';
        return model("Base")->wx_http_request($url, $params );
    }    
    
}
