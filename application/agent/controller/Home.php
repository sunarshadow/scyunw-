<?php
namespace app\agent\controller;
use think\Controller;
use think\Loader;
class Home extends Common
{
    //输入预约号视图
    public function index() {
        $code = input("get.code");
        $oauth = & load_wechat('Oauth');
        $result = $oauth->getWebOauthAccessToken($code);
        $unionid = $result["unionid"];

        $where["userid"] = array("exp"," in (select id from scy_user where wxunionid='".$unionid."')");
        $where["stat"] = 1;
        $agentorder = model("agentorder")->where($where)->find();
        
        //查询预约单号
        return view('index',['step'=>1,"agentorder"=>$agentorder]);
    }
    //选择车辆视图
    public function selectcar() {
        //查询预约单号
        $order_id = input('get.order_id') ?  input('get.order_id') : ''; //获得预约号
        $agentorder = model('agentorder')
            ->where('order_id',$order_id)
            ->where('agentid',session('agent_id'))
            ->find();
        //用户信息
        
        $user = model('user')
            ->where('id',$agentorder->userid)
            ->find();

        session('order_id',$order_id); //保存一下用户订单号
        session('caruserid',$agentorder->userid); //保存一下用户ID
        
        return view('Home/index',['step'=>2,'agentorder'=>$agentorder,'user'=>$user]);
    }
    //选择自己
    public function myself() {
        
        //查询预约单号
        $order_id = session('order_id'); //获取预约号
        $agentorder = model('agentorder')
            ->where('order_id',$order_id)
            ->where('agentid',session('agent_id'))
            ->find();
        //用户自身信息
        $user = model('user')
            ->where('id',$agentorder->userid)
            ->find();
        $agentclass = model('agentclass')->where("id",$agentorder["order_type"])->find();//获取业务类型
        
        return view('Home/index',['step'=>3,'agentorder'=>$agentorder,'agentclass'=>$agentclass,'user'=>$user,'carby'=>'self']);
    }
    //支付
    public function pay(){
        //查询预约单号
        $order_id = session('order_id'); //获取预约号
        $agentorder = model('agentorder')
            ->where('order_id',$order_id)
            ->where('agentid',session('agent_id'))
            ->find();
        //用户自身信息
        $user = model('user')
            ->where('id',$agentorder->userid)
            ->find();
        $isselfcar = $user["carlicense"]==$agentorder["car_license"]?1:0;
        return view('Home/index',['step'=>4,'agentorder'=>$agentorder,'user'=>$user,'carby'=>'pay','isselfcar'=>$isselfcar]);
    }
    //发起支付
    public function payreturn(){
        $str = "已成功发起支付，请等待支付。";
        return view('Home/index',['step'=>5,'str'=>$str]);
    }
    //确认支付
    public function payconfirm(){
        //获取订单信息
        $agentorder = model('agentorder')->where('order_id',session('order_id'))->where('agentid',session('agent_id'))->find();
        if($agentorder["paystat"]){
            $str = "已完成支付!";
            return view('Home/index',['step'=>6,'str'=>$str]);
        }else{
            $str = "尚未支付成功，请等待支付。";
            return view('Home/index',['step'=>5,'str'=>$str]);
        }
    }

    public function ajax_business() {
        $jx = input('post.jx') ?  input('post.jx') : exit($this->error('参数错误'));
        $step = input('get.step') ?  input('get.step') : 1;
        switch ($jx) {
            //输入订单号
            case 'create':
                $ordercode = input('post.ordercode') ?  input('post.ordercode') : exit($this->error('订单号错误')); //获取预约号
                //预约号查询数据库
                $agentorder = model('agentorder')
                ->where('order_id',$ordercode)
                ->where('agentid',session('agent_id'))
                ->find();
                $agentorder ? session('order_id',$ordercode) : exit($this->error('无此订单号')); //session存订单号
                if($agentorder["stat"]==0){exit($this->error('预约订单未审核'));}
                return  $this->success('搜索完成',url('Home/selectcar',['order_id'=>$ordercode,]));
                break;

            //选择本人或者他人车辆 用self和other区分自己或者他人车辆
            //自己车辆选择短信验证    他人直接付款
            case 'getcarinfo':
                $card = input('post.card') ? input('post.card') : exit($this->error('类型错误')); // 1为自己  3为他人
                if($card == 1)
                {
                    return  $this->success('车辆确认成功',url('Home/myself'));

                }elseif($card == 3){
                    //选择其他车辆， 确认后直接跳转支付
                    $cardid = input('post.cardid') ?  input('post.cardid') : exit($this->error('请输入车牌')); // 获取车牌号
                    $car = model('agentorder')->where('car_license',$cardid)->where('agentid',session('agent_id'))->find();//找到填写的车牌
                    if($car){
                        return $this->success('车辆确认成功',url('Home/pay',['car_license'=>$cardid]));//带上车牌
                    }else{
                        return $this->error('该车辆不存在或不是在此预约');
                    }
                    
                }
                break;
            case 'dobusiness':
                $phone = input('post.phone') ?  input('post.phone') : exit($this->error('请填写电话'));
                $smscode = input('post.smscode') ?  input('post.smscode') : exit($this->error('请填写验证码'));
                //找到yu
                $order_id = session('order_id'); //获取预约号

                $agentorder = model('agentorder')
                ->where('order_id',$order_id)
                ->where('agentid',session('agent_id'))
                ->find();

                if($agentorder->phone !=$phone) { exit($this->error('预约手机号错误'));}
                $result = model("Base")->smscheck($phone,$smscode); //判断验证码
                if($result!=1){exit($this->error('验证码错误'));}
                $update["stat"] = 2;
                $result = model("agentorder")->where("order_id",$order_id)->update($update);
                if($result!=1&&$agentorder["stat"]==1){exit($this->error('服务超时，请重新输入订单号'));}
                //检查结束跳转到付款
                return $this->success('用户确认成功',url('Home/pay'));
                break;
            case "payresult":
                $post = input("post.");
                $order_id = session('order_id'); //获取预约号
                $order = model("agentorder")->where("order_id",$order_id)->find()->toArray();
                $update["order_fee"] = input('post.order_fee') ? $post["order_fee"] : exit($this->error('请填写金额'));
                $update["stat"] = 3;
                $result = model("agentorder")->where("order_id",$order_id)->update($update);
                //业务处理完成
                return $this->success('发起支付成功',url('Home/payreturn'));
                break;
            case "payconfirm":
                $post = input("post.");
                //业务处理完成
                return $this->success('支付成功',url('Home/payconfirm'));
                break;
            default:
                # code...
                break;
        }
        return view('index',['step'=>$step]);
    }
}



