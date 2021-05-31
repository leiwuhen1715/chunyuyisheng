<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use think\Db;
use api\user\model\UserSignModel;
use cmf\controller\RestUserBaseController;

class SignController extends RestUserBaseController
{
    //签到
    public function sign()
    {

        $user_id   = $this->getUserId();

        Db::startTrans(); //开启事务
        try {

            $model = new UserSignModel();
            $res   = $model->sign($user_id);
            Db::commit();

        } catch (\Exception $e) {

            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($res['status']) {
            $this->success('ok!', $res['data']);
        } else {
            $this->error($res['status']);
        }
    }

    //判断今日签到
    public function isSign(){
        $user_id = $this->getUserId();
        $model   = new UserSignModel();
        $res     = $model->isSign($user_id);
        if ($res['status']) {
            $this->success('ok!', $res['msg']);
        } else {
            $this->error($res['status']);
        }
    }

    //签到记录
    public function logs(){
        $userId = $this->getUserId();

        $result       = Db::name('user_sign')->where(['user_id' => $userId])->order('ctime desc')->paginate();
        $list = $result->items();
        foreach ($list as $key => $value) {

            $list['ctime'] = date('Y-m-d H:i',$value['ctime']);
            
        }
        $this->success('请求成功', $list);
    }

    /**
     * 获取签到信息
     */
    public function SignInfo(){
        $user_id   = $this->getUserId();
        $model     = new UserSignModel();
        $res       = $model->getSignInfo($user_id);
        if ($res['status']) {
            $this->success('ok!', $res['data']);
        } else {
            $this->error($res['status']);
        }
    }

}