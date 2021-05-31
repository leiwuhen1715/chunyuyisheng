<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\doctor\controller;

use think\Db;
use think\Validate;
use think\facade\Log;
use api\user\service\WxPayService;
use api\user\service\AliPayService;
use cmf\controller\RestBaseController;

class NotifyController extends RestBaseController
{
    //医生回复
    public function doctor_reply(){
        $param = request()->param();
        
        Log::write($param,'医生回复');
        $status = request()->param('status');
        $problem_id = request()->param('problem_id');
        if($status == 'reply'){
            //医生回复
            $left_interactions = request()->param('left_interactions');
            $doctor     = request()->param('doctor');
            $user_id    = request()->param('user_id');
            $user_id    = str_replace('cy_','',$user_id);
            
            $where = [
                //['user_id','=',$user_id],
                ['problem_id','=',$problem_id]
            ];
            $order = Db::name('doctor_order')->where($where)->order('id','desc')->find();
            
            if(empty($order['doctor_id'])){
                if(!empty($doctor['id'])){
                    Db::name('doctor_order')->where('id',$order['id'])->update(['doctor_id'=>$doctor['id'],'doctor_name'=>$doctor['name'],'doctor_img'=>$doctor['image']]);
                }
                
            }
            Db::name('doctor_order')->where('id',$order['id'])->data(['left_interactions'=>$left_interactions])->update();
            
            $problem_arr = json_decode($param['content'],true);
            foreach($problem_arr as $vo){
                $content = $vo['type'] == 'text'?$vo['text']:$vo['file'];
                Db::name('chat_record')->insert([
        			'order_id'			=> $order['id'],
        			'user_id' 		    => $order['user_id'],
        			'send_user_id'	    => $doctor['id'],
        			'receive_user_id'	=> $doctor['id'],
        			'type'              => $vo['type'],
        			'add_time'			=> time(),
        			'msg'				=> $content
        		]);
            }
            $registration_id = Db::name('user')->where('id',$order['user_id'])->value('registration_id');
            if($registration_id){
                $param = [
                    'type'              => 1,
                    'registration_id'   => $registration_id,
                    'msg'               => $doctor['name'].'医生给您回复，请及时查看'
                ];
                hook_one('jpush_send',$param);
            }
        }elseif($status == 'close'){
            
        }
        
        
        
    }
    //服务关闭通知
    public function problem_close(){
        $param = request()->param();
        Log::write($param,'服务关闭通知');
        $status = request()->param('status');
        if($status == 'close'){
            //正常关闭
        }elseif($status == 'refund'){
            //退款
        }elseif($status == 'phone_close'){
            //电话正常关闭
        }elseif($status == 'phone_refund'){
            //电话退款
        }
        //'phone_close'为电话正常关闭, 'phone_refund'为电话退款
    }
    


}
