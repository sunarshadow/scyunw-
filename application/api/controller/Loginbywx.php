<?php
namespace app\api\controller;
use think\Controller;

class Loginbywx extends Controller
{
    //创建URL
    public function index()
    {
        // SDK实例对象
        $oauth = & load_wechat('Oauth');
        //设置参数
        $callback = url('/wechat/index/reg','','','chexian.302s.cn');
        $state = 'isbywx';//你传给微信服务器一个state，它又回传给你，可以根据state判断请求是否来自微信服务器跳转
        $scope = 'snsapi_userinfo';
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
    }
    public function getdata()
    {
        $oauth = & load_wechat('Oauth');

        // 执行接口操作
        $result = $oauth->getOauthAccessToken();

        // 处理返回结果
        if($result===FALSE){
            // 接口失败的处理
            return false;
        }else{
            // 接口成功的处理
            // SDK实例对象
            // 执行接口操作
            $access_token = $result['access_token'];
            $openid = $result['openid'];
            $result = $oauth->getOauthUserinfo($access_token, $openid);

            return json_encode($result);
            // 处理返回结果
            if($result===FALSE){
                // 接口失败的处理
                return false;
            }else{
                // 接口成功的处理
            }
        }
    }

}