<?php
namespace app\wechat\controller;
use think\Controller;
use think\Loader;
class Common extends Controller
{
    protected function _initialize() {

        //获取当前的模块
        // $module = request()->module();
        // //获取模块下的控制器
        // $controller = request()->controller();
	
        // echo $controller;
        //$action = request()->action();
        // 获取auth实例
        // Loader::import('Auth', EXTEND_PATH);
        // $Auth = new \com\Auth();
//        if($controller !== 'Index'){//首页都有权限
//            if(!$Auth->check(ucfirst($module).'/'.$controller.'/', session('admin_id'))){
//            header("Content-type: text/html; charset=utf-8");
//            echo '<p style="margin:10px;text-align:center;">对不起，您没有权限操作此模块！</p>';
//            exit();
//            }
//        }
        //运行公用导航
        
    }
}



