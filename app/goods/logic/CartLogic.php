<?php

namespace app\goods\logic;

use think\Db;
use think\Model;
use think\model\Relation;
/**
 * 购物车 逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class CartLogic extends Relation
{		
    /**
     * 加入购物车方法
     * @param type $goods_id  产品id
     * @param type $goods_num   产品数量
     * @param type $goods_spec  选择规格 
     * @param type $user_id 用户id
     */

    function addCart($goods_id,$goods_num,$user_id = 0,$prom_type,$item_path="")
    {       
        
        	$where['goods_id'] = $goods_id;
        
			if($prom_type==1){
				$where['prom_type']=1;
			}

	        $goods = Db::name('Goods')->where($where)->find(); 
	        if(empty($goods)){
	        	return array('status'=>-2,'msg'=>'产品不存在！','result'=>'');
	        }
	        if($item_path!=''){
		        $sku = Db::name('GoodsSku')->field('a.*,b.item')->alias('a')->join('spec_item b','a.item_path=b.id','left')->where('goods_id',$goods_id)->where('item_path',$item_path)->find();
				if(!$sku){
					return array('status'=>-1,'msg'=>'产品SKU不存在','result'=>''); 
				}
	        }
	        // 找出这个      
			$where = ["type"=>0];
	        $user_id = $user_id ? $user_id : 0;
			if($user_id){
				$where['user_id'] = $user_id;
			}
			
			$catr_count = Db::name('Cart')->where($where)->count(); // 查找购物车产品总数量		
	        if($catr_count >= 20){ 
	            return array('status'=>-9,'msg'=>'购物车最多只能放20种产品','result'=>'');            
	        }
			
			
			// 秒杀时间
			if($prom_type==1){
				$time=time();
				if($goods['start_time']>$time){
					return array('status'=>-2,'msg'=>'活动还未开始','result'=>''); 
				}elseif($goods['end_time']<$time){
					return array('status'=>-2,'msg'=>'活动已经结束','result'=>''); 
				}
			}
			if($goods_num > 100){ 
	            return array('status'=>-102,'msg'=>'单品数量不能超过100件','result'=>''); 
			}
			if($item_path!=''){
				$where['goods_id'] = $goods_id;
				$where['spec_path'] = $item_path;
		        $catr_goods = Db::name('Cart')->where($where)->find(); // 查找购物车是否已经存在该产品
				if($catr_goods['goods_num'] > 100){
					return array('status'=>-103,'msg'=>'单品数量不能超过100件','result'=>'');
				}
			}else{
				$where['goods_id'] = $goods_id;
		        $catr_goods = Db::name('Cart')->where($where)->find(); // 查找购物车是否已经存在该产品
				if($catr_goods['goods_num'] > 100){
					return array('status'=>-103,'msg'=>'单品数量不能超过100件','result'=>'');
				}
			}
			/*
			$where['goods_id'] = $goods_id;
	        $catr_goods = Db::name('Cart')->where($where)->find(); // 查找购物车是否已经存在该产品
			if($catr_goods['goods_num'] > 100){
				return array('status'=>-103,'msg'=>'单品数量不能超过100件','result'=>'');
			}
			*/
		
	        $data = array(                    
	                    'user_id'         => $user_id,   // 用户id
	                    'goods_id'        => $goods_id,   // 产品id
						'goods_cat_id'    => $goods['cat_id'],   // 产品分类id
	                    'goods_sn'        => $goods['goods_sn'],   // 产品货号
	                    'goods_name'      => $goods['goods_name'],   // 产品名称
	                    'market_price'    => $goods['market_price'],   // 市场价
	                    'goods_price'     => $goods['shop_price'],  // 购买价
	                    'member_goods_price' => $goods['shop_price'],  // 会员折扣价 默认为 购买价
	                    'goods_num'       => $goods_num, // 购买数量
	                    'type'             => 0,
	                    'spec_path'        => '', // 规格key
	                    'spec_item_name'   => '', // 规格 key_name
	                   // 'sku'        => "{$specGoodsPriceList[$spec_key]['sku']}", // 产品条形码                    
	                    'add_time'        => time(), // 加入购物车时间
	                    'prom_type'       => $prom_type,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
	                   // 'prom_id'         => $goods['prom_id'],   // 活动id                            
	        );                
			if($item_path!=''){
				$arr = explode('-',$item_path);
				$str = '';
				foreach ($arr as $k=>$v){
					$spec = Db::name('spec_item')->find($v);
					if($spec){
						$str .= $spec['item'].',';
					}
				}
				$str = trim($str,',');
				$data['spec_item_name'] = $str;
				$data['goods_price'] = $sku['price'];
				$data['spec_path'] = $item_path;
				
			}
	       // 如果产品购物车已经存在 
	       if($catr_goods) 
	       {     
				//是否存在规格
				
				// 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
	            $res_num = $catr_goods['goods_num'] + $goods_num;
	            if($res_num < 1){
	            	$result = Db::name('Cart')->where("id",$catr_goods['id'])->delete();
	            	return array('status'=>1,'msg'=>'修改购物车');
	            }else{
	            	if(($catr_goods['goods_num'] + $goods_num) > $goods['store_count']){
						return array('status'=>-102,'msg'=>'库存不足！','result'=>''); 
					}else{
						
						$result = Db::name('Cart')->where("id",$catr_goods['id'])->update(["goods_num"=>($catr_goods['goods_num'] + $goods_num)]); // 数量相加        
						return array('status'=>1,'msg'=>'成功加入购物车');
					}
	            }
				
				
				                           			
	            // $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量 
	            // setcookie('cnum',$cart_count,null,'/');
				//cookie('cn',$cart_count);
	            
	       }
	       else
	       {    
	       		if($goods_num <= 0){ 
		            return array('status'=>-2,'msg'=>'加入购物车失败','result'=>''); 
				}else{
					$insert_id = DB::name('Cart')->insert($data);
	             	// $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量
	             	// setcookie('cnum',$cart_count,null,'/');
				 	//cookie('cn',$cart_count);
	             	return array('status'=>1,'msg'=>'成功加入购物车');
				}
	             
	       }     
            // $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量 
            return array('status'=>-5,'msg'=>'加入购物车失败');     
    }
    
    /**
     * 购物车列表 
     * @param type $user   用户
     * @param type $session_id  session_id
     * @param type $selected  是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的产品情况
     * $mode 0  返回数组形式  1 直接返回result
     */
    function cartList($user_id,$type=0)
    {                   
        
        		
        $where = [
        	'type'		  => 0
        ];
        		
        if($user_id)// 如果用户已经登录则按照用户id查询
        {
             $where['user_id'] = $user_id;
             // 给用户计算会员价 登录前后不一样             
        }
        if($type == 1){
        	$where['selected'] = 1;
        }

                                
        $cartList = Db::name('Cart')->where($where)->select()->toArray();  // 获取购物车产品 

        if(empty($cartList)){
        	return ['status'=>-1,'msg'=>'购物车为空！'];
        }
        
        $anum = $total_price =  $cut_fee = 0;

        foreach ($cartList as $k=>$val){
        	$goods_img = Db::name('Goods')->where('goods_id' , $val['goods_id'])->value('goods_img');
        	$cartList[$k]['goods_img'] = cmf_get_image_url($goods_img);
        	$cartList[$k]['goods_fee'] = sprintf('%.2f',$val['goods_num'] * $val['goods_price']);
        	// $cartList[$k]['store_count']  = getGoodNum($val['goods_id'],$val['spec_path']); // 最多可购买的库存数量        	
                $anum += $val['goods_num'];
                
                // 如果要求只计算购物车选中产品的价格 和数量  并且  当前产品没选择 则跳过
                // if($selected == 1 && $val['selected'] == 0)
                //     continue;
			if($val['selected'] == 1){
				$cut_fee += $val['goods_num'] * $val['goods_price']; //应付价格                                   
        		$total_price += $val['goods_num'] * $val['goods_price']; //产品总价
			}
            
        }

        $total_price = array('total_fee' => sprintf('%.2f', $total_price), 'cut_fee' => sprintf('%.2f', $cut_fee),'num'=> $anum,); // 总计        
        // setcookie('cnum',$anum,null,'/');
        // if($mode == 1) return array('cartList' => $cartList, 'total_price' => $total_price);
        return array('status'=>1,'msg'=>'','result'=>array('cartList' =>$cartList, 'total_price' => $total_price));
    }    
	
	/**
     * 直接购买
     * @param type $goods_id  产品id
     * @param type $goods_num   产品数量
     * @param type $goods_spec  选择规格 
     */
	public function buyGoods($goods_id,$goods_num,$goods_spec,$user_id,$prom_type='',$buyGoods=''){
		$where=[
			'goods_id'=>$goods_id
		];
		if($prom_type==1){
			$where['prom_type']=1;
		}
		
		$goods = Db::name('Goods')->where($where)->find(); // 找出这个产品 
		
		if(!$goods){
			return array('status'=>-1,'msg'=>'产品不存在','result'=>''); 
		}
		if($goods_spec!=''){
			$sku = Db::name('GoodsSku')->field('a.*,b.item')->alias('a')->join('spec_item b','a.item_path=b.id','left')->where('goods_id',$goods_id)->where('item_path',$goods_spec)->find();
			if(!$sku){
				return array('status'=>-1,'msg'=>'产品SKU不存在','result'=>''); 
			}
		}
		
		
		// 秒杀时间
		if($prom_type==1){
			$time=time();
			if($goods['start_time']>$time){
				return array('status'=>-2,'msg'=>'活动还未开始','result'=>''); 
			}elseif($goods['end_time']<$time){
				return array('status'=>-2,'msg'=>'活动已经结束','result'=>''); 
			}
		}
        if($goods_spec!=''){
        	if($goods_num <= 0){ 
	            return array('status'=>-2,'msg'=>'购买产品数量不能为0','result'=>''); 
			}
			if($goods_num > 100){ 
	            return array('status'=>-102,'msg'=>'单品数量不能超过100件','result'=>''); 
			}
			if($sku['store_count'] < $goods_num){ 
	            return array('status'=>-102,'msg'=>'库存不足！','result'=>''); 
			}
        }else{
    		if($goods_num <= 0){ 
	            return array('status'=>-2,'msg'=>'购买产品数量不能为0','result'=>''); 
			}
			if($goods_num > 100){ 
	            return array('status'=>-102,'msg'=>'单品数量不能超过100件','result'=>''); 
			}
			if($goods['store_count'] < $goods_num){ 
	            return array('status'=>-102,'msg'=>'库存不足！','result'=>''); 
			}
			$goods['buy_num'] = $goods_num;
		
			//产品超出存库
			if($goods_num > $goods['store_count']){
				$goods_num = $goods['store_count'];
			}
        }
				                                   
        //$total_price += $goods_num * $goods['shop_price']+$goods['premium']+$goods['annual_fee']+$goods['residence_fee'];       
		//$cut_fee += $goods_num * $goods['shop_price']+$goods['premium']+$goods['annual_fee']+$goods['residence_fee']; 
		
		//$total_price += $goods_num * $goods['shop_price']; //产品总价  

		//$cut_fee += $goods_num * $goods['shop_price']; //应付价格
		Db::name('cart')->where(['user_id'=>$user_id,'type'=>1])->delete();
        $data = array(                    
                    'user_id'         => $user_id,   // 用户id
                    'goods_id'        => $goods_id,   // 产品id
                    'goods_sn'        => $goods['goods_sn'],   // 产品货号
                    'goods_name'      => $goods['goods_name'],   // 产品名称
                    'market_price'    => $goods['market_price'],   // 市场价
                    'goods_price'     => $goods['shop_price'],  // 购买价
                    'member_goods_price'=>$goods['shop_price'],
                    'goods_num'       => $goods_num, // 购买数量         
                    'add_time'        => time(), // 加入购物车时间
                    'prom_type'       => $prom_type,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠         
                    'type'			  => 1             
        );
        if($goods_spec!=''){
        	$data['spec_path'] = $goods_spec;
        	$arr = explode('-',$goods_spec);
			$str = '';
			foreach ($arr as $k=>$v){
				$spec = Db::name('spec_item')->find($v);
				if($spec){
					$str .= $spec['item'].',';
				}
			}
			$str = trim($str,',');
			$data['spec_item_name'] = $str;
        	$data['goods_price'] = $sku['price'];
        }
        //if($prom_type==1){
		// 	$data['market_price']=$goods['shop_price'];
		// 	$data['goods_price']=$goods['promote_price'];
		// 	$data['member_goods_price']=$goods['promote_price'];
		// }
		$insert_id = DB::name('Cart')->insert($data);
		return array('status'=>1);
	}

	
    public function getBuyGoods($user_id){
    	
        $goods=Db::name('cart')->where(['user_id'=>$user_id,'type'=>1])->find();
        if(!$goods){
        	return array('status'=>0,'msg'=>'无订单提交！');
        }

        $cut_fee = $goods['goods_num'] * $goods['goods_price']; //应付价格                                   
        $total_price = $goods['goods_num'] * $goods['goods_price']; //产品总价
        $coupon_fee = 0;
        
        $goods_img = Db::name('Goods')->where('goods_id' , $goods['goods_id'])->value('goods_img');
        $goods['goods_img'] = cmf_get_image_url($goods_img);

        $anum=$goods['goods_num'];
        $time = time();
        
        // if(!empty($coupon_id)){
        // 	$coupon = Db::name('coupon')->where(['user_id'=>$user_id,'id'=>$coupon_id,'is_use'=>0,'end_time'=>['>',time()]])->find();
        // 	if($coupon && $coupon['amount']<$cut_fee){
        // 		$cut_fee = $cut_fee - $coupon['amount'];
        // 		$coupon_fee = $coupon['amount'];
        // 	}
        // }

        $total_price = array('total_fee' => sprintf('%.2f', $total_price),'coupon_fee'=>sprintf('%.2f', $coupon_fee),'cut_fee' => sprintf('%.2f', $cut_fee),'num'=> $anum,);
        return array('status'=>1,'msg'=>'','result'=>array('cartList' =>[$goods], 'total_price' => $total_price));
    }
	/**
     * 添加订单
	 * @param type $user_id  用户id     
     * @param type $address_id 地址id
     * @param type $cart_price 应付金额
     * @param type $pay_status 支付方式
     */
	public function addOrder($user_id,$address_id,$cart_price,$type,$to_buy='',$coupon_id,$param=[]){
		// 仿制灌水 1天只能下 50 单 

        $order_count = Db::name('Order')->where("user_id= $user_id and order_sn like '".date('Ymd')."%'")->count(); // 查找购物车产品总数量
        if($order_count >= 50) 
            return array('status'=>-9,'msg'=>'一天只能下50个订单','result'=>'');
		// $score=Db::name('user')->where("id",$user_id)->value('score');
		// if($score<$cart_price['payables']){
		// 	return array('status'=>-9,'msg'=>'您的积分不足！','result'=>NULL);
		// }
		$address = Db::name('UserAddress')->where(["address_id"=>$address_id,'user_id'=>$user_id])->find();
		if(!$address)return array('status'=>-9,'msg'=>'请填写收货地址！','result'=>NULL);
		$project_id = $param['project_id'];
		$invoice_id = $param['invoice_id'];
		$find = Db::name('express')->where('provinceid',$address['province'])->find();
		if(!$find){
			$find = Db::name('express')->where('provinceid',0)->find();
		}
		if($find){
			$fare = (float)sprintf("%.2f",$find['cost']);
		}else{
			$fare = 0;
		}
		$transStatu=0;
        Db::startTrans(); //开启事务
        try {

        	
			$request = request();
			$user_note = $request->param('note');
			$order_amount = $cart_price['payables'];
			$order_amount += $fare;
			$total_amount = $cart_price['payables'];
			$coupon_price = 0;
			if(!empty($coupon_id)){
	        	$coupon = Db::name('coupon')->where(['user_id'=>$user_id,'id'=>$coupon_id,'is_use'=>0,'end_time'=>['>',time()]])->find();
	        	if($coupon && $coupon['amount']<$total_amount){
	        		$order_amount = $order_amount - $coupon['amount'];
	        		$coupon_price = $coupon['amount'];
	        	}else{
	        		$coupon_id = 0;
	        	}
	        }else{
	        	$coupon_id = 0;
	        }
	        
			$data = array(
				'order_sn' 		=> date('YmdHis').rand(1000,9999), // 订单编号
				'user_id' 		=> $user_id, //用户id
				'consignee' 	=> $address['consignee'], // 收货人
	            'country' 		=> $address['country'],//'省份id',
	            'province' 		=> $address['province'],//'详细地址',
	            'city' 			=> $address['city'],//'详细地址',
	            'district' 		=> $address['district'],//'详细地址',
	            'address' 		=> $address['address'],//'详细地址',
	            'mobile' 		=> $address['mobile'],//'手机', 
				'goods_price' 	=> $cart_price['payables'],//'产品总价'
				'order_amount' 	=> $order_amount,//'应付款金额',暂无优惠券运费等金额
				'total_amount' 	=> $total_amount,
				'coupon_price'  => $coupon_price,
				'shipping_price'=> $fare,
				'add_time' 		=> time(), //下单时间	
				'coupon_id'     => $coupon_id,
				'project_id'	=> $project_id,
				'order_status'	=> 1,
				'user_note'     => $user_note,
				'pay_status' 	=> 0,
				'pay_name' 		=> '微信支付'
			);
			
			if(!empty($project_id)){
	        	$project = Db::name('company_project')->where(['user_id'=>$user_id,'id'=>$project_id,'reveiw'=>1])->find();
	        	if($project){
	        		$data['project_name']  = $project['title'];
	        		$data['pay_type']      = $project['pay_status'];
	        		$data['days']          = $project['days'];
	        		$data['shipping_type'] = $project['shipping_name'];
	        	}else{
	        		$data['project_id'] = 0;
	        	}
	        }
	        if(!empty($invoice_id)){
	        	$invoice = Db::name('invoice')->where(['user_id'=>$user_id,'id'=>$invoice_id])->find();
	        	if($invoice){
	        		$data['invoice_id']  = $invoice_id;
	        		$data['invoice_type'] = 0;
	        		$data['invoice_title']  = $invoice['titlename'];
	        		$data['title_type']  = $invoice['title_type'];
	        		$data['dutynum']  = $invoice['dutynum'];
	        		$data['bankname']  = $invoice['bankname'];
	        		$data['banknum']  = $invoice['banknum'];
	        		$data['businessaddress']  = $invoice['businessaddress'];
	        		$data['businesstel']      = $invoice['businesstel'];
	        	}else{
	        		$data['invoice_id'] = 0;
	        	}
	        }
			$order_id = Db::name("Order")->insertGetId($data);
			if(!$order_id){
				$transStatu=-8;
				throw new \Exception($res['err']);
			}
			if(!empty($coupon_id)){
				Db::name('coupon')->where('id',$coupon_id)->update(['is_use'=>1,'use_time'=>time(),'order_id'=>$order_id]);
			}
			
			// 记录订单操作日志
	        //logOrder($order_id,'您提交了订单，请等待系统确认','提交订单',$user_id);
			
			$order = Db::name('Order')->where("order_id = $order_id")->find(); 
			
			if($to_buy && $to_buy == 'buy'){
				//直接购买加入订单
				$s_goods = Db::name('Cart')->where(['user_id'=>$user_id,'type'=>1])->find();
				$data2['order_id']    = $order_id; // 订单id
				$data2['goods_id']    = $s_goods['goods_id']; // 产品id
				$data2['goods_name']  = $s_goods['goods_name']; // 产品名称
				$data2['goods_sn']    = $s_goods['goods_sn']; // 产品货号(暂无)
				$data2['goods_num']   = $s_goods['goods_num']; // 购买数量
				$data2['goods_sn']    = $s_goods['goods_sn']; // 产品货号(暂无)
				$data2['spec_item_name']   = $s_goods['spec_item_name']; // 购买数量
				$data2['spec_path'] = $s_goods['spec_path']; // 产品价格
				$data2['goods_price'] = $s_goods['goods_price'];
				$data2['member_goods_price'] = $s_goods['goods_price']; // 会员折扣价 暂无
				$order_goods_id = Db::name("OrderSub")->insertGetId($data2);

				Db::name('Cart')->where(['user_id'=>$user_id,'type'=>1])->delete();

				logOrder($order_id,'下单成功','add',$user_id);
				
					
			}else{
				// throw new \Exception('购物车！');
				//购物车加入订单
				$where = ['user_id'=>$user_id,'selected'=>1];
				$cartList = Db::name('Cart')->where($where)->select();
				foreach($cartList as $key => $v)
				{
					$goods = Db::name('goods')->where("goods_id = {$v['goods_id']} ")->find();
					$data2['order_id'] = $order_id; // 订单id
					$data2['goods_id'] = $v['goods_id']; // 产品id
					$data2['goods_name'] = $v['goods_name']; // 产品名称
					//$data2['goods_sn'] = $v['goods_sn']; // 产品货号
					$data2['goods_num'] = $v['goods_num']; // 购买数量
					//$data2['market_price'] = $v['market_price']; // 市场价
					$data2['goods_price'] = $v['goods_price']; // 产品价格
					$data2['spec_path'] = $v['spec_path']; // 产品规格
					$data2['spec_item_name'] = $v['spec_item_name']; // 产品规格名称
					$data2['member_goods_price'] = $v['member_goods_price']; // 会员折扣价
					$order_goods_id = Db::name("OrderSub")->insertGetId($data2); 
				}
				logOrder($order_id,'下单成功','add',$user_id);
				
				//删除购物车已提交订单产品
				Db::name('Cart')->where($where)->delete();
			}
			$transStatu=1;
			$msg='提交订单成功';
        	Db::commit();
		} catch (\Exception $e) {
            $msg= $e->getMessage();
            $order_id=0;
            // 回滚事务
            Db::rollback();
        }
		
		
		
        return array('status'=>$transStatu,'msg'=>$msg,'result'=>$order_id); // 返回新增的订单id 
	}
	 
	
    /**
     * 查看购物车的产品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function cart_count($user_id,$mode = 0){
        $count = M('Cart')->where("user_id = $user_id and selected = 1")->count();
        if($mode == 1) return  $count;
        
        return array('status'=>1,'msg'=>'','result'=>$count);         
    }
    public function buy_count($user_id,$mode = 0,$type = 0){
    	$where=[
    		'user_id'=>$user_id
    	];
    	if($type == 0){
    		$where['type'] = 0;
    	}else{
    		$where['type'] = 1;
    	}
        $count = Db::name('Cart')->where($where)->count();
        if($mode == 1) return  $count;
        
        return array('status'=>1,'msg'=>'','result'=>$count);         
    }
    
   
}