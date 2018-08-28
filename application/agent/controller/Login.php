<?php
namespace app\agent\controller;
use think\Controller;
use think\Db;
use app\common\model;

class Login extends Controller {
    public function index(){
       $array['url'] = url('login/login','','html',$_SERVER['HTTP_HOST']);
       $this->assign('array',$array);
       return view('login');
    }
    public function tologin(){
        $post = input('post.');
        $password = input('post.password');
        $post['name'] = trim($post['name']);
        $post['password'] = trim($post['password']);  
        $captcha = $post ['captcha'];
        if(!captcha_check($captcha)){
            $this->error('验证码错误');
	    }
        if(empty($post['name'])){
            $this->error('请输入帐号');
        }
        if(empty($post['password'])){
            $this->error('请输入密码');
        }
        $post['password'] = md5($post['password']);
        $user = model("agent")->getByUsername($post['name']);
        if(!$user){
			$this->error('账号不存在');
        }
        if($post['password'] != $user['password'] ){
            $this->error('账号或密码错误');
		}
		if($user['allow_login']=='0'){
            $this->error('请耐心等待审核');
		}
		if($user['allow_login']=='2'){
            $this->error('您的帐号已被冻结');
		}
		if($user['allow_login']=='3'){
            $this->error('您的申请已被驳回,请重新申请',url('login/Applyaccounts',['userid'=>$user['id'] ] ));
		}
		
        session('agent_id',$user['id']);
        session('agent_name',$user['username']);
        session('agent_phone',$user['phone']);
        model("agent")->where('id',$user['id'])->setInc('logincount','1');
		model("Base")->CreateAgentLog("登录","管理员[".$user['username']."]登录系统管理后台");
        $this->success('欢迎回来',url('/agent'));
     }
     public function delogin(){
		model("Base")->CreateAgentLog("注销","商户后台管理员".session('agent_name')."注销登录");
		session(null);
        cookie(null);  
		$this->redirect('login/index');
     }
     public function choice(){
        return view('choice');
     }
     //申请帐号
     public function Applyaccounts(){
		if(request()->isPost()){ //提交时
			
			//获取并且初次处理数据
			$username 	 = input('post.username') 	 	? 	input('post.username') 				: exit($this->error('请填写用户名'));
			$company 	 = input('post.company') 	 	? 	input('post.company') 				: exit($this->error('请填写公司名'));
			$phone 		 = input('post.phone') 		 	? 	input('post.phone') 				: exit($this->error('请填写电话'));
			$servicetype = input('post.servicetype') 	? 	input('post.servicetype') 			: exit($this->error('请选择服务类型'));
			$server_zao  = input('post.server_zao')  	? 	input('post.server_zao') 		    : exit($this->error('请填写上午服务时间'));
			$server_wan  = input('post.server_wan')  	? 	input('post.server_wan') 		    : exit($this->error('请填写下午服务时间'));
			$weekday  	 = input('post.weekday/a')   	? 	array_keys(input('post.weekday/a')) : exit($this->error('请填写服务周期'));
			$email 		 = input('post.email') 		 	? 	input('post.email') 				: exit($this->error('请填写邮箱'));
			$password 	 = input('post.password') 	 	? 	input('post.password') 				: exit($this->error('请填写密码'));
			$nickname 	 = input('post.nickname') 	 	? 	input('post.nickname') 				: exit($this->error('请填写负责人'));
			$nickphone 	 = input('post.nickphone') 	 	? 	input('post.nickphone') 			: exit($this->error('请填写负责人电话'));
			$province 	 = input('post.province') 	 	? 	input('post.province') 				: exit($this->error('请填写省'));
			$city 		 = input('post.city') 		 	? 	input('post.city') 					: exit($this->error('请填写市'));
			$area 		 = input('post.area') 		 	? 	input('post.area') 					: exit($this->error('请填写区'));
			$location 	 = input('post.location') 	 	? 	input('post.location') 				: exit($this->error('请填写坐标'));
			$img 	 = input('post.img/a') 	 	? 	input('post.img/a') 				: exit($this->error('请上传资料图片'));
			$smscode      = input('post.smscode')      ? input('post.smscode')      : exit($this->error('验证码必填'));
			$result = model("Base")->smscheck($nickphone,$smscode); //判断验证码
			
			if($result!=1){exit($this->error('验证码错误'));}
			
			$newagent = model("agent")->where('nickphone',$nickphone)->find(); //当负责人手机存在且手机验证码正确 即视为修改 前提判断是否已经被通过， 若是被通过则不允许修改
			if(!$newagent){
				//新建时候检查用户名唯一
				if(model("agent")->getByUsername($username) != null ){
					exit($this->error('用户名已被注册'));
				}
				$newagent = model('agent');
			}else{
				//判断三种状态不允许提交
				$newagent->allow_login == 0 ? exit($this->error('请耐心等待审核')) : true;  //等待审核
				$newagent->allow_login == 1 ? exit($this->error('已通过，不允许修改')) : true;	//
				$newagent->allow_login == 2 ? exit($this->error('您已被封禁，请联系服务人员')) : true;	//被封禁
			}	
			
			//实例化商户表模型，添加各参数
			
			$newagent ->username = $username;
			$newagent ->company = $company;
			$newagent ->phone = $phone;
			$newagent ->servicetype = $servicetype;
			$newagent ->server_zao = $server_zao;
			$newagent ->server_wan = $server_wan;
			$newagent ->weekday = implode(",",$weekday);
			$newagent ->email = $email;
			$newagent ->password = md5($password);
			$newagent ->nickname = $nickname;
			$newagent ->nickphone = $nickphone;
			$newagent ->allow_login = 0;
			$newagent ->province = $province;
			$newagent ->city = $city;
			$newagent ->area = $area;
			$newagent ->com_other_img = json_encode($img,true);
			$newagent ->location = $location;
			$newagent ->address = model('base')->Getbyprovince($province).model('base')->Getbycity($city).model('base')->Getbyarea($area);
			$newagent ->rebate = '0';
			$newagent ->addtime = date('Y-m-d H:i:s');
			$newagent ->servertime = '上午：'.$server_zao.' 下午：'.$server_wan;
			
			//执行添加并返回相应信息
			if($newagent ->save()){

				$this->success('提交成功,请耐心等待审核');
			}else{
				$this->error('提交失败');
			}
		}	
        $sql = "select * from base_province ";
		$province = Db::query($sql);
		$agentclass = model('agentclass')->select();
		return view('Applyaccounts',['province'=>$province,'agentclass'=>$agentclass]);

    }

		
     
}