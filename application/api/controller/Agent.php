<?php
namespace app\api\controller;
use app\common\model;
use think\Session;
class Agent extends Common {
    public function index(){
        echo "agent";
    }

    //创建商户订单 
    public function create(){
        $post = input("post.");  
        $phone = Session::get('user_phone');
        if(!$phone){return "非法请求";}
        if($post["apply_phone"]==""){
            return "请填写手机号码";
        }
        if(isset($post["certificate"])){
            $temp_imgdata = model("Base")->saveBase64Image($post["certificate"]);
            $post["certificate"] = $temp_imgdata["url"];
        }
        //验证短信验证码
        $smscode = $post["smscode"];
        $result = model("Base")->smscheck($post["apply_phone"],$smscode);
        if($result!=1){return $result;}  
        unset($post["smscode"]);      
        //获取省份城市

        if(is_numeric($post["city"])){
            $tempdata = model("Base")->query("select * from base_city where city_id = '".$post["city"]."' limit 1");
        }else{
            $tempdata = model("Base")->query("select * from base_city where city_name like '%".$post["city"]."%' limit 1");
        }
        $post["city"] = $tempdata[0]["city_id"];
        $post["province"] = $tempdata[0]["province_id"];

        $agent = model("agent")->getbyid($post["agentid"]);
        $user = model("User")->getbyphone($phone);
        $post["userid"] = $user["id"];
        $post["username"] = $user["username"];
        $post["phone"] = $phone;
        $post["order_id"] = date("YmdHis").mt_rand(10000,99999);
        $post["addtime"] = date("Y-m-d H:i:s");
        $post["byrebate"] = $agent["rebate"];
        $result = model("agentorder")->insert($post);
        //预约商户订单后处理
        model("Base")->CreateUserLog("预约商户订单","用户[".$user["phone"]."][".$user["username"]."]预约商户服务");
        $agentclass = model("agentclass")->getbyid($post["order_type"]);
        $tempdata = array(
            $post["apply_phone"],//手机号码0
            $post["realname"],//姓名1
            $post["car_license"], //车牌号码2
            $agentclass["name"], //车牌号码3
            $post["bespeaktime"], //预约时间4
            $agent["address"]
        );			
        $result = model("message")->getmsg("bespeak",$tempdata);//消息相关处理           
        return $result;
    }

    //更新评价
    public function updatemsg(){
        $id = input("post.id");
        $evaluate = input("post.evaluate");
        $where = array("id"=>$id);
        $update["evaluate"] = $evaluate;
        $update["msg_time"] = date("Y-m-d H:i:s");
        $update["showevaluate"] = 1;
        $result = model("agentorder")->where($where)->update($update);
        if(!$result){
            $result = "请求错误";
        }
        return $result;
    }

    //根据定位获取商户列表
    public function getlist(){
        $post = input("post.");
        $where["servicetype"] = $post["v"];
        //获取省份城市
        if($post["city"]!=''){
            $tempdata = model("Base")->query("select * from base_city where city_name like '%".$post["city"]."%' or othername like '%".$post["city"]."%' limit 1");
            if($tempdata){
                $post["city"] = $tempdata[0]["city_id"];
                $post["province"] = $tempdata[0]["province_id"];
                $where["city"] = $post["city"];
            }else{
                $tempdata = model("Base")->query("select * from base_area where area_name like '%".$post["city"]."%'  limit 1");
                $post["city"] = $tempdata[0]["city_id"];
                $tempdata = model("Base")->query("select * from base_city where city_id = '".$tempdata[0]["city_id"]."'   limit 1");
                $post["province"] = $tempdata[0]["province_id"];
                $where["city"] = $post["city"];
            }
        }
        $list = model("agent")->where($where)->select();
        return json_encode($list);
    }

    //获取商户订单
    public function getorder(){
        $post = input("post.");
        $where = array("agentid"=>$post["id"],"stat"=>4,"paystat"=>1,"evaluate"=>["<>",'']);
        $list = model("agentorder")->where($where)->select();
        return json_encode($list);
    }

    //获取商户订单
    public function getagentorder(){
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone);
        $list = model("agentorder")->where($where)->select();
        return json_encode($list);
    }

    //获取商户订单详情
    public function getagentorderdetail(){
        $phone = Session::get('user_phone');
        $id = input("post.id");
        $where = array("phone"=>$phone,"id"=>$id);
        $list = model("agentorder")->where($where)->find();
        $agent = model("agent")->where("id",$list["agentid"])->find();
        $list["companyname"] = $agent["company"];
        $list["order_type"] = $list["order_type"]==1?"预约审车":"汽车维护";
        return json_encode($list);
    }

    //获取等待支付订单
    public function getpayorder(){
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone,"stat"=>3,"paystat"=>0);
        $order = model("agentorder")->where($where)->find();
        if($order){
            $body = "车辆：".$order["car_license"]."的商户服务订单";
            $subject = "车辆：".$order["car_license"]."的商户服务订单";
            $totalprice = $order["order_fee"];
            $totalprice = 0.01;
            $totalprice = sprintf("%.2f",$totalprice);
            $url = "http://chexian.302s.cn/api/agent/wxpayresult";
            $returnresult = model("Wechatpay")->wxpay($order["order_id"],$body,$totalprice,$url); 
            unset($returnresult["appid"]);
            $returnresult["tradeNO"] = $order["order_id"];            
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
    
	//回调结果处理
    public function payresult($ordernum){
        $orderinstall = model("agentorder")->where("order_id",$ordernum)->find();
        $update["paystat"] = 1;
        $update["paytime"] = date("Y-m-d H:i:s");
        $update["stat"] = 3;
        $result = model("agentorder")->where("order_id",$ordernum)->update($update);
        return $result;
    }    

    //根据定位获取商户信息
    public function getinfo(){
        $id = input("post.id");
        $info = model("agent")->getbyid($id)->toArray();
        $temp = model("agentclass")->getbyid($info["servicetype"])->toArray();
        $info["servertype"] = $temp["name"];
        $temp = explode("-",$info["server_zao"]);
        $info["server_time"] = substr($temp[0],0,5)." - ";
        $temp = explode("-",$info["server_wan"]);
        $info["server_time"] .= substr(trim($temp[1]),0,5);
        return json_encode($info);
    }
}