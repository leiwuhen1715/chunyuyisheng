<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\goods\controller;

use think\Db;
use cmf\controller\RestBaseController;
use api\goods\model\GoodsModel as Goods;
use api\goods\logic\CartLogic;


class CartController extends RestBaseController
{
    //type:0 获取全部购物城订单 1：获取结算场频
    public function cartList(){
        $userId       = $this->getUserId();
        $cartLogic = new CartLogic();
        $type = $this->request->param('type',0,'intval');

        $cartLogic = new CartLogic();
        if($type == 0){
            $res = $cartLogic->cartList($userId);
        }else{
            $res = $cartLogic->getBuyGoods($userId);
        }

        if($res['status']==1){
            $result=[
                'cartList'      =>$res['result']['cartList']
            ];

            $this->success('ok',$result);
        }else{
            $this->error($res['msg']);
        }

    }

    /*获取结算金额*/
    public function getTotalPrice(){
        
        $coupon_id = $this->request->param('coupon_id',0,'intval');
        $type = $this->request->param('type',0,'intval');
        $userId       = $this->getUserId();

        $cartLogic   = new CartLogic;
        $total_price = $cartLogic->getTotalPrice($userId,$type,$coupon_id);
        $this->success('ok',$total_price);
    }
    /*获取优惠卷*/
    public function orderCoupon(){
        $type      = $this->request->param('type',0,'intval');
        $userId       = $this->getUserId();
        $type   = $type==1?0:1;
        $cartLogic = new CartLogic;
        $list      = $cartLogic->getOrderCoupon($userId,$type);
        $this->success('ok',$list);
    }
    //购物车
    public function addCart(){
        $userId       = $this->getUserId();

        $goods_id  = $this->request->param('goods_id', 0, 'intval');
        $type      = $this->request->post('type', 0, 'intval');
        $item_path = $this->request->post('item_path');
        $num       = $this->request->param('goods_num', 0, 'intval');
        if(empty($num)){
            $num = $type == 1?1:-1;
        }

        $cartLogic = new CartLogic();
        $result = $cartLogic->addCart($goods_id,$num,$userId,$item_path);

        if($result['status']==1){
            $this->success('加入成功!');
        }else{
            $this->error($result['msg']);

        }
    }
    //购物车删除
    public function deleteCart(){
        $id = $this->request->param('id',0,'intval');
        $userId       = $this->getUserId();

        $ids = request()->param('ids');
        $where = [];
        if(!empty($ids)){
          	$id_arr  = explode(',',$ids);
          	$where[] = ['id','in',$id_arr];
        }else{
        	$where[] = ['selected','=',1] ;
        }

        Db::name('cart')->where(['user_id'=>$userId,'type'=>0])->where($where)->delete();
        $this->success('ok');
    }

    //购物车选中
    public function select(){
        $userId   = $this->getUserId();
        $id       = $this->request->param('id',0,'intval');
        $type     = $this->request->param('type',0,'intval');

        if($type == 1){
            $select     = $this->request->param('select');
            $selected   = $select=='true'?1:0;
            Db::name('cart')->where(['user_id'=>$userId,'type'=>0])->update(['selected'=>$selected]);
            $this->success('ok');
        }else{
            $cart     = Db::name('cart')->field('selected')->where(['user_id'=>$userId,'id'=>$id])->find();
            if($cart){
                $selected = $cart['selected']==1?0:1;
                Db::name('cart')->where('id',$id)->update(['selected'=>$selected]);
                $this->success('ok');
            }
        }
    }

    //立即下单
    public function buyGoods(){
        $userId       = $this->getUserId();

        $goods_id  = $this->request->post('goods_id', 0, 'intval');
        $goods_num = $this->request->post('goods_num', 1, 'intval');
        $prom_type = $this->request->post('prom_type', 0, 'intval');
        $item_path = $this->request->post('item_path');

        $cartLogic = new CartLogic();
        $result = $cartLogic->buyGoods($goods_id, $goods_num, $item_path,$userId,$prom_type);

        if($result['status']==1){
            $this->success('加入成功!');
        }else{
            $this->error($result['msg']);

        }
    }


    /**
     * 获取结算信息
     */
    public function orderGoods(){
        $userId       = $this->getUserId();
        $cartLogic = new CartLogic();
        $type = $this->request->param('type',0,'intval');

        $cartLogic = new CartLogic();
        if($type == 0){
            $res = $cartLogic->cartList($userId,1);
        }else{
            $res = $cartLogic->getBuyGoods($userId);
        }

        if($res['status']==1){
            $result=[
                'cartList'      =>$res['result']['cartList']
            ];
            $this->success('ok',$result);
        }else{
            $this->error($res['msg']);
        }
    }

    //提交订单

    public function addOrder(){
        $param = request()->param();
        $address_id = $this->request->post('address_id', 0, 'intval');
        $coupon_id  = $this->request->post('coupon_id', 0, 'intval');
        $type       = $this->request->post('type', 0, 'intval');

        $userId       = $this->getUserId();
        $cartLogic = new CartLogic();
        $count=$cartLogic->buy_count($userId,1,$type);
        if($count<1)$this->error('没有购买的产品！'); // 返回结果状态
        if(empty($param['pay_code']))$this->error('请选择支付方式');

        $total_price = $cartLogic->getTotalPrice($userId,$type,$coupon_id);
        $buy = $type == 0?'':'buy';

        $result = $cartLogic->addOrder($userId,$address_id,$total_price,1,$buy);
        
        if($result['status']=='1'){
            $this->success('ok',$result);
        }else{
            $this->error($result['msg']);
        }
    }




}
