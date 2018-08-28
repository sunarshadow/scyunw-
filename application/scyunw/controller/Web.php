<?php
namespace app\scyunw\controller;

class Web extends Common
{
    //服务案例：视图
    public function servercase(){
        return view('servercase');
    }
    //服务案例：数据
    public function getservercase(){
        $page =  input('get.page') ? input('get.page') : 1;
        $limit =  input('get.limit') ? input('get.limit') : 10;
        $title = input('get.title') ? input('get.title') : null;
        
        $where = [];
        if($title){
            $where['title'] = ['like','%'.$title.'%'];
        }
        $list = model('servercase')->where($where)->page($page,$limit)->order('status,id desc')->select();
        $count = model('servercase')->where($where)->count();
        $json['code'] = 0;
        $json['data'] = $list;
        $json['count'] = $count;
        return Json($json);
    }
    //服务案例：状态修改
    public function servercasestatus(){
        $id = input('post.id') ? input('post.id') : exit($this->error('ID错误'));
        $status = input('post.status') !==false ? input('post.status') : exit($this->error('状态错误'));
        $servercase = model('servercase')->getById($id);
        $servercase->status = $status;
        if($servercase->save()){
            $txt =$servercase->status == 1 ? '开启' : '关闭';
            model("Base")->CreateAdminLog("修改网站",$txt."了id为【".$id."】的服务案例");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //服务案例：删除
    public function deleteservercase(){
        $ids = input('post.ids') ? input('post.ids') : exit($this->error('ID错误'));
        $res = model('servercase')->destroy($ids);
        if($res){
            model("Base")->CreateAdminLog("修改网站","删除了了id为【".$ids."】的服务案例");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    //服务案例：增加和修改
    public function addservercase(){
        if(request()->isPost()){
            $id = input('post.id');
            $servercase = $id ?  model('servercase')->getById($id) : model('servercase');
            $servercase->title = input('post.title');
            $servercase->main = input('post.main');
            $servercase->status = input('post.status') ? input('post.status') : 0;
            $id ? true : $servercase->addtime =date('Y-m-d H:i:s');

            if($servercase->save()){
                if($id){
                    model("Base")->CreateAdminLog("修改网站","更新了id为【".$servercase->id."】的服务案例");
                    $this->success('操作成功');
                }else{
                    model("Base")->CreateAdminLog("修改网站","新增了了id为【".$servercase->id."】的服务案例");
                    $this->success('操作成功');
                }
            }else{
                $this->error('操作失败');
            }
            
        }else{
            $id = input('get.id');
            if($id){
                $servercase = model('servercase')->getById($id);
                $servercase->status = $servercase->status == 1 ? 'checked' : '';
                $this->assign('servercase',$servercase);
            }
            return view('addservercase');
        }
    }
    //公司简介
    public function aboutus(){
        if(request()->isPost()){
            $aboutus = model('aboutus')->getByTitle('公司简介');
            $aboutus->main = input('post.main');
            if($aboutus->save()){
                    model("Base")->CreateAdminLog("修改网站","更新了公司简介");
                    $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }else{
            $aboutus = model('aboutus')->getByTitle('公司简介');
            return view('aboutus',['aboutus'=>$aboutus]);
        }
    }
    //公司简介
    public function noviceguide(){
        if(request()->isPost()){
            $aboutus = model('aboutus')->getByTitle('新手指南');
            $aboutus->main = input('post.main');
            if($aboutus->save()){
                    model("Base")->CreateAdminLog("修改网站","更新了公司简介");
                    $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }else{
            $aboutus = model('aboutus')->getByTitle('新手指南');
            return view('noviceguide',['aboutus'=>$aboutus]);
        }
    }
    
    //加入我们：视图
    public function joinus(){
        return view('joinus');
    }
    //加入我们：数据
    public function getjoinus(){
        $page =  input('get.page') ? input('get.page') : 1;
        $limit =  input('get.limit') ? input('get.limit') : 10;
        $title = input('get.title') ? input('get.title') : null;
        
        $where = [];
        if($title){
            $where['title'] = ['like','%'.$title.'%'];
        }
        $list = model('joinus')->where($where)->page($page,$limit)->order('status,id desc')->select();
        $count = model('joinus')->where($where)->count();
        $json['code'] = 0;
        $json['data'] = $list;
        $json['count'] = $count;
        return Json($json);
    }
    //加入我们：状态修改
    public function joinusstatus(){
        $id = input('post.id') ? input('post.id') : exit($this->error('ID错误'));
        $status = input('post.status') !==false ? input('post.status') : exit($this->error('状态错误'));
        $joinus = model('joinus')->getById($id);
        $joinus->status = $status;
        if($joinus->save()){
            $txt =$joinus->status == 1 ? '开启' : '关闭';
            model("Base")->CreateAdminLog("修改网站",$txt."了id为【".$id."】的加入我们");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //加入我们：删除
    public function deletejoinus(){
        $ids = input('post.ids') ? input('post.ids') : exit($this->error('ID错误'));
        $res = model('joinus')->destroy($ids);
        if($res){
            model("Base")->CreateAdminLog("修改网站","删除了了id为【".$ids."】的加入我们");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    //加入我们：增加和修改
    public function addjoinus(){
        if(request()->isPost()){
            $id = input('post.id');
            $joinus = $id ?  model('joinus')->getById($id) : model('joinus');
            $joinus->title = input('post.title');
            $joinus->main = input('post.main');
            $joinus->status = input('post.status') ? input('post.status') : 0;
            $id ? true : $joinus->addtime =date('Y-m-d H:i:s');

            if($joinus->save()){
                if($id){
                    model("Base")->CreateAdminLog("修改网站","更新了id为【".$joinus->id."】的加入我们");
                    $this->success('操作成功');
                }else{
                    model("Base")->CreateAdminLog("修改网站","新增了了id为【".$joinus->id."】的加入我们");
                    $this->success('操作成功');
                }
            }else{
                $this->error('操作失败');
            }
            
        }else{
            $id = input('get.id');
            if($id){
                $joinus = model('joinus')->getById($id);
                $joinus->status = $joinus->status == 1 ? 'checked' : '';
                $this->assign('joinus',$joinus);
            }
            return view('addjoinus');
        }
    }

    //合作单位：视图
    public function partner(){
        return view('partner');
    }
    //合作单位：数据
    public function getpartner(){
        $page =  input('get.page') ? input('get.page') : 1;
        $limit =  input('get.limit') ? input('get.limit') : 10;
        $title = input('get.title') ? input('get.title') : null;
        
        $where = [];
        if($title){
            $where['title'] = ['like','%'.$title.'%'];
        }
        $list = model('partner')->where($where)->page($page,$limit)->order('sort')->select();
        $count = model('partner')->where($where)->count();
        $json['code'] = 0;
        $json['data'] = $list;
        $json['count'] = $count;
        return Json($json);
    }
    //合作单位：状态修改
    public function partnerstatus(){
        $id = input('post.id') ? input('post.id') : exit($this->error('ID错误'));
        $status = input('post.status') !==false ? input('post.status') : exit($this->error('状态错误'));
        $partner = model('partner')->getById($id);
        $partner->status = $status;
        if($partner->save()){
            $txt =$partner->status == 1 ? '开启' : '关闭';
            model("Base")->CreateAdminLog("修改网站",$txt."了id为【".$id."】的合作伙伴");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //合作单位：排序修改
    public function partnersort(){
        $id = input('post.id') ? input('post.id') : exit($this->error('ID错误'));
        $sort = input('post.sort') !==false ? input('post.status') : exit($this->error('状态错误'));
        $partner = model('partner')->getById($id);
        $partner->sort = $sort;
        if($partner->save()){
            model("Base")->CreateAdminLog("修改网站","修改了id为【".$id."】的合作伙伴排序为".$partner->sort);
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //合作单位：删除
    public function deletepartner(){
        $ids = input('post.ids') ? input('post.ids') : exit($this->error('ID错误'));
        $res = model('partner')->destroy($ids);
        if($res){
            model("Base")->CreateAdminLog("修改网站","删除了了id为【".$ids."】的合作伙伴");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //合作单位：增加和修改
    public function addpartner(){
        if(request()->isPost()){
            $id = input('post.id');
            $partner = $id ?  model('partner')->getById($id) : model('partner');
            $partner->title = input('post.title');
            $partner->sort = input('post.sort');
            $partner->pic = input('post.pic');
            $partner->status = input('post.status') ? input('post.status') : 0;
            $id ? true : $partner->addtime =date('Y-m-d H:i:s');

            if($partner->save()){
                if($id){
                    model("Base")->CreateAdminLog("修改网站","更新了id为【".$partner->id."】的加入我们");
                    $this->success('操作成功');
                }else{
                    model("Base")->CreateAdminLog("修改网站","新增了了id为【".$partner->id."】的加入我们");
                    $this->success('操作成功');
                }
            }else{
                $this->error('操作失败');
            }
            
        }else{
            $id = input('get.id');
            if($id){
                $partner = model('partner')->getById($id);
                $partner->status = $partner->status == 1 ? 'checked' : '';
                $this->assign('partner',$partner);
            }
            return view('addpartner');
        }
    }


    //咨询中心父菜单：视图
    public function consult(){
        return view('consult');
    }
    //咨询中心父菜单：数据
    public function getconsult(){
        $page =  input('get.page') ? input('get.page') : 1;
        $limit =  input('get.limit') ? input('get.limit') : 10;
        $title = input('get.title') ? input('get.title') : null;
        $where = [];
        if($title){
            $where['title'] = ['like','%'.$title.'%'];
        }
        $list = model('consult')->where($where)->page($page,$limit)->order('status,id desc')->select();
        $count = model('consult')->where($where)->count();
        $json['code'] = 0;
        $json['data'] = $list;
        $json['count'] = $count;
        return Json($json);
    }
    //咨询中心父菜单：状态修改
    public function consultstatus(){
        $id = input('post.id') ? input('post.id') : exit($this->error('ID错误'));
        $status = input('post.status') !==false ? input('post.status') : exit($this->error('状态错误'));
        $consult = model('consult')->getById($id);
        $consult->status = $status;
        if($consult->save()){
            $txt =$consult->status == 1 ? '开启' : '关闭';
            model("Base")->CreateAdminLog("修改网站",$txt."了id为【".$id."】的咨询中心（菜单");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //咨询中心父菜单：删除
    public function deleteconsult(){
        $ids = input('post.ids') ? input('post.ids') : exit($this->error('ID错误'));
        $res = model('consult')->destroy($ids);
        if($res){
            model("Base")->CreateAdminLog("修改网站","删除了了id为【".$ids."】的咨询中心（菜单");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    //咨询中心父菜单：增加和修改
    public function addconsult(){
        if(request()->isPost()){
            $id = input('post.id');
            $consult = $id ?  model('consult')->getById($id) : model('consult');
            $consult->title = input('post.title');
            $consult->status = input('post.status') ? input('post.status') : 0;
            if($consult->save()){
                if($id){
                    model("Base")->CreateAdminLog("修改网站","更新了id为【".$consult->id."】的咨询中心（菜单）");
                    $this->success('操作成功');
                }else{
                    model("Base")->CreateAdminLog("修改网站","新增了了id为【".$consult->id."】的咨询中心（菜单");
                    $this->success('操作成功');
                }
            }else{
                $this->error('操作失败');
            }
            
        }else{
            $id = input('get.id');
            if($id){
                $consult = model('consult')->getById($id);
                $consult->status = $consult->status == 1 ? 'checked' : '';
                $this->assign('consult',$consult);
            }
            return view('addconsult');
        }
    }

    //咨询中心子列表：视图
    public function consultmain(){
        $consult = model('consult')->select();
        return view('consultmain',['consult'=>$consult]);
    }
    //咨询中心子列表：数据
    public function getconsultmain(){
        $page =  input('get.page') ? input('get.page') : 1;
        $limit =  input('get.limit') ? input('get.limit') : 10;
        $title = input('get.title') ? input('get.title') : null;
        $rid = input('get.rid') ? input('get.rid') : null;
        
        $where = [];
        if($title){
            $where['title'] = ['like','%'.$title.'%'];
        }
        if($rid){
            $where['rid'] = $rid;
        }
        $list = model('consultmain')->where($where)->page($page,$limit)->order('status,id desc')->select();
        $consult = model('consult')->select();
        foreach($list as  &$val){
            $consult = model('consult')->getById($val['rid']);
            $val['rtitle'] = $consult ? $consult->title :'';
            
        }
        $count = model('consultmain')->where($where)->count();
        $json['code'] = 0;
        $json['data'] = $list;
        $json['count'] = $count;
        return Json($json);
    }
     //咨询中心子列表：状态修改
     public function consultmainstatus(){
        $id = input('post.id') ? input('post.id') : exit($this->error('ID错误'));
        $status = input('post.status') !==false ? input('post.status') : exit($this->error('状态错误'));
        $consultmain = model('consultmain')->getById($id);
        $consultmain->status = $status;
        if($consultmain->save()){
            $txt =$consultmain->status == 1 ? '开启' : '关闭';
            model("Base")->CreateAdminLog("修改网站",$txt."了id为【".$id."】的咨询中心（列表）");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //咨询中心子列表：删除
    public function deleteconsultmain(){
        $ids = input('post.ids') ? input('post.ids') : exit($this->error('ID错误'));
        $res = model('consultmain')->destroy($ids);
        if($res){
            model("Base")->CreateAdminLog("修改网站","删除了了id为【".$ids."】的咨询中心（列表");
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    //咨询中心子列表：增加和修改
    public function addconsultmain(){
        if(request()->isPost()){
            $id = input('post.id');
            $consultmain = $id ?  model('consultmain')->getById($id) : model('consultmain');
            $consultmain->title = input('post.title');
            $consultmain->status = input('post.status') ? input('post.status') : 0;
            $consultmain->main = input('post.main');
            $consultmain->rid = input('post.rid');
            $id ? true : $consultmain->addtime =date('Y-m-d H:i:s');
            if($consultmain->save()){
                if($id){
                    model("Base")->CreateAdminLog("修改网站","更新了id为【".$consultmain->id."】的咨询中心（列表）");
                    $this->success('操作成功');
                }else{
                    model("Base")->CreateAdminLog("修改网站","新增了了id为【".$consultmain->id."】的咨询中心（列表）");
                    $this->success('操作成功');
                }
            }else{
                $this->error('操作失败');
            }
            
        }else{
            $consult = model('consult')->select();
            $id = input('get.id');
            if($id){
                $consultmain = model('consultmain')->getById($id);
                $consultmain->status = $consultmain->status == 1 ? 'checked' : '';
                $this->assign('consultmain',$consultmain);
            }
            return view('addconsultmain',['consult'=>$consult]);
        }
    }
    
    

}
