<?php
namespace app\api\controller;
use think\Controller;
use app\common\model;
use think\Loader;
Loader::import('aop.AopClient', EXTEND_PATH,'.php');
Loader::import('aop.request.AlipayTradeAppPayRequest', EXTEND_PATH,'.php');
class weChatPay extends Controller {
        //APP支付接口
        public function index(){
            $aop = new \AopClient;
            $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
            $aop->appId = "2088921286184801";
            $aop->rsaPrivateKey = 'MIICXAIBAAKBgQC5Bx/56Cb1tQJ4xKHRPnse65G8X3ZVUnb/6QPGj6Ness/KqZ/2ugaeCdgGm9EMr9bb4LGDqu2wqhPOlOFReB1FP7V7rtOnCtscgfrx3QHw+7+5Z3j3m1B+E5BoFD87TlF3M/Hal73X63bt98zLfxCMmxEeXWeaHJGG8Vg6mPKNWwIDAQABAoGAWLN7tth+ZOhjyPWrBZ7Ic2hHM2EoX3rrJX/VmYkerrVWPDfZQfSoW3coovJr44Mgo8QyFYcJ79l5KV3iLqZAkTNLwM0iG9U0iSq4srwTJnf0dgZ+i6cJERcunffJ5XEcVQc6274VJWdPcuMM7gJ3ZCLgWxrBjHI5cUWclzGF12ECQQDhtk9OwCChHhUTTyo4NQL3e8ouzV4mUnCHfwcrHdzvOv8f3sP8FPOm8QOYy5W3dYmtIOcrBqTul3EuCgzCwlhzAkEA0ds5A5pnA/bk/N/Z7AhwkwfNCkDn4O8PKJCDfQFBNYBQH4xTVkdcqWQMzSMhagUtcc4CeApdfqDueuKmHpGFeQJBAIaFVED4Crpbpm3qqZv0JD+BXJ+GME2gpoZqs8gDtVAzFihVaPLNPeXKEL244BMGDzbKvFuNSzETuxWYUcCleXcCQCCYHxPbMHLiLxDDp/JMIUgE5yjXiexa1Qzk3TdWMY2gv1EXF36IGPFKU96svSzdXwCVEbcZTQo07rVxbIHE8mkCQFuz93avR4LKdGbKqATvd5Spm7Z8K0DHye7cMJdis4OrdrLi+0AY5RAgn4QIu1gjoiUSP536jIWnbEve9PWWxj4=' ;
            $aop->format = "json";
            $aop->charset = "UTF-8";
            $aop->signType = "RSA2";
            $aop->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';//对应填写
            //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
            $request = new \AlipayTradeAppPayRequest();
            //SDK已经封装掉了公共参数，这里只需要传入业务参数
            
            //********注意*************************下面除了body描述不是必填，其他必须有，否则失败
            $bizcontent = json_encode(array(
                        'body'=>'我是测试数据',
            
                        'subject' => 'App支付测试',//支付的标题，
            
                        'out_trade_no' => '20170125test11',//支付宝订单号必须是唯一的，不能在支付宝再次使用，必须重新生成，哪怕是同一个订单，不能重复。否则二次支付时候会失败，订单号可以在自己订单那里保持一致，但支付宝那里必须要唯一，具体处理自己操作！
            
                        'timeout_express' => '30m',//過期時間（分钟）
            
                        'total_amount' => '0.01',//金額最好能要保留小数点后两位数
            
                        'product_code' => 'QUICK_MSECURITY_PAY'
                    ));
            
            
            $request->setNotifyUrl("商户外网可以访问的异步地址");//你在应用那里设置的异步回调地址
            
            $request->setBizContent($bizcontent);
            
            //这里和普通的接口调用不同，使用的是sdkExecute
            $response = $aop->sdkExecute($request);
            print_r($response);
            
            //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
            // echo htmlspecialchars($response);
        }
        public function wxpay(){
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
            $newPara["nonce_str"] = "1add1a30ac87aa2db72f57a2375d8fec";
            //商品描述
            $newPara["body"] = "APP支付测试";
            //商户订单号,这里是商户自己的内部的订单号
            $newPara["out_trade_no"] = "1415659992";
            //总金额
            $newPara["total_fee"] = 1;
            //终端IP
            $newPara["spbill_create_ip"] = $_SERVER["REMOTE_ADDR"];
            //通知地址，注意，这里的url里面不要加参数
            $newPara["notify_url"] = "http://127.0.0.1/api/Wechatpay/";
            //交易类型
            $newPara["trade_type"] = "APP";
            //第一次签名
            $newPara["sign"] = $this->produceWeChatSign($newPara);
            //把数组转化成xml格式
            $xmlData = $this->getWeChatXML($newPara);
            //利用PHP的CURL包，将数据传给微信统一下单接口，返回正常的prepay_id
            $get_data = $this->sendPrePayCurl($xmlData);
            echo $get_data['return_code'];
            //返回的结果进行判断。
            if($get_data['return_code'] == "SUCCESS" && $get_data['result_code'] == "SUCCESS"){
                //根据微信支付返回的结果进行二次签名
                //二次签名所需的随机字符串
                $newPara["nonce_str"] = "5K8264ILTKCH16CQ2502SI8ZNMTM67VS";
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
                return json_encode($json);
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
            $data = curl_exec($curl);
            if (curl_errno($curl)) {
                print curl_error($curl);
            }
            curl_close($curl);
            $data = str_replace("&amp;lt;","<",$data);
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
}