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

use api\user\model\UserScoreLogModel;
use cmf\controller\RestUserBaseController;
use think\Db;

class ScoreController extends RestUserBaseController
{

    public function logs()
    {
        $userId            = $this->getUserId();
        $userScoreLogModel = new UserScoreLogModel();

        $logs = $userScoreLogModel->where('user_id', $userId)
            ->where('score', '<>', 0)
            ->order('create_time DESC')
            ->paginate();
            
        $data = Db::name('user')->field('score')->where('id',$userId)->find();    
        
        $res['list'] = $logs->items();
        $res['score'] = $data['score'];

        $this->success('请求成功',$res);
    }

}