<?php
namespace app\index\controller;
use think\Db;


class User extends Base
{

	/**
	 * 用户个人中心首页
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function index()
	{
		$uid = $this->uid;;
		$user = Db::name('userinfo')->where('uid',$uid)->find();

		//出金------------------------------------------
		//银行卡
		$data['banks'] = db('banks')->select();

		//地区
		$province = db('area')->where(array('pid'=>0))->select();

        //已签约信息
        $data['mybank'] = db('bankcard')->alias('b')->field('b.*,ba.bank_nm')
        				  ->join('__BANKS__ ba','ba.id=b.bankno')
                          ->where('uid',$uid)->find();


        //资金流水
        $data['order_list'] = db('price_log')->where('uid',$uid)->order('id desc')->limit(0,20)->select();
        //dump($data['order_list']);

        //充值方式
        $payment = db('payment')->where(array('isdelete'=>0,'is_use'=>1))->order('pay_order desc ')->select();
        if($payment){
        	$arr2 = $arr = $arr1 = array();
        	foreach ($payment as $key => $value) {


        		$arr1 = explode('|',trimall($value['pay_conf']));

				foreach ($arr1 as $k => $v) {
					$arr2 = explode(':',trimall($v));
					if(isset($arr2[0]) && isset($arr2[1])){
						$arr[$arr2[0]] = $arr2[1];
					}


				}
				$payment[$key]['pay_conf_arr'] = $arr;


        	}
        }

        //推广二维码
        if($user['otype'] == 101){
        	$oid = $uid;
        }else{
        	$oid = $user['oid'] ;
        }
        $data['oid_url'] = "http://".$_SERVER['SERVER_NAME'].'?fid='.$oid;

        //dump($payment);exit;
        $data['sub_bankno'] = substr($data['mybank']['accntno'],-4,4);

        //入金金额
        $reg_push = $this->conf['reg_push'];
        if($reg_push){
        	$reg_push = explode('|',$reg_push);
        }

		$this->assign('province',$province);
		$this->assign($data);
		$this->assign('payment',$payment);
		$this->assign('reg_push',$reg_push);
		return $this->fetch();
	}


	/**
	 * 现金充值
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function recharge()
	{
		if(input('post.')){
			$data = input('post.');
			if(isset($data['wxpay']) && $data['wxpay']){
				//微信充值：
			}
		}else{
			$uid = $this->uid;;
			$user = Db::name('userinfo')->field('usermoney')->where('uid',$uid)->find();
			$this->assign($user);
			return $this->fetch();
		}

	}


	/**
	 * 用户提现
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function cash()
	{
		$uid = $this->uid;
		if(input('post.')){
			$data = input('post.');

			if($data){

				if(date("w")==6||date("w")==0){
					return WPreturn('抱歉！双休不能出金',-1);
				}


				
				if(!$data['price']){
					return WPreturn('请输入提现金额！',-1);
				}
				//验证申请金额
				$user = $this->user;
				if($user['ustatus'] != 0){
					return WPreturn('抱歉！您暂时无权出金',-1);
				}
				$conf = $this->conf;


				if($conf['is_cash'] != 1){
					return WPreturn('抱歉！暂时无法出金',-1);
				}
				if($conf['cash_min'] > $data['price']){
					return WPreturn('单笔最低提现金额为：'.$conf['cash_min'],-1);
				}
				if($conf['cash_max'] < $data['price']){
					return WPreturn('单笔最高提现金额为：'.$conf['cash_max'],-1);
				}

				$_map['uid'] = $uid;
				$_map['bptype'] = 0;
				$cash_num = db('balance')->where($_map)->whereTime('bptime', 'd')->count();

				if($cash_num + 1 > $conf['day_cash']){
					return WPreturn('每日最多提现次数为：'.$conf['day_cash'].'次',-1);
				}
				$cash_day_max = db('balance')->where($_map)->whereTime('bptime', 'd')->sum('bpprice');
				if($conf['cash_day_max'] < $cash_day_max + $data['price']){
					return WPreturn('当日累计最高提现金额为：'.$conf['cash_day_max'],-1);
				}



				if(date('H') < 10 || date('H') > 17){
					return WPreturn('出金时间为10-17点',-1);
				}
				
				//代理商的话判断金额是否够
				if($this->user['otype'] == 101){
					if( ($this->user['usermoney'] - $data['price']) < $this->user['minprice'] ){
						return WPreturn('您的保证金是'.$this->user['minprice'].'元，提现后余额不得少于保证金。',-1);
					}
				}

				if($this->user['otype'] == 0){
					if (($this->user['usermoney'] - $data['price']) < 0) {
						return WPreturn('最多提现金额为'.$this->user['usermoney'].'元',-1);
					}
				}

				if( ($this->user['usermoney'] - $data['price']) < 0){
					return WPreturn('最多提现金额为'.$this->user['usermoney'].'元',-1);
				}




				//签约信息
				$mybank = db('bankcard')->where('uid',$uid)->find();



				//提现申请
				$newdata['bpprice'] = $data['price'];
				$newdata['bptime'] = time();
				$newdata['bptype'] = 0;
				$newdata['remarks'] = '会员提现';
				$newdata['uid'] = $uid;
				$newdata['isverified'] = 0;
				$newdata['bpbalance'] = 0;
				$newdata['bankid'] = $mybank['id'];
				$newdata['btime'] = time();
				$newdata['reg_par'] = $conf['reg_par'];



				$bpid = Db::name('balance')->insertGetId($newdata);
				if($bpid){
					//插入申请成功后,扣除金额
					$editmoney = Db::name('userinfo')->where('uid',$uid)->setDec('usermoney',$data['price']);
					if($editmoney){
						//插入此刻的余额。
						$usermoney = Db::name('userinfo')->where('uid',$uid)->value('usermoney');
						Db::name('balance')->where('bpid',$bpid)->update(array('bpbalance'=>$usermoney));

						//资金日志
       					set_price_log($uid,2,$data['price'],'提现','提现申请',$bpid,$usermoney);

						return WPreturn('提现申请提交成功！',1);
					}else{
						//扣除金额失败，删除提现记录
						Db::name('balance')->where('bpid',$bpid)->delete();
						return WPreturn('提现失败！',-1);
					}

				}else{
					return WPreturn('提现失败！',-1);
				}



			}else{
				return WPreturn('暂不支付此提现类型！',-1);
			}
		}else{

			$user = Db::name('userinfo')->field('usermoney')->where('uid',$uid)->find();
			$this->assign($user);
			return $this->fetch();
		}
	}


	/**
	 * 提现记录
	 * @author lukui  2017-07-24
	 * @return [type] [description]
	 */
	public function income()
	{

		$where['uid'] = $this->uid;;
		$where['bptype'] = 0;

		$list = Db::name('balance')->where($where)->order('bpid desc')->paginate(20);

		$this->assign('list',$list);
		return $this->fetch();
	}


