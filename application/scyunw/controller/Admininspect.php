<?php
namespace app\scyunw\controller;
use think\Request;
use app\common\model;
class Admininspect extends Common
{
	private $table='admininspect';
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
    public function index()
    {
        return view('index');
    }
    public function inspectlist()
    {
		$inspect = model($this->table)->all();
        return view('inspectlist',["inspect" => $inspect]);
    }
    public function info()
    {
		$id = input('get.id');
		$info = model($this->table)->getById($id);
        $groupmenus = explode("|",$info["menus"]);
        $groupmodes = explode("|",$info["modes"]); 		
        return view('info',['info' => $info,'groupmenus' => $groupmenus,'groupmodes' => $groupmodes]);
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
	//获取审核统计
	public function getinspectlist()
	{
		$page = input('?get.page')?input('get.page'):1;//获取页数
		$limits = input('?get.limit')?input('get.limit'):1;//获取每页显示数量
		$keyword = input('?get.keyword')?input('get.keyword'):'';//获取关键字
		$searchtime = input('?get.searchtime')?input('get.searchtime'):'';//获取搜索时间
		$sid = input('?get.sid')?input('get.sid'):'';//获取组别
		$where = "";
		$where = "where 1 ";
		if(input('?get.keyword')){
			$where .= " and (a.name like '%".$keyword."%') ";
		}	
		if(input('?get.sid')){
			$where .= " and a.sid=".$sid." ";
		}		
		//SQl语句开始
		$sql = "select a.id,a.name,b.title from scy_admin as a inner join scy_admininspect as b on a.sid=b.id ";
		$sql = $sql.$where." order by a.id desc limit ".($page-1)*$limits.",".$limits;
		$sqlcount = "select count(*) as total from scy_admin as a ".$where;
		//SQL语句结束
		$Base_model = model("Base");
		$list = $Base_model->query($sql);//获取列表
		$total = $Base_model->query($sqlcount);//获取总数

		//获取统计
		$ordersql = "select count(*) as total,sum(b.order_price) as sumtotal from scy_order as a inner join scy_userorder as b on a.rs=b.rs ";
		//获取分期未初审核数量
		$tempsql = $ordersql." where fktype=0 and fqstat<2 and fqstat>0";
		$temp = $Base_model->query($tempsql);
		$totalary[0]["count"] = $temp[0]["total"]; 
		$totalary[0]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取分期未复审核数量
		$tempsql = $ordersql." where fktype=0 and fqstat=2";
		$temp = $Base_model->query($tempsql);
		$totalary[1]["count"] = $temp[0]["total"]; 
		$totalary[1]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取保费未初审核数量
		$tempsql = $ordersql." where a.stat=0  and (a.paystat=1 or a.fqstat=4) and a.insurerid>0 ";
		$temp = $Base_model->query($tempsql);
		$totalary[2]["count"] = $temp[0]["total"]; 
		$totalary[2]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取保费未复审核数量
		$tempsql = $ordersql." where a.stat=1  and (a.paystat=1 or a.fqstat=4) and a.insurerid>0 ";
		$temp = $Base_model->query($tempsql);
		$totalary[3]["count"] = $temp[0]["total"]; 
		$totalary[3]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取保费未结算数量
		$tempsql = $ordersql." where a.stat=2";
		$temp = $Base_model->query($tempsql);
		$totalary[4]["count"] = $temp[0]["total"]; 
		$totalary[4]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 

		//获取商户提现未初审
		$tempsql = "select count(*) as total,sum(money) as sumtotal from scy_agenttixian where examinetype=0 ";
		$temp = $Base_model->query($tempsql);
		$totalary[5]["count"] = $temp[0]["total"]; 
		$totalary[5]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取商户提现未复审
		$tempsql = "select count(*) as total,sum(money) as sumtotal from scy_agenttixian where examinetype=1 ";
		$temp = $Base_model->query($tempsql);
		$totalary[6]["count"] = $temp[0]["total"]; 
		$totalary[6]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取商户提现未结算
		$tempsql = "select count(*) as total,sum(money) as sumtotal from scy_agenttixian where examinetype=2 ";
		$temp = $Base_model->query($tempsql);
		$totalary[7]["count"] = $temp[0]["total"]; 
		$totalary[7]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		
		//获取询价未初审
		$tempsql = "select count(*) as total,sum(order_price) as sumtotal from scy_userorder where stat=0 ";
		$temp = $Base_model->query($tempsql);
		$totalary[8]["count"] = $temp[0]["total"]; 
		$totalary[8]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 


		foreach($list as $key=>&$val){
			//获取询价初审次数
			$tempsql = "select count(*) as total from scy_userorder where checkadminid=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["check_count"] = "<a style=\"color: #5FB878;\" class=\"layui-btn\" data-type=\"tourl\" data-url=\"{:url('userorder\index')}\">".$temp[0]["total"]."</a>";	
			$val["check_count"] = $temp[0]["total"];
			
			//获取分期初审次数
			$tempsql = "select count(*) as total from scy_order where fktype=0 and firstadminid=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["first_count"] = $temp[0]["total"];	
			
			//获取分期复审次数
			$tempsql = "select count(*) as total from scy_order where fktype=0 and secondadminid=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["second_count"] = $temp[0]["total"];	
			
			//获取保费初审次数
			$tempsql = "select count(*) as total from scy_order where inspectadminid=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["inspect_count"] = $temp[0]["total"]?$temp[0]["total"]:0;	
			
			//获取保费复审次数
			$tempsql = "select count(*) as total from scy_order where comfirmadminid=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["comfirm_count"] = $temp[0]["total"];	
			
			//获取保费复审次数
			$tempsql = "select count(*) as total from scy_order where approadminid=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["appro_count"] = $temp[0]["total"];	
			
			//获取商户提现初审次数
			$tempsql = "select count(*) as total from scy_agenttixian where firstexamine=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["a_first_count"] = $temp[0]["total"];
			
			//获取商户提现复审次数
			$tempsql = "select count(*) as total from scy_agenttixian where secondexamine=".$val["id"];
            $temp = $Base_model->query($tempsql);
			$val["a_second_count"] = $temp[0]["total"];
		}

		$jsonary["code"] = 0;
		$jsonary["count"] = $total[0]["total"];
		$jsonary["data"] = $list;	
        $jsonary["totaldata"] = $totalary;		
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
				model("Base")->CreateAdminLog("编辑审核组","修改审核组[".$post["title"]."]");
			}else{//增加
				$post["addtime"] = $post["lastedit"] = date("Y-m-d H:i:s");
				$result = model($this->table)->insert($post);
				model("Base")->CreateAdminLog("添加审核组","添加审核组[".$post["title"]."]");
			}
		}
		return json($jsonary);
	}
	//删除
	public function GetDel(){
		$ids = input("post.ids");
		$result = model($this->table)->where('id','in',$ids)->delete();
		model("Base")->CreateAdminLog("删除审核组","删除审核组，ID[".$ids."]");
		$jsonary["code"] = $result;
		return json($jsonary);
	}
    
}
