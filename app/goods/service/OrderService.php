<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\goods\service;

use api\goods\model\GoodsModel as Goods;
use app\goods\model\OrderSubModel;
use app\goods\model\OrderModel;
use app\goods\logic\OrderLogic;
use think\db\Query;
use think\Db;

class OrderService
{

    /**
     * 订单查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getList($filter, $userId = false)
    {

        $OrderModel = new OrderModel();
        

        $field = 'o.*';
        $where=[
            'o.order_status' => 1
        ];
        if($userId){
            $where['o.user_id']=$userId;
        }
        $whereor='';
        if($filter['type'] == 1){
        	
            $where['o.pay_status']      = 0;
            //$where['o.shipping_status'] = 2;
            $whereor = "((o.shipping_status = 2 and o.pay_type = 2) or (o.pay_type = 1))";
        }elseif($filter['type'] == 2){
            $where['o.shipping_status'] = 0;
            $where['o.shipping_type']   = 1;
            
            $whereor = "((o.pay_type = 2 and o.pay_status = 0) or (o.pay_type = 1 and o.pay_status = 1))";
            
        }elseif($filter['type'] == 3){
            $where['o.order_status']    = 1;
            // $where['o.pay_status']      = 0;
            // $where['o.shipping_status'] = 1;
            
            $whereor = "((o.pay_type = 2 and o.pay_status = 0 and o.shipping_type = 1 and o.shipping_status = 1) or (o.pay_type = 2 and o.pay_status = 0 and o.shipping_type = 2 and o.shipping_status != 2 ) or (o.pay_type = 1 and o.pay_status = 1 and o.shipping_type = 1 and o.shipping_status = 1) or (o.pay_type = 1 and o.pay_status = 1 and o.shipping_type = 2 and o.shipping_status != 2 ) )";
        }elseif($filter['type'] == 4){
            $where['o.order_status'] = 1;
            $where['o.pay_status']      = 1;
            $where['o.shipping_status'] = 2;
            $where['o.is_comment']      = 0;
        }else{
            $where['o.order_status'] = ['<>',4];
        }


        $articles        = $OrderModel->alias('o')->field($field)
            ->where($where)
            ->where($whereor)
            ->order('o.order_id', 'DESC')
            ->paginate(10);

        return $articles;

    }

    /**
     * 商家订单
     */
    public function supplierList($filter, $supplier_id = false)
    {

        $OrderModel = new OrderModel();
      
        $field = 'o.*';
        $where=[
            'o.order_status' => ['<>',4],
            'o.pay_status'   => 1
        ];

        if($supplier_id){
            $where['o.supplier_id']=$supplier_id;
        }
        
        if($filter['type'] == 1){
            $where['o.order_status'] = 1;
        }elseif($filter['type'] == 2){
            $where['o.order_status'] = 1;
            $where['o.shipping_status'] = ['<>',2];
        }elseif($filter['type'] == 3){
            $where['o.order_status'] = 1;
            $where['o.shipping_status'] = 2;
        }else{
            $where['o.order_status'] = ['<>',4];
        }


        $articles = $OrderModel->alias('o')->field($field)->where($where)->order('o.order_id', 'DESC')->paginate(10);

        return $articles;

    }
    /**
     * 骑手订单
     */
    public function riderList($filter, $rider_id = false){
        $OrderModel = new OrderModel();
        

        $field = 'o.*';
        $where=[
            'o.order_status'=>['<>',4],
            'o.pay_status'   => 1
        ];
        if($rider_id){
            $where['o.rider_id'] = $rider_id;
        }
        
        if($filter['type'] == 1){
            $where['o.order_status'] = 1;
            $where['o.pay_status'] = 0;
        }elseif($filter['type'] == 2){
            $where['o.order_status'] = 1;
            $where['o.pay_status'] = 1;
            $where['o.shipping_status'] = ['<>',2];
        }elseif($filter['type'] == 3){
            $where['o.order_status'] = 1;
            $where['o.pay_status'] = 1;
            $where['o.shipping_status'] = 2;
        }else{
            $where['o.order_status'] = ['<>',4];
        }


        $articles        = $OrderModel->alias('o')->field($field)
            ->where($where)
            ->order('o.order_id', 'DESC')
            ->paginate(10);

        return $articles;
    }
    /**
     * 用户订单状态
     */
    public function Handle($data){
        $ordersub   =   new OrderSubModel();
        $goodsModel                = new Goods();


        foreach ($data as $key => $value) {
            
            $sub        =   $ordersub->where('order_id',$value['order_id'])->select();
            foreach ($sub as $k => $v) {
                $goods_img=$goodsModel->field('goods_img')->where('goods_id',$v['goods_id'])->find();
                $sub[$k]['goods_img'] = $goods_img['goods_img'];
            }
            $data[$key]['sub'] = $sub;

            $data[$key]['status_id']='';
            $data[$key]['caozuo']='';
            if($value['pay_type'] == 2){
            	if($value['order_status']==0){
	                $data[$key]['status']='待确定';
	                
	            }elseif($value['order_status']==3){
	                $data[$key]['status']='退款';
	            }elseif($value['shipping_status']==0){
	                
	                if($value['shipping_type'] == 2){
	                    $data[$key]['status']='待提货';
	                    $data[$key]['caozuo']='确认收货';
	                    $data[$key]['status_id']=2;
	                }else{
	                    $data[$key]['status']='待发货';
	                }
	                
	            }elseif($value['shipping_status']==1){
	                if($value['shipping_type'] == 2){
	                    $data[$key]['status']='待提货';
	                    $data[$key]['caozuo']='确认收货';
	                }else{
	                    $data[$key]['status']='待收货';
	                    $data[$key]['caozuo']='确认收货'; 
	                }
	                
	                $data[$key]['status_id']=2;
	            }elseif($value['pay_status']==0){
	
	                $now_time = time();
	                $end_time = $value['add_time'] + $value['days']*24*60*60;
	                if($end_time < $now_time){
	
	                    $data[$key]['caozuo']     = '去付款';
	                    $data[$key]['status_id']  = 1;
	                    $data[$key]['is_pay']     = 1;
	
	                }else{
	
	                    $data[$key]['caozuo']     = '去付款';
	                    $data[$key]['is_pay']     = 2;
	                    $data[$key]['end_time']   = $end_time;
	                }
	
	                $data[$key]['status']='待付款';
	                
	            }elseif($value['pay_status']==1){
	                $data[$key]['status']='已完成';
	                if($value['is_comment'] == 0){
	                    $data[$key]['status_id']=3;
	                    $data[$key]['caozuo']='去评价';
	                }
	            }
            }else{
            	if($value['order_status']==0){
	                $data[$key]['status']='待确定';
	                
	            }elseif($value['order_status']==3){
	                $data[$key]['status']='退款';
	            }elseif($value['pay_status']==0){
	
	                $data[$key]['caozuo']     = '去付款';
                    $data[$key]['status_id']  = 1;
                    $data[$key]['is_pay']     = 1;
	
	                $data[$key]['status']='待付款';
	            }elseif($value['shipping_status']==0){
	                if($value['shipping_type'] == 2){
	                    $data[$key]['status']='待提货';
	                    $data[$key]['caozuo']='确认收货';
	                    $data[$key]['status_id']=2;
	                }else{
	                    $data[$key]['status']='待发货';
	                }
	                // if($value['prom_type'] == 1){
	                //     $data[$key]['status']='待发货';
	                // }else{
	                //     $data[$key]['status']='待发货';
	                // }
	                
	            }elseif($value['shipping_status']==1){
	                if($value['shipping_type'] == 2){
	                    $data[$key]['status']='待提货';
	                    $data[$key]['caozuo']='确认收货';
	                }else{
	                    $data[$key]['status']='待收货';
	                    $data[$key]['caozuo']='确认收货'; 
	                }
	                
	                $data[$key]['status_id']=2;
	            }elseif($value['pay_status']==1){
	                $data[$key]['status']='已完成';
	                if($value['is_comment'] == 0){
	                    $data[$key]['status_id']=3;
	                    $data[$key]['caozuo']='去评价';
	                }
	            }
            }
            
        }
        return $data;
    }
    /**
     * 商家订单状态
     */
    public function sjHandle($data){
        $ordersub   =   new OrderSubModel();
        $goodsModel                = new Goods();

        foreach ($data as $key => $value) {
            
            $sub        =   $ordersub->where('order_id',$value['order_id'])->select();
            foreach ($sub as $k => $v) {
                $goods_img=$goodsModel->field('goods_img')->where('goods_id',$v['goods_id'])->find();
                $sub[$k]['goods_img'] = $goods_img['goods_img'];
            }
            $data[$key]['sub'] = $sub;

            $data[$key]['supplier_name'] = Db::name('plugin_supplier')->where('id',$value['supplier_id'])->value('name');
            
            if($value['order_status']==3){
                $data[$key]['status']='退款';
            }elseif($value['order_status']==0){
                $data[$key]['status']='待确定';
                $data[$key]['caozuo']='确认订单';
                $data[$key]['status_id']=1;
            }elseif($value['order_status']==3){
                $data[$key]['status']='退款';
            }elseif($value['shipping_status']==2){
                $data[$key]['status']='已完成';
               
            }elseif($value['shipping_status']==1){
                $data[$key]['status']='待取餐';
                
            }elseif($value['pay_status']==1){
                $data[$key]['status']='待配送';

            }elseif($value['pay_status']==0){
                $data[$key]['status']='待付款';
            }

        }
        return $data;
    }
    /**
     * 骑手状态
     */
    public function rdHandle($data){
        $ordersub   =   new OrderSubModel();
        $goodsModel                = new Goods();
        foreach ($data as $key => $value) {
            
            //订单产品

            $sub        =   $ordersub->where('order_id',$value['order_id'])->select();
            foreach ($sub as $k => $v) {
                $goods_img=$goodsModel->field('goods_img')->where('goods_id',$v['goods_id'])->find();
                $sub[$k]['goods_img'] = $goods_img['goods_img'];
            }
            $data[$key]['sub'] = $sub;

            $data[$key]['supplier_name'] = Db::name('plugin_supplier')->where('id',$value['supplier_id'])->value('name');
            $data[$key]['prefer_name'] = Db::name('plugin_prefer')->where('id',$value['prefer_id'])->value('name');
            
            if($value['order_status']==3){
                $data[$key]['status']='退款';
            }elseif($value['order_status']==0){
                $data[$key]['status']='待确定';
                $data[$key]['caozuo']='配送';
                $data[$key]['status_id']=1;
            }elseif($value['order_status']==3){
                $data[$key]['status']='退款';
            }elseif($value['shipping_status']==2){
                $data[$key]['status']='已完成';
               
            }elseif($value['shipping_status']==1){
                $data[$key]['status']='待取餐';
                if($value['is_send']==0){
                    if(!empty($value['formid'])){
                        $data[$key]['caozuo']    = '发送通知';
                        $data[$key]['status_id'] = 2;
                    }
                    
                }
            }elseif($value['pay_status']==1){
                $data[$key]['status']='待配送';
                $data[$key]['caozuo']='配送';
                $data[$key]['status_id']=1;
            }elseif($value['pay_status']==0){
                $data[$key]['status']='待付款';
                
            }
            // $data[$key]['status_id']='';
            // $data[$key]['caozuo']='';
        }
        return $data;
    }
    public function HandleBao($data){
        foreach ($data as $key => $value) {
            $end_time = strtotime('+1 month');
            if($value['xubao']==0){
                if($value['end_time']<$end_time){
                    $data[$key]['xu']=1;
                }else{
                    $data[$key]['xu']=0;
                }
            }else{
                if($value['end_time']<time()){
                     $data[$key]['xu']=2;
                }else{
                    $data[$key]['xu']=0;
                }
            }
            
        }
        return $data;
    }