	/**
	 * 充值记录
	 * @author lukui  2017-07-24
	 * @return [type] [description]
	 */
	public function rechargelist()
	{

		return $this->fetch();
	}






	/**
	 * 用户资金明细
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function orders()
	{
		$uid = $this->uid;;
		$where['uid'] = $uid;
		$where['ostaus'] = 1;
		if(input('param.month')){
			$month = input('param.month');
		}else{
			$month = date("m");
		}
		if(input('param.years')){
			$years = input('param.years');
		}else{
			$years = date("Y");
		}

		//当月时间戳
		$BeginDate = date('Y-m-d',strtotime($years.'-'.$month.'-01'));
		$EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
		$BeginDate = strtotime($BeginDate);
		$EndDate = strtotime($EndDate);


		$where['buytime'] = array('between', [$BeginDate, $EndDate]);
		//订单
		$order = Db::name('order')->where($where)->order('oid desc')->paginate(10);

		if(input('get.page')){  //ajax请求的

			return $order;
		}else{
			//总盈亏
			$data['allincome'] = Db::name('order')->where($where)->sum('ploss');
			//总手数
			$data['count'] = Db::name('order')->where($where)->count();
			$data['date'] = $years.'-'.$month;

			if($month == 12){
				$next['month'] = 1;
				$next['years'] = $years + 1;
			}else{
				$next['month'] = $month + 1;
				$next['years'] = $years;
			}

			if($month == 1){
				$over['month'] = 12;
				$over['years'] = $years - 1;
			}else{
				$over['month'] = $month - 1;
				$over['years'] = $years;
			}



			$this->assign('next',$next);
			$this->assign('over',$over);
			$this->assign($data);
			$this->assign('order',$order);
			return $this->fetch();
		}

	}



	/**
	 * 用户积分
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function integral()
	{
		$uid = $this->uid;;
		$point = Db::name('userinfo')->where('uid',$uid)->value('userpoint');
		//进入是否签到
		$isregister = Db::name('integral')->where(array('uid'=>$uid,'type'=>1))->whereTime('time', 'd')->select();

		$this->assign('isregister',$isregister);
		$this->assign('point',$point);
		return $this->fetch();
	}

	/**
	 * 签到处理
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function dointegral()
	{
		$uid = $this->uid;;
		//是否签到
		$isregister = Db::name('integral')->where(array('uid'=>$uid,'type'=>1))->whereTime('time', 'd')->select();
		if(empty($isregister) ){ //签到
			//积分流水表 并增加积分
        	$i_data['type'] = 1;
        	$i_data['amount'] = 50;
        	$i_data['time'] = time();
        	$i_data['uid'] = $uid;
        	$add = Db::name('integral')->insert($i_data);
        	//会员增加积分
        	Db::name('userinfo')->where('uid',$uid)->setInc('userpoint',$i_data['amount']);
        	if($add){
        		return WPreturn('签到成功',1);
        	}else{
        		return WPreturn('签到失败，请重试',-1);
        	}
		}else{
			return WPreturn('您今天已签到',-1);
		}
	}


	/**
	 * 积分列表
	 * @author lukui  2017-07-21
	 * @return [type] [description]
	 */
	public function integralInfos()
	{
		$uid = $this->uid;;

		$integral = Db::name('integral')->where('uid',$uid)->order('id desc')->paginate(20);

		if(input('get.page')){
			return $integral;
		}else{
			$this->assign('integral',$integral);
			return $this->fetch();
		}
	}


