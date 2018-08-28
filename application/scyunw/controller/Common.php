<?php
namespace app\scyunw\controller;
use think\Controller;
use think\Loader;
use think\Request;
class Common extends Controller
{
    protected function _initialize() {
        //判断管理员是否登陆
        if(!session('?admin_id')){
            $this->redirect('login/index');
        }
        //获取当前的模块
        $module = request()->module();
        //获取模块下的控制器
        $controller = request()->controller();
        $action = request()->action();

        $id = session('admin_id');
        $admin = model("admin")->where("id",$id)->find()->toArray();
        $admingroup = model("admingroup")->where("id",$admin["gid"])->find()->toArray();
        $groupmenus = explode("|",$admingroup["menus"]);
        $groupmodes = explode("|",$admingroup["modes"]); 
        $public_menus = array("Index","main");
        $view = $controller."/".$action;
        $group = array_merge($groupmenus,$groupmodes,$public_menus);
        if (!Request::instance()->isAjax()){
            // print_r($group);
            // print_r($view);
            if($admingroup["modes"]!="0"){
                if(!in_array($view,$group)&&!in_array($controller,$group)){
                    header("Content-type: text/html; charset=utf-8");
                    echo '<p style="margin:10px;text-align:center;">对不起，您没有权限操作此模块！</p>';
                    exit();
                }
            }            
        }
        
    }
}



