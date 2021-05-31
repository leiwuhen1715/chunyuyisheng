<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;

/**
 *资金明细
 */
class AdminCapitalController extends AdminBaseController
{

    /**
     * 列表
     */
    public function index()
    {
        
        // 渲染模板输出
        return $this->fetch();
    }
    public function ajax(){
        $param = request()->param();
        $limit = request()->param('limit',10,'intval');
        $keyword   = request()->param('keyword');
        $startTime = empty($request['start_time']) ? 0 : strtotime($request['start_time']);
        $endTime   = empty($request['end_time']) ? 0 : strtotime($request['end_time'].' 23:59'); 

        $where = [];
        
        if(!empty($startTime) && !empty($endTime)){
            $where[] = ['c.create_time','between',[$startTime,$endTime]];
        }else{
            if(!empty($startTime)) $where[] = ['c.create_time','>',$startTime];
            if(!empty($endTime))   $where[] = ['c.create_time','<',$endTime];
        }
        if($keyword) $where[] = ['u.user_nickname|u.mobile','like','%'.$keyword.'%'];
        

        $join = [
            ['user u','c.user_id = u.id','left']
        ];
        $count = Db::name('user_balance_log')->alias('c')->join($join)->where($where)->count();
        $data  = Db::name('user_balance_log')->alias('c')->field('c.*,u.user_nickname,u.mobile')->join($join)->where($where)->order(['id'=>'desc'])->paginate($limit);
        $list = $data->items();
        foreach($list as $key=>$vo){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$vo['create_time']);
        }
        $result = ['code'=>0,'count'=>$count,'data'=>$list];
        die(json_encode($result));
    }


     // 充值数据导出
    public function export(){
        $request = request()->param(); 
        
        $type = request()->param('type',0,'intval');
        $id = request()->param('user_id',0,'intval');
        
        $name='余额明细';

        $header=['用户','头像','变化','剩余','备注','日期'];

        $startTime = empty($request['start_time']) ? 0 : strtotime($request['start_time']);
        $endTime   = empty($request['end_time']) ? 0 : strtotime($request['end_time']); 

        $where = [];
        $times = '';  
        if(!empty($startTime) && !empty($endTime)){
            $times = "c.create_time >= $startTime and c.create_time <= $endTime";
        }elseif(!empty($startTime)){
            $where['c.create_time']=['>=',$startTime];

        }elseif(!empty($endTime)){
            $where['c.create_time']=['<=',$endTime];
        }

        if(!empty($id))$where['user_id'] = $id;

        $join = [
            ['user u','c.user_id = u.id','left']
        ];

        $list = Db::name('user_balance_log')->alias('c')->field('c.*,c.balance as total_num,u.user_nickname,u.realname,u.mobile,u.avatar')->join($join)->where($where)->order("c.id DESC")->select();

      
        $newdata = [];
        foreach($list as $key=>$vo){
             
             if($vo['change'] > 0){
                $change = "+".$vo['change'];
             }else{
                $change = $vo['change'];
             }
            $newdata[$key] = [
                'realname'  => $vo['user_nickname'],
                'avatar'   => $vo['avatar'],
                'change'    => $change, 
                'total_num'    => $vo['total_num'], 
                'description'      => $vo['description'],
                'addtime'   => date('Y-m-d H:i:s',$vo['create_time']),
            ];
        }
         
        excelBrowserExport($name,$header,$newdata);
        
    }

    public function add(){
        if(request()->isPost()){
            $id            = request()->param('id',0,'intval');
            $integral_type = request()->param('integral_type',0,'intval');
            $integral_num  = request()->param('integral_num',0,'intval');
            $remarks       = request()->param('remarks');
            $type = request()->param('type',0,'intval');
            $array=array(1,2);

            $user = Db::name('user')->where('id',$id)->value('id');
            if(empty($user))$this->error('请选择会员');
            if(in_array($integral_type, $array)){
                if(empty($integral_num)){
                    $this->error("请填写资金变动数量！");
                }
                if(empty($remarks)){
                    $this->error("请填写变更原因！");
                }
                $res_status = 0;
                Db::startTrans(); //开启事务
                try {
                    if($integral_type==2){

                        $integral_num=$integral_num*-1;
                        $res = log_balance_change($id, $remarks,$integral_num,0,1);
                    }else{

                        $res = log_balance_change($id, $remarks,$integral_num,0,1);
                    }
                    $res_status = 1;
                    Db::commit();
                } catch (\Exception $e) {
                    $Message= $e->getMessage();
                    // 回滚事务
                    Db::rollback();
                    $this->error($Message);
                }
                $this->success("操作成功！");

            }else{
                $this->error('请选择增加或减少');
            }
        }




        return $this->fetch();
    }

    public function select(){

        $param  = $this->request->param();
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);

        $keywordComplex = [];
        $where = [];
        if (!empty($param['keyword'])) {
            $keyword = $param['keyword'];

            $where['u.user_nickname|u.mobile']    = ['like', "%$keyword%"];
            $this->assign("keyword", $keyword);
        }


        $datas = Db::name('user')->alias('u')->field('u.id,u.user_nickname,u.mobile')->where('id','<>',1)->where($where)->paginate();

        $datas->appends($param);
        $this->assign("datas", $datas);
        $this->assign('page', $datas->render());
        $this->assign('selectedIds', $selectedIds);
        return $this->fetch();
    }

}