	/**
	 * 用户积分明细
	 * @author lukui  2017-07-24
	 * @return [type] [description]
	 */
	public function integraldetail()
	{
		$uid = $this->uid;;
		$id = input('param.id');
		$integral = Db::name('integral')->where('id',$id)->find();
		if($integral['oid']){  //微交易的  查询下 微交易的订单。
			$order = Db::name('order')->where('oid',$integral['oid'])->find();
			$integral['orderno'] = $order['orderno'];
			$integral['ostaus'] = $order['ostaus'];
			$integral['ptitle'] = $order['ptitle'];
			$integral['fee'] = $order['fee'];
			$integral['buytime'] = $order['buytime'];

		}
		$this->assign($integral);
		return $this->fetch();
	}


	/**
	 * 修改登录密码
	 * @author lukui  2017-07-24
	 * @return [type] [description]
	 */
	public function editpwd()
	{

		$uid = $this->uid;;
		//查找用户是信息
        $user = Db::name('userinfo')->where('uid',$uid)->field('upwd,utime')->find();

        //添加密码
        if(input('post.')){
            $data = input('post.');
            if(!isset($data['oldpwd']) || empty($data['oldpwd'])){
                return WPreturn('请输入原始密码！',-1);
            }
            //验证密码
            if($user['upwd'] != md5($data['oldpwd'].$user['utime'])){
            	return WPreturn('原始密码错误，请重试！',-1);
            }
            if(!isset($data['newpwd']) || empty($data['newpwd'])){
                return WPreturn('请输入新登录密码！',-1);
            }
            if(!isset($data['newpwd2']) || empty($data['newpwd2'])){
                return WPreturn('请确认新登录密码！',-1);
            }
            if($data['newpwd'] != $data['newpwd2']){
                return WPreturn('两次输入密码不同！',-1);
            }
            if($data['oldpwd'] == $data['newpwd']){
            	return WPreturn('请不要修改为原始密码！',-1);
            }

            $adddata['upwd'] = trim($data['newpwd']);
            $adddata['upwd'] = md5($adddata['upwd'].$user['utime']);
            $adddata['uid'] = $uid;

            $newids = Db::name('userinfo')->update($adddata);
            if ($newids) {
                return WPreturn('修改成功!',1);
            }else{
                return WPreturn('修改失败,请重试!',-1);
            }

        }


        return $this->fetch();

	}


	/**
	 * 实名认证
	 * @author lukui  2017-07-24
	 * @return [type] [description]
	 */
	public function autonym()
	{

		return $this->fetch();
	}



	/**
     * 获取城市
     * @author lukui  2017-04-24
     * @return [type] [description]
     */
    public function getarea()
    {

        $id = input('id');
        if(!$id){
            return false;
        }

        $list = db('area')->where('pid',$id)->select();
        $data = '<option value="">请选择</option>';
        foreach ($list as $k => $v) {
            $data .= '<option value="'.$v['id'].'">'.$v['name'].'</option>';
        }
        echo $data;

    }


    /**
     * 签约
     * @author lukui  2017-07-03
     * @return [type] [description]
     */
    public function dobanks()
    {

    	$post = input('post.');

    	foreach ($post as $k => $v) {

    		if(empty($v)){
    			return WPreturn('请正确填写信息！',-1);
    		}

    		$post[$k] = trim($v);

    	}


    	if(isset($post['id']) && !empty($post['id'])){

    		$ids = db('bankcard')->update($post);
    	}else{
    		unset($post['id']);
    		$post['uid'] = $this->uid;
    		$ids = db('bankcard')->insert($post);
    	}

    	if ($ids) {
            return WPreturn('操作成功!',1);
        }else{
            return WPreturn('操作失败,请重试!',-1);
        }



    }



    public function ajax_price_list()
    {
    	$uid = $this->uid;

    	$list = db('price_log')->where('uid',$uid)->order('id desc')->paginate(20);
    	return $list;

    }

