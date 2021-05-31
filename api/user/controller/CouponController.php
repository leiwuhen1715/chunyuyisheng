<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestBaseController;
use api\user\model\CouponModel;
use think\Db;


class CouponController extends RestBaseController
{

    public function getCoupon(){

        $userId       = $this->userId;
        $type   = $this->request->param('type',0,'intval');
        $time = time();
        $where = ['user_id'=>$userId,'is_use'=>0,'type'=>$type,'end_time'=>['>',$time]];
        $model = new CouponModel();
        
        $res = $model->where($where)->order('end_time','asc')->select();
        $this->success('ok',$res);
        
    }
    /*获取订单选择优惠劵*/
    public function getOrderCoupon(){

        $userId     = $this->getUserId();
        $order_sn   = $this->request->param('order_sn');
        
        
        $list = Db::name('couponType')->where('states',1)->select()->toarray();
        foreach ($list as $key=>$vo){
            $start_time = time();
            $end_time   = $start_time + 7*3600*$vo['days'];
            $list[$key]['start_tie'] = date('Y-m-d H:i',$start_time);
            $list[$key]['end_time'] = date('Y-m-d H:i',$end_time);
        }
        
        
        $this->success('ok',$list);
        
    }
    //添加优惠劵
    public function addCoupon(){
        
        
        
        $userId     = $this->getUserId();
        $order_sn   = $this->request->param('order_sn');
        $id         = $this->request->param('id',0,'intval');
        $couponType = Db::name('couponType')->where(['id'=>$id,'states'=>1])->find();
        if($couponType){
            
            $where = ['order_sn'=>$order_sn,'user_id'=>$userId,'type'=>1];
            $order = Db::name('user_card_open')->field('is_coupon,pay_status')->where($where)->find();
            if($order['pay_status'] == 1 && $order['is_coupon'] == 0 ){
                $type  = $couponType['type'];
                $name  = $couponType['name'];
                $start_time = time();
                $end_time   = $start_time + 24*3600*$couponType['days'];
                $data = [
                    'add_time'   => time(),
                    'user_id'    => $userId,
                    'start_time' => $start_time,
                    'end_time'   => $end_time,
                    'type'       => $couponType['type'],
                    'amount'     => 0,
                    'discount'   => $couponType['discount'],
                    'name'       => $couponType['name'],
                    'remark'     => $couponType['remark']
                ];
                Db::name('coupon')->insert($data);
                
                Db::name('user_card_open')->where($where)->update(['is_coupon'=>1]);
                $this->success('领取成功！');
            }else{
                $this->error('不可以领取！');
            }
        }else{
            $this->error('优惠劵不存在！');
        }
    
    }
    /*选择优惠劵*/
    // public function changeCoupon(){
    //     $order_id = $this->request->param('id',0,'intval');
    //     $userId   = $this->getUserId();
    //     $where = ['order_id'=>$order_id,'user_id'=>$userId];
    //     $order = Db::name('order')->field('is_coupon,pay_status')->where($where)->find();
    //     $amount = rand(1,20);
    //     if($order['pay_status'] == 1 && $order['is_coupon'] == 0 ){
    //         $start_time = time();
    //         $end_time   = $start_time + 24*3600*3;
    //         $data = [
    //             'add_time'   => time(),
    //             'user_id'    => $userId,
    //             'start_time' => $start_time,
    //             'end_time'   => $end_time,
    //             'amount'     => $amount/10
    //         ];
    //         Db::name('coupon')->insert($data);
    //         Db::name('order')->where($where)->update(['is_coupon'=>1]);
    //         $this->success('ok',$data);
    //     }else{
    //         $this->error('不存在');
    //     }
    // }
    //优惠劵列表
    public function lists(){
        $type = $this->request->param('type',0,'intval');
        $where = [];
        $time = time();
        switch ($type) {
            case 0:
                $where['is_use'] = 0;
                $where['end_time'] = ['>',$time];
                break;
            case 1:
                $where['is_use'] = 0;
                $where['end_time'] = ['<',$time];
                break;
            case 2:
                $where['is_use'] = 1;
                break;
        }
        if($type == 1){
            
        }
        $userId   = $this->getUserId();
        $model = new CouponModel();
        $data=$model->where('user_id',$userId)->where($where)->order('id','desc')->paginate(10);
        
        $datas = $data->items();
        $this->success('获取成功!', $datas);
    }
    
}
