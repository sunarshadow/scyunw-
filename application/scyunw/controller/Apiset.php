<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Apiset extends Common
{
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
    public function index()
    {
		$table = model("config");
		$info = $table->find();
		if($info){
			$info["share"] = json_decode($info["share_value"],true);
			$info["orderset_value"] = json_decode($info["orderset_value"],true);
			$info["red_value"] = json_decode($info["red_value"],true);
		}
		$loopvaule = array("0"=>"A","1"=>"B","2"=>"C","3"=>"D","4"=>"E","5"=>"F","6"=>"G");
		$redvalue = array("0"=>"注册奖励","1"=>"推荐红包","2"=>"消费奖励","3"=>"认证奖励","4"=>"生日红包");
        return view('index',["info"=>$info,"loopvaule"=>$loopvaule,"redvalue"=>$redvalue]);
	}
	//上传图片
    public function info()
    {	
		if(request()->isPost()){ //当图片上传时候
			$imgurl = action('Upload/img');
			return Json($imgurl);
		}
	}
    //添加/修改
    public function GetSet(){
		$table = model("config");
		$post = input('post.');
		$jsonary["msg"] = '';
		// $post["share"]["qrcodetext"] = $post["qrcodetext"];
		$share = $post["share"];
		$post["share_value"] = json_encode($share);
		// print_r($post);exit;
		$orderset = $post["orderset_value"];
		$post["orderset_value"] = json_encode($orderset);
		$red = $post["red_value"];
		$post["red_value"] = json_encode($red);
		unset($post["share"],$post["file"],$post["qrcodetext"]);
		
		$info = $table->find();
		if(!$jsonary["msg"]){
			if($info){//修改
				$post["lastedit"] = date("Y-m-d H:i:s");
				$result = $table->where('id', $info["id"])->update($post);
				model("Base")->CreateAdminLog("修改API设置","修改API设置");
			}else{//增加
				$post["lastedit"] = date("Y-m-d H:i:s");
				$result = $table->insert($post);
				model("Base")->CreateAdminLog("修改API设置","修改API设置");
			}
		}
		return json($jsonary);
	}
    
}
