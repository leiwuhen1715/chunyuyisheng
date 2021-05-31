<?php

namespace api\user\model;
use think\Db;
use think\Model;

/**
 * 用户积分记录表
 * Class UserPointLog
 * @package app\common\model
 */
class UserSignModel extends Model
{
    const POINT_TYPE_SIGN   = 1; //签到
    const SIGN_FIXED_POINT  = 1; //签到固定积分
    const SIGN_RANDOM_POINT = 2; //签到随机积分
    const sign_point_type   = 1;  //签到类型，固定奖励
    const first_sign_point  = 10;    //首次10积分
    const continuity_sign_additional = 0;   //连续签到每日递增5
    const sign_most_point   = 111;    //每日最大111
    const sign_random_min   = 3;      //随机每日最少
    const sign_random_max   = 10 ;     //随机每日最多
    const sign_qi_max       = 100 ;     //7天奖励

    /**
     * 签到
     * @param $user_id
     * @return array
     */
    public function sign($user_id)
    {
        $return = [
            'status' => 0,
            'msg' => '',
            'data' => 0
        ];

        //判断是否已经签到
        $res = $this->isSign($user_id);
        if($res['status'])
        {
            $return['msg'] = '今天已经签到，无需重复签到';
            return $return;
        }

        //获取店铺签到积分设置
        /*$sign_point_type = self::sign_point_type; //签到积分奖励类型

        //判断是固定积分计算还是随机积分计算
        if($sign_point_type == self::SIGN_RANDOM_POINT)
        {
            //随机计算
            $point = $this->signRandomPointCalculation();
        }
        else
        {
            //固定计算
            $point = $this->signFixedPointCalculation($user_id);
        }*/
        
        //$point = $this->signFixedPointCalculation($user_id);
        
        $point = self::first_sign_point;
   
        //插入数据库
        $remarks = '积分签到，获得'.$point.'个积分';
        //插入记录
        $data = [
            'user_id' => $user_id,
            'type'    => self::POINT_TYPE_SIGN,
            'num'     => $point,
            'remarks' => $remarks,
            'ctime' => time()
        ];
        $this->insert($data);
        log_score_change($user_id, $remarks,$point,'sign');
        Db::name('user')->where('id',$user_id)->setInc('sign_day');

        $return['status'] = 1;
        $return['msg']    = $remarks;
        return $return;
    }


    /**
     * 判断今天是否签到
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isSign($user_id)
    {
        $return = [
            'status' => false,
            'msg' => '今天还没有签到'
        ];

        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where=[
            'user_id' => $user_id,
            'type'    => self::POINT_TYPE_SIGN
        ];

        //兼容问题
        $day = $this->where($where)->where('ctime','BETWEEN',[$beginToday,$endToday])
            ->find();

        if($day)
        {
            $return['status'] = true;
            $return['msg'] = '今天已经签到了';
        }

        $this->checkDay($user_id);

        return $return;
    }
    protected function checkDay($user_id){
        $old = $this->where('user_id',$user_id)->order('id','desc')->find();
        $beginYesterday = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $endYesterday   = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $time=time();
        if($old){
            if($old['ctime'] >= $beginYesterday){
                $a=1;
            }else{
                Db::name('user')->where('id',$user_id)->update(['sign_day'=>0]);
            }
        }

    }

    /**
     * 签到随机积分计算
     * @return float|int
     */
    protected function signRandomPointCalculation()
    {
        $sign_random_min = self::sign_random_min; //最小随机
        $sign_random_max = self::sign_random_max; //最大随机
        $point = mt_rand($sign_random_min, $sign_random_max); //随机积分
        //$point = $this->signAppointDatePointCalculation( $point); //判断计算指定日期
        return $point;
    }


