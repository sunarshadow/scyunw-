<?php
namespace app\agent\controller;
use think\Controller;
use think\Db;
use think\Request;
use app\common\model;
class Infosys extends Common
{
    public function index()
    {
        
    }
    
    public function mechanism()
    {
        session('agent_id','258');
        session('agent_name','test');
        $agent = model('agent')
        ->where('id',session('agent_id'))
        ->find();
        if(Request::instance()->isPost())
        {
            input('post.area') ? $agent->area = input('post.area') :  false;
            input('post.city') ? $agent->city = input('post.city') :  false;
            input('post.province') ? $agent->province = input('post.province') :  false;
            input('post.username') ? $agent->username = input('post.username') :  false;
            input('post.phone') ? $agent->phone = input('post.phone') :  false;
            input('post.location') ? $agent->location = input('post.location') :  false;
            if($agent->save()){
                model("Base")->CreateAgentLog("修改","商户后台管理员".session('agent_name')."修改机构信息");
                $this->success('修改成功');
            }else{
                $this->error('修改失败') ;
            }
            exit;
        }
        $province = Db::table('base_province')->select();
        $city = Db::table('base_city')->where('province_id',$agent->province)->select();
        $area = Db::table('base_area')->where('city_id',$agent->city)->select();
        return view('mechanism',['agent'=>$agent,'province'=>$province,'city'=>$city,'area'=>$area]);
    }
    public function bancard()
    {
        session('agent_id','258');
        session('agent_name','test');
        $agent = model('agent')
        ->where('id',session('agent_id'))
        ->find();
        if(Request::instance()->isPost())
        {
            $code =input('post.code');
            switch ($code) {
                case 'ismain': //修改默认
                $id = input('post.id') ? input('post.id') : exit($this->error('错误'));
                $agentbank = model('agentbank') //取消掉默认
                ->where('userid',$agent->id)
                ->where('ismain','1')
                ->find();
                if($agentbank){
                    $agentbank->ismain = 0;
                    $agentbank->save();
                }

                $bankcard = model('agentbank') //增加新的默认
                ->where('userid',$agent->id)
                ->where('id',input('id'))
                ->find();
                $bankcard->ismain = 1;
                if($bankcard->save()){
                    model("Base")->CreateAgentLog("修改","商户后台管理员".session('agent_name')."修改默认银行卡");
                    $this->success('修改默认成功');
                }else{
                    $this->error('修改默认失败');
                }

                    break;
                case 'delete': //删除银行卡
                    $id = input('post.id') ? input('post.id') : exit($this->error('错误'));
                    if(model('agentbank')->destroy($id)){
                        model("Base")->CreateAgentLog("删除","商户后台管理员".session('agent_name')."删除银行卡");
                        $this->success('删除成功');
                    }else{
                        $this->error('删除失败');
                    }
                    break;
                case 'addbank': //添加银行卡
                    $acholder     = input('post.acholder')     ? input('post.acholder')     : exit($this->error('开卡所用名必填'));
                    $acmenid      = input('post.acmenid')      ? input('post.acmenid')      : exit($this->error('开卡证件号必填'));
                    $bank_account = input('post.bank_account') ? input('post.bank_account') : exit($this->error('银行卡必填'));
                    $password     = input('post.password')     ? input('post.password')     : exit($this->error('商户密码必填'));
                    $phone        = input('post.phone')        ? input('post.phone')        : exit($this->error('开卡手机必填'));
                    $smscode      = input('post.smscode')      ? input('post.smscode')      : exit($this->error('验证码必填'));
                    $agent->password == md5($password)  ? true : exit($this->error('密码错误')); //判断密码
                    $result = model("Base")->smscheck($phone,$smscode); //判断验证码
                    if($result!=1){exit($this->error('验证码错误'));}
                    $bank_name = model('Banklist')->bankInfo($bank_account);//获取银行
                    $openac_store = explode('-',$bank_name);
                    $bankcard = model('agentbank'); //执行添加
                    $bankcard->acholder     = $acholder;
                    $bankcard->acmenid      = $acmenid;
                    $bankcard->bank_account = $bank_account;
                    $bankcard->phone        = $phone;
                    $bankcard->acholder     = $acholder;
                    $bankcard->userid       = $agent->id;
                    $bankcard->username     = $agent->username;
                    $bankcard->bank_name    = $bank_name;
                    $bankcard->openac_store = $openac_store[0];
                    if($bankcard->save()){
                        model("Base")->CreateAgentLog("添加","商户后台管理员".session('agent_name')."添加银行卡");
                        $this->success('添加银行卡成功');
                    }else{
                        $this->error('添加银行卡失败');
                    }
                    break;
                
                default:
                    $this->error('参数错误');
                    break;
            }
            // input('post.area') ? $agent->area = input('post.area') :  false; 
            // input('post.id') ? $agent->location = input('post.id') :  false;
            // $agent->save() ? $this->success('修改成功') : $this->error('修改失败') ;
            exit;
        }
        $bankcard = model('agentbank')
        ->where('userid',$agent->id)
        ->select();
        return view('bancard',['agent'=>$agent,'bankcard'=>$bankcard]);
    }
    public function personal()
    {
        session('agent_id','258');
        session('agent_name','test');
        $agent = model('agent')
        ->where('id',session('agent_id'))
        ->find();
        if(Request::instance()->isPost())
        {
            $nickname      = input('post.nickname')    ? input('post.nickname')     : exit($this->error('负责人必填'));
            $nickphone     = input('post.nickphone')   ? input('post.nickphone')    : exit($this->error('负责人手机必填'));
            $email         = input('post.email')       ? input('post.email')        : exit($this->error('邮箱必填'));
            $morning       = input('post.morning')     ? input('post.morning')      : exit($this->error('上午服务时间必填'));
            $night         = input('post.night')       ? input('post.night')        : exit($this->error('下午服务时间必填'));
            $week          = input('post.week/a')      ? array_keys(input('post.week/a'))   : exit($this->error('服务周期必选必填'));
            $tpassword     = input('post.tpassword')   ? input('post.tpassword')    : false;
            $password      = input('post.password')    ? input('post.password')     : false;
            $truepassword  = input('post.truepassword')? input('post.truepassword') : false;
            $agent -> nickname = $nickname;
            $agent -> nickphone = $nickphone;
            $agent -> email = $email;
            $agent -> server_zao = $morning;
            $agent -> server_wan = $night;
            $agent -> weekday = implode(",",$week);
            $agent -> servertime = '上午：'.$morning.'&nbsp;&nbsp;'.'下午：'.$night;
            
            if($password || $truepassword || $tpassword ){
                $password == $truepassword ? true : exit($this->error('两次密码不一致'));
                $agent->password == md5($tpassword) ? true : exit($this->error('密码错误'));
                $agent ->password = md5($password);
            }
            if($agent->save()){
                model("Base")->CreateAgentLog("修改","商户后台管理员".session('agent_name')."修改个人信息");
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
            exit;
        }
        $province = Db::table('base_province')->select();
        $city = Db::table('base_city')->where('province_id',$agent->province)->select();
        $area = Db::table('base_area')->where('city_id',$agent->city)->select();
        return view('personal',['agent'=>$agent,'province'=>$province,'city'=>$city,'area'=>$area]);
    }
    
    
}