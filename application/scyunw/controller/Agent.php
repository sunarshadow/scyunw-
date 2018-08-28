<?php
namespace app\scyunw\controller;
use think\Request;
use think\Db;
use app\common\model;
class Agent extends Common
{
	private $table='agent';
	private $agentclass='agentclass';
    public function _empty()
    {
        return 'API错误，请核对参数';
	}
	//商户列表视图
    public function index()
    {
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$type = input('get.type');
        return view('index',['province'=>$province,'type'=>$type]);
	}
	//商户信息视图
    public function info()
    {	
		$id = input('param.id');
		$info = model($this->table)->getById($id);
		if(request()->isPost()){ //当图片上传时候
			$imgurl = action('Upload/img');
			$oldimg = $info ->com_other_img !='' ?  json_decode($info->com_other_img,true) : []; //数据库原先图片 为空则形成空数组
			$data = [
				$imgurl['path'], //整理格式
			];
			$imgurl['list'] = array_merge($oldimg,$data);
			$info ->com_other_img = json_encode($imgurl['list'],true); //合并数组并转为JSON
			$info ->save();
			model("Base")->CreateAdminLog("修改商户图片","上传修改ID为【".$id."】的商户图片信息，路径为：".$imgurl['path']);
			return Json($imgurl);
		}else{ //仅仅是展示
			$info->province = model('base')->Getbyprovince($info->province);
			$info->city = model('base')->Getbycity($info->city);
			$info->area = model('base')->Getbyarea($info->area);
			$info->ress = $info->province.$info->city;
			$info->address = $info->province.$info->city.$info->area;
			$info ->com_other_img = json_decode($info ->com_other_img,true);
			return view('info',['info' => $info]);
		}
		
	}
	//商户信息视图
    public function updateinfo()
    {	
		$id = input('param.id');
		$info = model($this->table)->getById($id);
		if(request()->isPost()){ //当图片上传时候
			if(input('param.type') == 'info')
			{
				// $smscode = input('post.smscode') ? input('post.smscode') : false;
				// $result = model("Base")->smscheck($info->nickphone,$smscode); //判断验证码
				// if($result!=1){exit($this->error('验证码错误'));}
				//修改信息
				input('post.password')  !== '' ? $info->password  = md5(input('post.password')) : false;
				input('post.nickname')  !== '' ? $info->nickname  = input('post.nickname') 		: false;
				input('post.nickphone') !== '' ? $info->nickphone = input('post.nickphone') 	: false;
				input('post.rebate')    !== '' ? $info->rebate    = input('post.rebate') 		: false;
				input('post.company')    !== '' ? $info->company    = input('post.company') 		: false;
				

				$bank = model('agentbank')->where('userid',$id)->where('ismain','1')->find();
				if(!$bank){
					$bank = model('agentbank');
					$bank->userid = $id;
					$bank->ismain = 1;
				}
				input('post.bank_account')    !== '' ? $bank->bank_account    = input('post.bankaccount') 	: false;
				input('post.openac_store')    !== '' ? $bank->openac_store    = input('post.openacstore') 	: false;
				input('post.acholder')    !== '' ? $bank->acholder    = input('post.acholder') 	: false;
				
				
				if($info->save() !== false && $bank->save() !== false){
					model("Base")->CreateAdminLog("修改商户信息","修改ID为【".$id."】的商户信息");
					$this->success('修改成功');
				}else{
					$this->error('修改失败');
				}
				
			}else{
				$imgurl = action('Upload/img');
				$oldimg = $info ->com_other_img !=='' ?  json_decode($info->com_other_img,true) : []; //数据库原先图片 为空则形成空数组
				$data = [
					$imgurl['path'], //整理格式
				];
				$imgurl['list'] = array_merge($oldimg,$data);
				$info ->com_other_img = json_encode($imgurl['list'],true); //合并数组并转为JSON
				$info ->save();
				model("Base")->CreateAdminLog("修改商户图片","上传修改ID为【".$id."】的商户图片信息，路径为：".$imgurl['path']);
				return Json($imgurl);
			}
			
		}else{ //仅仅是展示
			if(!$info) {return '暂无数据';}
			$info->province = model('base')->Getbyprovince($info->province);
			$info->city = model('base')->Getbycity($info->city);
			$info->area = model('base')->Getbyarea($info->area);
			$info->ress = $info->province.$info->city;
			$info->address = $info->province.$info->city.$info->area;
			$info ->com_other_img = json_decode($info ->com_other_img,true);
			return view('updateinfo',['info' => $info]);
		}
		
	}
	//添加商户
	public function addagent(){
		if(request()->isPost()){ //提交时
			//获取并且初次处理数据
			$username 	 = input('post.username') 	 	? 	input('post.username') 				: exit($this->error('请填写用户名'));
			$company 	 = input('post.company') 	 	? 	input('post.company') 				: exit($this->error('请填写公司名'));
			$phone 		 = input('post.phone') 		 	? 	input('post.phone') 				: exit($this->error('请填写电话'));
			$servicetype = input('post.servicetype') 	? 	input('post.servicetype') 			: exit($this->error('请选择服务类型'));
			$server_zao  = input('post.server_zao')  	? 	input('post.server_zao') 		    : exit($this->error('请填写上午服务时间'));
			$server_wan  = input('post.server_wan')  	? 	input('post.server_wan') 		    : exit($this->error('请填写下午服务时间'));
			$weekday  	 = input('post.weekday/a')   	? 	array_keys(input('post.weekday/a')) : exit($this->error('请填写服务周期'));
			$email 		 = input('post.email') 		 	? 	input('post.email') 				: exit($this->error('请填写邮箱'));
			$password 	 = input('post.password') 	 	? 	input('post.password') 				: exit($this->error('请填写密码'));
			$nickname 	 = input('post.nickname') 	 	? 	input('post.nickname') 				: exit($this->error('请填写负责人'));
			$nickphone 	 = input('post.nickphone') 	 	? 	input('post.nickphone') 			: exit($this->error('请填写负责人电话'));
			$allow_login = input('post.allow_login') 	? 	input('post.allow_login') 			: exit($this->error('请填写审核状态'));
			$province 	 = input('post.province') 	 	? 	input('post.province') 				: exit($this->error('请填写省'));
			$city 		 = input('post.city') 		 	? 	input('post.city') 					: exit($this->error('请填写市'));
			$area 		 = input('post.area') 		 	? 	input('post.area') 					: exit($this->error('请填写区'));
			$location 	 = input('post.location') 	 	? 	input('post.location') 				: exit($this->error('请填写坐标'));
			$rebate 	 = input('post.rebate') 	 	? 	input('post.rebate') 				: exit($this->error('请填写消费返点'));
			//检查用户名唯一
			if(model("agent")->getByUsername($username) != null ){
				exit($this->error('用户名已被注册'));
			}

			//实例化商户表模型，添加各参数
			$newagent = model('agent');
			$newagent ->username = $username;
			$newagent ->company = $company;
			$newagent ->phone = $phone;
			$newagent ->servicetype = $servicetype;
			$newagent ->server_zao = $server_zao;
			$newagent ->server_wan = $server_wan;
			$newagent ->weekday = implode(",",$weekday);
			$newagent ->email = $email;
			$newagent ->password = md5($password);
			$newagent ->nickname = $nickname;
			$newagent ->nickphone = $nickphone;
			$newagent ->allow_login = $allow_login;
			$newagent ->province = $province;
			$newagent ->city = $city;
			$newagent ->area = $area;
			$newagent ->location = $location;
			$newagent ->address = model('base')->Getbyprovince($province).model('base')->Getbycity($city).model('base')->Getbyarea($area);
			$newagent ->rebate = $rebate;
			$newagent ->addtime = date('Y-m-d H:i:s');
			$newagent ->servertime = '上午：'.$server_zao.' 下午：'.$server_wan;
			
			//执行添加并返回相应信息
			if($newagent ->save()){
				model("Base")->CreateAdminLog("增加商户","增加了ID为".$newagent->id.",用户名为".$username."的商户");
				$this->success('添加成功');
			}else{
				$this->error('添加失败');
			}
			

		}

		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$agentclass = model('agentclass')->select();
		return view('addagent',['province'=>$province,'agentclass'=>$agentclass]);
	}
	//商户类型视图
	public function agentclass(){
        return view('class');
	}
	//商户类型列表
	public function getclass(){
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		$type = input('?get.type')?input('get.type'):'';//获取状态
		//SQl语句开始
		$sql = "select * from scy_".$this->agentclass." ";
		$where = "where 1 ";
		if($type !== ''){
			$where .= " and (status = $type) ";
		} 
		if(input('?get.keyword')){
			$where .= " and (name like '%".$keyword."%') ";
		}
		$sql = $sql.$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_".$this->agentclass." ".$where;
		//SQL语句结束
		$list = Db::query($sql);//获取列表
		$total = Db::query($sqlcount);//获取总数
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]['total'];
		$jsonary["data"] = $list;	
		return json($jsonary);
	}
	//添加 修改商户类型
	public function crdclass(){
		if(request()->isPost()){
			$post = input('post.');
			$agentclass = input('?post.id') ? model($this->agentclass)->get($post['id']) : model('agentclass');
			input('?post.createtime')? $agentclass -> name = $post['name'] : false;
			input('?post.createtime')? $agentclass -> createtime = date('Y-m-d H:i:s') : false;
			input('?post.status') ? $agentclass -> status = $post['status'] : false;
			$result = $agentclass-> save();
			$jsonary["code"] = $result;
			input('?post.id') ? model("Base")->CreateAdminLog("修改商户类型","修改商户类型，ID[".$post['id']."]") : model("Base")->CreateAdminLog("新增商户类型","新增商户类型，ID[".$agentclass ->id."]");
			return json($jsonary);
		}else{
			return view('classadd');
		}
	}
	//删除商户类型
	public function delclass(){
		$ids = input("post.ids");
		$result = model($this->agentclass)->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除商户类型","删除商户类型，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
    //商户列表
	public function getlist()
    {
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		$type = input('?get.type')?input('get.type'):'';//获取状态
		$addtime = input('?get.addtime')?input('get.addtime'):'';//获取注册时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$rebate = input('?get.rebate')?input('get.rebate'):'';//获取消费返点
		$logincount = input('?get.logincount')?input('get.logincount'):'';//获取登录次数
		$join = '';
		$agent = model('agent');
		$where = " where 1 ";
		//检查状态
		if($type !== ''){
			$where .= " and (a.allow_login = $type) ";
		}
		//检查关键词
		if($keyword){
			$where .= " and (a.username  like '%".$keyword."%' ) ";
		}
		//检查消费返点
		if($rebate !==''){
			$where .= " and (a.rebate  like '%".$rebate."%' ) ";
		}
		//检查消登录次数
		if($logincount !==''){
			$where .= " and (a.logincount  like '%".$logincount."%' ) ";
		}
		
		//检查注册时间
		if($addtime){
			$temp = explode(" - ",$addtime);
			$where .= " and unix_timestamp(a.addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.addtime)<=unix_timestamp('".$temp[1]."') ";
		}
		$ceshi = $agent ->where($where)->fetchSql(true)->select();
		
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
		
		$sql = "select * ,(select name from scy_agentclass where id=a.servicetype ) as servicetype from scy_".$this->table." as a ".$join;
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
				$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$where." order by a.id desc " : $sql.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$list = Db::query($sql);//获取列表
		$statusarr = ['未审核','已审核','停用','驳回'];
		foreach($list as $key=>&$val){
			$sql = "select acholder,bank_account,bank_name from scy_agentbank where ismain=1 and userid=".$val["id"];
			$temp = Db::query($sql);
			if($temp){
				$val = array_merge($temp[0],$val);
			}else{
				$val["acholder"] = $val["bank_name"] = $val["bank_account"] = "";
			}
			$val["province"] = model("Base")->Getbyprovince($val["province"]);
			$val["city"] = model("Base")->Getbycity($val["city"]);
			$val["area"] = model("Base")->Getbyarea($val["area"]);
			$val["address"] = $val["province"].$val["city"].$val["area"].$val["address"];
			$val["test"] = $val["province"].$val["city"];
			$val["logincount"] =  $val["logincount"]?'共登录'. $val["logincount"].'次':'暂未登录';
			$val["status_cg"] =  $statusarr[$val["allow_login"]];
		}
		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出Excel","导出商户列表数据列表");
			$titary = array("用户列表数据");
			$list["tit"] = array(
				"id"=>"ID","username"=>"用户名","company"=>"公司名",
				"address"=>"地址","phone"=>"绑定手机","servicetype"=>"商户类型",
				"server_zao"=>"早上服务时间","server_wan"=>"晚上服务时间","weekday"=>"工作周期",
				"servertime"=>"服务时间","email"=>"邮箱"
				,"money"=>"money","nickname"=>"负责人","nickphone"=>"负责人手机","status_cg"=>"商户状态",
				"addtime"=>"添加时间","rebate"=>"消费返点","logincount"=>"登录情况"
			);
			$styleary = array(
				"address"=>"50","servicetype"=>"35","server_zao"=>"20","server_wan"=>"20","servertime"=>"20","email"=>"20","addtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
		$sqlcount = "select count(*) as total  from scy_".$this->table." as a ".$join.$where;
		$total = Db::query($sqlcount);//获取总数
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
		//增加条件判断
		if(!$id){
			$info = Db::query("select id from scy_".$this->table." where username='".$post["username"]."' ");
			if($info){$jsonary["msg"] = "商户名称已存在，请重新输入";}
		}
		//执行操作
		if(!$jsonary["msg"]){
			if($id>0){//修改
				unset($post['msg']);//删除掉信息，因为信息是驳回携带不写入数据库
				$msg = input('post.msg'); //单独获取信息 如果存在的话发送驳回短信
				$agent = model('agent')->where('id',$id)->find();//找到商家
				if($agent->allow_login=='0'&&$post['allow_login']=='1'){
					//审核通过
					model('message')->getmsg('agent_check',[$agent->nickphone]);//负责人电话  提现金额 理由
				}
				// if($msg){
				// 	//这里填写短信内容
				// 	model('message')->getmsg('agent_error',[$agent->nickphone,$msg]);//负责人电话  提现金额 理由

				// }
				$result = model($this->table)->where('id', $id)->update($post);
				model("Base")->CreateAdminLog("修改商户","导出商户Excel");
			}else{//增加
				$post["addtime"] = date("Y-m-d H:i:s");
				$result = model($this->table)->insert($post);
				model("Base")->CreateAdminLog("新增商户","新增商户ID：".$post['id']);
			}
		}
		return json($jsonary);
	}
	//删除
	public function GetDel(){
		$ids = input("post.ids");
		$result = model($this->table)->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除商户","删除商户，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
	//导出内容
	public function Toexcel(){
		$id = input('get.id');
		$Base_model = model("Base");
		$info = model($this->table)->getById($id);	
		$info->servicetype = model('Base')->Getagenttype($info->servicetype);
		$agentbank = Db::name('agentbank')->field('acholder,bank_account,bank_name')->where('ismain','1')->where('userid',$id)->find();
		$info -> province = model("Base")->Getbyprovince($info->province);
		$info -> city = model("Base")->Getbycity($info->city);
		$info -> area = model("Base")->Getbyarea($info->area);
		$info -> address = $info -> province.$info -> city.$info -> area.$info -> address;
		$info -> test = $info -> province.$info -> city;
		$statusarr = ['未审核','已审核','停用','驳回'];
		$Base_model->CreateAdminLog("导出EXCEL","导出用户[".$info->username."]的账户信息");
		$titary = array("商户信息表清单");	
		$list = array(		
			//单元格(A)，单元格填充(uploads/20171020/3ff9134973ba25b8e4cc33e16c840113.jpg)，扩展单元格(B), 宽度 , 高度 ,是否是图片
			// array(array("A",$info["headimg"],"B",0,120,1) ,array("C",$info["headimg"],"D",0,120,1)),
			array(array("A","用户名") , array("B",$info["username"],'',40) , array("C","公司") , array("D",$info["company"],'',40)),
			array(array("A","地区") , array("B",$info["test"]) , array("C","地址") , array("D",$info["address"])),
			array(array("A","商户类型") , array("B",$info["servicetype"]) , array("C","负责人") , array("D",$info["nickname"])),
			array(array("A","绑定手机") , array("B",$info["phone"]) , array("C","结算帐户名") , array("D",$agentbank["acholder"])),
			array(array("A","结算卡号") , array("B",$agentbank['bank_account']?$agentbank['bank_account']:'',"D")),
			array(array("A","开户行") , array("B",$agentbank['bank_name']?$agentbank['bank_name']:'') , array("C","消费返点") , array("D",$info["rebate"])),
			array(array("A","消费返点") , array("B",$info["rebate"]) , array("C","审核状态") , array("D",$statusarr[$info["allow_login"]])),
		);	
		model("Base")->Toexcelinfo($titary,$list,"D");
		exit;
	}
	//商户提现
    public function withdraw(){
		$agentid = input('agentid');
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$type = input('get.type');
		$sary = model("Base")->GetCheckAuth();
		if($agentid){
			return view('withdraw',['province'=>$province,'type'=>$type,'sary' => $sary,'agentid'=>$agentid]);
		}else{	
			return view('withdraw',['province'=>$province,'type'=>$type,'sary' => $sary]);
		}
        
	}
	//商户提现数据
	public function getwithdraw(){
		
		$agentid = input('?get.id')?input('get.id'):'';//获取商户ID  用于审核时候的查看
		$userid = input('?get.userid')?input('get.userid'):'';//获取用户id
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		$type = input('?get.type')?input('get.type'):'';//获取状态
		$firsttime = input('?get.firsttime')?input('get.firsttime'):'';//获取初审时间
		$secondtime = input('?get.secondtime')?input('get.secondtime'):'';//获取复审时间
		$paymenttime = input('?get.paymenttime')?input('get.paymenttime'):'';//获取结款时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$serchinfo = input('?get.serchinfo')?input('get.serchinfo'):'';//获取是否只是统计
		$join = '';
		$where = " where 1 ";
		//检查状态
		if($type !== ''){
			$where .= $type=="a"?" and a.examinetype<3 ":" and (a.examinetype = $type) ";
		}
		if($userid){
			$where .= " and (a.userid = $userid) ";
		}
		if($agentid){
			$where .= " and (a.userid = $agentid) ";
		}
		
		//检查关键词
		if($keyword){
			$where .= " and (b.username  = '$keyword') ";
		}
		//检查初审时间
		if($firsttime){
			$temp = explode(" - ",$firsttime);
			$where .= " and unix_timestamp(a.firsttime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.firsttime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查复审时间
		if($secondtime){
			$temp = explode(" - ",$secondtime);
			$where .= " and unix_timestamp(a.secondtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.secondtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查结款时间
		if($paymenttime){
			$temp = explode(" - ",$paymenttime);
			$where .= " and unix_timestamp(a.paymenttime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.paymenttime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查地区
		if($province){
			$join .= ' join base_province as d on b.province = d.province_id ';
			$where .= " and (d.province_id = $province ) ";
		}
		if($city){
			$join .= ' join base_city as e on b.city = e.city_id ';
			$where .= " and (e.city_id = $city ) ";
		}
		if($area){
			$join .= ' join base_area as f on b.area = f.area_id ';
			$where .= " and (f.area_id = $area ) ";
		}
		$sql = "select a.*,b.username,b.province,b.city,b.area,b.address,b.nickname,b.nickphone,b.phone,c.name, 
				(select sum(money) from scy_agenttixian where examinetype = 4) as historymoney 
				from scy_agenttixian as a left join scy_".$this->table." as b on a.userid = b.id join scy_agentclass as c on b.servicetype = c.id ".$join;
		
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
				$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$where." order by a.id desc " : $sql.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		if($serchinfo){
				$where .= " and (a.examinetype = 3) ";
				$sql = "select sum(a.money)  as thismoney ,sum(a.allmoney)  as thismoneyall 
				from scy_agenttixian as a ".$where;
				return $list = Db::query($sql);
		}
		$typearr = ['初审中','复审中','待打款','已结款','驳回'];
		$list = Db::query($sql);//获取列表
		$base = model('base');
			foreach($list as $key=>&$val){
				//获取初审
				if($val['firstexamine']){
					$sql = 'select a.name as firstadminname,b.title as firstadmingroup from scy_admin as a JOIN scy_admingroup as b on a.gid = b.id where a.id ='.$val['firstexamine'];
					$temp = Db::query($sql);
					$val = array_merge($temp[0],$val);
				}
				//获取复审
				if($val['secondexamine']){
					$sql = 'select a.name as secondadminname,b.title as secondadmingroup from scy_admin as a JOIN scy_admingroup as b on a.gid = b.id where a.id ='.$val['secondexamine'];
					$temp = Db::query($sql);
					$val = array_merge($temp[0],$val);
				}
				//获取结款
				if($val['collector']){
					$sql = 'select a.name as collectorname,b.title as collectoradmingroup from scy_admin as a JOIN scy_admingroup as b on a.gid = b.id where a.id ='.$val['collector'];
					$temp = Db::query($sql);
					$val = array_merge($temp[0],$val);
				}
				//获取地址
				$sql = "select acholder,bank_account,bank_name from scy_agentbank where ismain=1 and userid=".$val["id"];
				$temp = Db::query($sql);
				if($temp){
					$val = array_merge($temp[0],$val);
				}else{
					$val["acholder"] = $val["bank_name"] = $val["bank_account"] = "";
				}
				$val["firstexamine"]= $val["firstexamine"]?$base->Getadmin($val["firstexamine"]):'暂未审核';
				$val["secondexamine"]= $val["secondexamine"]?$base->Getadmin($val["secondexamine"]):'暂未审核';
				$val["collector"]= $val["collector"]?$base->Getadmin($val["collector"]):'暂未审核';
				$val["province"] = model("Base")->Getbyprovince($val["province"]);
				$val["city"] = model("Base")->Getbycity($val["city"]);
				$val["area"] = model("Base")->Getbyarea($val["area"]);
				$val["address"] = $val["province"].$val["city"].$val["area"].$val["address"];
				$val["test"] = $val["province"].$val["city"];
				$val["examinetype_cg"] = $typearr[$val["examinetype"]];
				$val["firsttime"] =  $val["firsttime"] !== '0000-00-00 00:00:00' ? $val["firsttime"]:'暂未审核';
				$val["secondtime"] =  $val["secondtime"] !== '0000-00-00 00:00:00' ? $val["secondtime"]:'暂未审核';
				$val["paymenttime"] =  $val["paymenttime"] !== '0000-00-00 00:00:00' ?$val["paymenttime"]:'暂未审核';
			}
		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出商户提现数据列表");
			$titary = array("用户列表数据");
			$list["tit"] = array(
				"id"=>"ID","test"=>"地区","username"=>"商户名",
				"name"=>"商户类型","money"=>"提现金额","allmoney"=>"allmoney",
				"historymoney"=>"历史提现总额","firstexamine"=>"资金初审","firsttime"=>"初审时间",
				"secondexamine"=>"资金复审","secondtime"=>"复审时间",
				"collector"=>"结款人","paymenttime"=>"结款时间","examinetype_cg"=>"审核状态"
			);
			$styleary = array(
				"name"=>"20","firsttime"=>"20","secondtime"=>"20","paymenttime"=>"20","firstexamine"=>"20","secondexamine"=>"20","collector"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;	
		}else{
			$sqlcount = "select count(*) as total from scy_agenttixian as a join scy_agent as b on a.userid =b.id ".$join.$where;
			$total = Db::query($sqlcount);;//获取总数
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			return json($jsonary);
		}
	}
	//审核商家提现
	public function updateExaminetype(){
		$post = input('post.');
		$agenttixian = input('?post.id') ? model('agenttixian')->get($post['id']) : model('agenttixian');
		input('?post.examinetype') ? $agenttixian -> examinetype = $post['examinetype'] : exit;
		switch ($post['examinetype']) {
			case 1: $agenttixian-> firsttime = date('Y-m-d H:i:s');
					$agenttixian-> firstexamine = session('admin_id');
					$title = '初审商户提现';
					$msg = "初审商户提现，ID[".$post['id']."]";
					break;
			case 2: $agenttixian-> secondtime = date('Y-m-d H:i:s');
					$agenttixian-> secondexamine = session('admin_id');
					$title = '复审商户提现';
					$msg = "复审商户提现，ID[".$post['id']."]";
					break;
			case 3: $agenttixian-> paymenttime = date('Y-m-d H:i:s');
					$agenttixian-> collector = session('admin_id');
					$title = '结算商户提现';
					$msg = "结算商户提现，ID[".$post['id']."]";
					break;
			case 4: $title = '驳回商户提现';
					$msg = "驳回商户提现，ID[".$post['id']."]";
					break;
			case 5: $title = '回收商户提现';
					$msg = "回收商户提现，ID[".$post['id']."]";
					break;
			default:exit;
		}
		$result = $agenttixian-> save();
		$jsonary["code"] = $result;
		model("Base")->CreateAdminLog($title,$msg);
		// input('?post.id') ? model("Base")->CreateAdminLog("修改商户类型","修改商户类型，ID[".$post['id']."]") : model("Base")->CreateAdminLog("新增商户类型","新增商户类型，ID[".$agenttixian ->id."]");
		return json($jsonary);
	}
	//商户消费详情和预约详情 通过 paystat 判别   0预约 1 消费
	public function payinfo(){
		$agentid = input('?get.agentid') ? input('get.agentid') : '';
		$userid = input('?get.userid') ? input('get.userid') : '';
		
		if($agentid){
			$sql = 'select a.id,a.username,a.province,a.city,a.area,a.address,b.name,a.server_zao,a.server_wan,a.weekday from scy_agent as a left join scy_agentclass as b on  a.servicetype = b.id where a.id ='.$agentid;
			$agentinfo = Db::query($sql);
			$agentinfo = $agentinfo[0];
			$agentinfo['province']=model("Base")->Getbyprovince($agentinfo["province"]);
			$agentinfo['city']=model("Base")->Getbycity($agentinfo["city"]);
			$agentinfo['area']=model("Base")->Getbyarea($agentinfo["area"]);
			$agentinfo["address"] = $agentinfo["province"].$agentinfo["city"].$agentinfo["area"].$agentinfo["address"];
			$agentinfo["test"] = $agentinfo["province"].$agentinfo["city"];
			$sql = "select count(*) as cnumber from scy_agentorder as a where a.paystat =1 and a.agentid=".$agentid;
			$cnumber = Db::query($sql);
			$sql = "select sum(order_fee) as sum from scy_agentorder as a where a.paystat =1 and a.agentid=".$agentid;
			$sum = Db::query($sql);
			$agentinfo["cnumber"] = $cnumber[0]['cnumber'];
			$agentinfo["sum"] = $sum[0]['sum'];
			return view('payinfobyagent',['agentinfo'=>$agentinfo]);
		}elseif($userid){
			$sql = 'select * from scy_user where id ='.$userid;
			$userinfo = Db::query($sql);
			if(!$userinfo) return '暂无数据';
			$userinfo = $userinfo[0];
			$userinfo['province']=model("Base")->Getbyprovince($userinfo["province"]);
			$userinfo['city']=model("Base")->Getbycity($userinfo["city"]);
			$userinfo['area']=model("Base")->Getbyarea($userinfo["area"]);
			$userinfo["address"] = $userinfo["province"].$userinfo["city"].$userinfo["area"].$userinfo["address"];
			$userinfo["test"] = $userinfo["province"].$userinfo["city"];
			return view('payinfobyuser',['userinfo'=>$userinfo]);
		}else{
			$sql = "select * from base_province ";
			$province = Db::query($sql);
			$paystat = input('?get.paystat') ? input('get.paystat') : '';
			if($paystat == 1){//消费管理
				return view('payinfoAll',['province'=>$province,'paystat'=>$paystat]);
			}
			if($paystat == 0){//预约管理
				$date =  date('Y-m-d H:i:s',strtotime(date('Y-m-d')));
				//今日数目 历史数目  预约成功的（排除驳回和待审核）
				$sql = "select count(if(unix_timestamp(a.addtime) <=unix_timestamp('".$date."') && paystat = 0,true,null)) as histry ,
						count(if(unix_timestamp(a.addtime) > unix_timestamp('".$date."') && paystat = 0,true,null)) as today,
						count(if(stat not in(-1,5) && paystat = 0,true,null)) as expnumber
						from scy_agentorder as a ";
				$info = Db::query($sql);
				$info = $info[0];
				return view('bespeakAll',['province'=>$province,'paystat'=>$paystat,'info'=>$info]);
			}
		}
	}
	//商户消费数据
	public function getpayinfo(){
		$userid = input('?get.userid')? input('get.userid'):'';//检查商户ID
		$agentid = input('?get.agentid')? input('get.agentid'):'';//检查商户ID
		$paystat = input('?get.paystat')? input('get.paystat'):'';//分别消费还是预约
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$username = input('?get.username')?input('get.username'):'';//获取用户名
		$agentusername = input('?get.agentusername')?input('get.agentusername'):'';//获取商户名
		$realname = input('?get.realname')?input('get.realname'):'';//获取车主
		$type = input('?get.type')?input('get.type'):'';//获取状态
		$paytime = input('?get.paytime')?input('get.paytime'):'';//获取消费时间
		$addtime = input('?get.addtime')?input('get.addtime'):'';//获取添加时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$join = '';
		$where = " where 1 ";
		//检查是否消费了
		if($paystat !==''){
			$where .= " and (a.paystat = $paystat) ";
		}
		//检查商户ID
		if($agentid){
			$where .= " and (a.agentid = $agentid) ";
		}
		//检查状态 
		if($type !== ''){
			$data = date('Y-m-d H:i:s');
			$where .= " and unix_timestamp(a.paytime) >=unix_timestamp(DATE_SUB('$data', INTERVAL 1 $type))";
		}
		//检查用户名搜索
		if($username){
			$where .= " and (a.username  like '%".$username."%') ";
		}
		//检查商户名搜索
		if($agentusername){
			$where .= " and (b.username  like '%".$agentusername."%') ";
		}
		//检查车主
		if($realname){
			$where .= " and (a.realname  like '%".$realname."%') ";
		}
		//检查消费时间
		if($paytime){
			$temp = explode(" - ",$paytime);
			$where .= " and unix_timestamp(a.paytime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.paytime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查消费时间
		if($addtime){
			$temp = explode(" - ",$addtime);
			$where .= " and unix_timestamp(a.addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.addtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查地区
		if($province){
			$join .= ' join base_province as d on b.province = d.province_id ';
			$where .= " and (d.province_id = $province ) ";
		}
		if($city){
			$join .= ' join base_city as e on b.city = e.city_id ';
			$where .= " and (e.city_id = $city ) ";
		}
		if($area){
			$join .= ' join base_area as f on b.area = f.area_id ';
			$where .= " and (f.area_id = $area ) ";
		}
		$sql = "select a.*,b.username as agentusername,b.rebate,b.province,b.city,b.area,b.address from scy_agentorder as a left join scy_agent as b on a.agentid = b.id ";
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
			$where .=' and a.id in (0'.$ids.')';
		}
		//检查用户ID
		if($userid){
			$where .= " and (a.userid = $userid) ";
			$sql = "select a.*,b.username as agentusername,b.province,b.city,b.area,b.address from scy_agentorder as a left join scy_user as b on a.userid = b.id ";
		}	
		$sql = input("?get.toexcel")? $sql.$join.$where." order by a.id desc " : $sql.$join.$where."order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$list = Db::query($sql);//获取列表
		$statarray = ['未审核','已审核','已付款','已服务','已完成','失效/驳回'];
		foreach($list as $key=>&$val){
				//获取地址
				$sql = "select acholder,bank_account,bank_name from scy_agentbank  where ismain=1 and userid=".$val["id"];
				$temp = Db::query($sql);
				if($temp){
					$val = array_merge($temp[0],$val);
				}else{
					$val["acholder"] = $val["bank_name"] = $val["bank_account"] = "";
				}
				$val["province"] = model("Base")->Getbyprovince($val["province"]);
				$val["city"] = model("Base")->Getbycity($val["city"]);
				$val["area"] = model("Base")->Getbyarea($val["area"]);
				$val["address"] = $val["province"].$val["city"].$val["area"].$val["address"];
				$val["test"] = $val["province"].$val["city"];
				$val["paystat_cg"] = $statarray[$val["paystat"]];
				$val["order_type"] = getagentclass($val["order_type"]);
			}
		
		if(input("?get.toexcel")){
			if(input('paystat')==1){
				$Base_model = model("Base");
				$Base_model->CreateAdminLog("导出列表","导出商户【".$list['0']['username']."】详情列表");
				$titary = array("商户【".$list['0']['username']."】消费详情");
				$list["tit"] = array(
					"id"=>"ID","car_license"=>"消费车辆","realname"=>"车主",
					"order_type"=>"消费类型","order_fee"=>"消费金额","paytime"=>"消费时间"
				);
				$styleary = array(
					"paytime"=>"20"
				);
				$Base_model->Toexcel($titary,$list,$styleary);
				exit;
			}
			if(input('paystat')=='0'){
				$Base_model = model("Base");
				$Base_model->CreateAdminLog("导出列表","导出商户【".$list['0']['username']."】详情列表");
				$titary = array("商户【".$list['0']['username']."】消费详情");
				$list["tit"] = array(
					"id"=>"ID","car_license"=>"消费车辆","realname"=>"车主",
					"order_type"=>"消费类型","order_fee"=>"消费金额","paytime"=>"消费时间"
				);
				$styleary = array(
					"paytime"=>"20"
				);
				$Base_model->Toexcel($titary,$list,$styleary);
			}
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出商户消费数据列表");
			$titary = array("商户消费数据");
			$list["tit"] = array(
				"id"=>"ID","username"=>"用户名","phone"=>"用户手机",
				"test"=>"受理地区","agentusername"=>"消费商家","order_type"=>"商家业务类型",
				"order_fee"=>"消费金额","rebate"=>"返现比例","paytime"=>"消费时间",
				"paystat_cg"=>"消费状态","addtime"=>"提交时间"
			);
			$styleary = array(
				"addtime"=>"20","firstadmin"=>"20","firsttime"=>"20","secondadmin"=>"20","secondtime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
			$sqlcount = "select count(*) as total from scy_agentorder as a left join scy_agent as b on a.agentid = b.id  ".$join.$where;
			$total = Db::query($sqlcount);;//获取总数
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			return json($jsonary);
		}
		
	}
	public function withinfo(){
		$userid = input('get.userid');
		return $userid ? view('withinfo',['userid'=>$userid]) : false;
	}
	//商户预约信息查看
	public function likebespoke(){

		$id = input('get.id') ? input('get.id') : exit('缺少ID');
		$stat = ['未审核','已审核','已服务','已发起支付','已完成','失效/驳回'];
		$data = model('agentorder')->getById($id);
		$data->agent = model('agent')->getById($data->agentid)->company;
		$data->isNewcar =  $data->isNewcar == 1 ? '是' : '否';
		$data->stat = $stat[$data->stat];
		$data->order_type = model('agentclass')->getById($data->order_type)->name;
		return view('likebespoke',['data'=>$data]);
	}


	//商户提现
    public function moneycount(){
		$paymenttime = input('?get.paymenttime')?input('get.paymenttime'):'';//获取结款时间
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$date = date('Y-m-d');
		//检查结款时间
		$where= '';
		if($paymenttime){
			$temp = explode(" - ",$paymenttime);
			$where .= " and unix_timestamp(a.paymenttime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.paymenttime)<=unix_timestamp('".$temp[1]."') ";
		}
		$sqlall = "select sum(money) as today ,sum(allmoney) as todayke from scy_agenttixian as a where examinetype = 3".$where; //总

		$sql = "select sum(money) as histry ,sum(allmoney) as histryke from scy_agenttixian as a where  examinetype = 3 and unix_timestamp(addtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))".$where ; //今日
		$counttoday = Db::query($sql);
		$counthistry = Db::query($sqlall);
		return view('moneycount',['province'=>$province,'countall'=>$counttoday[0],'count'=>$counthistry[0]]);
		
	}
	//返利
	public function Rebate(){
		$where= '';
		$date = date('Y-m-d');
		$sql = "select * from base_province ";
		$province = Db::query($sql);
		$sqlall = "select FORMAT(sum(order_fee*byrebate*0.01),2) as histry from scy_agentorder as a where stat = 4".$where; //总
		$sql = "select FORMAT(sum(order_fee*byrebate*0.01),2) as today from scy_agentorder as a where  stat = 4 and unix_timestamp(addtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))".$where ; //今日
		$counttoday = Db::query($sql);
		$counthistry = Db::query($sqlall);
		return view('Rebate',['province'=>$province,'countall'=>$counttoday[0],'count'=>$counthistry[0]]);
	}
	//商户消费数据
	public function getrebate(){
		$userid = input('?get.userid')? input('get.userid'):'';//检查商户ID
		$agentid = input('?get.agentid')? input('get.agentid'):'';//检查商户ID
		$paystat = input('?get.paystat')? input('get.paystat'):'';//分别消费还是预约
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$username = input('?get.username')?input('get.username'):'';//获取商户名
		$agentusername = input('?get.agentusername')?input('get.agentusername'):'';//获取商户名
		$realname = input('?get.realname')?input('get.realname'):'';//获取车主
		$type = input('?get.type')?input('get.type'):'';//获取状态
		$paytime = input('get.paytime')?input('get.paytime'):'';//获取消费时间
		$addtime = input('?get.addtime')?input('get.addtime'):'';//获取添加时间
		$province = input('?get.province')?input('get.province'):'';//获取省份
		$city = input('?get.city')?input('get.city'):'';//获取市
		$area = input('?get.area')?input('get.area'):'';//获取区
		$serchinfo = input('?get.serchinfo')?input('get.serchinfo'):'';//获取是否统计
		
		$join = '';
		$where = " where 1 ";
		//检查是否消费了
		if($paystat !==''){
			$where .= " and (a.paystat = $paystat) ";
		}
		//检查商户ID
		if($agentid){
			$where .= " and (a.agentid = $agentid) ";
		}
		//检查状态 
		if($type !== ''){
			$where .= " and (a.stat >= 3) ";
		}
		//检查用户名搜索
		if($username){
			$where .= " and (a.username  like '%".$username."%') ";
		}
		//检查商户名搜索
		if($agentusername){
			$where .= " and (b.username  like '%".$agentusername."%') ";
		}
		//检查车主
		if($realname){
			$where .= " and (a.realname  like '%".$realname."%') ";
		}
		//检查消费时间
		if($paytime){
			$temp = explode(" - ",$paytime);
			$where .= " and unix_timestamp(a.paytime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.paytime)<=unix_timestamp('".$temp[1]."') ";
		}
		//检查地区
		if($province){
			$join .= ' join base_province as d on b.province = d.province_id ';
			$where .= " and (d.province_id = $province ) ";
		}
		if($city){
			$join .= ' join base_city as e on b.city = e.city_id ';
			$where .= " and (e.city_id = $city ) ";
		}
		if($area){
			$join .= ' join base_area as f on b.area = f.area_id ';
			$where .= " and (f.area_id = $area ) ";
		}
		//确定付款后的
		$where .= " and a.stat = 4 ";

		$sql = "select FORMAT(a.byrebate * (a.order_fee / 100),2)  as rebatemoney,a.*,b.username as agentusername,b.rebate,b.province,b.nickname,b.city,b.area,b.address from scy_agentorder as a left join scy_agent as b on a.agentid = b.id ";
		//SQl语句开始
		$ids = input("?get.ids")?input("get.ids"):"";
		if($ids){
			$where .=' and a.id in (0'.$ids.')';
		}
		$sql = input("?get.toexcel")? $sql.$join.$where." order by a.id desc " : $sql.$join.$where."order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$list = Db::query($sql);//获取列表
		foreach($list as $key=>&$val){
				//获取地址
				$sql = "select acholder,bank_account,bank_name from scy_agentbank  where ismain=1 and userid=".$val["id"];
				$temp = Db::query($sql);
				if($temp){
					$val = array_merge($temp[0],$val);
				}else{
					$val["acholder"] = $val["bank_name"] = $val["bank_account"] = "";
				}
				$val["province"] = model("Base")->Getbyprovince($val["province"]);
				$val["city"] = model("Base")->Getbycity($val["city"]);
				$val["area"] = model("Base")->Getbyarea($val["area"]);
				$val["address"] = $val["province"].$val["city"].$val["area"].$val["address"];
				$val["test"] = $val["province"].$val["city"];
			}
		if(input("?get.toexcel")){
			$Base_model = model("Base");
			$Base_model->CreateAdminLog("导出列表","导出商户提现数据列表");
			$titary = array("用户列表数据");
			$list["tit"] = array(
				"id"=>"ID","test"=>"地区","username"=>"商户名",
				"nickname"=>"联系人","phone"=>"电话","order_fee"=>"客户消费金额",
				"byrebate"=>"返现比例","rebatemoney"=>"返现金额","paytime"=>"返现时间",
			
			);
			$styleary = array(
				"地区"=>"20","paytime"=>"20"
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;
		}else{
			$sqlcount = "select count(*) as total from scy_agentorder as a left join scy_agent as b on a.agentid = b.id  ".$join.$where;
			$total = Db::query($sqlcount);;//获取总数
			$sql = "select FORMAT(sum(a.order_fee*byrebate*0.01),2) as histry from scy_agentorder as a left join scy_agent as b on a.agentid = b.id ".$join.$where; //总
			$other = Db::query($sql);
			$jsonary["code"] = 0;
			$jsonary["count"] = $total[0]['total'];
			$jsonary["data"] = $list;	
			$jsonary["other"] = $other;	
			return json($jsonary);
		}
		
	}
	public function deleteimg()
	{
		$id = input('post.id');
		$imgid = input('post.imgid');
		$agent = model('agent')->where('id',$id)->find();
		$oldimgurl = json_decode($agent ->com_other_img,true); //json转换数组
		$newimgurl = array_splice($oldimgurl,1,$imgid+1); //删除一个
		$agent ->com_other_img = json_encode($newimgurl,true); //json转换数组
		
		if($agent ->save()){
			model("Base")->CreateAdminLog("删除商户图片","删除了ID为【".$id."】商户的一张图片");
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	public function getmap()
	{
		$location =  input('get.location');
		return view('map',['location'=>$location]);
	}
	public function getloginlog()
	{
		$id = input('get.userid');
		return view('loginlog',['id'=>$id]);
	}
	public function getagentlogbylogin()
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
		$list = model('LogAgent')
		->where('type','登录')
		->where($where)
		->page($page,$limits)
		->select();
		$count = model('LogAgent')
			->where('type','登录')
			->where($where)
			->count();
		$jsonary["code"] = 0;
		$jsonary["count"] = $count;
		$jsonary["data"] = $list;	
		return json($jsonary);
	}
	public function updaterebate()
	{
		//批量修改比例
		$ids = input('?post.ids')? input('post.ids'): exit($this->error('ID不存在'));//修改的ID
		$val = input('?post.val')? input('post.val'): exit($this->error('修改的值错误'));///获得的值
		$res =  model('agent')->where('id','in',$ids)->update(['rebate'=>$val]);
		if($res){
			model("Base")->CreateAdminLog("修改比例","批量修改了ID为【".$ids."】的商户比例为".$val);
			$this->success('修改成功');
		}else{
			$this->error('ID不存在');
		}
	}

	//提现审核时候查看信息
	public function liketxinfo(){
		$datatype = input('get.datatype');
		$id = input('get.id')? input('get.id'): exit('ID不存在');// 查看信息
		$huishou = input('?get.huishou')? true : false;// 查看信息
		$typearr = ['初审中','复审中','待打款','已结款','驳回'];
		$sql = "select a.*,b.username,b.province,b.city,b.area,b.address,b.nickname,b.nickphone,b.phone,c.name, 
				(select sum(money) from scy_agenttixian where examinetype = 4) as historymoney 
				from scy_agenttixian as a left join scy_".$this->table." as b on a.userid = b.id join scy_agentclass as c on b.servicetype = c.id where a.id = ".$id." limit 1";
		$list = Db::query($sql);
		$base = model('base');
		
		foreach($list as $key=>&$val){
			//获取初审
			if($val['firstexamine']){
				$sql = 'select a.name as firstadminname,b.title as firstadmingroup from scy_admin as a JOIN scy_admingroup as b on a.gid = b.id where a.id ='.$val['firstexamine'];
				$temp = Db::query($sql);
				$val = array_merge($temp[0],$val);
			}
			//获取复审
			if($val['secondexamine']){
				$sql = 'select a.name as secondadminname,b.title as secondadmingroup from scy_admin as a JOIN scy_admingroup as b on a.gid = b.id where a.id ='.$val['secondexamine'];
				$temp = Db::query($sql);
				$val = array_merge($temp[0],$val);
			}
			//获取结款
			if($val['collector']){
				$sql = 'select a.name as collectorname,b.title as collectoradmingroup from scy_admin as a JOIN scy_admingroup as b on a.gid = b.id where a.id ='.$val['collector'];
				$temp = Db::query($sql);
				$val = array_merge($temp[0],$val);
			}
			//获取地址
			$sql = "select acholder,bank_account,bank_name from scy_agentbank where ismain=1 and userid=".$val["id"];
			$temp = Db::query($sql);
			if($temp){
				$val = array_merge($temp[0],$val);
			}else{
				$val["acholder"] = $val["bank_name"] = $val["bank_account"] = "";
			}
			$val["firstexamine"]= $val["firstexamine"]?$base->Getadmin($val["firstexamine"]):'暂未审核';
			$val["secondexamine"]= $val["secondexamine"]?$base->Getadmin($val["secondexamine"]):'暂未审核';
			$val["collector"]= $val["collector"]?$base->Getadmin($val["collector"]):'暂未审核';
			$val["province"] = model("Base")->Getbyprovince($val["province"]);
			$val["city"] = model("Base")->Getbycity($val["city"]);
			$val["area"] = model("Base")->Getbyarea($val["area"]);
			$val["address"] = $val["province"].$val["city"].$val["area"].$val["address"];
			$val["test"] = $val["province"].$val["city"];
			$val["examinetype_cg"] = $typearr[$val["examinetype"]];
			$val["firsttime"] =  $val["firsttime"] !== '0000-00-00 00:00:00' ? $val["firsttime"]:'暂未审核';
			$val["secondtime"] =  $val["secondtime"] !== '0000-00-00 00:00:00' ? $val["secondtime"]:'暂未审核';
			$val["paymenttime"] =  $val["paymenttime"] !== '0000-00-00 00:00:00' ?$val["paymenttime"]:'暂未审核';
		}
		return view('liketxinfo',['list'=>$list[0],'datatype'=>$datatype]);
	}

	//新版本商户提现审核 改动时间：2018/01/17
	public function saveExaminetype(){
		$id = input('post.id') ? input('post.id') : exit($this->error('参数错误')); 
		$code = input('post.code') ? input('post.code') : exit($this->error('参数错误')); 
		$code_arr = ['cs','fs','jk','bh'];
		$code_arr['-1'] = 'hs';
		if(!in_array($code,$code_arr)){
			exit($this->error('参数错误'));
		}
		$res = model("Base")->GetCheckAuth();
		$agenttixian = model('agenttixian')->getById($id);
		
		switch ($code) {
			case 'cs': //初审
					if(in_array('agent_1',$res)){
						$agenttixian->examinetype = 1;
						$agenttixian-> firsttime = date('Y-m-d H:i:s');
						$agenttixian-> firstexamine = session('admin_id');
						$title = '初审商户提现';
						$msg = "初审商户提现，ID[".$id."]";
					}else{
						return $this->error('无此权限');
					}
					
					break;
			case 'fs': //复审
					
					if(in_array('agent_2',$res)){
						$agenttixian->examinetype = 2;
						$agenttixian-> secondtime = date('Y-m-d H:i:s');
						$agenttixian-> secondexamine = session('admin_id');
						$title = '复审商户提现';
						$msg = "复审商户提现，ID[".$id."]";
					}else{
						return $this->error('无此权限');
					}
					
					break;
			case 'jk': //结款
					if(in_array('agent_3',$res)){
						$agenttixian->imgurl = input('post.imgurl');
						$agenttixian->examinetype = 3;
						$agenttixian-> paymenttime = date('Y-m-d H:i:s');
						$agenttixian-> collector = session('admin_id');
						$title = '结算商户提现';
						$msg = "结算商户提现，ID[".$id."]";
					}else{
						return $this->error('无此权限');
					}
					
					break;
			case 'bh': //驳回
					if(in_array('agent_4',$res)){
						if($agenttixian->msg==0){
							$agenttixian-> firsttime = date('Y-m-d H:i:s');
							$agenttixian-> firstexamine = session('admin_id');
						}
						if($agenttixian->msg==1){
							$agenttixian-> secondtime = date('Y-m-d H:i:s');
							$agenttixian-> secondexamine = session('admin_id');

						}
						
						$agenttixian->msg = input('post.msg');
						$agenttixian->examinetype = 4;
						$agenttixian-> paymenttime = date('Y-m-d H:i:s');
						$title = '驳回商户提现';
						$msg = "驳回商户提现，ID[".$id."]";
					}else{
						return $this->error('无此权限');
					}
					
					break;
			case 'hs': 

					if(in_array('agent_-1',$res)){
						$agenttixian->examinetype = -1;
						$title = '回收商户提现';
						$msg = "回收商户提现，ID[".$id."]";
					}else{
						return $this->error('无此权限');
					}
					break;
			default:exit;
		}
		if($agenttixian -> save()){
			if($code == 'bh'){//驳回
				$agent = model('agent')->where('id',$agenttixian->userid)->find();//找到商家
				model('message')->getmsg('agent_txerror',[$agent->nickphone,$agenttixian->money,$agenttixian->msg]);//负责人电话  提现金额 理由
			}
			if($code == 'jk'){//结款
				$agent = model('agent')->where('id',$agenttixian->userid)->find();//找到商家
				model('message')->getmsg('agent_txsuccess',[$agent->nickphone,$agenttixian->money]);//负责人电话  提现金额 
			}
			if($code == 'fs'){//复审通过
				$agent = model('agent')->where('id',$agenttixian->userid)->find();//找到商家
				model('message')->getmsg('agent_txcheck',[$agent->nickphone,$agenttixian->money]);//负责人电话  提现金额 
			}
			
			model("Base")->CreateAdminLog($title,$msg);//写入日志
			return $this->success('操作成功');
		}else{
			return $this->error('操作失败');
		}	
	
	}


	// public function updaterebate()
	// {
	// 	//批量修改比例
	// 	$ids = input('?post.ids')? input('post.ids'): exit($this->error('ID不存在'));//修改的ID
	// 	$val = input('?post.val')? input('post.val'): exit($this->error('修改的值错误'));///获得的值
	// 	$res =  model('agent')->where('id','in',$ids)->update(['rebate'=>$val]);
	// 	if($res){
	// 		model("Base")->CreateAdminLog("修改比例","批量修改了ID为【".$ids."】的商户比例为".$val);
	// 		$this->success('修改成功');
	// 	}else{
	// 		$this->error('ID不存在');
	// 	}
	// }







	
}
