<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;
// 应用公共文件
//生成随机数 支持保留2位小数
use think\Config;
use Wechat\Loader;
function & load_wechat($type = '') {
    static $wechat = array();
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        $config = Config::get('wechat');
        $config['cachepath'] = CACHE_PATH . 'Data/';
        $wechat[$index] = Loader::get($type, $config);
    }
    return $wechat[$index];
}


function randomFloat($min = 0, $max = 1) {
    $number =  $min + mt_rand() / mt_getrandmax() * ($max - $min);
    return round($number,2); 
}

//树
function genTree($items,$pid ="parentid") {
    $map  = [];
    $tree = [];   
    foreach ($items as &$it){ $map[$it['fyid']] = &$it; }  //数据的ID名生成新的引用索引树
    foreach ($items as &$it){
        $parent = &$map[$it[$pid]];
        if($parent) {
            $parent['son'][] = &$it;
        }else{
            $tree[] = &$it;
        }
    }
    return $tree;
}
//抓数据
function curl_go($url){
    //初始化一个 cURL 对象
    $ch  = curl_init();
    //设置你需要抓取的URL
    curl_setopt($ch, CURLOPT_URL, $url);
    // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //是否获得跳转后的页面
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $data = curl_exec($ch);
    $data = json_decode($data);
    curl_close($ch);
    return $data;
}

function unescape($str) { //这个是解密用的  
    $str = rawurldecode($str);   
    preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U",$str,$r);   
    $ar = $r[0];   
    foreach($ar as $k=>$v) {   
             if(substr($v,0,2) == "%u")   
                      $ar[$k] = iconv("UCS-2","GBK",pack("H4",substr($v,-4)));   
             elseif(substr($v,0,3) == "&#x")   
                      $ar[$k] = iconv("UCS-2","GBK",pack("H4",substr($v,3,-1)));   
             elseif(substr($v,0,2) == "&#") {   
                      $ar[$k] = iconv("UCS-2","GBK",pack("n",substr($v,2,-1)));   
             }   
    }   
    return join("",$ar);   
}  

function phpescape($str){//这个是加密用的  
preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/",$str,$newstr);  
$ar = $newstr[0];  
foreach($ar as $k=>$v){  
   if(ord($ar[$k])>=127){  
       $tmpString=bin2hex(iconv("GBK","ucs-2",$v));  
       if (!eregi("WIN",PHP_OS)){  
           $tmpString = substr($tmpString,2,2).substr($tmpString,0,2);  
       }  
       $reString.="%u".$tmpString;  
   } else {  
       $reString.= rawurlencode($v);  
   }  
}  
return $reString;  
} 

function getAddressByuserid($userid,$isdefault=false){
    if($isdefault)
    {
        $where['isdefault'] = $isdefault;
    }
    $data = Db::name('useraddress')
        ->where('uid',$userid)
        ->where($where)
        ->find();
    if(is_array($data)){
        $data['province'] = $data['province'] ? model('base')->Getbyprovince($data['province']) : '';
        $data['city'] = $data['city'] ? model('base')->Getbycity($data['city']) : '';
        $data['area'] = $data['area'] ? model('base')->Getbyarea($data['area']) : '';
        $data['address'] = $data['province'].$data['city'].$data['area'].$data['address'];
    }
    return $data;
    
}
function getstatbyorder($rs){
    $order = model('order')->where('rs',$rs)->find();
    $str = "";
    if($order){
        switch ($order -> stat )
        {
        // {0，1复审中，2拨款中，3出单中，4生效中，5过期，6违约
        case '0':
            $str = "初审中";
          break;  
        case '2':
            $str = "拨款中";
          break;
        case '3':
            $str = "<a  href='policycreate.html?rs=$rs' class='baojia'>生成订单</a>";
            break;
        case '4':
            $nowdate = time();
            $awaketime = strtotime($order -> awaketime);
            if($nowdate >= $awaketime)
            {
                $str = "生效中";
            }
            else
            {
                $str = "待生效";
            }
            break;
        case '5':
            $str = "过期";
            break;
        case '6':
            $str = "违约";
            break;
        default:
        }
    }else{
        $str = "待付款";
    }
    return $str;
}
//通过id得到服务类型
function getservertype($req)
{
    $agentclass = model('agentclass')->where('id',$req)->find();
    return $agentclass->name;
}
//预约总次数
function getbespokecount($req)
{
    $count = model('agentorder')->where('paystat','0')->where('agentid',$req)->count();
    return $count;
}
//消费总次数
function getconsumptioncount($req)
{
    $count = model('agentorder')->where('paystat','1')->where('agentid',$req)->count();
    return $count;
}
//出单成交率
function getconsumptionrate($req)
{
    $consumption = getconsumptioncount($req);
    $allcount = model('agentorder')->where('agentid',$req)->count();
    $rate = $allcount !== 0 ? round($consumption/$allcount,2):0;
    return $rate.'%';
}
function bankinfo($id,$main)
{
    $bank = Db::name('agentbank')->where('userid',$id)->where('ismain','1')->find();
    switch ($main) {
        case 'bankaccount':
            return $bank['bank_account'];
            break;
        case 'acholder':
            return $bank['acholder'];
            break;
        case 'openacstore':
            return $bank['openac_store'];
            break;
        default:
            break;
    }
}
//通过id得到商户类型
function getagentclass($id,$gettype=false)
{
    $agentclass = model('agentclass')->where('id',$id)->find();
    if($gettype){
        return $agentclass ? $agentclass->status : '';
    }else{
        return $agentclass ? $agentclass->name : '';
    }
}
//通过id得到商户银行卡信息
function getagentbank($id,$field=[])
{
    $agentbank = model('agentbank')
        ->where('id',$id)
        ->field($field)
        ->find();
    if(is_array($field)){
        return $agentbank ? $agentbank : null;
    }
    if(is_string($field)){
        return $agentbank ? $agentbank->$field : null;
    }

}
//通过id得到商户信息
function getagent($id,$field=[])
{
    $agent = model('agent')
        ->where('id',$id)
        ->field($field)
        ->find();
    if(is_array($field)||count($field) > 1){
        return $agent ? $agent : null;
    }
    if(is_string($field)||count($field)==1){
        return $agent ? $agent->$field : null;
    }
}
//清除html css js 图片
function cutstr_html($string,$length=0,$ellipsis='…'){
    $string=preg_replace("/<img.+?>/","", $string);
    $string=strip_tags($string);
    $string=preg_replace('/\n/is','',$string);
    $string=preg_replace('/ |　/is','',$string);
    $string=preg_replace('/&nbsp;/is','',$string);
    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/",$string,$string);
    if(is_array($string)&&!empty($string[0])){
        if(is_numeric($length)&&$length){
            $string=join('',array_slice($string[0],0,$length)).$ellipsis;
        }else{
            $string=implode('',$string[0]);
        }
    }else{
        $string='';
    }
    return $string;
}



