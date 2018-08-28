<?php
namespace app\api\controller;
use app\common\model;
use think\Session;
class Order extends Common
{
    //获取保单列表
    public function index()
    {
        $post = input("post.");
        $phone = Session::get('user_phone');

        $where = array("phone"=>$phone,"stat"=>[">=",0]);
        $list = model("userorder")->where($where)->field('id,rs,order_price,car_license,stat as bstat')->order('id desc')->select();
        foreach($list as $key=>&$val){
            $temp = model("order")->getbyrs($val["rs"]);
            if($temp){
                $val["addtime"] = $temp["addtime"];
                $val["stat"] = $temp["stat"];
                $val["paystat"] = $temp["paystat"];
                $val["zt"] = $temp["fqstat"];
                $val["fktype"] = $temp["fktype"];
            }else{
                $val["addtime"] = date("Y-m-d H:i:s");
                $val["addtime"] = date("Y-m-d H:i:s");
                $val["fktype"] = 0;
                $val["stat"] = 0;
                $val["zt"] = 4;
            }
        }
        return json_encode($list);
    }

    //获取保单列表--web
    public function getorderlist()
    {
        $Base_model = model("Base");
        $post = input("post.");
        $phone = Session::get('user_phone');
        $where = array("a.phone"=>$phone,"a.stat"=>[">",0]);
        $list = model("userorder")
        ->alias('a')
        ->where($where)
        ->field('a.car_license,a.stat as bstat,a.addtime,a.order_price,a.rs,a.company,a.id')
        ->select();
        foreach($list as $key=>&$val){
            $order = model("order")->getbyrs($val["rs"]);
            if($order){
                $val["install_count"] = $order["install_count"];
                $val["stat"] = $order["stat"];
                $val["fktype"] = $order["fktype"];  
                $val["zt"] = $order["fqstat"];  
                $val["isp"] = $order["paystat"];  
            }else{
                $val["addtime"] = date("Y-m-d H:i:s");
                $val["fktype"] = $val["stat"] = $val["isp"] = 0;
                $val["install_count"] = 9;
                $val["zt"] = 4;
            }
            $val["paytype"] = $val["fktype"]?"全款":"分期";    
            $val["company"] = $Base_model->Getcompany($val["company"]);
            $temp = explode("_",$val["rs"]);//获取保单编号
            $val["rs"] = $temp[0].hexdec($temp[1]);//获取保单时间          
        }
        return json_encode($list);
    }    

