<?php

namespace api\goods\logic;

use think\Db;
use think\Log;
use think\Model;
use api\goods\model\CartModel;
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

    function addCart($goods_id,$goods_num,$user_id = 0,$item_path="")
    {

        $where['goods_id'] = $goods_id;

        $goods = Db::name('Goods')->where($where)->find();
        if(empty($goods)){
            return array('status'=>-2,'msg'=>'产品不存在！','result'=>'');
        }
        // 找出这个
        $where = ["type"=>0,'user_id'=>$user_id];

        $catr_count = Db::name('Cart')->where($where)->count(); // 查找购物车产品总数量
        if($catr_count >= 20){
            return array('status'=>-9,'msg'=>'购物车最多只能放20种产品','result'=>'');
        }



        $where['goods_id']  = $goods_id;


        $specGoods = $this->getSkuArr($item_path,$goods_id);
        $specGoodsSku       = $specGoods['specGoodsSku'];
        $spec_item_name     = $specGoods['spec_item_name'];
        $goods_spec_path    = $specGoods['goods_spec_path'];

        //规格信息
        $sku_one = Db::name('goods_sku')->where('goods_id',$goods_id)->find();
        if($sku_one && empty($specGoodsSku)){
            return array('status'=>-101,'msg'=>'请选择规格','result'=>'');
        }elseif(!empty($specGoodsSku)){
            $goods['store_count'] = $specGoodsSku['store_count'];
            $goods['shop_price']  = $specGoodsSku['price'];
        }

        $where['spec_path'] = $goods_spec_path;
        $catr_goods = Db::name('Cart')->where($where)->find(); // 查找购物车是否已经存在该产品



        $data = [
            'user_id'         => $user_id,   // 用户id
            'goods_id'        => $goods_id,   // 产品id
            'goods_cat_id'    => $goods['cat_id'],   // 产品分类id
            'supplier_id'     => $goods['supplier_id'],
            'goods_sn'        => $goods['goods_sn'],   // 产品货号
            'goods_name'      => $goods['goods_name'],   // 产品名称
            'market_price'    => $goods['market_price'],   // 市场价
            'goods_price'     => $goods['shop_price'],  // 购买价
            'member_goods_price' => $goods['shop_price'],  // 会员折扣价 默认为 购买价
            'goods_num'        => $goods_num, // 购买数量
            'type'             => 0,
            'goods_img'        => $goods['goods_img'],
            'spec_path'        => $goods_spec_path, // 规格key
            'spec_item_name'   => $spec_item_name, // 规格 key_name
           // 'sku'        => "{$specGoodsPriceList[$spec_key]['sku']}", // 产品条形码
            'add_time'        => time()
        ];

       // 如果产品购物车已经存在
       if($catr_goods)
       {
            //是否存在规格

            // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            $res_num = $catr_goods['goods_num'] + $goods_num;
            if($res_num < 1){
                $result = Db::name('Cart')->where("id",$catr_goods['id'])->delete();
                return ['status'=>1,'msg'=>'修改购物车'];
            }else{
                if(($catr_goods['goods_num'] + $goods_num) > $goods['store_count']){

                    $cha_num = $goods['store_count'] - $catr_goods['goods_num'];
                    return ['status'=>-102,'msg'=>'库存不足,最多购买'.$cha_num.'件','result'=>''];

                }else{

                    $result = Db::name('Cart')->where("id",$catr_goods['id'])->update(["goods_num"=>($catr_goods['goods_num'] + $goods_num)]); // 数量相加
                    return ['status'=>1,'msg'=>'成功加入购物车'];
                }
            }

        }
        else
        {
            if($goods_num <= 0){
                return ['status'=>-2,'msg'=>'加入购物车失败','result'=>''];
            }else{
                if($goods_num >$goods['store_count'])
                    return ['status'=>-102,'msg'=>'库存不足,最多购买'.$goods_num.'件','result'=>''];

                $insert_id = DB::name('Cart')->insert($data);
                return ['status'=>1,'msg'=>'成功加入购物车'];
            }

        }
        return ['status'=>-5,'msg'=>'加入购物车失败'];
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
            'type'        => 0,
            'user_id'     => $user_id
        ];
        if($type == 1){
            $where['selected'] = 1;
        }
        $cartModel = new CartModel;
        
        $result = [];
        $anum = $total_price =  $cut_fee = 0;
        
        $cartList = $cartModel->where($where)->select()->toArray();  // 获取购物车产品
        foreach ($cartList as $k=>$val){
            $cartList[$k]['checked']   = $val['selected'] == 1?true:false;
            $cartList[$k]['goods_fee'] = sprintf('%.2f',$val['goods_num'] * $val['goods_price']);
        }

        return ['status'=>1,'msg'=>'','result'=>['cartList' =>$cartList]];
    }
    public function getTotalPrice($user_id,$type=0,$coupon_id = 0){
        $coupon_price = $shipping_price = 0;
        if($type == 0){
            $where = [
                'type'        => 0,
                'user_id'     => $user_id,
                'selected'    => 1
            ];

            $cartModel = new CartModel;
            $cartList = $cartModel->where($where)->select();  // 获取购物车产品
            $anum = $total_price =  $cut_fee = 0;
            foreach ($cartList as $k=>$val){
                $anum        += $val['goods_num'];
                $cut_fee     += $val['goods_num'] * $val['goods_price']; //应付价格
                $total_price += $val['goods_num'] * $val['goods_price']; //产品总价
            }

        }else{
            $where = [
                'type'        => 1,
                'user_id'     => $user_id
            ];
            $cartModel = new CartModel;
            $cart = $cartModel->where($where)->find();  // 获取购物车产品
            $anum = $cart['goods_num'];
            $total_price =  $cart['goods_num']*$cart['goods_price'];   //总价
            $cut_fee = $cart['goods_num']*$cart['goods_price'];        //消减费用

        }
        if(!empty($coupon_id)){
           // $coupon = Db::name('coupon')->where(['user_id'=>$user_id,'id'=>$coupon_id,'is_use'=>0,'end_time'=>['>',time()]])->find();
           /************修改部分01********************/  
           $map = [
                ['user_id'  ,'=', $user_id],
                ['id'       ,'=', $coupon_id],
                ['end_time' ,'>',time() ]
            ];
           $coupon = Db::name('coupon')->field('amount,total_amount')->where($map)->find();
           /************修改部分01******************/
           
       
       
       
            /*    
            if($coupon && $coupon['total_amount'] <= $total_amount){
                $cut_fee      = $order_amount - $coupon['amount'];
                $coupon_price = $coupon['amount'];
            }else{
                $coupon_id = 0;
            }*/
            /************修改部分02******************/
            if ($coupon && $coupon['total_amount'] <= $total_price ){
                $cut_fee = $total_price - $coupon['amount'];
                $coupon_price = $coupon['amount'];
            }else{
                $coupon_id = 0;
            }
            /************修改部分02******************/
            
        }
        $result = ['total_fee' => sprintf('%.2f', $total_price), 'cut_fee' => sprintf('%.2f', $cut_fee),'coupon_price' => sprintf('%.2f', $coupon_price),'coupon_id'=>$coupon_id,'shipping_price'=>$shipping_price,'num'=> $anum];

        return $result;
    }
    public function getOrderCoupon($user_id,$type=1){
        /*
        
        $list = Db::name('coupon')->field('id,user_id,amount,total_amount,name,remark')->where(['user_id'=>$user_id,'is_use'=>0,'end_time'=>['>',time()]])->select();
        foreach ($list as $key=>$vo){
            $lisk[$key]['start_time'] = date('Y-m-d H:i',$vo['start_time']);
            $lisk[$key]['end_time'] = date('Y-m-d H:i',$vo['end_time']);
        }*/
        
        
        $field = 'id,user_id,amount,total_amount,name,remark,start_time,end_time,is_use';
        
         //有效(未使用 is_use = 0 && end_time > time())
        $active = Db::name('coupon')->field($field)->where([['user_id', '=', $user_id],['type', '=', $type], ['is_use','=', 0], ['end_time','>',time()]])->select();
        
        
         //失效(已使用: is_use = 1 || 已过期:  end_time < time())
        $invalid = Db::name('coupon')
            ->field($field)
            ->where('user_id', '=', $user_id)
            ->where('type', '=', $type)
            ->where('is_use = 1 or end_time <'.time())
            ->select();
        
        $list['active'] = $active;
        $list['activeCount'] = count($active->toArray());

        $list['invalid'] = $invalid;
        $list['invalidCount'] = count($invalid->toArray());
        
        return $list;
    }
    /**
     * 直接购买
     * @param type $goods_id  产品id
     * @param type $goods_num   产品数量
     * @param type $goods_spec  选择规格
     */
    public function buyGoods($goods_id,$goods_num,$goods_spec,$user_id,$prom_type = 0){
        $prom_id = 0;
        switch ($prom_type) {
            case 0:
                // 普通商品
                    $where=[
                        'goods_id'=>$goods_id
                    ];
                    $goods = Db::name('Goods')->where($where)->find(); // 找出这个产品
                    if(!$goods){
                        return array('status'=>-1,'msg'=>'产品不存在','result'=>'');
                    }
                    if($goods_num <= 0){
                        return array('status'=>-2,'msg'=>'购买产品数量不能为0','result'=>'');
                    }
                    //产品规格
                    $specGoods = $this->getSkuArr($goods_spec,$goods_id);
                    $specGoodsSku       = $specGoods['specGoodsSku'];
                    $spec_item_name     = $specGoods['spec_item_name'];
                    $goods_spec_path    = $specGoods['goods_spec_path'];

                    //规格信息
                    $sku_one = Db::name('goods_sku')->where('goods_id',$goods_id)->find();
                    if($sku_one && empty($specGoodsSku)){
                        return array('status'=>-101,'msg'=>'请选择规格','result'=>'');
                    }elseif(!empty($specGoodsSku)){
                        $goods['store_count'] = $specGoodsSku['store_count'];
                        $goods['shop_price']  = $specGoodsSku['price'];
                    }
                break;
            case 1:
                //秒杀
                $goods = Db::name('store_seckill')->where('id',$goods_id)->find();
                //秒杀时间
                $time=time();
                if($goods['start_time']>$time){
                    return array('status'=>-2,'msg'=>'活动还未开始','result'=>'');
                }elseif($goods['end_time']<$time){
                    return array('status'=>-2,'msg'=>'活动已经结束','result'=>'');
                }
                $prom_id  = $goods_id;
                $goods_id = $goods['goods_id'];
                $goods['goods_sn']     = '';
                $goods['market_price'] = $goods['ot_price'];
                $goods_spec_path = $spec_item_name = '';
                break;
            default:
                return ['status'=>-2,'msg'=>'产品不存在','result'=>''];
                break;
        }



        if($goods['store_count'] < $goods_num){
            return ['status'=>-102,'msg'=>'库存不足！','result'=>''];
        }
        $goods['buy_num'] = $goods_num;

        //产品超出存库
        if($goods_num > $goods['store_count']){
            $goods_num = $goods['store_count'];
        }

        Db::name('cart')->where(['user_id'=>$user_id,'type'=>1])->delete();
        $data = array(
            'user_id'          => $user_id,   // 用户id
            'goods_id'         => $goods_id,   // 产品id
            'goods_sn'         => $goods['goods_sn'],   // 产品货号
            'goods_name'       => $goods['goods_name'],   // 产品名称
            'market_price'     => $goods['market_price'],   // 市场价
            'goods_price'      => $goods['shop_price'],  // 购买价
            'member_goods_price'=>$goods['shop_price'],
            'goods_img'        => $goods['goods_img'],
            'supplier_id'      => $goods['supplier_id'],
            'goods_num'        => $goods_num, // 购买数量
            'spec_path'        => $goods_spec_path, // 规格key
            'spec_item_name'   => $spec_item_name, // 规格 key_name
            'add_time'         => time(), // 加入购物车时间
            'type'             => 1,
            'prom_type'        => $prom_type,
            'prom_id'          => $prom_id
        );
        $insert_id = DB::name('Cart')->insert($data);
        return array('status'=>1);
    }


    public function getBuyGoods($user_id){
        $cartModel = new CartModel;
        $goods = $cartModel->where(['user_id'=>$user_id,'type'=>1])->find();
        if(!$goods){
            return array('status'=>0,'msg'=>'无订单提交！');
        }
        
        $cut_fee = $goods['goods_num'] * $goods['goods_price']; //应付价格
        $total_price = $goods['goods_num'] * $goods['goods_price']; //产品总价
        $coupon_fee = 0;

        $anum=$goods['goods_num'];
        $time = time();

        $result = [ 
            [$goods]
        ];
        return array('status'=>1,'msg'=>'','result'=>['cartList' =>$result]);
    }
    /**
     * 添加订单
     * @param type $user_id  用户id
     * @param type $address_id 地址id
     * @param type $cart_price 应付金额
     * @param type $pay_status 支付方式
     */
    public function addOrder($user_id,$address_id,$cart_price,$type,$to_buy=''){
        // 仿制灌水 1天只能下 50 单

        $order_count = Db::name('Order')->where("user_id= $user_id and order_sn like '".date('Ymd')."%'")->count(); // 查找购物车产品总数量
        if($order_count >= 50)return array('status'=>-9,'msg'=>'一天只能下50个订单','result'=>'');

        $address = Db::name('UserAddress')->where(["address_id"=>$address_id,'user_id'=>$user_id])->find();
        if(!$address)return array('status'=>-9,'msg'=>'请填写收货地址！','result'=>NULL);
        $param = request()->param();
        $pay_code = $param['pay_code'];
        $pay_name = $pay_code=='wxpay'?'微信支付':'支付宝支付';

        $transStatu=0;
        Db::startTrans(); //开启事务
        try {


            $request = request();
            $user_note = $request->param('note');
            $order_amount   = $cart_price['cut_fee'];
            $total_amount   = $cart_price['total_fee'];
            $coupon_price   = $cart_price['coupon_price'];
            $shipping_price = $cart_price['shipping_price'];
            $coupon_id      = $cart_price['coupon_id'];
            
            $result_sn = $order_sn = get_order_sn();
            $pay_data = [
                'amount'   => $order_amount,
                'is_paid'  => 0,
                'type'     => 2,
                'order_sn' => $order_sn,
                'pay_code' => $pay_code,
                'add_time' => time(),
                'user_id'  => $user_id
            ];
            $pay_id = Db::name('pay_log')->insertGetId($pay_data);
            
            /*
            if(!empty($coupon_id)){
                Db::name('coupon')->where('id',$coupon_id)->update(['is_use'=>1,'use_time'=>time(),'order_id'=>$order_id]);
            }
            */
            
            /****************************修改部分03**************/
            if(!empty($coupon_id)){
                Db::name('coupon')->where('id',$coupon_id)->update(['is_use'=>1,'use_time'=>time(),'order_id'=>$pay_id]);
            }
            /****************************修改部分03**************/
            
            
            
            $data = [
                'order_sn'      => $order_sn, // 订单编号
                'user_id'       => $user_id, //用户id
                'consignee'     => $address['consignee'], // 收货人
                'country'       => $address['country'],//'省份id',
                'province'      => $address['province'],//'详细地址',
                'city'          => $address['city'],//'详细地址',
                'district'      => $address['district'],//'详细地址',
                'address'       => $address['address'],//'详细地址',
                'mobile'        => $address['mobile'],//'手机',
                'goods_price'   => $cart_price['total_fee'],//'产品总价'
                'order_amount'  => $order_amount,//'应付款金额',暂无优惠券运费等金额
                'total_amount'  => $total_amount,
                'coupon_price'  => $coupon_price,
                'shipping_price'=> $shipping_price,
                'add_time'      => time(), //下单时间
                'coupon_id'     => $coupon_id,
                'order_status'  => 1,
                'user_note'     => $user_note,
                'pay_status'    => 0,
                'pay_code'      => $pay_code,
                'pay_name'      => $pay_name
            ];
            $order_id = Db::name("Order")->insertGetId($data);

            // 记录订单操作日志
            
            //立即下单
            if($to_buy && $to_buy == 'buy'){
                $s_goods = Db::name('Cart')->field('goods_id,goods_name,goods_sn,goods_num,goods_img,spec_item_name,spec_path,goods_price,member_goods_price,supplier_id,prom_type,prom_id')->where(['user_id'=>$user_id,'type'=>1])->find();
                Db::name('pay_log')->where('id',$pay_id)->update(['goods_name'=>$s_goods['goods_name']]);

                Db::name('order')->where('order_id',$order_id)->update(['prom_type'     => $s_goods['prom_type'],'prom_id'       => $s_goods['prom_id']]);
            
                
                //直接购买加入订单
                logOrder($order_id,'提交订单','提交订单',$user_id);
                
                $s_goods['order_id']    = $order_id; // 订单id
            
                Db::name("OrderSub")->strict(false)->insert($s_goods);
               
                Db::name('Cart')->where(['user_id'=>$user_id,'type'=>1])->delete();

                logOrder($order_id,'下单成功','add',$user_id);


            }else{

                //购物车加入订单
                $where = ['user_id'=>$user_id,'selected'=>1];
               
                $cartList = Db::name('Cart')->field('goods_id,goods_name,goods_sn,goods_num,goods_img,spec_item_name,spec_path,goods_price,member_goods_price,supplier_id,prom_type,prom_id')->where($where)->select();
                foreach($cartList as $key=>$v)
                {
                    $prom_type  = $v['prom_type'];
                    $prom_id    = $v['prom_id'];
                    $goods_name = $v['goods_name'];
                    $v['order_id']    = $order_id; // 订单id
                    Db::name("OrderSub")->strict(false)->insert($v);
                    if($key == 0){
                        Db::name('order')->where('order_id',$order_id)->update([
                            'prom_type' => $prom_type,
                            'prom_id'   => $prom_id
                        ]);
                        Db::name('pay_log')->where('id',$pay_id)->update(['goods_name'=>$goods_name]);
                    }
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
        return ['status'=>$transStatu,'msg'=>$msg,'result'=>$result_sn]; // 返回新增的订单id
    }


    /**
     * 查看购物车的产品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function cart_count($user_id,$mode = 0){
        $count = Db::name('Cart')->where("user_id = $user_id and selected = 1")->count();
        if($mode == 1) return  $count;

        return ['status'=>1,'msg'=>'','result'=>$count];
    }
    public function buy_count($user_id,$mode = 0,$type = 0){
        $where=[
            'user_id'=>$user_id
        ];
        $where['type'] = $type == 0?0:1;

        $count = Db::name('Cart')->where($where)->count();
        if($mode == 1) return  $count;

        return ['status'=>1,'msg'=>'','result'=>$count];
    }
    public function getSkuArr($goods_spec,$goods_id){
        $result = [
            'spec_item_name'  => '',
            'goods_spec_path' => '',
            'specGoodsSku'    => []
        ];
        if(empty($goods_spec))return $result;

        $goods_spec = is_array($goods_spec)?$goods_spec:explode('-', $goods_spec);

        //处理产品规格
        foreach($goods_spec as $vo){
            $spec_path = Db::name('spec_item')->field('spec_id,item')->where("id",$vo)->find();
        $spec_name = Db::name('spec')->where('id',$spec_path['spec_id'])->value('spec_name');

            $spec_item_name[] = $spec_name.'：'.$spec_path['item'];
        }

        sort($goods_spec);
        $spec_item_name = implode(' ',$spec_item_name);//显示规格名
        $goods_spec_path = implode('-',$goods_spec);//规格规则

        // 是否选择产品规格
        $specGoodsSku = Db::name('GoodsSku')->where(["goods_id" => $goods_id,"item_path"=>$goods_spec_path])->find();


        if($specGoodsSku){
            $result['spec_item_name']  = $spec_item_name;
            $result['goods_spec_path'] = $goods_spec_path;
            $result['specGoodsSku']    = $specGoodsSku;
        }
        return $result;
    }

}
