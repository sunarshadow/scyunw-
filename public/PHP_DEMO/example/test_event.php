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

//添加事件证书
//addEventCertId();

//事件签署
 eventSignPDF();


//获取签署详情
//getSignDetail($signServiceId);



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
echo $certId;
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
        'dstPdfFile' => 'E:\test-dst.pdf',
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
    echo $signServiceId;
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
        array('name' => '参与者3', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '333333333333333333'),
        array('name' => '参与者5', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '333333333333333313'),
        array('name' => '参与者4', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '444444444444444444')
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