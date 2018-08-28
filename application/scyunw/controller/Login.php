<?php
namespace app\scyunw\controller;
use think\Controller;
use app\common\model;
class Login extends Controller {
    public function index(){
       $array['url'] = url('login/login','','html',$_SERVER['HTTP_HOST']);
       $this->assign('array',$array);
	   $user = model("Admin")->join('admingroup','scy_admingroup.id = scy_admin.gid','LEFT')->select();
		foreach($user as &$val){
			$val["showname"] = $val["title"]."-".$val["nickname"];
		}
       return view('login',["user"=>$user]);
    }
    public function tologin(){
        $post = input('post.');
        $password = input('post.password');
        $post['name'] = trim($post['name']);
        $post['password'] = trim($post['password']);  
        // $captcha = $post ['captcha'];
		$smscode = input("?post.smscode")?$post["smscode"]:0;
		$phone = $post['apply_phone'];
		$result = model("Base")->smscheck($phone,$smscode);
		if($result!=1){
			// return $result;
			$this->error($result);
		}
        // if(!captcha_check($captcha)){
            // $this->error('验证码错误');
	    // }
        if(empty($post['name'])){
            $this->error('请输入帐号');
        }
        if(empty($post['password'])){
            $this->error('请输入密码');
        }
        $post['password'] = md5($post['password']);
        $user = model("Admin")->getByName($post['name']);
        if(!$user){
            $this->error('账号不存在');
        }
        if($post['password'] !== $user['password'] ){
            $this->error('账号或密码错误');
        }
        session('admin_id',$user['id']);
        session('admin_name',$user['name']);
		model("Base")->CreateAdminLog("登录","管理员[".$user['name']."]登录系统管理后台");
        $this->success('欢迎回来',url('index/index'));
     }
     public function delogin(){
		model("Base")->CreateAdminLog("注销","系统后台管理员注销登录");
		session(null);
		cookie(null);  
		$this->redirect('login/index');
     }
	 public function getphone(){
        $post = input("post.");
		 $temp = model("admin")->where("name",$post["name"])->find();
		 $post["apply_phone"] = $temp["phone"];
		 return $temp["phone"];
	 }
     
}