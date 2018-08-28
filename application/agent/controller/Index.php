<?php
namespace app\agent\controller;
use think\Controller;

class Index extends Common
{
    public function index()
    {
        return view('index/index');
    }
    public function main()
    {
       
        return view('index/main');
    }
    public function navlist()
    {
        
        $jsonpath = ROOT_PATH ."public/static/shopadmin/json/agentnav.json";
        $json_string = file_get_contents($jsonpath);  
        return json($json_string);
    }
    public function _empty()
    {
        return 'API错误，请核对参数';
    }


    //获取累积总数
    public function gettotal(){
        $date = date("Y-m-d");
        //获取预约订单数
        $sql = "select count(*) as total from scy_agentorder where stat=0 and agentid=".session('agent_id');
        $total = model("Base")->query($sql);
        $totary["ordersum"] = $total[0]["total"]?$total[0]["total"]:0;
        return json_encode($totary);
    }    
}
