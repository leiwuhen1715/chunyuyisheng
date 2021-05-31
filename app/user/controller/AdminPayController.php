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
use app\user\model\PayLogModel;
use think\Db;
use think\db\Query;

/**
 *资金明细
 */
class AdminPayController extends AdminBaseController
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

        $where = [
            ['c.is_paid','=',1]
        ];
        
        if(!empty($startTime) && !empty($endTime)){
            $where[] = ['c.pay_time','between',[$startTime,$endTime]];
        }else{
            if(!empty($startTime)) $where[] = ['c.pay_time','>',$startTime];
            if(!empty($endTime))   $where[] = ['c.pay_time','<',$endTime];
        }
        if($keyword) $where[] = ['u.user_nickname|u.mobile','like','%'.$keyword.'%'];
        

        $join = [
            ['user u','c.user_id = u.id','left']
        ];
        $count = Db::name('pay_log')->alias('c')->join($join)->where($where)->count();

        $model = new PayLogModel;
        $data  = $model->alias('c')->field('c.*,u.user_nickname,u.mobile')->join($join)->where($where)->order(['id'=>'desc'])->paginate($limit);
        $list = $data->items();
        $result = ['code'=>0,'count'=>$count,'data'=>$list];
        die(json_encode($result));
    }


    
}
