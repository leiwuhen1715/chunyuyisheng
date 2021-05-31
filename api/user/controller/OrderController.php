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
use api\user\service\OrderService;
use api\goods\model\GoodsModel as Goods;
use cmf\controller\RestBaseController;
use EasyWeChat\Factory;
use think\Db;
use think\Validate;

class OrderController extends RestUserBaseController
{

    //订单列表
    public function order(){

        $param = $this->request->param();
        $userId   = $this->getUserId();
        $OrderService = new OrderService;
        $data = $OrderService->getList($param,$userId);

        $data_list=$OrderService->Handle($data->items());

        $this->success('获取成功!', $data_list);

    }

    //订单详情
    public function orderDetail(){

        $id = $this->request->get('id', 0, 'intval');

        $userId   = $this->getUserId();
        $OrderService = new OrderService;
        $data = $OrderService->orderInfo($id,$userId);

        if($data){

            $result=[
                'order_info'=> $data
            ];
            $this->success('ok', $result);
        }else{
            $this->error('非法操作！');
        }

    }
    
   /**
     * 删除订单
     */
    public function delete(){

        $id = $this->request->param('id',0,'intval');

        $userId   = $this->getUserId();
        $OrderService = new OrderService;
        $data = $OrderService->orderInfo($id,$userId);
   
        if($data['order_status'] == 4){
            Db::name('order_sub')->where('order_id',$id)->delete();
            Db::name('order_log')->where('order_id',$id)->delete();
            Db::name('order')->where('order_id',$id)->delete();

            $this->success('删除成功！');
        }else{
            $this->error('订单不能删除');
        }

    }
    
    public function getOpen(){
        $userId    = $this->getUserId();
        $data = Db::name('third_party_user')->field('openid')->where('user_id',$userId)->find();
        $this->success("ok",$data);
    }
    /**
     * 获取微信openid
     */
    public function getOpenid(){
        $validate = new Validate([
            'code'           => 'require'
        ]);
        $validate->message([
            'code.require'           => '缺少参数code!'
        ]);
        $data = $this->request->param();

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $code          = $data['code'];
        $wxappSettings = cmf_get_option('wxapp_settings');

        $appId = $this->request->header('XX-Wxapp-AppId');
        if (empty($appId)) {
            if (empty($wxappSettings['default'])) {
                $this->error('没有设置默认小程序！');
            } else {
                $defaultWxapp = $wxappSettings['default'];
                $appId        = $defaultWxapp['app_id'];
                $appSecret    = $defaultWxapp['app_secret'];
            }
        } else {
            if (empty($wxappSettings['wxapps'][$appId])) {
                $this->error('小程序设置不存在！');
            } else {
                $appId     = $wxappSettings['wxapps'][$appId]['app_id'];
                $appSecret = $wxappSettings['wxapps'][$appId]['app_secret'];
            }
        }


        $response = cmf_curl_get("https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$appSecret&js_code=$code&grant_type=authorization_code");

        $response = json_decode($response, true);
        if (!empty($response['errcode'])) {
            $this->error('操作失败!');
        }

        $openid     = $response['openid'];
        $this->success('ok',$openid);
    }


    public function getCoupon(){
        $order_id = $this->request->param('id',0,'intval');
        $userId   = $this->getUserId();
        $where = ['order_id'=>$order_id,'user_id'=>$userId];
        $order = Db::name('order')->field('is_coupon,pay_status')->where($where)->find();
        $amount = rand(1,20);
        if($order['pay_status'] == 1 && $order['is_coupon'] == 0 ){
            $start_time = time();
            $end_time   = $start_time + 24*3600*3;
            $data = [
                'add_time'   => time(),
                'user_id'    => $userId,
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'amount'     => $amount/10
            ];
            Db::name('coupon')->insert($data);
            Db::name('order')->where($where)->update(['is_coupon'=>1]);
            $this->success('ok',$data);
        }else{
            $this->error('不存在');
        }
    }


    //确认收货
    public function invalid(){
        $id = $this->request->post('id', 0, 'intval');
        $order = DB::name('order');
        $userId   = $this->getUserId();
        $where = [
            'user_id' =>  $userId,
            'order_id'=>$id
        ];
        $order_info    = Db::name('order')->where($where)->find();
        if($order_info){
            if($order_info['shipping_status']==1){

                if($order_info['shipping_status']==1){
                    $data=['shipping_status'=>2];
                    $order->where($where)->update($data);
                    logOrder($id,'确认收货','receive',$userId);
                    $this->success('收货成功');
                }elseif($order_info['shipping_status']==0){
                    $this->error('订单未配送！');
                }


            }elseif($order_info['shipping_status']==2){
                $this->error('订单已收货！');
            }else{
                $this->error('订单未配送！');
            }
        }else{
            $this->error('非法操作！');
        }

    }
     /**
     * 取消订单
     */
    public function cancel(){

        $id = $this->request->param('id',0,'intval');

        $userId   = $this->getUserId();
        $OrderService = new OrderService;
        $data = $OrderService->orderInfo($id,$userId);
        if($data['status_id'] == 1){
            Db::name('order')->where('order_id',$id)->update(['order_status'=>4]);

            $this->success('取消成功！');
        }else{
            $this->error('订单不能取消');
        }

    }

