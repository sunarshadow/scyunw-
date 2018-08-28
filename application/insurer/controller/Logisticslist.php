<?php
namespace app\insurer\controller;
use think\Controller;
class Logisticslist  extends Common
{
    public function index()
    {
        $express_id = input('?get.express_id')?input('get.express_id'): false;
        $keywords = input('?get.keywords')?input('get.keywords'): false;
        $radio = input('?get.radio')?input('get.radio'): false;
        $where = [];
        if($keywords){
            if($radio == 'ordernumber')
            {  
                $dechex = substr($keywords,14);
                $datestr = substr($keywords,0,14);
                $keywords = $datestr.'_'.dechex($dechex);
            }
            if($radio == 'codetime')
            {  
                $where["unix_timestamp(order.addtime)"] = ['exp'," >= unix_timestamp('$keywords')"];
            }else{
                $where[$radio] = ['like',$keywords];
            }
        }
        switch ($express_id) {
            case 'wei':
                $where['order.express_id'] = ['=',''];
                break;
            case 'dai':
                $where['order.express_id'] = ['<>',''];
                $where['order.express_gettime'] = ['exp','is null'];
                break;
            case 'shou':
                $where['order.express_gettime'] = ['exp','is not null'];
                break;
        }
        $list = model('order')
        ->alias('order')
        ->join('userorder userorder','order.rs = userorder.rs','RIGHT')
        ->join('admin admin','admin.id = userorder.checkadminid')
        ->where('order.stat','4')
        ->where('insurerid',session('insurer_id'))
        ->where($where)
        // ->fetchSql(true)
        ->paginate(2,false,[
            'query' => array('express_id'=>$express_id),
            'type'      => 'page\Page',//分页类  
            'var_page'  => 'page',  
            ]);

        foreach($list as $key=>&$val){
            $temp= explode("_",$val['rs']);//获取保单编号
            $list[$key]['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
            $list[$key]['codetime'] = $val['checktime'];
        }
        return view('logisticslist',['list'=>$list,'express_id'=>$express_id,'keywords'=>$keywords,'radio'=>$radio]);
    }
    public function logisticsdetail(){
        $rs = input('?get.rs')?input('get.rs'): false;
        $order = model('order')
        ->where('rs',$rs)
        ->find();
        return view('logisticsdetail',['order'=>$order]);
    }
    public function logisticslistsave(){
        $rs = input('?get.rs')?input('get.rs'): false;
        $orderinfo = model('userorder')
            ->where('rs',$rs)
            ->find();
        $useraddress = getAddressByuserid($orderinfo->userid,1);
        $temp= explode("_",$rs);//获取保单编号
        $orderinfo['ordernumber'] = $temp[0].hexdec($temp[1]);//获取保单时间
        $orderinfo['addtime'] = date('Y年m月d日H时i分s秒',strtotime($orderinfo['addtime']));
        return view('logisticslistsave',['info'=>$orderinfo,'rs'=>$rs,'useraddress'=>$useraddress]);
    }
    //修改
    public function update()
    {
        $rs = input('?post.rs')?input('post.rs'): false;
        $order = model('order')->where('rs',$rs)->find();
        if(!input('post.express_id')){return $this->error('请填写快递订单号');}
        if(!input('post.express_phone')){return $this->error('请填写收货人电话号码');}
        // $order ->express_address = input('?post.express_address')?input('post.express_address'): false;
        // $order ->express_id = input('?post.express_id')?input('post.express_id'): false;
        // $order ->express_phone = input('?post.express_phone')?input('post.express_phone'): false;
        // $order ->orderpics = json_encode(input('post.orderpics'));
        // $order ->express_time = date("Y-m-d H:i:s");

        $update["express_address"] = input('?post.express_address')?input('post.express_address'): false;
        $update["express_id"] = input('?post.express_id')?input('post.express_id'): false;
        $update["express_phone"] = input('?post.express_phone')?input('post.express_phone'): false;
        $update["express_company"] = input('?post.express_company')?input('post.express_company'): false;
        $update["orderpics"] = json_encode(array("orderpic"=>input('post.orderpic'),"expresspic"=>input('post.expresspic'),"otherpic"=>input('post.otherpic')));
        $update["express_time"] = date("Y-m-d H:i:s");

        $result = model("order")->where("rs",$rs)->update($update);

        if($result){
            model("Base")->CreateInsurerLog("修改","保险后台管理员".session('insurer_name')."修改了快递信息");
            $order = model('userorder')->where('rs',$rs)->find();
			$tempdata = array(
				$order["apply_phone"],//手机号码0
				$order["car_name"],//姓名1
				$order["car_license"], //车牌号码2
				input('post.express_company'),//快递公司3
                input('post.express_id'),//快递单号4
                $order["id"]
			);			
			$result = model("message")->getmsg("expresssend",$tempdata);//消息相关处理   
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
    }
    
    

}