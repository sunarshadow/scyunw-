<?php
namespace app\Lib\controller;
use think\Request;
use app\common\model;
use think\Db;
class Base extends Common
{
    public function _empty()
    {
        return 'API错误，请核对参数';
    }	
	public function province($id){
		$sql = "select city_id as id,city_name as name from base_city where province_id=".$id."";
		$city = model("Base")->query($sql);		
		return json($city);
	}
	public function city($id){
		$sql = "select area_id as id,area_name as name from base_area where city_id=".$id."";
		$city = model("Base")->query($sql);		
		return json($city);
	}
	public function insurer($id){
		$sql = "select * from scy_insurer where city=".$id."";
		$city = model("Base")->query($sql);		
		return json($city);
	}
}
