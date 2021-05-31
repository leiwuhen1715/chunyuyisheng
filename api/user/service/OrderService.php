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
namespace api\user\service;

use api\goods\model\GoodsModel as Goods;
use api\user\model\OrderSubModel;
use api\user\model\OrderModel;
use api\goods\logic\OrderLogic;
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
            // 'o.order_status' => 1
        ];
        if($userId){
            $where[] = ['o.user_id','=',$userId];
        }
        $whereor='';

        $filter['type']= empty($filter['type'])?0:$filter['type'];
        if($filter['type'] == 1){
            
            $where[] = ['o.pay_status','=',0];
            $where[] = ['o.order_status','in',[0,1]];
            
        }elseif($filter['type'] == 2){

            $where[] = ['o.order_status','=',1];
            $where[] = ['o.pay_status','=',1];
            $where[] = ['o.shipping_status','=',0];
            
        }elseif($filter['type'] == 3){

            $where[] = ['o.order_status','=',1];
            $where[] = ['o.pay_status','=',1];
            $where[] = ['o.shipping_status','=',1];

        }elseif($filter['type'] == 4){

            $where[] = ['o.order_status','=',1];
            $where[] = ['o.pay_status','=',1];
            $where[] = ['o.shipping_status','=',2];
            $where[] = ['o.is_comment','=',0];

        }elseif($filter['type'] == 5){
            
            $where[] = ['o.pay_status','=',1];
            $whereor = "o.order_status = 3 or o.refun_status in (1,2)";

        }elseif($filter['type'] == 6){
            
            $where[] = ['o.order_status','=',4];
            
        }elseif($filter['type'] == 7){
            
            $where[] = ['o.order_status','=',1];
            $where[] = ['o.pay_status','=',1];
            $where[] = ['o.shipping_status','=',2];
            
            /*
             * 原demo
             * $where[] = ['o.is_comment','=',1];
             * is_comment = 1  已评价
             * 订单完成
             */
            $where[] = ['o.is_comment','=',0];
            
        }


        $articles        = $OrderModel->alias('o')->field($field)
            ->where($where)
            ->where($whereor)
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

            $data[$key]['sub'] = $sub;

            $data[$key]['status_id']='';
            $data[$key]['caozuo']='';
            if($value['order_status']==3){

                if($data[$key]['refun_status'] == 0){
                    $data[$key]['status']='退款中';
                }elseif($data[$key]['refun_status'] == 1){
                    $data[$key]['status']='已退款';
                }elseif($data[$key]['refun_status'] == 2){
                    $data[$key]['status']='退款已处理';
                }
            }elseif($value['order_status']==0){
                $data[$key]['status']='待确定';
                if($value['pay_status']==0){
                    $data[$key]['status']='待支付';
                    $data[$key]['caozuo']='去付款';
                    $data[$key]['status_id']=1;
                }
            }elseif($value['order_status']==4){
                $data[$key]['status']='已取消';
                $data[$key]['status_id'] = 4;
            }elseif($value['shipping_status']==2){
                $data[$key]['status']='已完成';
                if($value['is_comment'] == 0){
                    $data[$key]['status_id']=3;
                    $data[$key]['caozuo']='去评价';
                }
                
                /*
                 *修改收货状态
                 * }elseif($value['shipping_status']==1){
                 */
                
                
            }elseif($value['shipping_status']==2){
                if($value['prom_type'] == 1){
                    $data[$key]['status']='待收货';
                    $data[$key]['caozuo']='确认收货';
                }else{
                    $data[$key]['status']='待收货';
                    $data[$key]['caozuo']='确认收货';
                }
                $data[$key]['is_tui'] = 1;
                $data[$key]['status_id']=2;
            }elseif($value['pay_status']==1){

                if($value['prom_type'] == 1){
                    $data[$key]['status']='待发货';
                }else{
                    $data[$key]['status']='待发货';
                }
                $data[$key]['is_tui'] = 1;

            }elseif($value['pay_status']==0){
                $data[$key]['status']='待付款';
                $data[$key]['caozuo']='去付款';
                $data[$key]['status_id']=1;
            }else{
                $data[$key]['status_id'] = 5;
            }
        }
        return $data;
    }


    public function Hand($data){


        $data['status_id'] = 0;
        $data['is_tui']    = 0;
        $data['status_id']='';
        $data['caozuo'] ='';
        if($data['order_status']==3){
            $data['status']='退款';
        }elseif($data['order_status']==0){
            $data['status']='待确定';
        }elseif($data['order_status']==4){
            $data['status']='已取消';
        }elseif($data['shipping_status']==2){
            $data['status']='已完成';
            if($data['is_comment'] == 0){
                $data['status_id']=3;
                $data['caozuo']='去评价';
            }
        }elseif($data['shipping_status']==1){
            $data['status']='待收货';
            $data['caozuo']='确认收货';
            $data['status_id']=2;
            $data['is_tui'] = 1;
        }elseif($data['pay_status']==1){
            $data['status']='待发货';
            $data['is_tui'] = 1;
        }elseif($data['pay_status']==0){
            $data['status']='待付款';
            $data['caozuo']='去付款';
            $data['status_id']=1;
        }

        $orderLogic = new OrderLogic;
        $data['address'] = $orderLogic->getAddressName($data['province'],$data['city'],$data['district']).$data['address'];
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
            'order_id'  =>  $id
        ];
        if($userId){
            $where['user_id']=$userId;
        }
        $order_info =   $OrderModel->where($where)->find();
        if($order_info){
            $order_info =   $this->Hand($order_info);

            $sub        =   $ordersub->where('order_id',$id)->select();

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
