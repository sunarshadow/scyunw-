<?php
namespace app\Insurer\controller;
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
        $user = model("insurer")->getByLoginname($post['name']);
        if(!$user){
            $this->error('账号不存在');
        }
        if($post['password'] != $user['password'] ){
            $this->error('账号或密码错误');
        }
        if($user['type'] == '0' ){
            $this->error('请耐心等待审核');
        }
        if($user['type']  == '2'){
            $this->error('您的帐号已被冻结');
        }
        if($user['type']  == '3'){
            $this->error('您的申请被驳回');
        }
        
        session('insurer_id',$user['id']);
        session('insurer_name',$user['loginname']);
        session('insurer_pany',$user['insuranceid']);
        model("insurer")->where('id',$user['id'])->setInc('logincount','1');
        model("Base")->CreateInsurerLog("登录","保险后台管理员".session('insurer_name')."登录后台");
        $this->success('欢迎回来',url('index/index'));
     }
     public function delogin(){
        model("Base")->CreateInsurerLog("注销","保险后台管理员".session('insurer_name')."注销登录");
		session(null);
		cookie(null);  
		$this->redirect('login/index');
     }
     public function Applyaccounts(){
        if(request()->isPost()){
            $companyname = input('post.companyname') ? input('post.companyname') : exit('');
            $loginname = input('post.loginname') ? input('post.loginname') : exit('');
            $password = input('post.password') ? input('post.password') : exit('');
            $province = input('post.province') ? input('post.province') : exit('');
            $city = input('post.city') ? input('post.city') : exit('');
            $area = input('post.area') ? input('post.area') : exit('');
            $insuranceid = input('post.insuranceid') ? input('post.insuranceid') : exit('');
            $corporation = input('post.corporation') ? input('post.corporation') : exit('');
            $cardname = input('post.cardname') ? input('post.cardname') : exit('');
            $carnumber = input('post.carnumber') ? input('post.carnumber') : exit('');
            $carbank = input('post.carbank') ? input('post.carbank') : exit('');
            $cptphone = input('post.cptphone') ? input('post.cptphone') : exit('');
            $img 	 = input('post.img/a') 	 	? 	input('post.img/a') 				: exit($this->error('请上传资料图片'));
			$smscode      = input('post.smscode')      ? input('post.smscode')      : exit($this->error('验证码必填'));
            $result = model("Base")->smscheck($cptphone,$smscode); //判断验证码
            if($result!=1){exit($this->error('验证码错误'));}
            $newinsurer = model("insurer")->where('cptphone',$cptphone)->find(); //当负责人手机存在且手机验证码正确 即视为修改 前提判断是否已经被通过， 若是被通过则不允许修改
			if(!$newinsurer){
				//新建时候检查公司名称唯一
				if(model("insurer")->getByCompanyname($companyname) != null ){
					exit($this->error('用户名已被注册'));
                }
                //新建时候检查登录名称唯一
				if(model("insurer")->getByLoginname($loginname) != null ){
					exit($this->error('用户名已被注册'));
                }
                
				$newinsurer = model('insurer');
			}else{
				//判断三种状态不允许提交
				$newinsurer->type == 0 ? exit($this->error('请耐心等待审核')) : true;  //等待审核
				$newinsurer->type == 1 ? exit($this->error('已通过，不允许修改')) : true;	//
				$newinsurer->type == 2 ? exit($this->error('您已被封禁，请联系服务人员')) : true;	//被封禁
			}
            $newinsurer->companyname = $companyname;
            $newinsurer->image = json_encode($img);
            $newinsurer->loginname = $loginname;
            $newinsurer->password = md5($password);
            $newinsurer->truepassword = $password;
            $newinsurer->addtime = date('Y-m-d H:i:s');
            $newinsurer->province = $province;
            $newinsurer->city = $city;
            $newinsurer->area = $area;
            $newinsurer->insuranceid = $insuranceid;
            $newinsurer->corporation = $corporation;
            $newinsurer->cardname = $cardname;
            $newinsurer->carnumber = $carnumber;
            $newinsurer->carbank = $carbank;
            $newinsurer->cptphone = $cptphone;
			
            if($newinsurer-> save()){
                $this->success('提交成功，请等待审核');
            }else{
                $this->error('提交失败');
            }
            exit;
		}else{
            $sql = "select * from base_province ";
            $province = Db::query($sql);
            $sql = "select * from scy_insurancecompany ";
			$insuranceid = Db::query($sql);
			// if(input('get.id')){
			// 	$thisid = input('get.id');
			// 	$company = model('insurer')->get($thisid);
			// 	$city = Db::table('base_city')->where('province_id',$company -> province)->select();
			// 	$area = Db::table('base_area')->where('city_id',$company -> city)->select();
			// 	return view('addcompany',['province'=>$province,'company'=>$company,'city'=>$city,'area'=>$area,]);
            // }
            
			return view('Applyaccounts',['province'=>$province,'insuranceid'=>$insuranceid]);
		}
     }
     
}