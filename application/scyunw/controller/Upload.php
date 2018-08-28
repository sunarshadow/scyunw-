<?php
namespace app\scyunw\controller;
use think\Controller;
use think\Request;
class Upload extends Controller{
    public function img(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $filename = $_FILES["file"]["name"];
        $temary = explode(".",$filename);
        if(count($temary)>2){
            return "请再测试一下。";
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(strpos($filename,'php',1)){
            return "请再测试一下。";
        }
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                $savename =  $info->getSaveName();
                $path= str_replace("\\","/",$savename);  
                $res = [
                    'code' => '0' ,
                    'msg' => '上传成功',    
                    'path' => '/uploads/'.$path,
                    "data" => [
                        "src" => '/uploads/'.$path,
                        "title" => "" //可选
                    ]
                    ];
                return $res;
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            } 
        }
    }
    public function imgall(){
    // 获取表单上传文件
    $files = request()->file('image');
    foreach($files as $file){
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            print_r($info->getExtension()); 
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            print_r($info->getFilename()); 
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }    
    }
}


}