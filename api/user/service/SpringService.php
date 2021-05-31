<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace api\user\service;

use think\Log;
use think\Db;
use api\doctor\model\DoctorOrderModel;

class SpringService
{
	protected $url;
    protected $sign;
    protected $partner;
    protected $partner_key;
    protected $atime;
    function __construct($user_id) {

    	$this->url		    = 'https://test.chunyutianxia.com';
    	$this->partner		= 'ydjkzj';
        $this->partner_key  = '2Q0pIByfmCuYOHhE';
        
        // UNIX TIMESTAMP 最小单位为秒
        $this->atime   = time();
        // 第三方用户唯一标识，可以为字母与数字组合的字符串。
        $this->user_id = $this->get_user_id($user_id);
        // 生成签名 5afda19c5d65a7a7
        $this->sign = substr(md5($this->partner_key.$this->atime.$this->user_id), 8, 16);
       

    }

    /*科室医生列表*/
    public function get_doctors($start=0,$limit=10,$clinic_no ='',$service_type='',$famous_doctor=0,$province='',$city=''){
    	$url = $this->url.'/cooperation/server/doctor/get_clinic_doctors';
    	$post_data = [
    		'clinic_no' 	=> $clinic_no,	//科室编号
    		'famous_doctor' => $famous_doctor,	//是否筛选名医 接受值:0:否, 1:是
    		'partner'		=> $this->partner,
    		'sign'			=> $this->sign,
    		'user_id'		=> $this->user_id,
    		'atime'			=> $this->atime,
    		'start_num'		=> $start,	//开始数
    		'count'			=> $limit,	//每次取的医生数
    		'province'      => $province, //省份
            'city'          => $city, //城市
    		'service_type'	=> $service_type, //不填为默认获取开通图文服务的医生，值为inquiry表示获取开通电话服务的医生
    	];

    	$res = cmf_curl_post($url,$post_data);
    	return $res;
    }
    /*查询医生*/
    public function search_doctors($keyword,$page=1){

		$url = $this->url.'/cooperation/server/doctor/search_doctor/';
    	$post_data = [
    		'partner'		=> $this->partner,
    		'sign'			=> $this->sign,
    		'user_id'		=> $this->user_id,
    		'atime'			=> $this->atime,
    		'query_text'	=> $keyword,
    		'page'		=> $page,	//开始数
    	];

    	$res = cmf_curl_post($url,$post_data);
    	return $res;
    }
    /*推荐医生*/
    public function recommend_doctors($ask){

		$url = $this->url.'/cooperation/server/doctor/get_recommended_doctors';
    	$post_data = [
    		'ask'			=> $ask,
    		'partner'		=> $this->partner,
    		'sign'			=> $this->sign,
    		'user_id'		=> $this->user_id,
    		'atime'			=> $this->atime,
    	];

    	$res = cmf_curl_post($url,$post_data);
    	return $res;
    }
    /*医生详情*/
    public function doctor_detail($doctor_id){
    	$url = $this->url.'/cooperation/server/doctor/detail';
    	$post_data = [
    		'partner'		=> $this->partner,
    		'sign'			=> $this->sign,
    		'user_id'		=> $this->user_id,
    		'atime'			=> $this->atime,
    		'doctor_id'		=> $doctor_id
    	];
    	$res = cmf_curl_post($url,$post_data);
    	return $res;
    }
    /*快速问答*/
    public function create_paid_problem($order_id){
        $order = Db::name('doctor_order')->where('id',$order_id)->find();
        $url = $this->url.'/cooperation/server/problem/create_paid_problem/';
        $content_list = [
		    ['type'=>'text','text'=>$order['problem']],
		    // array ('type'=>'image','file'=>'这是图片形式的内容,这里是图片的 url'),
		    // array ('type'=>'audio','file'=>'这是语音形式的内容,这里是音频文件的 url'),
		    // array ('type'=>'patient_meta','age'=>'15 岁', 'sex'=>'男'),
		    ['type'=>'patient_meta','age'=>$order['patient_age'].'岁', 'sex'=>$order['patient_sex']]
		];
		
		$content_str = json_encode($content_list,JSON_UNESCAPED_UNICODE);
    	$post_data = [
    		'user_id'		=> $this->user_id,
    		'partner'		=> $this->partner,
    		'content'		=> $content_str,
    		'sign'			=> $this->sign,
    		'atime'			=> $this->atime,
    		'partner_order_id' => $order['order_sn'],
    		'pay_type'      => 'qc_hospital_common'
    	];
    	//二甲医生：qc_hospital_common三甲医生：qc_hospital_upgrade
    	$res = cmf_curl_post($url,$post_data);
    	
    	if($res){
    	    if($res['error'] == 0){
        	    //无法更新
        	    if($res['problem_id']){
        	        Db::name('doctor_order')->where('id',$order_id)->update(['problem_id'=>$res['problem_id'],'order_status'=>1]);
            	    
            		Db::name('chat_record')->insert([
            			'order_id'			=> $order_id,
            			'user_id' 		    => $order['user_id'],
            			'send_user_id'	    => $order['user_id'],
            			'add_time'			=> time(),
            			'msg'				=> $order['problem'],
            			'problem_id'		=> $res['problem_id']
            		]);
        	    }
        		
        	}
    	}
    	return $res;
    }
    /*创建定向问题*/
    public function create_oriented($order_id,$content){
        $param = request()->param();
        $update = [];
        if(!empty($param['user_name'])) $update['user_name'] = $param['user_name'];
        if(!empty($param['patient_age'])) $update['patient_age'] = $param['patient_age'];
        if(!empty($param['user_name'])) $update['patient_sex'] = $param['patient_sex'];
        Db::name('doctor_order')->where('id',$order_id)->update($update);
        
    	$order = Db::name('doctor_order')->where('id',$order_id)->find();
        //$url = $this->url.'/cooperation/server/phone/create_oriented_order/';
        $url = $this->url."/cooperation/server/problem/create_oriented_problem/";
        $content_list = [
            ['type'=>'text','text'=>$content],
            ['type'=>'patient_meta','age'=>$order['patient_age'].'岁', 'sex'=>$order['patient_sex']]

        ];

        if (!empty($order['patient_photo'])){
            array_push($content_list,['type'=>'image','file'=>$order['patient_photo']]);
        }

		$content_str = json_encode($content_list,JSON_UNESCAPED_UNICODE);
    	$post_data = [
    		'partner'		=> $this->partner,
    		'sign'			=> $this->sign,
    		'user_id'		=> $this->user_id,
    		'atime'			=> $this->atime,
    		'doctor_ids'	=> $order['doctor_id'],
    		'partner_order_id' => $order['order_sn'],
    		'price'			=> $order['order_amount']*100,
    		'content'		=> $content_str
    	];
    	$res = cmf_curl_post($url,$post_data);
    	if($res['error'] == 0){
    		Db::name('doctor_order')->where('id',$order_id)->update(['problem_id'=>$res['problems']['problem_id']]);
    		Db::name('chat_record')->insert([
    			'order_id'			=> $order_id,
    			'user_id' 		    => $order['user_id'],
    			'send_user_id'	    => $order['user_id'],
    			'receive_user_id'	=> $order['doctor_id'],
    			'add_time'			=> time(),
    			'msg'				=> $content,
    			'problem_id'		=> $res['problems']['problem_id']
    		]);
    	}
    	return $res;
    }
    /*追问问题*/
    public function problem_content($order_id,$content){
    	$order = Db::name('doctor_order')->where('id',$order_id)->find();
    	$url = $this->url.'/cooperation/server/problem_content/create';
    	$content_list = [
		    ['type'=>'text','text'=>$content],
		    // array ('type'=>'image','file'=>'这是图片形式的内容,这里是图片的 url'),
		    // array ('type'=>'audio','file'=>'这是语音形式的内容,这里是音频文件的 url'),
		    // array ('type'=>'patient_meta','age'=>'15 岁', 'sex'=>'男'),
		];
		$content_str = json_encode($content_list,JSON_UNESCAPED_UNICODE);
    	$post_data = [
    		'partner'		=> $this->partner,
    		'sign'			=> $this->sign,
    		'user_id'		=> $this->user_id,
    		'atime'			=> $this->atime,
    		'problem_id'	=> $order['problem_id'],
    		'content'		=> $content_str
    	];
    	$res = cmf_curl_post($url,$post_data);
    	if($res['error'] == 0){
    		//Db::name('order')->where('id',$order_id)->update(['problem_id'=>$res['problems']['problem_id']]);
    		Db::name('chat_record')->insert([
    			'order_id'			=> $order_id,
    			'user_id' 		    => $order['user_id'],
    			'send_user_id'	    => $order['user_id'],
    			'receive_user_id'	=> $order['doctor_id'],
    			'add_time'			=> time(),
    			'msg'				=> $content,
    			'content_id'		=> $res['content_id']
    		]);
    	}
    	return $res;
    }
    /*创建快捷电话接口*/
    public function create_fast_phone_order($order_id){
        
        $order = Db::name('phone_order')->where('id',$order_id)->find();
        $url = $this->url.'/cooperation/server/phone/create_fast_phone_order/';
        $content_list = [
		    ['type'=>'text','text'=>$order['problem']],
		    //['type'=>'patient_meta','age'=>$order['patient_age'].'岁', 'sex'=>$order['patient_sex']]
		];
		$content_str = json_encode($content_list,JSON_UNESCAPED_UNICODE);
    	$post_data = [
    		'user_id'		=> $this->user_id,
    		'partner'		=> $this->partner,
    		'content'		=> $content_str,
    		'sign'			=> $this->sign,
    		'atime'			=> $this->atime,
    		'partner_order_id' => $order['order_sn'],
    		'clinic_no'     => $order['clinic_no'],   //科室号
    		'phone'         => $order['phone'],
    		'content'       => $content_str
    	];
    	$res = cmf_curl_post($url,$post_data);
    	
    	
    	if($res['error'] == 0){
    	    if($res['service_id']){
    	        Db::name('phone_order')->where('id',$order_id)->update(['service_id'=>$res['service_id'],'order_status'=>1]);
    	    }
    		
    	
    	}
    	return $res;
    }
    
