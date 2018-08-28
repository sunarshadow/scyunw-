<?php
namespace app\api\controller;
use app\common\model;
use think\Session;
class Red extends Common {
    // 余额，收入，支出，明细总额，明细列表
    public function index(){
        $json = array();
        $type = input("post.t");
        $phone = Session::get('user_phone');
        $where = array("phone"=>$phone);
        $info = model("User")->where($where)->find();
        $json["balance"] = $info["money_free"];
        $tempwhere = $where;
        $moneymodel = model("usermoney");
        $tempwhere["money"] = array(">=",0);
        $json["income"] = $moneymodel->where($tempwhere)->sum("money");//收入金额
        $tempwhere["money"] = array("<",0);
        $json["pay"] = $moneymodel->where($tempwhere)->sum("money");//收入金额
        $jsonmodel = $type?model("usermoney"):model("userred");
        $json["sum"] = $jsonmodel->where($where)->sum("money");//明细总额
        $json["list"] = $jsonmodel->where($where)->select();//明细列表
        echo json_encode($json);
    }
    // 红包个数，红包总金额，推广，注册，认证，生日
    public function count(){
        $red = array();
        $Redmodel = model("userred");
        $phone = session('user_phone');
        $where = array("phone"=>$phone);
        $red["count"] = $Redmodel->where($where)->count();//红包个数
        $red["sum"] = $Redmodel->where($where)->sum("money");//红包总金额
        $where = array("phone"=>$phone,"redtype"=>1);
        $red["tg_sum"] = $Redmodel->where($where)->sum("money");//推广红包金额
        $where = array("phone"=>$phone,"redtype"=>0);
        $red["zc_sum"] = $Redmodel->where($where)->sum("money");//注册红包金额
        $where = array("phone"=>$phone,"redtype"=>3);
        $red["rz_sum"] = $Redmodel->where($where)->sum("money");//认证红包金额
        $where = array("phone"=>$phone,"redtype"=>4);
        $red["sr_sum"] = $Redmodel->where($where)->sum("money");//生日红包金额
        echo json_encode($red);
    }
}