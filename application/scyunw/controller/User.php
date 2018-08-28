<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
use think\Session;
class User extends Common
{
	private $table='user';
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
	//列表页
    public function index()
    {
        return view('index');
    } 
	//认证列表页
    public function lists()
    {
        return view('lists');
    } 
    public function ulist()
    {
        return view('list');
    } 
	//列表页
    public function redlist()
    {
		$rs = input("get.r")?input("get.r"):"";
        return view('redlist',["rs"=>$rs]);
    } 
    public function paylist()
    {
        return view('paylist');
	} 
	/*********************************************************************************************/
	//用户提现
	/*********************************************************************************************/

	//提现页面
	public function withdraw(){
		$type = input('get.type');
		$sql = "select * from base_province ";
		$province = model("Base")->query($sql);
		$sary = model("Base")->GetCheckAuth();
		return view('withdraw',['province'=>$province,'type'=>$type,'sary' => $sary]);
	}
	//获取提现
	public function getwithdraw(){
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$where = "where 1=1  ";
		$type = input('?get.type')?input('get.type'):'';//获取状态
		if($type !== ''){
			$where .= $type=="a"?" and stat<3 ":" and (stat = $type) ";
		}
		//获取初审日期
		$first = input('?get.first')?input('get.first'):'';
		if($first){
			$temp = explode(" - ",$first);
			$where .= " and unix_timestamp(firsttime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(firsttime)<=unix_timestamp('".$temp[1]."') ";
		}
		//获取复审日期
		$second = input('?get.second')?input('get.second'):'';
		if($second){
			$temp = explode(" - ",$second);
			$where .= " and unix_timestamp(secondtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(secondtime)<=unix_timestamp('".$temp[1]."') ";
		}	
		//获取复审日期
		$pay = input('?get.pay')?input('get.pay'):'';
		if($pay){
			$temp = explode(" - ",$pay);
			$where .= " and unix_timestamp(paytime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(paytime)<=unix_timestamp('".$temp[1]."') ";
		}		
		$sql = "select * from scy_usertixian ".$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_usertixian ".$where;
		$list = model("Base")->query($sql);
		$paystat_ary = array("待审核","待审核","待结款","已结款","已驳回");
		$Base_model = model("Base");
		foreach ($list as $key => &$val) {
			$temp = model("userbank")->where("id",$val["bankid"])->find();
			$val["bank"] = $temp["bank_account"];
			$val["paystat"] = $paystat_ary[$val["stat"]];;
			$val["examinetype"] = $val["stat"];
			$val["firstadmin"] = $Base_model->Getadmin($val["firstadminid"]);
			$val["secondadmin"] = $Base_model->Getadmin($val["secondadminid"]);
			$val["payadmin"] = $Base_model->Getadmin($val["payadminid"]);
		}
		if(input("?get.toexcel")){
			$Base_model->CreateAdminLog("导出列表","导出用户提现订单列表");
			$titary = array("用户提现订单列表");
			$list["tit"] = array(
				"id"=>"ID","ordernum"=>"提现订单号","money"=>"金额",
				"bank"=>"银行信息","addtime"=>"提交时间","firstadmin"=>"初审人",
				"firsttime"=>"初审时间","secondadmin"=>"复审人","secondtime"=>"复审时间",
				"payadmin"=>"结款人","paytime"=>"结款时间"
			);
			$styleary = array(
				"addtime"=>"20","firstadmin"=>"20","firsttime"=>"20","secondadmin"=>"20","secondtime"=>"20","payadmin"=>"20","paytime"=>"20"
			);
			print_r($list);
			exit;
			$Base_model->Toexcel($titary,$list,$styleary);			
		}else{		
			$total = model("Base")->query($sqlcount);
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]["total"];
			$jsonary["data"] = $list;		
			return json($jsonary);		
		}		
	}
	//提现审核
	public function txinfo(){
		$datatype = input('get.st');
		$Base_model = model("Base");
		$id = input('get.id')? input('get.id'): exit('ID不存在');// 查看信息
		$typearr = ['初审中','复审中','待打款','已结款','驳回'];
		$sql = "select * from scy_usertixian where id=".$id;
		$list = $Base_model->query($sql);
		$list = $list[0];
		$user = model("User")->getbyid($list["uid"]);
		$list["phone"] = $user["phone"];
		$temp = model("userbank")->where("id",$list["bankid"])->find();
		$list["bank"] = $temp["acholder"]." ".$temp["bank_account"]." ".$temp["bank_name"];	
		$list["firstadmin"] = $Base_model->Getadmin($list["firstadminid"]);
		$list["secondadmin"] = $Base_model->Getadmin($list["secondadminid"]);
		$list["payadmin"] = $Base_model->Getadmin($list["payadminid"]);	
		$list["statstr"] = $typearr[$list["stat"]];
		return view('txinfo',['datatype'=>$datatype,'list'=>$list]);
	}

    //添加/修改
    public function txGetSet(){
		$id = input('post.id');
		$post = input('post.');
		unset($post["id"]);
		$jsonary["msg"] = '';
		//执行操作
		$adminary = array("1"=>"first","2"=>"second","3"=>"pay");
		if(isset($post["stat"])){
			if($post["stat"]>0){
				$post[$adminary[$post["stat"]]."adminid"] = Session::get('admin_id');
				$post[$adminary[$post["stat"]]."time"] = date("Y-m-d H:i:s");
			}
			if($post["stat"]=="-1"){
				$post["stat"] = -1 ;
			}
		}	
		$result = model("usertixian")->where('id', $id)->update($post);
		return json($jsonary);
	}	

	//用户编辑
	public function edit()
	{
		$id = input('get.id');
		//获取用户信息
		$info = model($this->table)->where('id',$id)->find();
		$address = model("useraddress")->where(["uid"=>$id,"isdefault"=>1])->find();
		$info["e_phone"] = $address["apply_phone"];
		$info["e_name"] = $address["name"];
		$info["e_address"] = model('base')->Getbyprovince($address['province']);
		$info["e_address"] .= model('base')->Getbycity($address['city']);
		$info["e_address"] .= model('base')->Getbyarea($address['area']);
		$info["e_address"] .= $address["address"];
		if($info["reco_id"]){
			$username = model($this->table)->where('id',$info["reco_id"])->find();
			$info["reco_id"] = $info["reco_id"]?$username["username"]:"无";
		}		
		$info["is_inquiry"] = $info["is_inquiry"]?"<span style=\"color: #5FB878;\">已询价</span>":"<span style=\"color: #F581B1;\">未询价</span>";
        return view('edit',['info' => $info]);
	}
	//用户情况
    public function info()
    {
		$id = input('get.id');
		//获取用户信息
		$info = model($this->table)->where('id',$id)->column('id,username,realname,reputation,is_inquiry,reg_phone,nickname,reco_id,wxopenid,wxnickname,wxavatar,phone,rnstat,money_free,money_frozen,allow_login,last_ip,last_client,logincount,addtime,qrcode');
		$info = $info[$id];
		//获取显示信息备注
		$temp = array("ID","用户名","真实姓名","信用评级","询价车辆数","手机号码","昵称","来自推荐人","wxopenid","微信昵称","微信头像","绑定手机","实名认证","可用余额","冻结余额","账号状态","最后登录IP","最护登录客户端","登录次数","注册时间","系统生成推广码");
		$info["wxavatar"] = "<img width='132' src='".$info["wxavatar"]."'>";//获取微信头像

		if($info["reco_id"]){
			$username = model($this->table)->where('id',$info["reco_id"])->find();
			$info["reco_id"] = $info["reco_id"]?$username["username"]:"无";
		}
		$info["reg_phone"] = $info["reg_phone"]?$info["reg_phone"]:$info["phone"];
		$info["allow_login"] = $info["allow_login"]?"<a style=\"color: #5FB878;\">正常</a>":"<a style=\"color: #F581B1;\">封禁</a>";
		
		foreach($info as $key=>$val){
			$temp_val[] = $val;
		}
		foreach($temp_val as $key=>$val){
			$show_ary[$key]["val"] = $val;
			$show_ary[$key]["str"] = $temp[$key];
		}
        return view('info',['info' => $show_ary]);
	}

	//用户认证
    public function check()
    {
		$id = input('get.id');
		$type = input("get.type");
		//获取用户信息
		$info = model($this->table)->where('id',$id)->find();
		$pdetail = array();
		if($type=="phone_stat"){
			$pdetail = model("orderphonedata")->where("phone",$info["phone"])->find();
			if($pdetail){
				$pdetail = $pdetail->toArray();
				$pdetail = json_decode($pdetail["result"],true);
			}
		}
		$address = model("useraddress")->where(["uid"=>$id,"isdefault"=>1])->find();
		$info["e_phone"] = $address["apply_phone"];
		$info["e_name"] = $address["name"];
		$info["e_address"] = model('base')->Getbyprovince($address['province']);
		$info["e_address"] .= model('base')->Getbycity($address['city']);
		$info["e_address"] .= model('base')->Getbyarea($address['area']);
		$info["e_address"] .= $address["address"];
		if($info["reco_id"]){
			$username = model($this->table)->where('id',$info["reco_id"])->find();
			$info["reco_id"] = $info["reco_id"]?$username["username"]:"无";
		}		
		$info["is_inquiry"] = $info["is_inquiry"]?"<span style=\"color: #5FB878;\">已询价</span>":"<span style=\"color: #F581B1;\">未询价</span>";
        return view('check',['info' => $info,'type' => $type,'pdetail'=>$pdetail]);
    }	
	//询价列表
	public function order(){
		$id = input('?get.id')?input('get.id'):"";
		$user = model($this->table)->where('id',$id)->column('id,username,logincount,phone');
		$user = $user[$id];
		$url = url('user/getorderlist')."?phone=".$user["phone"];
		return view('order',['url' => $url,'user' => $user]);
	}
	//登陆次数
	public function logincount(){
		$id = input('?get.id')?input('get.id'):"";
		$user = model($this->table)->where('id',$id)->column('id,username,logincount,phone');
		$user = $user[$id];
		$url = url('user/getloginlist')."?phone=".$user["phone"];
		return view('logincount',['url' => $url,'user' => $user]);
	}
	//询价次数列表
	public function getorderlist(){
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$where = "where phone='".input("get.phone")."'  ";
		$sql = "select * from scy_userorder ".$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_userorder ".$where;
		$list = model("Base")->query($sql);
		$total = model("Base")->query($sqlcount);
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;		
		return json($jsonary);		
	}
	//登陆次数列表
	public function getloginlist(){
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$where = "where phone='".input("get.phone")."' and type like '%登录%' ";
		$sql = "select * from scy_log_user ".$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_log_user ".$where;
		$list = model("Base")->query($sql);
		$total = model("Base")->query($sqlcount);
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;		
		return json($jsonary);		
	}
	//获取数据分析
	public function phonedata(){
		$phone = input('?get.phone')?input('get.phone'):'';
		//获取数据分析结果
		$pdetail = model("orderphonedata")->where("phone",$phone)->find();
		if($pdetail){
			$pdetail = $pdetail->toArray();
			$pdetail = json_decode($pdetail["result"],true);
		}
        return view('phonedetail',['pdetail' => $pdetail]);		
	}
    //列表
    public function getlist()
    {
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		$where = "where 1 ";
		if(input('?get.keyword')){
			$where .= " and (username like '%".$keyword."%') ";
		}
		//获取认证/未认证
		if(input('?get.stat')){
			$stat = input('get.stat');
			if($stat){
				$where .= " and (rnstat=1 or zmxy_stat=2 or face_stat=1 or phone_stat=1)";
			}else{
				$where .= " and rnstat=0 and zmxy_stat<2 and face_stat=0 and phone_stat=0 ";
			}
		}		
		//获取选中ids
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
			$where .= " and id in (0".$ids.")";
		}	
		$reg_ary = array("APP端","微信端","PC端");
		
		//sql开始
		$sql = "select * from scy_".$this->table." ";
		$sql = $sql.$where;
		if(!input("?get.toexcel")){
			$page = input('?get.page')?input('get.page'):1;//获取页数
			$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
			$sql = $sql." order by id desc limit ".($page-1)*$limits.",".$limits;
		}
		$Base_model = model("Base");
		$sqlcount = "select count(*) as total from scy_".$this->table." ".$where;
		$list = $Base_model->query($sql);//获取列表
		foreach($list as $key=>&$val){
			$val["is_inquiry_str"] = $val["is_inquiry"]?"<span style=\"color: #5FB878;\">已询价</span>":"<span style=\"color: #F581B1;\">未询价</span>";
			$val["reg_type"] = $reg_ary[$val["reg_client"]?$val["reg_client"]:0];
			$val["logincount"] = $val["logincount"]."次";
			$val["checkadmin"] = $Base_model->Getadmin($val["checkadminid"]);
			$temp = $Base_model->query("select id,fqstat from scy_order where fqstat>0 and phone=".$val["phone"]." order by fqstat asc limit 1");
			if($temp){
				$val["fqstat"] = $temp[0]["fqstat"];
			}
			$val["billstat"] = "<span style=\"color: #F581B1;\">无</span>";
			$temp = $Base_model->query("select id from scy_orderinstall where  phone=".$val["phone"]." and paystat = 0 order by qishu desc limit 1");
			if($temp){
				$val["billstat"] = "<span style=\"color: #FFB800;\">还款中</span>";
			}else{
				$temp = $Base_model->query("select qishu from scy_orderinstall where  phone=".$val["phone"]." and paystat = 1 order by qishu desc limit 1");
				if($temp){
					$val["billstat"] = $temp[0]["qishu"]>1?"<span style=\"color: #5FB878;\">已完结</span>":"<span style=\"color: #009688;\">全款</span>";
				}
			}
			
		}
		if(input("?get.toexcel")){
			model("Base")->CreateAdminLog("导出列表","导出用户列表");
			$titary = array("用户列表");
			$list["tit"] = array(
				"id"=>"ID","username"=>"用户名","realname"=>"真实姓名","reputation"=>"信用评级",
				"phone"=>"绑定手机","phone_core"=>"归属地","is_inquiry"=>"询价状态",
				"rnstat"=>"实名认证","rnstat"=>"分期认证","rnstat"=>"账单状态",
				"logincount"=>"登陆次数","last_logintime"=>"登陆时间","allow_login"=>"状态",
				"reg_type"=>"注册方式","addtime"=>"注册时间"
			);
			model("Base")->Toexcel($titary,$list);
		}else{
			$total = model("Base")->query($sqlcount);//获取总数
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
		if($id>0){//修改
			if(count($post)==1&&input('?post.allow_login')){
				$info = model($this->table)->getById($id);
				model("Base")->CreateAdminLog("更改用户状态","更改用户[".$info["username"]."]的状态");
			}elseif(count($post)==1){
				$info = model($this->table)->getById($id);
				$post["checktime"] = date("Y-m-d H:i:s");
				$post["checkadminid"] = Session::get('admin_id');
				model("Base")->CreateAdminLog("更改用户认证状态","更改用户[".$info["username"]."]的认证状态");
			}else{
				model("Base")->CreateAdminLog("编辑用户","修改用户[".$post["username"]."]的账户信息");
			}
			$result = model($this->table)->where('id', $id)->update($post);
		}else{//增加
			$jsonary["msg"] = "非法访问";
		}
		return json($jsonary);
	}
	//删除
	public function GetDel(){
		$ids = input("post.ids");
		$result = model($this->table)->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除用户","删除用户，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
	//导出内容
	public function Toexcel(){
		$id = input('get.id');
		$Base_model = model("Base");
		$info = model($this->table)->getById($id);	
		
		$val["province"] = $Base_model->Getbyprovince($info["province"]);
		$val["city"] = $Base_model->Getbycity($info["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		$Base_model->CreateAdminLog("导出EXCEL","导出用户[".$info->username."]的账户信息");
		$titary = array("用户列表");		
		$list = array(		
			//单元格(A)，单元格填充(uploads/20171020/3ff9134973ba25b8e4cc33e16c840113.jpg)，扩展单元格(B), 宽度 , 高度 ,是否是图片
			array(array("A",$info["headimg"],"B",0,120,1) ,array("C",$info["headimg"],"D",0,120,1)),
			array(array("A","用户名") , array("B",$info["username"],'',40) , array("C","真实姓名") , array("D",$info["realname"],'',40)),
			array(array("A","电话") , array("B",$info["phone"]) , array("C","身份证") , array("D",$info["id_license"])),
			array(array("A","收货联系人") , array("B",$info["realname"]) , array("C","收货联系电话") , array("D",$info["phone"])),
			array(array("A","收货地址") , array("B",$info["orderarea"],"D")),
			array(array("A","注册时间") , array("B",$info["addtime"]) , array("C","登录时间") , array("D",$info["last_logintime"])),
			array(array("A","推荐人") , array("B",$info["reco_id"]) , array("C","登录次数") , array("D",$info["logincount"])),
			array(array("A","车牌号") , array("B",$info["carlicense"]) , array("C","账户状态") , array("D",$info["logincount"])),
			array(array("A",$info["id_img"],"B",0,120,1) ,array("C",$info["id_img_b"],"D",0,120,1)),
			array(array("A",$info["car_img"],"B",0,120,1) ,array("C",$info["car_img_b"],"D",0,120,1)),
		);
		model("Base")->Toexcelinfo($titary,$list,"D");
	}
}