    /*
     * 创建定向电话
     * @param int $order_id订单id
     * return array
     */
    public function create_oriented_order($order_id)
    {
 
        $order = $order = Db::name('phone_order')->where('id',$order_id)->find();
        $url = $this->url.'/cooperation/server/phone/create_oriented_order/';
        /*
        $content_list = [
            ['type'=>'text','text'=>$order['problem']],
            ['type'=>'image','file'=>$order['patient_photo']],
            ['type'=>'patient_meta','age'=>$order['patient_age'].'岁', 'sex'=>$order['patient_sex']]
        ];*/
        if (empty($order['patient_photo'])){
            $content_list = [
                ['type'=>'text','text'=>$order['problem']],
                ['type'=>'patient_meta','age'=>$order['patient_age'].'岁', 'sex'=>$order['patient_sex']]
            ];

        }else{
            $content_list = [
                ['type'=>'text','text'=>$order['problem']],
                ['type'=>'image','file'=>$order['patient_photo']],
                ['type'=>'patient_meta','age'=>$order['patient_age'].'岁', 'sex'=>$order['patient_sex']]
            ];
        }

        $content_str = json_encode($content_list,JSON_UNESCAPED_UNICODE);

        $post_data = [
            'user_id'           => $this->user_id,   //用户id
            'partner'           => $this->partner,   //合作方标识
            'content'           => $content_str,     //电话补充描述内容
            'sign'              => $this->sign,      //签名
            'atime'             => $this->atime,     //时间戳
            'partner_order_id'  => $order['order_sn'],  //合作方支付ID
            'doctor_id'         => $order['doctor_id'], //doctor_id
            'minutes'           => $order['minutes'],   //拨打时长
            'tel_no'            => $order['phone'],     //用户电话
            'price'             => (int)($order['total_amount']),     //int 订单价格
            'inquiry_time'      => $order['inquiry_time']  //预约时间
        ];

        $res = cmf_curl_post($url,$post_data);

        if($res['error'] == 0){
            if($res['service_id']){
                Db::name('phone_order')->where('id',$order_id)->update([
                    'service_id'=>$res['service_id'],
                    'order_status' =>1,
                    'first_dial_time' => $res['inquiry_time'],  //拨打开始时间 格式如"2018-01-28 09:30"
                ]);
            }
        }
        return $res;
    }


