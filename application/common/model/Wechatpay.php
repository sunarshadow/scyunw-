<?php
namespace app\common\model;

use think\Model;
use think\Db;
use think\Session;
class Wechatpay extends Model
{
    //自动完成[新增和修改时都会执行]
    protected $auto =[
    ];
    //新增时自动验证
    protected $insert=[
    ];
    //修改时自动验证
    protected $update=[
	];
	

	//微信APP支付接口
	public function wxpay($out_trade_no,$body,$total_fee,$url = "http://chexian.302s.cn/api/index/wxpayresult"){
		$json = array();
		//生成预支付交易单的必选参数:
		$newPara = array();
		//应用ID
		$newPara["appid"] = "wx360a9ada7f6d5d32";
		//商户号
		$newPara["mch_id"] = "1495105242";
		//设备号
		$newPara["device_info"] = "WEB";
		//随机字符串,这里推荐使用函数生成
		$newPara["nonce_str"] = $this->generate_password(32);
		//商品描述
		$newPara["body"] = $body;
		//商户订单号,这里是商户自己的内部的订单号
		$newPara["out_trade_no"] = $out_trade_no;
		//总金额
		$newPara["total_fee"] = $total_fee*100;
		//终端IP
		$newPara["spbill_create_ip"] = $_SERVER["REMOTE_ADDR"];
		//通知地址，注意，这里的url里面不要加参数
		$newPara["notify_url"] = $url;
		//交易类型
		$newPara["trade_type"] = "APP";
		//第一次签名
		$newPara["sign"] = $this->produceWeChatSign($newPara);
		//把数组转化成xml格式
		$xmlData = $this->getWeChatXML($newPara);
		//利用PHP的CURL包，将数据传给微信统一下单接口，返回正常的prepay_id
		$get_data = $this->sendPrePayCurl($xmlData);
			//  return $get_data;
		//返回的结果进行判断。
		if($get_data['return_code'] == "SUCCESS" && $get_data['result_code'] == "SUCCESS"){
			//根据微信支付返回的结果进行二次签名
			//二次签名所需的随机字符串
			$newPara["nonce_str"] = $this->generate_password(32);
			//二次签名所需的时间戳
			$newPara['timeStamp'] = time()."";
			//二次签名剩余参数的补充
			$secondSignArray = array(
				"appid"=>$newPara['appid'],
				"noncestr"=>$newPara['nonce_str'],
				"package"=>"Sign=WXPay",
				"prepayid"=>$get_data['prepay_id'],
				"partnerid"=>$newPara['mch_id'],
				"timestamp"=>$newPara['timeStamp'],
			);
			$json['datas'] = $secondSignArray;
			$json['ordersn'] = $newPara["out_trade_no"];
			$json['datas']['sign'] = $this->weChatSecondSign($newPara,$get_data['prepay_id']);
			$json['message'] = "预支付完成";
			//预支付完成,在下方进行自己内部的业务逻辑
			/*****************************/
			// return json_encode($json);
			return $json;
		}
		else{
			$json['message'] = $get_data['return_msg'];
		}
	}
	
	//第一次签名的函数produceWeChatSign
	public function produceWeChatSign($newPara){
		$stringA = $this->getSignContent($newPara);
		$stringSignTemp=$stringA."&key=T0YXPJWQQ1ggVOe1OeBViAjxYykceE2b";
		return strtoupper(MD5($stringSignTemp));
	}
	
	//生成xml格式的函数
	static function getWeChatXML($newPara){
		$xmlData = "<xml>";
		foreach ($newPara as $key => $value) {
			$xmlData = $xmlData."<".$key.">".$value."</".$key.">";
		}
		$xmlData = $xmlData."</xml>";
		return $xmlData;
	}
	
	//通过curl发送数据给微信接口的函数
	public function sendPrePayCurl($xmlData) {
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		$header[] = "Content-type: text/xml";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlData);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		if (curl_errno($curl)) {
			print curl_error($curl);
		}
		curl_close($curl);
		return $this->XMLDataParse($data);
	}
	
	//xml格式数据解析函数
	static function XMLDataParse($data){
		$msg = array();
		$msg = (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		return $msg;
	}
	
	//二次签名的函数
	public function weChatSecondSign($newPara,$prepay_id){
		$secondSignArray = array(
			"appid"=>$newPara['appid'],
			"noncestr"=>$newPara['nonce_str'],
			"package"=>"Sign=WXPay",
			"prepayid"=>$prepay_id,
			"partnerid"=>$newPara['mch_id'],
			"timestamp"=>$newPara['timeStamp'],
		);
		$stringA = $this->getSignContent($secondSignArray);
		$stringSignTemp=$stringA."&key=T0YXPJWQQ1ggVOe1OeBViAjxYykceE2b";
		return strtoupper(MD5($stringSignTemp));
	}

	public function getSignContent($newPara){
		$string = "";
		$i = 1;
		ksort($newPara);
		foreach($newPara as $key=>$val){
			if($i==1){
				$string .= $key."=".$val;
			}else{
				$string .= "&".$key."=".$val;
			}
			$i++;
		}
		return $string;
	}	
	public function generate_password( $length = 8 ) { 
		// 密码字符集，可任意添加你需要的字符 
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; 
		$password = ''; 
		for ( $i = 0; $i < $length; $i++ ) { 
			$password .= $chars[ mt_rand(0, strlen($chars) - 1) ]; 
		} 
		return $password; 
	} 	
}
