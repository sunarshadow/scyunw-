<?php
/**
 * User: timevale
 * Date: 2016/12/8
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
use tech\core\Upload;


//实例化
$esign = null;
try {
    $esign = new eSign();

} catch (Exception $e) {
    echo 'init error :', $e->getMessage();
    exit;
}


//添加个人账号
//addPersonAccount();

//更新个人账号
//updatePsersonAccount();

//添加企业账号
addOrgAccount();

//更新企业账号
//updateOrgAccount();

//添加模板印章
//addOrgTemplateSeal();


//添加事件证书
//addEventCertId();

//事件签署
//eventSignPDF();

//文本签署
//signDataHash();

//获取签署详情
//getSignDetail();

//公证存证
//saveSignedFile('836759510973435910', 1);

//平台自身签署
//selfSignPDF();

//平台用户签署
//userSignPDF();

//注销账号
//delUserAccount();

//根据证件号、证件类型获取用户详情
//getAccountInfo();

function getAccountInfo() {

    global $esign;
    $idNo = '110102199901010113';
    $ret = $esign->getAccountInfoByIdNo($idNo, 11);
    print_r($ret);
}


function addPersonAccount()
{
    global $esign;

    if ($esign == null) {
        echo '初始化失败';
        exit;
    }
    $mobile = '13588888888';
    $name = '个人测试';
    $idNo = '110102199901010113';
    $ret = $esign->addPersonAccount($mobile,
        $name,
        $idNo,
        $personarea = PersonArea::MAINLAND,
        $email = '',
        $organ = '',
        $title = '',
        $address = ''
    );
    print_r($ret);
    return $ret;
}

function updatePsersonAccount()
{
    global $esign;
    $accountId = 'DDF39879590F407BA027D8834634800F';
    $name = '啦啦99999';
    $modifyArray = array(
        'mobile' => '13666666666',
        'email' => '',
        'title' => '222',
        'address' => '',
        'organ' => NULL,
        'name' => $name
    );
    $ret = $esign->upatePersonAccount($accountId, $modifyArray);
    print_r($ret);
    return $ret;
}

function delUserAccount()
{
    global $esign;
    $r = addPersonAccount();
    $accountId = $r['accountId'];
    $res = $esign->delUserAccount($accountId);
    var_dump($res);
}

function addOrgAccount()
{
    global $esign;

    $mobile = '13111111111';
    $name = '企业测试';
    $organType = '0';
    $email = '';
    $organCode = '814187118';
    $regType = OrganRegType::NORMAL;
    $legalArea = PersonArea::MAINLAND;
    $userType = UserType::USER_AGENT;
    $agentName = '李四';
    $agentIdNo = '360730198902261416';

    $res = $esign->addOrganizeAccount($mobile,
        $name,
        $organCode,
        $regType ,
        $email,
        $organType,
        $legalArea ,
        $userType ,
        $agentName ,
        $agentIdNo ,
        $legalName = '',
        $legalIdNo = '',
        $address = '',
        $scope = '');
    print_r($res);
}

function updateOrgAccount()
{
    global $esign;
    $accountId = 'FE3098AF3C2F452BB17A2014731BD8F7';
    //需要修改的字段集
    $modifyArray = array (
        "email" => NULL,  // '' 或 NULL 表示清空改字段
        "mobile" => '13511111111',
        //"name" => '企业测试', //不修改
        //"organType" => '0', //0-普通企业  不修改
        "userType" => UserType::USER_LEGAL, //1-代理人注册，2-法人注册；0-默认
        "agentIdNo" => '', //代理人身份证号 userType = 1 此项不能为空
        "agentName" => '', //代理人姓名 userType = 1 此项不能为空
        "legalIdNo" => '360730198902261416', //法人身份证号  userType = 2 此项不能为空
        "legalName" => '张三',//法人身份证号  userType = 2 此项不能为空
        "legalArea" => NULL //用户归属地 0-大陆
    );
    $res = $esign->updateOrganizeAccount($accountId, $modifyArray);
    print_r($res);
}


function addPersonTemplateSeal()
{
    global $esign;
    $accountId = '3E12C4BE4AA248218144E5F305547D20';

    $ret = $esign->addTemplateSeal(
        $accountId,
        $templateType = PersonTemplateType::SQUARE,
        $color = SealColor::RED
    );
    print_r($ret);
    return $ret;
}


//企业模板印章，返回印章imgbase64
function addOrgTemplateSeal()
{
    global $esign;

    $accountId = '7816E92F75DC4F848BDADD694267FCBC';
    $ret = $esign->addTemplateSeal(
        $accountId,
        $templateType = OrganizeTemplateType::OVAL,
        $color = SealColor::BLUE,
        $hText = '合同专用',
        $qText = '测试章'
    );
    print_r($ret);
    return $ret;
}


function signDataHash()
{
    global $esign;
    $data = '123456789987777';
    $accountId = 'FE3098AF3C2F452BB17A2014731BD8F7';
    $res = $esign->signDataHash($data, $accountId);
    print_r($res);
}

function localVerifyText()
{
    global $esign;
    $srcData = '123456';
    $signResult = "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\n";
    $res = $esign->localVerifyText($srcData, $signResult);
    print_r($res);
}


//事件证书签署
function eventSignPDF()
{
    global $esign;

    //印章模板
    //$sealData = '';
    $sealImg = file_get_contents('3.png');
    $sealData = base64_encode($sealImg);

    //创建事件证书，获取证书ID
    $cert = addEventCertId();
    if ($cert['errCode'] !== 0) {
        echo "创建事件证书失败";
        return;
    }
    $certId = $cert['certId'];

    $signType = SignType::SINGLE;
    $signPos = array(
        'posPage' => 1,
        'posX' =>  100,
        'posY' => 100,
        'key' =>  '',
        'width' => ''
    );
    $signFile = array(
        'srcPdfFile' => 'E:\test.pdf',
        'dstPdfFile' => 'E:\3-dst.pdf',
        'fileName' => '',
        'ownerPassword' => ''
    );
    $res = $esign->eventSignPDF($signFile, $signPos, $signType, $certId, $sealData, $stream = true);
    print_r(Util::jsonEncode($res));
    if (isset($res['errCode']) && $res['errCode'] !==0) {
        echo '事件证书签署失败';
        return;
    }

    $signServiceId = $res['signServiceId'];
    $res = $esign->getSignDetail($signServiceId);
    print_r($res);
    //$esign->selfSignPDF();
}
//添加事件证书
function addEventCertId()
{
    global $esign;
    $content = '1111111111';
    $objects = array(
        array('name' => '参与者1', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '111111111111111111'),
        array('name' => '参与者2', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '222222222222222222'),
        array('name' => '参与者3', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '333333333333333333')
    );
    //$objects = array();
    $a = $esign->addEventCert($content, $objects);
    print_r(Util::jsonEncode($a));
    //var_dump(json_encode($a, JSON_UNESCAPED_UNICODE));
    return $a;
}
//查询事件证书详情
function getSignDetail()
{
    global $esign;
    $signServiceId = '829215267337818119';
    $res = $esign->getSignDetail($signServiceId);
    print_r(Util::jsonEncode($res));
}


//userSignPDF();平台用户签
function userSignPDF()
{
    global $esign;

    //通过调用印章模板、获取印章数据
    //$seal = addOrgTemplateSeal();
    //$sealData = $seal['imageBase64'];

    //通过印章图片，获取
    $sealImg = file_get_contents('3.png');
    $sealData = base64_encode($sealImg);

    //$sealData = '' //允许印章图片为空
    $accountId = '5BBE5EC93D65455A9AE06D488EAE03FE';
    $signType = SignType::SINGLE;
    $signPos = array(
        'posPage' => 1,
        'posX' =>  100,
        'posY' => 100,
        'key' =>  '',
        'width' => ''
    );
    $signFile = array(
        'srcPdfFile' => 'E:\测试.pdf',
        'dstPdfFile' => 'E:\3-dst.pdf',
        'fileName' => '',
        'ownerPassword' => ''
    );
    //$mobile = '13588366603';
    //$code = '358237';
    //$res = $esign->userSafeMobileSignPDF($accountId, $signFile, $signPos, $signType, $sealData,$mobile, $code, $stream = false);
    $res = $esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = false);
    var_dump($res);
    //$esign->selfSignPDF();
}

//平台自身签署
function selfSignPDF()
{
    global $esign;

    $sealId = '0';
    $signType = SignType::SINGLE;
    $signPos = array(
        'posPage' => 1,
        'posX' =>  100,
        'posY' => 100,
        'key' =>  '',
        'width' => '120'
    );
    $signFile = array(
        'srcPdfFile' => 'E:\test.pdf',
        'dstPdfFile' => 'E:\3-dst.pdf',
        'fileName' => '3.pdf',
        'ownerPassword' => ''
    );
    $res = $esign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = false);
    var_dump($res);
    //$esign->selfSignPDF();
}

//文档验签
function fileVerify()
{
    global $esign;
    $filePath = 'E:\3-dst.pdf';
    $res = $esign->fileVerify($filePath, true);
    var_dump($res);
}


