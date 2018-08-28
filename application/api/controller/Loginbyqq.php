<?php
namespace app\api\controller;
use think\Controller;

class Loginbyqq extends Controller
{
    //创建URL
    public function index()
    {
        echo 1;
        return view('index');
    }
    public function tologin()
    {
        import('QQAPI.qqConnectAPI', EXTEND_PATH);
        $qc = new \QC();
        
        $qc->qq_login();
        // $qc->get_user_info();
        exit;
        //dump();
        
    }
        //qq验证登陆
    public function qq_callback(){
        import('QQAPI.qqConnectAPI', EXTEND_PATH);
        $qc = new \QC();
        $qc->qq_callback();
        // dump($res1);
        $qc->get_openid();
        // dump($res);
        //$this->success("QQ登陆成功",U('Login/qq_user'));
    }
         //qq取资料
    public function qq_user(){
        import('QQAPI.qqConnectAPI', EXTEND_PATH);
        $qc = new \QC();
        $arr = $qc->get_user_info();
        //判断是否绑定
        $Q=M('QQ绑定数据库');
        $where['openid']=$_SESSION['QC_userData']['openid'];
        $isqq=$Q->where($where)->find();
        if($isqq){
                    //如果已绑定某用户,则用uid取用户名直接session到此用户
        }else{
                    //如果未绑定则跳转到完善用户信息
            session('head',$arr['figureurl_2']);
            session('nick',$arr['nickname']);
            $this->success("请完善用户信息",U('Login/reg_qq'));
        }
    }

   
    

}