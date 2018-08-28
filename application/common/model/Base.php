<?php
namespace app\common\model;

use think\Model;
use think\Db;
use think\Session;
class base extends Model
{
    //自动完成[新增和修改时都会执行]
    protected $auto =[
    ];
    //新增时自动验证
    protected $insert=[
    ];
    //修改时自动验证
    protected $update=[
    ];
	//写入管理员日志
	public function CreateAdminLog($type,$content){
		$data = array(
			"type" => $type,
			"username" => Session::get('admin_name'),
			"userid" => Session::get('admin_id'),
			"content" => $content,
			"addtime" => date("Y-m-d H:i:s"),
			"referpage" => $_SERVER['HTTP_REFERER'],
			"ip" => request()->ip(),
			"client" => $_SERVER['HTTP_USER_AGENT']
		);
		Db::table('scy_log_admin')->insert($data);		
	}
	//写入商户日志
	public function CreateAgentLog($type,$content){
		$data = array(
			"type" => $type,
			"userid" => Session::get('agent_id'),
			"username" => Session::get('agent_name'),
			"phone" => Session::get('agent_phone'),
			"content" => $content,
			"addtime" => date("Y-m-d H:i:s"),
			"referpage" => $_SERVER['HTTP_REFERER'],
			"ip" => request()->ip(),
			"client" => $_SERVER['HTTP_USER_AGENT']
		);
		Db::table('scy_log_agent')->insert($data);		
	}
	//写入保险日志
	public function CreateInsurerLog($type,$content){
		$data = array(
			"type" => $type,
			"userid" => Session::get('insurer_id'),
			"username" => Session::get('insurer_name'),
			"content" => $content,
			"addtime" => date("Y-m-d H:i:s"),
			"referpage" => $_SERVER['HTTP_REFERER'],
			"ip" => request()->ip(),
			"client" => $_SERVER['HTTP_USER_AGENT']
		);
		Db::table('scy_log_insurer')->insert($data);		
	}
	//写入用户日志
	public function CreateUserLog($type,$content){
		$data = array(
			"type" => $type,
			"username" => Session::get('user_name'),
			"phone" => Session::get('user_phone'),
			"content" => $content,
			"addtime" => date("Y-m-d H:i:s"),
			"referpage" => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],
			"ip" => request()->ip(),
			"ipcity" => $this->getCity(request()->ip()),
			"client" => $_SERVER['HTTP_USER_AGENT']
		);
		Db::table('scy_log_user')->insert($data);		
	}
	//写入短信日志
	public function CreateSmscode($code,$phone){
		$data = array(
			"phone" => $phone,
			"smscode" => $code,
			"sendtime" => date("Y-m-d H:i:s"),
			"stat" => 1,
			"expiredtime" => date("Y-m-d H:i:s",strtotime("+5 min")),
			"ip" => request()->ip()
		);
		Db::table('scy_smscode')->insert($data);	
	}
	
	//获取省份
	public function Getbyprovince($id){
		$temp = Db::table('base_province')->where('province_id',$id)->field("province_name")->find();
		return $temp["province_name"];
		
	}
	
	//获取城市
	public function Getbycity($id){
		$temp = Db::table('base_city')->where('city_id',$id)->field("city_name")->find();
		return $temp["city_name"];
	}
	
	//获取地区
	public function Getbyarea($id){
		$temp = Db::table('base_area')->where('area_id',$id)->field("area_name")->find();
		return $temp["area_name"];
	}
	
	//获取审核组-管理员
	public function Getadmin($id){
		$temp = model('admin')->where('id',$id)->field("nickname,sid")->find();
		$temp_inspect = model('admininspect')->where('id',$temp["sid"])->field("title")->find();
		return $temp_inspect["title"]."-".$temp["nickname"];
	}

	public function GetCheckAuth(){
        $id = session('admin_id');
        $admin = model("admin")->where("id",$id)->find()->toArray();
        $admingroup = model("admininspect")->where("id",$admin["sid"])->find()->toArray();
		$groupmodes = explode("|",$admingroup["modes"]); 
		if($admingroup["modes"]=="0"){
			$groupmodes = array(
				"inquiry_1","inquiry_2","inquiry_-1",
				"fqorder_2","fqorder_3","fqorder_-1",
				"order_1","order_2","order_3","order_-1",
				"agent_1","agent_2","agent_3","agent_-1",
				"finance_1",
				"user_1","user_2","user_3","user_-1",
			);
		}
		return $groupmodes;	
	}
	
	//获取保险公司类型
	public function Getcompany($id){
		$temp = model('insurancecompany')->where('id',$id)->field("name")->find();
		return $temp["name"];
	}
	
	//获取保险公司
	public function Getinsurer($id){
		$temp = model('insurer')->where('id',$id)->field("companyname")->find();
		if($id==0){
			$temp["companyname"] = "平台";
		}
		return $temp["companyname"];
	}

	
	/****************************/
	//数据列表导出
	//标题数组，数据列表，对应宽度
	//author:三年二班：最帅的
	/***************************/
	public function Toexcel($titary,$list,$styleary = array()){
		$charary = array(0=>"A",1=>"B",2=>"C",3=>"D",4=>"E",5=>"F",6=>"G",7=>"H",8=>"I",9=>"J",10=>"K",11=>"L",12=>"M",13=>"N",14=>"O",15=>"P",16=>"Q",17=>"R",18=>"S",19=>"T",20=>"U",21=>"V",22=>"W",23=>"X",24=>"Y",25=>"Z");
		vendor("PHPExcel.PHPExcel");
		$objPHPExcel = new \PHPExcel();	
		$sharedStyle1 = new \PHPExcel_Style();
		$sharedStyle1->applyFromArray(
		array('borders' => array(
				'left'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
				'right'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
				'top'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
				'bottom'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
			)
		));
		
		$lastitem = $charary[count($list["tit"])-1];
		$item = 1;
		foreach($titary as $key=>$val){
			$objPHPExcel->getActiveSheet()->getRowDimension($item)->setRowHeight(30);
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$item,$val);
			$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);	
			$objPHPExcel->getActiveSheet()->mergeCells('A'.$item.':'.$lastitem.''.$item); 
			$item++;
		}
		
		$objPHPExcel->getActiveSheet()->getDefaultColumnDimension('A')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getRowDimension($item)->setRowHeight(30);

		$tempary = [];
		foreach($list["tit"] as $k=>$v){
			$tempary[] = $k;
		}
		foreach($tempary as $k=>$v){
			if(isset($styleary[$v])){
				$objPHPExcel->getActiveSheet()->getColumnDimension($charary[$k])->setWidth($styleary[$v]);
			}
			$objPHPExcel->getActiveSheet()->setCellValueExplicit($charary[$k].$item,$list["tit"][$v],\PHPExcel_Cell_DataType::TYPE_STRING);
		}
		unset($list["tit"]);
		$item++;	
		foreach($list as $key=>$row){		
			$objPHPExcel->getActiveSheet()->getRowDimension($item)->setRowHeight(30);
			foreach($tempary as $k=>$v){
				$objPHPExcel->getActiveSheet()->setCellValueExplicit($charary[$k].$item,$row[$v],\PHPExcel_Cell_DataType::TYPE_STRING);
			}
			$item++;
		}
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A1:".$lastitem.($item-1)); 
		$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:".$lastitem.($item-1))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:".$lastitem.($item-1))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);	

		$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
		$file_name = date('YmdHis').'.xls';
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:applicationnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header("Content-Disposition:attachment; filename=".$file_name);
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit;
	}

	/****************************/
	//单页列表导出
	//标题数组，数据，最后一个单元格
	//author:三年二班：最帅的
	//单元格(A)，单元格填充(uploads/20171020/3ff9134973ba25b8e4cc33e16c840113.jpg)，扩展单元格(B), 单元格宽度 , 单元格高度 ,是否是图片,扩展单元格(列)
	/***************************/
	public function Toexcelinfo($titary,$list,$lastitem){
		$charary = array(0=>"A",1=>"B",2=>"C",3=>"D",4=>"E",5=>"F",6=>"G",7=>"H",8=>"I",9=>"J",10=>"K",11=>"L",12=>"M",13=>"N",14=>"O",15=>"P",16=>"Q",17=>"R",18=>"S",19=>"T",20=>"U",21=>"V",22=>"W",23=>"X",24=>"Y",25=>"Z");
		vendor("PHPExcel.PHPExcel");
		$objPHPExcel = new \PHPExcel();	
		$sharedStyle1 = new \PHPExcel_Style();
		$sharedStyle1->applyFromArray(
		array('borders' => array(
				'left'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
				'right'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
				'top'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
				'bottom'        => array('style' => \PHPExcel_Style_Border::BORDER_MEDIUM),
			)
		));
		$objPHPExcel->getActiveSheet()->getDefaultColumnDimension('A')->setWidth(15);		
		
		$item = 1;
		foreach($titary as $key=>$val){
			$objPHPExcel->getActiveSheet()->getRowDimension($item)->setRowHeight(30);
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$item,$val);
			$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);	
			$objPHPExcel->getActiveSheet()->mergeCells('A'.$item.':'.$lastitem.''.$item); 
			$item++;
		}
		foreach($list as $k=>$v){
			foreach($v as $key=>$val){
				$temp_height = isset($val[4])?$val[4]:30;
				$temp_width = isset($val[3])?$val[3]:0;
				if($temp_width){$objPHPExcel->getActiveSheet()->getColumnDimension($val[0])->setWidth($temp_width);}
				$objPHPExcel->getActiveSheet()->getRowDimension($item)->setRowHeight($temp_height);
				if(isset($val[6])){
					if(isset($val[2])&&$val[2]){$objPHPExcel->getActiveSheet()->mergeCells($val[0].$item.':'.$val[2].($item+$val[6])); }
				}else{
					if(isset($val[2])&&$val[2]){$objPHPExcel->getActiveSheet()->mergeCells($val[0].$item.':'.$val[2].$item); }
				}
				if(isset($val[5])){
					if($val[5]==1){
						$img = 	$val[1];
						if(strstr($img,"http")){
							if(strstr($img,"png")){
								$img = imagecreatefrompng($img);
							}else{
								$img = imagecreatefromjpeg($img);
							}
							$width = imagesx($img);
							$height = imagesy($img);
							$objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
							$objDrawing->setImageResource($img);
							$objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_DEFAULT);//渲染方法
							$objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
						}else{
							$objDrawing = new \PHPExcel_Worksheet_Drawing();
							if(substr($img,0,1)=="/"){
								$img = substr($img,1);	
							}
							$objDrawing->setPath($img);
						}
						$objDrawing->setCoordinates($val[0].$item); 
						$objDrawing->getShadow()->setVisible(true);
						$objDrawing->getShadow()->setDirection(50);
						$objDrawing->setOffsetX(10); 
						$objDrawing->setOffsetY(10); 
						$objDrawing->setHeight($temp_height); 
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet()); 						
					}else{
						$objPHPExcel->getActiveSheet()->setCellValueExplicit($val[0].$item,$val[1],\PHPExcel_Cell_DataType::TYPE_STRING);	
					}
				}else{
					$objPHPExcel->getActiveSheet()->setCellValueExplicit($val[0].$item,$val[1],\PHPExcel_Cell_DataType::TYPE_STRING);
				}
			}
			$item++;
		}		
		
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A1:".$lastitem.($item-1)); 
		$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:".$lastitem.($item-1))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle("A1:".$lastitem.($item-1))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
		$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
		$file_name = date('YmdHis').'.xls';
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:applicationnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header("Content-Disposition:attachment; filename=".$file_name);
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit;
	}
	///****************************/
	//获取险种
	/***************************/
	public function GetInsurance($rs,$str="<strong style=\"color:green\">√ </strong>",$end="<br>"){
		$insurance = model("userinsurance")->getByRs($rs);
		$temp_str = "";
		$temp_str .= $insurance["jqxian"]?$str."交强险".$end:"";
		$temp_str .= $insurance["ccxian"]?$str."车船税".$end:"";
		$config = model("config")->find();
		$order_vaule = $config["orderset_value"];
		$orderset = json_decode($order_vaule,true);
		if($insurance["syxian"]){
			$temp_str .= $str."商业险".$end;
			if($insurance["csxian"]){
				$temp_str .= $str."车损险";
				$temp_str .= ($insurance["bj_cs"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["dszxian"]){
				$temp_str .= $str."第三者险";
				$temp_str .= $insurance["dszxian_opt"]?" ".$insurance["dszxian_opt"]."元":"";
				$temp_str .= ($insurance["bj_dsz"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["dqxian"]){
				$temp_str .= $str."盗抢险";
				$temp_str .= ($insurance["bj_dq"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["zwxian"]){
				$temp_str .= $str."司机座位险";
				$temp_str .= $insurance["zwxian_cn"]?" ".$insurance["zwxian_cn"]."万元":"";
				$temp_str .= ($insurance["bj_zw"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["ckxian"]){
				$temp_str .= $str."乘客座位险";
				$temp_str .= $insurance["ckxian_cn"]?" ".$insurance["ckxian_cn"]."万元/座":"";
				$temp_str .= ($insurance["bj_ck"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["zrxian"]){
				$temp_str .= $str."自燃险";
				$temp_str .= ($insurance["bj_zr"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["blxian"]){
				$temp_str .= $str."玻璃险";
				$temp_str .= " ".$orderset["blxian"][$insurance["blxian_opt"]];
			}
			if($insurance["ssxian"]){
				$temp_str .= $str."涉水险";
				$temp_str .= ($insurance["bj_ss"]?"(".$str."不计免赔)":"").$end;
			}
			if($insurance["hhxian"]){
				$temp_str .= $str."车身划痕险";
				$temp_str .= $insurance["hhxian_opt"]?" ".$insurance["hhxian_opt"]."元":"";
				$temp_str .= ($insurance["bj_hh"]?"(".$str."不计免赔)":"").$end;
			}
		}
		return $temp_str;

	}

	//此函数已废弃，暂保留，以防遗漏的地方还有调用
	public function GetInsurance_s($rs,$str="<td class='text-r'>",$end="</td>",$other="</tr><tr>"){
		$insurance = model("userinsurance")->getByRs($rs);
		$config = model("config")->find();
		$order_vaule = $config["orderset_value"];
		$orderset = json_decode($order_vaule,true);
		$temp_str = "";
		if($insurance["syxian"]){
			if($insurance["csxian"]){
				$temp_str .= $str."<span>车损险</span>";
				$temp_str .= ($insurance["bj_cs"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			if($insurance["dszxian"]){
				$temp_str .= $str."<span>第三者险</span>";
				$temp_str .= $insurance["dszxian_opt"]?" <input type='text' disabled  value='".$insurance["dszxian_opt"]."'><span>元</span>":"";
				$temp_str .= ($insurance["bj_dsz"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			$temp_str .= $other;
			if($insurance["dqxian"]){
				$temp_str .= $str."<span>盗抢险</span>";
				$temp_str .= ($insurance["bj_dq"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			if($insurance["zwxian"]){
				$temp_str .= $str."<span>司机座位险</span>";
				$temp_str .= $insurance["zwxian_cn"]?" ".$insurance["zwxian_cn"]."万元":"";
				$temp_str .= ($insurance["bj_zw"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			$temp_str .= $other;
			if($insurance["ckxian"]){
				$temp_str .= $str."<span>乘客座位险</span>";
				$temp_str .= $insurance["ckxian_cn"]?" ".$insurance["ckxian_cn"]."万元/座":"";
				$temp_str .= ($insurance["bj_ck"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			if($insurance["zrxian"]){
				$temp_str .= $str."<span>自燃险</span>";
				$temp_str .= ($insurance["bj_zr"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			$temp_str .= $other;
			if($insurance["blxian"]){
				$temp_str .= $str."<span>玻璃险</span>";
				$temp_str .= " ".$orderset["blxian"][$insurance["blxian_opt"]];
			}
			if($insurance["ssxian"]){
				$temp_str .= $str."<span>涉水险</span>";
				$temp_str .= ($insurance["bj_ss"]?"<input type='text'  value='(不计免赔)'><span>元</span>":"").$end;
			}
			$temp_str .= $other;
			if($insurance["hhxian"]){
				$temp_str .= $str."<span>乘客座位险</span>";
				$temp_str .= $insurance["hhxian_opt"]?" ".$insurance["hhxian_opt"]."元":"";
				$temp_str .= ($insurance["bj_hh"]?"(不计免赔)<span>元</span>":"").$end.$other;
			}
		}
		return $temp_str;

	}	
	

	//获取审核组-管理员
	public function Getagenttype($id){
		$agentclass = model('agentclass')->where('id',$id)->field("name")->find();
		return $agentclass['name'];
	}


	/***************************************************************************************************************/
	//以下为base64图片处理函数
	//author:三年二班：最帅的
	/***************************************************************************************************************/	

	//保存64位图片
    function saveBase64Image($base64_image_content){    
		//判断64位格式
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
			//图片后缀
			$type = $result[2];
			//保存位置--图片名
			$image_name=date('His').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT).".".$type;
			$image_url = '/uploads/'.date('Ymd').'/'.$image_name;           
			if(!is_dir(dirname('.'.$image_url))){
				mkdir(dirname('.'.$image_url));
				chmod(dirname('.'.$image_url), 0777);
			}
			//解码
			$decode=base64_decode(str_replace($result[1], '', $base64_image_content));
			if (file_put_contents('.'.$image_url, $decode)){
				$data['code']=0;
				$data['imageName']=$image_name;
				$data['url']=$image_url;
				$data['msg']='保存成功！';
			}else{
			$data['code']=1;
			$data['imgageName']='';
			$data['url']='';
			$data['msg']='图片保存失败！';
			}
		}else{
			$data['code']=1;
			$data['imgageName']='';
			$data['url']='';
			$data['msg']='base64图片格式有误！';
		}       
		return $data;


	}	

	//图片转base64
	function base64EncodeImage($image_file){
		$base64_image = '';
		$image_info = getimagesize($image_file);
		$image_data = fread(fopen($image_file, 'r'), filesize($image_file));
		$base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
		return $base64_image;
	}		

	/***************************************************************************************************************/
	//以下为公用接口函数
	//author:三年二班：最帅的
	/***************************************************************************************************************/

	

	//根据IP获取城市
	public function getCity($ip)
	{
	   $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
	   $ipinfo=json_decode(file_get_contents($url)); 
	   if($ipinfo->code=='1'){
		   return false;
	   }
	   $city = $ipinfo->data->region.$ipinfo->data->city;
	   return $city; 
	}

	//根据手机号码获取城市
	public function getProvince($mobile){
		$content = $this->getphoneinfo($mobile);
		$result = json_decode($content,true);
		return $result["province"];
	}

	//根据手机号码获取手机信息数据
	public function getphoneinfo($mobile){
		$sms = array('province'=>'', 'supplier'=>'');    //初始化变量
		//根据淘宝的数据库调用返回值
		$url = "http://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel=".$mobile."&t=".time();
		$content = file_get_contents($url);
        $content = iconv("gb2312", "utf-8//IGNORE",$content);
        $temparray = explode("{",$content);
        $temparray = explode("}",$temparray[1]);
        $temparray = explode(",",$temparray[0]);
        foreach($temparray as $key=>$val){
            $temp = explode(":",$val);
            $result[trim($temp[0])] = str_replace("'","",$temp[1]);
        }
        return json_encode($result);		
	}
	
	//获取快递状态
	public function getexress($expressid,$expresstype = ""){
		$url = "http://www.kuaidi100.com/query?type=shunfeng&postid=".$expressid;
		$content = file_get_contents($url);
		return $content;
	}
	
	//短信接口-吉信通 
	public function smssend_j($tos,$msg){
		header("Content-type: application/json; charset=utf-8");	
		$params = array(
			'tos' => $tos,
			'msg' => $msg."退订回T".config('SMSPRE'),
			'appkey' => config('JDAPPKEY')
		);
		$url = 'https://way.jd.com/jixintong/SMSmarketing';
		return $this->wx_http_request($url, $params );	
		
	}


	
	//短信接口-创瑞云
	public function smssend($tos,$msg){
		header("Content-type: application/json; charset=utf-8");
		$params = array(
			'sign' => config('SMSPRE'),
			'mobile' => $tos,
			'content' => $msg,
			'appkey' => config('JDAPPKEY')
		);
		$url = 'https://way.jd.com/chonry/sms';
		return $this->wx_http_request($url, $params );	
		
	}

	//信息提醒
	public function msgsend($msg,$phone="",$openid="",$templateid="",$url="",$data=""){
		$result = 0;
		//发送短信
		if($phone){
			$returntxt  = $this->smssend($phone,$msg);
			$returntxt = json_decode($returntxt,true);  
			if($returntxt["code"]=="10000"){
				$result = 1;
			}
		}
		//发送模板消息
		if($openid){
			if($url){
				$template_msg=array('touser'=>$openid,'template_id'=>$templateid,'url'=>$url,'topcolor'=>'#2D2F2D','data'=>$data);
			}else{
				$template_msg=array('touser'=>$openid,'template_id'=>$templateid,'topcolor'=>'#2D2F2D','data'=>$data);	
			}
			$result = $result?2:3;
		}
		return $result;
	}

	//短信验证
	public function smscheck($phone,$code){
		$coderesult = model("smscode")->where(["phone"=>$phone,"stat"=>1])->find();
		if(!$coderesult){
			return "短信已超时，请重新获取";
		}
		if($coderesult["smscode"]!=$code){
			return "短信验证码不正确，请重新输入";
		}	
		if(strtotime($coderesult["expiredtime"])<strtotime(date("Y-m-d H:i:s"))){
			return "短信已超时，请重新获取";
		}
		return 1;
	}

	//获取物流接口
	public function getexpress($expressid){
		header("Content-type: application/json; charset=utf-8");
		$params = array(
			'no' => $expressid,
			'type' => '',
			'appkey' => config('JDAPPKEY')
		);
		$url = 'https://way.jd.com/fegine/kdwlc';
		return $this->wx_http_request($url, $params );
	}

	//京东万象接口
    public function wx_http_request($url, $params, $body="", $isPost=false, $isImage=false ) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url."?".http_build_query($params));
        if($isPost){
            if($isImage){
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: multipart/form-data;',
                        "Content-Length: ".strlen($body)
                    )
                );
            }else{
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: text/plain'
                    )
                );
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
	}	
	

}
