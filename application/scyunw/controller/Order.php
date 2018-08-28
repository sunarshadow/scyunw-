<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Order extends Common
{
	private $table='order';
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
    public function index()
    {
		$sql = "select * from base_province ";
		$province = model("Base")->query($sql);
		$url = url('order/getlist');
		$url = input('?get.stat')?$url."?stat=".input('get.stat'):$url;
        return view('index',['province' => $province,'url' => $url,'stat' => input('get.stat')]);
    }
    public function info()
    {
		$Base_model = model("Base");
		$id = input('get.id');
		$info = model($this->table)->getById($id);
		$offer = model("Userorder")->getByRs($info["rs"]);

		$temp = explode("_",$info["rs"]);//获取保单编号
		$info["ordernumber"] = $temp[0].hexdec($temp[1]);//获取保单时间
		$info["inspectadmin"] = $Base_model->Getadmin($info["inspectadminid"]);
		$info["comfirmadmin"] = $Base_model->Getadmin($info["comfirmadminid"]);
		$info["firstadmin"] = $Base_model->Getadmin($info["firstadminid"]);
		$info["secondadmin"] = $Base_model->Getadmin($info["secondadminid"]);
		
		$val["province"] = $Base_model->Getbyprovince($offer["province"]);
		$val["city"] = $Base_model->Getbycity($offer["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		$info["endtime"] = date("Y-m-d",strtotime("+1 year",strtotime($info["awaketime"])));
		$info["insurance"] = $Base_model->GetInsurance($info["rs"]);
		$info["orderpics"] = json_decode($info["orderpics"],true);
		if($offer["offerinsurerid"] == 0){
			$offer["companyname"] = "平台";
		}else{
			$insurance = model("insurer")->getbyid($offer["offerinsurerid"]);
			if($insurance){     
				$insurance->toArray();
				$offer["companyname"] = $insurance["companyname"];
			}     
		}		
		
        return view('info',['info' => $info,'offer' => $offer]);
    }
    //列表
    public function getlist()
    {
		$where = "where 1 ";
		//获取有效/失效保单
		$stat = input('?get.stat')?input('get.stat'):'';
		if(input('?get.stat')){
			//$where .= $stat?" and unix_timestamp(a.awaketime)>0 ":" and unix_timestamp(a.awaketime)=0 ";//是否生效
			$where .= $stat?" and a.stat=4 ":" and a.stat>4 ";//是否有效
		}
		//获取关键字
		$keyword = input('?get.keyword')?input('get.keyword'):'';
		if(input('?get.keyword')){
			$where .= " and (b.car_license like '%".$keyword."%' or a.phone like '%".$keyword."%' or a.realname like '%".$keyword."%') ";
		}
		//获取地区
		$area = input('?get.area')?input('get.area'):'';
		if($area){
			$where .= " and b.area='".$area."' ";
		}
		//获取生效日期
		$awake = input('?get.awake')?input('get.awake'):'';
		if($awake){
			$temp = explode(" - ",$awake);
			$where .= " and unix_timestamp(a.awaketime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.awaketime)<=unix_timestamp('".$temp[1]."') ";
		}
		//获取到期日期
		$end = input('?get.end')?input('get.end'):'';
		if($end){
			$temp = explode(" - ",$end);
			$where .= " and unix_timestamp(a.awaketime)>=unix_timestamp(DATE_SUB('".$temp[0]."', INTERVAL 1 YEAR)) and unix_timestamp(a.awaketime)<=unix_timestamp(DATE_SUB('".$temp[1]."', INTERVAL 1 YEAR)) ";
		}
		//获取出单日期
		$start = input('?get.start')?input('get.start'):'';
		if($start){
			$temp = explode(" - ",$start);
			$where .= " and unix_timestamp(a.checktime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.checktime)<=unix_timestamp('".$temp[1]."') ";
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
		$sqlcount = "select count(*) as total from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs  ".$where;
		$list = $Base_model->query($sql);//获取列表
		foreach($list as $key=>&$val){
			$val["province"] = $Base_model->Getbyprovince($val["province"]);
			$val["city"] = $Base_model->Getbycity($val["city"]);
			$val["orderarea"] = $val["province"].$val["city"];
			$val["fktype"] = $val["fktype"]?"全款支付":"分期支付";
			$val["jqprice"] = "￥".sprintf("%.2f",$val["jqprice"]);
			$val["csprice"] = "￥".sprintf("%.2f",$val["csprice"]);
			$val["syprice"] = "￥".sprintf("%.2f",$val["syprice"]);
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);
			$val["endtime"] = date("Y-m-d H:i:s",strtotime("+1 year",strtotime($val["awaketime"])));
			$val["reason"] = $val["stat"]>1?($val["stat"]==2?"过期作废":"违约作废"):"";
			$insurance = model("insurancecompany")->getbyid($val["company"]);
			$val["insurname"] = $insurance["name"];
			$val["companyname"] = $Base_model->Getinsurer($val["insurerid"]);
		}
		if(input("?get.toexcel")){
			$Base_model->CreateAdminLog("导出列表","导出保单列表");
			$titary = array("保单列表");
			$list["tit"] = array(
				"car_license"=>"车牌号","realname"=>"姓名","phone"=>"手机",
				"orderarea"=>"归属地","jqprice"=>"交强险","csprice"=>"车船税",
				"syprice"=>"商业险","order_price"=>"保费总额","checktime"=>"出单时间",
				"awaketime"=>"生效时间","endtime"=>"到期时间","fktype"=>"支付方式","reason"=>"原因"
			);
			$styleary = array(
				"checktime"=>"20","awaketime"=>"20","endtime"=>"20"
			);
			if($stat){unset($list["tit"]["reason"]);}
			$Base_model->Toexcel($titary,$list,$styleary);
		}else{
			$total = $Base_model->query($sqlcount);//获取总数
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]["total"];
			$jsonary["data"] = $list;		
			return json($jsonary);
		}
    }
    //添加/修改
    public function GetSet(){
		$id = input('post.id');
		$post = input('post.');
		unset($post["id"]);
		$jsonary["msg"] = '';

		//执行操作
		if(!$jsonary["msg"]){
			if($id>0){//修改
				$result = model($this->table)->where('id', $id)->update($post);
				// model("Base")->CreateAdminLog("编辑权限组","修改权限组[".$post["title"]."]");
			}else{//增加
				$post["addtime"] = date("Y-m-d H:i:s");
				$result = model($this->table)->insert($post);
				// model("Base")->CreateAdminLog("添加权限组","添加权限组[".$post["title"]."]");
			}
		}
		return json($jsonary);
	}
	//删除
	public function GetDel(){
		$ids = input("post.ids");
		$result = model($this->table)->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除权限组","删除权限组，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
	//导出内容
	public function Toexcel(){
		$Base_model = model("Base");
		$id = input('get.id');
		$info = model($this->table)->getById($id);
		$offer = model("Userorder")->getByRs($info["rs"]);

		$temp = explode("_",$info["rs"]);//获取保单编号
		$info["ordernumber"] = $temp[0].hexdec($temp[1]);//获取保单时间
		$info["inspectadmin"] = $Base_model->Getadmin($info["inspectadminid"]);
		$info["comfirmadmin"] = $Base_model->Getadmin($info["comfirmadminid"]);
		
		$val["province"] = $Base_model->Getbyprovince($offer["province"]);
		$val["city"] = $Base_model->Getbycity($offer["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		$info["endtime"] = date("Y-m-d",strtotime("+1 year",strtotime($info["awakedate"])));
		$info["insurance"] = $Base_model->GetInsurance($info["rs"],"","\n");
		if($offer["offerinsurerid"]>0){
		$insurance = model("insurer")->getbyid($offer["offerinsurerid"]);
		if($insurance){     
			$insurance->toArray();
			$offer["companyname"] = $insurance["companyname"];
		}  
		}else{
			$offer["companyname"] = "平台";
		}
		// $Base_model->CreateAdminLog("导出EXCEL","导出保单[".$info->username."]的账户信息");
		$titary = array("保单详情");				
		$list = array(		
			array(array("A",$offer["headimg"],"C",0,120,1,3),array("D","车主姓名"),array("E",$info["realname"],"H") ),
			array(array("D","身份证"),array("E",$info["id_license"],"H") ),
			array(array("D","保单收货地址"),array("E",$info["express_address"],"H") ),
			array(array("D","保单联系电话"),array("E",$info["phone"],"H") ),
			array(array("A","保单联系电话"),array("B",$info["ordernumber"],"H") ),
			array(array("A","询价时间"),array("B",$info["addtime"],"H") ),
			array(array("A","签约时间"),array("B",$info["signtime"],"H") ),
			array(array("A","询价终端"),array("B",$info["signtime"],"H") ),
			array(array("A","支付方式"),array("B",$info["signtime"],"H") ),
			array(array("A","初审人"),array("B",$info["inspectadmin"],"D"),array("E","初审时间"),array("F",$info["inspecttime"],"H") ),
			array(array("A","复审人"),array("B",$info["comfirmadmin"],"D"),array("E","复审时间"),array("F",$info["comfirmtime"],"H") ),
			array(array("A","车牌照"),array("B",$offer["car_license"],"D"),array("E","投保公司"),array("F",$offer["companyname"],"H") ),
			array(array("A","出单时间"),array("B",$info["checktime"],"D"),array("E","投保地区"),array("F",$info["orderarea"],"H") ),
			array(array("A","生效时间"),array("B",$info["awakedate"],"D"),array("E","交强险"),array("F",$offer["jqprice"],"H") ),
			array(array("A","到期时间"),array("B",$info["endtime"],"D"),array("E","车船税"),array("F",$offer["csprice"],"H") ),
			array(array("A","快递公司"),array("B",$info["express_company"],"D"),array("E","商业险"),array("F",$offer["syprice"],"H") ),
			array(array("A","快递单号"),array("B",$info["express_id"],"D"),array("E","合计金额"),array("F",$offer["order_price"],"H") ),
			array(array("A","保单详情"),array("B",$info["insurance"],"H",0,120) ),
			array(array("A","用户身份证件","H") ),
			array(array("A",$offer["id_img"],"D",0,120,1),array("E",$offer["id_img_b"],"H",0,120,1) ),
			array(array("A","机动车行驶证","H") ),
			array(array("A",$offer["car_img"],"D",0,120,1),array("E",$offer["car_img_b"],"H",0,120,1) ),	
		);
		model("Base")->Toexcelinfo($titary,$list,"H");	
	}
    
}
