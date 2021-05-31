<?php

namespace app\goods\controller;

use app\goods\model\OrderModel;
use cmf\controller\AdminBaseController;
use app\goods\logic\OrderLogic;

use think\db;


class AdminDeliveryController extends AdminBaseController {

	protected function initialize()
    {

        parent::initialize();
        

		$this->order_model = Db::name('delivery_order');
		$this->OrderLogic = new OrderLogic;
		$order_status=config('goods.ORDER_STATUS');
		$pay_status=config('goods.PAY_STATUS');
		$shipping_status=config('goods.SHIPPING_STATUS');

        // 订单 支付 发货状态*/
        $this->assign('order_status'   ,$order_status);
        $this->assign('pay_status'     ,$pay_status);
        $this->assign('shipping_status',$shipping_status);
    }
    
	
	// 订单列表
    public function index(){
	
		return $this->fetch();
	}
	//ajax获取订单列表
	public function ajaxorderlist(){
		$where = [];
      	$request = input('request.');
      	
		$limit = request()->param('limit',10,'intval');
		$startTime = empty($request['start_time']) ? 0 : strtotime($request['start_time']);
        $endTime   = empty($request['end_time']) ? 0 : strtotime($request['end_time']);
        $whereor = '';
		if(!empty($startTime) && !empty($endTime)){
            $whereor="add_time >= $startTime and add_time < $endTime";
        }elseif(!empty($endTime)){
            $where[]=['add_time','<=',$endTime];
        }elseif(!empty($startTime)){
            $where[]=['add_time','>=',$startTime];
        }

        $keyword_sn	  =	$this->request->param('keyword_sn');
		$keyword_name =	$this->request->param('keyword_name');
		
        if($keyword_sn)   $where[]=array('order_sn','like',"%$keyword_sn%");	//搜索订单编号		 	 
        
		if($keyword_name)  $where[]=array('consignee','like',"%$keyword_name%");	//搜索收货人		 	 
        
        
        $order_status =	$this->request->param('order_status');
	
		if($order_status)   $where[] = ['status','=',$order_status];
		
        
		
		$order_str = "delivery_id desc";
		$count = Db::name('delivery_order')->where($where)->count();	

		$order_list = $this->order_model->where($where)->where($whereor)->order($order_str)->paginate($limit);
		$list = $order_list->items();
		foreach($list as $key=>$vo){
		    $list[$key]['add_time'] = date('Y-m-d H:s',$vo['add_time']);
		}
		$result = ['code'=>0,'count'=>$count,'data'=>$list];
		die(json_encode($result));
	}
	
	//订单详细
	public function detail(){
        $id = request()->param('id',0,'intval');
      
        $delivery_order = $this->order_model->where('delivery_id',$id)->find();
        $order_id = $delivery_order['order_id'];
 
		$order = $this->OrderLogic->orderInfo($order_id); //获取订单详细 goods_order

		$goods_order = $this->OrderLogic->getGoodsOrder($order_id); //获取订单产品信息 order_sub
		$order_btn = $this->OrderLogic->getOrderBtn($order); //获取按钮
		$action_log = DB::name('OrderLog')->where(['order_id'=>$order_id])->order('log_time desc')->select();
		
        $this->assign('delivery_order',$delivery_order);
		$this->assign('order',$order);
		$this->assign('goods_order',$goods_order);
		$this->assign('order_btn',$order_btn);
		$this->assign('action_log',$action_log);
		return $this->fetch();
	}
	
	
	
	//执行操作按钮
	public function orderOperate($order_id){
		$request=request();
		$type=$request->param('type');
		if(!empty($type)){
			$updata=[];
			$order=DB::name('Order')->where("order_id",$order_id)->find();
			if($order['order_status']=='4'){
				$this->error('订单不能操作！');
			}
			if($order['order_status']=='2'){
				$this->error('订单已完成不能操作！');
			}
			$note=trim($request->param('note'));
			switch($type){
				case 'confirm':
					$updata['order_status'] = 1;
					$note = empty($note) ? '订单确认' : $note;
					$goods_arr= Db::name('order_sub')->field('goods_id')->where('order_id',$order_id)->select();
					foreach ($goods_arr as $key => $value) {
						Db::name('goods')->where('goods_id',$value['goods_id'])->setInc('sales_sum');
						$store_count = Db::name('goods')->where('goods_id',$value['goods_id'])->value('store_count');
						if($store_count > 0){
							$res = Db::name('goods')->where('goods_id',$value['goods_id'])->setDec('store_count');
						}
						
					}
					break;
				case 'cancel':
					$updata['order_status'] = 0;
					$note = empty($note) ? '取消确认' : $note;
					break;
				case 'pay':
					$updata['pay_status'] = 1;
					$updata['pay_time'] = time();
					$note = empty($note) ? '付款' : $note;
					
					
					break;
				case 'nopay':
					if(empty($note)){
						$this->error('请填写操作备注');
					}
					$updata['pay_time'] = null;
					$updata['pay_status'] = 0;
					$note = empty($note) ? '取消付款' : $note;
					break;
				case 'invalid':
					if(empty($note)){
						$this->error('请填写操作备注');
					}
					$updata['order_status'] = 4;
					break;
				case 'receive':
					$updata['shipping_status'] = 2;
					$note = empty($note) ? '确认收货' : $note;
					break;
				case 'complete':
					$updata['order_status'] = 2;
					$note = empty($note) ? '订单完成' : $note;
					break;
				case 'return':
					$updata['order_status'] = 3;
					$note = empty($note) ? '退款' : $note;

					// $goods_id= Db::name('order_sub')->field('goods_id')->where('order_id',$order_id)->find();
					// Db::name('goods')->where('goods_id',$goods_id['goods_id'])->setDec('sales_sum');

					// log_account_change($order['user_id'],$note,$order['order_amount'],0);
					break;
                case 'refund':
					$updata['order_status'] = 3;
                	$updata['refun_status'] = 1;
					$note = empty($note) ? '操作退款' : $note;
					Db::name('delivery_order')->where('order_id',$order_id)->update(['status'=>1]);
					// $goods_id= Db::name('order_sub')->field('goods_id')->where('order_id',$order_id)->find();
					// Db::name('goods')->where('goods_id',$goods_id['goods_id'])->setDec('sales_sum');

					// log_account_change($order['user_id'],$note,$order['order_amount'],0);
					break;
				default:
					return true;
			}			
			$result = DB::name('Order')->where("order_id",$order_id)->update($updata);
			$admin_id = cmf_get_current_admin_id();
			logOrder($order_id,$note,$type,$admin_id);
			//$log = $this->OrderLogic->orderLog($order_id,$type,$note);
			
			if($result){
				$this->success('操作成功',url('AdminOrder/detail',array("order_id"=>$order_id)));
			}else{
				$this->error('操作失败');
			}
		}
	}
	
}