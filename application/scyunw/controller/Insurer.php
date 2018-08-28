<?php
namespace app\scyunw\controller;
use think\Request;
use think\Db;
use app\common\model;
use think\Session;
class insurer extends Common
{
	private $table='';
    public function _empty()
    {
        return 'API错误，请核对参数';
	}
	//商户列表视图
    public function index()
    {
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$type = input('?get.type')?input('get.type'):'';
        return view('index',['province'=>$province,'type'=>$type,'istype'=>input('?get.type')]);
	}
	
    public function getinsurer(){
		
       $page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
        $companyname = input('?get.companyname')?input('get.companyname'):'';//获取公司名称
        $jqxrate = input('?get.jqxrate')?input('get.jqxrate'):'';//获取交强险比例
        $xyxrate = input('?get.xyxrate')?input('get.xyxrate'):'';//获取商业险比例
        $type = input('?get.type')?input('get.type'):'';//获取状态
		$addtime = input('?get.addtime')?input('get.addtime'):'';//获取注册时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$logincount = input('?get.logincount')?input('get.logincount'):'';//获取登录次数
		$join = '';

		$where = " where 1 ";
		//检查状态
		if($type !== ''){
			$where .= " and (a.type = $type) ";
		}
		//检查保险公司
		if($companyname){
			$where .= " and (a.companyname  like '%".$companyname."%' ) ";
		}
		//检查消登录次数
		if($logincount){
            $where .= " and (a.logincount = $logincount) ";
		}
		if($jqxrate){
            $where .= " and (a.jqxrate = $jqxrate) ";
		}
		if($xyxrate){
            $where .= " and (a.xyxrate = $xyxrate) ";
		}
		
		//检查注册时间
		if($addtime){
			$temp = explode(" - ",$addtime);
			$where .= " and unix_timestamp(a.addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.addtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查地区
		if($province){
			$join .= ' join base_province as d on a.province = d.province_id ';
			$where .= " and (d.province_id = $province ) ";
		}
		if($city){
			$join .= ' join base_city as e on a.city = e.city_id ';
			$where .= " and (e.city_id = $city ) ";
		}
		if($area){
			$join .= ' join base_area as f on a.city = f.area_id ';
			$where .= " and (f.area_id = $area ) ";
		}
        $sql = 'select a.* from scy_insurer as a ';
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
				$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$join.$where." order by a.id desc " : $sql.$join.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$list = Db::query($sql);//获取列表
		$tyoearr = ['待审核','已审核','已停用','被驳回'];
		foreach($list as $key=>&$val){
			$val["province"] = model("Base")->Getbyprovince($val["province"]);
			$val["city"] = model("Base")->Getbycity($val["city"]);
			$val["area"] = model("Base")->Getbyarea($val["area"]);
			// $val["address"] = $val["province"].$val["city"].$val["area"].$val["address"];
			$val["test"] = $val["province"].$val["city"];
			$val["type_cg"] = $tyoearr[$val["type"]];
			$val["logincount"] = $val["logincount"] ? '共登录'.$val["logincount"].'次' : '从未登录';
		}

		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出云车险商户一览表列表");
			$titary = array("云车险商户一览表");
			$list["tit"] = array(
				"id"=>"ID","test"=>"地区","companyname"=>"保险公司","corporation"=>"负责人","cptphone"=>"联系电话","cardname"=>"结算账户名",
				"carnumber"=>"结算卡号","jqxrate"=>"交强险佣金比例","type_cg"=>"审核状态","logincount"=>"登录情况","addtime"=>"注册时间"
			);
			$styleary = array(
				"test"=>"25","companyname"=>"20","jqxrate"=>"20","addtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
		$sqlcount = "select count(*) as total  from scy_insurer as a ".$join.$where;
		$total = Db::query($sqlcount);//获取总数
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;	
		return json($jsonary);
        }
    }
    //修改保险公司状态
	public function getset(){
		if(request()->isPost()){
			$post = input('post.');
            $list = input('?post.id') ? model('Insurer')->get($post['id']) : model('Insurer');
            input('?post.name')? $list -> name = $post['name'] : false;
            input('?post.createtime')? $list -> createtime = date('Y-m-d H:i:s') : false;
            input('?post.type') ? $list -> type = $post['type'] : false;
			$result = $list-> save();
			$jsonary["code"] = $result;
			$postid = input('post.id');
			$posttype = input('post.type');
			if($postid){
				if($list->type=='0' && $posttype == '1'){
					//审核通过
					model('message')->getmsg('insurer_check',[$list->cptphone]);//负责人电话
				}
			}
			input('?post.id') ? model("Base")->CreateAdminLog("修改保险公司状态","修改保险公司状态[".$post['id']."]") : model("Base")->CreateAdminLog("新增商户类型","新增商户类型，ID[".$agentclass ->id."]");
			return json($jsonary);
		}else{
			return view('classadd');
		}
    }
    //添加保险公司
    public function addcompany(){
		if(request()->isPost()){
            $list = input('post.id')? model('Insurer')->get(input('post.id')) : model('Insurer');
			input('post.companyname')? $list -> companyname =  input('post.companyname')  : false;
			input('post.loginname')  ? $list -> loginname   =  input('post.loginname')    : false;
			input('post.password')   ? $list -> password    =  md5(input('post.password')): false;
			input('post.password')   ? $list -> truepassword=  input('post.password')     : false;
			input('post.type')    	 ? $list -> type 	 	=  input('post.type')     	  : false;
			input('?post.id') ==false? $list -> addtime 	=  date('Y-m-d H:i:s') 	 	  : false;
			input('post.lastedit')   ? $list -> lastedit    =  date('Y-m-d H:i:s')        : false;
			input('?post.province')   ? $list -> province    =  input('post.province')     : false;
			input('?post.city')       ? $list -> city        =  input('post.city')         : false;
			input('?post.area')       ? $list -> area        =  input('post.area')         : false;
			input('post.insuranceid')? $list -> insuranceid =  input('post.insuranceid')  : false;
			input('post.corporation')? $list -> corporation =  input('post.corporation')  : false;
			input('post.cardname')   ? $list -> cardname    =  input('post.cardname')     : false;
			input('post.carnumber')  ? $list -> carnumber   =  input('post.carnumber')    : false;
			input('post.carbank')    ? $list -> carbank     =  input('post.carbank')      : false;
			input('post.jqxrate')    ? $list -> jqxrate     =  input('post.jqxrate')      : false;
			input('post.xyxrate')    ? $list -> xyxrate     =  input('post.xyxrate')      : false;
			input('post.cptphone')   ? $list -> cptphone    =  input('post.cptphone')     : false;
			$result = $list-> save();
			$jsonary["code"] = $result;
			input('?post.id') ? model("Base")->CreateAdminLog("修改保险公司状态","修改保险公司状态[".$list ->id."]") : model("Base")->CreateAdminLog("新增商户类型","新增商户类型，ID[".$list ->id."]");
			return json($jsonary);
		}else{
            $sql = "select * from base_province ";
			$province = Db::query($sql);
			$sql = "select * from scy_insurancecompany ";
			$insuranceid = Db::query($sql);
			if(input('get.id')){
				$thisid = input('get.id');
				$company = model('insurer')->get($thisid);
				$city = Db::table('base_city')->where('province_id',$company -> province)->select();
				$area = Db::table('base_area')->where('city_id',$company -> city)->select();
				return view('addcompany',['province'=>$province,'company'=>$company,'city'=>$city,'area'=>$area,'insuranceid'=>$insuranceid]);
			}
			return view('addcompany',['province'=>$province,'insuranceid'=>$insuranceid]);
		}
	}
	//查看保险公司信息
	public function lookinfo(){
		$thisid = input('param.id');
		$company = model('insurer')->get($thisid);
					
		// ----------------------分割----------------------------
		if(request()->isPost()){ //当图片上传时候
			
			$imgurl = action('Upload/img');
			$oldimg = $company -> image !=='' ?  json_decode($company->image,true) : []; //数据库原先图片 为空则形成空数组
			$data = [
				$imgurl['path'], //整理格式
			];
			$imgurl['list'] = array_merge($oldimg,$data);
			$company ->image = json_encode($imgurl['list'],true); //合并数组并转为JSON
			$company ->save();
			model("Base")->CreateAdminLog("上传保险公司图片","修改ID为[".$thisid."]的保险公司图片") ;
			return Json($imgurl);
		}else{ //仅仅是展示
			$sql = "select count(*) as  count from scy_userorder  where offerinsurerid=".$thisid; //询价
			$consultation = Db::query($sql);
			$sql = "select count(*) as  count , count(if(stat in ('4,5,6'),true,null)) as success from scy_order  where insurerid=".$thisid; //保单
			$policy = Db::query($sql);
			$company -> province = model("Base")->Getbyprovince($company -> province);
			$company -> city = model("Base")->Getbycity($company -> city);
			$company -> area = model("Base")->Getbyarea($company -> area);
			$company -> test =  $company -> province.$company -> city;
			$company -> address =  $company -> province.$company -> city.$company -> area;
			$company -> image = json_decode($company -> image,true);
			$company -> count = $consultation[0]['count'];
			$company -> success = $policy[0]['count'];
			$company -> probability = $policy[0]['success'] == 0 ? '0%' : round($policy[0]['success']/$policy[0]['count']*100, 2)."%";
			return view('lookinfo',['company'=>$company]);
		}
		
	}
	//保费申请、
	public function premium(){
		
		$adminlist = model('admin')->all();
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$sary = model("Base")->GetCheckAuth();
		return view('premium',['province'=>$province,'adminlist' => $adminlist,'sary' => $sary]);
		
	}
	//保费统计、
	public function countpremium(){
		$companyname = input('?get.companyname') ? input('get.companyname') : '';
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		return view('countpremium',['province'=>$province,'companyname'=>$companyname]);
	}
	//获取保费申请列表
	public function getorder(){
		$userid = input('?get.userid')?input('get.userid'):'';//获取用户id
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$companyname = input('?get.companyname')?input('get.companyname'):'';//获取保险公司名
		$inspectadminid = input('?get.inspectadminid')?input('get.inspectadminid'):'';//获取资金初审人
		$inspecttime = input('?get.inspecttime')?input('get.inspecttime'):'';//获取初审时间
		$comfirmadminid = input('?get.comfirmadminid')?input('get.comfirmadminid'):'';//获取资金复审人
		$comfirmtime = input('?get.comfirmtime')?input('get.comfirmtime'):'';//获取复审时间
		$approadminid = input('?get.approadminid')?input('get.approadminid'):'';//获取资金结算人
		$approtime = input('?get.approtime')?input('get.approtime'):'';//获取结算时间
		$type = input('?get.type')?input('get.type'):'';//获取状态
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$join = '';
		$where = " where 1 and (a.stat < 4 or a.stat = 7) and a.stat != -1 and (a.paystat=1 or a.fqstat=4) ";
		//检查状态
		if($type !== ''){
			$where .=  $type =='count' ? " and (a.stat in (4,5))": " and (a.stat = $type) ";
		}
		if($userid){
			$where .= " and (a.userid = $userid) ";
		}
		//检查保险公司名
		if($companyname){
			$where .= " and (c.companyname  like '%".$companyname."%') ";
		}
		//检查资金初审人
		if($inspectadminid){
			$where .= " and a.inspectadminid = '$inspectadminid'";
		}
		//检查资金复审人
		if($comfirmadminid){
			$where .= " and a.comfirmadminid = '$comfirmadminid'";
		}
		//检查资金结算人
		if($approadminid){
			$where .= " and a.approadminid = '$approadminid'";
		}
		//检查初审时间
		if($inspecttime){
			$temp = explode(" - ",$inspecttime);
			$where .= " and unix_timestamp(a.inspecttime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.inspecttime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查复审时间
		if($comfirmtime){
			$temp = explode(" - ",$comfirmtime);
			$where .= " and unix_timestamp(a.comfirmtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.comfirmtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查结款时间
		if($approtime){
			$temp = explode(" - ",$approtime);
			$where .= " and unix_timestamp(a.approtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.approtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查地区
		if($province){
			$join .= ' join base_province as j on c.province = j.province_id ';
			$where .= " and (j.province_id = $province ) ";
		}
		if($city){
			$join .= ' join base_city as k on c.city = k.city_id ';
			$where .= " and (k.city_id = $city ) ";
		}
		if($area){
			$join .= ' join base_area as l on c.area = l.area_id ';
			$where .= " and (l.area_id = $area ) ";
		}
		if($companyname||1==1){
			$sql = "select a.rs,a.id,a.inspecttime,a.inspectadminid,a.comfirmtime,a.comfirmadminid,a.approtime,a.approadminid,a.stat,
				b.car_license,b.order_price, b.username,b.jqprice,b.csprice, b.syprice , 
				c.companyname,c.province,c.city,c.area
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				";
		}else{
			$sql = "select a.insurerid,a.rs,a.id,a.inspecttime,a.inspectadminid,a.comfirmtime,a.comfirmadminid,a.approtime,a.approadminid,a.stat,
				b.car_license,b.order_price, b.username,b.jqprice,b.csprice, b.syprice 
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				";			
		}
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
				$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$join.$where." order by a.id desc " : $sql.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		/* echo $sql;exit; */
		$list = Db::query($sql);//获取列表
		$tyoearr = ['初审中','复审中','拨款中','出单中','生效','过期','违约','驳回'];
		$tyoearr[-1]= '回收';
		$Base_model = model("Base");
		foreach($list as $key=>&$val){
/* 			$temp = model("insurer")->getbyid($val["insurerid"]);
			if($temp){
				$val["province"] = model("Base")->Getbyprovince($temp["province"]);
				$val["city"] = model("Base")->Getbycity($temp["city"]);
				$val["area"] = model("Base")->Getbyarea($temp["area"]);
				$val["test"] = $val["province"].$val["city"];
				$val["companyname"] = $temp["companyname"];
			}else{
				$val["province"] = $val["city"] = $val["area"] = $val["test"] = "";
				$val["companyname"] = "平台";
			} */

				$val["province"] = model("Base")->Getbyprovince($val["province"]);
				$val["city"] = model("Base")->Getbycity($val["city"]);
				$val["area"] = model("Base")->Getbyarea($val["area"]);
				$val["test"] = $val["province"].$val["city"];

				
			$val["inspectadmin"] = $Base_model->Getadmin($val["inspectadminid"]);

			$val["jqprice"] = "￥".sprintf("%.2f",$val["jqprice"]);
			$val["csprice"] = "￥".sprintf("%.2f",$val["csprice"]);
			$val["syprice"] = "￥".sprintf("%.2f",$val["syprice"]);
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);
						
			$val["inspectadminid"] = $val["inspectadminid"] ? model("Base")->Getadmin($val["inspectadminid"]): '暂未审核';
			$val["comfirmadminid"] = $val["comfirmadminid"] ? model("Base")->Getadmin($val["comfirmadminid"]): '暂未审核';
			$val["approadminid"] = $val["approadminid"] ? model("Base")->Getadmin($val["approadminid"]): '暂未审核';

			$val["inspecttime"] = $val["inspecttime"] !== '0000-00-00 00:00:00' ? $val["inspecttime"]: '暂未审核';
			$val["comfirmtime"] = $val["comfirmtime"] !== '0000-00-00 00:00:00' ? $val["comfirmtime"]: '暂未审核';
			$val["approtime"] = $val["approtime"] !== '0000-00-00 00:00:00' ? $val["approtime"]: '暂未审核';
			$val["stat_cg"]  = $tyoearr[$val["stat"]];
		}
		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出云车险商户一览表列表");
			$titary = array("云车险商户一览表");
			$list["tit"] = array(
				"id"=>"ID","car_license"=>"申请车辆","username"=>"车主姓名","companyname"=>"保险公司","jqprice"=>"交强险","csprice"=>"车船税","syprice"=>"商业险","order_price"=>"保单总额",
				"inspectadminid"=>"资金初审","inspecttime"=>"初审时间","comfirmadminid"=>"资金复审","comfirmtime"=>"复审时间","approadminid"=>"结款人","approtime"=>"结款时间","stat_cg"=>"审核状态"
			);
			$styleary = array(
				"inspectadminid"=>"20","comfirmadminid"=>"20","approadminid"=>"20","inspecttime"=>"20","inspecttime"=>"20","comfirmtime"=>"20","approtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
			
			$sqlcount = "select count(*)  as total
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				
				".$join.$where;
			$total = Db::query($sqlcount);//获取总数
			if($type == 'count'){  //如果需要统计保费
				$sqlcount = "select sum(order_price)  as other
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				
				".$join.$where;
				$jsonary["other"] = Db::query($sqlcount);//获取总数
			}
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			return json($jsonary);
		}
	}
	//获取保费申请列表
	public function getpremium(){
		$Base_model = model("Base");
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$companyname = input('?get.companyname')?input('get.companyname'):'';//获取保险公司名
		$approtime = input('?get.approtime')?input('get.approtime'):'';//获取结算时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$join = '';
		$where = " where 1 and a.stat in (3,4) ";
		//获取统计信息
		$date = date('Y-m-d');
		//历史保费
		$tempsql = "select sum(b.order_price) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs  where  a.stat in (3,4)";
		$temp = $Base_model->query($tempsql);
		$totalary[0]["sum"] = $temp[0]["total"]?$temp[0]["total"]:0; 
		//今日保费
		$tempsql = "select sum(b.order_price)  as total from scy_order as a left join scy_userorder as b on a.rs = b.rs  where  a.stat in (3,4) and unix_timestamp(a.approtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))";
		$temp = $Base_model->query($tempsql);
		$totalary[1]["sum"] = $temp[0]["total"]?$temp[0]["total"]:0; 	
		$totalary[2]["sum"] = 0;
		//检查保险公司名
		if($companyname){ $where .= " and (c.companyname  like '%".$companyname."%') ";}
		//检查结款时间
		if($approtime){
			$temp = explode(" - ",$approtime);
			$where .= " and unix_timestamp(a.approtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.approtime)<=unix_timestamp('".$temp[1]."') ";

			$tempsql = "select sum(b.order_price)  as total from scy_order as a left join scy_userorder as b on a.rs = b.rs  where  a.stat in (3,4) and unix_timestamp(a.approtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.approtime)<=unix_timestamp('".$temp[1]."') ";
			$temp = $Base_model->query($tempsql);
			$totalary[2]["sum"] = $temp[0]["total"]?$temp[0]["total"]:0; 				
		}
		//检查地区
		if($province){
			$where .= " and (c.province = $province ) ";
		}
		if($city){
			$where .= " and (c.city = $city ) ";
		}
		if($area){
			$where .= " and (c.area = $area ) ";
		}
		$sql = "select a.rs,a.id,a.inspecttime,a.inspectadminid,a.comfirmtime,a.comfirmadminid,a.approtime,a.approadminid,a.stat,
				b.car_license,b.order_price, b.username,b.jqprice,b.csprice, b.syprice , 
				c.companyname,c.province,c.city,c.area
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				";
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
				$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$join.$where." order by a.id desc " : $sql.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$list = Db::query($sql);//获取列表
		$tyoearr = ['初审中','复审中','拨款中','出单中','生效','过期','违约'];
		foreach($list as $key=>&$val){

			$val["inspectadmin"] = $Base_model->Getadmin($val["inspectadminid"]);

			$val["jqprice"] = "￥".sprintf("%.2f",$val["jqprice"]);
			$val["csprice"] = "￥".sprintf("%.2f",$val["csprice"]);
			$val["syprice"] = "￥".sprintf("%.2f",$val["syprice"]);
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);
						
			$val["inspectadminid"] = $val["inspectadminid"] ? model("Base")->Getadmin($val["inspectadminid"]): '暂未审核';
			$val["comfirmadminid"] = $val["comfirmadminid"] ? model("Base")->Getadmin($val["comfirmadminid"]): '暂未审核';
			$val["approadminid"] = $val["approadminid"] ? model("Base")->Getadmin($val["approadminid"]): '暂未审核';

			$val["inspecttime"] = $val["inspecttime"] !== '0000-00-00 00:00:00' ? $val["inspecttime"]: '暂未审核';
			$val["comfirmtime"] = $val["comfirmtime"] !== '0000-00-00 00:00:00' ? $val["comfirmtime"]: '暂未审核';
			$val["approtime"] = $val["approtime"] !== '0000-00-00 00:00:00' ? $val["approtime"]: '暂未审核';
			$val["stat_cg"]  = $tyoearr[$val["stat"]];

			$val["province"] = model("Base")->Getbyprovince($val["province"]);
			$val["city"] = model("Base")->Getbycity($val["city"]);
			$val["area"] = model("Base")->Getbyarea($val["area"]);
			$val["test"] = $val["province"].$val["city"];
		}
		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出云车险商户一览表列表");
			$titary = array("云车险商户一览表");
			$list["tit"] = array(
				"id"=>"ID","car_license"=>"申请车辆","username"=>"车主姓名","companyname"=>"保险公司","jqprice"=>"交强险","csprice"=>"车船税","syprice"=>"商业险","order_price"=>"保单总额",
				"inspectadminid"=>"资金初审","inspecttime"=>"初审时间","comfirmadminid"=>"资金复审","comfirmtime"=>"复审时间","approadminid"=>"结款人","approtime"=>"结款时间","stat_cg"=>"审核状态"
			);
			$styleary = array(
				"inspectadminid"=>"20","comfirmadminid"=>"20","approadminid"=>"20","inspecttime"=>"20","inspecttime"=>"20","comfirmtime"=>"20","approtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
			
			$sqlcount = "select count(*)  as total
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				
				".$join.$where;
			$total = Db::query($sqlcount);//获取总数

			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			$jsonary["totaldata"] = $totalary;	
			return json($jsonary);
		}
	}
	//添加 修改
	public function savepremium(){
		if(request()->isPost()){
			$id = input('post.id');
			$code = input('post.code');
			$post = input('post.');
			unset($post["code"]);
			unset($post["id"]);
			$jsonary["msg"] = '';
			//执行操作
			if($code=='hs'){
				//回收
				$post["stat"] =-1;
			}else{
				$post["stat"] = $post["stat"]+1;
				if($post["stat"]!=8){
					$adminary = array("1"=>"inspect","2"=>"comfirm","3"=>"appro");
					if($post["stat"]>0){
						$post[$adminary[$post["stat"]]."adminid"] = Session::get('admin_id');
						$post[$adminary[$post["stat"]]."time"] = date("Y-m-d H:i:s");
					}
				}
			}
			if($code=='bh'){
				//驳回
				$post["stat"] =7;
			}
			
			
		
			model("Base")->CreateAdminLog("修改订单信息","修改订单信息，ID[".$id."]");

			$result = model("order")->where('id', $id)->update($post);
			// if($result){
			// 	//修改后查看code  判断审核等进行短信发送
			
			// }
			$jsonary["code"] = $result;
			return json($jsonary);
		}else{
			return view('classadd');
		}		
	}
	//查看车辆
	public function carinfo()
    {
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$sary = model("Base")->GetCheckAuth();
        return view('premium',['province'=>$province,'sary' => $sary]);
	}

	//查看结款
	public function approinfo(){
		$id = input("get.id");
		$Base_model = model("Base");
		$order = model("order")->getbyid($id);
		$order["approadmin"] = $Base_model->Getadmin($order["approadminid"]);
        return view('approinfo',['order' => $order]);

	}

	//佣金统计、
	public function countbrokerage(){
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		return view('countbrokerage',['province'=>$province]);
	}

	//获取保费佣金列表
	public function getbbrokerage(){
		$Base_model = model("Base");
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$companyname = input('?get.companyname')?input('get.companyname'):'';//获取保险公司名
		$approtime = input('?get.approtime')?input('get.approtime'):'';//获取结算时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$join = '';
		$where = " where 1 and a.stat in (3,4) ";
		//检查保险公司名
		if($companyname){ $where .= " and (c.companyname  like '%".$companyname."%') ";}
		//检查结款时间
		if($approtime){
			$temp = explode(" - ",$approtime);
			$where .= " and unix_timestamp(a.approtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.approtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查地区
		if($province){
			$where .= " and (c.province = $province ) ";
		}
		if($city){
			$where .= " and (c.city = $city ) ";
		}
		if($area){
			$where .= " and (c.area = $area ) ";
		}
		$sql = "select a.rs,a.id,a.inspecttime,a.inspectadminid,a.comfirmtime,a.comfirmadminid,a.approtime,a.approadminid,a.stat,
				b.car_license,b.order_price, b.username,b.jqprice,b.csprice, b.syprice , c.jqxrate , c.xyxrate, c.csxrate,
				c.companyname,c.province,c.city,c.area
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				";
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
				$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$join.$where." order by a.id desc " : $sql.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$list = Db::query($sql);//获取列表
		$tyoearr = ['初审中','复审中','拨款中','出单中','生效','过期','违约'];
		$Base_model = model("Base");
		foreach($list as $key=>&$val){

			$val["inspectadmin"] = $Base_model->Getadmin($val["inspectadminid"]);
			
			$val["jqcommission"] = ($val["jqxrate"] * $val["jqprice"] * 0.01);
			$val["sycommission"] = ($val["xyxrate"] * $val["syprice"] * 0.01);
			$val["cscommission"] = ($val["csxrate"] * $val["csprice"] * 0.01);
			$val["commission"] = $val["jqcommission"] + $val["sycommission"] + $val["cscommission"];

			$val["jqcommission"] = "￥".sprintf("%.2f",$val["jqcommission"]);
			$val["sycommission"] = "￥".sprintf("%.2f",$val["sycommission"]);
			$val["commission"] = "￥".sprintf("%.2f",$val["commission"]);

			$val["jqprice"] = "￥".sprintf("%.2f",$val["jqprice"]);
			$val["csprice"] = "￥".sprintf("%.2f",$val["csprice"]);
			$val["syprice"] = "￥".sprintf("%.2f",$val["syprice"]);
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);

			$val["jqxrate"] = $val["jqxrate"]."%";
			$val["xyxrate"] = $val["xyxrate"]."%";
						
			$val["inspectadminid"] = $val["inspectadminid"] ? model("Base")->Getadmin($val["inspectadminid"]): '暂未审核';
			$val["comfirmadminid"] = $val["comfirmadminid"] ? model("Base")->Getadmin($val["comfirmadminid"]): '暂未审核';
			$val["approadminid"] = $val["approadminid"] ? model("Base")->Getadmin($val["approadminid"]): '暂未审核';

			$val["inspecttime"] = $val["inspecttime"] !== '0000-00-00 00:00:00' ? $val["inspecttime"]: '暂未审核';
			$val["comfirmtime"] = $val["comfirmtime"] !== '0000-00-00 00:00:00' ? $val["comfirmtime"]: '暂未审核';
			$val["approtime"] = $val["approtime"] !== '0000-00-00 00:00:00' ? $val["approtime"]: '暂未审核';
			$val["stat_cg"]  = $tyoearr[$val["stat"]];

			$val["province"] = model("Base")->Getbyprovince($val["province"]);
			$val["city"] = model("Base")->Getbycity($val["city"]);
			$val["area"] = model("Base")->Getbyarea($val["area"]);
			$val["test"] = $val["province"].$val["city"];
		}
		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出云车险商户一览表列表");
			$titary = array("云车险商户一览表");
			$list["tit"] = array(
				"id"=>"ID","car_license"=>"申请车辆","username"=>"车主姓名","companyname"=>"保险公司","jqprice"=>"交强险","csprice"=>"车船税","syprice"=>"商业险","order_price"=>"保单总额",
				"inspectadminid"=>"资金初审","inspecttime"=>"初审时间","comfirmadminid"=>"资金复审","comfirmtime"=>"复审时间","approadminid"=>"结款人","approtime"=>"结款时间","stat_cg"=>"审核状态"
			);
			$styleary = array(
				"inspectadminid"=>"20","comfirmadminid"=>"20","approadminid"=>"20","inspecttime"=>"20","inspecttime"=>"20","comfirmtime"=>"20","approtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
			$sqlcount = "select count(*)  as total
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				".$join.$where;
			$total = Db::query($sqlcount);//获取总数


			$date = date("Y-m-d");

			//总佣金数据
			//交强险佣金
			$where = " a.stat in (3,4)";
			$tempsql = "select sum(b.jqprice*c.jqxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
			$temp = $Base_model->query($tempsql);
			$totalary[1]["sum"] = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 
			//商业险佣金
			$tempsql = "select sum(b.syprice*c.xyxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
			$temp = $Base_model->query($tempsql);
			$totalary[2]["sum"] = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 
			//车船险佣金
			$tempsql = "select sum(b.csprice*c.csxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
			$temp = $Base_model->query($tempsql);
			$temp = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 

			$totalary[0]["sum"] = $totalary[1]["sum"] + $totalary[2]["sum"] + $temp;	


			//今日佣金数据
			//交强险佣金
			$where = "  a.stat in (3,4) and unix_timestamp(a.approtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))";
			$tempsql = "select sum(b.jqprice*c.jqxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
			$temp = $Base_model->query($tempsql);
			$totalary[4]["sum"] = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 
			//商业险佣金
			$tempsql = "select sum(b.syprice*c.xyxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
			$temp = $Base_model->query($tempsql);
			$totalary[5]["sum"] = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 
			//车船险佣金
			$tempsql = "select sum(b.csprice*c.csxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
			$temp = $Base_model->query($tempsql);
			$temp = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 

			$totalary[3]["sum"] = $totalary[4]["sum"] + $totalary[5]["sum"] + $temp;		

			if($approtime){
				$temp = explode(" - ",$approtime);
				//本期佣金数据
				//交强险佣金
				$where = " a.stat in (3,4) and unix_timestamp(a.approtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.approtime)<=unix_timestamp('".$temp[1]."') ";
				$tempsql = "select sum(b.jqprice*c.jqxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
				$temp = $Base_model->query($tempsql);
				$totalary[7]["sum"] = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 
				//商业险佣金
				$tempsql = "select sum(b.syprice*c.xyxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
				$temp = $Base_model->query($tempsql);
				$totalary[8]["sum"] = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 
				//车船险佣金
				$tempsql = "select sum(b.csprice*c.csxrate*0.01) as total from scy_order as a  left join scy_userorder as b on a.rs = b.rs left join scy_insurer as c on a.insurerid=c.id  where ".$where;
				$temp = $Base_model->query($tempsql);
				$temp = $temp[0]["total"]?sprintf("%.2f",$temp[0]["total"]):0; 

				$totalary[6]["sum"] = $totalary[7]["sum"] + $totalary[8]["sum"] + $temp;	
			}else{
				$totalary[6]["sum"] = $totalary[7]["sum"] = $totalary[8]["sum"] = 0;	
			}		

			$jsonary["code"] = 0;

			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			$jsonary["totaldata"] = $totalary;	
			return json($jsonary);
		}
	}	
	
	public function Toexcel(){
		$id = input('get.id');
		$Base_model = model("Base");
		$info = model('insurer')->getById($id);	
		$sql = "select count(*) as  count from scy_userorder  where offerinsurerid=".$id; //询价
		$consultation = Db::query($sql);
		$sql = "select count(*) as  count , count(if(stat in ('4,5,6'),true,null)) as success from scy_order  where insurerid=".$id; //保单
		$policy = Db::query($sql);
		$info -> province = model("Base")->Getbyprovince($info -> province);
		$info -> city = model("Base")->Getbycity($info -> city);
		$info -> area = model("Base")->Getbyarea($info -> area);
		$info -> test =  $info -> province.$info -> city;
		$info -> address =  $info -> province.$info -> city.$info -> area;
		$info -> image = json_decode($info -> image,true);
		$info -> count = $consultation[0]['count'];
		$info -> success = $policy[0]['count'];
		$info -> probability = $policy[0]['success'] == 0 ? '0%' : round($policy[0]['success']/$policy[0]['count']*100, 2)."%";
		// echo 1;exit;
		// $Base_model->CreateAdminLog("导出EXCEL","导出保险[".$info->username."]的账户信息");
		$titary = array("保险公司[".$info->companyname."]的账户信息");	
		$list = array(		
			//单元格(A)，单元格填充(uploads/20171020/3ff9134973ba25b8e4cc33e16c840113.jpg)，扩展单元格(B), 宽度 , 高度 ,是否是图片
			// array(array("A",$info["headimg"],"B",0,120,1) ,array("C",$info["headimg"],"D",0,120,1)),

			array(array("A",$info["test"],'',40) , array("B",$info["corporation"],'',40)),
			array(array("A","公司地址",'',40) , array("B",$info["address"],'',40)),
			array(array("A","负责人/联系人",'',40) , array("B",$info["corporation"],'',40)),
			array(array("A","联系电话",'',40) , array("B",$info["cptphone"],'',40)),
			array(array("A","登录用户名",'',40) , array("B",$info["loginname"],'',40)),
			array(array("A","注册时间",'',40) , array("B",$info["addtime"],'',40)),



			array(array("A","登陆次数",'',40) , array("B",$info["logincount"],'',40)),
			array(array("A","询价总次数",'',40) , array("B",$info["count"],'',40)),
			array(array("A","出单总次数",'',40) , array("B",$info["success"],'',40)),
			array(array("A","出单成交率",'',40) , array("B",$info['probability']?$info['probability']:'','',40)),
			array(array("A","消费返点",'',40) , array("B",$info["jqxrate"],'',40)),
			array(array("A","结算详情",'B',40) ),
			array(array("A","结算账户名",'',40) , array("B",$info["cardname"],'',40)),
			array(array("A","结算卡号",'',40) , array("B",$info["carnumber"],'',40)),
			array(array("A","开户银行",'',40) , array("B",$info["carbank"],'',40)),
			array(array("A","交强险佣金比例",'',40) , array("B",$info["jqxrate"],'',40)),
			array(array("A","商业险佣金比例",'',40) , array("B",$info["xyxrate"],'',40)),
			array(array("A","相关证明资料扫描件",'B',40) ),
		);
		(array)$image = $info -> image;
		if($image){
			foreach($image as $key =>&$val){
				$list[] = [ [
					'A',$val,'B','40','100','1'
					]
				];
			};
		}
		model("Base")->CreateAdminLog("导出EXCEL","导出ID为".$id."的保险公司信息");
		model("Base")->Toexcelinfo($titary,$list,"B");
		exit;
	}

	public function getloginlog()
	{
		$id = input('get.userid');
		return view('loginlog',['id'=>$id]);
	}
	public function getinsurerlogbylogin()
	{
		$userid = input('?get.userid')? input('get.userid'):'';//检查商户ID
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$addtime = input('?get.addtime')?input('get.addtime'):'';//获取添加时间
		
		$join = '';
		$where = [];
	
		//检查商户ID
		if($userid){
			$where['userid'] = $userid;
		}
		//检查消费时间
		if($addtime){
			$temp = explode(" - ",$addtime);
			// $where[] .= " and unix_timestamp(a.addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.addtime)<=unix_timestamp('".$temp[1]."') ";
			$where[] = ['exp',"unix_timestamp(addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(addtime)<=unix_timestamp('".$temp[1]."')"];
		}
		$list = model('LogInsurer')
		->where('userid',$userid)
		->where($where)
		->page($page,$limits)
		->select();
		$count = model('LogInsurer')
			->where('type','登录')
			->where('userid',$userid)
			->where($where)
			->page($page,$limits)
			->count();
		$jsonary["code"] = 0;
		$jsonary["count"] = $count;
		$jsonary["data"] = $list;	
		return json($jsonary);
	}
	//驳回保险公司
	public function bohui(){
		$id = input('?post.id')? input('post.id'):exit($this->error('ID不存在'));
		$insurer = model('insurer')->get($id);
		$insurer->type = 3;
		if($insurer->save()){
			//驳回成功 发送短讯
			$msg = input('post.msg');
			model('message')->getmsg('insurer_check',[$insurer->cptphone,$msg]);//负责人电话 驳回原因
			//发送完成后返回信息
			model("Base")->CreateAdminLog("驳回保险公司","驳回ID为".$id."的保险公司，驳回理由：".$msg);
			return $this->success('驳回成功');
		}
	}
	//最新更新的车辆信息查看
	public function carinfonew(){
		$rs = input('?get.rs')? input('get.rs'):exit($this->error('ID不存在'));//检查RS
		$carinfo = model('userorder')->getByRs($rs);
		$carinfo["checkadminid"] = $carinfo["checkadminid"] ? model("Base")->Getadmin($carinfo["checkadminid"]): '暂未审核';
		return view('carinfonew',['carinfo'=>$carinfo]);
	}
	//结款统计视图
	public function payment(){
		$insurer = model('insurer')->select();
		return view('payment',['insurer'=>$insurer]);
	}
	//结款统计数据
	public function getpayment(){
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$companyname = input('get.companyname')?input('get.companyname'):false;//保险公司
		$addtime = input('get.addtime')?input('get.addtime'):false;//添加时间
		$paytime = input('get.paytime')?input('get.paytime'):false;//结款时间
		$where = [];
		//筛选
		if($companyname){
			$where['insurerid'] = $companyname;
		}
		if($addtime){
			$temp = explode(" - ",$addtime);
			$where[] = ['exp',"unix_timestamp(addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(addtime)<=unix_timestamp('".$temp[1]."')"];
		}
		if($paytime){
			$temp = explode(" - ",$paytime);
			$where[] = ['exp',"unix_timestamp(paytime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(paytime)<=unix_timestamp('".$temp[1]."')"];
		}
		//数据
		$list = model('insurertx')
			->where($where)
			->page($page,$limits)
			->select();
		foreach($list as $key =>&$val){
			$val['insurer'] = model('insurer')->getById($val['insurerid'])->companyname;
		}
		//统计本期
		$thissearch = model('insurertx')
		->where($where)
		->sum('amount');

		//统计今日金额
		$today = model('insurertx')
		->where($where)
		->where('','exp','to_days(addtime) = to_days(now())')
		->sum('amount');
		//统计历史金额
		$histry = model('insurertx')
		->where($where)
		->where('','exp','to_days(addtime) < to_days(now())')
		->sum('amount');
		//统计数量
		$count =  model('insurertx')->count();

		$jsonary["code"] = 0;
		$jsonary["count"] = $count;
		$jsonary["data"] = $list;	
		$jsonary["today"] = $today;	
		$jsonary["histry"] = $histry;
		$jsonary["thissearch"] = $thissearch;	
		return json($jsonary);
	}
	//查看结款详情
	public function paymentinfo(){
		$id = input('?get.id')? input('get.id'):exit($this->error('ID不存在'));//检查RS
		$insurer = model('insurertx')->getById($id);
		$insurer->insurer = model('insurer')->getById($insurer->insurerid)->companyname;
		return view('paymentinfo',['insurer'=>$insurer]);
	}
	//新增：审核等之前先查看信息
	public function likepremium(){
		$datatype=input('get.datatype');
		$id = input('?get.id')?input('get.id'):exit('ID不存在');//获取区
		
		$sql = "select a.rs,a.img,a.msg,a.insurerid,a.id,a.inspecttime,a.inspectadminid,a.comfirmtime,a.comfirmadminid,a.approtime,a.approadminid,a.stat,
				b.car_license,b.order_price, b.username,b.jqprice,b.csprice, b.syprice , 
				c.companyname,c.province,c.city,c.area
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				where 1 and (a.stat < 4 or a.stat = 7) and  (a.paystat=1 or a.fqstat=4) and a.id = ".$id ;
		//SQl语句开始
		$list = Db::query($sql);//获取列表
		$tyoearr = ['初审中','复审中','拨款中','出单中','生效','过期','违约','驳回'];
		$Base_model = model("Base");
		foreach($list as $key=>&$val){

			$val["inspectadmin"] = $Base_model->Getadmin($val["inspectadminid"]);

			$val["jqprice"] = "￥".sprintf("%.2f",$val["jqprice"]);
			$val["csprice"] = "￥".sprintf("%.2f",$val["csprice"]);
			$val["syprice"] = "￥".sprintf("%.2f",$val["syprice"]);
			$val["order_price"] = "￥".sprintf("%.2f",$val["order_price"]);
						
			$val["inspectadminid"] = $val["inspectadminid"] ? model("Base")->Getadmin($val["inspectadminid"]): '暂未审核';
			$val["comfirmadminid"] = $val["comfirmadminid"] ? model("Base")->Getadmin($val["comfirmadminid"]): '暂未审核';
			$val["approadminid"] = $val["approadminid"] ? model("Base")->Getadmin($val["approadminid"]): '暂未审核';

			$val["inspecttime"] = $val["inspecttime"] !== '0000-00-00 00:00:00' ? $val["inspecttime"]: '暂未审核';
			$val["comfirmtime"] = $val["comfirmtime"] !== '0000-00-00 00:00:00' ? $val["comfirmtime"]: '暂未审核';
			$val["approtime"] = $val["approtime"] !== '0000-00-00 00:00:00' ? $val["approtime"]: '暂未审核';
			$val["stat_cg"]  = $tyoearr[$val["stat"]];

			$val["province"] = model("Base")->Getbyprovince($val["province"]);
			$val["city"] = model("Base")->Getbycity($val["city"]);
			$val["area"] = model("Base")->Getbyarea($val["area"]);
			$val["test"] = $val["province"].$val["city"];
		}
		
		return view('likepremium',['list'=>$list[0],'datatype'=>$datatype]);
	}

}