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
use app\goods\logic\OrderLogic;
use think\Db;
use think\Validate;

class AddressController extends RestBaseController
{

    public function index(){
        $model = Db::name('PluginModulesCitys');
        $list = $model->field('id as code,name,pid')->where(['pid'=>0,'level'=>1])->select()->toarray();
        foreach ($list as $key => $value) {
            $list[$key]['sub']     = Db::name('PluginModulesCitys')->field('id as code,name,pid')->where('pid',$value['code'])->where('level',2)->select()->toarray();
            foreach ($list[$key]['sub'] as $k => $v) {
                $list[$key]['sub'][$k]['sub']     = Db::name('PluginModulesCitys')->field('id as code,name,pid')->where('pid',$v['code'])->where('level',3)->where('level3','in',[3,4,5])->select();
            }
        }
        $this->success('ok',$list);
    }
    
    public function hot(){
        $list = Db::name('PluginModulesCitys')->field('id as code,name,pid')->where('is_hot',1)->limit(20)->select();
        $this->success('ok',$list);
    }
    /*获取订单结算地址*/
    public function getAddress(){
        $userId   = $this->getUserId();
        $address_id = $this->request->param('id', 0, 'intval');
        if($address_id){
            $address=Db::name('UserAddress')->where(['user_id'=>$userId,'address_id'=>$address_id])->find();
            if($address){

            }else{
                $this->success('地址不存在！');
            }
        }else{
            $address=Db::name('UserAddress')->where(['user_id'=>$userId,'is_default'=>1])->find();
            if($address){

            }else{
                $address=Db::name('UserAddress')->where(['user_id'=>$userId])->find();
                if($address){

                }else{
                    $this->error('请添加地址');
                }
            }
        }
        $OrderLogic = new OrderLogic;
        $address['area'] = $OrderLogic->getAddressName($address['province'],$address['city'],$address['district']);
        $this->success('ok',$address);

    }

    public function getAddressList(){
        $userId   = $this->getUserId();
        $address=Db::name('UserAddress')->where(['user_id'=>$userId])->select()->toarray();
        $OrderLogic = new OrderLogic;

        foreach ($address as $key => $value) {
            $address[$key]['area'] = $OrderLogic->getAddressName($value['province'],$value['city'],$value['district']);
        }
        $this->success('ok',$address);
    }


    public function getAddressInfo(){
        $userId   = $this->getUserId();
        $address_id = $this->request->param('id', 0, 'intval');
        $address=Db::name('UserAddress')->where(['user_id'=>$userId,'address_id'=>$address_id])->find();
        if($address){
            $OrderLogic = new OrderLogic;
            $address['area'] = $OrderLogic->getAddressName($address['province'],$address['city'],$address['district']);

            $this->success('ok',$address);
        }else{
            $this->error('ok',$address);
        }

    }

    public function addAddress(){
        $userId   = $this->getUserId();
        $address_id = $this->request->param('id', 0, 'intval');
        $is_default = $this->request->param('is_default',0, 'intval');
        $param = $this->request->param();
        
        $result          = $this->validate($param, 'Address');
        if ($result !== true) {
            $this->error($result);
        }

        $p_date = [
            'consignee' => $param['consignee'],
            'mobile'    => $param['mobile'],
            'address'   => $param['address'],
            'district'  => $param['district'],
            'province'  => $param['province'],
            'city'      => $param['city'],
            'user_id'   => $userId,
            'is_default'=> $is_default
        ];
        if($address_id){
            Db::name('UserAddress')->where(['user_id'=>$userId,'address_id'=>$address_id])->update($p_date);
        }else{
            $address_id = Db::name('UserAddress')->insertGetId($p_date);
        }
        if($is_default == 1){
            Db::name('UserAddress')->where('user_id',$userId)->where('address_id','<>',$address_id)->update(['is_default'=>0]);
        }

        $this->success('ok',$address_id);

    }

    public function deladdress(){
        $userId   = $this->getUserId();

        $address_id = $this->request->param('id', 0, 'intval');
        $a=Db::name('UserAddress')->where(['user_id'=>$userId,'address_id'=>$address_id])->find();
        if($a){

            Db::name('UserAddress')->where(['user_id'=>$userId,'address_id'=>$address_id])->delete();
            $this->success('OK');
        }else{
            $this->error('地址不存在！');
        }

    }

}
