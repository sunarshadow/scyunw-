<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Finance extends Common
{
    //平台接入
    public function borrow(){
		$sary = model("Base")->GetCheckAuth();
        return view("borrow",['sary' => $sary]);
    }
    //平台支付
    public function pay(){
		$sary = model("Base")->GetCheckAuth();
        return view("pay",['sary' => $sary]);
    }
    //获取审核统计
    public function checklist(){
		$type = input("get.type")?input("get.type"):0;
		$keyword = input("get.keyword")?input("get.keyword"):"";
		$where = "where type=".$type." ";
		if($keyword){ $where .= " and (name like '".$keyword."' or remark like '".$keyword."' or car_license like '".$keyword."')";}
		//sql开始
		$sql = "select * from scy_finance ";
		$sql = $sql.$where;
		if(!input("?get.toexcel")){
			$page = input('?get.page')?input('get.page'):1;//获取页数
			$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
			$sql = $sql." order by id desc limit ".($page-1)*$limits.",".$limits;
		}
		$Base_model = model("Base");
		$sqlcount = "select count(*) as total from scy_finance";
        $list = $Base_model->query($sql);//获取列表
        foreach($list as $key=>&$val){
            $val["astypestr"] = $val["astype"]?"<font color='#FF5722'>减少</font>":"<font color='#5FB878'>增加</font>";
			$val["checkadmin"] = $Base_model->Getadmin($val["checkadminid"]);
			$val["statstr"] = $val["stat"]?"<font color='#5FB878'>已审核</font>":"<font color='#FFB800'>未审核</font>";
        }
		if(input("?get.toexcel")){
			$Base_model->CreateAdminLog("导出列表","导出保单列表");
			$titary = array("保单列表");
			$list["tit"] = array(
				"addtime"=>"时间","name"=>"借款人","car_license"=>"车牌号",
				"remark"=>"摘要","add"=>"增加","sub"=>"减少",
				"balance"=>"期末余额"
			);
			$styleary = array(
				"addtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);
		}else{
			$total = $Base_model->query($sqlcount);//获取总数
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]["total"];
			$jsonary["data"] = $list;		
			return json($jsonary);
		}
	}
    //获取流水统计
    public function getlist(){
		$type = input("get.type")?input("get.type"):0;
		$keyword = input("get.keyword")?input("get.keyword"):"";
		$where = "where type=".$type." ";
		if($keyword){ $where .= " and (name like '%".$keyword."%' or remark like '%".$keyword."%' or car_license like '%".$keyword."%')";}
		//sql开始
		$sql = "select * from scy_orderfinance ";
		$sql = $sql.$where;
		if(!input("?get.toexcel")){
			$page = input('?get.page')?input('get.page'):1;//获取页数
			$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
			$sql = $sql." order by id desc limit ".($page-1)*$limits.",".$limits;
		}else{
			$sql .= "order by id asc";
		}
		$Base_model = model("Base");
		$sqlcount = "select count(*) as total from scy_orderfinance";
        $list = $Base_model->query($sql);//获取列表
        foreach($list as $key=>&$val){
			$tempadd = $val["add"];
			$val["add"] = "￥".sprintf("%.2f",$val["add"]);
			$val["sub"] = "￥".sprintf("%.2f",$val["sub"]);
			$val["balance"] = "￥".sprintf("%.2f",$val["balance"]);
			switch($val["type"]){
				case 0:
					$temp = model("order")->where("rs",$val["rs"])->find();
					$val["detailurl"] = url('order/info',["id"=>$temp["id"]]);
					break;
				case 1:
					$temp = model("agentorder")->where("order_id",$val["rs"])->find();
					$val["detailurl"] = url('agent/likebespoke',['id'=>$temp["id"]]);
					break;
				case 2:
					if($tempadd>0){
						$temp = model("order")->where("ordernumber",$val["rs"])->find();
						$val["detailurl"] = url('order/info',["id"=>$temp["id"]]);
					}else{
						$temp = model('insurertx')->getByOrdernumber($val["rs"]);
						$val["detailurl"] = url('insurer/paymentinfo',['id'=>$temp["id"]]);
					}
					break;
				case 3:
					$temp = model("finance")->where("ordernum",$val["rs"])->find();
					$val["detailurl"] = $temp["image"];
					break;
				case 4:
					$val["detailurl"] = url("user/redlist",["r"=>$val["rs"]]);
					break;
				case 5:
					if($val["car_license"]){
						$temp = model("order")->where("rs",$val["rs"])->find();
						$val["detailurl"] = url('order/info',["id"=>$temp["id"]]);
						break;
					}else{
						$temp = model("finance")->where("ordernum",$val["rs"])->find();
						$val["detailurl"] = $temp["image"];
					}
					break;
				case 7:
					$temp = model("agentorder")->where("order_id",$val["rs"])->find();
					$val["detailurl"] = url('agent/likebespoke',['id'=>$temp["id"]]);
					break;
			}
        }
		if(input("?get.toexcel")){
			switch($type){
				case 0:
					$Base_model->CreateAdminLog("导出应收保费流水列表","导出应收保费流水列表");
					$titary = array("应收保费流水列表");
					$list["tit"] = array("addtime"=>"时间","name"=>"用户","car_license"=>"车牌号","remark"=>"摘要","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 1:
					$Base_model->CreateAdminLog("导出应收商户返现流水列表","导出应收商户返现流水列表");
					$titary = array("应收商户返现流水");
					$list["tit"] = array("addtime"=>"时间","name"=>"合作商家","remark"=>"摘要(车牌号)","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 2:
					$Base_model->CreateAdminLog("导出应收保险公司佣金列表","导出应收保险公司佣金列表");
					$titary = array("应收保险公司佣金");
					$list["tit"] = array("addtime"=>"时间","name"=>"合作保险公司","remark"=>"摘要(保险单号)","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 3:
					$Base_model->CreateAdminLog("导出平台借入资金列表","导出平台借入资金列表");
					$titary = array("平台借入资金");
					$list["tit"] = array("addtime"=>"时间","name"=>"用户","remark"=>"摘要","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 4:
					$Base_model->CreateAdminLog("导出红包明细列表","导出红包明细列表");
					$titary = array("红包明细");
					$list["tit"] = array("addtime"=>"时间","name"=>"用户","remark"=>"摘要(红包类型)","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 5:
					$Base_model->CreateAdminLog("导出平台支出列表","导出平台支出列表");
					$titary = array("导出平台支出列表");
					$list["tit"] = array("addtime"=>"时间","name"=>"用户","remark"=>"摘要","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 6:
					$Base_model->CreateAdminLog("导出应付用户资金流水列表","导出应付用户资金流水列表");
					$titary = array("应付用户资金流水");
					$list["tit"] = array("addtime"=>"时间","name"=>"用户","remark"=>"摘要","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
				case 7:
					$Base_model->CreateAdminLog("导出应付商户资金流水列表","导出应付商户资金流水列表");
					$titary = array("应付商户资金流水");
					$list["tit"] = array("addtime"=>"时间","name"=>"合作商家","remark"=>"摘要(车牌号)","add"=>"增加","sub"=>"减少","balance"=>"期末余额");
					break;
			}
			$styleary = array("addtime"=>"20");
			$Base_model->Toexcel($titary,$list,$styleary);
		}else{
			$total = $Base_model->query($sqlcount);//获取总数
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]["total"];
			$jsonary["data"] = $list;		
			return json($jsonary);
		}
	}
	//添加资金流水
	public function getset(){
		$post = input('post.');
		$id = input("post.id");
		$jsonary["msg"] = '';
		if(!$id>0){
			$insert["uid"] = $post["uid"] = session('admin_id');
			$insert["addtime"] = $post["addtime"] = date("Y-m-d H:i:s");
			$insert["rs"] = $post["ordernum"] = date("YmdHis").mt_rand("0000","9999");
			$insert["remark"] = $post["remark"];
			$insert["add"] = $insert["sub"] = $insert["balance"] = 0;
			$post["astype"]?$insert["sub"] = $post["money"]:$insert["add"] = $post["money"];
			$insert["type"] = $post["type"];
			unset($post["file"]);
			$result = model("finance")->insert($post);
			// $result = model("orderfinance")->insert($insert);
		}else{
			$stat = input("post.stat");
			$update = ["stat"=>$stat,"checktime"=>date("Y-m-d H:i:s"),"checkadminid"=>session('admin_id')];
			$result = model("finance")->where("id",$id)->update($update);
		}
		return json($jsonary);
	}
}
