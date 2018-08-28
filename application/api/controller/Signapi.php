<?php
namespace app\api\controller;
use app\common\model;
use think\Session;
use think\Loader;
Loader::import('EAPI.eSignOpenAPI', EXTEND_PATH,'.php');
use tech\core\eSign;
use tech\constants\PersonArea;
use tech\constants\PersonTemplateType;
use tech\constants\OrganizeTemplateType;
use tech\constants\SealColor;
use tech\constants\UserType;
use tech\constants\OrganRegType;
use tech\constants\SignType;
use tech\constants\LicenseType;
use tech\core\Util;

class Signapi extends Common
{
    //手签接口
    

    protected   $sign;
    public function _initialize()
    {
        header("Content-type: text/html; charset=utf-8"); //设置输出编码格式  
        set_exception_handler(function ($e) {
            file_put_contents(
                __DIR__ . '/exception.log',
                date('Y-m-d H:i:s') . ' - ' . $e . PHP_EOL . PHP_EOL,
                FILE_APPEND
            );
        });
        //实例化
        try {
            $this->sign = new eSign();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }  
    //获取全部逻辑  
    public function index(){
        $sign = $this->sign;
        $iRet = $sign->init();
        if($iRet === 0){
            $order = model("userorder")->getbyid(input("post.orderid"))->toArray();
            $mobile = $order['apply_phone'];
            $name = $order['car_name'];
            $idNo = $order['id_code'];
            $personarea = input("post.area")?input("post.area"):0;
            $temp = explode("_",$order["rs"]);//获取保单编号
            $orderno = $temp[0].hexdec($temp[1]);//获取保单时间         
            
            $ret = $sign->getAccountInfoByIdNo($idNo, 11);
            $accountId = $ret["accountInfo"]["accountUid"];
            if($ret["errCode"]>0){
                $ret = $sign->addPersonAccount(
                    $mobile,
                    $name,
                    $idNo,
                    $personarea
                );   
                $accountId = $ret["accountId"];
            }

            $imgB64 = input("post.sealData");
            $imgB64 = substr($imgB64, strpos($imgB64, ',') + 1);
            $ret['errCode'] = 0;
            $sealData = $imgB64;
    
 
            $signType = "Single";
            $signPos = array(
                'posPage' => !empty($_POST['posPage']) ? $_POST['posPage'] : 3,
                'posX' =>  400,
                'posY' => 400,
                'key' =>  "",
                'width' => 0,
                'isQrcodeSign'  => isset($_POST['isQrcodeSign']) && $_POST['isQrcodeSign'] == "false" ? false : true
            );
            $temppath = ROOT_PATH . 'public' . DS . 'uploads/contract/'.$orderno.'.pdf';
            $temppath = str_replace("\\","/",$temppath);
            $temppath_dst = ROOT_PATH . 'public' . DS . 'uploads/contract/'.$orderno.'-dst.pdf';
            $temppath_dst = str_replace("\\","/",$temppath_dst);
            $signFile = array(
                'srcPdfFile' => $temppath,
                'dstPdfFile' => $temppath_dst,
                'fileName' => "test",
                'ownerPassword' => ''
            );
			
		
            $ret = $sign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
            $update = array("signtime"=>date("Y-m-d H:i:s"));
            $result = model("order")->where('rs',$order["rs"])->update($update);
			model("Base")->CreateUserLog("用户手签","用户".$order["phone"]."]执行手签操作，订单号：[".$order["rs"]."]");
            echo Util::jsonEncode($ret);           
        }
    }

    public function create(){
        $sign = $this->sign;
        
        $order = model("userorder")->getbyid(input("post.orderid"))->toArray();
        $temp = explode("_",$order["rs"]);//获取保单编号
        $orderno = $temp[0].hexdec($temp[1]);//获取保单时间         
        $signType = "Single";
        $sealId = 0;
        $signPos = array(
            'posPage' => !empty($_POST['posPage']) ? $_POST['posPage'] : 3,
            'posX' =>  200,
            'posY' => 400,
            'key' =>  "",
            'width' => 0,
            'isQrcodeSign'  => isset($_POST['isQrcodeSign']) && $_POST['isQrcodeSign'] == "false" ? false : true
        );
        $temppath_dst = ROOT_PATH . 'public' . DS . 'uploads/contract/'.$orderno.'-dst.pdf';
        $temppath_dst = str_replace("\\","/",$temppath_dst);
        $temppath_final = ROOT_PATH . 'public' . DS . 'uploads/contract/'.$orderno.'-final.pdf';
        $temppath_final = str_replace("\\","/",$temppath_final);
        $signFile = array(
            'srcPdfFile' => $temppath_dst,
            'dstPdfFile' => $temppath_final,
            'fileName' => "test",
            'ownerPassword' => ''
        );
        $ret = $sign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = true);
        if(!$ret["errCode"]>0){
            $update = array("fqstat"=>4,"submittime"=>date("Y-m-d H:i:s"));
            $result = model("order")->where('rs',$order["rs"])->update($update);
        }
        echo Util::jsonEncode($ret);   
    }

