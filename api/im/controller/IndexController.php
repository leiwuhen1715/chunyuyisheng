<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\im\controller;

use cmf\controller\RestBaseController;
use api\user\service\SpringService;
use think\swoole\Server;
use think\db;

class IndexController extends Server
{
    protected $host = '0.0.0.0'; //监听所有地址
    protected $port = 9501; //监听9501端口
    protected $serverType = 'socket';
    //开启wss
    //protected $sockType = SWOOLE_SOCK_TCP | SWOOLE_SSL;
    protected $option = [
        //'ssl_cert_file' => '/www/wwwroot/server.crt',
        //'ssl_key_file' => '/www/wwwroot/server.key',
        'worker_num' => 4, //设置启动的Worker进程数
        'daemonize' => false,//守护进程化（上线改为true）
        'max_request' => 10000,
        'dispatch_mode' => 2, //固定模式，保证同一个连接发来的数据只会被同一个worker处理
        'debug_mode' => 1,
        //心跳检测：每60秒遍历所有连接，强制关闭10分钟内没有向服务器发送任何数据的连接
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 600
    ];

    //建立连接时回调函数
    function onOpen($server, $req)
    {
        $fd    = $req->fd;//客户端标识
        $token = $req->get['token'];//客户端传递的token
        $id    = $req->get['id'];//客户端传递的token
        $uid   = $this->getUid($token);
        $this->bind($uid, $req->fd,$id);
        echo "用户{$uid}建立了连接,标识为{$fd}\n";
    }

    //接收数据时回调函数
    public function onMessage($server,$frame)
    {
        $fd = $frame->fd;
        $message = json_decode($frame->data);
        
        $id    = $message->id;//客户端传递的token
        $token = $message->token;//客户端传递的token
        $uid   = $this->getUid($token);
        
        $data = [
            'is_send'   => 1,  #1:发送，0：接收
            'add_time'  => date("m/d H:i",time()),
            'msg'       => $message->content
        ];
        $arr = ['status'=>1,'message'=>'success','data'=>$data,'uid'=>$uid];
        $res = $this->add($uid, $id, $message->content); //保存消息
        if($res['status'] == 1){
            $server->push($fd, json_encode($arr));
        }else{
            $arr['status'] = 0;
            $arr['msg']     = $res['msg'];
            $server->push($fd, json_encode($arr));
        }
        
        

    }
    public function add($user_id, $order_id, $msg)
    {
        $result = ['status'=>0,'msg'=>''];
        $order = Db::name('doctor_order')->field('pay_status,problem_id,order_status,pay_status,order_type')->where('id',$order_id)->where('user_id',$user_id)->find();
    	
    	if($order['pay_status'] == 1){
    	    
    		$service = new SpringService($user_id);
    		    
    		if($order['order_status'] == 0 && $order['order_type'] == 1){
    		    $result['msg'] = '待分配医生';
    		}else{
    		    if(empty($order['problem_id'])){
    			
        			$res = $service->create_oriented($order_id,$msg);
        		}else{
        			$res = $service->problem_content($order_id,$msg);
        		}
        		if($res['error'] == 0){
        			$result['status'] = 1;
        		}else{
        			$result['msg'] = $res['error_msg'];
        		}
    		}
	        
    	}else{
    	    $result['msg'] = '订单未付款';
    	}
    	return $result;
        /*$arr = ['fid'=>$fid,'tid'=>$tid,'content'=>$content,'time'=>date("y/m/d H:i",time())];
        Db::name('msg')->insertGetId($arr);*/
    }
    public function getFd($uid,$id)
    {
        //$data = Db::name('fd')->where('uid',$uid)->find();
        $data = cache('uid'.$uid.'_'.$id);
        return $data['fd'];
    }
    
    public function getUid($token)
    {
        $user_id = Db::name('user_token')->where('token',$token)->value('user_id');
        return $user_id;
    }
    public function bind($uid, $fd,$id)
    {
        $value = ['uid'=>$uid,'fd'=>$fd,'id'=>$id];
        cache('uid'.$uid.'_'.$id, $value,1800);
        
        return true;
    }
    //连接关闭时回调函数
    public function onClose($server,$fd)
    {
        
    }

}
