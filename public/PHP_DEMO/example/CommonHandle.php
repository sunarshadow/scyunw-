<?php
header("Content-type: text/html; charset=utf-8"); //设置输出编码格式


set_exception_handler(function ($e) {
    file_put_contents(
        __DIR__ . '/exception.log',
        date('Y-m-d H:i:s') . ' - ' . $e . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );
});


include("../EAPI/eSignOpenAPI.php");

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
$sign = null;
try {
    $sign = new eSign();
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}


$action = $_GET["action"];
switch ($action) {
    case 'init':
        init();
        break;
    case 'addPerson':
        addPerson();
        break;
    case 'addOrganize':
        addOrganize();
        break;
    case 'addTemplateSeal':
        addTemplateSeal();
        break;
    case 'addFileSeal':
        addFileSeal();
        break;
    case 'userSignPDF':
        userSignPDF();
        break;
    case 'selfSignPDF':
        selfSignPDF();
        break;
    default:
        break;
}

/**
 * 初始化和登录
 */
function init()
{
    global $sign;//声明引用全局变量

    $iRet = $sign->init();
    if (0 === $iRet) {
        $array = array(
            "errCode" => 0,
            "msg" => "初始化成功",
            "errShow" => true
        );
        echo Util::jsonEncode($array);
    }
}

/**
 * 添加个人用户
 */
function addPerson()
{
    global $sign;//声明引用全局变量

    $mobile = $_POST['mobile'];
    $name = $_POST['name'];
    $idNo = $_POST['id'];
    $personarea = $_POST['area'];
    $email = !empty($_POST['email']) ? $_POST['email'] : '';
    $organ = !empty($_POST['organ']) ? $_POST['organ'] : '';
    $title = !empty($_POST['title']) ? $_POST['title'] : '';
    $address = !empty($_POST['address']) ? $_POST['address'] : '';

    $ret = $sign->addPersonAccount(
        $mobile,
        $name,
        $idNo,
        $personarea,
        $email,
        $organ,
        $title,
        $address
    );

    echo Util::jsonEncode($ret);
}

/**
 * 添加企业用户
 */
function addOrganize()
{
    global $sign;//声明引用全局变量

    $ret = $sign->addOrganizeAccount(
        $_POST['mobile'],
        $_POST['name'],
        $_POST['organCode'],
        $_POST['regType'] ,
        $_POST['email'],
        $_POST['organType'],
        $_POST['legalArea'] ,
        $_POST['userType'] ,
        $_POST['agentName'] ,
        $_POST['agentIdNo'] ,
        $_POST['legalName'],
        $_POST['legalIdNo'],
        $address = '',
        $scope = '');
    echo Util::jsonEncode($ret);
}

/**
 * 新建模版印章
 */
function addTemplateSeal()
{
    global $sign;//声明引用全局变量

    $ret = $sign->addTemplateSeal(
        $_POST['accountId'],
        $_POST['templateType'],
        $_POST['color'],
        $_POST['hText'],
        $_POST['qText']
    );
    echo Util::jsonEncode($ret);
}

/**
 * 添加手绘印章
 */
function addFileSeal()
{
    global $sign;//声明引用全局变量
    $imgB64 = $_POST['sealData'];
    $imgB64 = substr($imgB64, strpos($imgB64, ',') + 1);
    //$ret = $sign->addFileSeal($_POST['accountId'], $imgB64, $_POST['color']);
    $ret['errCode'] = 0;
    $ret['sealData'] = $imgB64;
    echo Util::jsonEncode($ret);
}

/**
 * 平台用户签署
 */
function userSignPDF()
{
    global $sign;//声明引用全局变量

    $accountId = $_POST['accountId'];
    $signType = $_POST['signType'];
    $signPos = array(
        'posPage' => !empty($_POST['posPage']) ? $_POST['posPage'] : 1,
        'posX' =>  $_POST['posX'],
        'posY' => $_POST['posY'],
        'key' =>  $_POST['key'],
        'width' => 0,
		'isQrcodeSign'  => isset($_POST['isQrcodeSign']) && $_POST['isQrcodeSign'] == "false" ? false : true
    );
    $signFile = array(
        'srcPdfFile' => $_POST['srcFile'],
        'dstPdfFile' => $_POST['dstFile'],
        'fileName' => $_POST['fileName'],
        'ownerPassword' => ''
    );
    $sealData = $_POST['sealData'];

    $ret = $sign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
    echo Util::jsonEncode($ret);
}

/**
 * 平台自身签署
 */
function selfSignPDF()
{
    global $sign;//声明引用全局变量

    $sealId = $_POST['sealId'];
    $signType = $_POST['signType'];
    $signPos = array(
        'posPage' => $_POST['posPage'],
        'posX' =>  $_POST['posX'],
        'posY' => $_POST['posY'],
        'key' =>  $_POST['key'],
        'width' => 0,
		'isQrcodeSign'  => isset($_POST['isQrcodeSign']) && $_POST['isQrcodeSign'] == "false" ? false : true
    );
    $signFile = array(
        'srcPdfFile' => $_POST['srcFile'],
        'dstPdfFile' => $_POST['dstFile'],
        'fileName' => $_POST['fileName'],
        'ownerPassword' => ''
    );
    $ret = $sign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = true);
    echo Util::jsonEncode($ret);
}

/**
 * 文档保全
 */
function saveSignedFile()
{
    global $sign;//声明引用全局变量

    $docFilePath = $_POST['docFilePath'];
    $docName = $_POST['docName'];
    $signServiceId = $_POST['signServiceId'];
    //$signServiceId = "829215267337818119";
    $saveRet = $sign->saveSignedFile($docFilePath, $docName, $signServiceId, $storeType = \tech\constants\StoreType::ESIGN_STORE);

    echo Util::jsonEncode($saveRet);
}
