<?php
namespace app\scyunw\controller;
use think\Request;
use think\Db;
use app\common\model;
use think\Session;
class Selforder extends Common
{
	private $table='order';
    public function _empty()
    {
        return 'API错误，请核对参数';
	}
	
	public function index(){
		$sary = model("Base")->GetCheckAuth();
		$stat = input("get.stat");
		return view('index',['stat'=>$stat,'sary' => $sary]);
	}	
	
    public function info()
    {
		$Base_model = model("Base");
		$id = input('get.id');
		$stat = input("get.stat");
		$info = model($this->table)->getById($id);
		$offer = model("Userorder")->getByRs($info["rs"]);
		$info["ordernum"] = $info["ordernumber"];
		$temp = explode("_",$info["rs"]);//获取保单编号
		$info["ordernumber"] = $temp[0].hexdec($temp[1]);//获取保单时间
		$info["inspectadmin"] = $Base_model->Getadmin($info["inspectadminid"]);
		$info["comfirmadmin"] = $Base_model->Getadmin($info["comfirmadminid"]);
		
		$val["province"] = $Base_model->Getbyprovince($offer["province"]);
		$val["city"] = $Base_model->Getbycity($offer["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		$info["endtime"] = date("Y-m-d",strtotime("+1 year",strtotime($info["awaketime"])));
		$info["insurance"] = $Base_model->GetInsurance($info["rs"]);
		$info["orderpics"] = json_decode($info["orderpics"],true);
		$insurance = model("insurer")->getbyid($offer["offerinsurerid"]);
		$useraddress = getAddressByuserid($offer["userid"],1);
		$offer["companyname"] = "平台";       		
		
        return view('info',['info' => $info,'offer' => $offer , 'stat' => $stat , 'useraddress' => $useraddress]);
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
				if(isset($post["ordernumber"])){
					$post["stat"] = 4;
					model("Base")->CreateAdminLog("生成订单","保单id[".$id."]已出单");
				}
				if(isset($post["orderpic"])){
					$post["orderpics"] = json_encode(array("orderpic"=>input('post.orderpic'),"expresspic"=>input('post.expresspic'),"otherpic"=>input('post.otherpic')));
					unset($post["orderpic"],$post["expresspic"],$post["otherpic"],$post["file"]);
				}
				$result = model($this->table)->where('id', $id)->update($post);
			}
		}
		return json($jsonary);
	}	
	
    //列表
    public function getlist()
    {
		$where = "where b.stat = 2 and b.offerinsurerid = 0 and (a.paystat = 1 or (a.fktype=0 and a.fqstat=4 )) ";
		//获取有效/失效保单
		$stat = input('?get.stat')?input('get.stat'):'';
		if(input('?get.stat')){
			switch($stat){
				case 0:
					$where .= " and a.stat<4 ";
					break;
				case 1:
					$where .= " and a.express_id = '' and a.stat = 4 ";
					break;
				case 2:
					$where .= " and a.express_id <> '' and a.stat = 4 ";
					break;
			}
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
}