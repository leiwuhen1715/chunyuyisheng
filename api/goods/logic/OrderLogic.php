<?php

namespace api\goods\logic;

use think\Db;
use think\Model;
use think\model\Relation;
class OrderLogic extends Relation
{
	//获取基本信息
	public function orderInfo($order_id){
		$join = [
            ['__USER__ b', 'a.user_id = b.id','left']
        ];
		$order = DB::name('Order')->field('a.*,b.user_nickname as user_login')->alias("a")->join($join)->where("order_id = {$order_id}")->find();
		return $order;
	}

	//获取订单产品信息
	public function getGoodsOrder($order_id){

		$goods_order = DB::name('OrderSub')->field(["a.*","(a.goods_num * a.goods_price)"=>"goods_total"])
		->alias('a')
		->where("order_id",$order_id)
		->order('rec_id', 'desc')
		->select();
		return $goods_order;
	}

	//获取当前可操作按钮
	public function getOrderBtn($order){
		$os = $order['order_status']; //订单状态 1确定 2订单完成 3退货 4无效
		$sh = $order['shipping_status']; //发货状态 1已发货 2已收货
		$pay = $order['pay_status']; //支付状态 1已支付
		$pay_type		= $order['pay_type']; //支付状态 2 月结
		$shipping_type  = $order['shipping_type']; //支付状态 2 月结
		$btn=[];

		if($pay_type == 2){
			if($os == 0 && $sh == 0 && $pay == 0){
				$btn['confirm'] = '确定';
				//$btn['pay'] = '付款';
				$btn['return'] = '取消';
			// }elseif($os == 1 && $sh == 0 && $pay == 1){
			}elseif($os == 1 && $sh == 0){
				if($shipping_type == 2){
					$btn['receive'] = '已收货';
				}else{
					$btn['ship'] = '发货';
				}
				//$btn['cancel'] = '取消';
				//$btn['nopay'] = '未付款';
				//$btn['invalid'] = '无效';
			}elseif($os == 1 && $sh == 1){
				$btn['receive'] = '已收货';
			}elseif($os == 0 && $sh == 0 && $pay == 1){
				$btn['confirm'] = '确定';
				//$btn['nopay'] = '未付款';
				$btn['return'] = '取消';
			}elseif($os == 1 && $sh == 2 && $pay == 0){
				//$btn['ship'] = '发货';
				$btn['pay'] = '付款';
				// $btn['cancel'] = '取消';
				// $btn['invalid'] = '无效';
			}elseif($os == 0 && $sh == 0 && $pay == 3){
				$btn['confirm'] = '确定';
				$btn['invalid'] = '无效';
			}elseif($os == 1 && $sh == 0 && $pay == 3){
				// $btn['ship'] = '发货';
				$btn['cancel'] = '取消';
				$btn['invalid'] = '无效';
			}elseif($os == 1 && $sh == 2){
				//$btn['return'] = '退货';
			}

		}else{
			if($os == 0 && $sh == 0 && $pay == 0){
				$btn['confirm'] = '确定';
				//$btn['pay'] = '付款';
				//$btn['return'] = '取消';
			}elseif($os == 1 && $sh == 0 && $pay == 0){
				$btn['pay'] = '付款';
			}elseif($os == 1 && $sh == 0 && $pay == 1){
				if($shipping_type == 2){
					$btn['receive'] = '已收货';
				}else{
					$btn['ship'] = '发货';
					$btn['nopay'] = '未付款';
				}

			}elseif($os == 1 && $sh == 1){
				$btn['receive'] = '已收货';
			}elseif($os == 0 && $sh == 0 && $pay == 1){
				$btn['confirm'] = '确定';
				$btn['nopay'] = '未付款';
				$btn['return'] = '取消';
			}elseif($os == 0 && $sh == 0 && $pay == 3){
				$btn['confirm'] = '确定';
				$btn['invalid'] = '无效';
			}elseif($os == 1 && $sh == 0 && $pay == 3){
				// $btn['ship'] = '发货';
				$btn['cancel'] = '取消';
				$btn['invalid'] = '无效';
			}elseif($os == 1 && $sh == 2){
				//$btn['return'] = '退货';
			}
		}


		if($os == 4){
			$btn['void'] = '订单作废';
		}

		return $btn;
	}

    /**
     * 获取地区名字
     * @param int $p
     * @param int $c
     * @param int $d
     * @return string
     */
    public function getAddressName($p=0,$c=0,$d=0){
        $p = DB::name('plugin_modules_citys')->where(array('id'=>$p))->field('name,areaname')->find();
        $c = DB::name('plugin_modules_citys')->where(array('id'=>$c))->field('name,areaname')->find();
        $d = DB::name('plugin_modules_citys')->where(array('id'=>$d))->field('name,areaname')->find();
        return $p['name'].$p['areaname'].''.$c['name'].$c['areaname'].''.$d['name'].$d['areaname'].'';
    }
    public function getcode($id){
    	return DB::name('plugin_modules_citys')->where('id',$id)->value('code');

    }
}