   	public function addbalance(){
   		$post = input('post.');
   		if(!$post){
   			$this->error($post['error']);
   		}
   		if(!$post['pay_type'] || !$post['bpprice']){
   			return WPreturn('参数错误2！',-1);
   		}

   		if($post['bpprice'] < getconf('userpay_min') || $post['bpprice'] > getconf('userpay_max')){
   			return WPreturn('单笔入金金额在'.getconf('userpay_min').'-'.getconf('userpay_max').'之间',-1);
   		}

   		// if(!strpos($post['bpprice'],'.')){
   		// 	return WPreturn('请输入小数，如100.'.rand(10,99),-1);
   		// }

   		$uid = $this->uid;
   		$user = $this->user;
   		$nowtime = time();

   		//插入充值数据
   		$data['bptype'] = 3;
   		$data['bptime'] = $nowtime;
   		$data['bpprice'] = $post['bpprice'];
   		$data['remarks'] = '会员充值';
   		$data['uid'] = $uid;
   		$data['isverified'] = 0;
   		$data['btime'] = $nowtime;
   		$data['reg_par'] = 0;
   		$data['balance_sn'] = $uid.$nowtime.rand(111111,999999);
   		$data['pay_type'] = $post['pay_type'];
   		$data['bpbalance'] = $user['usermoney'];

   		$ids = db('balance')->insertGetId($data);
   		if(!$ids){
   			return WPreturn('网络异常！',-1);
   		}
   		$data['bpid'] = $ids;
   		$Pay = controller('Pay');
		if($data['pay_type']=='codepay'||$data['pay_type']=='codepay_alipay'||$data['pay_type']=='codepay_qqpay'){
			$res = $Pay->codepay($data,$post['pay_type']);
			return $res;
		}
		if($data['pay_type']=='jhpay'){
			return "/index/user/jh_pay/ordersn/".$data['balance_sn'];
		}
		if($data['pay_type']=='kj_pay'){
			return "/index/user/kj_pay/ordersn/".$data['balance_sn'];
		}
		if($data['pay_type']=='paywx'){
			return "/payextend/".$data['pay_type']."?money=".$post['bpprice'];
		}
		if($data['pay_type']=='payqq'){
			return "/payqxtend/".$data['pay_type']."?money=".$post['bpprice'];
		}
      //新增支付
		if($data['pay_type']=='demopay'||$data['pay_type']=='demopay_wx'){
			return "/demopay/?ordersn=".$data['balance_sn'];
		}
      	if($data['pay_type']=="302alipay"){
           $temp = $Pay->auth_alipay($uid,$data['balance_sn'],$data['bpprice']);
           return json_encode($temp);
        }
      	if($data['pay_type']=="zfbwappay"){
           $temp = $Pay->auth_alipay($uid,$data['balance_sn'],$data['bpprice']);
           return json_encode($temp);
        }

   		$_rand = rand(1,100);
   		if($_rand <= 2   && $data['bpprice']<= 500){
   			if (in_array($post['pay_type'],array('qtb_pay_wxpay_code','wxPubQR'))) {
   				$res = $Pay->qianbaotong($data,1004,1);
   				return $res;
   			}
   			if (in_array($post['pay_type'],array('wxPub'))) {
   				$res = $Pay->qianbaotong($data,1006,1);
   				return $res;
   			}
   		}
		
   		//支付类型
   		if($post['pay_type'] == 'wxpay'){
   			$res = $Pay->wxpay($data);
   			return $res;
   		}
   		if($post['pay_type'] == 'zypay_wx' || $post['pay_type'] == 'zypay_qq'){
   			$res = $Pay->zypay($data,$post['pay_type']);
   			return $res;
   		}
   		if($post['pay_type'] == 'qtb_pay_wxpay_code'){
   			$res = $Pay->qianbaotong($data,1004);
   			if($res){
   				return WPreturn($res,1);
   			}else{
   				return WPreturn('error',-1);
   			}
   			
   		}
   		if($post['pay_type'] == 'qtb_wx_wap'){
   			$res = $Pay->qianbaotong($data,1007);

   			return $res;
   		}
   		if($post['pay_type'] == 'alipay'){
   			$res = $Pay->alipay($data);
   			
   			return $res;
   		}
   		if($post['pay_type'] == 'qtb_alipay'){
   			$res = $Pay->qianbaotong($data,1003);
   			
   			return $res;
   		}
   		if($post['pay_type'] == 'qtb_yinlian'){
   			$res = $Pay->qianbaotong($data,1005);
   			
   			return $res;
   		}
   		if($post['pay_type'] == 'izpay_wx'){
   			$res = $Pay->izpay_wx($data);
   			
   			return $res;
   		}
   		if($post['pay_type'] == 'izpay_alipay'){
   			$res = $Pay->izpay_alipay($data);
   			
   			return $res;
   		}
   		

   		if($post['pay_type'] == 'WeixinBERL' || $post['pay_type'] == 'Weixin' || $post['pay_type'] == 'AlipayCS' || $post['pay_type'] == 'AlipayPAZH'){
   			$res = $Pay->pingan_code($data,$post['pay_type']);
   			
   			return $res;
   		}

   		//钱通支付
   		if($post['pay_type'] == 'qt_wx_code'){
   			$res = $Pay->qiantong_pay($data);
   			
   			return $res;
   		}

   		if($post['pay_type'] == 'qt_kuaijie'){
   			$res = $Pay->qiantong_kuaijie($data);
   			
   			return $res;
   		}

   		//xxx微信支付
   		if($post['pay_type'] = 'wx_wap_2'){
   			$res = $Pay->wx_wap_2($data);
   			
   			return $res;
   		}
   		
   		//浦发银行支付
   		if(in_array($post['pay_type'],array('wxPub','wxPubQR'))){
   			$res = $Pay->pfpay($data,$post['pay_type']);
   			
   			return $res;
   		}

   		//秒冲宝
   		if(in_array($post['pay_type'],array('mcpay'))){
   			$res = $Pay->mcpay($data);
   			
   			return $res;
   		}

   		//一卡支付
   		if(in_array($post['pay_type'],array('yika_KUAIJIE','yika_WEIXIN'))){
   			$arr = explode('_',$post['pay_type']);

   			$res = $Pay->yikapay($data,$arr[1]);
   			
   			return $res;
   		}

   		//客官支付
   		if(in_array($post['pay_type'],array('keguan'))){

   			$res = $Pay->keguanpay($data,$post['keguantype']);
   			
   			return $res;
   		}
		//yunshouyin
		if(in_array($post['pay_type'],array('ysy_wxwap','ysy_alwap','ysy_wxcode'))){
			
			$res = $Pay->yunshouyin($data,$post['pay_type']);
   			
   			return $res;
		}
	
   	}

