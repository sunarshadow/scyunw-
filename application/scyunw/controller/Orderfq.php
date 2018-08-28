<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
use think\Session;
class Orderfq extends Common
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
		$url = url('orderfq/getlist');
		$url = input('?get.stat')?$url."?stat=".input('get.stat'):$url;
		if(input('?get.adminid')){
			$url .= "?adminid=".input('get.adminid');
			$url .= "&type=".input('get.type');
		}
		$adminlist = model('admin')->all();
		$sary = model("Base")->GetCheckAuth();
		// print_r($sary);exit;
        return view('index',['province' => $province,'url' => $url,'adminlist' => $adminlist,'stat' => input('get.stat'),'sary' => $sary]);
    }
    public function info()
    {
		$id = input('get.id');
		$info = model($this->table)->getById($id);
		$offer = model("Userorder")->getByRs($info["rs"]);
		$val["province"] = model("Base")->Getbyprovince($offer["province"]);
		$val["city"] = model("Base")->Getbycity($offer["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		
        return view('info',['info' => $info,'offer' => $offer]);
	}
	//询价审核
    public function check()
    {
		$id = input('get.id');
		$Base_model = model("Base");
		$order = model($this->table)->getById($id);
		$info = model("Userorder")->getByRs($order["rs"]);
		$insurance = model("insurancecompany")->getbyid($info["company"]);
		if($insurance){     
			$insurance->toArray();
			$info["company"] = $insurance["name"];
		} 		
		$val["province"] = $Base_model->Getbyprovince($info["province"]);
		$val["city"] = $Base_model->Getbycity($info["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		if($order["firstadminid"]){
			$order["firstadmin"] = $Base_model->Getadmin($order["firstadminid"]);
		}
		$config = model("config")->find();
		$score = $config?($config["face_value"]/100):0.5;
		$order["stat"] = $order["faceresult"]>$score?"<span style='color:#5FB878'>认证成功</span>":"<span style='color:#FF5722'>认证失败</span>";
		//获取险种
		$info["insurance"] = $Base_model->GetInsurance($order["rs"]);
		$tokentemp = model("orderphonedata")->getByRs($order["rs"]);
		$stat = input("get.fs");
        return view('check',['info' => $info,'stat' => $stat,'order' => $order , "token" => $tokentemp["token"] ]);		
	}		
	//人脸识别
	public function checkface(){
		$id = input('post.id');
		$Base_model = model("Base");
		$order = model($this->table)->getById($id);
		if($order["faceresult"]==0){
			$id_img_r = model("Base")->base64EncodeImage(ROOT_PATH."/public/".$order["id_img_r"]);
			$id_img = model("Base")->base64EncodeImage(ROOT_PATH."/public/".$order["id_img"]);
			$id_img_b = model("Base")->base64EncodeImage(ROOT_PATH."/public/".$order["id_img_b"]);

			$id_img_r = preg_replace("/^data:image\/(jpg|jpeg|png|gif);base64,/", "", $id_img_r);
			$id_img = preg_replace("/^data:image\/(jpg|jpeg|png|gif);base64,/", "", $id_img); 
			$id_img_b = preg_replace("/^data:image\/(jpg|jpeg|png|gif);base64,/", "", $id_img_b); 


			$returntxt = $this->checkfaceid($id_img,$id_img_r);//运行人证识别接口
			$returntxt = json_decode($returntxt,true);
			$config = model("config")->find();
			$score = $config?($config["face_value"]/100):0.5;//获取系统人脸识别设置
			if($returntxt["code"]=="10000"){
				if(!isset($returntxt["result"]["score"])){
					if(isset($returntxt["result"]["code"])){
						$errorary = array(
							"430"=>"没有有效的key",
							"431"=>"请求不在白名单范围内",
							"432"=>"fingerprint illegal",
							"433"=>"package name illegal",
							"434"=>"您没有申请相应的服务，或服务的次数已到临界值，或服务已到期",
							"437"=>"身份证请上传横板图片",
							"438"=>"认证失败",
							"439"=>"您的请求参数中图片数据为空",
							"4020"=>"不是有效的图片",
							"4030"=>"识别服务错误"
						);  
						if(isset($errorary[$returntxt["result"]["code"]])){
							return $errorary[$returntxt["result"]["code"]];
						}else{
							return "认证失败";
						}
					}else{
						return "认证失败";
					}
				}else{
					$inset["faceresult"] = $returntxt["result"]["score"];  
					$result = model($this->table)->where("rs",$order["rs"])->update($inset);
					if($returntxt["result"]["score"]>$score){
						return 1;
						$inset["faceresult"] = $returntxt["result"]["score"];  
						if($applyphone==$phone){
							$result = model("user")->where("phone",$phone)->update(["face_stat"=>1]);
						}   
						break;
					}else{
						return "手持照片必须与身份证头像相符！";//return 0;//相似度低于50%
					}
				}	
			}	
		}else{
			return 1;
		}
	}

    //人证对比接口
    public function checkfaceid($img1,$img2){
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/hanvoncloud/faceid';
        $body = '{"uid": "118.12.0.12","idcardImage":"'.$img1.'","faceImage":"'.$img2.'"}';
        return model("Base")->wx_http_request($url, $params , $body, true );        
    }	

    //列表
    public function getlist()
    {
		$where = "where 1 and a.fktype=0 ";
		//获取关键字
		$keyword = input('?get.keyword')?input('get.keyword'):'';
		if(input('?get.keyword')){
			$where .= " and (b.car_license like '%".$keyword."%' or a.phone like '%".$keyword."%' or a.realname like '%".$keyword."%') ";
		}
		//分期状态
		$fqstat = input('?get.fqstat')?input('get.fqstat'):'';
		if($fqstat!=''){
			$where .= " and a.fqstat='".$fqstat."' ";
		}else{
			//获取待审核/待确认/已作废
			$stat = input('?get.stat')?input('get.stat'):'';
			if(input('?get.stat')){
				//$where .= $stat?" and unix_timestamp(a.awaketime)>0 ":" and unix_timestamp(a.awaketime)=0 ";//是否生效
				$where .= $stat?($stat>1?" and a.stat in (-1,7) ":" and a.fqstat in (3) "):" and a.fqstat in (1,2) and a.stat<7 ";//是否有效
			}			
		}
		//获取提交日期
		$add = input('?get.add')?input('get.add'):'';
		if($add){
			$temp = explode(" - ",$add);
			$where .= " and unix_timestamp(a.addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.addtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//获取初审日期
		$first = input('?get.first')?input('get.first'):'';
		if($first){
			$temp = explode(" - ",$first);
			$where .= " and unix_timestamp(a.firsttime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.firsttime)<=unix_timestamp('".$temp[1]."') ";
		}
		//获取复审日期
		$second = input('?get.second')?input('get.second'):'';
		if($second){
			$temp = explode(" - ",$second);
			$where .= " and unix_timestamp(a.secondtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.secondtime)<=unix_timestamp('".$temp[1]."') ";
		}
		//获取审核人相关数据
		if(input('?get.adminid')){
			$where .= " and ".input('get.type')."adminid= ".input('get.adminid');
		}
		//sql开始
		$sql = "select *,a.id as id,a.stat as stat,b.id as bid,a.admin_note as admin_note,a.rejecttime as rejecttime from scy_".$this->table." as a left join scy_userorder as b on a.rs=b.rs ";
		$sql = $sql.$where;
		if(input("?get.toexcel")){
			$statary = array("等待认证","等待初审","等待复审","审核完成","已签约");
			$statary[-1] = "已失效";
		}else{
			$statary = array("<font color='#d2d2d2'>等待认证</font>","<font color='#FFB800'>等待初审</font>","<font color='#FF5722'>等待复审</font>","<font color='#5FB878'>审核完成</font>","<font color='#009688'>已签约</font>");
			$statary[-1] = "<font color='#2F4056'>已失效</font>";
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
			$val["install_fee"] = "￥".sprintf("%.2f", ($val["install_count"]?($val["order_price"]/$val["install_count"]):0));
			$val["order_price"] = "￥".sprintf("%.2f", $val["order_price"]);
			$val["firstadmin"] = $Base_model->Getadmin($val["firstadminid"]);
			$val["secondadmin"] = $Base_model->Getadmin($val["secondadminid"]);
			$val["company"] = $Base_model->Getinsurer($val["offerinsurerid"]);
			$val["statshow"] = $statary[$val["fqstat"]];
			if($val["stat"]==7){ $val["statshow"] = "<font color='#FF5722'>已驳回</font>";}
		}		
		// print_r($list);exit;	
		if(input("?get.toexcel")){
			$Base_model->CreateAdminLog("导出列表","导出分期订单列表");
			$titary = array("分期订单列表");
			$list["tit"] = array(
				"id"=>"ID","username"=>"用户名","phone"=>"手机",
				"car_license"=>"车牌号","jqprice"=>"投保地区","company"=>"投保公司",
				"order_price"=>"保费总额","install_count"=>"分期数","install_fee"=>"每月还款",
				"statshow"=>"审核状态","addtime"=>"提交时间","firstadmin"=>"风控初审",
				"firsttime"=>"初审时间","secondadmin"=>"风控复审","secondtime"=>"复审时间",
			);
			$styleary = array(
				"addtime"=>"20","firstadmin"=>"20","firsttime"=>"20","secondadmin"=>"20","secondtime"=>"20"
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
		$adminary = array("2"=>"first","3"=>"second");
		if(isset($post["fqstat"])){
			if($post["fqstat"]>0){
				$post[$adminary[$post["fqstat"]]."adminid"] = Session::get('admin_id');
				$post[$adminary[$post["fqstat"]]."time"] = date("Y-m-d H:i:s");
				$post["stat"] = 0 ;
				$info = model("order")->getbyid($id);
				$order = model("userorder")->getbyrs($info["rs"]);
				$temp = explode("_",$order["rs"]);//获取保单编号  
				$ordernum = $temp[0].hexdec($temp[1]);//获取保单时间   
				$temp = model("insurancecompany")->getbyid($order["company"]);
				$companyname = $temp["name"];
				$tempdata = array(
					$order["apply_phone"],//手机号码0
					$order["car_name"],//姓名1
					$order["car_license"], //车牌号码2
					sprintf("%.2f",$info["install_fee"]),//分期金额3
					$info["install_count"],//分期金额4
					$info["addtime"],//分期金额5
					$order["id"]
				);	
				if($post["fqstat"]==2){
					model("Base")->CreateAdminLog("分期初审","对分期订单[ID：".$id."]执行了分期初审操作");
				}elseif($post["fqstat"]==3){
					model("Base")->CreateAdminLog("分期复审","对分期订单[ID：".$id."]执行了分期复审操作");		
					$result = model("message")->getmsg("stagesuccess",$tempdata);//消息相关处理   
				}elseif($post["fqstat"]=="-1"){
					model("Base")->CreateAdminLog("分期回收","对分期订单[ID：".$id."]执行了分期回收操作");
					$result = model("message")->getmsg("stagefail",$tempdata);//消息相关处理   
				}
			}

			if($post["fqstat"]=="-1"){
				$post["stat"] = -1 ;
			}
		}else if(isset($post["stat"])){
			$post["rejecttime"] = date("Y-m-d H:i:s");
		}		
		$result = model($this->table)->where('id', $id)->update($post);
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
    
}