    //保单询价
    public function create(){
        $post = input('post.');
        if(!isset($post["isNewcar"])) $post["isNewcar"] = 0;
        $insurer = $post["insurer"];

        //上传图片保存

        $temp_imgdata = model("Base")->saveBase64Image($post["id_img"]);
        $post["id_img"] = $temp_imgdata["url"];
        $temp_imgdata = model("Base")->saveBase64Image($post["id_img_b"]);
        $post["id_img_b"] = $temp_imgdata["url"];
        $temp_imgdata = model("Base")->saveBase64Image($post["car_img"]);
        $post["car_img"] = $temp_imgdata["url"];
        $temp_imgdata = model("Base")->saveBase64Image($post["car_img_b"]);
        $post["car_img_b"] = $temp_imgdata["url"];

        $id_img_text = json_decode($post["id_img_text"],true);
        $car_img_text = json_decode($post["car_img_text"],true);
		
		$post["car_license"] = strtoupper($post["car_license"]);

        /***************************************************************************************/
        //以下为前台询价图片验证接口
        /***************************************************************************************/
        // print_r($id_img_text);exit;
        // //获取身份证识别返回消息
        // if($id_img_text["code"]==10000){
        //     $post["id_code"] = $id_img_text["result"]["cardsinfo"][0]["items"][6]["content"];
        //     $id_name = $id_img_text["result"]["cardsinfo"][0]["items"][1]["content"];
        //     // 获取身份证头像
        //     $headimg = "data:image/jpg;base64,".$id_img_text["result"]["cardsinfo"][0]["items"][7]["content"];
        //     $imgurl = model("Base")->saveBase64Image($headimg);
        //     if($imgurl["code"]==0){
        //         $post["headimg"] = $imgurl["url"];
        //     }

        // }else{
        //     return "验证错误";//接口返回失败
        // }
        // //获取行驶证识别返回消息
        // if($post["isNewcar"]==0){
        //     if($car_img_text["code"]==10000){
        //         $post["car_code"] = $car_img_text["result"]["words_result"]["车辆识别代号"]["words"];
        //         $car_name = $car_img_text["result"]["words_result"]["所有人"]["words"];
        //         $car_license = $car_img_text["result"]["words_result"]["号牌号码"]["words"];
        //         $post["car_motor"] = $car_img_text["result"]["words_result"]["发动机号码"]["words"];
        //     }else{
        //         return "验证错误";//接口返回失败
        //     }
		// 	if($id_name!=$car_name){
		// 		return "身份证姓名与行驶证姓名不符";//身份证姓名与行驶证姓名不符
		// 	}
		// 	if($car_license!=$post["car_license"]){
		// 		return "车牌号与行驶证车牌号不符";//车牌号与行驶证车牌号不符
		// 	}
        // }
        //获取省份城市
        $tempdata = model("Base")->query("select * from base_city where city_name like '%".$post["city"]."%' or othername like '%".$post["city"]."%' limit 1");
        $post["city"] = $tempdata[0]["city_id"];
        $post["province"] = $tempdata[0]["province_id"];


        // echo $headimg;
        unset($post["insurer"]);//此项为险种
        unset($post["id_img_text"],$post["id_img_b_text"],$post["car_img_text"],$post["car_img_b_text"]);
        $phone = Session::get('user_phone');
        $userinfo = model("User")->getByPhone($phone);
        $insurer["username"] = $post["username"] = $userinfo["username"];
        $post["userid"] = $userinfo["id"];
        $insurer["addtime"] = $post["addtime"] = date("Y-m-d H:i:s");
        $insurer["phone"] = $post["phone"] = $phone;
        $insurer["rs"] = $post["rs"] = date("YmdHis") . "_" . $this->randhex(8); 
        
        
        $result = model("userorder")->insert($post);            //创建询价订单
        $insertid = model("userorder")->getLastInsID();
        $result = model("userinsurance")->insert($insurer);     //创建订单对应险种

        //注册成功后相关操作,信息提醒

        $temp = explode("_",$insurer["rs"]);//获取保单编号  
        $ordernum = $temp[0].hexdec($temp[1]);//获取保单时间   
        $temp = model("insurancecompany")->getbyid($post["company"]);
        $companyname = $temp["name"];
        $tempdata = array(
            $phone,//手机号码0
            $post["car_name"],//姓名1
            $post["car_license"], //车牌号码2
            $ordernum,//订单号3
            $companyname,//保险公司4
            $insertid
        );
        model("Base")->CreateUserLog("用户询价","用户[".$phone."]对车辆[".$post["car_license"]."]进行询价操作");//写入日志
        $result = model("message")->getmsg("inquery",$tempdata);//消息相关处理        

        //更新用户数据
        $user = model("user")->where("phone",$phone)->field("is_inquiry")->find();
        $update["is_inquiry"] = $user["is_inquiry"] + 1;
        if($post["apply_phone"]==$phone){
            $update["realname"] = $post["car_name"];
            $update["carlicense"] = $post["car_license"];
            // $update["id_license"] = $post["id_code"];
            $update["id_img"] = $post["id_img"];
            $update["id_img_b"] = $post["id_img_b"];
            $update["car_img"] = $post["car_img"];
            $update["car_img_b"] = $post["car_img_b"];
            // $update["headimg"] = $post["headimg"];
            $update["rnstat"] = 1;
            $update["carstat"] = 1;
            $result = model("user")->where("phone",$phone)->update($update);
        }
        return 1;
        // print_r($insurer);
        // print_r($post);
    }

