<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
class Index extends Common
{
    public function index()
    {
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        return view('index/index',["user" => $user]);
    }
    public function head()
    {
        return view('index/head');
    }
    public function inquiry()
    {
        $phone = Session::get('user_phone');
        $user = model("User")->getbyphone($phone);
        return view('index/inquiry',["user" => $user]);
    }

}
