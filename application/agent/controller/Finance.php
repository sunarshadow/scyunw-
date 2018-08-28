<?php
namespace app\agent\controller;
use think\Controller;
use think\Request;
class Finance extends Common
{
    public function Apply()
    {
        if(Request::instance()->isPost())
        {   
            $password = input('post.password') ? md5(input('post.password')) : $this->error('提现密码错误');
            //判断密码
            $agent = model('agent')
            ->where('id',session('agent_id'))
            ->find();
            $agent->password == md5(input('post.password')) ? true : $this->error('提现密码错误');
            //增加提现信息
            $tixian   = model('agenttixian');
            $tixian->money     = input('post.money') && is_numeric(input('post.money')) && input('post.money') < $agent->money ? input('post.money') : $this->error('提现金额错误'); //判断金额的是否存在，小于用户本身金额以及为数字
            $tixian->bankcarid = input('post.bankcarid') || is_numeric(input('post.bankcarid'))? input('post.bankcarid') : $this->error('提现银行卡错误');
            $tixian->userid    = session('agent_id');
            $tixian->addtime   = date('Y-m-d H:i:s');
            $tixian->allmoney  = $agent->money;
            if($tixian->save()) //如果提现了的话自身商户自身金额减少
            {
                model("Base")->CreateAgentLog("申请提现","商户后台管理员".session('agent_name')."申请提现".$tixian->money."元");
                model('agent')
                ->where('id',session('agent_id'))
                ->setDec('money',$tixian->money);
                //发送信息
                model('message')->getmsg('agent_tixian',[$agent->nickphone,$tixian->money]);//负责人电话  提现金额 

                return $this->error('提现成功');
            }else{
                return $this->error('提现失败');
            }
            
        }else{
            session('agent_id','258');
            session('agent_name','test');
            $agentbank =model('agentbank')
            ->where('userid',session('agent_id'))
            ->select();
            $agent = model('agent')
            ->where('id',session('agent_id'))
            ->find();
            return view('Apply',['agentbank'=>$agentbank,'agent'=>$agent]);

        }
        
    }
    public function select()
    {
        return view('select');
    }
    //提现申请记录
    public function getWithdrawals()
    {   
        $page = input('?get.page') ? input('get.page')  : false ; //获得当前页
        $limit = input('?get.limit') ? input('get.limit')  : false ; //获得数量
        $stat = input('?get.stat') ? input('get.stat')  : false ; //获得状态
        $phone = input('?get.phone') ? input('get.phone')  : false ; //获得电话
        $bank_account = input('?get.bank_account') ? input('get.bank_account')  : false ; //获得银行卡
        $username = input('?get.username') ? input('get.username')  : false ; //获得用户名
        $toexcel = input('?get.toexcel') ? input('get.toexcel')  : false ; //是否需要导出EXCEL
        $ids = input("?get.ids")?input("get.ids"):false; //导出EXCEL时候是否存在导出ID
        $where = [];
        if($stat != '')
        {   
            $where['agenttixian.examinetype'] = ['in',$stat];
        }
        if($ids){
            $where['agenttixian.id'] = ['in',$ids];
		}
        if($phone)
        {
            $where['agent.phone'] = $phone;
        }
        if($bank_account)
        {
            $where['agentbank.bank_account'] = $bank_account;
        }
        if($username)
        {
            $where['agent.username'] = $username;
        }
        $toexcel ? 
        $list =model('agenttixian')
        ->alias('agenttixian')
        ->where('agenttixian.userid',session('agent_id'))
        ->where($where)
        ->join('agentbank agentbank','agenttixian.bankcarid = agentbank.id','LEFT')
        ->join('agent agent','agenttixian.userid = agent.id','LEFT')
        ->field(['agenttixian.*','agentbank.bank_account','agent.phone','agent.username'])
        ->order('agenttixian.id desc')
        ->select()
        :
        $list =model('agenttixian')
        ->alias('agenttixian')
        ->where('agenttixian.userid',session('agent_id'))
        ->where($where)
        ->join('agentbank agentbank','agenttixian.bankcarid = agentbank.id','LEFT')
        ->join('agent agent','agenttixian.userid = agent.id','LEFT')
        ->field(['agenttixian.*','agentbank.bank_account','agent.phone','agent.username'])
        ->order('agenttixian.id desc')
        ->page($page,$limit)
        ->select();
        $statarr = ['初审中','复审中','待打款','已结款','驳回','被删除'];
        foreach($list as $key =>&$val)
        {
            $val['examinetypename'] =  $statarr[$val['examinetype']];
        }

        if($toexcel){
            model("Base")->CreateAgentLog("导出EXCEL","商户后台管理员".session('agent_name')."导出申请提现记录EXCEL");
            $Base_model = model("Base");
			$titary = array('提现列表');
			$list["tit"] = array(
				"username"=>"用户","phone"=>"电话",
				"bank_account"=>"银行卡","addtime"=>"时间","examinetypename"=>"状态",
			);
			$styleary = array(
				"bank_account"=>"30","addtime"=>"20",
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;

        }else{
            $count =model('agenttixian')
            ->alias('agenttixian')
            ->where('agenttixian.userid',session('agent_id'))
            ->where($where)
            ->join('agentbank agentbank','agenttixian.bankcarid = agentbank.id','LEFT')
            ->join('agent agent','agenttixian.userid = agent.id','LEFT')
            ->field(['agenttixian.*','agentbank.bank_account','agent.phone','agent.username'])
            ->count();
    
            $jsonary["code"] = 0;
            $jsonary["count"] = $count;
            $jsonary["data"] = $list;		
            return Json($jsonary);

        }
        
    }
    public function settlement()
    {
        return view('settlement');
    }
    public function getsettlement()
    {
        $page = input('?get.page') ? input('get.page')  : false ; //获得当前页
        $limit = input('?get.limit') ? input('get.limit')  : false ; //获得数量
        $stat = input('?get.stat') ? input('get.stat')  : false ; //获得状态
        $car_license = input('?get.car_license') ? input('get.car_license')  : false ; //获得车牌
        $order_id = input('?get.order_id') ? input('get.order_id')  : false ; //获得订单号
        $username = input('?get.username') ? input('get.username')  : false ; //获得用户名
        $phone = input('?get.phone') ? input('get.phone')  : false ; //获得手机
        $toexcel = input('?get.toexcel') ? input('get.toexcel')  : false ; //是否需要导出EXCEL
        $ids = input("?get.ids")?input("get.ids"):false; //导出EXCEL时候是否存在导出ID
        $where = [];
        $tempwhere = "";

		//获取支付日期
		$awake = input('?get.paytime')?input('get.paytime'):'';
		if($awake){
            $temp = explode(" - ",$awake);
            $where[] = ['exp',"unix_timestamp(paytime) >= unix_timestamp('".$temp[0]."') and unix_timestamp(paytime)<=unix_timestamp('".$temp[1]."') "];
            $tempwhere .= " and unix_timestamp(paytime) >= unix_timestamp('".$temp[0]."') and unix_timestamp(paytime)<=unix_timestamp('".$temp[1]."') ";
			// $where .= " and unix_timestamp(a.awaketime)>=unix_timestamp('".$temp[0]."') and unix_timestamp(a.awaketime)<=unix_timestamp('".$temp[1]."') ";
		}          
        if($stat != '')
        {   
            $where['stat'] = ['in',$stat];
            $tempwhere .= " and stat in (".$stat.")";
        }
        if($ids){
            $where['id'] = ['in',$ids];
            $tempwhere .= " and id in (".$ids.")";
		}
        if($phone)
        {
            $where['phone'] = ['exp','like "%'.$phone.'%"'];
            $tempwhere .= " and phone like %'".$phone."'%";
        }
        if($car_license)
        {
            $where['car_license'] = ['exp','like "%'.$car_license.'%"'];
            $tempwhere .= " and car_license like %'".$car_license."'%";
        }
        if($order_id)
        {
            $where['order_id'] = ['exp','like "%'.$order_id.'%"'];
            $tempwhere .= " and order_id like %'".$order_id."'%";
        }
        if($username)
        {
            $where['username'] = ['exp','like "%'.$username.'%"'];
            $tempwhere .= " and username like %'".$username."'%";
        }
		//获取统计
        $Base_model = model("Base");
        $ordersql = "select count(*) as total,sum(order_fee) as sumtotal,sum(order_fee*byrebate*0.01) as psumtotal from scy_agentorder where 1 ".$tempwhere;
		//获取已完成数量
        $tempsql = $ordersql." and stat=4";
		$temp = $Base_model->query($tempsql);
		$totalary[0]["count"] = $temp[0]["total"]; 
		$totalary[0]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0;  
        $totalary[0]["paysum"] = $temp[0]["psumtotal"]?sprintf("%.2f",$temp[0]["psumtotal"]):0;       
        
        $list = model('agentorder')
        ->where($where)
        ->where('agentid',session('agent_id'))
        ->where('stat',4)
        ->order('id desc')
        ->page($page,$limit)
        ->select();
        $statarr = ['未审核','已审核','已服务','已发起支付','已完成','失效/驳回'];
        foreach($list as $key =>&$val)
        {
            $val['byrebate'] = $val['byrebate'].'%';
            $val['order_fee'] = $val['order_fee'].'￥';
            $val['aftermoney'] = $val['order_fee']-$val['order_fee']*$val['byrebate']/100 .'￥';
            $val['stat'] =  $statarr[$val['stat']];
            
        }

        if($toexcel){
            model("Base")->CreateAgentLog("导出EXCEL","商户后台管理员".session('agent_name')."导出资金明细EXCEL");
            $Base_model = model("Base");
			$titary = array('资金明细');
			$list["tit"] = array(
				"order_id"=>"订单号","username"=>"用户","car_license"=>"车牌","phone"=>"电话",
				"order_fee"=>"抽成前","aftermoney"=>"抽成后","byrebate"=>"当时比例","paytime"=>"付款时间","stat"=>"状态"
			);
			$styleary = array(
				"order_id"=>"30","paytime"=>"20",
			);
			$Base_model->Toexcel($titary,$list,$styleary);	
			exit;

        }else{
            $count = model('agentorder')
            ->where($where)
            ->where('agentid',session('agent_id'))
            ->where('stat',4)
            ->page($page,$limit)
            ->count();
    
            $jsonary["code"] = 0;
            $jsonary["count"] = $count;
            $jsonary["data"] = $list;	
            $jsonary["totaldata"] = $totalary;		
            return Json($jsonary);

        }
    }
    

}

    
     
    
