<?php
namespace app\api\controller;
use app\common\model;
use think\Session;
use think\Request;
class Webuser extends Common{
    public function index(){
        echo "login";
    }

    //获取记录
    public function getlist(){
        $post = input("post.");
        $tableary = array("userrecharge","usertixian","userbank","userred");
        $phone = Session::get('user_phone');
        $user = model("User")->field('id')->getbyphone($phone);
        $where = array("uid"=>$user["id"]);
        $list = model($tableary[$post["v"]])->where($where)->select();
        switch($post["v"]){
            case 0:
                $paytype_ary = array("余额支付","微信支付","支付宝支付");
                foreach ($list as $key => &$val) {
                    $val["paytype"] = $paytype_ary[$val["paytype"]];
                    $val["paystat"] = $val["paystat"]==1?"已支付":"未支付";
                }
                break;
            case 1:
                $paystat_ary = array("待审核","待审核","待结款","已结款","已驳回");
                foreach ($list as $key => &$val) {
                    $temp = model("userbank")->where("id",$val["bankid"])->find();
                    $val["bank"] = $temp["bank_account"];
                    $val["paystat"] = $paystat_ary[$val["stat"]];;
                }
                break;
            case 2:
                break;
            case 3:
                $paystat_ary = array("注册奖励","推荐红包","消费奖励","认证奖励","生日红包");
                foreach ($list as $key => &$val) {
                    $val["info"] = $val["content"];
                    $val["content"] = $paystat_ary[$val["redtype"]];;
                }
                break;
        }
        return json_encode($list);
    }

