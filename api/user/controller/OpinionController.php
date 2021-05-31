<?php
/**
 * OpinionController.php
 * 文件描述: 用户反馈
 * Created on 2021/4/22 11:11
 * Create  by peipei.song
 */

namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use think\Db;
use think\Validate;

class OpinionController extends RestUserBaseController
{
    public function addOpinion()
    {
        $type    = $this->request->param('type_id', '', 'intval');
        $content = $this->request->param('content', '', 'htmlspecialchars');
        $user_id = $this->userId;
        $device_type = $this->deviceType;

        $validate = new Validate([
            'type'    => 'require|in:1,2,3',
            'content' => 'require',
        ]);

        $validate->message([
            'type.require' => '请选择反馈类型',
            'type.in'      => '非法请求',
            'content'      => '请输入反馈内容',
        ]);

        $data = [
            'type'        => $type,
            'content'     => $content,
            'device_type' => $device_type,
            'user_id'     => $user_id,
            'create_time' => time()
        ];

        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $res = Db::name('user_opinion')->insert($data);

        if ($res) {
            $this->success('感谢你的宝贵意见');
        } else {
            $this->error('error');
        }
    }

}