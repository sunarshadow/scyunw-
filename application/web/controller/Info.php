<?php
namespace app\web\controller;
use think\Controller;
use think\Session;
class Info extends Common
{
    //加盟合作
    public function join()
    {
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $list = model('servercase')->where('status',1)->order('id desc')->paginate(3);
        foreach($list as $key=>&$val){
            $val['main']=cutstr_html($val['main']);
        }
        $page = $list->render();
        return view('join',["user" => $user,"list" => $list,'page'=>$page]);
    }
    //查看服务案例
    public function lookservercase()
    {
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $id = input('get.id') ? input('get.id') : $this->redirect('index/index');
        $list = model('servercase')->getById($id);
        return view('lookservercase',["user" => $user,"list" => $list]);
    }

    //加入我们
    public function joinus()
    {
        $id = input('get.id') ? input('get.id') : 1;
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);

        $listtitle = model('joinus')->field('id,title')->where('status',1)->select();
        $list = model('joinus')->where('status',1)->where('id',$id)->find();
        return view('joinus',["user" => $user,"list"=>$list,"id"=>$id,"listtitle"=>$listtitle]);
    }
    //公司简介
    public function aboutus()
    {
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);

        $list = model('aboutus')->where('title','公司简介')->find();
        return view('aboutus',["user" => $user,"list"=>$list]);
    }
    //咨询中心
    public function consult()
    {
        $id = input('get.id') ? input('get.id') : 1;
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
       
        $listtitle = model('consult')->field('id,title')->where('status',1)->select();
        $list = model('consultmain')->where('status',1)->where('rid',$id)->order('id desc')->paginate(3,false,[
            'query'     => [
                'id'=>$id,
            ],
        ]);
        foreach($list as $key=>&$val){
            $val['addtime'] = date('m-d',strtotime($val['addtime']));
            $val['main'] = cutstr_html($val['main']);
        }
        $page = $list->render();
        return view('consult',["user" => $user,"list"=>$list,"id"=>$id,"listtitle"=>$listtitle,'page'=>$page]);
    }
    //查看咨询中心列表
    public function lookconsult(){
        $id = input('get.id') ? input('get.id') :  $this->redirect('index/index');
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        $list = model('consultmain')->where('status',1)->where('id',$id)->order('id desc')->find();
        $list->addtime= date('Y-m-d H:i',strtotime($list->addtime));
        return view('lookconsult',["user" => $user,"list"=>$list]);
    }
    //新手指南
    public function noviceguide(){
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);

        $list = model('aboutus')->where('title','新手指南')->find();
        return view('noviceguide',["user" => $user,"list"=>$list]);

    }
    
 

}