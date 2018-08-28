<?php
namespace app\wechat\controller;
use think\Controller;

class Index extends Common
{
    public function index()
    {
        $phone = input("get.phone");
        $url = input("get.url");
        $id = input("get.id");
        // SDK实例对象
        $oauth = & load_wechat('Oauth');
        $urlstr = 'phone='.$phone;
        if($url){
            $urlstr .= "&url=".$url; 
        }
        if($url){
            $urlstr .= "&id=".$id; 
        }
        //设置参数
        $callback = url('/wechat/index/reg',$urlstr,'','chexian.302s.cn');
        $state = 'isbywx';//你传给微信服务器一个state，它又回传给你，可以根据state判断请求是否来自微信服务器跳转
        $scope = 'snsapi_userinfo';
        // print_r($callback);exit;
        // 执行接口操作
        $result = $oauth->getOauthRedirect($callback, $state, $scope);

        // 处理返回结果
        if($result===FALSE){
            // 接口失败的处理  
            return false;
        }else{
            $this->redirect($result);
            // 接口成功的处理
        }     
        return view('index/index',["result"=>$result]);
    }
    public function reg()
    {
        $phone = input("get.phone")?input("get.phone"):"17606036160";//获取推荐人信息
        $url = input("get.url");
        $id = input("get.id");
        $oauth = & load_wechat('Oauth');

        // 执行接口操作
        $result = $oauth->getOauthAccessToken();
		// print_r($result);exit;
        // 处理返回结果
        if($result===FALSE){
			header("Content-type: text/html; charset=utf-8");
            // 接口失败的处理
			echo ("<script>alert('重复授权');window.location='/wechat/?phone='+".$phone.";</script>");
			exit;
			// return view('index/reg',["result"=>$result,"isuser"=>$isuser,"phone"=>$phone]);
			
            return false;
			
        }else{
            // 接口成功的处理
            // SDK实例对象
            // 执行接口操作
            $access_token = $result['access_token'];
            $openid = $result['openid'];
            $result = $oauth->getOauthUserinfo($access_token, $openid);
            // print_r(json_encode($result));
            // exit;
            // 处理返回结果
            if($result===FALSE){
                // 接口失败的处理
                return false;
            }else{
                // 接口成功的处理
            }
        } 
              
        $isuser = 0;
        $user = model("User")->getbywxunionid($result["unionid"]);
        $update["wxopenid"] = $openid;
        //判断openid是否一致，否则更新wxopenid
        if($openid != $user["wxopenid"]){
            model("User")->where("wxunionid",$result["unionid"])->update($update);
        }         
        if($user){
            $isuser = 1;
            session('user_phone',$user["phone"]);
            session('user_name',$user["username"]);
        }
        if($url){
            $url = "/mobile/html/".$url."_frm.html";
            if($id){
                $url .= "?id=".$id;
            }
            echo "<script>window.location='".$url."'</script>";
            exit;
        }
        return view('index/reg',["result"=>$result,"isuser"=>$isuser,"phone"=>$phone]);
    }
    public function getocken(){
        $url = input("post.url")?input("post.url"):"chexian.302s.cn";
        $script = & load_wechat('Script');
        // 执行接口操作
        $result = $script->getJsSign($url);
        return json_encode($result);        
    }

}