    //获取已报价未确认订单
    public function getuserorder(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        if(!input("?post.id")){
            $where = array("phone"=>$phone);
            $where["rs"] = array(
                ["exp","NOT IN (select rs from scy_order where phone='".$phone."' or fktype=1)"],
                ["exp","IN (select rs from scy_order where phone='".$phone."' and fqstat in (0,1,2,3))"],
                'or'
            );
			$where["stat"] = array("exp","IN (0,1,2)");
			// $where["rs"] = array("exp","NOT IN (select rs from scy_order where fktype=1)");
	
        }else{
            $id = input("post.id");
            $where = array("id"=>$id);
        }
        $userorder = model("userorder")
        ->alias('a')
        ->field('a.id,admin_note as adminnote,apply_phone,stat,phone,rs as ordernum,jqprice,csprice,syprice,order_price,car_license,car_name,id_code,car_code,a.id as orderid,offerinsurerid')
        ->where($where)
        ->order(['a.id'=>'desc'])
        ->find();
        if($userorder){
            $userorder->toArray();
            $userorder["step"] = 0;//已报价，未选择分期/全款
            $insurance = model("insurer")->getbyid($userorder["offerinsurerid"]);
            // if($insurance){     
            //     $insurance->toArray();
            //     $userorder["companyname"] = $insurance["companyname"];
            // }            
            $order = model("order")->getbyrs($userorder["ordernum"]);
            if($order){
                //获取保单信息
                $order->toArray();
                $userorder["isp"] = $order["paystat"];//已选择分期
                $userorder["step"] = 2;//已选择分期
                $userorder["install_count"] = $order["install_count"];
                $install = model("orderinstall")->where(array("rs"=>$userorder["ordernum"],"paystat"=>0))->order('id asc')->find();
                //身份已认证
                if($order["faceresult"]>0){
                    $userorder["step"] = 2;
                    $phoneinfo = model("Base")->getphoneinfo($userorder["apply_phone"]);
                    $phoneinfo = json_decode($phoneinfo,true);
                    $userorder["carrier"] = $phoneinfo["carrier"];
                }
                if($order["phoneresult"]=="SAME"){$userorder["step"] = 3;}//运营商已认证
                if($order["fqstat"]>0){$userorder["step"] = 4;}//已生成分期
                if($install){
                    $install->toArray();
                    $userorder["paydate"] = date("Y-m-d",strtotime($install["yuqitime"]));
                    $userorder["payfee"] = $install["money"];
                    $userorder["nowqishu"] = $install["qishu"];
                }else{
                    $userorder["isp"] = 1;
                }
                $userorder["awaketime"] = strtotime($order["awaketime"])>0?date("Y-m-d",strtotime($order["awaketime"])):"未生效  ";
                $userorder["endtime"] = strtotime($order["awaketime"])>0?date("Y-m-d",strtotime("+1 year",strtotime($order["awaketime"]))):" ";
                $userorder["lasttime"] = date("Y-m-d",strtotime("+1 day",strtotime($userorder["endtime"])));
                $userorder["express_company"] = $order["express_company"];
                $userorder["express_id"] = $order["express_id"];
                $userorder["express_gettime"] = strtotime($order["express_gettime"]);
                $userorder["express_time"] = $order["express_time"];
                $userorder["senddays"] = $order["express_time"]?intval((strtotime(date("Y-m-d"))-strtotime($order["express_time"]))/60/60/24):-1;
                $userorder["fqstat"] = $order["fqstat"];
                $userorder["fktype"] = $order["fktype"];
                $userorder["install_3"] = sprintf("%.2f",$userorder["order_price"]/3);
                $userorder["install_6"] = sprintf("%.2f",$userorder["order_price"]/6);
                $userorder["install_9"] = sprintf("%.2f",$userorder["order_price"]/9);
				$userorder["orderpics"] = $order["orderpics"];
            }else{
                $userorder["install_3"] = sprintf("%.2f",$userorder["order_price"]/3);
                $userorder["install_6"] = sprintf("%.2f",$userorder["order_price"]/6);
                $userorder["install_9"] = sprintf("%.2f",$userorder["order_price"]/9);
            }

            $temp = explode("_",$userorder["ordernum"]);//获取保单编号
            $userorder["ordernum"] = $temp[0].hexdec($temp[1]);//获取保单时间
            $userorder["ordertime"] = substr($temp[0],0,4)."-".substr($temp[0],4,2)."-".substr($temp[0],6,2)." ".substr($temp[0],8,2).":".substr($temp[0],10,2);
        }
        return json_encode($userorder);
    }
    
