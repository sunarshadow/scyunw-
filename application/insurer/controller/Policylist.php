<?php
namespace app\insurer\controller;
use think\Controller;
use think\Paginator;
class Policylist extends Common
{
    public function index()
    {
        $stat = input('?get.stat')?input('get.stat'): 1;
        $keywords = input('?get.keywords')?input('get.keywords'): false;
        $radio = input('?get.radio')?input('get.radio'): false;
        $where = [];
        
        if($keywords){
            if($radio == 'ordernumber')
            {  
                $radio = 'userorder.rs';
                $dechex = substr($keywords,14);
                $datestr = substr($keywords,0,14);
                $keywords = $datestr.'_'.dechex($dechex);
            }
            if($radio == 'codetime'){
                $where[] = ['exp',"unix_timestamp(userorder.checktime) >= unix_timestamp('$keywords')"];
            }else{
                $where[$radio] = ['like',$keywords];
            }
            
        }
     
        if($stat != false){
            if($stat == 1 || $stat == 2){ //未报价 尚未出订单 询价状态：0:未初审，1:初审,未报价，2:已报价，3:已驳回.
                //订单号 rs	车主姓名 car_name	身份证 id_code	车牌号 car_license	行驶证 car_code	操作人	创建时间	操作
                //未报价 只有userorder 无order 
                //已报价 只有userorder 无order 
                //已出单 审核已经结束  有userorder 也有order
                //失效 过期
                
                $where['userorder.stat'] = ['in',$stat];
                if($stat == 1){
                    $where["company"] = array("exp","in (select type from scy_insurer where id=".session('insurer_id').")");
                }else if($stat == 2){
                    $where["offerinsurerid"] = session('insurer_id');
                }
                $list = model('userorder')
                ->alias('userorder')
                ->where($where)
                ->order('userorder.id desc')
                ->join('user user','user.id = userorder.userid','LEFT')
                // ->join('admin admin','admin.id = userorder.checkadminid')
                ->field(['user.*','userorder.*'])
                ->paginate(10,false,[
                    'query' => array('stat'=>$stat),
                    'type'      => 'page\Page',//分页类  
                    'var_page'  => 'page',
                    ]);
            }
            
            if($stat > 2 && $stat){
                $where['order.stat'] = ['in',$stat];
                $list = model('order')
                ->alias('order')
                ->join('userorder userorder','order.rs = userorder.rs','RIGHT')
                ->join('admin admin','admin.id = userorder.checkadminid')
                ->where('insurerid',session('insurer_id'))
                ->where($where)
                ->paginate(10,false,[
                    'query' => array('stat'=>$stat),
                    'type'      => 'page\Page',//分页类  
                    'var_page'  => 'page',  
                    ]);
            }
            
        }
        foreach($list as $key=>&$val){
            $temp= explode("_",$val['rs']);//获取保单编号
            $list[$key]['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
            $list[$key]['codetime'] = $val['checktime'];
        }
        $insurer = model('insurer')->get(session('insurer_id'));
        // dump($insurer);
        // exit;
        return view('policylist',['list'=>$list,'insurer'=>$insurer,'stat'=>$stat,'radio'=>$radio,'keywords'=>$keywords]);
    }
    public function policywrite(){
        $rs = input('?get.rs')?input('get.rs'): false;
        $orderinfo = model('userorder')
            ->where('rs',$rs)
            ->find();
            // dump($orderinfo);
        $temp= explode("_",$rs);//获取保单编号
        $insurance = model('base')->GetInsurance($rs,"<td class=\"text-r\">","</td>");
        $orderinfo['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
        $orderinfo['addtime'] = date('Y年m月d日H时i分s秒',strtotime($orderinfo['addtime']));
        return view('policywrite',['info'=>$orderinfo,'insurance'=>$insurance,'rs'=>$rs]);
        
    }
    public function policywritefinish(){
        $rs = input('?get.rs')?input('get.rs'): false;
        $cqprice = input('?get.cqprice')?input('get.cqprice'): false;
        $syprice = input('?get.syprice')?input('get.syprice'): false;
        $jqprice = input('?get.jqprice')?input('get.jqprice'): false;
        $awaketime = input('?get.awaketime')?input('get.awaketime'): false;
        $price =[
            'cqprice'=>$cqprice,'syprice'=>$syprice,'jqprice'=>$jqprice,'awaketime'=>$awaketime
        ];
        
        $orderinfo = model('userorder')
            ->where('rs',$rs)
            ->find();
        $temp= explode("_",$rs);//获取保单编号
        $insurance = model('base')->GetInsurance($rs);
        $orderinfo['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
        $orderinfo['addtime'] = date('Y年m月d日H时i分s秒',strtotime($orderinfo['addtime']));
        return view('policywritefinish',['info'=>$orderinfo,'insurance'=>$insurance,'rs'=>$rs,'price'=>$price]);
    }
    //修改
    public function update()
    {
        $rs = input('?post.rs')?input('post.rs'): false;
        $userorder = model('userorder')->where('rs',$rs)->find();
        if($userorder ->stat == 1) $userorder ->stat = 2;
        $userorder ->jqprice = input('?post.jqprice')?input('post.jqprice'): false;
        $userorder ->syprice = input('?post.syprice')?input('post.syprice'): false;
        $userorder ->csprice = input('?post.cqprice')?input('post.cqprice'): false;
        $userorder ->order_price = input('?post.allmoney')?input('post.allmoney'): false;
        $userorder ->offerinsurerid = session('insurer_id');
        $userorder ->offertime = date('Y-m-d H:i:s');
        if($userorder -> save()){
            model("Base")->CreateInsurerLog("报价","保险后台管理员".session('insurer_name')."报价了订单号为".$rs."的订单");
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
        // return $userorder -> save() !==false ? $this->success('修改成功') : $this->error('修改失败');
    }
    //查看
    public function policyuselessdetail()
    {
        $rs = input('?get.rs')?input('get.rs'): false;
        $orderinfo = model('userorder')
            ->where('rs',$rs)
            ->find();
        $temp= explode("_",$rs);//获取保单编号
        $insurance = model('base')->GetInsurance($rs);
        $orderinfo['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
        $orderinfo['addtime'] = date('Y年m月d日H时i分s秒',strtotime($orderinfo['addtime']));
        return view('policyuselessdetail',['info'=>$orderinfo,'insurance'=>$insurance,'rs'=>$rs]);
    }
    public function policycreate()
    {
        $rs = input('?get.rs')?input('get.rs'): false;
        $orderinfo = model('userorder')
            ->where('rs',$rs)
            ->find();
        $temp= explode("_",$rs);//获取保单编号
        $insurance = model('base')->GetInsurance($rs);
        $orderinfo['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
        $orderinfo['addtime'] = date('Y年m月d日H时i分s秒',strtotime($orderinfo['addtime']));
        return view('policycreate',['info'=>$orderinfo,'insurance'=>$insurance,'rs'=>$rs]);
    }
    public function updatetime()
    {
        $rs = input('?post.rs')?input('post.rs'): $this->error('保单错误');
        $awaketime = input('?post.awaketime')?input('post.awaketime'): $this->error('生效时间错误');
        $ordernumber = input('?post.ordernumber')?input('post.ordernumber'): $this->error('请输入正确的保险单号');
        
        $order = model('order')
        ->where('rs',$rs)
        ->find();
        $order->checktime = date('Y-m-d H:i:s');
        $order->awaketime = date('Y-m-d H:i:s',strtotime($awaketime));
        $order->awakedate = date('Y-m-d',strtotime($awaketime));
        $order->ordernumber = $ordernumber;
        $order->stat      = 4;
        if($order -> save()){
            model("Base")->CreateInsurerLog("出单","保险后台管理员".session('insurer_name')."出单了订单号为".$rs."的订单");
            return $this->success('出单成功');
        }else{
            return $this->error('出单失败');
        }
        // $order->save() ? $this->success('出单成功') :$this->error('出单失败');
    }
    

    
}