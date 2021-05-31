<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use api\user\service\WxPayService;
use api\user\service\AliPayService;
use cmf\controller\RestBaseController;
use think\Db;
use think\Validate;

class PayController extends RestUserBaseController
{

     /**
      * 支付
      */
     public function fiedorder(){

         $order_sn = $this->request->param('order_sn');
         $openid   = $this->request->param('openid');
         //$userId   = $this->getUserId();
         $one = Db::name('pay_log')->where('order_sn',$order_sn)->find();
         if($one){
             if($one['is_paid'] == 1){
                 $this->error('订单已付款！');
             }else{
                 /*switch ($one['type']) {
                     //商城付款信息
                     case 2:
                         $join = [
                             ['__ORDER_SUB__ s','o.order_id = s.order_id','left']
                         ];
                         $order = Db::name('order')->field('s.goods_name,o.order_amount,o.order_sn,o.pay_status,o.pay_code')->alias('o')->join($join)->where('o.order_id',$one['order_id'])->find();
                         break;
                     case 1:
                            //课程报名
                             $order = Db::name('course_enter')->field('goods_name,order_amount,order_sn,pay_code')->where('id',$one['order_id'])->find();
                             break;
                     case 3:
                            //课程购买
                             $order = Db::name('course_order')->field('goods_name,order_amount,order_sn,pay_code')->where('id',$one['order_id'])->find();
                             break;
                     default:
                         // code...
                         break;
                 }*/
                 $data = [
                     'openid'       => $openid,
                     'goods_name'   => $one['goods_name'],
                     'total_fee'    => $one['amount'],
                     'out_trade_no' => $one['order_sn'],
                 ];
                 $result = [];
                 if($one['pay_code'] == 'wxpay'){

					$result['pay_code'] = 'wxpay';
					$server = new WxPayService();
					$res = $server->pay($data);
					$result['body'] = $res;
					
					
                // }elseif($order['pay_code'] == 'alipay'){
                     
                 }elseif($one['pay_code'] == 'alipay'){  
                 	$result['pay_code'] = 'alipay';

                    $server = new AliPayService();
                    $res = $server->pay($data);
                 
                	$result['body'] = $res->body;
                	
                 }
                 $this->success('ok', $result);

             }
         }else{
             $this->error('订单不存在！');
         }

     }

