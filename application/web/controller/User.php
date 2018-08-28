<?php
namespace app\web\controller;
use think\Controller;
use think\Session;
class User extends Common
{
    public function _initialize()
    {
        header("Content-type: text/html; charset=utf-8");
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        if(!$user){
            echo "<script>alert('请先登录！');window.location='/';</script>";
            exit;
        }
    }    
    //用户中心
    public function index()
    {
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $log = model("Base")->query("select * from scy_log_user where phone=".$phone." order by id desc limit 10");
        return view('user/usercenter',["user" => $user,"log" => $log  ,"action" => $action]);
    }
    //个人资料
    public function info()
    {
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $log = model("Base")->query("select * from scy_log_user where phone=".$phone." order by id desc limit 10");
        return view('user/userdata',["user" => $user,"log" => $log ,"action" => $action]);
    }
    //账户安全
    public function security()
    {
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        $log = model("Base")->query("select * from scy_log_user where phone=".$phone." order by id desc limit 10");
        return view('user/useraccount',["user" => $user,"log" => $log ,"action" => $action]);
    }
    //我的保单
    public function order()
    {
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        $log = model("Base")->query("select * from scy_log_user where phone=".$phone." order by id desc limit 10");
        return view('user/userpolicy',["user" => $user,"log" => $log ,"action" => $action]);
    }
    //保单详情
     public function orderdetail(){
        $action = request()->action();
        $phone = Session::get('user_phone');
        $id = input("get.id");
        $order = model("userorder")->getbyid($id);
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        $install = model("orderinstall")->where("rs",$order["rs"])->select();
        return view('user/userpolicyop',["user" => $user,"id" => $id ,"action" => $action, "install" => $install]);
    }
    //保单详情
     public function sign(){
        $action = request()->action();
        $phone = Session::get('user_phone');
        $id = input("get.id");
        $order = model("userorder")->getbyid($id);
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        $install = model("orderinstall")->where("rs",$order["rs"])->select();
        return view('user/sign',["user" => $user,"id" => $id ,"action" => $action, "install" => $install]);
    }
    //账单列表
    public function bill(){
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        $log = model("Base")->query("select * from scy_log_user where phone=".$phone." order by id desc limit 10");
        return view('user/userbill',["user" => $user,"log" => $log ,"action" => $action]);
    }
    //资金管理
    public function capital(){
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        return view('user/usercapital',["user" => $user ,"action" => $action]);
    }
    //商户列表
    public function agentorder(){
        $action = request()->action();
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $agentorder = model("agentorder")->field("*,a.id as id")->alias('a')->order('a.id desc')->join('agent b','a.agentid = b.id')->where("a.phone",$phone)->select();
        foreach($agentorder as $key=>&$val){
            $val["order_type"] = $val["order_type"]==1?"预约审车":"洗车服务";
        }
        return view('user/useryuyue',["user" => $user ,"action" => $action , "agentorder" => $agentorder]);
    }
    //商户预约
    public function reserve(){
        $action = request()->action();
        $aid = input("get.id");
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $agent = model("agent")->getbyid($aid)->toArray();
        return view('user/carmaintain',["user" => $user ,"action" => $action ,"agent" => $agent]);
    }
    //商户评价
    public function evaluate(){
        $action = request()->action();
        $aid = input("get.id");
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        // $agent = model("agent")->getbyid($aid)->toArray();
        $where = array("phone"=>$phone,"agentid"=>$aid,"showevaluate"=>1);
        $agentorder = model("agentorder")->where($where)->select();
        return view('user/evaluate',["user" => $user ,"action" => $action ,"agentorder" => $agentorder]);
    }
    //评价
    public function addevaluate(){
        $action = request()->action();
        $id = input("get.id");
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $user["showphone"] = substr($phone,0,3)."****".substr($phone,7,4);
        // $agent = model("agent")->getbyid($aid)->toArray();
        $where = array("id"=>$id);
        
        $agentorder = model("agentorder")->field("*,a.id as id")->alias('a')->join('agent b','a.agentid = b.id')->where("a.id",$id)->find();
        $agentorder["order_type"] = $agentorder["order_type"]==1?"预约审车":"洗车服务";
        return view('user/addevaluate',["user" => $user ,"action" => $action ,"agentorder" => $agentorder]);
    }
}