    //获取物流信息
    public function getexpress(){
        $phone = Session::get('user_phone');
        $express_id = input("post.express_id");
        model("Base")->CreateUserLog("获取物流信息","用户[".$phone."]对运单号[".$express_id."]进行查询操作");
        return model("Base")->getexpress($express_id);
    }

    //确认收货
    public function send(){
        $id = input("post.id");
        $phone = Session::get('user_phone');
        $userorder = model("userorder")->getbyid($id);
        $result = model("order")->where("rs",$userorder["rs"])->update(["express_gettime"=>date("Y-m-d H:i:s")]);
        model("Base")->CreateUserLog("确认收货","用户[".$phone."]对保单进行收货操作");
        return $result;
    }


    //车险购买，生成保单
    public function buy(){
        set_time_limit(0);
        $post = input("post.");
        $phone = Session::get('user_phone');
        //获取询价订单信息
        $inset = model("userorder")
        ->field('offerinsurerid as insurerid,rs,username,phone,car_name as realname,id_code as id_license,order_price,car_license,apply_phone')
        ->where("id",$post["id"])->find()->toArray();
        //获取保单信息
        $applyphone = $inset["apply_phone"];
        $order = model("order")->getbyrs($inset["rs"]);
        $inset["addtime"] = date("Y-m-d H:i:s");
        $timestamp = strtotime($inset["addtime"]);
        $totalprice = $inset["order_price"];
        $car_license = $inset["car_license"];
        unset($inset["order_price"],$inset["car_license"],$inset["apply_phone"]);
        $inset["fktype"] = $post["fktype"];
        $install = array();
        $install["rs"] = $inset["rs"];
        $install["username"] = $inset["username"];
        $install["phone"] = $inset["phone"];
        $inset["active"] = 0;
        if($inset["fktype"]==0){
            $update["install_count"] = $inset["install_count"] = isset($post["install_count"])?$post["install_count"]:9;
            $update["install_day"] = $inset["install_day"] = date("d");
            $update["install_fee"] = $inset["install_fee"] = sprintf("%.2f",$totalprice/$inset["install_count"]);
            $install["type"] = 0;            
            if($order){
                if(isset($post["install_count"])){
                    for($i=1;$i<=$inset["install_count"];$i++){
                        $install["qishu"] = $i;
                        $timestamp = strtotime("+1 months",$timestamp);
                        $install["yuqitime"] = date("Y-m-d",$timestamp)." 23:59:59";
                        $install["money"] = $inset["install_fee"];
                        $install["beforemoney"] = $totalprice;
                        $totalprice = sprintf("%.2f",($totalprice - $inset["install_fee"]));
                        if($totalprice<0){$totalprice = 0;}
                        $install["aftermoney"] = $totalprice;
                        $install["car_license"] = $car_license;
                        //print_r($install);
                        $result = model("orderinstall")->insert($install); 
                    }
                    $update["active"] = 1;
                    $update["fqstat"] = 1;
                    $result = model("order")->where("rs",$inset["rs"])->update($update); 
                    model("Base")->CreateUserLog("创建分期订单","用户[".$phone."]创建分期订单，订单号：[".$inset["rs"]."]");
                }else{
                    if($order["phoneresult"]!="SAME"){
                        //验证短信验证码
                        $smscode = input("?post.smscode")?$post["smscode"]:0;
                        $result = model("Base")->smscheck($phone,$smscode);
                        if($result!=1){return $result;}
                        $returntxt = $this->checkphone($post["apply_phone"],$post["car_name"]);//运行运营商验证接口
                        //$returntxt = '{"code":"10000","charge":true,"msg":"查询成功,扣费","result":{"data":{"name":"王策士","mobile":"17606036160","compareStatus":"SAME","compareStatusDesc":"一致"},"success":true}}';
                        $returntxt = json_decode($returntxt,true);
                        // print_r($returntxt);
                        if($returntxt["code"]=="10000"){
                            if(isset($returntxt["result"]["data"])){
                                $result = model("order")->where("rs",$inset["rs"])->update(["phoneresult"=>$returntxt["result"]["data"]["compareStatus"]]); 
                                if($returntxt["result"]["data"]["compareStatus"]!="SAME"){
                                    return "运营商验证失败";//运营商验证失败
                                }
                            }else{
                                return $returntxt["errorDesc"];//运营商验证失败
                            }
                            if($applyphone==$phone){
                                $result = model("user")->where("phone",$phone)->update(["phone_stat"=>1]);
                            }                            
                        }else{
                            return "接口返回失败";//接口返回失败
                        }    
                    }                
                }
            }else{
                $temp_imgdata = model("Base")->saveBase64Image($post["id_img"]);
                $inset["id_img"] = $temp_imgdata["url"];
                $temp_imgdata = model("Base")->saveBase64Image($post["id_img_b"]);
                $inset["id_img_b"] = $temp_imgdata["url"];
                $temp_imgdata = model("Base")->saveBase64Image($post["id_img_r"]);
                $inset["id_img_r"] = $temp_imgdata["url"];
                $post["id_img_text"] = preg_replace("/^data:image\/(jpg|jpeg|png|gif);base64,/", "", $post["id_img_text"]);
                $post["id_img_r_text"] = preg_replace("/^data:image\/(jpg|jpeg|png|gif);base64,/", "", $post["id_img_r_text"]); 
                


                $inset["awakedate"] = $post["awakedate"];
				if($inset["insurerid"]==0){
					$inset["approtime"] = date("Y-m-d H:i:s");
				}

                $result = model("order")->insert($inset); 
                return 1;
                exit;
                //以下是前台验证                
                for($i=0;$i<2;$i++){
                    $returntxt = $this->checkfaceid($post["id_img_text"],$post["id_img_r_text"]);//运行人证识别接口
                    // $returntxt = '{"code":"10000","charge":true,"msg":"查询成功,扣费","result":{"result":"success","score":"0.608741","code":"0"}}';
                    $returntxt = json_decode($returntxt,true);
                    //print_r($returntxt);
                    $config = model("config")->find();
                    $score = $config?($config["face_value"]/100):0.5;//获取系统人脸识别设置
                    if($returntxt["code"]=="10000"){
                        if(!isset($returntxt["result"]["score"])){
                            $inset["awakedate"] = $post["awakedate"];
                            $result = model("order")->insert($inset); 
                            // return 1;//跳过错误，后台审核
                            if(isset($returntxt["result"]["code"])){
                                $errorary = array(
                                    "430"=>"没有有效的key",
                                    "431"=>"请求不在白名单范围内",
                                    "432"=>"fingerprint illegal",
                                    "433"=>"package name illegal",
                                    "434"=>"您没有申请相应的服务，或服务的次数已到临界值，或服务已到期",
                                    "437"=>"身份证请上传横板图片",
                                    "438"=>"您的请求参数json格式非法",
                                    "439"=>"您的请求参数中图片数据为空",
                                    "4020"=>"不是有效的图片",
                                    "4030"=>"识别服务错误"
                                );  
                                if(isset($errorary[$returntxt["result"]["code"]])){
                                    if($i==1){return $errorary[$returntxt["result"]["code"]];}
                                }else{
                                    if($i==1){return "脸部占比不足，请参考示例重新上传：".$returntxt["result"]["result"];}
                                }
                            }else{
                                return "获取失败";
                            }
                        }else{
                            if($returntxt["result"]["score"]>$score){
                                $inset["faceresult"] = $returntxt["result"]["score"];  
                                $inset["awakedate"] = $post["awakedate"];
                                if($applyphone==$phone){
                                    $result = model("user")->where("phone",$phone)->update(["face_stat"=>1]);
                                }   
                                $result = model("order")->insert($inset); 
                                break;
                            }else{
                                return "手持照片必须与身份证头像相符！";//return 0;//相似度低于50%
                            }
                        }
                    }else{
                        return "接口返回失败";//return 0;//接口返回失败
                    }
                }
                //print_r($post["id_img"]);
            }
            return 1;
        }else{
            if($order&&$order["fktype"]==1&&$order["paystat"]==1){
                return "已存在订单";
            }else{
                $inset["install_fee"] = $totalprice;
                $inset["install_count"] = 1;
                $install["type"] = 1;
                $install["qishu"] = 1;
                $install["money"] = $totalprice;
                $install["beforemoney"] = $totalprice;
                $install["aftermoney"] = 0;
                $install["car_license"] = $car_license;
                $install["ordernum"] = date("YmdHis").mt_rand("1000","9999");
                $install["paytype"] = $post["paytype"];
                $body = "车辆：".$car_license."的全款支付订单";
                $subject = "车辆：".$car_license."的全款订单";
                //print_r($install);
                $totalprice = 0.01;
                $totalprice = sprintf("%.2f",$totalprice);
                if($order){
                    model("order")->where("rs",$order["rs"])->delete();
                }
                $result = model("order")->insert($inset); 
                $result = model("orderinstall")->insert($install); 
                if($post["paytype"]==1){
                    $returnresult = model("Wechatpay")->wxpay($install["ordernum"],$body,$totalprice); 
                    unset($returnresult["appid"]);
                    $returnresult["tradeNO"] = $install["ordernum"];
                }else{
                    $returnresult["subject"] = $subject;
                    $returnresult["body"] = $body;
                    $returnresult["amount"] = $totalprice;
                    $returnresult["tradeNO"] = $install["ordernum"];
                }
                model("Base")->CreateUserLog("创建全款订单","用户[".$phone."]创建全款订单，订单号：[".$inset["rs"]."]");
                return json_encode($returnresult);              
            }
        }
        // print_r($post);
        // print_r($inset);
    }

