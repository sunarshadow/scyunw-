<?php
/**
 * Des: test PHP >= 5.3 需支持命名空间
 * User: wanglf
 * Date: 2017/1/12
 */
header("Content-type: text/html; charset=utf-8"); //设置输出编码格式

//自动捕获异常
set_exception_handler(function ($e) {
    file_put_contents(
        __DIR__ . '/exception.log',
        date('Y-m-d H:i:s') . ' - ' . $e . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );
});

$e = new test_class();
$e->addPersonAccount();



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

class test_class
{
    private $esign = null;

    public function __construct()
    {
        try {
            $this->esign = new eSign();
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    //添加事件证书
    function addEventCertId()
    {
        $content = '1111111111';
        $objects = [
            ['name' => '参与者1', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '111111111111111111'],
            ['name' => '参与者2', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '222222222222222222'],
            ['name' => '参与者3', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '333333333333333333'],
            ['name' => '参与者4', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '444444444444444444']
        ];
        $a = $this->esign->addEventCert($content, $objects);
        print_r(Util::jsonEncode($a));
        return $a;
    }

    function addPersonAccount()
    {
        $mobile = '13588888888';
        $name = '个人测试';
        $idNo = '360730198902261416';
        $ret = $this->esign->addPersonAccount($mobile,
            $name,
            $idNo,
            $personarea = PersonArea::MAINLAND,
            $email = '',
            $organ = '',
            $title = '',
            $address = ''
        );
        print_r(Util::jsonEncode($ret));
        return $ret;
    }

    function updatePsersonAccount()
    {
        $accountId = 'BED4CAC8FB554359AC839EFFC1476EA5';
        $name = '个人测试22222';
        $modifyArray = array(
            'mobile' => '13666666666',
            'email' => '',
            'title' => '222',
            'address' => '',
            'organ' => NULL,
            'name' => $name
        );
        $ret = $this->esign->upatePersonAccount($accountId, $modifyArray);
        print_r($ret);
        return $ret;
    }

    function delUserAccount()
    {
        $r = addPersonAccount();
        $accountId = $r['accountId'];
        $res = $this->esign->delUserAccount($accountId);
        var_dump($res);
    }

    function addOrgAccount()
    {
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

        $res = $this->esign->addOrganizeAccount($mobile,
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

        $accountId = 'FE3098AF3C2F452BB17A2014731BD8F7';
        //需要修改的字段集
        $modifyArray = array(
            "email" => NULL,  // '' 或 NULL 表示清空改字段
            "mobile" => '13511111111',
            //"name" => '企业测试', //不修改
            //"organType" => '0', //0-普通企业  不修改
            "userType" => UserType::USER_LEGAL, //1-代理人注册，2-法人注册
            "agentIdNo" => '', //代理人身份证号 userType = 1 此项不能为空
            "agentName" => '', //代理人姓名 userType = 1 此项不能为空
            "legalIdNo" => '360730198902261416', //法人身份证号  userType = 2 此项不能为空
            "legalName" => '张三',//法人身份证号  userType = 2 此项不能为空
            "legalArea" => NULL //用户归属地 0-大陆
        );
        $res = $this->esign->updateOrganizeAccount($accountId, $modifyArray);
        print_r($res);
    }


    function addPersonTemplateSeal()
    {

        $accountId = '16AD330415874BA98301005D29A5101C';

        $ret = $this->esign->addTemplateSeal(
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
        $accountId = 'FE3098AF3C2F452BB17A2014731BD8F7';
        $ret = $this->esign->addTemplateSeal(
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
        $srcData = '123456';
        $signResult = "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\n";
        $res = $this->esign->localVerifyText($srcData, $signResult);
        print_r($res);
    }


    //创建事件证书、事件证书签署、查询签署详情
    function eventSignPDF()
    {
        //印章图片
        //$sealData = '';
        $sealImg = file_get_contents('3.png');
        $sealData = base64_encode($sealImg);

        //创建事件证书，获取证书ID
        $cert = addEventCertId();
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
        $res = $this->esign->eventSignPDF($signFile, $signPos, $signType, $certId, $sealData, $stream = false);
        var_dump($res);
        $signServiceId = $res['signServiceId'];
        $res = $this->esign->getSignDetail($signServiceId);
        print_r($res);
        //$esign->selfSignPDF();
    }

//userSignPDF();
    function userSignPDF()
    {
        //$seal = addOrgTemplateSeal();
        //$sealData = $seal['imageBase64'];

        $sealImg = file_get_contents('3.png');
        $sealData = base64_encode($sealImg);

        $accountId = 'FE3098AF3C2F452BB17A2014731BD8F7';
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
        $res = $this->esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = false);
        var_dump($res);
        //$esign->selfSignPDF();
    }

    function fileVerify()
    {
        $filePath = 'E:\3-dst.pdf';
        $res = $this->esign->fileVerify($filePath, true);
        var_dump($res);
    }

    function selfSignPDF()
    {
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
        $res = $this->esign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = true);
        var_dump($res);
        //$esign->selfSignPDF();
    }

    function getSignDetail($signServiceId)
    {
        $res = $this->esign->getSignDetail($signServiceId);
        print_r($res);
        return $res;
    }


}