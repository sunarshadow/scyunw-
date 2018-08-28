<?php
namespace app\common\model;

use think\Model;
use think\Db;
use think\Session;
use think\Loader;
Loader::import('aop.AopClient', EXTEND_PATH,'.php');
Loader::import('aop.request.AlipayTradeAppPayRequest', EXTEND_PATH,'.php');
class Alipay extends Model
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
	public function alipay(){
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
		
		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
		return htmlspecialchars($response);
	}
}
