<?php
namespace app\scyunw\controller;
class Index extends Common
{
    public function index()
    {
        return view('index/index');
    }
    //更新逾期订单
    public function updateorder(){
        //获取三日后逾期
        $sql = "select a.phone,b.realname,a.money,a.wymoney,a.yuqitime,b.wxopenid,a.rs,a.id from scy_orderinstall as a left join scy_user as b on a.phone=b.phone where a.issms=0 and a.paystat=0 and unix_timestamp(now()) > (unix_timestamp(a.yuqitime)-259200) and unix_timestamp(now()) < (unix_timestamp(a.yuqitime)-86400)";//60*60*24*3
        $list = model("Base")->query($sql);
        foreach($list as $key=>$val){
            $order = model('userorder')->where('rs',$val["rs"])->find();
            $data = array($val["phone"],$val["realname"],$val["money"],$val["wymoney"],$val["yuqitime"],3,$order["id"]);
            model('message')->getmsg('stageyuqi',$data);//数据备注->0:手机号,1:姓名,2:待还金额,3:违约金,4:逾期时间,5:天数
            model("orderinstall")->where("id",$val["id"])->update(["issms"=>1]);
        }

        //获取一日后逾期
        $sql = "select a.phone,b.realname,a.money,a.wymoney,a.yuqitime,b.wxopenid,a.rs,a.id from scy_orderinstall as a left join scy_user as b on a.phone=b.phone where a.issms<0 and a.paystat=0 and unix_timestamp(now()) > (unix_timestamp(a.yuqitime)-86400)";//60*60*24*3
        $list = model("Base")->query($sql);
        foreach($list as $key=>$val){
            $order = model('userorder')->where('rs',$val["rs"])->find();
            $data = array($val["phone"],$val["realname"],$val["money"],$val["wymoney"],$val["yuqitime"],1,$order["id"]);
            model('message')->getmsg('stageyuqi',$data);//数据备注->0:手机号,1:姓名,2:待还金额,3:违约金,4:逾期时间,5:天数
            model("orderinstall")->where("id",$val["id"])->update(["issms"=>2]);
        }     
        $config = model("config")->find();
        $orderset = json_decode($config["orderset_value"],true);
        //获取逾期
        $sql = "select a.phone,b.realname,a.money,a.wymoney,a.yuqitime,b.wxopenid,a.rs,a.id,(unix_timestamp(now())-unix_timestamp(a.yuqitime))/60/60/24 as differ from scy_orderinstall as a left join scy_user as b on a.phone=b.phone where a.paystat=0 and unix_timestamp(now()) > (unix_timestamp(a.yuqitime)) and type=0";//60*60*24*3
        $list = model("Base")->query($sql);
        foreach($list as $key=>$val){
            $sql = "select sum(money) as total,sum(yuqicount) as yuqitotal from scy_orderinstall where paystat=0 and rs='".$val["rs"]."'";
            $temp = model("Base")->query($sql);
            $yuqicount = intval($val["differ"]);
            $wymoney = $yuqicount*$orderset["yuqirebate"]*0.01*$temp[0]["total"];
            model("orderinstall")->where("id",$val["id"])->update(["yuqistat"=>1,"wymoney"=>$wymoney,"yuqicount"=>$yuqicount]);
            model("order")->where("rs",$val["rs"])->update(["yuqistat"=>1,"yuqicount"=>$temp[0]["yuqitotal"]]);
        }           
    }
    //获取累积总数
    public function gettotal(){
        $date = date("Y-m-d");
		$sary = model("Base")->GetCheckAuth();
        //更新逾期订单
        $this->updateorder();
        //未处理询价
        $tempcount = 0;
        $sql = "select count(*) as total from scy_userorder where stat=0";
        $total = model("Base")->query($sql);
        $totary["inquery"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["inquery"];
        //未处理分期
		$totary["fqorder"] = 0;
		if(in_array("fqorder_2",$sary) || in_array("fqorder_3",$sary)){//获取分期权限:fqorder_2/初审;fq_order_3/复审
			if(in_array("fqorder_2",$sary) &&  in_array("fqorder_3",$sary)){
				$sql = "select count(*) as total from scy_order where 1 and fktype=0  and fqstat in (1,2) ";
			}elseif(in_array("fqorder_3",$sary)){
				$sql = "select count(*) as total from scy_order where 1 and fktype=0  and fqstat in (2) ";
			}elseif(in_array("fqorder_2",$sary)){
				$sql = "select count(*) as total from scy_order where 1 and fktype=0  and fqstat in (1) ";
			}
			$total = model("Base")->query($sql);
			$totary["fqorder"] = $total[0]["total"]?$total[0]["total"]:0;
			$tempcount += $totary["fqorder"];
		}
        //未结算保单
		if(in_array("order_1",$sary) || in_array("order_2",$sary) || in_array("order_3",$sary)){//获取分期权限:order_1/初审;order_2/复审；order_3/结算
			if(in_array("order_1",$sary) && in_array("order_2",$sary) && in_array("order_3",$sary)){
				$sql = "select count(*) as total from scy_order where stat<3 and (fqstat=4 or paystat=1)";
			}elseif(in_array("order_1",$sary) && in_array("order_2",$sary)){
				$sql = "select count(*) as total from scy_order where stat in (0,1) and (fqstat=4 or paystat=1)";
			}elseif(in_array("order_2",$sary) && in_array("order_3",$sary)){
				$sql = "select count(*) as total from scy_order where stat in (2,1) and (fqstat=4 or paystat=1)";
			}elseif(in_array("order_1",$sary) && in_array("order_3",$sary)){
				$sql = "select count(*) as total from scy_order where stat in (2,0) and (fqstat=4 or paystat=1)";
			}elseif(in_array("order_1",$sary)){
				$sql = "select count(*) as total from scy_order where stat in (0) and (fqstat=4 or paystat=1)";
			}elseif(in_array("order_2",$sary)){
				$sql = "select count(*) as total from scy_order where stat in (1) and (fqstat=4 or paystat=1)";
			}elseif(in_array("order_3",$sary)){
				$sql = "select count(*) as total from scy_order where stat in (2) and (fqstat=4 or paystat=1)";
			}
			$sql .= " and insurerid>0";
		}
        $total = model("Base")->query($sql);
        $totary["order"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["order"];
        //新增用户
        $sql = "select count(*) as total from scy_user where unix_timestamp(addtime) >=unix_timestamp(DATE_SUB('$date', INTERVAL 1 DAY))";
        $total = model("Base")->query($sql);
        $totary["newuser"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["newuser"];
        //未审核商户
        $sql = "select count(*) as total from scy_agent where allow_login=0";
        $total = model("Base")->query($sql);
        $totary["newagent"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["newagent"];
        //未审核保险公司
        $sql = "select count(*) as total from scy_insurer where type=0";
        $total = model("Base")->query($sql);
        $totary["newinsurer"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["newinsurer"];
        //未审核商户提现
        $sql = "select count(*) as total from scy_agenttixian where examinetype in (0,1,2)";
        $total = model("Base")->query($sql);
        $totary["newagenttixian"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["newagenttixian"];
        //逾期账单
        $sql = "select count(*) as total from scy_order where rs in (select rs from scy_orderinstall where yuqistat=1 and paystat=0) ";
        $total = model("Base")->query($sql);
        $totary["yuqicount"] = $total[0]["total"]?$total[0]["total"]:0;
        $tempcount += $totary["yuqicount"];
        $totary["allcount"] = $tempcount;
        return json_encode($totary);
    }
    public function main()
    {
        $totary = $this->gettotal();
        $totary = json_decode($totary,true);
        return view('index/main',["totary"=>$totary]);

    }
    public function navlist()
    {
        $jsonpath = ROOT_PATH ."public/static/shopadmin/json/navs.json";
        $json_string = file_get_contents($jsonpath);  
        $id = session('admin_id');
        $admin = model("admin")->where("id",$id)->find()->toArray();
        $admingroup = model("admingroup")->where("id",$admin["gid"])->find()->toArray();
        $groupmenus = explode("|",$admingroup["menus"]);
        $groupmodes = explode("|",$admingroup["modes"]);
        $temp = json_decode($json_string,true);
        foreach($temp as $key=>&$val){
            if(!(in_array($val["module"],$groupmenus)||$admingroup["menus"]=="0")){
                unset($temp[$key]);
            }
            if(isset($val["children"])){
                foreach($val["children"] as $k=>$v){
                    if(!(in_array($v["module"],$groupmodes)||$admingroup["modes"]=="0")){
                        unset($val["children"][$k]);
                    }
                }
            }
			// print_r($val);
        }
		$newary = array();
        foreach($temp as $k=>$v){
            $newary[] = $v;
        }
        $json_string = json_encode($newary);
        return json($json_string);
    }
    public function _empty()
    {
        return 'API错误，请核对参数';
    }
}
