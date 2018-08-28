<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
use think\Loader;
class Bill extends Common
{
	private $table='order';
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
    public function index()
    {
		$url = url('bill/getlist');
		$url = input('?get.stat')?$url."?stat=".input('get.stat'):$url;
        return view('index',['url' => $url,'stat' => input('get.stat')]);
    }
	//逾期列表
	public function yqlist(){
		$url = url('bill/getyqlist');
		$url = input('?get.stat')?$url."?stat=".input('get.stat'):$url;
        return view('yqlist',['url' => $url,'stat' => input('get.stat')]);
	}
    //账单列表
    public function getlist()
    {
		$where = "where a.stat>3 ";
		//获取全款/分期/逾期
		$stat = input('?get.stat')?input('get.stat'):'';
		if(input('?get.stat')){
			//$where .= $stat?" and unix_timestamp(a.awaketime)>0 ":" and unix_timestamp(a.awaketime)=0 ";//是否生效
			$where .= $stat==2?" and a.yuqistat=1 ":" and a.fktype=".$stat." ";//是否有效
		}
		//获取关键字
		$keyword = input('?get.keyword')?input('get.keyword'):'';
		if(input('?get.keyword')){
			$where .= " and (b.car_license like '%".$keyword."%' or a.phone like '%".$keyword."%' or a.realname like '%".$keyword."%' or a.express_id like '%".$keyword."%') ";
		}
		//获取选中ids
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
			$where .= " and a.id in (0".$ids.")";
		}	

