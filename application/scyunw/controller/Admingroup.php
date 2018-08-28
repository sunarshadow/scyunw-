<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Admingroup extends Common
{
	private $table='admingroup';
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
		$id = input('get.id');
		$info = model($this->table)->getById($id);
		$menus = file_get_contents("http://127.0.0.1/static/shopadmin/json/navs.json");
		$menus = json_decode($menus,true);
        $groupmenus = explode("|",$info["menus"]);
        $groupmodes = explode("|",$info["modes"]); 		
        return view('info',['info' => $info,'menus' => $menus,'groupmenus' => $groupmenus,'groupmodes' => $groupmodes]);
    }
    //列表
    public function getlist()
    {
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		
		//SQl语句开始
		$sql = "select * from scy_".$this->table." ";
		$where = "where 1 ";
		if(input('?get.keyword')){
			$where .= " and (title like '%".$keyword."%') ";
		}
		$sql = $sql.$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_".$this->table." ".$where;
		//SQL语句结束

		$list = model("Base")->query($sql);//获取列表
		$total = model("Base")->query($sqlcount);//获取总数
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;		
		return json($jsonary);
    }
    //添加/修改
    public function GetSet(){
		$id = input('post.id');
		$post = input('post.');
		$temp = "";
		foreach($post["menus"] as $key=>$val){$temp .= $val."|";}
		$post["menus"] = $temp;
		$temp = "";
		foreach($post["modes"] as $key=>$val){
			$temp .= $val."|";
			if($val=="Webmanage"){$temp .= "Banner|News|";}
		}
		$post["modes"] = $temp;
		unset($post["id"]);
		$jsonary["msg"] = '';
		//增加条件判断
		if(!$id){
			$info = model("Base")->query("select id from scy_".$this->table." where title='".$post["title"]."' ");
			if($info){$jsonary["msg"] = "分组名称已存在，请重新输入";}
		}
		//执行操作
		if(!$jsonary["msg"]){
			if($id>0){//修改
				$post["lastedit"] = date("Y-m-d H:i:s");
				$result = model($this->table)->where('id', $id)->update($post);
				model("Base")->CreateAdminLog("编辑权限组","修改权限组[".$post["title"]."]");
			}else{//增加
				$post["addtime"] = $post["lastedit"] = date("Y-m-d H:i:s");
				$result = model($this->table)->insert($post);
				model("Base")->CreateAdminLog("添加权限组","添加权限组[".$post["title"]."]");
			}
		}
		return json($jsonary);
	}
	//删除
	public function GetDel(){
		$ids = input("post.ids");
		$result = model($this->table)->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除权限组","删除权限组，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
    
}
