<?php

// +----------------------------------------------------------------------
namespace api\doctor\controller;

use think\Db;
use api\user\service\SpringService;
use cmf\controller\RestBaseController;

class PhoneOrderController extends RestBaseController
{
    

    //创建快捷电话
    public function fastCreate(){
    	$user_id  = 1;
        $problem  = request()->param('problem');   //患者问题
        $phone    = request()->param('phone');   //手机号
        $clinic_no= request()->param('clinic_no');   //科室号
      
    	if(empty($problem))         $this->error('请填写问题');
    	if(empty($phone))           $this->error('请填写联系电话');
    	if(empty($clinic_no))       $this->error('请选择科室');
    	
    	
        $pay_code  = request()->param('pay_code');
        
        if(empty($pay_code))	$this->error('请选择支付方式');
        $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';
        
        $order_sn = get_order_sn();
    	$coupon_amount = 0;
    	$total_amount = $order_amount = 10;
    	
    	$transStatu=0;
        Db::startTrans(); //开启事务
        try {
        	$pay_data = [
                //'amount'   => $order_amount,
                'amount'   => 0.03,
                'is_paid'  => 0,
                'type'     => 5,
                'goods_name' => '电话极速义诊',
                'order_sn' => $order_sn,
                'pay_code' => $pay_code,
                'add_time' => time(),
                'user_id'  => $user_id
            ];
            $pay_id = Db::name('pay_log')->insertGetId($pay_data);
        	$insert_data = [
        		'user_id'  		=> $user_id,
        		'add_time' 		=> time(),
        		'order_type'    => 0,
        		'order_amount'	=> $order_amount,
        		'total_amount'	=> $total_amount,
        		'pay_name'		=> $pay_name,
        		'pay_code'		=> $pay_code,
        		'phone'         => $phone,
        		'order_sn'		=> $order_sn,
        		'problem'       => $problem,
        		'clinic_no'     => $clinic_no
        	];
        	$order_id = Db::name('phone_order')->insertGetId($insert_data);
        	
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
    
    //创建定向电话
    public function createOrientedOrder()
    {
        $user_id      = $this->getUserId();
        $problem      = request()->param('problem');   //电话补充描述内容
        $phone        = request()->param('phone');   //手机号
        $doctor_id    = $this->request->param('doctor_id'); //医生id
        $minutes      = $this->request->param('minutes');  //共有10，15，20，30四个时间长度，具体取决于医生是否配置了相应的时间长度
        //$price        = $this->request->param('price');  //订单价格
        $inquiry_time = $this->request->param('inquiry_time');  //预约时间
        
        //患者信息
        $patient_name = $this->request->param('patient_name');  //患者姓名
        $patient_sex  = $this->request->param('patient_sex');  //患者性别
        $patient_age  = $this->request->param('patient_age');  //患者年龄
        $patient_photo = $this->request->param('patient_photo'); //患者图片

        $service = new SpringService($user_id);

        if (empty($problem)) $this->error('请填写问题');
        if (empty($phone)) $this->error('请填写联系电话');
        if (empty($doctor_id)) $this->error('请选择医生');
        if (empty($minutes)) $this->error('请选择服务时间');
        if (empty($inquiry_time)) $this->error('请选择预约时间');
        
        //if (empty($patient_name)) $this->error('请输入姓名');
        if (empty($patient_sex)) $this->error('请输入性别');
        if (empty($patient_age)) $this->error('请输入年龄');
        
        //根据时间取出定向问诊医生服务价格
        $service = new SpringService($user_id);
        $res = $service->get_doctor_phone_info($doctor_id);
        $arrar = $res['price_info'];
        foreach ($arrar  as $k=>$v){
             if ($k == $minutes){
                 $price = $v;
             }
        }
        
        if (empty($price)) $this->error('请选择服务时间');
        $order_sn      = get_order_sn();   //订单id
        $coupon_amount = 0; //优惠卷金额
        $total_amount  = $order_amount = $price;
        $transStatu = 0;
        Db::startTrans(); //开启事务
        try {
            $pay_data = [
                'user_id' => $user_id,  //用户id
                //'amount' => $order_amount, //订单数量
                'amount' => 0.03, //订单数量
                'is_paid' => 0,  //
                'type' => 5,  // 6:定向电话
                'order_sn' => $order_sn, //订单id
                'goods_name' => '定向电话问诊', //商品名称
                'pay_code' => '',  //支付代码
                'add_time' => time(), //添加时间
            ];
            $pay_id   = Db::name('pay_log')->insertGetId($pay_data);

            $insert_data = [
                'user_id' => $user_id,
                'pay_status' => 0,
                'order_type' => 1,   //1；定向电话
                'add_time' => time(),
                
                
                // 修改价格
                'order_amount' => $order_amount,   //订单金额
                'total_amount' => $total_amount,   //总金额

                //'pay_name'		=> $pay_name,
                //'pay_code'		=> $pay_code,
                'phone' => $phone,   //手机号
                'order_sn' => $order_sn, //订单号
                'problem' => $problem,  //电话补充内容
                //'clinic_no'     => $clinic_no, //
                'doctor_id' => $doctor_id, //医生id
                'minutes' => $minutes,   //拨打时长
                'inquiry_time' => $inquiry_time,  //预约时间
                
                'patient_name' => $patient_name,
                'patient_sex'  => $patient_sex,
                'patient_age'  => $patient_age,
                'patient_photo'=> $patient_photo,
            ];
            $order_id    = Db::name('phone_order')->insertGetId($insert_data);
            $transStatu  = 1;
            Db::commit();
        } catch (\Exception $e) {
            $msg      = $e->getMessage();
            $order_id = 0;
            // 回滚事务
            Db::rollback();
        }

        if ($transStatu == 1) {
            $this->success('ok', ['order_id' => $order_id, 'order_sn' => $order_sn]);
        } else {
            $this->error($msg);
        }

    }

    
    
    //定向电话订单支付
    public function orientedPhonePay()
    {
        $user_id = $this->getUserId();  //用户id

        $order_id  = request()->param('order_id', 0, 'intval'); //订单id
        $pay_code  = request()->param('pay_code'); //支付类型

        if (empty($pay_code)) $this->error('请选择支付方式');
        $pay_name = $pay_code == 'wxpay' ? '微信支付' : '支付宝支付';

        $where = [
            ['id', '=', $order_id],   //订单id
            ['user_id', '=', $user_id]  //用户id
        ];

        $order = Db::name('phone_order')->where($where)->find();
        if ($order) {
            if (empty($order['pay_code'])) {
                if ($order['pay_status'] == 1) $this->error('订单已支付');

                Db::name('phone_order')->where('id', $order_id)->update([
                    'pay_name' => $pay_name, //支付名称
                    'pay_code' => $pay_code  //支付demo
                ]);

                $where = [
                    ['user_id', '=', $user_id],
                    ['type', '=', 5],
                    ['order_sn', '=', $order['order_sn']],
                ];
                Db::name('pay_log')->where($where)->update([
                    'pay_code' => $pay_code
                ]);

                $this->success('ok', ['order_id' => $order['id'], 'order_sn' => $order['order_sn']]);
            } else {
                $this->error('订单已提交过，请直接支付');
            }
        } else {
            $this->error('订单不存在');
        }

    }
    
     //获取定向电话订单信息
    public function getOrientedPhoneOrder()
    {
        $order_id = $this->request->param('order_id');
        $user_id  = $this->getUserId();

        $field = 'id,order_sn,doctor_id,doctor_name,add_time,order_amount,total_amount';
        $where = ['id' => $order_id,'user_id'=> $user_id];

        $res = Db::name('phone_order')->field($field)->where($where)->find();

        if ($res){
            $this->success('ok',$res);
        }else{
            $this->error('订单不存在');
        }
    }
    


}
