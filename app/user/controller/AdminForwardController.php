<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use EasyWeChat\Factory;
use think\Db;


class AdminForwardController extends AdminBaseController
{


    public function index()
    {
        $param = request()->param();
        $type  = request()->param('type',0,'intval');
        $where   = ['type'=>$type];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['u.id'] = intval($request['uid']);
        }
       if (isset($request['status']) && $request['status'] != '') {
            $where['r.status'] = intval($request['status']);
        }
        $join = [
            ['__USER__ u', 'r.user_id = u.id','left']
        ];
        $field = 'r.*,u.user_login,u.mobile,u.user_nickname,u.user_email';
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['u.user_nickname|r.real_name|u.mobile']    = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('recharge');

        $list = $usersQuery->alias('r')->field($field)->join($join)->where($where)->where($keywordComplex)->order("r.id DESC")->paginate(10);
        
        // 获取分页显示
        $list->appends($param);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $join = [
            ['__USER__ u', 'r.user_id = u.id','left']
        ];
        $recharge = Db::name('recharge');
        $post            = $recharge->alias('r')->field('r.*,u.user_login,u.mobile,u.user_nickname,u.user_email,u.card_on')->join($join)->where(['r.id'=>$id])->where('r.type','in',[0,2])->find();

        $this->assign('post', $post);

