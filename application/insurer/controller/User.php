<?php
namespace app\insurer\controller;
use think\Controller;
class User extends Common
{
    public function usercenter()
    {
        $insurer = model('insurer')
            ->where('id',session('insurer_id'))
            ->find()
            ->toArray();
        $insurer['province'] = $insurer['province'] ? model('base')->Getbyprovince($insurer['province']) : '';
        $insurer['city'] = $insurer['city'] ? model('base')->Getbycity($insurer['city']) : '';
        $insurer['area'] = $insurer['area'] ? model('base')->Getbyarea($insurer['area']) : '';
        $insurer['address'] = $insurer['province'].$insurer['city'].$insurer['area'];
        return view('usercenter',['insurer'=>$insurer]);
    }
    public function update(){
        $insurer = model('insurer')
        ->where('id',session('insurer_id'))
        ->find();
        $key = input('?post.key')?input('post.key'): false;
        $value = input('?post.value')?input('post.value'): false;
        if($key && $value){
            $insurer->$key = $value;
        }

        $oldpwd = input('?post.oldpwd')?input('post.oldpwd'): false;
        $newpwd = input('?post.newpwd')?input('post.newpwd'): false;
        $quitpwd = input('?post.quitpwd')?input('post.quitpwd'): false;
        $type = input('?post.type')?input('post.type'): false;
        if($oldpwd && $newpwd && $quitpwd && $type){
            $md5oldpwd = md5($oldpwd);
            $md5newpwd = md5($newpwd);
            $newpwd == $quitpwd ? true : $this->error('两次密码不一致');
            
            if($type == 'account')
            {
                $md5oldpwd == $insurer->password ? true : $this->error('密码错误');
                $insurer->truepassword = $newpwd;
                $insurer->password = $md5newpwd;
            }
            elseif($type == 'finance')
            {
                
                $md5oldpwd == $insurer->financepwd ? true : $this->error('密码错误');
                $insurer->financepwd = $md5newpwd;
            }
        }
        if($insurer -> save()){
            model("Base")->CreateInsurerLog("修改","保险后台管理员".session('insurer_name')."修改了个人中心");
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
        // $insurer->save() ? $this->success('修改成功') : $this->error('修改失败');
    }

    
}