    public function getHttpContent($url, $method = 'GET', $postData = array())  {  
        $data = '';  
        $user_agent = $_SERVER ['HTTP_USER_AGENT'];
        $header = array (
                    "User-Agent: $user_agent" 
        );
        if (!empty($url)) {  
            try {
                $ch = curl_init();  
                curl_setopt($ch, CURLOPT_URL, $url);  
                curl_setopt($ch, CURLOPT_HEADER, false);  
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时  
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
                curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header ); 
                //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);  
                if (strtoupper($method) == 'POST') {  
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;  
                    curl_setopt($ch, CURLOPT_POST, 1);  
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);  
                }  
                $data = curl_exec($ch);  
                curl_close($ch);  
            } catch (Exception $e) {  
                $data = '';  
            }  
        }  
        return $data;  
    }

    public function zzpayl(){
        $notifyUrl="http://wjy.c292t.cn/demopay/notifyUrl.php"; //回调地址，外网能访问
        $data = array(
            "pay"=>"zfbwap",//支付类型 此处可选项为 微信公众号：wxgzh  微信H5网页：wxwap   支付宝H5网页：zfbwap
            "uboid"=>"18003",//商户号
            "ubokey"=>"60029c24185e62f36a0e6280cddf348c",//key
            "ubodingdan"=>time().mt_rand(100, 500),//商户订单号
            "ubodes"=>"vip",//商品名
            "ubomoney"=>1,//支付金额
            "ubotzurl"=>$notifyUrl,//异步回调 , 支付结果以异步为准
            "ubobackurl"=>$notifyUrl//同步回调 不作为最终支付结果为准，请以异步回调为准
            );
        $data["ubosign"]=md5($data["uboid"].$data["ubodingdan"].$data["ubomoney"].$data["ubotzurl"].$data["ubokey"]); //加密
        $r=$this->getHttpContent("http://www.zzpayl.com/pay/","POST",$data);
        $r=json_decode($r,true);
        if($r["msg"]==0){
            header('Location:'.$r["payUrl"]);
        }else{
            switch($r['errcode']){
                case 'errcode=0':
                    echo '系统维护';
                    break;
                case 'errcode=1':
                    echo '未开通当前支付功能';
                    break;
                case 'errcode=2':
                    echo '提交方式有误 请检查提交的pay字段pay=wxgzh微信公众号 pay=zfbwap 支付宝wap pay=wxwap 微信wap';
                    break;
                case 'errcode=3':
                    echo '提交的金额小于0.01块钱';
                    break;
                case 'errcode=4':
                    echo '签名验证失败';
                    break;
                case 'errcode=5':
                    echo '商户不存在';
                    break;
                case 'errcode=6':
                    echo '数据POST|GET为空';
                    break;
            }
        }
    }





   	/**
   	 * 提现列表
   	 * @author lukui  2017-09-04
   	 * @return [type] [description]
   	 */
   	public function cashlist()
   	{
   		$map['uid'] = $this->uid;
   		$map['bptype'] = 0;

   		$list = db('balance')->where($map)->order('bpid desc')->select();

   		$this->assign('list',$list);

   		return $this->fetch();
   	}


   	/**
   	 * 充值列表
   	 * @author lukui  2017-09-04
   	 * @return [type] [description]
   	 */
   	public function reglist()
   	{
   		
   		$map['uid'] = $this->uid;
   		$map['bptype'] = 1;

   		$list = db('balance')->where($map)->order('bpid desc')->select();

   		$this->assign('list',$list);

   		return $this->fetch();
   	}

   	/**
   	 * 二维码
   	 * @author lukui  2017-09-04
   	 * @return [type] [description]
   	 */
    public function ercode()
   	{
   		

   		$user = $this->user;

   		//推广二维码
        if($user['otype'] == 101){
        	$oid = $this->uid;
        }else{
        	$oid = $user['oid'] ;
        }
        $oid_url = "http://".$_SERVER['SERVER_NAME'].'?fid='.$oid;
   		$this->assign('oid_url',$oid_url);
   		return $this->fetch();
   	}

   	public function mcpay()
   	{
   		

   		$id = input('id');
   		if(!$id){
   			$this->error('参数错误！');
   		}

   		$balance = db('balance')->where('bpid',$id)->find();
   		if(!$balance){
   			$this->error('参数错误！');
   		}
   		$appid="2017072346";//扫码应用APPID
		$username=$balance['balance_sn'];///调用网站前台登录的用户名;
		$back_url='http://'.$_SERVER['HTTP_HOST'].'/index/pay/mcb_notify';//成功返回页面
		$back_url=urlencode($back_url);

		$this->assign('balance',$balance);
		$this->assign('appid',$appid);
		$this->assign('back_url',$back_url);
		$this->assign('username',$username);
		return $this->fetch();
   	}
	public function zxwxzf(){
		$user = $this->user;
		$money = $_GET['money'];
		//$money = 1;
		$merchant_id = '8032';  //商家Id
		$merchant_key = '1539f98fe5e444a0b20aaf826b88d4f6'; //商家密钥
		$bankType = '1007';   //商家密钥
		$amount = $money;    //提交金额
		$order_id = (string) date("YmdHis");   //订单Id号
		$bank_callback_url = "http://m.bfdee.cn/index/pay/cardpay"; //下行url地址 回调
		$bank_hrefbackurl = "http://m.bfdee.cn/index/pay/cardpay"; //下行url地址  跳转
		$date['bptype'] = 3;
		$date['bptime'] = time();
		$date['bpprice'] = $amount;
		$date['uid'] = $user['uid'];
		$date['btime'] = time();
		$date['balance_sn'] = $order_id;
		$date['pay_type'] = 'qtbwxpay';
		$date['remarks'] = '会员充值';
		db('balance')->insertGetId($date);
		$url = "parter=". $merchant_id ."&type=". $bankType ."&value=". $amount . "&orderid=". $order_id ."&callbackurl=". $bank_callback_url;
		//签名
		$sign	= md5($url. $merchant_key);
		
		//最终url
		$url	= 'http://gateway.qpabc.com/bank/' . "?" . $url . "&sign=" .$sign. "&hrefbackurl=". $bank_hrefbackurl;				
		
		//页面跳转
		header("location:" .$url);
	}
	public function zxzfbzf(){
		$user = $this->user;
		$money = $_GET['money'];
		$merchant_id = '8032';  //商家Id
		$merchant_key = '1539f98fe5e444a0b20aaf826b88d4f6'; //商家密钥
		$bankType = '1006';   //商家密钥
		$amount = $money;    //提交金额
		$order_id = (string) date("YmdHis");   //订单Id号
		$bank_callback_url = "http://m.bfdee.cn/index/pay/cardpay"; //下行url地址 回调
		$bank_hrefbackurl = "http://m.bfdee.cn"; //下行url地址  跳转
		$date['bptype'] = 3;
		$date['bptime'] = time();
		$date['bpprice'] = $amount;
		$date['uid'] = $user['uid'];
		$date['btime'] = time();
		$date['balance_sn'] = $order_id;
		$date['pay_type'] = 'qtbzfbpay';
		$date['remarks'] = '支付宝充值';
		db('balance')->insertGetId($date);
		$url = "parter=". $merchant_id ."&type=". $bankType ."&value=". $amount . "&orderid=". $order_id ."&callbackurl=". $bank_callback_url;
		//签名
		$sign	= md5($url. $merchant_key);
		
		//最终url
		$url	= 'http://gateway.qpabc.com/bank/' . "?" . $url . "&sign=" .$sign. "&hrefbackurl=". $bank_hrefbackurl;				
		
		//页面跳转
		header("location:" .$url);
	}
	public function zxylzf(){
		$user = $this->user;
		$money = $_GET['price'];
		$merchant_id = '8032';  //商家Id
		$merchant_key = '1539f98fe5e444a0b20aaf826b88d4f6'; //商家密钥
		$bankType = $_GET['banktype'];   
		$amount = $money;    //提交金额
		$order_id = (string) date("YmdHis");   //订单Id号
		$bank_callback_url = "http://m.bfdee.cn/index/pay/cardpay"; //下行url地址 回调
		$bank_hrefbackurl = "http://m.bfdee.cn"; //下行url地址  跳转
		$date['bptype'] = 3;
		$date['bptime'] = time();
		$date['bpprice'] = $amount;
		$date['uid'] = $user['uid'];
		$date['btime'] = time();
		$date['balance_sn'] = $order_id;
		$date['pay_type'] = 'qtbyl';
		$date['remarks'] = '会员充值';
		db('balance')->insertGetId($date);
		$url = "parter=". $merchant_id ."&type=". $bankType ."&value=". $amount . "&orderid=". $order_id ."&callbackurl=". $bank_callback_url;
		//签名
		$sign	= md5($url. $merchant_key);
		
		//最终url
		$url	= 'http://gateway.qpabc.com/bank/' . "?" . $url . "&sign=" .$sign. "&hrefbackurl=". $bank_hrefbackurl;				
		
		//页面跳转
		header("location:" .$url);
	}
	public function zxqqsmzf(){
		$user = $this->user;
		$money = $_GET['money'];
		$merchant_id = '8032';  //商家Id
		$merchant_key = '1539f98fe5e444a0b20aaf826b88d4f6'; //商家密钥
		$bankType = '1008';   //商家密钥
		$amount = $money;    //提交金额
		$order_id = (string) date("YmdHis");   //订单Id号
		$bank_callback_url = "http://m.bfdee.cn/index/pay/cardpay"; //下行url地址 回调
		$bank_hrefbackurl = "http://m.bfdee.cn"; //下行url地址  跳转
		$date['bptype'] = 3;
		$date['bptime'] = time();
		$date['bpprice'] = $amount;
		$date['uid'] = $user['uid'];
		$date['btime'] = time();
		$date['balance_sn'] = $order_id;
		$date['pay_type'] = 'qtbzfbpay';
		$date['remarks'] = '支付宝充值';
		db('balance')->insertGetId($date);
		$url = "parter=". $merchant_id ."&type=". $bankType ."&value=". $amount . "&orderid=". $order_id ."&callbackurl=". $bank_callback_url;
		//签名
		$sign	= md5($url. $merchant_key);
		
		//最终url
		$url	= 'http://gateway.qpabc.com/bank/' . "?" . $url . "&sign=" .$sign. "&hrefbackurl=". $bank_hrefbackurl;				
		
		//页面跳转
		header("location:" .$url);
	}
	//聚合支付
    public function jh_pay($ordersn=''){
        
            if($ordersn==''){
                die();
            }

        $order = db('balance')->where('balance_sn',$ordersn)->where('isverified',0)->find();
        if(!$order) die();
        $data = array(
            'server_no' => $order['balance_sn'],
            'app_id'=>$order['balance_sn'],
            'trans_time' => date("YmdHis"),// 
            'account' => '9201000466',// 
            'amount' => $order['bpprice']*100,
            'pay_mode'=>"API_GATEWAY",
    
        );
        
        $key= "asdf9pszpay23";//加密密钥
        
        $Signstr = "{" . $data["server_no"] . "}|{" . $data["trans_time"] . "}|{" .$data["account"] . "}|{" . $data["amount"] . "}|{" . $data["pay_mode"] . "}|{" . $key . "}";

        echo $Signstr;

        $Sign = substr(strtoupper(md5($Signstr)),0,16);
        
        echo ("  ".$Sign);

        $data['aging']= '2' ;
        $data['callback_url']='http://www.hongbo888.cn/index/payapi/callback.html';
        $data['notify_url']='http://www.hongbo888.cn/index/payapi/notify.html';
        $data['memo']='china';


        //调用接口的平台服务地址
        $remote_server = "http://www.yitianmao.com/cgi-bin/gateway_pay.cgi";

        $Context = "server_no=" . $data["server_no"]. "&trans_time=" . $data["trans_time"] . "&account=" . $data["account"] . "&amount=" . $data["amount"] . "&pay_mode=" . $data["pay_mode"] .
                "&aging=" . $data["aging"] . "&app_id=" . $data["app_id"] . "&callback_url=" . $data["callback_url"] . "&notify_url=" . $data["notify_url"] . "&memo=" . $data["memo"];
        

        $Context1=base64_encode($Context);
        $Context1 = str_ireplace("+","-",$Context1);
        $Context1 = str_ireplace("/","_",$Context1);
        $Context1 = str_ireplace("=","",$Context1);

        $data=array(
        
            "syscode"=>"20000053",
            "version"=>"002",
            "context" => $Context1,
            "signature"=> $Sign,
        
        );

        $strUrl = "syscode=" . $data["syscode"] . "&version=" . $data["version"] . "&context=" . $data["context"] . "&signature=" . $data["signature"] ;
    
        $ch = curl_init();
        $this_header = array(
            "content-type: application/x-www-form-urlencoded; charset=gb2312"
        );
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $strUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $result=curl_exec($ch);    
        $curl_errno = curl_errno($ch);
        
        file_put_contents('gatepay.log', $result.PHP_EOL, FILE_APPEND);
        curl_close($ch);
        if ($curl_errno >0) { 
            echo '失败'; 
            return ;

        } 
        
            $dic=json_decode($result,true);

        if ($dic["errorcode"] != "0000")
            {
            
                echo ("\n请求失败!" . $dic["errorcode"] ."  ". $dic["errormessage"]);
                return;
            }

            $strSign = $dic["signature"];
            $context = base64_decode($dic["context"]);

            parse_str($context,$dic); 
            
            $Signstr = "{" . $dic["trans_id"] . "}|{" . $dic["amount"] . "}|{" . $dic["pay_url"] . "}|{" . $key . "}";
            $Sign = substr(strtoupper(md5($Signstr)),0,16);
            if ($strSign != $Sign)
            {
                echo("\n签名失败! Get：" . $strSign." Sign: ".$Sign);
                return;
            }  
            
            $pay_url = base64_decode( $dic["pay_url"] );
            echo $pay_url;
                
            header('Location:'.$pay_url)  ;
    }

    public function kj_pay($ordersn=''){
	
    	if($ordersn==''){
    		die();
    	}
	   	$Syscode ="20000053";
	    $Version = "002";
		$Account = '9201000466';
		$key = "asdf9pszpay23";//加密密钥
	    $PayCode   = $ordersn;
		$QueryCode = "100009";
		$API_GATEWAY = "API_GATEWAY";
	    $API_QWAB = "API_QWAB";
	    $Pay_server = "http://www.yitianmao.com/cgi-bin/gateway_pay_pho.cgi";  
		$Query_server =  "http://www.yitianmao.com/cgi-bin/get_result_m.cgi"; 





		$order = db('balance')->where('balance_sn',$ordersn)->where('isverified',0)->find();
		if(!$order) die();
	    $data = array(
		    'server_no' => $PayCode,
		    'app_id'=>$PayCode,
	  		'trans_time' => date("YmdHis"),// 
	  		'account' => $Account,// 
			'amount' => $order['bpprice']*100,
			'pay_mode'=>$API_QWAB,
		);
	



		$Signstr = "{" . $data["server_no"] . "}|{" . $data["trans_time"] . "}|{" .$data["account"] . "}|{" . $data["amount"] . "}|{" . $data["pay_mode"] . "}|{" . $key . "}";

		$Sign = substr(strtoupper(md5($Signstr)),0,16);

	 	$data['aging']= '2' ;
	    $data['callback_url']='http://'.$_SERVER['HTTP_HOST'].'/index/payapi/callback.html';
		$data['notify_url']='http://'.$_SERVER['HTTP_HOST'].'/index/payapi/notify.html';
		$data['memo']='地址';

	    $Context = "server_no=" . $data["server_no"]. "&trans_time=" . $data["trans_time"] . "&account=" . $data["account"] . "&amount=" . $data["amount"] . "&pay_mode=" . $data["pay_mode"] ."&aging=" . $data["aging"] . "&app_id=" . $data["app_id"] . "&callback_url=" . $data["callback_url"] . "&notify_url=" . $data["notify_url"] . "&memo=" . $data["memo"];
	    
	    $Context=mb_convert_encoding($Context, "GBK","UTF-8");
		$Context=base64_encode($Context);
		$Context= $this->PackUrlBase64($Context);
		$strUrl = $this->Postdata($Syscode,$Version,$Context,$Sign);
		list($curl_errno, $result)=$this->Post($Pay_server,$strUrl);
		$res=null;
		if ( $curl_errno >0) { 
			echo '通讯失败 errorno: '.$curl_errno; 
			return '';
    	} 
    	$dic=json_decode($result,true);
    	if ($dic["errorcode"] != "0000")
        {
            echo ("\n请求失败!" . $dic["errorcode"] ."  ". $dic["errormessage"] );
            return '';
        }
        $strSign = $dic["signature"];

        $context = $this->UnPackUrlBase64($dic["context"]);
        $context = base64_decode($context);
        $res=null;
        parse_str($context,$dic);
        $Signstr = "{" . $dic["trans_id"] . "}|{" . $dic["amount"] . "}|{" . $dic["pay_url"] . "}|{" . $key . "}";
        $Sign = substr(strtoupper(md5($Signstr)),0,16);
        if ($strSign != $Sign)
        {
            echo("\n签名失败! Get：" . $strSign." Sign: ".$Sign);
            return;
        } 
        $pay_url =   $this->UnPackUrlBase64( $dic["pay_url"] );
        $pay_url =   base64_decode( $pay_url ) ;
        $res=null;
        header('Location:'.$pay_url);
    }

	public function  Post($Url,$strUrl){	 
        $strUrl = mb_convert_encoding($strUrl, "GBK","UTF-8");  
        $ch = curl_init();
        $this_header = array(
            "content-type: application/x-www-form-urlencoded; charset=GBK");
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $strUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $result=curl_exec($ch);   
        $result =  mb_convert_encoding($result,"UTF-8", "GBK"); 

        $curl_errno = curl_errno($ch);
        curl_close($ch);
        
        return 	array($curl_errno,$result);			 

	}

	public function  Postdata($Syscode,$Version,$result,$Sign){	 
        $data=array(

            "syscode"=>$Syscode,
            "version"=>$Version,
            "context" => $result,
            "signature"=> $Sign,
        );

        $strUrl = "syscode=" . $data["syscode"] . "&version=" . $data["version"] . "&context=" . $data["context"] . "&signature=" . $data["signature"] ; 
        $strUrl = mb_convert_encoding($strUrl, "GBK","UTF-8");  
        return  $strUrl ;

	}   

	public function PackUrlBase64($strdata) {
		$strdata = str_ireplace("+","-",$strdata);
		$strdata = str_ireplace("/","_",$strdata);
		$strdata = str_ireplace("=","",$strdata);

		return $strdata;
	 }

	public function UnPackUrlBase64($strdata) {
		$strdata = str_ireplace("-","+",$strdata);
		$strdata = str_ireplace("_","/",$strdata);
	   while (strlen($strdata) % 4 != 0)
	    {
	         $strdata .= "=";
	    }    	

		return $strdata;
	 }


} 