        return $this->fetch();
    }

    /**
     * 
     */
    public function editPost()
    {

        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $status = $this->request->param('status', 0, 'intval');

            $transStatu=0;
            Db::startTrans(); //开启事务
            try {
                $recharge=DB::name('recharge')->field('status,user_id,addtime,type,money,baoshi')->where(['id'=>$id])->where('type','in',[0,2])->find();
                if($recharge){
                    if($recharge['status']==0){
                        if($status==1){

                            /*$forward = Db::name('pay_log')->where(['order_id'=>$id,'type'=>4])->find();
                            if($forward){
                                $order_id = $forward['id'];
                            }else{
                                $order_sn = get_partner_order_sn();
                                $data = [
                                    'order_sn'      => $order_sn,
                                    'order_id'      => $id,
                                    'amount'        => $recharge['money'],
                                    'is_paid'       => 0,
                                    'user_id'       => $recharge['user_id'],
                                    'add_time'      => time()
                                ];
                                $order_id = Db::name('pay_log')->insertGetId($data);
                            }
                            $pay_result = toBalance($order_id);

                            if($pay_result['status'] == 1){
                                DB::name('recharge')->where("id",$id)->update(['status'=>1,'handle_time'=>time()]);
                                $transStatu=1;
                            }else{
                                $transStatu=3;
                            }*/
                            DB::name('recharge')->where("id",$id)->update(['status'=>1,'handle_time'=>time()]);
                            $config = config('EASY_WECHAT');
                            $app = Factory::officialAccount($config);
                            $openid = Db::name('third_party_user')->where('user_id',$recharge['user_id'])->order('create_time','desc')->value('openid');
                            if(!empty($openid)){
                                $res = $app->template_message->send([
                    	            'touser' => $openid,
                    	            'template_id' => '4WVyi9ypHZqn4Pzmsz31TSi_DPF9ttPtu9FlzHSjnuM',
                    	            'data' => [
                    	                'first'    => '提现到账通知',
                    	                'keyword1' => $recharge['money'],
                    	                'keyword2' => date('Y-m-d H:i',$recharge['addtime']),
                    	                'keyword3' => '提现银行卡',
                    	                'remark'   => '您的提现已到账，请及时查看'
                    	            ]
                    	        ]);
                            }
                    
                            $transStatu=1;

                            Db::commit();

                        }elseif($status==2){
                            if($recharge['type'] == 0){
                                $res = log_balance_change($recharge['user_id'], '提现驳回',$recharge['baoshi'],0,1);
                                if($res['status']!=1)throw new \Exception($res['err']);
                            }else{
                               $res = log_money_change($recharge['user_id'], '提现驳回',$recharge['money']);
                                if($res['status']!=1)throw new \Exception($res['err']); 
                            }
                            
                            
                            DB::name('recharge')->where("id",$id)->update(['status'=>2]);
                            $transStatu=1;
                            Db::commit();
                            
                        }else{
                            $transStatu=2;
                        }

                    }else{

                        throw new \Exception('提现已处理！');
                    }

                }else{
                    throw new \Exception('提现不存在！');
                }


            } catch (\Exception $e) {
                $Message= $e->getMessage();
                // 回滚事务
                Db::rollback();
            }
            if($transStatu==1){
                $this->success('处理成功！');
            }elseif($transStatu==2){
                $this->error('未处理！');
            }elseif($transStatu == 3){
                if($pay_result['status'] == 2){
                	DB::name('recharge')->where("id",$id)->update(['status'=>1]);
                    $this->error('已提现过');
                }else{
                    $this->error($pay_result['msg']);
                }

            }else{
                $this->error($Message);
            }

        }
    }
    
    public function cenck_pay(){
        //->where(['status'=>0])
        /*$where   = [
            'status'    =>0,
            'type'      => ['in',[0,2]],
        ];
        $whereor = [
            'status'        => 1,
            'type'          => ['in',[0,2]],
            'send_status'   => ['in',[0,2,4]]
        ];*/
        $where = "(status=0 and type in (0,2)) or (status=1 and type in (0,2) and send_status in (0,2,4))";
        $list = Db::name('recharge')->field('id')->field('order_sn,money,addtime,id,status,user_id')->where($where)->order('id','desc')->select();
        
        $nums = $wait_num = 0;
        $config = config('EASY_WECHAT');
        $app = Factory::officialAccount($config);
        foreach($list as $key=>$vo){
            $res = cenck_user_pay($vo['id']);
            
            if($res){
                if($res == 1){
                    $nums += 1;
                    $openid = Db::name('third_party_user')->where('user_id',$vo['user_id'])->order('create_time','desc')->value('openid');
                    if(!empty($openid)){
                        $res = $app->template_message->send([
            	            'touser' => $openid,
            	            'template_id' => '4WVyi9ypHZqn4Pzmsz31TSi_DPF9ttPtu9FlzHSjnuM',
            	            'data' => [
            	                'first'    => '提现到账通知',
            	                'keyword1' => $vo['money'],
            	                'keyword2' => date('Y-m-d H:i',$vo['addtime']),
            	                'keyword3' => '提现银行卡',
            	                'remark'   => '您的提现已到账，请及时查看'
            	            ]
            	        ]);
                    }
                    Db::name('recharge')->where('id',$vo['id'])->update(['status'=>1]);
                }elseif($res == 2){
                    $wait_num += 1;
                    
                    
                }
                
    			
            }
        }
        $this->success('成功处理'.$nums.'提现,'.$wait_num.'处理中订单');
    }
    /**
	 * 导出excel
	 */
	
	public function export(){
	    $request = request()->param();
	    $request['sub']=!empty($request['sub'])?$request['sub']:'';
	    $type  = request()->param('type',0,'intval');
	    $name = '';
	    if($type == 0)$name='宝石';
	    if($type == 2)$name='余额';
		$name .= '提现信息';
		
		$header=['姓名','身份证号','收款账号','开户支行','手机号','打款金额','订单号'];
		$startTime = empty($request['start_time']) ? 0 : strtotime($request['start_time']);
		$endTime   = empty($request['end_time']) ? 0 : strtotime($request['end_time']);
	
		
		
        $where   = ['r.type'=>$type];
		$whereor = '';
		if(!empty($startTime) && !empty($endTime)){
			$whereor = "r.addtime >= $startTime and r.addtime <= $endTime";
		}elseif(!empty($startTime)){
			$where['r.addtime'] = ['>=',$startTime];
	
		}elseif(!empty($endTime)){
			$where['r.addtime'] = ['<=',$endTime];
		}
		$join = [
		    ['user u','r.user_id = u.id','left']   
		];
	
		$list = Db::name('recharge')->alias('r')->field('r.real_name,u.card_on,u.mobile,r.bank_name,r.branch_name,r.bank_on,r.status,r.addtime,r.money,r.order_sn')->join($join)->where(['r.status'=>0,'r.send_status'=>0])->where($where)->where($whereor)->order('r.id','desc')->select();
		$newdata = [];
		foreach($list as $key=>$vo){
			$status = '';
			
			switch ($vo['status']) {
				case 1:
				    // code...
				    $status = '未处理';
				    break;
			    case 1:
			        // code...
			        $status = '已处理';
			        break;
			
			    case 2:
			        // code...
			        $status = '已驳回';
			        break;
			}
			
			$newdata[$key] = [
				'real_name' 	=> $vo['real_name'],
				'card_on' 	    => $vo['card_on'],
				'bank_on'	    => $vo['bank_on'],
				'branch_name'   => $vo['bank_name'].$vo['branch_name'],
				'mobile'	    => $vo['mobile'],
				'money'         => $vo['money'],
				'order_sn'      => $vo['order_sn']
			];
		}
		excelBrowserExport($name,$header,$newdata);
	    
	}

}
