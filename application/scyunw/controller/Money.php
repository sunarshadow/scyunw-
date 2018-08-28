<?php
namespace app\scyunw\controller;
use think\Request;
use think\Db;
use app\common\model;

class Money extends Common
{
    //添加平台资金流水
    public function add(){
        $type = input("get.type");
        return view("add",["type"=>$type]);
    }
    //查看平台资金流水
    public function info(){
        return view("info");
    }
    //应收保费流水统计,type=0
    public function order(){
        return view("order");
    }
    //应收商户明细流水,type=1
    public function agentshou(){
        return view('agentshou');
    }
    //应收保险公司佣金流水,type=2
    public function insurershou(){
        return view('insurershou');
    }
    //平台接入,type=3
    public function borrow(){
        return view("borrow");
    }
    //红包明细,type=4
    public function redfu(){
        return view("redfu");
    }
    //平台支付,type=5
    public function pay(){
        return view("pay");
    }
    //应付用户资金流水,type=6
    public function userfu(){
        return view("userfu");
    }
    //应付商户明细流水,type=7
    public function agentfu(){
        return view('agentfu');
    }

}