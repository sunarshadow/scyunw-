<?php
namespace app\common\model;

use think\Model;
use think\Db;
use think\Session;
class Wechatred extends Model
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


	//发送红包，参数：array()
	//红包金额，红包类型，红包详情，用户ID，推荐人手机
	public function sendred($arr){
		$redarray = array("注册奖励","推荐红包","消费奖励","认证奖励","生日红包");
		$user = model("User")->where("id",$arr["uid"])->find();
		$post["ordernum"] = date("YmdHis").mt_rand(10000,99999);//订单号
		$post["money"] = $arr["money"];//红包金额
		$post["redtype"] = $arr["redtype"];//红包类型
		$post["content"] = $arr["content"];//红包详情
		$post["uid"] = $arr["uid"];//用户ID
		$post["username"] = $user["username"];//用户名
		$post["phone"] = $user["phone"];//用户手机
		$post["comphone"] = $arr["comphone"];//推荐人手机
		$post["addtime"] = date("Y-m-d H:i:s");

		$redname = $redarray[$arr["redtype"]];
		$arr = array();
        $arr['openid'] = $user["wxopenid"];
        $arr['hbname'] = $redname;
        $arr['act_name'] = $redname;
        $arr['body']="您的红包已经成功发送，请注意查收";
		$arr['fee']=0;//$arr["money"];	
		// $result = model("userred")->insert($post);
		$result = $this->sendhongbaoto($arr);
		if($result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
			$result = model("userred")->insert($post);
			if($result){
				return 1;
			}
		}else{
			return $result["err_code_des"];
		}
	}
	
	public function sendhongbaoto($arr){
        //$comm = new Common_util_pub();
        $data['mch_id'] = '1291988301';
        $data['mch_billno'] = '1291988301'.date("Ymd",time()).date("His",time()).rand(1111,9999);
        $data['nonce_str'] = self::createNoncestr();
        $data['re_openid'] = $arr['openid'];
        $data['wxappid'] = 'wxc2f2ea52e8a1170d';
        $data['nick_name'] = $arr['hbname'];
        $data['send_name'] = $arr['hbname'];
        $data['total_amount'] = $arr['fee']*100;
        $data['min_value'] = $arr['fee']*100;
        $data['max_value'] = $arr['fee']*100;
        $data['total_num'] = 1;
        $data['client_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['act_name'] = $arr['act_name'];
        $data['remark'] = '备注一下';
        $data['wishing'] = $arr['body'];
        if(!$data['re_openid']) {
           $rearr['return_msg']='缺少用户openid';
           return $rearr;
        }
        $data['sign'] = self::getSign($data);
        $xml = self::arrayToXml($data);
        //var_dump($xml);
        $url ="https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $re = self::wxHttpsRequestPem($xml,$url);
        $rearr = self::xmlToArray($re);
        return $rearr;
    }
    function trimString($value){
		$ret = null;
		if (null != $value)
		{
			$ret = $value;
			if (strlen($ret) == 0)
			{
			$ret = null;
			}
		}
		return $ret;
    }
	/**
	* 作用：产生随机字符串，不长于32位
	*/
	public function createNoncestr( $length = 32 )
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ ) {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}
	/**
	* 作用：格式化参数，签名过程需要使用
	*/
	function formatBizQueryParaMap($paraMap, $urlencode)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
			if($urlencode)
			{
			$v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0)
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	/**
	* 作用：生成签名
	*/
	public function getSign($Obj){
		foreach ($Obj as $k => $v)
		{
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String."&key="."cptbtptpcptbtptpcptbtptpcptbtptp"; // 商户后台设置的key
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}
	/**
	* 作用：array转xml
	*/
	public function arrayToXml($arr){
		$xml = "<xml>";
		foreach ($arr as $key=>$val)
		{
			if (is_numeric($val))
			{
			$xml.="<".$key.">".$val."</".$key.">";
			}
			else
			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
		}
		$xml.="</xml>";
		return $xml;
	}
	/**
	* 作用：将xml转为array
	*/
	public function xmlToArray($xml){
		//将XML转为array
		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $array_data;
	}
	public function wxHttpsRequestPem( $vars,$url, $second=30,$aHeader=array()){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		//以下两种方式需选择一种
		//第一种方法，cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT,ROOT_PATH.'/redcert/apiclient_cert.pem');
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY,ROOT_PATH.'/redcert/apiclient_key.pem');
		curl_setopt($ch,CURLOPT_CAINFO,'PEM');
		curl_setopt($ch,CURLOPT_CAINFO,ROOT_PATH.'/redcert/rootca.pem');
		//第二种方式，两个文件合成一个.pem文件
		//curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);
			return $data;
		}
		else {
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n";
			curl_close($ch);
			return false;
		}
	}    
}
