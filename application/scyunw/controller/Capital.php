<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Capital extends User
{
    //获取红包统计列表
    public function getlist()
    {
        $table = "userred";
        $Base_model = model("Base");
        $rs = input("get.rs")?input("get.rs"):"";
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		
		//SQl语句开始
		$sql = "select * from scy_".$table." ";
        $where = "where 1 ";
        //获取用户/手机/红包类型
		if(input('?get.keyword')){
			$where .= " and (username like '%".$keyword."%' or phone like '%".$keyword."%' or content like '%".$keyword."%') ";
        }
        if($rs){ $where .= " and ordernum='".$rs."'";}
		//获取时间段
		$awake = input('?get.add')?input('get.add'):'';
		if($awake){
			$temp = explode(" - ",$awake);
			$where .= " and unix_timestamp(addtime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(addtime)<=unix_timestamp('".$temp[1]."') ";
		}        
		$sql = $sql.$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_".$table." ".$where;
		//SQL语句结束

        $totalary = array();
        //其他统计数据
        //0:注册，1:推广，2:消费，3:认证，4:生日，5:全部
        for($i=0;$i<=5;$i++){
            if($i<5){
                $redtype = " and redtype=".$i." ";
            }else{
                $redtype = " ";
            }
            $tempsql = "select count(*) as total,sum(money) as sumtotal from scy_".$table."  ".$where.$redtype;
            $temp = $Base_model->query($tempsql);
            $totalary[$i]["count"] = $temp[0]["total"];
            $totalary[$i]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0;
        }

		$list = $Base_model->query($sql);//获取列表
		$total = $Base_model->query($sqlcount);//获取总数
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;	
        $jsonary["totaldata"] = $totalary;		
		return json($jsonary);
    }
    //获取还款统计列表
    public function getpaylist()
    {
        $client_ary = array("APP端","微信端","PC端");
        $table = "orderinstall";
        $Base_model = model("Base");
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		
		//SQl语句开始
		$sql = "select * from scy_".$table." ";
        $where = "where 1";
        //获取用户/手机
		if(input('?get.keyword')){
			$where .= " and (username like '%".$keyword."%' or phone like '%".$keyword."%' ) ";
        }
        
        
		//获取时间段
        $awake = input('?get.paytime')?input('get.paytime'):'';
        $datewhere = "";
		if($awake){
			$temp = explode(" - ",$awake);
			$datewhere .= " and unix_timestamp(paytime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(paytime)<=unix_timestamp('".$temp[1]."') ";
        }   
        //其他统计数据
        $totalary = array();
        //0:今日，2:历史，3:时间段
        for($i=0;$i<=2;$i++){
            if($i==0){
                $paywhere = " and to_days(paytime)=to_days(now()) and paystat=1 ";
                $deadwhere = " and to_days(deadline)=to_days(now())";
            }elseif($i==1){
                $paywhere = " and paystat=1 ";
                $deadwhere = " and to_days(deadline)<=to_days(now())";
            }else{
                $where .= $datewhere;
                $paywhere = " and paystat=1 ";
                $deadwhere = " and to_days(deadline)<=to_days(now())";
            }

            $tempsql = "select sum(money) as total from scy_".$table."  ".$where.$paywhere;
            $temp = $Base_model->query($tempsql);
            $totalary[$i*3]["sum"] = $temp[0]["total"]?$temp[0]["total"]:0; 

            $tempsql = "select sum(money) as total from scy_".$table."  ".$where.$deadwhere;
            $temp = $Base_model->query($tempsql);
            $totalary[($i*3 + 1)]["sum"] = $temp[0]["total"]?$temp[0]["total"]:0;   

            $totalary[($i*3 + 2)]["sum"] = $totalary[($i*3 + 1)]["sum"] - $totalary[$i*3]["sum"];        
        } 
        $where .= $datewhere;
        $where .= " and paystat=1 ";    
		$sql = $sql.$where." order by id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_".$table." ".$where;
        //SQL语句结束
        

		$list = $Base_model->query($sql);//获取列表
        $total = $Base_model->query($sqlcount);//获取总数
        foreach($list as $key=>&$val){
            $temp = model("User")->field('realname')->where("phone",$val["phone"])->find();
            $val["realname"] = $temp["realname"];
        }
		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;	
        $jsonary["totaldata"] = $totalary;		
		return json($jsonary);
        
    }
}
