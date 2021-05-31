<?php
/**
 * VersionController.php
 * 文件描述:
 * Created on 2021/4/17 11:58
 * Create  by peipei.song
 */

namespace api\user\controller;

use cmf\controller\RestBaseController;
use cmf\controller\RestUserBaseController;
use think\Db;
use think\facade\Validate;

class VersionController extends RestBaseController
{
    public function checkVersion()
    {
        //客户端的版本号
        $client_version = $this->request->param('version', '0', 'intval');
        
        //客户端类型(android/iphone)
        $client_type = $this->deviceType;

        if (!in_array($client_type, $this->allowedDeviceTypes)) {
            $this->error('设备类型错误');
        }

        if (empty($client_version)) {
            $this->error('版本号不能为空');
        }

        $field = 'client_type,app_type,client_version,server_version,update_note,app_link,is_required';
        //查询版本信息
        $data = Db::name('version')->field($field)->where(['client_type' => $client_type])->find();

        if ($client_version < intval($data['server_version'])) {
            $data['is_update'] = 'true';
            $this->success('ok', $data);
        } else {
            $this->error('当前已是最新版本');
        }
    }


}