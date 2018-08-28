<?php
namespace app\api\controller;
use think\Controller;
use think\Loader;
class Common extends Controller
{
    protected function _initialize() {
        //判断管理员是否登陆
        if(!session('?user_phone')){
            $this->redirect('login/index');
        }     
        //获取当前的模块
        $module = request()->module();
        //获取模块下的控制器
        $controller = request()->controller();
        if(!request()->isPost()){
            // $this->redirect('login/index');
        } 
    }
}



