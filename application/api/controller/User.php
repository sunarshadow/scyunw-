<?php
namespace app\api\controller;
use app\common\model;
use think\Session;
use think\Request;
use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
class User extends Common{
    public function index(){
        echo "login";
    }

    /********************************************************************************************************************/
    //设置支付密码    
    /********************************************************************************************************************/
    public function setpaypwd(){
        $phone = Session::get('user_phone');
        $post = input("post.");
        //验证短信验证码
        $smscode = $post["smscode"];
        $result = model("Base")->smscheck($phone,$smscode);
        if($result!=1){return $result;}
        //数据处理
        unset($post["smscode"]); 
        if($post["paypassword"]!=$post["repaypassword"]){
            return "两次输入的密码不一致，请重新输入！";
        }
        $password = md5(trim($post["paypassword"]));
        $result = model("User")->where("phone",$phone)->update(["paypassword"=>$password]);
        if($result){
            model("Base")->CreateUserLog("修改支付密码","用户[".$phone."]重新设置了支付密码");
            return $result;
        }else{
            return "请勿重复提交！";
        }
    }

    /********************************************************************************************************************/
    //收货地址操作    
    /********************************************************************************************************************/

    //获取收货地址
    public function address(){
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone);
        $list = model("Useraddress")
        ->where($where)
        ->order("isdefault desc")
        ->select();
        $Basemodel = model("Base");
        foreach($list as $key=>&$val){
            $address = $val["address"];
            $val["address"] = $Basemodel->Getbyprovince($val["province"])." ";
            $val["address"] .= $Basemodel->Getbycity($val["city"])." ";
            $val["address"] .= $Basemodel->Getbyarea($val["area"])." ".$address;
        }
        return json_encode($list);
    }

    //获取收货地址
    public function getaddress(){
        $id = input("post.id");
        $address = model("Useraddress")->getbyid($id)->find()->toArray();
        $Basemodel = model("Base");
        $address["area"] = $Basemodel->Getbyprovince($address["province"])."-".$Basemodel->Getbycity($address["city"])."-".$Basemodel->Getbyarea($address["area"]);
        return json_encode($address);
    }

    //删除收货地址
    public function deladdress(){
        $id = input("post.id");
        $phone = Session::get('user_phone');
        $address = model("Useraddress")->getbyid($id)->find()->toArray();
        $Basemodel = model("Base");
        $address["area"] = $Basemodel->Getbyprovince($address["province"])."-".$Basemodel->Getbycity($address["city"])."-".$Basemodel->Getbyarea($address["area"]);
        $result = model("Useraddress")->where("id",$id)->delete();
        if($result){
            model("Base")->CreateUserLog("删除收货地址","用户[".$phone."]删除收货地址");
        }
        return json_encode($address);
    }
    
    //设置默认收货地址
    public function setdefault(){
        $id = input("post.id");
        $phone = Session::get('user_phone');
        $result = model("Useraddress")->where("phone",$phone)->update(["isdefault"=>0]);
        $result = model("Useraddress")->where("id",$id)->update(["isdefault"=>1]);
        model("Base")->CreateUserLog("设置默认地址","用户[".$phone."]设置默认收货地址");
        return 1;
    }

    //添加/编辑收货地址
    public function addaddress(){
        $phone = Session::get('user_phone');
        $post = input("post.");
        $user = model("User")->getbyphone($phone);

        $area = explode("-",$post["area"]);
		// print_r($area);exit;
        //获取省份城市
        $tempdata = model("Base")->query("select * from base_city where city_name like '%".$area[1]."%' or othername like '%".$area[1]."%' limit 1");
		if($tempdata){
			$post["city"] = $tempdata[0]["city_id"];
			$post["province"] = $tempdata[0]["province_id"];
		}else{
			$tempdata = model("Base")->query("select * from base_area where area_name like '%".$area[1]."%'  limit 1");
			$post["area"] = $tempdata[0]["area_id"];
			$post["city"] = $tempdata[0]["city_id"];
			
			$tempdata = model("Base")->query("select * from base_city where city_id = '".$post["city"]."' limit 1");
			$post["province"] = $tempdata[0]["province_id"];
		}
        

        $post["phone"] = $phone;
        $post["uid"] = $user["id"];
        $post["username"] = $user["username"];
        $post["addtime"] = date("Y-m-d H:i:s");
        $id = input("post.id");
        if(input("post.isdefault")){
            if($post["isdefault"]==1){
                $result = model("Useraddress")->where("phone",$phone)->update(["isdefault"=>0]);
            }
        }
        if($id){
            $result = model("Useraddress")->where("id",$id)->update($post);
            model("Base")->CreateUserLog("编辑收货地址","用户[".$phone."]编辑收货地址");
        }else{
            $result = model("Useraddress")->insert($post); 
            model("Base")->CreateUserLog("添加收货地址","用户[".$phone."]添加收货地址");
        }
        return 1;
    }  

    //添加/编辑收货地址
    public function addwaddress(){
        $phone = Session::get('user_phone');
        $post = input("post.");
        $user = model("User")->getbyphone($phone);
        $post["phone"] = $phone;
        $post["uid"] = $user["id"];
        $post["username"] = $user["username"];
        $post["addtime"] = date("Y-m-d H:i:s");
        $id = input("post.id");
        if(input("post.isdefault")){
            if($post["isdefault"]==1){
                $result = model("Useraddress")->where("phone",$phone)->update(["isdefault"=>0]);
            }
        }
        if($id){
            $result = model("Useraddress")->where("id",$id)->update($post);
            model("Base")->CreateUserLog("编辑收货地址","用户[".$phone."]编辑收货地址");
        }else{
            $result = model("Useraddress")->insert($post); 
            model("Base")->CreateUserLog("添加收货地址","用户[".$phone."]添加收货地址");
        }
        return 1;
    }    

    /********************************************************************************************************************/
    //银行卡操作    
    /********************************************************************************************************************/


    //获取银行卡列表
    public function bank(){
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone);
        $list = model("Userbank")
        ->where($where)
        ->order("isdefault desc")
        ->select();
        foreach($list as $key=>&$val){
            $temp = explode("-",$val["bank_name"]);
            $val["bankname"] = isset($temp[0])?$temp[0]:0;
            $val["cardtype"] = isset($temp[2])?$temp[2]:0;
        }
        return json_encode($list);
    }

    //获取银行卡信息
    public function getbank(){
        $id = input("post.id");
        $bank = model("Userbank")->getbyid($id)->toArray();
        return json_encode($bank);
    }

    //获取银行卡信息
    public function delbank(){
        $id = input("post.id");
        $bank = model("Userbank")->getbyid($id)->toArray();
        $result = model("Userbank")->where("id",$id)->delete();
        if($result){  
            model("Base")->CreateUserLog("删除银行卡","用户[".$phone."]删除银行卡：".$bank["bank_account"]);
        }
        return $result;
    }

    //获取银行卡类型
    public function getbanktype(){
        $bankaccount = input("post.bankaccount");
        return model("Banklist")->bankInfo($bankaccount);
    }

    //添加/编辑银行卡
    public function addbank(){
        $phone = Session::get('user_phone');
        $post = input("post.");
        //验证短信验证码
        $smscode = $post["smscode"];
        $result = model("Base")->smscheck($post["bank_phone"],$smscode);
        if($result!=1){return $result;}

        $user = model("User")->getbyphone($phone);
        $post["phone"] = $phone;
        $post["userid"] = $user["id"];
        $post["username"] = $user["username"];
        $post["addtime"] = date("Y-m-d H:i:s");
        $id = input("post.id");
        unset($post["id"],$post["smscode"]);
        if($id){
            $result = model("Userbank")->where("id",$id)->update($post);
            model("Base")->CreateUserLog("编辑银行卡","用户[".$phone."]编辑银行卡：".$post["bank_account"]);
        }else{
            $result = model("Userbank")->insert($post); 
            model("Base")->CreateUserLog("编辑银行卡","用户[".$phone."]添加银行卡：".$post["bank_account"]);
        }
        return 1;
    }

    /********************************************************************************************************************/
    //修改手机号码操作    
    /********************************************************************************************************************/

    //修改手机号码
    public function changetel(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        if($phone!=$post["phone"]){
            //return "您输入的手机号码不是当前账号号码，请重新输入";
        }
        $phone = $post["new_phone"];
        //验证短信验证码
        $smscode = $post["smscode"];
        $result = model("Base")->smscheck($phone,$smscode);
        if($result!=1){return $result;}
        //验证账号密码
        $user = model("User")->field('phone,password')->getbyphone($post["phone"]);
        if($user){
            $user->toArray();
        }else{
            return "账号不存在";
        }
        if(trim($user["password"])!=md5($post["password"])){
            return "密码不正确！";
        }
        //验证手机号是否已注册
        $newuser = model("User")->field('phone,password')->getbyphone($post["new_phone"]);
        if($newuser){
            return "该手机号已被注册，无法绑定";
        }
        //修改手机号码
        $result = model("User")->where("phone",$post["phone"])->update(array("phone"=>$post["new_phone"]));
        $oldphone = $post["phone"];
        $newphone = $post["new_phone"];
        if($result){
            //更新被推荐用户信息
            model("User")->where("phone",$oldphone)->update(array("reco_phone"=>$newphone));
            //更新用户银行卡信息
            model("Userbank")->where("phone",$oldphone)->update(array("phone"=>$newphone));
            //更新用户收货地址信息
            model("Useraddress")->where("phone",$oldphone)->update(array("phone"=>$newphone));
            //更新用户保单相关信息
            model("Userorder")->where("phone",$oldphone)->update(array("phone"=>$newphone));//询价订单
            model("Userinsurance")->where("phone",$oldphone)->update(array("phone"=>$newphone));//订单险种
            model("Order")->where("phone",$oldphone)->update(array("phone"=>$newphone));//保单
            model("Orderinstall")->where("phone",$oldphone)->update(array("phone"=>$newphone));//保单支付
            //更新用户相关日志
            model("Userred")->where("phone",$oldphone)->update(array("phone"=>$newphone));//用户红包
            model("Usermoney")->where("phone",$oldphone)->update(array("phone"=>$newphone));//用户
            model("Base")->query("update scy_log_user set phone=".$newphone." where phone=".$phone);
            //更新商户订单相关信息
            model("Agentorder")->where("phone",$oldphone)->update(array("phone"=>$newphone));
            //
            model("Base")->CreateUserLog("修改手机号","用户[".$oldphone."]更改了手机号码为[".$newphone."]");
            session_unset();
            Session::clear();
            return 1;
        }else{
            return "请求失败";
        }
    }    

    /********************************************************************************************************************/
    //充值操作    
    /********************************************************************************************************************/

    public function recharge(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        $user = model("User")->field('id')->getbyphone($phone);
        $insert["money"] = $post["money"];
        $insert["ordernum"] = date("YmdHis").mt_rand("1000","9999");
        $insert["paytype"] = isset($post["paytype"])?$post["paytype"]:1;
        $insert["addtime"] = date("Y-m-d H:i:s");
        $insert["phone"] = $phone;
        $insert["uid"] = $user["id"];
        $result = model("userrecharge")->insert($insert);//创建充值订单

        /* 支付所需信息 */
        $body = "用户:".$phone."的".$insert["money"]."元的充值订单";
        $subject = "用户:".$phone."的充值订单";
        $totalprice = $insert["money"];
        $totalprice = 0.01;
        $totalprice = sprintf("%.2f",$totalprice);

        if($result){
            if($insert["paytype"]==1){//微信生成预支付订单
                $returnresult = model("Wechatpay")->wxpay($insert["ordernum"],$body,$totalprice); 
                unset($returnresult["appid"]);
                $returnresult["tradeNO"] = $insert["ordernum"];
            }else{//支付宝直接返回信息到app，apicloud方案一
                $returnresult["subject"] = $subject;
                $returnresult["body"] = $body;
                $returnresult["amount"] = $totalprice;
                $returnresult["tradeNO"] = $insert["ordernum"];
            }
            return json_encode($returnresult);   
        }else{
            return 0;
        }
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
        $update = array();
        $update["paystat"] = 1;
        $update["paytime"] = date("Y-m-d H:i:s");
        $update["payclient"] = $_SERVER['HTTP_USER_AGENT'];
        $update["payip"] = request()->ip();
        $result = model("userrecharge")->where("ordernum",$ordernum)->update($update);
        $recharge = model("userrecharge")->where("ordernum",$ordernum)->find();
        if($result){
            $update = array();
            $user = model("User")->getbyid($recharge["uid"]);
            $update["money_free"] = $user["money_free"] + $recharge["money"];
            $result = model("User")->where("id",$recharge["uid"])->update($update);
            return $result;
        }        
        return $result;
    }     

    /********************************************************************************************************************/
    //提现    
    /********************************************************************************************************************/
    public function withdrawal(){
        $phone = Session::get('user_phone');
        $post = input("post.");
        //验证短信验证码
        if(!isset($post["noneed"])){
            $smscode = $post["smscode"];
            $result = model("Base")->smscheck($phone,$smscode);
            if($result!=1){return $result;}
        }
        //数据处理
        unset($post["smscode"],$post["noneed"]); 
        $user = model("User")->getbyphone($phone);
        if($user["money_free"]<$post["money"]){
            return "您的余额不足";
        }
        //用户提现
        if(!isset($post["bankid"])){
            $where = array("phone"=>$phone,"isdefault"=>1);
            $temp = model("userbank")->where($where)->find();
            $post["bankid"] = $temp["id"];
        }
        $post["addtime"] = date("Y_m-d H:i:s");
        $post["uid"] = $user["id"];
        $post["ordernum"] = date("YmdHis").mt_rand("0000","9999");
        $post["allmoney"] = $user["money_free"];
        $result = model("Usertixian")->insert($post);
        if($result){
            //更新账户金额
            $update["money_free"] = $user["money_free"] - $post["money"];
            $update["money_frozen"] = $user["money_frozen"] + $post["money"];
            $result = model("User")->where("phone",$phone)->update($update);
            return $result;
        }else{
            return "提现失败";
        }
    }    

    /********************************************************************************************************************/
    //接口操作    
    /********************************************************************************************************************/

    //上传行驶证识别
    public function getcarcode(){
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'detect_direction' => 'false',
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/TONGLI/OcrVehicleLicense';
        $file = input("post.body");
        //$file = "E:/test.jpg";
        $body = @file_get_contents($file) or exit('not found file ( '.$file.' )');
        return model("Base")->wx_http_request($url, $params , $body, true , true);
    }
    
    //上传身份证识别
    public function getidcode(){
        $type = input("?post.type")?input("post.type"):2;
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'typeId' => $type,
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/wintone/IDCard';
        $file = input("post.body");
        //$file = "http://7zfrrz.com1.z0.glb.clouddn.com/apicloud/6f61084b3eac452c0274937c242e3361.jpg";
        $body = @file_get_contents($file) or exit('not found file ( '.$file.' )');
        return model("Base")->wx_http_request($url, $params , $body, true , true);        
    }    

    //测试用接口
    public function test(){
        $mobile = "17606036160";
        return model("Base")->getProvince($mobile);
    }
}