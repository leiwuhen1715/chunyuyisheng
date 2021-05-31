<?php

// +----------------------------------------------------------------------
namespace api\doctor\controller;

use think\Db;
use api\user\service\SpringService;
use cmf\controller\RestBaseController;

class OrderController extends RestBaseController
{
    
    //定向问诊
    public function create(){
    	$user_id  = $this->getUserId();
        $doctor_id = request()->param('doctor_id');    //
        $pay_code  = request()->param('pay_code'); 

        if(empty($pay_code))	$this->error('请选择支付方式');

        $service = new SpringService($user_id);
        $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';
        $res = $service->doctor_detail($doctor_id);
       
        if($res['error'] == 0){
        	$where = [
        		['user_id','=',$user_id],
        		['pay_status','=',0],
        		['doctor_id','=',$doctor_id]
        	];
        	$order = Db::name('doctor_order')->where($where)->find();
        	if($order) 	$this->success('ok', $res['order_sn']);
        	
        	$order_sn = get_order_sn();
        	
        	
        	//价格修改
        	//$price = $res['price']/100;
        	$price = $res['price']/1000;
        	
        	
        	$transStatu=0;
	        Db::startTrans(); //开启事务
	        try {
	        	$pay_data = [
	                'amount'   => $price,
	                'is_paid'  => 0,
	                'type'     => 4,
	                'goods_name' => '图文咨询'.$res['name'],
	                'order_sn' => $order_sn,
	                'pay_code' => $pay_code,
	                'add_time' => time(),
	                'user_id'  => $user_id
	            ];
	            $pay_id = Db::name('pay_log')->insertGetId($pay_data);
	        	$insert_data = [
	        		'user_id'  		=> $user_id,
	        		'add_time' 		=> time(),
	        		'doctor_id'   	=> $doctor_id,
	        		'doctor_name'   => $res['name'],
	        		'doctor_img'	=> $res['image'],
	        		'order_amount'	=> $price,
	        		'total_amount'	=> $price,
	        		'pay_name'		=> $pay_name,
	        		'pay_code'		=> $pay_code,
	        		'order_sn'		=> $order_sn
	        	];
	        	$order_id = Db::name('doctor_order')->insertGetId($insert_data);
	            $transStatu=1;
	            Db::commit();
	        } catch (\Exception $e) {
	            $msg= $e->getMessage();
	            $order_id=0;
	            // 回滚事务
	            Db::rollback();
	        }
	        if($transStatu == 1){
	        	$this->success('ok', $order_sn);
	        }else{
	        	$this->error($msg);
	        }
        }else{
            $this->error($res['error_msg']);
        }
    }
    
    public function getQuestionOrder(){
        $order_id = request()->param('order_id',0,'intval');
        $user_id  = $this->getUserId();
        $field = 'id,doctor_id,total_amount,coupon_amount,order_amount,order_sn,doctor_name,patient_name';
        $where = [
            ['id','=',$order_id],
            ['user_id','=',$user_id]
        ];
        $order = Db::name('doctor_order')->field($field)->where($where)->find();
        if($order){
            $this->success('ok',$order);
        }else{
            $this->error('订单不存在');
        }
        
    }
    
