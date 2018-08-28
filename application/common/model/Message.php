<?php
namespace app\common\model;

use think\Model;
use think\Db;
use think\Session;
use think\Loader;
Loader::import('appts.autoload');
use JPush\Client as JPush;
class message extends Model
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
	public function getmsg($type,$data){
		$msg = "";
		$tempdate = date("Y-m-d H:i:s");
		$temptomodate = "未生效,暂无到期时间";
		$templateid = "";

		$tempurl = "http://".config('DOMAIN_URL')."/wechat";
		switch($type){
			
			/************************************************************************************************************/
			/* 用户提醒 */
			/************************************************************************************************************/
			
			//用户注册成功时
			case "regsuccess":
				//数据备注->0:手机号
				$msg = $data[0].",欢迎您注册匀享车险,匀享车险为您提供便捷的车险在线服务,客服热线:4001880817";
				break;
			//用户提交询价时
			case "inquery":
				//数据备注->0:手机号,1:姓名,2:车牌,3:订单号,4:保险公司,5:询价订单id
				$msg = $data[1].",您提交的".$data[2]."询价,我们已经收到,我们将尽快为你做出精准报价,请耐心等候";
				$keydata = array($data[3],$data[2],$data[4],$tempdate,"first"=>$msg);
				$tempdata = $this->complatedate($keydata);
				$templateid = "PS1-n1Y30sEY0UeLPg0t8Doj8j2YVJ_Q70t_l6RxmBU";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=buy&id=".$data[5];
				break;
			//用户收到询价时
			case "inquerysuccess":
				//数据备注->0:手机号,1:姓名,2:车牌,3:订单金额,4:订单号,5:保险公司,6:询价订单id
				$msg = $data[1].",你的爱车".$data[2]."保单询价成功,保单总金额".$data[3].",请登录匀享车险APP/微信公众号查看详情!";
				$tempmsg = $data[1].",你的爱车".$data[2]."保单询价成功.";//,保单总金额".$data[3].".";
				$keydata = array($data[4],$data[2],$data[5],$data[3],$temptomodate,"first"=>$tempmsg,"remark"=>"请点击查看详情!");
				$tempdata = $this->complatedate($keydata);
				$templateid = "v__SO12iDBULdaZK2I5Rhsg2yn2CSvWPDfXnk5CFo24";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=buy&id=".$data[6];
				break;
			//用户询价被驳回
			case "inqueryfail":
				//数据备注->0:手机号,1:姓名,2:车牌,3:订单号,4:保险公司,5:驳回理由
				$tempmsg = $data[1].",您提交的".$data[2]."询价,因".$data[5]."被驳回";
				$keydata = array($data[3],$data[2],$data[4],$tempdate,"first"=>$tempmsg,"remark"=>"请点击查看详情!");
				$tempdata = $this->complatedate($keydata);
				$templateid = "PS1-n1Y30sEY0UeLPg0t8Doj8j2YVJ_Q70t_l6RxmBU";
				break;
			//用户分期审核成功
			case "stagesuccess":
				//数据备注->0:手机号,1:姓名,2:车牌,3:分期金额,4:分期期数，5:下单时间,6:询价订单id
				$msg = $data[1]."您好,您的爱车".$data[2]."车险分期业务已通过,请登录匀享车险APP/微信公众号进行下一步操作!";
				$tempmsg = $data[1]."您好,您的爱车".$data[2]."车险分期业务已通过";
				$keydata = array($data[3],$data[4],$data[5],"first"=>$tempmsg,"remark"=>"点击本通知查看分期详情，尽快完成确认！");
				$tempdata = $this->complatedate($keydata);
				$templateid = "i26f7nmYqRw4vkTitEy83uxjh96QVcqs5KLEA_1ISUM";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=auditbystages&id=".$data[6];
				break;
			//用户分期审核失败
			case "stagefail":
				//数据备注->0:手机号,1:姓名,2:车牌,3:分期金额,4:分期期数，5:下单时间,6:询价订单id
				$msg = $data[1]."您好,您的爱车".$data[2]."车险分期业务未通过,请登录匀享车险APP/微信公众号查看详情!";
				$tempmsg = $data[1]."您好,您的爱车".$data[2]."车险分期业务未通过";
				$keydata = array($data[3],$data[4],$data[5],"first"=>$tempmsg,"remark"=>"点击本通知查看分期详情，尽快完成确认！");
				$tempdata = $this->complatedate($keydata);
				$templateid = "i26f7nmYqRw4vkTitEy83uxjh96QVcqs5KLEA_1ISUM";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=auditbystages&id=".$data[6];
				break;
			//用户支付/还款成功后
			case "orderpay":
				//数据备注->0:手机号,1:姓名,2:车牌,3:订单号,4:还款金额,5:询价订单id
				$msg = $data[1]."您好,你的订单已支付,请登录匀享车险APP/微信公众号查看详情";
				$tempmsg = $data[1]."您好,你的订单已支付";
				$keydata = array($data[3],$data[2]."车险",$data[4],"first"=>$tempmsg,"remark"=>"点击本通知查看分期详情，尽快完成确认！");
				$tempdata = $this->complatedate($keydata);
				$templateid = "OBZF0esicYOVk7LhK-1REjpxjVM0TA-XbtljZb53lAk";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=mybystagesdetail&id=".$data[5];
				break;
			//用户保单发货
			case "expresssend":
				//数据备注->0:手机号,1:姓名,2:车牌,3:快递公司,4:快递单号,5:询价订单id
				$msg = $data[1]."您好,您的爱车".$data[2]."保单已通过".$data[3]."发货,快递单号为:".$data[4].",请登录匀享车险APP/微信公众号查看详情!";
				$tempmsg = $data[1]."您好,您的爱车".$data[2]."保单已发货";
				$keydata = array($data[3],$data[4],"first"=>$tempmsg,"remark"=>"请点击查看详情。");
				$tempdata = $this->complatedate($keydata);
				$templateid = "VEtTtx5Nq7ZDVuHHVAA3SzA-tULz54W4BsmDgA-9qJ4";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=mybystagesdetail&id=".$data[5];
				break;
			//用户保单签收
			case "expressget":
				//数据备注->0:手机号,1:姓名,2:车牌
				$msg = $data[1]."您好,您的爱车".$data[2]."保单已成功签收!";
				break;
			//用户分期还款时
			case "stagepay":
				//数据备注->0:手机号,1:姓名,2:车牌
				$msg = $data[1]."您好,您在匀享车险分期订单,明日即将到还款日,请通过微信或者APP登陆进行还款!";
				break;
			//用户逾期还款时
			case "stageyuqi":
				//数据备注->0:手机号,1:姓名,2:待还金额,3:违约金,4:逾期时间,5:天数,6:询价订单id
				$msg = $data[1]."您好,您在匀享车险分期订单,即将到还款日,还款时间:".$data[4].",请通过微信或者APP登陆进行还款!";
				$tempmsg = $data[1]."您好,您的还款日还有".$data[5]."天就要到了！";
				$keydata = array($data[2],$data[3],$data[4],"first"=>$tempmsg,"remark"=>"友情提醒您，按时还款有利于提高你的信用额度哦！");
				$tempdata = $this->complatedate($keydata);
				$templateid = "Ibctnvjdy9nkffSGUXco_yDF3dp-QOebRfBoY51Xi6Q";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=mybystagesdetail&id=".$data[6];
				break;
			//用户逾期催收
			case "yuqinotice":
				//数据备注->0:手机号,1:姓名,2:车牌,3:违约金,4:逾期时间,5:天数
				$msg = $data[1]."您好，您在匀享车险办理".$data[2]."的分期业务,最近还款期为".$data[4].",已逾期".$data[5]."天.请尽快登陆匀享车险平台归还分期金额(包括逾期金额),否则你将承担不必要的法律责任!";
				$tempmsg = $data[1]."您好，您在匀享车险办理".$data[2]."的分期业务,最近还款期为".$data[4].",已逾期".$data[5]."天";
				$keydata = array($data[2],$data[3],$data[4],"first"=>$tempmsg,"remark"=>"请尽快登陆匀享车险平台归还分期金额(包括逾期金额),否则你将承担不必要的法律责任!");
				$tempdata = $this->complatedate($keydata);
				$templateid = "Ibctnvjdy9nkffSGUXco_yDF3dp-QOebRfBoY51Xi6Q";
				break;
			//用户提现结果
			case "tixianresult":
				//数据备注->0:手机号,1:姓名,2:车牌,3:提现结果
				$msg = $data[1]."您好,申请的提现结果".$data[3].",请登录匀享车险APP/公众号进行查看详情!";
				break;
			//用户消费成功后
			case "consume":
				//数据备注->0:手机号,1:姓名,2:车牌,3:商户,4:金额
				$msg = $data[1]."您好,你在".$data[3]."消费金额".$data[4]."元,请知悉!";
				break;
			//用户提交预约时
			case "bespeak":
				//数据备注->0:手机号,1:姓名,2:车牌,3:服务,4:时间,5预约地点,6:id
				$msg = $data[1]."您好,你的爱车".$data[2]."预约的".$data[3].",预约时间为:".$data[4].",请注意查收短信通知!";
				$tempmsg = $data[1]."您好,您的爱车".$data[2]."预约".$data[3]."成功";
				$keydata = array($data[3],$data[4],$data[5],"first"=>$tempmsg,"remark"=>"请点击查看详情。");
				$tempdata = $this->complatedate($keydata);
				$templateid = "HfkGap0gHR1_NhAEPHACoRSbQhmMp2peQOgFT3Mhmpw";
				$tempurl = "http://".config('DOMAIN_URL')."/wechat/?url=agentdetail&id=".$data[6];
				break;
			//用户预约被驳回  zed
			// case "bespeak_error":
			// 	//数据备注->0:手机号,1:姓名,2:车牌,3:服务,4:时间,5预约地点,6驳回理由
			// 	$msg = $data[1]."您好,你的爱车".$data[2]."预约的".$data[3].",预约时间为:".$data[4].",请注意查收短信通知!";
			// 	$tempmsg = $data[1]."您好,您的爱车".$data[2]."预约".$data[3]."失败,驳回原因".$data[6];
			// 	$keydata = array($data[3],$data[4],$data[5],"first"=>$tempmsg,"remark"=>"请点击查看详情。");
			// 	$tempdata = $this->complatedate($keydata);
			// 	$templateid = "HfkGap0gHR1_NhAEPHACoRSbQhmMp2peQOgFT3Mhmpw";
			// 	break;

			/************************************************************************************************************/
			/* 商户提醒 */
			/************************************************************************************************************/

			//商户审核被驳回时 zed
			// case "agent_error":
			// 	//数据备注->0:手机号,1:驳回理由
			// 	$msg = "您的申请入驻匀享车险合作商家被驳回，驳回原因为：".$data[1].",感谢您的支持!如有疑问拨打4001880817";
			// 	break;
			case "agent_check":
				//数据备注->0:手机号
				$msg = "您已成功入驻匀享车险合作商家,感谢您的支持!客服电话:4001880817";
				break;
			//商户提交提现申请提交后
			case "agent_tixian":
				//数据备注->0:手机号,1:提现金额
				$msg = "您申请的金额为:".$data[1]."商户提现平台已受理,待平台审核。如有疑问拨打4001880817";
				break;
			//商户提交提现审核结果
			case "agent_txcheck":
				//数据备注->0:手机号,1:提现金额
				$msg = "您申请的金额为:".$data[1]."商户提现已通过审核,平台将尽快为你提现到您的指定银行卡。如有疑问拨打4001880817";
				break;
			//商户提交提现审核结果 zed
			case "agent_txerror":
				//数据备注->0:手机号,1:提现金额，2：驳回理由
				$msg = "您申请的金额为:".$data[1]."商户提现被驳回,驳回理由为：".$data[2]."。如有疑问拨打4001880817";
				break;
			//商户提交提现审核结果
			case "agent_txsuccess":
				//数据备注->0:手机号,1:提现金额
				$msg = "您申请的金额为:".$data[1]."的商户提现已成功转账,请注意查收!如有疑问拨打4001880817";
				break;
			

			/************************************************************************************************************/
			/* 保险公司提醒 */
			/************************************************************************************************************/	
			//保险公司审核被驳回 zed
			// case "insurer_error":
			// 	//数据备注->0:手机号
			// 	$msg = "您的申请入驻匀享车险合作商家被驳回，驳回原因为：".$data[1].",感谢您的支持!如有疑问拨打4001880817";
			// 	break;
			// //保险公司审核通过时 zed
			// case "insurer_check": 
			// 	//数据备注->0:手机号
			// 	$msg = "您已成功入驻匀享车险合作保险公司,感谢您的支持!客服电话:4001880817";
			// 	break;
			// //保险公司提交提现申请提交后 zed
			// case "insurer_bfxian":
			// 	//数据备注->0:手机号,1:提现金额
			// 	$msg = "您申请的金额为:".$data[1]."商户提现平台已受理,待平台审核。如有疑问拨打4001880817";
			// 	break;
			// //保险公司提交提现审核结果 zed
			// case "insurer_bfcheck":
			// 	//数据备注->0:手机号,1:提现金额
			// 	$msg = "您申请的金额为:".$data[1]."商户提现已通过审核,平台将尽快为你提现到您的指定银行卡。如有疑问拨打4001880817";
			// 	break;
			// //保险公司提交提现审核结果 zed
			// case "insurer_bferror":
			// 	//数据备注->0:手机号,1:提现金额，2：驳回理由
			// 	$msg = "您申请的保费单号为:".$data[1]."的订单被驳回,驳回理由为：".$data[2]."。如有疑问拨打4001880817";
			// 	break;
			// //保险公司提交提现审核结果 zed
			// case "insurer_bfsuccess":
			// 	//数据备注->0:手机号,1:提现金额
			// 	$msg = "您申请的金额为:".$data[1]."的商户提现已成功转账,请注意查收!如有疑问拨打4001880817";
			// 	break;		

		}
		if($templateid){
			$oauth = & load_wechat('Message');
			$user = model("User")->getbyphone($data[0]);
			if($user["wxopenid"]){
				$senddata = array('touser'=>$user["wxopenid"],'template_id'=>$templateid,'url'=>$tempurl,'topcolor'=>'#2D2F2D','data'=>$tempdata);
				$result = $oauth->sendTemplateMessage($senddata);
			}
		}
		// print_r($senddata);
        // Loader::import('wechat.WeiXin');
        // $wxtlp = new \WeiXin; 

        // $res=$wxtlp->send_template_message(urldecode(json_encode($senddata))); 
		// $data=json_decode($res,true); 
		// print_r($data);		
		if($msg){
			$result = model("Base")->msgsend($msg,$data[0]);//发送短信

			$app_key = config('JG_app_key');
			$master_secret = config('JG_master_secret');
			$client = new JPush($app_key, $master_secret);
			$pusher = $client->push();
			$pusher->setPlatform(array('ios', 'android'));
			$pusher->addAlias(array($data[0]));
			// $pusher->setNotificationAlert($msg);
			$pusher->iosNotification($msg, array(
				'badge' => '0'
			));
			$pusher->androidNotification($msg);			
			$pusher->options(array(
				 'apns_production' => true,
			));
			try {
				$pusher->send();
			} catch (\JPush\Exceptions\JPushException $e) {
				// try something else here
				print $e;
			}	
			return $result;		
		}
		
	}
	public function complatedate($data,$colordata=array()){
		$tempdata = array(
			'first' => array(
				'value' => isset($data["first"])?$data["first"]:"",
				'color' => isset($colordata["first"])?$colordata["first"]:"#2D2F2D",
			),
			'keyword1' => array(
				'value' => isset($data["0"])?$data["0"]:"",
				'color' => isset($colordata["0"])?$colordata["0"]:"#2D2F2D",
			),
			'keyword2' => array(
				'value' => isset($data["1"])?$data["1"]:"",
				'color' => isset($colordata["1"])?$colordata["1"]:"#2D2F2D",
				//'color' => '#FF0000'
			),
			'keyword3' => array(
				'value' => isset($data["2"])?$data["2"]:"",
				'color' => isset($colordata["2"])?$colordata["2"]:"#2D2F2D",
				//'color' => '#FF0000'
			),
			'keyword4' => array(
				'value' => isset($data["3"])?$data["3"]:"",
				'color' => isset($colordata["3"])?$colordata["3"]:"#2D2F2D",
				//'color' => '#FF0000'
			),
			'keyword5' => array(
				'value' => isset($data["4"])?$data["4"]:"",
				'color' => isset($colordata["4"])?$colordata["4"]:"#2D2F2D",
				//'color' => '#FF0000'
			),
			'remark' => array(
				'value' => isset($data["remark"])?$data["remark"]:"",
				'color' => isset($colordata["remark"])?$colordata["remark"]:"#2D2F2D",
			)
		);	
		return $tempdata;	
	}
}
