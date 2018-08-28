<?php
namespace app\insurer\controller;
use think\controller;
class Index  extends Common
{
    public function index()
    {
        return view('index');
    }
    public function header()
    {
        return view('header');
    }
    /**
     * $lastid order表id 取top用于轮询 第二次开始有
     * $wfhnum 未发货统计，检测总数有无变化
     * $wcdnum 未出单统计，检测总数有无变化
     * $order 实时数据
     * $res Json返回
     * $wfh_count 首页未发货数量 未发货->状态=4&为本家保险公司&单号为空
     * $wcd_count 首页未出单数量 未发货->状态>4为本家保险公司
     */
    public function getInsurerInfo()
    {
        $lastid = input('?get.lastid')?input('get.lastid'):false;
        $wfhnum = input('?get.wfhnum')?input('get.wfhnum'):false;
        $wcdnum = input('?get.wcdnum')?input('get.wcdnum'):false;
        $keywords = input('?get.keywords')?input('get.keywords'):false;
        $order_where = [];
        if($lastid){
            $order_where['order.id'] = ['>',$lastid];
        }
        if($keywords){
            $order_where['order.car_license|order.car_name'] = ['like',$keywords];
        }
        $order = model('userorder')
            ->alias('order')
            ->field('order.id,order.car_license,order.checktime,order.car_name,order.rs,user.wxavatar')
            ->where('stat','1')
            ->where('order.company',"exp"," in (select type from scy_insurer where id=".session('insurer_id').")")
            ->where($order_where)
            ->order('id desc')
            ->join('user user','user.id = order.userid','LEFT')
            ->select();
        foreach($order as $key=>&$val){
            $temp= explode("_",$val['rs']);//获取保单编号
            $order[$key]['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
            $val['checktime'] = date('Y年m月d日H时i分s秒',strtotime($val['checktime']));
        }
        $wfh_count = model('order')
        ->where('insurerid',session('insurer_id'))
        ->where('stat','4')
        ->where('express_id','')
        ->count();

        // $wcd_count = model('order')
        //     ->where('insurerid',session('insurer_id'))
        //     ->where('stat','3')
        //     ->count();

        $wcd_count = model('userorder')
        ->alias('a')
        ->join('order b','a.rs = b.rs','LEFT')
        ->where('offerinsurerid',session('insurer_id'))
        ->where('a.stat',2)
        ->where('b.stat',"<","4")
        ->order('a.id desc')
        ->count();
           
        $res['main'] = $order;
        $res['dbz_count'] = count($order);
        $res['wfh_count'] = $wfh_count == $wfhnum ? 0 : $wfh_count; 
        $res['wcd_count'] = $wcd_count == $wcdnum ? 0 : $wcd_count;
        return json($res);
    }
    
}