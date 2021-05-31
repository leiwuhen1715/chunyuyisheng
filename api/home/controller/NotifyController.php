<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use think\Db;
use think\Validate;
use think\facade\Log;
use api\user\service\WxPayService;
use api\user\service\AliPayService;
use cmf\controller\RestBaseController;
use api\user\service\SpringService;

class NotifyController extends RestBaseController
{

    public function alipay(){
    	
        $data = request()->post();
        $data['fund_bill_list'] = htmlspecialchars_decode($data['fund_bill_list']);
        $server = new AliPayService;
        $result = $server->notify($data);
        if($result === true){
        	$res_status = $this->pay($data['out_trade_no']);
        	if($res_status == 1){
        	    echo "success";
        	}
        }else{
        	Log::write($data['out_trade_no'].'签名失败','alipay');
        }
        
    }
    
    public function wxpay(){
    	
		$server = new WxPayService;
    	$app    = $server->getApp();
		$param = request()->param();
	
		$response = $app->handlePaidNotify(function ($message, $fail) {
		    // 你的逻辑
		    
		    if($message['result_code'] == 'SUCCESS' && $message['return_code'] == 'SUCCESS'){
		    
	    	    $res_status = $this->pay($message['out_trade_no']);
		    	if($res_status == 1){
		    	    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		    	}else{
		    	    Log::write('支付失败-'.$message['out_trade_no'],'pay');
		    	}
		    
		    	
		    }else{
		    	//Log::write('错误','wxpay');
		    	$fail('错误');	
		    }
		    // 或者错误消息
		    
		});
		
	
    }
    
    
    private function pay($order_sn){
    	$data = DB::name('pay_log')->where('order_sn',$order_sn)->order('id','desc')->find();
        //判断订单金额
        $result_status = 0;
        if($data['is_paid']==0){
        	Db::startTrans(); //开启事务
        	try {
                switch ($data['type']) {
                    case 1:
                        //
                        $result = Db::name('course_enter')->where('id',$data['order_id'])->find();
                        
                        $updata=[
                        	'pay_status'=>1,
                        	'pay_time'  => time()
                        ];
                        DB::name('course_enter')->where("id",$data['order_id'])->update($updata);
                        
                        Db::name('item_train')->where('id',$result['goods_id'])->setInc('sales_sum');
                        // Db::name('user')->where('id',$result['user_id'])->setInc('card_num');
                        
                        $result_status = 1;
                        break;
                    
                    case 2:
                        $order = DB::name('order')->where("order_sn",$order_sn)->order('order_id','desc')->find();
                        //商城付款信息
                        $updata=[
                        	'pay_status'=>1,
                        	'pay_time'  => time()
                        ];
    
    					$result = DB::name('order')->where("order_id",$order['order_id'])->update($updata);
    					logOrder($order['order_id'],'付款','pay','');
    					$goods = Db::name('order_sub')->field('goods_id,goods_num')->where('order_id',$order['order_id'])->select();
    					foreach ($goods as $value){
    					    if($order['prom_type'] == 1){
    					        Db::name('store_seckill')->where('id',$value['goods_id'])->setInc('sales_sum',$value['goods_num']);
                                Db::name('store_seckill')->where('id',$value['goods_id'])->setDec('store_count',$value['goods_num']);
    					    }else{
    					        Db::name('goods')->where('goods_id',$value['goods_id'])->setInc('sales_sum',$value['goods_num']);
                                Db::name('goods')->where('goods_id',$value['goods_id'])->setDec('store_count',$value['goods_num']);
    					    }
    					}
                        $result_status = 1;
                        break;
                    case 3:
                        //
                        $updata=[
                        	'pay_status'=>1,
                        	'pay_time'  => time()
                        ];
                        $result = Db::name('course_order')->where('id',$data['order_id'])->find();
                        DB::name('course_order')->where("id",$data['order_id'])->update($updata);
                        Db::name('item_course')->where('id',$result['goods_id'])->setInc('sales_sum');
                        
                        $result_status = 1;
                        break;
                    case 4:
                        //
                        $order = DB::name('doctor_order')->field('id,user_id,order_type')->where("order_sn",$order_sn)->order('id','desc')->find();
                        $updata=[
                            'pay_status'=>1,
                            'pay_time'  => time()
                        ];
                        DB::name('doctor_order')->where("id",$order['id'])->update($updata);
                        if($order['order_type'] == 1){
                            //发起提问
                            $service = new SpringService($order['user_id']);
                            $res = $service->create_paid_problem($order['id']);
                         
                        }else{
                            DB::name('doctor_order')->where("id",$order['id'])->update(['order_status'=>1]);
                        }
                        $result_status = 1;
                        break;
                    case 5:
                        
                        $order = DB::name('phone_order')->field('id,user_id,order_type')->where('order_sn',$order_sn)->order('id','desc')->find();

                        $updata=[
                            'pay_status'=>1,
                            'pay_time'  => time()
                        ];

                        //$res = DB::name('phone_order')->where("id",$order['id'])->update($updata);
                        $res = Db::name('phone_order')
                            ->where('id',$order['id'])
                            ->data($updata)
                            ->update();

                        if($order['order_type'] == 0){
                            //快捷电话
                            $service = new SpringService($order['user_id']);
                            $res = $service->create_fast_phone_order($order['id']);
                        }else{
                            //创建定向电话
                            $service = new SpringService($order['user_id']);
                            $res = $service->create_oriented_order($order['id']);
                            
                        }
                        $result_status = 1;
                        break;
                    case 6:
                        $oreder = Db::name('phone_order')->field('id,user_id,order_type')->where('order_sn',$order_sn)->order('id','desc')->find();

                        $updata=[
                            'pay_status'=>1,
                            'pay_time'  => time()
                        ];
                        Db::name('phone_order')->where('id',$order['id'])->update($updata);
                        if ($order['order_type'] == 1){  //定向电话
                            //创建定向电话
                            $service = new SpringService($order['user_id']);
                            $res = $service->create_oriented_order($order['id']);
                        }else{

                        }
                        $result_status = 1;
                        break;
                }
                if($result_status == 1){
					Db::name('pay_log')->where('id',$data['id'])->update(['is_paid'=>1,'pay_time'=>time()]);
				}
                Db::commit();
			} catch (\Exception $e) {
			    $Message= $e->getMessage();
			    // 回滚事务
			    Db::rollback();
				Log::write('回调错误--'.$Message,'on_pay');
			}
        	
        }else{
            $result_status = 1;
            Log::write('已经支付过了--'.$order_sn,'on_pay');
        }
        return $result_status;
        
    }
    
    public function test_pay($order_sn){
        $this->pay($order_sn);
    }


}