    /**
     * 退款
     */
    public function refund(){
        $msg = $this->request->param('msg');
        $id = $this->request->param('id',0,'intval');
        if(empty($msg)){
            $this->error('请选择退款原因！');
        }
        $userId   = $this->getUserId();
        $OrderService = new OrderService;
        $data = $OrderService->orderInfo($id,$userId);
        if($data['is_tui'] == 1){

            $total_fee = $data['order_amount']*100;
            $params = [
                'total_fee'     => $total_fee,
                'refund_fee'    => $total_fee,
                'out_refund_no' => $data['order_sn'],
                'out_trade_no'  => $data['order_sn']
            ];
            $res = \wxpay\Refund::exec($params);
            if($res['result_code'] == 'SUCCESS' && $res['return_code'] == 'SUCCESS'){
                $update = [
                    'refund_note'  => $msg,
                    'order_status' => 3
                ];
                Db::name('order')->where('order_id',$id)->update($update);
                logOrder($id,'申请退款','refund',$userId);
            }

            $this->success('提交成功！');
        }else{
            $this->error('订单不能退款');
        }
    }
    public function getPayDetail()
    {
        $type = request()->param('type',0,'intval');
        $id   = request()->param('id',0,'intval');
        switch ($type) {
            case 0:
                //课程购买
                $data = Db::name('item_course')->field('id,price,title')->where('id',$id)->where('state',1)->find();
                break;

            case 1:
                //课程报名
                $data = Db::name('item_train')->field('id,price,title')->where('id',$id)->where('state',1)->find();

                break;
        }
        if($data){
            $this->success('ok',$data);
        }else{
            $this->error('不存在');
        }

    }
    /**
     * 课程购买
     */
    public function addCourseOrder(){
        $user_id = $this->getUserId();

        $address_id = request()->param('address_id',0,'intval');
        $invoice_id = request()->param('invoice_id',0,'intval');
        $id = request()->param('id',0,'intval');
        $pay_code = request()->param('pay_code');
        if(empty($pay_code)){
            $this->error('请选择支付方式！');
        }
        $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';
        $coupon_id = request()->param('coupon_id',0,'intval');


		if($invoice_id){
			$invoice = Db::name('UserInvoice')->where(['id'=>$invoice_id,'user_id'=>$user_id])->find();
			if(empty($invoice))$this->error('发票信息不存在！');
            $address = Db::name('UserAddress')->where(["address_id"=>$address_id,'user_id'=>$user_id])->find();
            if(!$address)$this->error('请填写收货地址！');
		}

        $one = Db::name('item_course')->field('id,price,picture,title')->where('id',$id)->where('state',1)->find();
        if($one){

            $order_sn = get_order_sn();
            $order_amount = $one['price'];

            $order = Db::name('course_order')->where(['user_id'=>$user_id,'goods_id'=>$id,'pay_status'=>1])->find();
            if($order)$this->error('已购买该课程，不能重复购买！');
            $discount = 0;
            if($coupon_id){
                $time = time();
                $coupon = Db::name('coupon')->where(['user_id'=>$user_id,'id'=>$coupon_id,'type'=>1,'is_use'=>0,'end_time'=>['>',$time]])->find();
                if(empty($coupon))$this->error('优惠劵不存在！');
                $order_amount = $one['price']*$coupon['discount']/10;
                $discount = $coupon['discount'];
            }
            $data = [
                'user_id'       => $user_id,
                'add_time'      => time(),
                'goods_id'      => $id,
                'order_sn'      => $order_sn,
                'order_amount'  => $order_amount,
                'goods_price'   => $one['price'],
                'pay_code'      => $pay_code,
                'pay_name'      => $pay_name,
                'goods_name'    => $one['title'],
                'goods_img'     => $one['picture'],
                'discount'      => $discount
            ];
            if(!empty($invoice)){
				$data['invoice_type'] = 0; //发票类型
				$data['title_type']   = $invoice['types']; //抬头类型
				if($invoice['types'] == 1){
					$data['invoice_title'] = $invoice['personal_title'];
					$data['card_on']		= $invoice['card_on'];

				}else{
					$data['invoice_title'] = $invoice['company_title'];
					$data['dutynum']       = $invoice['duty_number']; //税号
					$data['bankname']      = $invoice['brank_name']; //开户行
					$data['banknum']       = $invoice['brank_on']; //开户行
					$data['businessaddress']   = $invoice['company_address']; //企业地址
					$data['businesstel']   = $invoice['company_phone']; //企业电话
				}
				$data['consignee'] 	    = $address['consignee']; // 收货人
	            $data['country'] 		= $address['country'];//'省份id',
	            $data['province'] 		= $address['province'];//'详细地址',
	            $data['city'] 			= $address['city'];//'详细地址',
	            $data['district'] 		= $address['district'];//'详细地址',
	            $data['address'] 		= $address['address'];//'详细地址',
	            $data['email'] 		    = $address['email'];//'详细地址',
	            $data['mobile'] 		= $address['mobile'];//'手机',

			}
            $order_id = Db::name('course_order')->insertGetId($data);
            $pay_data = [
                'amount'   => $order_amount,
                'is_paid'  => 0,
                'type'     => 3,
                'order_sn' => $order_sn,
                'goods_name' => $one['title'],
                'order_id' => $order_id
            ];
            Db::name('pay_log')->insert($pay_data);

            if($coupon_id){
                $coupon = Db::name('coupon')->where('id',$coupon_id)->update(['is_use'=>1,'use_time'=>time(),'order_id'=>$order_id]);
            }

            $this->success('ok',$order_sn);
        }else{
            $this->error('您购买的课程不存在！');
        }

    }
    /**
     * 课程报名
     */
    public function addTrainOrder(){
        $user_id = $this->getUserId();
        $address_id = request()->param('address_id',0,'intval');
        $invoice_id = request()->param('invoice_id',0,'intval');
        $id       = request()->param('id',0,'intval');
        $pay_code = request()->param('pay_code');
        $photos = request()->param('photos');
        
        if(empty($pay_code))$this->error('请选择支付方式！');
        if(empty($photos))$this->error('请上传证件！');
        $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';

        if($invoice_id){
			$invoice = Db::name('UserInvoice')->where(['id'=>$invoice_id,'user_id'=>$user_id])->find();
			if(empty($invoice))$this->error('发票信息不存在！');
            $address = Db::name('UserAddress')->where(["address_id"=>$address_id,'user_id'=>$user_id])->find();
            if(!$address)$this->error('请填写收货地址！');
		}
        $one = Db::name('item_train')->field('id,price,picture,title')->where('id',$id)->where('state',1)->find();
        if($one){

            $order_sn = get_order_sn();
            $order_amount = $one['price'];

            $order = Db::name('course_enter')->where(['user_id'=>$user_id,'goods_id'=>$id,'pay_status'=>1])->find();
            if($order)$this->error('已购买该课程，不能重复购买！');
            $discount = 0;

            $data = [
                'user_id'       => $user_id,
                'add_time'      => time(),
                'goods_id'      => $id,
                'order_sn'      => $order_sn,
                'order_amount'  => $order_amount,
                'goods_price'   => $one['price'],
                'photos'		=> $photos,
                'pay_code'      => $pay_code,
                'pay_name'      => $pay_name,
                'goods_name'    => $one['title'],
                'goods_img'     => $one['picture']
            ];
            if(!empty($invoice)){
				$data['invoice_type'] = 0; //发票类型
				$data['title_type']   = $invoice['types']; //抬头类型
				if($invoice['types'] == 1){
					$data['invoice_title'] = $invoice['personal_title'];
					$data['card_on']		= $invoice['card_on'];

				}else{
				    
					$data['invoice_title'] = $invoice['company_title'];
					$data['dutynum']       = $invoice['duty_number']; //税号
					$data['bankname']      = $invoice['brank_name']; //开户行
					$data['banknum']       = $invoice['brank_on']; //开户行
					$data['businessaddress']   = $invoice['company_address']; //企业地址
					$data['businesstel']   = $invoice['company_phone']; //企业电话
				}
				$data['consignee'] 	    = $address['consignee']; // 收货人
	            $data['country'] 		= $address['country'];//'省份id',
	            $data['province'] 		= $address['province'];//'详细地址',
	            $data['city'] 			= $address['city'];//'详细地址',
	            $data['district'] 		= $address['district'];//'详细地址',
	            $data['address'] 		= $address['address'];//'详细地址',
	            $data['email'] 		    = $address['email'];//'详细地址',
	            $data['mobile'] 		= $address['mobile'];//'手机',

			}
            $order_id = Db::name('course_enter')->insertGetId($data);
            $pay_data = [
                'amount'     => $order_amount,
                'is_paid'    => 0,
                'type'       => 1,
                'order_sn'   => $order_sn,
                'order_id'   => $order_id
            ];
            Db::name('pay_log')->insert($pay_data);


            $this->success('ok',$order_sn);
        }else{
            $this->error('您购买的课程不存在！');
        }

    }

}