    //支付分期订单
    public function payinstall(){
        $id = input("post.id");
        $qishu = input("post.qishu");
        $paytype = input("post.paytype");
        $userorder = model("userorder")->field('rs,car_license')->where("id",$id)->find()->toArray();        
        $where = array("rs"=>$userorder["rs"],"qishu"=>array("exp","in (".$qishu.")"));
        $install = model("orderinstall")->where($where)->find()->toArray();
        // print_r($install);
        $update = array();
        $update["ordernum"] = date("YmdHis").mt_rand("1000","9999");
        $install["ordernum"] = $update["ordernum"];
        $update["paytype"] = $paytype;
        $result = model("orderinstall")->where($where)->update($update);//更新订单号，支付类型

        /* 支付所需信息 */
        $body = "车辆：".$userorder["car_license"]."的第".$qishu."期的支付订单";
        $subject = "车辆：".$userorder["car_license"]."的分期订单";

        /* 获取选中期数金额 */
        $temp = model("orderinstall")->where($where)->sum("money");
        $totalprice = $temp;
        $totalprice = 0.01;
        $totalprice = sprintf("%.2f",$totalprice);
        if($result){
            if($paytype==1){//微信生成预支付订单
                $returnresult = model("Wechatpay")->wxpay($install["ordernum"],$body,$totalprice); 
                unset($returnresult["appid"]);
                $returnresult["tradeNO"] = $install["ordernum"];
            }else{//支付宝直接返回信息到app，apicloud方案一
                $returnresult["subject"] = $subject;
                $returnresult["body"] = $body;
                $returnresult["amount"] = $totalprice;
                $returnresult["tradeNO"] = $install["ordernum"];
            }
            return json_encode($returnresult);   
        }else{
            return 0;
        }
    }
	
