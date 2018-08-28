<?php
/**
 * User: Administrator
 * Date: 2017/2/8
 */
header("Content-type: text/html; charset=utf-8"); //设置输出编码格式

echo 'phpversion ', phpversion(), '\r\n<br/>';

//自动捕获异常
set_exception_handler(function ($e) {
    file_put_contents(
        __DIR__ . '/exception.log',
        date('Y-m-d H:i:s') . ' - ' . $e . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );
});

include("../API/eSignOpenAPI.php");


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

//实例化
$esign = null;
try {
    $esign = new eSign();
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

//短信验证码 发送到指定手机
/*$res = $esign->sendSignCodeToMobile('DDF39879590F407BA027D8834634800F', '13588366603');
var_dump($res);
exit;*/

//平台用户签
//userSignPDF();

//批量签署
//userMultiSignPDF();

//平台自身签署
selfSignPDF();


//短信批量签署
function userMultiSignPDF()
{
    global $esign;
    $accountId = 'DDF39879590F407BA027D8834634800F';

    //通过调用印章模板接口、获取印章数据
    //$seal = addOrgTemplateSeal($accountId);
    //$sealData = $seal['imageBase64'];

    //直接获取印章图片
    $sealImg = file_get_contents('3.png');
    $sealData = base64_encode($sealImg);

    //$sealData = '' //允许印章图片为空

    //待签署文档1
    $fileBean1 = array(
        'srcPdfFile' => 'E:\test1.pdf',
        'dstPdfFile' => 'E:\test1-dst.pdf',
        'fileName' => '',
        'ownerPassword' => ''
    );
    //待签署文档2
    $fileBean2 = array(
        'srcPdfFile' => 'E:\测试.pdf',
        'dstPdfFile' => 'E:\测试-dst.pdf',
        'fileName' => '',
        'ownerPassword' => ''
    );
    //批量签署参数拼装
    $signParams = array(
        0 => array(
            'signType' => SignType::SINGLE,
            'fileBean' => $fileBean1,
            'signPos' => array(
                'posPage' => 1,
                'posX' => 100,
                'posY' => 100,
                'key' => '',
                'width' => ''
            )
        ),
        1 => array(
            'signType' => SignType::KEYWORD,
            'fileBean' => $fileBean2,
            'signPos' => array(
                'posPage' => 0,
                'posX' => 0,
                'posY' => 0,
                'key' => 'DESCRIPTION',
                'width' => ''
            )
        )
    );
    //调用 8.1发送签署短信验证码 $esign->sendSignCode($accountId)， $mobile 为空
    //调用 8.2发送签署短信验证码（指定手机号）$esign->sendSignCodeToMobile($accountId,$mobile)， $mobile 不能为空
    $mobile = '13588366603';
    $code = '126117';
    $res = $esign->userMutilSignPDF($accountId, $signParams, $sealData, $mobile, $code);
    print_r(Util::jsonEncode($res));
}

function userSignPDF()
{
    global $esign;

    //通过调用印章模板、获取印章数据
    //$seal = addOrgTemplateSeal();
    //$sealData = $seal['imageBase64'];

    //通过印章图片，获取
    //$sealImg = file_get_contents('3.png');
    //$sealData = base64_encode($sealImg);
$sealData = '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';
    //$sealData = '' //允许印章图片为空
    $accountId = 'DDF39879590F407BA027D8834634800F';
    $signType = SignType::SINGLE;
    $signPos = array(
        'posPage' => 12,
        'posX' => 400,
        'posY' => 0,
        'key' => '',
        'width' => 159
    );
    $signFile = array(
        'srcPdfFile' => 'E:\pdf\test1-dst.pdf',
        'dstPdfFile' => 'E:\pdf\test1-dst-final.pdf',
        'fileName' => '',
        'ownerPassword' => ''
    );
    //$mobile = '13588366603';
    //$code = '358237';
    //$res = $esign->userSafeMobileSignPDF($accountId, $signFile, $signPos, $signType, $sealData,$mobile, $code, $stream = false);
    $res = $esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
    var_dump($res);
    //$esign->selfSignPDF();
}

//平台自身签署
function selfSignPDF()
{
    global $esign;

    $sealId = '0';
    $signType = SignType::KEYWORD;
    $signPos = array(
        'posPage' => 1,
        'posX' =>  100,
        'posY' => '',
        'key' =>  '甲方（盖章）',
        'width' => '120',
        'cacellingSign' => true
    );
    $signFile = array(
        'srcPdfFile' => 'E:\pdf\13958ddc26324a3492f9264f351bde93.pdf',
        'dstPdfFile' => 'E:\pdf\13958ddc26324a3492f9264f351bde93.pdf-final.pdf',
        'fileName' => '13958ddc26324a3492f9264f351bde93.pdf',
        'ownerPassword' => ''
    );
    $res = $esign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = true);
    var_dump($res);
    //$esign->selfSignPDF();
}

function addOrgTemplateSeal($accountId)
{
    global $esign;

    $ret = $esign->addTemplateSeal(
        $accountId,
        $templateType = OrganizeTemplateType::OVAL,
        $color = SealColor::RED,
        $hText = '合同专用',
        $qText = '测试章'
    );
    print_r($ret);
    return $ret;
}


