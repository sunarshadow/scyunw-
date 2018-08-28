<?php
namespace app\agent\controller;
use think\Controller;
use app\common\model;
class Business extends Common
{
    public function index()
    {
        $ordertype = model("agentclass")->select();
        $istoday = input('?get.istoday') ? input('get.istoday')  : false ; //获得用户名
        $stat = input('?get.stat') ? input('get.stat')  : false ; //获得用户名
        return view('Business/index',['istoday'=>$istoday,'stat'=>$stat,'ordertype'=>$ordertype]);
    }

	//商户预约信息查看
	public function likebespoke(){

		$id = input('get.id') ? input('get.id') : exit('缺少ID');
		$stat = ['未审核','已审核','已服务','已发起支付','已完成','失效/驳回'];
		$data = model('agentorder')->getById($id);
		$data->agent = model('agent')->getById($data->agentid)->company;
		$data->isNewcar =  $data->isNewcar == 1 ? '是' : '否';
		$data->stat = $stat[$data->stat];
		$data->order_type = model('agentclass')->getById($data->order_type)->name;
		return view('likebespoke',['data'=>$data]);
    }
    //获取服务    
    public function getserver()
    {
        $stat = input('?get.stat') ? input('get.stat')  : false ; //获得状态
        $page = input('?get.page') ? input('get.page')  : false ; //获得当前页
        $limit = input('?get.limit') ? input('get.limit')  : false ; //获得数量
        $order_type = input('?get.order_type') ? input('get.order_type')  : false ;  //获得服务类型
        $phone = input('?get.phone') ? input('get.phone')  : false ; //获得电话
        $car_license = input('?get.car_license') ? input('get.car_license')  : false ; //获得车牌
        $username = input('?get.username') ? input('get.username')  : false ; //获得用户名
        $istoday = input('?get.istoday') ? input('get.istoday')  : false ; //获得用户名
        $toexcel = input('?get.toexcel') ? input('get.toexcel')  : false ; //是否需要导出EXCEL
        $ids = input("?get.ids")?input("get.ids"):false; //导出EXCEL时候是否存在导出ID
        $where = [];
        $tempwhere = "";
		//获取预约日期
		$awake = input('?get.bespeaktime')?input('get.bespeaktime'):'';
		if($awake){
            $temp = explode(" - ",$awake);
            $where[] = ['exp',"unix_timestamp(bespeaktime) >= unix_timestamp('".$temp[0]."') and unix_timestamp(bespeaktime)<=unix_timestamp('".$temp[1]."') "];
            $tempwhere .= " and unix_timestamp(bespeaktime) >= unix_timestamp('".$temp[0]."') and unix_timestamp(bespeaktime)<=unix_timestamp('".$temp[1]."') ";
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
        if($order_type)
        {
            $where['order_type'] = $order_type;
            $tempwhere .= " and order_type = '".$order_type."'";
        }
        if($car_license)
        {
            $where['car_license'] = ['exp','like "%'.$car_license.'%"'];
            $tempwhere .= " and car_license like %'".$car_license."'%";
        }
        if($username)
        {
            $where['username'] = ['exp','like "%'.$username.'%"'];
            $tempwhere .= " and username like %'".$username."'%";
        }
        if($istoday == 'today'){
            $where["bespeaktime"] = ['exp','  = to_days(now())'];
            $where[] = ['exp',' to_days(bespeaktime) = to_days(now())'];
        }
        if($istoday == 'histry'){
            // $where["bespeaktime"] = ['exp','  = to_days(now())'];
            // $where[] = ['exp',' to_days(bespeaktime) = to_days(now())'];
        }
        $where["agentid"] = session('agent_id');
        $tempwhere .= " and agentid = '".session('agent_id')."'";
		//获取统计
        $Base_model = model("Base");
        $ordersql = "select count(*) as total,sum(order_fee) as sumtotal from scy_agentorder where 1 ".$tempwhere;
		//获取未审核数量
        $tempsql = $ordersql." and stat=0";
		$temp = $Base_model->query($tempsql);
		$totalary[0]["count"] = $temp[0]["total"]; 
		$totalary[0]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		//获取服务中数量
        $tempsql = $ordersql." and stat>0 and stat<4";
		$temp = $Base_model->query($tempsql);
		$totalary[1]["count"] = $temp[0]["total"]; 
		$totalary[1]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
		//获取已完成数量
        $tempsql = $ordersql." and stat=4";
		$temp = $Base_model->query($tempsql);
		$totalary[2]["count"] = $temp[0]["total"]; 
		$totalary[2]["sum"] = $temp[0]["sumtotal"]?$temp[0]["sumtotal"]:0; 
      
        
        // $sql = model('agentorder')
        // ->where($where)
        // ->page($page,$limit)
        // ->order('id desc')
        // ->fetchSql(True)->find(1);;
        // print_r($sql);
        $toexcel ? 
        $userorder = model('agentorder')
        ->where($where)
        ->order('id desc')
        ->select()
        : 
        $userorder = model('agentorder')
        ->where($where)
        ->page($page,$limit)
        ->order('id desc')
        ->select();

        // print_r($where);exit;
        $statarr = ['未审核','已审核','已服务','已发起支付','已完成','失效/驳回'];
        foreach($userorder as $key =>&$val)
        {
            $val['statname'] =  $statarr[$val['stat']];
            $val['order_type'] =  getagentclass($val['order_type']);
        }

        if($toexcel){
            model("Base")->CreateAgentLog("导出EXCEL","商户后台管理员".session('agent_name')."导出EXCEL");
            $title = '';
            if($stat == '1'){$title = '预约成功订单数据--'. date('Y-m-d');}
            if($stat == '5'){$title = '预约失败订单数据--'. date('Y-m-d');}
            if($istoday == 'today'){$title = date('Y-m-d').'今日订单数据';}
            if($istoday == 'histry'){$title = date('Y-m-d').'历史订单数据';}
            $Base_model = model("Base");
			$titary = array($title);
			$userorder["tit"] = array(
				"username"=>"用户","car_license"=>"车牌","phone"=>"电话",
				"order_type"=>"项目","bespeaktime"=>"时间","statname"=>"状态",
			);
			$styleary = array(
				"bespeaktime"=>"20",
			);
			$Base_model->Toexcel($titary,$userorder,$styleary);	
			exit;
        }else{
            $count = model('agentorder')
            ->where($where)
            ->count();
            $jsonary["code"] = 0;
            $jsonary["count"] = $count;
            $jsonary["data"] = $userorder;	
            $jsonary["totaldata"] = $totalary;		
            return Json($jsonary);
        }
        
    }
    public function toexamine()
    {
        $id = input('?post.id') ? input('post.id')  : exit($this->error('无效ID')) ; //获得状态\
        $userorder = model('agentorder')->get($id);
        input('?post.code')?$userorder->stat = 5 :$userorder->stat = 1;
        if($userorder->save()){
            if(input('?post.code') ){
                model("Base")->CreateAgentLog("驳回","商户后台管理员".session('agent_name')."驳回了预约");
                $agentclass = model('agentclass')->getById($userorder->order_type);//得到预约服务类型
                $agent = model('agent')->getById($userorder->agentid);//得到商户

                $msg = input('?post.msg');
                $data =[
                    $userorder->apply_phone,//手机号
                    $userorder->realname,//姓名
                    $userorder->car_license,//车牌
                    $agentclass->name,//服务
                    $userorder->bespeaktime,//时间
                    $agent->address,//5预约地点
                    $msg,//6驳回理由
                ];
                model('message')->getmsg('bespeak_error',$data);//数据备注->0:手机号,1:姓名,2:车牌,3:服务,4:时间,5预约地点,6驳回理由
                return $this->success('驳回成功');
            }else{
                model("Base")->CreateAgentLog("审核","商户后台管理员".session('agent_name')."通过了预约");
                return $this->success('审核成功');
            }
        }else{
            if(input('?post.code') ){
                return $this->success('驳回失败');
            }else{
                model("Base")->CreateAgentLog("审核","商户后台管理员".session('agent_name')."通过了预约");
                return $this->success('审核失败');
            }
        }

    }
}