<?php
/**
 * Login.php
 * 文件描述: 更新登录信息
 * Created on 2021/4/12 11:30
 * Create  by peipei.song
 */
namespace api\user\behavior;

use think\Db;

class LastInfo
{
    //更新用户最后登录信息
    public function run($param)
    {
        /*
         *last_login_time：最后登录时间
         *last_login_ip:  最后登录ip
         */
        $last_login_time = time();
        $last_login_ip = get_client_ip();
        Db::name('user')->where(['id'=>$param])->update(['last_login_time'=>$last_login_time,'last_login_ip'=>$last_login_ip]);
    }


}