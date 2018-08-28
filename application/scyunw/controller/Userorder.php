<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
use think\Session;
class Userorder extends Common
{
	private $table='userorder';
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
	//列表页 
    public function index()
    {
		$id = input('?get.id')?input('get.id'):"";
		$adminid = input('?get.adminid')?input('get.adminid'):"";
		$user = array();
		$url = url('userorder/getlist');
		if($id){
			$user = model("user")->where('id',$id)->column('id,username,logincount,phone');
			$user = $user[$id];
			$url .= "?phone=".$user["phone"];
		}
		if($adminid){
			$url .= "?adminid=".$adminid;
		}
		if(input('?get.stat')){
			$url .= "?stat=".input('get.stat');
		}
		$sary = model("Base")->GetCheckAuth();
        return view('index',['url' => $url,'user' => $user,'sary' => $sary]);
    } 
	//投保险种/证件/详情
    public function info()
    {
		$id = input('get.id');
		$oid = input('get.oid');
		$tokentemp["token"] = "";
		$Base_model = model("Base");
		if(input('get.oid')){
			$temp = model("order")->getbyid($oid);
			$info = model($this->table)->getByRs($temp["rs"]);
			$tokentemp = model("orderphonedata")->getByRs($temp["rs"]);
		}else{
			$info = model($this->table)->getById($id);
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
		// print_r($pdetail["result"]);
			$pdetail = json_decode($pdetail["result"],true);
			// print_r($pdetail);exit;
		}
        return view('info',['info' => $info,'pdetail' => $pdetail , "oid" => $oid , "token" => $tokentemp["token"] ]);		
	}

	//询价审核
    public function check()
    {
		$id = input('get.id');
		$Base_model = model("Base");
		$info = model($this->table)->getById($id);
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
        return view('check',['info' => $info]);		
	}	
	//报价
	public function setprice(){
		$id = input('get.id');
		$Base_model = model("Base");
		$info = model($this->table)->getById($id);
		$insurance = model("insurancecompany")->getbyid($info["company"]);
		if($insurance){     
			$insurance->toArray();
			$info["company"] = $insurance["name"];
		} 		
		$insurerlist = model("Insurer")->where("insuranceid",$info["company"])->select();

		$titary = array(
			"car_name"=>"车主姓名","car_license"=>"车牌号码","apply_phone"=>"车主手机","orderarea"=>"归属地","company"=>"投保公司",
			"car_img"=>"行驶证正面","car_img_b"=>"行驶证背面","id_img"=>"身份证正面","id_img_b"=>"身份证背面","insurance"=>"险种",
		);
		$val["province"] = $Base_model->Getbyprovince($info["province"]);
		$val["city"] = $Base_model->Getbycity($info["city"]);
		$info["orderarea"] = $val["province"].$val["city"];
		$info["checkadmin"] = $Base_model->Getadmin($info["checkadminid"]);
		//获取险种
		$info["insurance"] = $Base_model->GetInsurance($info["rs"]);	
		return view('setprice',['info' => $info,'titary' => $titary,'insurerlist' => $insurerlist]);
	}
	//获取列表
	public function getlist(){
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		
		//SQl语句开始
		$sql = "select * from scy_".$this->table." ";
		$where = "where 1 ";
		if(input('?get.keyword')){
			$where .= " and (title like '%".$keyword."%') ";
		}
		if(input('?get.phone')){
			$where .= " and phone='".input("get.phone")."'  ";
		}
		if(input('?get.stat')){
			$where .= input("get.stat")?" and stat>1 ":" and stat in (0,1) ";
		}
		if(input('?get.adminid')){
			$where .= " and checkadminid= ".input('get.adminid');
		}
		$sql = $sql.$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_".$this->table." ".$where;
		//SQL语句结束
		$Base_model = model("Base");
		$list = $Base_model->query($sql);//获取列表
		$statary = array("<span style=\"color: #FFB800;\">初审中</span>","<span style=\"color: #5FB878;\">报价中</span>","<span style=\"color: #009688;\">已报价</span>","<span style=\"color: #FF5722;\">已驳回</span>");//状态数组
		$statary["-1"] = "<span style=\"color: #FFB800;\">已失效</span>";
		foreach($list as $key=>&$val){
			$val["statstr"] = $statary[$val["stat"]];
			$val["checkadmin"] = $Base_model->Getadmin($val["checkadminid"]);
			$val["offerinsurer"] = $Base_model->Getinsurer($val["offerinsurerid"]);
			$val["company"] = $Base_model->Getcompany($val["company"]);
		}
		
		$total = $Base_model->query($sqlcount);//获取总数
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;		
		return json($jsonary);	
	}
    //添加/修改
    public function GetSet(){
		$id = input('post.id');
		$post = input('post.');
		unset($post["id"]);
		$jsonary["msg"] = '';
		//执行操作
		if(isset($post["stat"])){
			if($post["stat"]==1){
				$post["checkadminid"] = Session::get('admin_id');
				$post["checktime"] = date("Y-m-d H:i:s");
				model("Base")->CreateAdminLog("询价订单初审","对订单[ID：".$id."]执行了初审操作");
			}elseif($post["stat"]==3){
				$post["rejecttime"] = date("Y-m-d H:i:s");
				model("Base")->CreateAdminLog("询价订单驳回","对订单[ID：".$id."]执行了驳回操作,原因:".$post['admin_note']);

				//驳回后处理
				$order = model("userorder")->getbyid($id);
				$temp = explode("_",$order["rs"]);//获取保单编号  
				$ordernum = $temp[0].hexdec($temp[1]);//获取保单时间   
				$temp = model("insurancecompany")->getbyid($order["company"]);
				$companyname = $temp["name"];
				$tempdata = array(
					$order["apply_phone"],//手机号码0
					$order["car_name"],//姓名1
					$order["car_license"], //车牌号码2
					$ordernum,//保单号3
					$companyname,//保险公司4
					$post['admin_note']//驳回理由5
				);			
				$result = model("message")->getmsg("inqueryfail",$tempdata);//消息相关处理  
			}else{
				model("Base")->CreateAdminLog("询价订单回收","对订单[ID：".$id."]执行了回收操作");
			}
		}elseif(isset($post["order_price"])){
			$post["stat"] = 2;
			$post["offertime"] = date("Y-m-d H:i:s");
			$order = model("userorder")->getbyid($id);
			if(!$post["order_price"]>0){
				$jsonary["msg"] =  "保单金额为空,请重新填写.";
				return json($jsonary);
			}

			//报价成功后处理
			model("Base")->CreateAdminLog("订单报价","对订单[ID：".$id."]执行了报价操作");
			$temp = explode("_",$order["rs"]);//获取保单编号  
			$ordernum = $temp[0].hexdec($temp[1]);//获取保单时间   
			$temp = model("insurancecompany")->getbyid($order["company"]);
			$companyname = $temp["name"];
			$tempdata = array(
				$order["apply_phone"],//手机号码0
				$order["car_name"],//姓名1
				$order["car_license"], //车牌号码2
				$post["order_price"],//报价金额3
				$ordernum,//保单号4
				$companyname,//保险公司5
				$id
			);			
			$result = model("message")->getmsg("inquerysuccess",$tempdata);//消息相关处理   	
		}
		$result = model($this->table)->where('id', $id)->update($post);
		return json($jsonary);
	}