    //支付商户订单-app
    public function payappagentorder(){
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone,"stat"=>3,"paystat"=>0);
        $order = model("agentorder")->where($where)->find();
        $user = model("User")->field('id,wxopenid')->getbyphone($phone);
        $paytype = input("post.paytype");
        // print_r($order);exit;
        if($order){
            $body = "车辆：".$order["car_license"]."的商户服务订单";
            $subject = "车辆：".$order["car_license"]."的商户服务订单";
            $totalprice = $order["order_fee"];
            $totalprice = 0.01;
            $totalprice = sprintf("%.2f",$totalprice);

                if($paytype==1){//微信生成预支付订单
                    $returnresult = model("Wechatpay")->wxpay($order["order_id"],$body,$totalprice); 
                    unset($returnresult["appid"]);
                    $returnresult["tradeNO"] = $order["order_id"];
                }else{//支付宝直接返回信息到app，apicloud方案一
                    $returnresult["subject"] = $subject;
                    $returnresult["body"] = $body;
                    $returnresult["amount"] = $totalprice;
                    $returnresult["tradeNO"] = $order["order_id"];
                }
                return json_encode($returnresult);   
         
        }else{
            return 0;
        }
    }   

    //支付商户订单-jsapi
    public function payagentorder(){
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone,"stat"=>3,"paystat"=>0);
        $order = model("agentorder")->where($where)->find();
        $user = model("User")->field('id,wxopenid')->getbyphone($phone);
        if($order){
            $body = "车辆：".$order["car_license"]."的商户服务订单";
            $subject = "车辆：".$order["car_license"]."的商户服务订单";
            $totalprice = $order["order_fee"];
            $totalprice = 0.01;
            $totalprice = sprintf("%.2f",$totalprice);
            $url = "http://chexian.302s.cn/";
            ///** *
            $wxpay = & load_wechat('Pay');
            $result = $wxpay->getPrepayId($user["wxopenid"],$body,$order["order_id"],$totalprice*100,$url);
            $result = $wxpay->createMchPay($result);
            return $result;            
        }else{
            return 0;
        }
    }    

    //微信充值-扫码
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
        $url = "http://chexian.302s.cn/";

        if($result){
            $wxpay = & load_wechat('Pay');
            $result = $wxpay->getQrcPrepayId("",$body,$insert["ordernum"],$totalprice*100,$url);
            return 'http://paysdk.weixin.qq.com/example/qrcode.php?data='.$result["code_url"].''; 
        }else{
            return 0;
        }
    }    

    //微信充值-jsapi
    public function rechargejs(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        $user = model("User")->field('id,wxopenid')->getbyphone($phone);
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
        $url = "http://chexian.302s.cn/";

        if($result){
            $wxpay = & load_wechat('Pay');
            $result = $wxpay->getPrepayId($user["wxopenid"],$body,$insert["ordernum"],$totalprice*100,$url);
            $result = $wxpay->createMchPay($result);
            return $result;
        }else{
            return 0;
        }
    }    

    //微信分期支付-扫码
    public function payinstall(){
        $id = input("post.id");
        $qishu = input("post.qishu");
        $paytype = input("post.paytype");
        $userorder = model("userorder")->field('rs,car_license')->where("id",$id)->find()->toArray();        
        $where = array("rs"=>$userorder["rs"],"qishu"=>array("exp","in (".$qishu.")"));
        $install = model("orderinstall")->where($where)->find()->toArray();
        // print_r($install);
        $update = array();
        $update["ordernum"] = date("YmdHis").mt_rand("1000","9999");
        $install["ordernum"] = $update["ordernum"];
        $update["paytype"] = $paytype;
        $update["payclient"] = $_SERVER['HTTP_USER_AGENT'];
        $update["payip"] = request()->ip();
        $result = model("orderinstall")->where($where)->update($update);//更新订单号，支付类型

        /* 支付所需信息 */
        $body = "车辆：".$userorder["car_license"]."的第".$qishu."期的支付订单";
        $subject = "车辆：".$userorder["car_license"]."的分期订单";
        /* 获取选中期数金额 */
        $temp = model("orderinstall")->where($where)->sum("money");

        $totalprice = $temp;
        $totalprice = 0.01;
        $totalprice = sprintf("%.2f",$totalprice);
        if($result){
            $wxpay = & load_wechat('Pay');
            $result = $wxpay->getQrcPrepayId("",$body,$update["ordernum"],$totalprice*100,"http://chexian.302s.cn/");
            return 'http://paysdk.weixin.qq.com/example/qrcode.php?data='.$result["code_url"].'';
        }   
    }  

    //微信分期支付-jsapi
    public function payinstalljs(){
        $phone = Session::get('user_phone');
        $id = input("post.id");
        $qishu = input("post.qishu");
        $paytype = input("post.paytype");
        $userorder = model("userorder")->field('rs,car_license')->where("id",$id)->find()->toArray();        
        $where = array("rs"=>$userorder["rs"],"qishu"=>array("exp","in (".$qishu.")"));
        $install = model("orderinstall")->where($where)->find()->toArray();
        // print_r($install);
        $update = array();
        $update["ordernum"] = date("YmdHis").mt_rand("1000","9999");
        $install["ordernum"] = $update["ordernum"];
        $update["paytype"] = $paytype;
        $update["payclient"] = $_SERVER['HTTP_USER_AGENT'];
        $update["payip"] = request()->ip();
        $result = model("orderinstall")->where($where)->update($update);//更新订单号，支付类型

        $user = model("User")->getbyphone($phone);
        /* 支付所需信息 */
        $body = "车辆：".$userorder["car_license"]."的第".$qishu."期的支付订单";
        $subject = "车辆：".$userorder["car_license"]."的分期订单";
        /* 获取选中期数金额 */
        $temp = model("orderinstall")->where($where)->sum("money");
        $totalprice = $temp;
        $totalprice = 0.01;
        $totalprice = sprintf("%.2f",$totalprice);
        if($result){
            $wxpay = & load_wechat('Pay');
            $result = $wxpay->getPrepayId($user["wxopenid"],$body,$update["ordernum"],$totalprice*100,"http://chexian.302s.cn/");
            $result = $wxpay->createMchPay($result);
            return $result;
        }   
    } 

    //微信支付全款-扫码
    public function buy(){
        set_time_limit(0);
        $post = input("post.");
        $phone = Session::get('user_phone');
        //获取询价订单信息
        $inset = model("userorder")
        ->field('offerinsurerid as insurerid,rs,username,phone,car_name as realname,id_code as id_license,order_price,car_license,apply_phone')
        ->where("id",$post["id"])->find()->toArray();
        //获取保单信息
        $applyphone = $inset["apply_phone"];
        $order = model("order")->getbyrs($inset["rs"]);
        $inset["addtime"] = date("Y-m-d H:i:s");
        $timestamp = strtotime($inset["addtime"]);
        $totalprice = $inset["order_price"];
        $car_license = $inset["car_license"];
        unset($inset["order_price"],$inset["car_license"],$inset["apply_phone"]);
        $inset["fktype"] = 1;
        $install = array();
        $install["rs"] = $inset["rs"];
        $install["username"] = $inset["username"];
        $install["phone"] = $inset["phone"];
        $inset["active"] = 0;
        if($order&&$order["fktype"]==1&&$order["paystat"]==1){
            return "已存在订单";
        }else{
            $inset["install_fee"] = $totalprice;
            $inset["install_count"] = 1;
            $install["type"] = 1;
            $install["qishu"] = 1;
            $install["money"] = $totalprice;
            $install["beforemoney"] = $totalprice;
            $install["aftermoney"] = 0;
            $install["car_license"] = $car_license;
            $install["ordernum"] = date("YmdHis").mt_rand("1000","9999");
            $install["paytype"] = isset($post["paytype"])?$post["paytype"]:1;
            $install["payclient"] = $_SERVER['HTTP_USER_AGENT'];
            $install["payip"] = request()->ip();
            $body = "车辆：".$car_license."的全款支付订单";
            $subject = "车辆：".$car_license."的全款订单";
            //print_r($install);
            $totalprice = 0.01;
            $totalprice = sprintf("%.2f",$totalprice);
            if($order){
                model("order")->where("rs",$order["rs"])->delete();
            }
            $result = model("order")->insert($inset); 
            $result = model("orderinstall")->insert($install); 
            if($install["paytype"]==1){
                $wxpay = & load_wechat('Pay');
                $result = $wxpay->getQrcPrepayId("",$body,$install["ordernum"],$totalprice*100,"http://chexian.302s.cn/");
                model("Base")->CreateUserLog("创建全款订单","用户[".$phone."]创建全款订单，订单号：[".$inset["rs"]."]");
                return 'http://paysdk.weixin.qq.com/example/qrcode.php?data='.$result["code_url"].'';
            }else{
                $returnresult["subject"] = $subject;
                $returnresult["body"] = $body;
                $returnresult["amount"] = $totalprice;
                $returnresult["tradeNO"] = $install["ordernum"];
            }      
        }
   
        // print_r($post);
        // print_r($inset);
    }

    //微信支付全款-jsapi
    public function buyjs(){
        set_time_limit(0);
        $post = input("post.");
        $phone = Session::get('user_phone');
        //获取询价订单信息
        $inset = model("userorder")
        ->field('offerinsurerid as insurerid,rs,username,phone,car_name as realname,id_code as id_license,order_price,car_license,apply_phone')
        ->where("id",$post["id"])->find()->toArray();
        //获取保单信息
        $applyphone = $inset["apply_phone"];
        $order = model("order")->getbyrs($inset["rs"]);
        $inset["addtime"] = date("Y-m-d H:i:s");
        $timestamp = strtotime($inset["addtime"]);
        $totalprice = $inset["order_price"];
        $car_license = $inset["car_license"];
        unset($inset["order_price"],$inset["car_license"],$inset["apply_phone"]);
        $inset["fktype"] = 1;
        $install = array();
        $install["rs"] = $inset["rs"];
        $install["username"] = $inset["username"];
        $install["phone"] = $inset["phone"];
        $inset["active"] = 0;
        if($order&&$order["fktype"]==1&&$order["paystat"]==1){
            return "已存在订单";
        }else{
            $inset["install_fee"] = $totalprice;
            $inset["install_count"] = 1;
            $install["type"] = 1;
            $install["qishu"] = 1;
            $install["money"] = $totalprice;
            $install["beforemoney"] = $totalprice;
            $install["aftermoney"] = 0;
            $install["car_license"] = $car_license;
            $install["ordernum"] = date("YmdHis").mt_rand("1000","9999");
            $install["paytype"] = isset($post["paytype"])?$post["paytype"]:1;
            $install["payclient"] = $_SERVER['HTTP_USER_AGENT'];
            $install["payip"] = request()->ip();
            $body = "车辆：".$car_license."的全款支付订单";
            $subject = "车辆：".$car_license."的全款订单";
            //print_r($install);
            $totalprice = 0.01;
            $totalprice = sprintf("%.2f",$totalprice);
            if($order){
                model("order")->where("rs",$order["rs"])->delete();
            }
            $result = model("order")->insert($inset); 
            $result = model("orderinstall")->insert($install); 
            if($install["paytype"]==1){
                $user = model("User")->getbyphone($phone);
                $wxpay = & load_wechat('Pay');
                $result = $wxpay->getPrepayId($user["wxopenid"],$body,$install["ordernum"],$totalprice*100,"http://chexian.302s.cn/");
                $result = $wxpay->createMchPay($result);
                return $result;
            }else{
                $returnresult["subject"] = $subject;
                $returnresult["body"] = $body;
                $returnresult["amount"] = $totalprice;
                $returnresult["tradeNO"] = $install["ordernum"];
            }      
        }

        // print_r($post);
        // print_r($inset);
    }    
}