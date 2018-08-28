<?php
namespace app\insurer\controller;
use think\Controller;
class Financenter  extends Common
{
    public function index()
    {
        $financepwd = input('?post.financepwd')?input('post.financepwd'): false;
        $insurer = model('insurer')->where('id',session('insurer_id'))->find();
        
        if($insurer && $insurer ->financepwd == md5($financepwd))
        {
            session('insurer_financepwd',$financepwd);
        }else{
            $where['order.stat'] = 4;
            $count = model('order')
            ->alias('order')
            ->join('userorder userorder','order.rs = userorder.rs','RIGHT')
            ->join('admin admin','admin.id = userorder.checkadminid')
            ->where('insurerid',session('insurer_id'))
            ->where($where)
            ->count();
            $sum = model('order')
            ->alias('order')
            ->join('userorder userorder','order.rs = userorder.rs','RIGHT')
            ->join('admin admin','admin.id = userorder.checkadminid')
            ->join('insurer insurer','insurer.id = order.insurerid')
            ->where('insurerid',session('insurer_id'))
            ->where($where)
            ->sum('userorder.jqprice * insurer.jqxrate/100 + userorder.csprice * insurer.csxrate/100 + userorder.syprice * insurer.xyxrate/100'); 
            $moneyok = model('insurertx')
            ->where('insurerid',session('insurer_id'))
            ->sum('amount'); 
            $data['count'] = $count;
            $data['sum'] = $sum;
            $data['moneyok'] = $moneyok;
            return view('financenter',['data'=>$data]);
            
        }
        $this->redirect('financenter/index');
    }
    public function financelist()
    {
        $keywords = input('get.keywords')?input('get.keywords'): false;
        $radio = input('get.radio')?input('get.radio'): false;
        $where = [];
        if($keywords){
            if($radio == 'paytime' || $radio == 'addtime'){
                $where[] = ['exp',"unix_timestamp($radio) >= unix_timestamp('$keywords')"];
            }else{
                $where[$radio] = $keywords;
            }
        }
        
        $list = model('insurertx')
        ->where('insurerid',session('insurer_id'))
        ->where($where)
        ->paginate(10,false,[
            // 'query' => array('stat'=>$stat),
            'type'      => 'page\Page',//分页类  
            'var_page'  => 'page',  
            ]);
        return view('financelist',['list'=>$list,'keywords'=>$keywords,'radio'=>$radio]);
    }
    public function finanpayfor()
    {
        return view('finanpayfor');
    }
    public function insertData()
    {
        $knotman    = input('?post.knotman')    ? input('post.knotman')    : exit($this->error('结款人错误'));
        $bank       = input('?post.bank')       ? input('post.bank')       : exit($this->error('银行名错误'));
        $bankcarnub = input('?post.bankcarnub') ? input('post.bankcarnub') : exit($this->error('银行卡号错误'));
        preg_match('/^([1-9]{1})(\d{14}|\d{18})$/', $bankcarnub) ? true : exit($this->error('银行卡号错误')); //正则判断银行卡
        $amount     = input('?post.amount') &&    input('post.amount') > '0'  ? input('post.amount/f')     : exit($this->error('结款金额错误'));
        $paytime    = input('?post.paytime')    ? input('post.paytime')    : exit($this->error('打款时间错误'));
        $imgurl     = input('?post.imgurl')     ? input('post.imgurl')     : exit($this->error('图片上传失败'));
        $ordernumber     = date("YmdHis").mt_rand("1000","9999");
        $img = model('base')->saveBase64Image($imgurl);
        $insurertx = model('insurertx');
        $insurertx->knotman = $knotman;
        $insurertx->bank = $bank;
        $insurertx->bankcarnub = $bankcarnub;
        $insurertx->amount = $amount;
        $insurertx->paytime = $paytime;
        $insurertx->imgurl = $img['url'];
        $insurertx->addtime = date('Y-m-d H:i:s');
        $insurertx->knotman = $knotman;
        $insurertx->ordernumber = $ordernumber;
        $insurertx->insurerid = session('insurer_id');
        if($insurertx->save()){
            model("Base")->CreateInsurerLog("结款","保险后台管理员".session('insurer_name')."结款了".$amount."元");
            return $this->success('提交成功');
        }else{
            return $this->error('提交失败');
        }
    }
    // public function ceshi(){
    //     $str = "6217001930012283686";
    //     if (preg_match('/^([1-9]{1})(\d{14}|\d{18})$/', $str)) { 
    //         echo "验证成功";} else { 
    //         echo "验证失敗";}
    //     // 
    // }
    

}