    //获取分期账单
    public function getinstall(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        $where = array("a.phone"=>$phone,"b.fktype"=>0,"b.fqstat"=>[">=",1]);
        $json["list"] = model("userorder")
        ->alias('a')
        ->join('scy_order b','a.rs = b.rs')
        ->field('b.fqstat as zt,a.id,a.rs,a.car_license,a.addtime,b.awaketime,a.order_price')
        ->where($where)
        ->select();
        $count = 0;
        $rslist = "'0'";
        if($json["list"]){
            foreach($json["list"] as $key=>&$val){
                $rslist .= ",'".$val["rs"]."'";
                $count ++;
                $val["addtime"] = date("Y-m-d",strtotime($val["addtime"]));
                $val["endtime"] = strtotime($val["awaketime"])?date("Y-m-d",strtotime("+1 year",strtotime($val["awaketime"]))):"";
                if(!strtotime($val["awaketime"])){
                    $val["statstr"] = $val["zt"]<4?"等待审核":"等待出单";
                }
            }
            $where = "UNIX_TIMESTAMP(yuqitime)-UNIX_TIMESTAMP(now())<=31*24*60*60 and paystat=0 and rs in (".$rslist.") ";
            $json["nopaycount"] = model("orderinstall")
            ->where($where)
            ->group('rs')
            ->count();
            $json["count"] = $count;
            $json["rs"] = $rslist;
        }else{
            $json["count"] = 0;
            $json["rs"] = $rslist;
        }
        return json_encode($json);
    }