	//获取接口数据
	public function getdetail(){
		$id = input("post.id");
		$Base_model = model("Base");
		$order = model($this->table)->getById($id);

		$id_img = model("Base")->base64EncodeImage(ROOT_PATH."/public/".$order["id_img"]);
		$car_img = model("Base")->base64EncodeImage(ROOT_PATH."/public/".$order["car_img"]);


		$id_img_text = $this->getidcode($id_img);
		$id_img_text = json_decode($id_img_text,true);

		$car_img_text = $this->getcarcode($car_img);
		$car_img_text = json_decode($car_img_text,true);

		$test["idstat"] = $test["carstat"] = '';
		// if($order["car_motor"]){
			$msg = "认证成功";
			if($id_img_text["code"]==10000){
				if(isset($id_img_text["result"]["cardsinfo"][0])){
					$update["id_code"] = $id_img_text["result"]["cardsinfo"][0]["items"][6]["content"];//获取身份证
					$id_name = $id_img_text["result"]["cardsinfo"][0]["items"][1]["content"];
					// 获取身份证头像
					$headimg = "data:image/jpg;base64,".$id_img_text["result"]["cardsinfo"][0]["items"][7]["content"];
					$imgurl = model("Base")->saveBase64Image($headimg);
					if($imgurl["code"]==0){
						$update["headimg"] = $imgurl["url"];
					}
					if($id_name!=$order["car_name"]){
						$msg =  "姓名与身份证姓名不符";//车牌号与行驶证车牌号不符
					}
				}else{
					$msg =  "图片识别失败";//接口返回失败
				}
			}else{
				$msg =  "验证错误";//接口返回失败
			}
			$test["idstat"] = $msg;
			//获取行驶证识别返回消息
			$msg = "认证成功";
			if($order["isNewcar"]==0){
				if($car_img_text["code"]==10000){
					if(isset($car_img_text["result"]["words_result"]["车辆识别代号"]["words"])){
						$update["car_code"] = $car_img_text["result"]["words_result"]["车辆识别代号"]["words"];
						$car_name = $car_img_text["result"]["words_result"]["所有人"]["words"];
						$car_license = $car_img_text["result"]["words_result"]["号牌号码"]["words"];
						$update["car_motor"] = $car_img_text["result"]["words_result"]["发动机号码"]["words"];
						if($id_name!=$car_name){
							$msg =  "身份证姓名与行驶证姓名不符";//身份证姓名与行驶证姓名不符
						}
						if($car_license!=$order["car_license"]){
							$msg =  "车牌号与行驶证车牌号不符";//车牌号与行驶证车牌号不符
						}
					}else{
						$msg =  "图片识别失败";//接口返回失败
					}
				}else{
					$msg =  "验证错误";//接口返回失败
				}
			}
			$test["carstat"] = $msg;
			if(isset($update)){
				$result = model("userorder")->where("rs",$order["rs"])->update($update);
			}
		// }
		return json_encode($test);
	}

    /********************************************************************************************************************/
    //接口操作    
    /********************************************************************************************************************/

    //上传行驶证识别
    public function getcarcode($img){
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'detect_direction' => 'false',
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/TONGLI/OcrVehicleLicense';
        $file = $img;
        //$file = "E:/test.jpg";
        $body = @file_get_contents($file) or exit('not found file ( '.$file.' )');
        return model("Base")->wx_http_request($url, $params , $body, true , true);
    }
    
    //上传身份证识别
    public function getidcode($img){
        $type = input("?post.type")?input("post.type"):2;
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'typeId' => $type,
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/wintone/IDCard';
        $file = $img;
        //$file = "http://7zfrrz.com1.z0.glb.clouddn.com/apicloud/6f61084b3eac452c0274937c242e3361.jpg";
        $body = @file_get_contents($file) or exit('not found file ( '.$file.' )');
        return model("Base")->wx_http_request($url, $params , $body, true , true);        
    }    
	
}
