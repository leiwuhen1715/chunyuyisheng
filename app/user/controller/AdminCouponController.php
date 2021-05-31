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
use think\Db;


class AdminCouponController extends AdminBaseController
{

    /**
     * 后台本站用户列表
     * @adminMenu(
     *     'name'   => '本站用户',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $content = hook_one('user_admin_index_view');

        if (!empty($content)) {
            return $content;
        }
        $where   = [];
        $request = input('request.');

        if (isset($request['is_use']) && $request['is_use'] != '') {
            $where['c.is_use'] = intval($request['is_use']);
        }

        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['u.user_login|u.user_nickname|u.user_email|u.mobile']    = ['like', "%$keyword%"];
        }
        $join=[
            ['user u','u.id = c.user_id','left']
        ];

        $list = Db::name('coupon')->alias('c')->field('c.*,u.user_nickname')->join($join)->where($where)->whereOr($keywordComplex)->order("id DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();


    }



    public function reviewe()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $one=Db::name("coupon")->where("id" ,$id)->find();
            if($one['is_use']==1){
                $this->error("请勿重复使用！");
            }else{
                $result = Db::name("coupon")->where(["id" => $id])->update(['is_use'=>1,'use_time'=>time()]);
                $this->success("使用成功！");
            }

        } else {
            $this->error('数据传入失败！');
        }
    }

    public function couponType(){
        $list = Db::name('coupon_type')->order("id DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }
    
    //给用户添加优惠卷
    public function addUserCoupon(){
        if(request()->isPost()){
            $user_id = request()->param('user_id');
            $id      = request()->param('id');
            if(empty($user_id))     $this->error('请选择用户');
            $coupon_type = Db::name('coupon_type')->where('id',$id)->find();
            if(!$coupon_type)       $this->error('优惠券不存在');
            $user = Db::name('user')->where('id',$id)->value('id');
            if(!$user)   $this->error('用户不存在');
            
            $end_time = time()+$coupon_type['days']*24*3600;
            $insert_data = [
                'user_id'       => $user_id,
                'amount'        => $coupon_type['amount'],
                'total_amount'  => $coupon_type['total_amount'],
                'add_time'      => time(),
                'start_time'    => time(),
                'end_time'      => $end_time,
                'name'          => $coupon_type['name'],
                'remark'        => $coupon_type['remark'],
                'type'          => $coupon_type['type']
            ];
            Db::name('coupon')->insert($insert_data);
            $this->success('添加成功');
        }
        $list = Db::name('coupon_type')->order("id","DESC")->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function couponEdit(){
        $id   = request()->param('id',0,'intval');
        if (request()->ispost()) {
            $param = request()->param();
            $update = [
                'name'      => $param['name'],
                'remark'    => $param['remark'],
                'amount'  => $param['amount'],
                'total_amount'  => $param['total_amount'],
                'days'      => $param['days']
            ];
            Db::name('coupon_type')->where('id',$id)->update($update);
            $this->success('编辑成功！');
        }
        $data = Db::name('coupon_type')->where('id',$id)->find();

        $this->assign('data', $data);

        // 渲染模板输出
        return $this->fetch();
    }


}