    /**
     * 申请退款
     */
    public function refund(){
        $action_note = $this->request->param('action_note');
        $id = $this->request->param('order_id',0,'intval');
        $photo = $this->request->param('photo');
        // if(empty($photo)){
        //     $this->error('请上传退款凭证！');
        // }
        if(empty($action_note)){
            $this->error('请填写退款说明！');
        }
        $userId   = $this->getUserId();
        $OrderService = new OrderService;
        $data = $OrderService->orderInfo($id,$userId);
        

        if($data['is_tui'] == 1){
            
            if($data['shipping_status'] == 1){
                if(empty($photo))$this->error('请上传退款凭证！');
            }
            $refund_fee = $data['order_amount'] - $data['shipping_price'];
            $data = [
                'order_sn'  => $data['order_sn'],
                'parent_sn' => $data['parent_sn'],
                'order_id'  => $id,
                'user_id'   => $userId,
                'order_amount' => $data['order_amount'],
                'shipping_fee' => $data['shipping_price'],
                'refund_fee' => $refund_fee,
                'add_time'  => time(),
                'photo'     => $photo,
                'mobile'    => $data['mobile'],
                'consignee' => $data['consignee'],
                'supplier_id' => $data['supplier_id'],
                'status'    => 0,
                'user_note' => $action_note
            ];
            Db::name('delivery_order')->insert($data);
            Db::name('order')->where('order_id',$id)->update(['order_status'=>3]);
            logOrder($id,'申请退款','refund',$userId);
            
            
            $this->success('提交成功！');
        }else{
            $this->error('订单不能退款,请联系客服解决');
        }
    }
    /**
     * 退款详情
     */
    public function refundOrder(){
        $id = $this->request->param('id',0,'intval');
        $userId   = $this->getUserId();
        $delivery_order =Db::name('delivery_order')->where('user_id',$userId)->where('order_id',$id)->find();

        if($delivery_order){
            $delivery_order['photo'] = $delivery_order['photo1'] = explode(',', $delivery_order['photo']);
            foreach ($delivery_order['photo1'] as $key => $value) {
                $delivery_order['photo1'][$key] = cmf_get_image_url($value);
            }
            if($delivery_order['status'] == 1){
                $delivery_order['refun_status'] = '退款成功！';
            }elseif($delivery_order['status'] == 2){
                $delivery_order['refun_status'] = '已驳回！';
            }else{
                $delivery_order['refun_status'] = '待处理';
            }
            $this->success('ok',$delivery_order);
        }
    }
    
    public function addComment(){
        $params   = $this->request->param();
        $order_id = $this->request->param('id',0,'intval');
        $user_id  = $this->getUserId();

        $order = Db::name('order')->field('order_id,supplier_id,is_comment')->where(['order_id'=>$order_id,'user_id'=>$user_id])->find();
        if($order){
            if($order['is_comment'] == 1){
                $this->error('订单已评论，不能重复评论！');
            }
            if(empty($params['content'])){
                $this->error('请填写评论内容！');
            }
            $photo = empty($params['photo'])?'':implode(',',$params['photo']);
            $goods_id = Db::name('order_sub')->where('order_id',$order_id)->value('goods_id');
            $data = [
                'img'         => $photo,
                'goods_rank'  => $params['goods_rank'],
                'supplier_id' => $order['supplier_id'],
                'add_time'    => time(),
                'order_id'    => $order_id,
                'content'     => $params['content'],
                'user_id'     => $user_id,
                'goods_id'    => $goods_id
            ];
            

            $res = Db::name('goods_comment')->insert($data);
            if($res){
                
                Db::name('order')->where(['order_id'=>$order_id,'user_id'=>$user_id])->update(['is_comment'=>1]);

                $this->success('评论成功');

            }else{

                $this->error('评论失败，请稍后再试');
            }
        }else{
            $this->error('订单不存在！');
        }

    }
    /**
     * 智能接口
     */
    public function shipping(){

        $order_id = $this->request->param('id',0,'intval');
        $user_id  = $this->getUserId();

        $order = Db::name('order')->field('shipping_code,shipping_name,delivery_no,shipping_status')->where(['order_id'=>$order_id,'user_id'=>$user_id])->find();


        if($order['shipping_status'] == 1 || $order['shipping_status'] == 2){
            $key      = 'oMXNlTSz4515';                      //客户授权key
            $customer = 'F6837206AABBFD0222F795C589940F3F';                 //查询公司编号
            $order['delivery_no'] = trim($order['delivery_no']);
            $param = array (
                'com' => $order['shipping_code'],           //快递公司编码
                'num' => $order['delivery_no'],   //快递单号
            );

            //请求参数
            $post_data = array();
            $post_data["customer"] = $customer;
            $post_data["param"] = json_encode($param);
            $sign               = md5($post_data["param"].$key.$post_data["customer"]);
            $post_data["sign"]  = strtoupper($sign);

            $url = 'http://poll.kuaidi100.com/poll/query.do';   //实时查询请求地址

            $params = "";
            foreach ($post_data as $k=>$v) {
                $params .= "$k=".urlencode($v)."&";     //默认UTF-8编码格式
            }
            $post_data = substr($params, 0, -1);

            //发送post请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $data = str_replace("\"", '"', $result );

            $data = json_decode($data,true);
            
            //追加快递名称
            //$data['shipping_name'] = $order['shipping_name'];

            $this->success('ok',$data);
        }else{
            $this->error('订单无法查询');
        }

    }

}
