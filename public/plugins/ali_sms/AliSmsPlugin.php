<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2018 http://www.wuwuseo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\ali_sms;

use cmf\lib\Plugin;

class AliSmsPlugin extends Plugin
{
    public $info = array(
        'name'=>'AliSms',
        'title'=>'阿里云通信手机验证码',
        'description'=>'阿里云通信手机验证码',
        'status'=>1,
        'author'=>'',
        'version'=>'1.2'
    );
    
    public $has_admin=0;//插件是否有后台管理界面
    
    public function install()
    {//安装方法必须实现
        return true;//安装成功返回true，失败false
    }
    
    public function uninstall()
    {//卸载方法必须实现
        return true;//卸载成功返回true，失败false
    }
    
    //实现的send_mobile_verification_code钩子方法
    public function sendMobileVerificationCode($param)
    {
        $mobile        = $param['mobile'];//手机号
        $code          = $param['code'];//验证码
        $config        = $this->getConfig();
        $expire_minute = intval($config['expire_minute']);
        $expire_minute = empty($expire_minute) ? 30 : $expire_minute;
        $expire_time   = time() + $expire_minute * 60;
        $result        = false;
        //send message
        if ($code!==false) {
            $params['PhoneNumbers'] = $mobile;
            $params['SignName'] = $config['SignName'];
            $params['TemplateCode'] = $config['TemplateCode'];
            $params['TemplateParam'] = [
                $config['codeKey']=>$code
            ];
            $sms = new \plugins\ali_sms\lib\Sms($config['accessKeyId'], $config['accessKeySecret']);
            $reponse = $sms->sendSms($params);
            if ($reponse) {
                $result = [
                    'error'=>0,
                    'message'=>'发送成功！'
                ];
            } else {
                $result = [
                    'error'=>1,
                    'message'=> $sms->getError()
                ];
            }
        } else {
            $result = [
                'error'=>1,
                'message'=>'发送次数过多，不能再发送'
            ];
        }
        return $result;
    }
}
