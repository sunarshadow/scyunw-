<?php
/**
 * User: Administrator
 * Date: 2017/9/22
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

use tech\core\eSign;
use tech\core\Util;

include("../API/eSignOpenAPI.php");

//实例化
$esign = null;
try {
    $esign = new eSign();
} catch (Exception $e) {
    echo 'init error :', $e->getMessage();
    exit;
}
pdfTemplate();
function pdfTemplate()
{
    global $esign;

    $templateId = '34918d7f9bd2439ab13b7460a0c407db';
    $keyValuePair = ["img1" => '123', "name" => 'abc'];
       /* array(
        'user' =>'123123123',
        'storageNumber' => 1,
        'issueTime' => 'aaa',
        'userName' => 'ddddd',
        'docuType' => '8',
        'docuNumber' =>'1',
        'platform' =>'2015',
        'isTempered' =>'1',
        'saveTime' =>'1',
        'provedSeal' =>'1'
    );*/

    $ret = $esign->generatePdfByTemplate($templateId, $keyValuePair);
    print_r($ret);
    return $ret;
}