    //获取分期账单详细
    public function getinstalldetail(){
        $post = input("post.");
        $phone = Session::get('user_phone');
        $id = $post["id"];
        $userorder = model("userorder")->getbyid($id);
        $install = model("orderinstall")->where("rs",$userorder["rs"])->select();
        foreach($install as $key=>&$val){
            $val["paystatstr"] = $val["paystat"]?"已还款":"未还款";
            
            if($val["yuqistat"]){ 
                $val["paystatstr"] = "已逾期";
                $val["yuqiday"] = intval((strtotime(date("Y-m-d H:i:s")) - strtotime($val["yuqitime"]))/24/60/60);
            }else{
                $val["yuqiday"] = 0 - intval((strtotime(date("Y-m-d H:i:s")) - strtotime($val["yuqitime"]))/24/60/60);
            }
            $val["yuqitime"] = date("Y-m-d",strtotime($val["yuqitime"]));
            if($val["issms"]){
                $val["paystatstr"] = $val["issms"]==1?"三日后逾期":"明日逾期";
            }
        }
        return json_encode($install);

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

    //运营商验证接口
    public function checkphone($phone,$name){
        header("Content-type: application/json; charset=utf-8");
        $params = array(
            'mobile' => $phone,
            'name' => $name,
            'appkey' => config('JDAPPKEY')
        );
        $url = 'https://way.jd.com/freedt/element2';
        return model("Base")->wx_http_request($url, $params );
    }
    
    public function gen_randomstr($n=12) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $n);
    }

    public function randhex(){
        $a = mt_rand(268435456,999999999);
        return dechex($a);
    }


}