		//sql开始
		$sql = "select *,a.id as id,a.stat as stat from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs ";
		$sql = $sql.$where;
		if(!input("?get.toexcel")){
			$page = input('?get.page')?input('get.page'):1;//获取页数
			$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
			$sql = $sql." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		}
		$Base_model = model("Base");
		$Order_install_model = model("orderinstall");
		$sqlcount = "select count(*) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where;
		$list = $Base_model->query($sql);//获取列表
		foreach($list as $key=>&$val){	
			$val["province"] = $Base_model->Getbyprovince($val["province"]);
			$val["city"] = $Base_model->Getbycity($val["city"]);
			$val["orderarea"] = $val["province"].$val["city"];
			$val["jqprice"] = "￥".sprintf("%.2f",$val["jqprice"]);
			$val["csprice"] = "￥".sprintf("%.2f",$val["csprice"]);
			$val["syprice"] = "￥".sprintf("%.2f",$val["syprice"]);
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);
			$val["install_stat"] = model("orderinstall")->where(['rs'=>$val["rs"],'paystat'=>1])->count();
			$val["install_stat"] = $val["install_stat"]."/".$val["install_count"];
			$val["yuqicount"] = model("orderinstall")->where(['rs'=>$val["rs"],'yuqistat'=>1])->count();
			$val["secondadmin"] = $Base_model->Getadmin($val["secondadminid"]);
			$val["firstadmin"] = $Base_model->Getadmin($val["firstadminid"]);
			$val["fakuan"] = model("orderinstall")->where(['rs'=>$val["rs"]])->sum("wymoney");
			$val["companyname"] = $Base_model->Getinsurer($val["insurerid"]);
			$insurance = model("insurancecompany")->getbyid($val["company"]);
			$val["insurname"] = $insurance["name"];
		}	
		//数据导出
		if(input("?get.toexcel")){
			$Base_model->CreateAdminLog("导出列表","导出账单列表");
			$titary = array("账单列表");
			$list["tit"] = array(
				"username"=>"用户名","phone"=>"绑定手机","companyname"=>"出单公司","car_license"=>"车牌号",
				"ordernumber"=>"保单号","jqprice"=>"交强险","csprice"=>"车船税",
				"syprice"=>"商业险","order_price"=>"保单总价","install_count"=>"分期期数",
				"install_stat"=>"还款状态","install_day"=>"月还款日","yuqicount"=>"有无逾期",
				"fakuan"=>"逾期罚息","broke_flag"=>"提前还款","express_company"=>"快递公司",
				"express_id"=>"快递单号","express_time"=>"配送时间","express_gettime"=>"签收时间","signtime"=>"合同时间"
			);
			$styleary = array(
				"express_time"=>"20","express_gettime"=>"20","signtime"=>"20"
			);
			if($stat==1){unset($list["tit"]["install_count"],$list["tit"]["install_stat"],$list["tit"]["install_day"],$list["tit"]["yuqicount"],$list["tit"]["fakuan"],$list["tit"]["broke_flag"]);}
			$Base_model->Toexcel($titary,$list,$styleary);
		}else{
			$totalary = array();
			//其他统计数据，PS：#@$@%^@!#%~
			$tempsql = "select count(*) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where." and a.fktype=1 ";
			$temp = $Base_model->query($tempsql);
			$totalary["ordercount"] = $temp[0]["total"];
	
			$tempsql = "select count(*) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where." and unix_timestamp(a.express_gettime)>0 ";
			$temp = $Base_model->query($tempsql);
			$totalary["isexpresscount"] = $temp[0]["total"];
			
			$tempsql = "select count(*) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where." and unix_timestamp(a.express_time)>0 and (unix_timestamp(a.express_gettime)<>'' or unix_timestamp(a.express_gettime)<=0) ";
			$temp = $Base_model->query($tempsql);
			$totalary["notexpresscount"] = $temp[0]["total"];

			$tempsql = "select sum(syprice) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where." and a.paystat=1 ";
			$temp = $Base_model->query($tempsql);
			$totalary["sytotalprice"] = $temp[0]["total"];

			$total = $Base_model->query($sqlcount);//获取总数
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]["total"];
			$jsonary["data"] = $list;	
			$jsonary["totaldata"] = $totalary;		
			return json($jsonary);
		}
	}
	

    //账单列表
    public function getyqlist()
    {
		$where = "where 1 ";
		//获取全款/分期/逾期
		$stat = input('?get.stat')?input('get.stat'):'';
		if(input('?get.stat')){
			//$where .= $stat?" and unix_timestamp(a.awaketime)>0 ":" and unix_timestamp(a.awaketime)=0 ";//是否生效
			$where .= $stat?" and a.rs in (select rs from scy_orderinstall where yuqistat=1 and paystat=0) ":" and a.rs in (select rs from scy_orderinstall where yuqistat=1 and paystat=1)";//是否有效
		}
		$ystat = input('?get.ystat')?input('get.ystat'):0;
		if(input('?get.ystat')){
			$where .= " and a.ystat=".$ystat."";//是否有效
		}
		//获取关键字
		$keyword = input('?get.keyword')?input('get.keyword'):'';
		if(input('?get.keyword')){
			$where .= " and (b.car_license like '%".$keyword."%' or a.phone like '%".$keyword."%' or a.realname like '%".$keyword."%' or a.express_id like '%".$keyword."%') ";
		}
		//获取选中ids
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
			$where .= " and a.id in (0".$ids.")";
		}	

		//sql开始
		$sql = "select *,a.id as id,a.stat as stat,b.id as bid from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs ";
		$sql = $sql.$where;
		if(!input("?get.toexcel")){
			$page = input('?get.page')?input('get.page'):1;//获取页数
			$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
			$sql = $sql." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		}
		$sqlcount = "select count(*) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where;
		$Base_model = model("Base");
		$User_model = model("User");
		$Order_install_model = model("orderinstall");
		$list = $Base_model->query($sql);//获取列表
		$tempary = array("无","催收","退保","诉讼");
		foreach($list as $key=>&$val){	
			//获取地区
			$val["province"] = $Base_model->Getbyprovince($val["province"]);
			$val["city"] = $Base_model->Getbycity($val["city"]);
			$val["orderarea"] = $val["province"].$val["city"];
			//获取每期应还
			$val["install_fee"] = "￥".sprintf("%.2f", ($val["install_count"]?($val["order_price"]/$val["install_count"]):0));
			//获取保单总价
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);
			//获取已还分期/分期总数
			$val["install_stat"] = $Order_install_model->where(['rs'=>$val["rs"],'paystat'=>1])->count();
			$val["install_stat"] = $val["install_stat"]."/".$val["install_count"];
			//获取逾期罚息
			$val["fakuan"] = $Order_install_model->where(['rs'=>$val["rs"]])->sum("wymoney");
			$val["fakuan"] = "￥".sprintf("%.2f",$val["fakuan"]);
			//获取已还金额
			$val["install_ispay"] = $Order_install_model->where(['rs'=>$val["rs"],'paystat'=>1])->sum("money");
			$val["install_ispay"] = "￥".sprintf("%.2f",$val["install_ispay"]);
			//获取待还金额
			$val["install_notpay"] = $Order_install_model->where(['rs'=>$val["rs"],'paystat'=>0])->sum("money");
			$val["install_notpay"] = "￥".sprintf("%.2f",$val["install_notpay"]);
			//获取逾期金额
			$val["yqmoney"] = $Order_install_model->where(['rs'=>$val["rs"],'yuqistat'=>1])->sum("money");
			$val["yqmoney"] = "￥".sprintf("%.2f",$val["yqmoney"]);
			//获取分期复审人
			$val["secondadmin"] = $Base_model->Getadmin($val["secondadminid"]);
			//获取紧急联系人信息
			$user = $User_model->where(["phone"=>$val["phone"]])->find();
			$val["sos_realname"] = $user["sos_realname"];
			$val["sos_phone"] = $user["sos_phone"];
			$val["ystat"] = $val["ystat"]?$tempary[$val["ystat"]]:"无";
		}	
		//数据导出
		if(input("?get.toexcel")){
			$Base_model->CreateAdminLog("导出列表","导出账单列表");
			$titary = array("账单列表");
			$list["tit"] = array(
				"orderarea"=>"地区","car_license"=>"车牌号","realname"=>"车主",
				"phone"=>"手机号","yuqicount"=>"逾期天数","order_price"=>"保单总价",
				"install_count"=>"分期期数","install_fee"=>"每期应还","install_ispay"=>"已还金额",
				"install_notpay"=>"待还金额","yqmoney"=>"逾期总额","fakuan"=>"逾期罚息",
				"secondadmin"=>"保单终审人","sos_realname"=>"紧急联系人姓名","sos_phone"=>"紧急电话"
			);
			$styleary = array(
				"sos_realname"=>"20"
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
	//导出合同
	public function Toexceldoc(){
		$rs = input('get.rs');
		$url = url('bill/getinstalllist');
		$url = input('?get.rs')?$url."?rs=".input('get.rs'):$url;
		$info = model($this->table)->getByRs($rs);
		$offer = model("Userorder")->getByRs($info["rs"]);		
        return view('contract',['info' => $info,'offer' => $offer,'url' => $url]);	
	}

	public function Topdf()
	{
		$id = input('post.id');
		$info = model("order")->getbyid($id)->toArray();
		$temp = explode("_",$info["rs"]);//获取保单编号
		$info["ordernum"] = $temp[0].hexdec($temp[1]);//获取保单时间
		$filename = ROOT_PATH . 'public' . DS . 'uploads/contract/'.$info["ordernum"].'.pdf';
		if (file_exists($filename)) { 
			return '/uploads/contract/'.$info["ordernum"].'-final.pdf';
		}else{
			return 0;
		}
	}	
	//分期详情
	public function Installlist(){
		$rs = input('get.rs');
		$url = url('bill/getinstalllist');
		$url = input('?get.rs')?$url."?rs=".input('get.rs'):$url;
		$info = model($this->table)->getByRs($rs);
		
		$sql = "select sum(wymoney) as total from scy_orderinstall where rs='".$rs."' ";
		$temp = model("Base")->query($sql);
		$info["wxmoney"] = $temp[0]["total"];

		$sql = "select sum(money) as total from scy_orderinstall where rs='".$rs."' and paystat=1 ";
		$temp = model("Base")->query($sql);
		$info["paymoney"] = $temp[0]["total"];
		$offer = model("Userorder")->getByRs($info["rs"]);		
        return view('installlist',['info' => $info,'offer' => $offer,'url' => $url]);
	}
	//获取分期详情列表
    public function Getinstalllist(){
		$rs = input('get.rs');
		$sql = "select * from scy_orderinstall where rs='".$rs."' order by qishu asc";
		$list = model("Base")->query($sql);
		foreach($list as $key=>&$val){
			$val["money"] = "￥".sprintf("%.2f",$val["money"]);
			$val["beforemoney"] = "￥".sprintf("%.2f",$val["beforemoney"]);
			$val["aftermoney"] = "￥".sprintf("%.2f",$val["aftermoney"]);
			$val["wymoney"] = "￥".sprintf("%.2f",$val["wymoney"]);
		}
		$jsonary["code"] = 0;
		$jsonary["count"] = 0;
		$jsonary["data"] = $list;		
		return json($jsonary);
	}


	//投保险种/证件/详情
    public function check()
    {
		$id = input('get.id');
		$oid = input('get.oid');
		$tokentemp["token"] = "";
		$Base_model = model("Base");
		if(input('get.oid')){
			$temp = model("order")->getbyid($oid);
			$info = model("userorder")->getByRs($temp["rs"]);
			$tokentemp = model("orderphonedata")->getByRs($temp["rs"]);
		}else{
			$info = model("userorder")->getById($id);
			$temp = model("order")->getByRs($info["rs"]);
			$oid = $temp["id"];
		}
		$insurance = model("insurancecompany")->getbyid($info["company"]);
		if($insurance){     
			$insurance->toArray();
			$info["company"] = $insurance["name"];
		} 		
		$val["province"] = $Base_model->Getbyprovince($info["province"]);
		$val["city"] = $Base_model->Getbycity($info["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		//获取险种
		$info["insurance"] = $Base_model->GetInsurance($info["rs"]);

		//获取数据分析结果
		$pdetail = model("orderphonedata")->where("rs",$info["rs"])->find();
		if($pdetail){
			$pdetail = $pdetail->toArray();
			$pdetail = json_decode($pdetail["result"],true);
		}
        return view('check',['info' => $info,'pdetail' => $pdetail , "oid" => $oid , "token" => $tokentemp["token"] ]);		
	}	
}