    public function Hand($data){
        

        $data['status_id']  = 0;
        $data['is_tui']     = 0;
        $data['caozuo']     = '';
        $data['is_pay']     = 0;
        
        $order_status     = config('ORDER_STATUS');
		$pay_status       = config('PAY_STATUS');
		$shipping_status  = config('SHIPPING_STATUS');
        
        $data['zhuagtai'] = $order_status[$data['order_status']].'/'.$pay_status[$data['pay_status']];
        if($data['shipping_type'] == 2 && $data['shipping_status'] != 2){
        	$data['zhuagtai'] .= "/待提货";
        }else{
        	$data['zhuagtai'] .= '/'.$shipping_status[$data['shipping_status']];
        }
		if($data['pay_type'] == 2){
			if($data['order_status']==0){
	            $data['status']='待确定';
	            
	        }elseif($data['order_status']==3){
	            $data['status']='退款';
	        }elseif($data['shipping_status']==0){
	            
	            if($data['shipping_type'] == 2){
                    $data['status']='待提货';
                    $data['caozuo']='确认收货';
                    $data['status_id']=2;
                }else{
                    $data['status']='待发货';
                }
	            
	        }elseif($data['shipping_status']==1){
	            if($data['shipping_type'] == 2){
                    $data['status']='待提货';
                    $data['caozuo']='确认收货';
                }else{
	                $data['status']='待收货';
	                $data['caozuo']='确认收货'; 
	            }
	            
	            $data['status_id']=2;
	        }elseif($data['pay_status']==0){
	        	
	            $now_time = time();
	            $end_time = $data['add_time'] + $data['days']*24*60*60;
	            if($end_time < $now_time){
	
	                $data['caozuo']     = '去付款';
	                $data['status_id']  = 1;
	                $data['is_pay']     = 1;
	
	            }else{
	                $cha = $end_time - $now_time;
	
	                $data['is_pay']     = 2;
	                $data['end_time']   = $end_time;
	                $data['caozuo']     = '去付款';
	            }
	            $data['status']='待付款';
	
	        }elseif($data['pay_status']==1){
	            $data['status']='已完成';
	            if($data['is_comment'] == 0){
	                $data['status_id']=3;
	                $data['caozuo']='去评价';
	            }
	        }
		}else{
			if($data['order_status']==0){
	            $data['status']='待确定';
	            
	        }elseif($data['order_status']==3){
	            $data['status']='退款';
	        }elseif($data['pay_status']==0){
	            $data['caozuo']     = '去付款';
	            $data['status_id']  = 1;
	            $data['is_pay']     = 1;
	            $data['status']='待付款';
	
	        }elseif($data['shipping_status']==0){
	            
	            if($data['shipping_type'] == 2){
                    $data['status']='待提货';
                    $data['caozuo']='确认收货';
                    $data['status_id']=2;
                }else{
                    $data['status']='待发货';
                }
	            
	        }elseif($data['shipping_status']==1){
	            if($data['shipping_type'] == 2){
                    $data['status']='待提货';
                    $data['caozuo']='确认收货';
                }else{
	                $data['status']='待收货';
	                $data['caozuo']='确认收货'; 
	            }
	            
	            $data['status_id']=2;
	        }elseif($data['pay_status']==1){
	            $data['status']='已完成';
	            if($data['is_comment'] == 0){
	                $data['status_id']=3;
	                $data['caozuo']='去评价';
	            }
	        }
		}
        



        $time =time();
        if($data['prom_type'] == 0){
            
            $today_date = date('Y-m-d',$time);
            $start_date = strtotime($today_date);
            $end_date = strtotime($today_date.' 11:00');

            if(($data['add_time'] < $end_date) && ($data['add_time'] > $start_date) && $data['order_status']!=3){
                $data['is_tui'] = 1;
            }
        }else{
            if($data['yu_time']  && $data['order_status']!=3){
                $yu_time =$data['yu_time']-30*60;
                if($yu_time > $time){
                    $data['is_tui'] = 1;
                }
            }
             
        }
        
       
        return $data;
    }
    /**
     * 已发布文章查询
     * @param  int $postId     文章id
     * @param int  $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderInfo($id, $userId = 0)
    {
        $OrderModel =   new OrderModel();
        $ordersub   =   new OrderSubModel();
        $where=[
            'order_id'  =>  $id,
            'order_status'=>['<>',4]
        ];
        if($userId){
            $where['user_id']=$userId;
        }
        $order_info =   $OrderModel->where($where)->find();
        if($order_info){
            $order_info =   $this->Hand($order_info);
            $OrderLogic = new OrderLogic;
            $order_info['address'] = $OrderLogic->getAddressName($order_info['province'],$order_info['city'],$order_info['district']).$order_info['address'];
            $sub        =   $ordersub->where('order_id',$id)->select();
			
			
            $goods                 = new Goods();
            foreach ($sub as $key => $value) {
                $goods_img=$goods->field('goods_img')->where('goods_id',$value['goods_id'])->find();
                $sub[$key]['goods_img'] = $goods_img['goods_img'];
            }
            $order_info->sub = $sub;
        }
        

        return $order_info;
    }
    /**
     * 骑手订单详情
     */
    public function orderRiderInfo($id,$userId){
        $OrderModel =   new OrderModel();
        $ordersub   =   new OrderSubModel();
        $where=[
            'order_id'  =>  $id,
            'order_status'=>['<>',4]
        ];
        
        $where['rider_id']=$userId;
        
        $order_info =   $OrderModel->where($where)->find();
        if($order_info){
            $order_info =   $this->Hand($order_info);
            $sub        =   $ordersub->where('order_id',$id)->select();

            $goods                 = new Goods();
            foreach ($sub as $key => $value) {
                $goods_img=$goods->field('goods_img')->where('goods_id',$value['goods_id'])->find();
                $sub[$key]['goods_img'] = $goods_img['goods_img'];
            }
            $order_info->sub = $sub;
        }
        

        return $order_info;
    }
    /**
     * 骑手订单详情
     */
    public function orderSupplierInfo($id,$supplier_id){
        $OrderModel =   new OrderModel();
        $ordersub   =   new OrderSubModel();
        $where=[
            'order_id'  =>  $id,
            'order_status'=>['<>',4]
        ];
        
        $where['supplier_id']=$supplier_id;
        
        $order_info =   $OrderModel->where($where)->find();
        if($order_info){
            $order_info =   $this->Hand($order_info);
            $sub        =   $ordersub->where('order_id',$id)->select();

            $goods                 = new Goods();
            foreach ($sub as $key => $value) {
                $goods_img=$goods->field('goods_img')->where('goods_id',$value['goods_id'])->find();
                $sub[$key]['goods_img'] = $goods_img['goods_img'];
            }
            $order_info->sub = $sub;
        }
        

        return $order_info;
    }
    public function orderNum($userId = 0){
        $data=[];
        $OrderModel =   new OrderModel();
        $where=[
            'user_id'   => $userId,
            'pay_status'=> 0,
            'order_status'=>['<>',4]
        ];
        $data['nopay']  = $OrderModel->where($where)->count();
        $where['order_status']=1;
        $where['pay_status']=1;
        $where['shipping_status']=0;
        $data['waitShip']   = $OrderModel->where($where)->count();
        $where['order_status']=1;
        $where['pay_status']=1;
        $where['shipping_status']=1;
        $data['waitInvalid']   = $OrderModel->where($where)->count();
        return $data;
    }


}