    //极速义诊
    public function questionCreate(){
    	$user_id      = $this->getUserId();
        $problem      = request()->param('problem');   //患者问题
        $patient_name = request()->param('user_name'); //患者姓名
        $patient_sex  = request()->param('sex');       //患者性别
        $patient_age  = request()->param('age');       //患者年龄
        $coupon_id    = 0; //优惠卷id
    	
    	if(empty($problem))        $this->error('请填写问题');
    	if(empty($patient_name))   $this->error('请填写姓名');
    	if(empty($patient_sex))    $this->error('请填写性别');
    	if(empty($patient_age))    $this->error('请填写年龄');
    	
    	//疾病标签匹配
        $problem_length = strlen($problem);
        if ($problem_length < 30){
            $field = 'label_content';
            $where = ['label_name' => $problem];

            $res = Db::name('disease_label')->field($field)->where($where)->find();
            $problem = $res['label_content'];
        }
    	
        $pay_code  = request()->param('pay_code');
        // if(empty($pay_code))	$this->error('请选择支付方式');
        // $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';
        $pay_code = $pay_name  = '';
        $order_sn = get_order_sn();
    	$coupon_amount = 0;
    	$total_amount = $order_amount = 0.01;
    	
    	/*$coupon = Db::name('coupon')->where(['user_id'=>$user_id,'id'=>$coupon_id,'is_use'=>0,'type'=>0])->find();
    	if($coupon){
    	    
    	    if($coupon['end_time'] < time())        $this->error('优惠券已过期');
    	    if($coupon['total_amount'] > $total_amount)    $this->error('未达到金额');
    	    $coupon_amount = $coupon['amount'];
    	    $order_amount  = $total_amount-$coupon['amount'];
    	}else{
    	    $coupon_id = 0;
    	}*/
    	$transStatu=0;
        Db::startTrans(); //开启事务
        try {
        	$pay_data = [
                'amount'   => $order_amount,
                'is_paid'  => 0,
                'type'     => 4,
                'goods_name' => '极速义诊',
                'order_sn' => $order_sn,
                'pay_code' => $pay_code,
                'add_time' => time(),
                'user_id'  => $user_id
            ];
            $pay_id = Db::name('pay_log')->insertGetId($pay_data);
        	$insert_data = [
        		'user_id'  		=> $user_id,
        		'add_time' 		=> time(),
        		'order_type'    => 1,
        		'order_amount'	=> $order_amount,
        		'total_amount'	=> $total_amount,
        		'coupon_amount'	=> $coupon_amount,
        		'coupon_id'	    => $coupon_id,
        		'doctor_name'   => '极速义诊',
        		'pay_name'		=> $pay_name,
        		'pay_code'		=> $pay_code,
        		'patient_name'  => $patient_name,
        		'patient_sex'   => $patient_sex,
        		'patient_age'   => $patient_age,
        		'order_sn'		=> $order_sn,
        		'problem'       => $problem
        	];
        	$order_id = Db::name('doctor_order')->insertGetId($insert_data);
        	
            $transStatu=1;
            Db::commit();
        } catch (\Exception $e) {
            $msg= $e->getMessage();
            $order_id=0;
            // 回滚事务
            Db::rollback();
        }
        if($transStatu == 1){
        	$this->success('ok', ['order_id'=>$order_id,'order_sn'=>$order_sn]);
        }else{
        	$this->error($msg);
        }
        
    }
    //订单支付
    public function questionPay(){
        $user_id  = $this->getUserId();
        
        $order_id   = request()->param('order_id',0,'intval');
        $coupon_id  = request()->param('coupon_id'); //优惠卷id
        $pay_code   = request()->param('pay_code');
        if(empty($pay_code))	$this->error('请选择支付方式');
        $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';
        
        $where = [
            ['id','=',$order_id],
            ['user_id','=',$user_id]
        ];
        $order = Db::name('doctor_order')->where($where)->find();
        if($order){
            if(empty($order['pay_code'])){
                if($order['pay_status'] == 1)  $this->error('订单已支付');
                //
                $coupon_amount=0;
                $total_amount = $order['total_amount'];
                $order_amount = $order['order_amount'];
                $coupon = Db::name('coupon')->where(['user_id'=>$user_id,'id'=>$coupon_id,'is_use'=>0,'type'=>0])->find();
            	if($coupon){
            	    
            	    if($coupon['end_time'] < time())        $this->error('优惠券已过期');
            	    if($coupon['total_amount'] > $total_amount)    $this->error('未达到金额');
            	    $coupon_amount = $coupon['amount'];
            	    $order_amount  = $total_amount-$coupon['amount'];
            	}else{
            	    $coupon_id = 0;
            	}
            	Db::name('doctor_order')->where('id',$order_id)->update([
            	    'order_amount'  => $order_amount,
            	    'coupon_amount' => $coupon_amount,
            	    'coupon_id'     => $coupon_id,
            	    'pay_name'      => $pay_name,
                    'pay_code'      => $pay_code
            	]);
            	$where = [
            	    ['user_id','=',$user_id],
            	    ['type','=',4],
            	    ['order_sn','=',$order['order_sn']],
            	];
            	Db::name('pay_log')->where($where)->update([
            	    'amount'    => $order_amount,
            	    'pay_code'  => $pay_code
            	]);
            	if($coupon_id){
            	    Db::name('coupon')->where('id',$coupon_id)->update(['order_id'=>$order_id,'is_use'=>1,'use_time'=>time()]);
            	}
        	    $this->success('ok', ['order_id'=>$order['id'],'order_sn'=>$order['order_sn']]);
                
            }else{
                $this->error('订单已提交过，请直接支付');
            }
            
        }else{
            $this->error('订单不存在');
        }
    }

    public function list(){
    	$user_id  = $this->getUserId();
    	$data = Db::name('doctor_order')->field('id,doctor_name,doctor_img')->where('user_id',$user_id)->where('pay_status',1)->order('id','desc') ->paginate(10);
    	$this->success('ok',$data->items());
    }

    public function sendMsg(){
    	$user_id  = $this->getUserId();
    	$order_id = request()->param('id');
    	$msg  = request()->param('msg');
    	if(empty($msg))		$this->error('请填写内容');
    	$order = Db::name('doctor_order')->field('pay_status,problem_id,order_status,pay_status,order_type')->where('id',$order_id)->where('user_id',$user_id)->find();
    	if($order['pay_status'] == 0)  $this->error('订单未付款');
    	if($order['pay_status'] == 1){
            //if($order['order_status'] != 1) $this->error('订单待确认');
    		$service = new SpringService($user_id);
    		    
    		if($order['order_status'] == 0 && $order['order_type'] == 1)    $this->error('待分配医生');
    		
    		if(empty($order['problem_id'])){
    			$res = $service->create_oriented($order_id,$msg);
    		}else{
    			$res = $service->problem_content($order_id,$msg);
    		}
    		if($res['error'] == 0){
    			$this->success('ok');
    		}else{
    			$this->error($res['error_msg']);
    		}
	        
    	}
    }

    public function chat(){
    	$user_id  = $this->getUserId();
    	$order_id = request()->param('id');
    	$list = Db::name('chat_record')->field('id,order_id,user_id,send_user_id,add_time,msg')->where(['user_id'=>$user_id,'order_id'=>$order_id])->order(['add_time'=>'asc','id'=>'asc'])->select()->toarray();
    	foreach($list as $key=>$vo){
    		$list[$key]['is_send']  = $vo['send_user_id'] = $user_id?1:0;
    		$list[$key]['add_time'] = date('Y-m-d H:i:s',$vo['add_time']);
    	}

    	$this->success('ok',$list);

    }

}