	public function Topdf()
	{
        $order = model("userorder")->getbyid(input("post.orderid"))->toArray(); 
		$info = model("order")->getbyrs($order["rs"])->toArray();
		$orderinstall = model("orderinstall")->where("rs",$info["rs"])->select();
		// getbyrs($info["rs"])->toArray();
		$temp = explode("_",$info["rs"]);//获取保单编号
		$info["ordernum"] = $temp[0].hexdec($temp[1]);//获取保单时间
		

		header("Content-type: application/json; charset=utf-8");		
		$html = '
		<div style="font-size:12px;padding:5px;">
			<div style="background-color: #FFFFFF; border:#dfe6ea solid 1px; padding: 5px 12px;">
				<div style="width: 98%;text-align: right;">协议编号：<span>' . $info["ordernum"] . '</span></div>
				<h2 align="center">借款协议</h2><br/>
				<div style="text-align: left;font-weight: 600;">甲方（出借人）：<strong>四川云网文化传播有限公司</strong></div>
				<div style="text-align: left;font-weight: 600;">执照号：<strong>91350502M0000WC393</strong></div><br/>
				<div>
				<p style="text-align: left;font-weight: 600;">乙方（借款人）：<span>' . $info["ordernum"] . '</span></p>
				<p style="text-align: left;font-weight: 600;">云车险用户名：<span>' . $info["username"] . '</span></p>
				<p style="text-align: left;font-weight: 600;">手机号码：<span>' . $info["phone"] . '</span></p>
				<table border="1" style="margin: 0px auto; border-collapse: collapse; border: 1px solid rgb(0, 0, 0); width: 80%; ">
				<tr>
					<td style="text-align:center;"> 期数</td>
					<td style="text-align:center;"> 还款日期(实际以首次付款当天为准)</td>
					<td style="text-align:center;">期初余额</td>
					<td style="text-align:center;"> 还款额</td>
					<td style="text-align:center;"> 期末余额</td>
				</tr>
				';
				// print_r($orderinstall);
				foreach($orderinstall as $key=>$val){
					$html .='<tr>
							<td style="text-align:center;">'.$val["qishu"].'</td>
							<td style="text-align:right;padding-right:10px">'.date("Y-m-d",$val["yuqitime"]).'</td>
							<td>￥'.$val["beforemoney"].'</td>
							<td>￥'.$val["money"].'</td>
							<td>￥'.$val["aftermoney"].'</td>
							</tr>
							';
				}
				$html .='
				<tr>
					<td style="text-align:center;">总计</td>
					<td style="text-align:right;padding-right:10px">￥'.$order["order_price"].'</td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
				</table>
				<p>注：因计算中存在四舍五入，最后一期应收本息与之前略有不同</p><br/>
				</div>
	
				<br/><p><strong>鉴于：</strong></p>
				<p>1、甲方方是一家在阆中合法成立并有效存续的有限责任公司，拥有http://loan.scyunw.com/ 网站（以下简称“该网站”）的经营权，提供信用咨询，为交易提供信息服务；</p>
				<p>2、乙方已在该网站注册，并承诺其提供给甲方的信息是完全真实的；</p>
				<p>3、甲方承诺对本协议涉及的借款具有完全的支配能力，是其自有闲散资金，为其合法所得；并承诺其提供给乙方的信息是完全真实的；</p>
				<p>4、乙方有借款需求，甲方亦同意借款，双方有意成立借贷关系；</p>
				<br/>
				<p style="text-align: left;font-weight: 600;">各方经协商一致，于<span>年月日</span>签订如下协议，共同遵照履行：</p>
				<br/>
				<p style="text-align: left;font-weight: 600;"> 第一条 借款基本信息</p>
				<br/>
				<table border="1" style="margin: 0px auto; border-collapse: collapse; border: 1px solid rgb(0, 0, 0); width: 70%; ">
				<tr>
				<td width="20%" style="padding-left:10px"> 借款详细用途</td>
				<td style="padding-left:10px"> 车险分期</td>
				</tr>
				<tr>
				<td style="padding-left:10px">借款本金数额</td>
				<td style="padding-left:10px">￥（各出借人借款本金数额详见本协议文首表格）</td>
				</tr>
				<tr>
				<td style="padding-left:10px"> 月偿还本息数额</td>
				<td style="padding-left:10px">￥0.00（因计算中存在四舍五入，最后一期应还金额与之前可能有所不同，为￥100.00）</td>
				</tr>
				<tr>
				<td style="padding-left:10px"> 还款分期月数</td>
				<td style="padding-left:10px">个月</td>
				</tr>
				<tr>
				<td style="padding-left:10px">还款日</td>
				<td style="padding-left:10px">自年月日起，每月日（24:00前，节假日不顺延）（实际以首次付款当天为准）</td>
				</tr>
				<tr>
				<td style="padding-left:10px"> 借款期限</td>
				<td style="padding-left:10px">个月，年月日起，至年月日止</td>
				</tr>
				</table>
				<br/>
				<div>
				<p style="text-align: left;font-weight: 600;">第二条 各方权利和义务</p>				
				<p style="text-align: left;font-weight: 600;"><u>甲方的权利和义务</u></p>
				<p>1、 如乙方实际还款金额少于本协议约定的本金，甲方有权力要求乙方按照本协议文首约定的还款。</p>
				<p>2、 甲方应确保其提供信息和资料的真实性，不得提供虚假信息或隐瞒重要事实。 </p>
				<p style="text-align: left;font-weight: 600;"><u>乙方权利和义务</u></p>
				<p>1、 乙方必须按期足额向甲方偿还本金。 </p>
				<p>2、 乙方承诺所借款项不用于任何违法用途。 </p>
				<p>3、 乙方应确保其提供的信息和资料的真实性，不得提供虚假信息或隐瞒重要事实。 </p>
				<p>4、 乙方不得将本协议项下的任何权利义务转让给任何其他方。 </p>
				<p>5、甲方有权对乙方进行关于本协议借款的违约提醒及催收工作，包括但不限于：电话通知、上门催收提醒、发律师函、对乙方提起诉讼等</p>
				<p style="text-align: left;font-weight: 600;">&nbsp;</p>
				<p style="text-align: left;font-weight: 600;">第三条 违约责任</p>
				<p>1、协议各方均应严格履行合同义务，非经各方协商一致或依照本协议约定，任何一方不得解除本协议。 </p>
				<p>2、任何一方违约，违约方应承担因违约使得其他各方产生的费用和损失，包括但不限于调查、诉讼费、律师费等，应由违约方承担。如违约方为乙方的，甲方有权立即解除本协议，并要求乙方立即偿还未偿还的本金、违约金。</p>
				<p>3、乙方的每期还款均应按照如下顺序清偿：</p>
				<p>（1）根据本协议产生的其他全部费用；</p>
				<p>（2）拖欠的本金； </p>
				<p>（3）正常的本金；</p>
				<p>4、乙方应严格履行还款义务，如乙方逾期还款，则应按照下述条款向甲方支付逾期罚款，自逾期开始之后，每次每笔收取100元罚款。</p>
				<table border="1" style=" margin-left: 10px; border-collapse: collapse; border: 1px solid rgb(0, 0, 0); width: 20%; ">
				<tr>
				<td style="padding-left:10px">逾期天数</td>
				<td style="padding-left:10px">自逾期开始之一</td>
				</tr>
				<tr>
				<td style="padding-left:10px">罚息利率</td>
				<td style="padding-left:10px">100元/次/笔</td>
				</tr>
				</table>
				<p>5、乙方上述借款仅用于购买车险使用，甲方作为保单投保人，乙方作为被保险人和受益人，且甲方不对投保车辆所产生的交通违章、交通事故、交通肇事逃逸等任何法律风险担负责任。</p>
				<p>6、乙方如不能按规定时间还款，甲方将有权利持保单到保险公司退保，所退金额将直接退至甲方帐户内。 </p>
				<br/>
				<p style="text-align: left;font-weight: 600;">第四条 法律及争议解决</p>
				<p>本协议的签订、履行、终止、解释均适用中华人民共和国法律，并由丙方所在地阆中市人民法院管辖。</p>
				<br/>
				<p style="text-align: left;font-weight: 600;">第五条 附则</p>
				<p>1、本协议采用电子文本形式制成，并永久保存在丙方为此设立的专用服务器上备查，各方均认可该形式的协议效力。</p>
				<p>2、本协议自文本最终生成之日生效。</p>
				<p>3、本协议签订之日起至借款全部清偿之日止，乙方或甲方有义务在下列信息变更三日内提供更新后的信息给丙方：本人、本人的家庭联系人及紧急联系人、工作单位、居住地址、住所电话、手机号码、电子邮箱、银行账户的变更。若因任何一方不及时提供上述变更信息而带来的损失或额外费用应由该方承担。</p>
				<p>4、如果本协议中的任何一条或多条违反适用的法律法规，则该条将被视为无效，但该无效条款并不影响本协议其他条款的效力。</p>
				<br/>
				</div>
				<br/>
				<div style="width: 98%;text-align: right;"><p>'.date("Y年m月d日").'</p></div>
				<div style="text-align: center"></div>
			</div>
		</div>';		


		$pdf = model("orderpdfdata")->getbyrs($info["rs"]);
		if(!$pdf){
			$insert["rs"] = $info["rs"];
			$insert["phone"] = $info["phone"];
			$insert["result"] = $html;
			$result = model("orderpdfdata")->insert($insert);
		}		
		Loader::import('tcpdf.tcpdf');
		$pdf = new \tcpdf('A4-L');
		$pdf->setfont('stsongstdlight','', 10);
		$pdf->AddPage();
		$pdf->writeHTML($html, true, false, true, false, '');
		$path = ROOT_PATH . 'public' . DS . 'uploads/contract/'.$info["ordernum"].'.pdf';
		$pdf->Output($path, 'f');
		return $html;
		exit;
		// $pdf->Output('1.pdf', 'D');
		// 在"D"输出方式下，下载下来的1.pdf文件能正常打开并显示 
	}	


}
