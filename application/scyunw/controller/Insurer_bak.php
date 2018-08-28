<?php
namespace app\scyunw\controller;
use think\Request;
use think\Db;
use app\common\model;
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
        return view('index',['province'=>$province]);
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
		$tyoearr = ['未审核','已审核'];
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
			if(input('get.id')){
				$thisid = input('get.id');
				$company = model('insurer')->get($thisid);
				$city = Db::table('base_city')->where('province_id',$company -> province)->select();
				$area = Db::table('base_area')->where('city_id',$company -> city)->select();
				return view('addcompany',['province'=>$province,'company'=>$company,'city'=>$city,'area'=>$area,]);
			}
			return view('addcompany',['province'=>$province]);
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
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		return view('premium',['province'=>$province]);
		
	}
	//保费统计、
	public function countpremium(){
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$date = date('Y-m-d');
		$sql = "select sum(b.order_price)  as histry from scy_order as a  left join scy_userorder as b on a.rs = b.rs  where  a.stat in (4,5)";//历史保费
		$histry = Db::query($sql);
		$sql = "select sum(b.order_price)  as today from scy_order as a left join scy_userorder as b on a.rs = b.rs  where  a.stat in (4,5) and unix_timestamp(a.approtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))";//今日保费
		$today = Db::query($sql);
		return view('countpremium',['province'=>$province,'today'=>$today[0],'histry'=>$histry[0]]);
	}
	//获取保费申请列表
	public function getpremium(){
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
		$where = " where 1 and a.stat <> -1 ";
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
			$where .= " and d.name = '$inspectadminid'";
		}
		//检查资金复审人
		if($comfirmadminid){
			$where .= " and e.name = '$comfirmadminid'";
		}
		//检查资金结算人
		if($approadminid){
			$where .= " and f.name = '$approadminid'";
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
		$sql = "select a.id,a.inspecttime,a.inspectadminid,a.comfirmtime,a.comfirmadminid,a.approtime,a.approadminid,a.stat,
				b.car_license,b.order_price, b.username,b.jqprice,b.csprice, b.syprice , 
				c.companyname,c.province,c.city,c.area,
				d.name as csname , 
				e.name as fsname ,
				f.name as jsname , 
				g.title as cstitle ,
				h.title as fstitle ,
				i.title as jstitle 
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				
				join scy_admin as d on a.inspectadminid = d.id   /*连接管理员，用于获取管理员名称 初审 标识e*/
				join scy_admin as e on a.comfirmadminid = e.id 	 /*连接管理员，用于获取管理员名称 复审 标识f*/
				join scy_admin as f on a.approadminid = f.id 	 /*连接管理员，用于获取管理员名称 结款 标识g*/

				join scy_admininspect as g on g.id = e.sid 		 /*连接审核组，用于获取审核组名称 初审 标识h*/
				join scy_admininspect as h on h.id = e.sid 		 /*连接审核组，用于获取审核组名称 复审 标识i*/
				join scy_admininspect as i on i.id = e.sid       /*连接审核组，用于获取审核组名称 初结款审 标识j*/
				
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
				
				join scy_admin as d on a.inspectadminid = d.id   /*连接管理员，用于获取管理员名称 初审 标识e*/
				join scy_admin as e on a.comfirmadminid = e.id 	 /*连接管理员，用于获取管理员名称 复审 标识f*/
				join scy_admin as f on a.approadminid = f.id 	 /*连接管理员，用于获取管理员名称 结款 标识g*/

				join scy_admininspect as g on g.id = e.sid 		 /*连接审核组，用于获取审核组名称 初审 标识h*/
				join scy_admininspect as h on h.id = e.sid 		 /*连接审核组，用于获取审核组名称 复审 标识i*/
				join scy_admininspect as i on i.id = e.sid       /*连接审核组，用于获取审核组名称 初结款审 标识j*/
				
				".$join.$where;;
			$total = Db::query($sqlcount);;//获取总数
			if($type == 'count'){  //如果需要统计保费
				$sqlcount = "select sum(order_price)  as other
				from scy_order as a             				 /*查找保费申请表，*/
				left join scy_userorder as b on a.rs = b.rs      /*连接用户订单表，用于获取车牌，价格，用户名，标识b*/
				join scy_insurer as c on a.insurerid = c.id 	 /*连接保险公司表，用于获取保险公司名，标识c*/
				
				join scy_admin as d on a.inspectadminid = d.id   /*连接管理员，用于获取管理员名称 初审 标识e*/
				join scy_admin as e on a.comfirmadminid = e.id 	 /*连接管理员，用于获取管理员名称 复审 标识f*/
				join scy_admin as f on a.approadminid = f.id 	 /*连接管理员，用于获取管理员名称 结款 标识g*/

				join scy_admininspect as g on g.id = e.sid 		 /*连接审核组，用于获取审核组名称 初审 标识h*/
				join scy_admininspect as h on h.id = e.sid 		 /*连接审核组，用于获取审核组名称 复审 标识i*/
				join scy_admininspect as i on i.id = e.sid       /*连接审核组，用于获取审核组名称 初结款审 标识j*/
				
				".$join.$where;;
				$jsonary["other"] = Db::query($sqlcount);;//获取总数
			}
			$jsonary["code"] = 0;

			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			return json($jsonary);
		}
	}
	//添加 修改商户类型
	public function savepremium(){
		if(request()->isPost()){
			$post = input('post.');
			$order = input('?post.id') ? model('order')->get($post['id']) : model('order');
			input('?post.createtime')? $order -> name = $post['name'] : false;
			input('?post.createtime')? $order -> createtime = date('Y-m-d H:i:s') : false;
			input('?post.stat') ? $order -> stat = $post['stat'] : false;
			$result = $order-> save();
			$jsonary["code"] = $result;
			input('?post.id') ? model("Base")->CreateAdminLog("修改商户类型","修改商户类型，ID[".$post['id']."]") : model("Base")->CreateAdminLog("新增商户类型","新增商户类型，ID[".$order ->id."]");
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
        return view('premium',['province'=>$province]);
	}

	//佣金统计、
	public function countbrokerage(){
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$date = date('Y-m-d');
		$sql = "select sum(b.order_price)  as histry from scy_order as a  left join scy_userorder as b on a.rs = b.rs  where  a.stat in (4,5)";//历史保费
		$histry = Db::query($sql);
		$sql = "select sum(b.order_price)  as today from scy_order as a left join scy_userorder as b on a.rs = b.rs  where  a.stat in (4,5) and unix_timestamp(a.approtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))";//今日保费
		$today = Db::query($sql);
		return view('countpremium',['province'=>$province,'today'=>$today[0],'histry'=>$histry[0]]);
	}
    

}