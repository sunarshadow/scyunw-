<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Adminlist extends Common
{
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
    public function index()
    {
        return view('index');
    }
    public function info()
    {
		$id = input('get.id')?input('get.id'):session('admin_id');
		if(input('get.isadd')){$id=0;}
		$user = model('admin')->getById($id);
		$auth = model('admingroup')->all();
		$sauth = model('admininspect')->all();
        return view('info',['user' => $user,'auth' => $auth,'sauth' => $sauth]);
    }
    //管理员列表
    public function getlist()
    {
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		
		//SQl语句开始
		$sql = "select *,(select title from scy_admingroup where id=scy_admin.gid) as title,(select title from scy_admininspect where id=scy_admin.sid) as stitle from scy_admin ";
		$where = "where 1 ";
		if(input('?get.keyword')){
			$where .= " and (name like '%".$keyword."%' or nickname like '%".$keyword."%') ";
		}
		$sql = $sql.$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_admin ".$where;
		//SQL语句结束

		$adminlist = model('Base')->query($sql);//获取列表
		$total = model('Base')->query($sqlcount);//获取总数
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $adminlist;		
		return json($jsonary);
    }
    //添加/修改管理员
    public function adminSet(){
		$id = input('post.id');
		$post = input('post.');
		unset($post["id"]);
		$jsonary["msg"] = '';
		//密码判断
		if(input('?post.password')){
			if($post["password"]){
				if($post["password"]!=$post["repassword"]){
					$jsonary["msg"] = "两次输入的密码不一致";
					$do = 0;
				}else{
					$post["password"] = md5($post["password"]);
					unset($post["repassword"]);
				}
			}else{
				if(!$id){$jsonary["msg"] = "请输入您的密码";}
				unset($post["password"],$post["repassword"]);
			}
		}
		//增加条件判断
		if(!$id){
			$user = model('Base')->query("select id from scy_admin where name='".$post["name"]."' or phone='".$post["phone"]."'");
			if($user){$jsonary["msg"] = "用户名或者手机号码已存在，请重新输入";}
		}
		//执行操作
		if(!$jsonary["msg"]){
			if($id>0){//修改管理员信息
				if(count($post)==1){
					$user = model('admin')->getById($id);
					model("Base")->CreateAdminLog("更改管理员状态","更改管理员[".$user["name"]."]的状态");
				}else{
					model("Base")->CreateAdminLog("编辑管理员","修改管理员[".$post["name"]."]账户信息");
				}
				unset($post["name"]);
				$result = model('Admin')->where('id', $id)->update($post);
			}else{//增加管理员
				$post["addtime"] = date("Y-m-d H:i:s");
				$result = model('Admin')->insert($post);
				model("Base")->CreateAdminLog("增加管理员","添加管理员[".$post["name"]."]");
			}
		}
		return json($jsonary);
	}
	//删除管理员
	public function adminDel(){
		$ids = input("post.ids");
		$result = model('Admin')->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除管理员","删除管理员，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
    
}
