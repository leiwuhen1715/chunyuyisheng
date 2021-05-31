<?php

namespace app\goods\controller;
use app\goods\logic\CartLogic;
use cmf\controller\HomeBaseController;
use think\Db;

class CartController extends HomeBaseController {
	
	public $user_id = 0;
	
	public function _initialize() {       
        parent::_initialize();
		$this->cartLogic = new CartLogic();
		$this->user_id=cmf_get_current_user_id();
		if($this->user_id){
			M('Cart')->where(array("session_id"=>session_id()))->update(array("user_id"=>$this->user_id));
		}else{
			$this->error('请先登录！',url("user/login/index"));
		}
		
	}
	
	public function cart(){           
        return $this->fetch();
    }
	
	public function ajaxAddCart()
    {
        $goods_id = I("goods_id"); // 图片id
        $goods_num = 1;// 图片数量
        $goods_spec = I("goods_spec"); // 图片规格
			
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,session_id(),$this->user_id); // 将图片加入购物车                     
        exit(json_encode($result));       
    }
		
	
	 /*
     * ajax 请求获取购物车列表
     */
    public function ajaxCartList()
    {   
    	$request=request();
        $post_goods_num = $_REQUEST["goods_num"]; // goods_num 购物车图片数量
        $post_cart_select =$_REQUEST["cart_select"]; // 购物车选中状态   
                                         
		if(empty($this->user_id) || $this->user_id == 0){
			$where['session_id'] = session_id(); // 默认按照 session_id 查询
		}else{
			$where['user_id'] = $this->user_id; // 如果这个用户已经登陆了则按照用户id查询
		}
          
        $cartList = M('Cart')->field("id,goods_num,selected,prom_type,prom_id")->where($where)->select()->ToArray(); 
		
		
		//echo '<br>';
		//print_r(count($cartList));
		$this->assign('check_num',count($cartList));//是否全选
	   	   
        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {   
                //$data['goods_num'] = $val < 1 ? 1 : $val;
                $data['goods_num'] = 1;              
                
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ; //全选 				
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])) 
                M('Cart')->where(array('id'=> $key))->update($data);
            }            
        }
        
        
                
        $result = $this->cartLogic->cartList($this->user_id, session_id(),1,1); // 选中的图片        
        if(empty($result['total_price'])){
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
        }
		
        $this->assign('cartList', $result['cartList']); // 购物车的图片                
        $this->assign('total_price', $result['total_price']); // 图片总计
        $this->assign('cut_fee', $result['cut_fee']); // 应付总计
        return $this->fetch('ajax_cart_list');
    }
	
	//ajax 请求购物车列表
    public function header_cart_list()
    {
    	$cart_result = $this->cartLogic->cartList($this->user_id, session_id(),0,1);
    	if(empty($cart_result['total_price']))
    		$cart_result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
    	
		//print_r($cart_result['total_price']);die;
    	$this->assign('cartList', $cart_result['cartList']); // 购物车的图片
    	$this->assign('cart_total_price', $cart_result['total_price']); // 总计
		
        $template = I('template','header_cart_list'); 
		
        return $this->fetch($template);		 
    }
	
	//结算购物车
	public function cart_buy(){
		//ajax请求是否登陆
		$request=request();
		//直接购买
		if($request->isPost()){
			$goods_id = I("goods_id"); // 图片id
			$goods_num = I("goods_num");// 图片数量
			$goods_spec = I("goods_spec"); // 图片规格
			
			$result = $this->cartLogic->buyGoods($goods_id,$goods_num,$goods_spec); // 选中的图片			
			exit(json_encode($result));
		}
		
		$top_buy = I('get.top_buy');
		if($top_buy && $top_buy == 'buy'){
			$goods = session('sgoods');	

			$this->assign('buyGoods', $goods);
			$this->assign('total_price', session('stotal_price'));
		}else{
			$result = $this->cartLogic->cartList($this->user_id, session_id(),1,1); // 选中的图片
			$this->assign('cartList', $result['cartList']);
			$this->assign('total_price', $result['total_price']);
		}

		$this->assign('top_buy', $top_buy);
		return $this->fetch();
	}
	
	//结算页面获取图片价格&提交订单
	public function cart_buy_order(){
		
		$address_id = I('address_id');
		//暂无没有优惠券和运费
		
		$to_buy =  I('to_buy');
		
		//计算价格
		if(!empty($to_buy)){
			//直接购买
			$s_goods = session('sgoods');
			if(empty($s_goods))
				exit(json_encode(array('status'=>-4,'msg'=>'系统繁忙','result'=>null))); // 返回结果状态
			$total_price = session('stotal_price');
			$reslute['payables'] = $total_price['cut_fee'];
		}else{
			//结算购物车
			if($this->cartLogic->cart_count($this->user_id,1) == 0 ) 
			exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中图片','result'=>null))); // 返回结果状态
			
			$order_goods = M('cart')->where("user_id = {$this->user_id} and selected = 1")->select();
			foreach($order_goods as $v){
				$reslute['payables'] += $v['goods_num'] * $v['goods_price'];
				$reslute['payables'] = sprintf('%.2f',$reslute['payables']);
			}
		}
		//提交订单操作
		if($_REQUEST['act'] == 'submit_order')
        {  
			$pay_status = I('pay_status'); //支付方式
			$to_buy = I('to_buy'); //支付方式
		
			$result = $this->cartLogic->addOrder($this->user_id,$address_id,$reslute,$pay_status,$to_buy);
            exit(json_encode($result));      
        }
		
		
		$return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$reslute); // 返回结果状态
        exit(json_encode($return_arr));
	}
	
	
	//支付页面
	public function topay(){
		if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态

		$request=request();
		if($request->isPost()){
	            //页面上通过表单选择在线支付类型，支付宝为alipay 财付通为tenpay
	            

	            $order_id = I('post.order_id');
				$order = M('Order')->where(array("order_id"=>$order_id,"is_del"=>0,'user_id'=>$this->user_id))->find();		
				if(empty($order)){
					$this->error('订单无效或不存在');
				}
				if($order['order_status'] == 4 || $order['order_status'] == 2 || $order['order_status'] == 3){
					$this->error('订单无效或不存在');
				}
				if($order['pay_status']==1){
					$this->error('订单已支付');
				}
				$goods_name=M('OrderSub')->where("order_id=$order_id")->value('goods_name');
				$id=log_account_change($this->user_id, '购买：'.$goods_name,-$order['order_amount']);
				if($id){
					$array=array(
						'pay_status'=>1,
						'order_status'=>1,
						'pay_time'=>time()
					);
					M("Order")->where("order_id = $order_id")->update($array);
					$this->success('支付成功',U("User/profile/order_detail/order_id/$order_id"));
					logOrder($order_id,'订单确认，支付成功','订单确认',$this->user_id);
				}else{
					$this->error('支付失败！！');
				}
				
	    } else {
	            //在此之前goods1的业务订单已经生成，状态为等待支付
	            
	    }
	    
		$order_id = I('order_id');
		$order = M('Order')->where(array("order_id"=>$order_id,"is_del"=>0,'user_id'=>$this->user_id))->find();		
		if(empty($order)){
			$this->error('订单无效或不存在');
		}
		if($order['order_status'] == 4 || $order['order_status'] == 2 || $order['order_status'] == 3){
			$this->error('订单无效或不存在');
		}
		if($order['pay_status']==1){
			$this->success('支付成功',U("User/profile/order_detail/order_id/$order_id"));
		}	
		$this->assign('integral',$integral);
		$this->assign('order',$order);
		return $this->fetch();		
	} 
	
	//删除顶部购物车图片
	public function ajaxDelTopCart(){
		$goods_id = I('post.goods_id');
		$spec_path = I('post.spec_path');
		if(!empty($goods_id)){
			//$where['session_id'] =  session_id();
			$where['goods_id'] = $goods_id;
			$where['user_id'] =$this->user_id;
			//$where['spec_path'] = $spec_path;
			$result = Db::name('Cart')->where($where)->delete();
			if($result !== false){
				$res = array('status'=>1,'msg'=>'删除成功','result'=>'');
			}else{
				$res = array('status'=>-22,'msg'=>'删除失败','result'=>'');
			}
		}
		 exit(json_encode($res));
	}
	
	//删除购物车图片
	public function ajaxDelCart(){
		$ids = I("ids"); // 图片 ids        
        $result = M("Cart")->where(" id in ($ids)")->delete(); // 删除id购物车
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态       
        exit(json_encode($return_arr));;
	}
}