    /*获取医生电话信息*/
    public function get_doctor_phone_info($doctor_id)
    {
        $url = $this->url.'/cooperation/server/phone/get_doctor_phone_info/';
        $post_data = [
            'user_id'       => $this->user_id,
            'partner'       => $this->partner,
            'sign'          => $this->sign,
            'atime'         => time(),
            'doctor_id'     => $doctor_id
        ];
        $res = cmf_curl_post($url,$post_data);
        return $res;


    } 
    
   
    /*获取用户user_id*/
    public function get_user_id($user_id){
    	$url = $this->url.'/cooperation/server/login';
    	$user_name = 'cy_'.$user_id;
		$this->sign = substr(md5($this->partner_key.$this->atime.$user_name), 8, 16);
		$insert_date = [
			'partner'	=> $this->partner,
			'sign'		=> $this->sign,
			'atime'		=> $this->atime,
			'user_id'   => $user_name,
			'password'  => '123456'
		];
    	$user = Db::name('cy_user')->where('user_id',$user_id)->find();
    	if($user){
    		if($user['status'] == 0){
				$res = cmf_curl_post($url,$insert_date);
    			if($res['error'] == 0){
    				Db::name('cy_user')->update(['status'=>1]);
    			}
    		}
    	}else{
    		$res = cmf_curl_post($url,$insert_date);
    		if($res['error'] == 0){
    			$insert_date['user_id']  = $user_id;
    			$insert_date['user_name']= $user_name;
    			$insert_date['add_time'] = time();
    			$insert_date['status']   = 1;
				Db::name('cy_user')->strict(false)->insert($insert_date);
    		}
    	}

    	return $user_name;
    }
    
    //问题详情
    public function problem_detail($order_id)
    {
        $order = Db::name('doctor_order')->where('id',$order_id)->find();
        $url = $this->url.'/cooperation/server/problem/detail';
        $post_data = [
            'user_id'		=> $this->user_id,
            'partner'		=> $this->partner,
            'problem_id'    => $order['problem_id'],
            'sign'			=> $this->sign,
            'atime'			=> $this->atime,
        ];
        $res = cmf_curl_post($url,$post_data);
        return $res;
    }
    

}