    /**
     * 签到指定积分计算
     * @param $user_id
     * @return array|float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function signFixedPointCalculation($user_id)
    {
        $first_sign_point           = self::first_sign_point; //首次签到积分
        $continuity_sign_additional = self::continuity_sign_additional; //连续签到追加
        $sign_most_point            = self::sign_most_point; //签到最多积分
        $sign_qi_max                = self::sign_qi_max; //连续签到7天奖励

        $sign_day=Db::name('user')->where('id',$user_id)->value('sign_day');
        /*$max_continuity_day = intval($sign_day+1);
        //积分
        $point   = $first_sign_point;
        $is_qian = intval($max_continuity_day%7);

        if($is_qian==0){

            $point = $point + $sign_qi_max;
        }*/
        $point = ($point > $sign_most_point) ? $sign_most_point : $point;
        //$point = $this->signAppointDatePointCalculation($point); //判断计算指定日期
        return $point;
    }


    /**
     * 指定日期签到积分计算
     * @param $old_point
     * @return float|int|mixed
     */
    protected function signAppointDatePointCalculation($old_point)
    {
        $sign_appoint_date_status = '';//getShopSetting( 'sign_appoint_date_status'); //指定日期
        $nowDate = date('Y-m-d', time());
        if($sign_appoint_date_status)
        {
            //开启指定日期
            $sign_appoint_date = '';//getShopSetting('sign_appoint_date'); //特殊指定日期
            $sign_appoint_date = json_decode($sign_appoint_date, true);
            if(in_array($nowDate, $sign_appoint_date))
            {
                //当前是指定日期
                $sign_appoint_data_type = '';//getShopSetting('sign_appoint_data_type'); //特殊指定日期奖励类型
                if($sign_appoint_data_type == self::SIGN_APPOINT_DATE_RATE)
                {
                    //倍率
                    $sign_appoint_date_rate = 2;//getShopSetting('sign_appoint_date_rate'); //特殊指定日期倍数
                    $point = $old_point * $sign_appoint_date_rate;
                }
                else
                {
                    //追加
                    $sign_appoint_date_additional = 1;//getShopSetting( 'sign_appoint_date_additional'); //特殊指定日期追加数量
                    $point = $old_point + $sign_appoint_date_additional;
                }
            }
            else
            {
                //不是指定日期
                $point = $old_point;
            }
        }
        else
        {
            //没有开启指定日期
            $point = $old_point;
        }
        return $point;
    }


    /**
     * 获取积分记录
     * @param $user_id
     * @param bool $type
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pointLogList($user_id, $type = false, $page = 1, $limit = 20)
    {
        $return = [
            'status' => false,
            'msg' => '获取失败',
            'data' => [],
            'total' => 0,
            'count' => 0
        ];
        $where=[];
        if($type)
        {
            $where['type'] = $type;
        }
        $where['user_id'] = $user_id;

        $res = $this->field('id, type, num, balance, remarks, ctime')
            ->where($where)
            ->order('ctime', 'desc')
            ->page($page, $limit)
            ->select();

        $count = $this->where($where)->count();

        $return['data'] = $res;
        $return['count'] = $count;
        $return['total'] = ceil($count/$limit);
        if($res)
        {
            $return['status'] = true;
            if(count($res)>=1)
            {
                $return['msg'] = '积分记录获取成功';
                foreach($return['data'] as &$v)
                {
                    //$v['type'] = config('params.user_point_log')['type'][$v['type']];
                    $v['type'] = '签到';
                    $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
                }
            }
            else
            {
                $return['msg'] = '暂无积分记录';
            }
        }
        return $return;
    }


    /**
     * 返回layui的table所需要的格式
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;
        $re['sql'] = $this->getLastSql();

        return $re;
    }


    /**
     * @param $post
     * @return mixed
     */
    protected function tableWhere($post)
    {
        $where = [];
        if(isset($post['mobile']) && $post['mobile'] != ""){
            if($user_id = get_user_id($post['mobile'])){
                $where[] = ['user_id', 'eq', $user_id];
            }else{
                $where[] = ['user_id', 'eq', '99999999'];       //如果没有此用户，那么就赋值个数值，让他查不出数据
            }
        }
        if(isset($post['type']) && $post['type'] != ""){
            $where[] = ['type', 'eq', $post['type']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = 'ctime desc';
        return $result;
    }


    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list  //array格式的collection
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach($list as $k => $v) {
            if($v['type']) {
                $list[$k]['type'] = config('params.user_point_log')['type'][$v['type']];
            }
            if($v['user_id']) {
                $list[$k]['user_id'] = get_user_info($v['user_id']);
            }

            if($v['ctime']) {
                $list[$k]['ctime'] = getTime($v['ctime']);
            }
        }
        return $list;
    }


    /**
     * 获取签到信息
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSignInfo($user_id)
    {
        $return = [
            'status' => true,
            'msg' => '获取成功',
            'data' => [
                'isSign' => true, //今日是否已经签到
                'asi' => [], //签到的日期
                'total' => 0, //累计签到
                'continuous' => 0, //连续签到
                'next' => 0, //下次签到奖励积分
                'rule' => [] //签到规则
            ]
        ];

        $where=[
            'user_id'=>$user_id,
           'type'   =>self::POINT_TYPE_SIGN
        ];
        $date = $this->field('DATE_FORMAT(FROM_UNIXTIME(ctime),"%Y-%m-%d") as `date`')
            ->where($where)
            ->group('DATE_FORMAT(FROM_UNIXTIME(ctime), "%Y-%m-%d")')
            ->select()->toArray();

        $asi = [];
        $total = 0;
        $isSign = false;
        if($date !== false)
        {
            foreach($date as $k => $v)
            {
                $_date = explode("-", $v['date']);
                array_push($asi, $_date);
                $total++;
                if($v['date'] == date('Y-m-d', time()))
                {
                    $isSign = true;
                }
            }
        }

        $this->checkDay($user_id);
        $sign_day=Db::name('user')->field('sign_day')->where('id',$user_id)->find();
        $total = $sign_day['sign_day'];
        $fasi = array_reverse($asi);
        $continuous = $this->continuousSignCalculation($fasi);
        $next = $this->nextSignCalculation($fasi);
        $rule = $this->getSignRule();

        $return['data']['isSign'] = $isSign;
        $return['data']['asi'] = $asi;
        $return['data']['total'] = $total;
        $return['data']['continuous'] = $continuous;
        $return['data']['next'] = $next;
        $return['data']['rule'] = $rule;
        return $return;
    }


    /**
     * 连续签到计算
     * @param $fasi
     * @return int
     */
    public function continuousSignCalculation($fasi)
    {
        //todo::连续签到时长计算
        return 0;
    }


    /**
     * 下一次签到积分计算
     * @return int
     */
    public function nextSignCalculation($fasi)
    {
        //下一次签到奖励积分计算（包括今天没签到或今天已签到）
        return 0;
    }


    /**
     * 获取签到规则
     * @return array
     */
    public function getSignRule()
    {

        $sign_point_type = self::sign_point_type;
        $first_sign_point = self::first_sign_point;
        $continuity_sign_additional = self::continuity_sign_additional;
        $sign_most_point = self::sign_most_point;
        $sign_random_min = self::sign_random_min;
        $sign_random_max = self::sign_random_max;
        $sign_qi_max = self::sign_qi_max;

        /*$rule[] = '下单时'.$point_discounted_proportion.'积分可抵扣1元人民币。';
        $rule[] = '下单使用积分抵扣时，最多可以抵扣订单额的'.$orders_point_proportion.'%。';
        $rule[] = '订单额每满'.$orders_reward_proportion.'元，奖励1积分。';*/
        if($sign_point_type == self::SIGN_FIXED_POINT)
        {
            //固定积分奖励
            $rule[] = '每日签到赢'.$first_sign_point.'积分。';
            $rule[] = '连续签到每满7天，奖励'.$sign_qi_max.'积分。';
        }
        else
        {
            //随机积分奖励
            $rule[] = '每日随机签到奖励积分，最少'.$sign_random_min.'积分，最多'.$sign_random_max.'积分。';
        }

        return $rule;
    }
}
