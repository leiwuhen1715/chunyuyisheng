<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use function PHPSTORM_META\elementType;
use think\Db;
use think\facade\Hook;
use think\facade\Validate;
use api\user\model\UserModel;
use cmf\controller\RestBaseController;

class PublicController extends RestBaseController
{
    /**
     *  用户注册
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function register()
    {
        $validate = new \think\Validate([
            'username'          => 'require',
            'password'          => 'require',
            'verification_code' => 'require'
        ]);

        $validate->message([
            'username.require'          => '请输入手机号,邮箱!',
            'password.require'          => '请输入您的密码!',
            'verification_code.require' => '请输入数字验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $user = [];

        $findUserWhere = [];

        if (Validate::is($data['username'], 'email')) {
            $user['user_email']          = $data['username'];
            $findUserWhere['user_email'] = $data['username'];
        } else if (cmf_check_mobile($data['username'])) {
            $user['mobile']          = $data['username'];
            $findUserWhere['mobile'] = $data['username'];
        } else {
            $this->error("请输入正确的手机或者邮箱格式!");
        }

        $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        $findUserCount = Db::name("user")->where($findUserWhere)->count();

        if ($findUserCount > 0) {
            $this->error("此账号已存在!");
        }
        
        if(!empty($data['promo_code'])){
        	
        	$user['f_id'] = Db::name('user')->where('promo_code',$data['promo_code'])->value('id');

        }

        $user['create_time'] = time();
        $user['user_status'] = 1;
        $user['user_type']   = 2;
        $user['user_pass']   = cmf_password($data['password']);
        $user['user_nickname'] = 'user_'.$data['username'];   //昵称

        $result = Db::name("user")->insert($user);


        if (empty($result)) {
            $this->error("注册失败,请重试!");
        }

        $this->success("注册并激活成功,请登录!");

    }

    /**
     * 验证码登录
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function verificationCodeLogin()
    {
        $validate = new \think\Validate([
            'username'          => 'require',
            'verification_code' => 'require'
        ]);

        $validate->message([
            'username.require'          => '请输入手机号,邮箱!',
            'verification_code.require' => '请输入数字验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $user = [];

        $findUserWhere = [];

        if (Validate::is($data['username'], 'email')) {
            $user['user_email']          = $data['username'];
            $findUserWhere['user_email'] = $data['username'];
        } else if (cmf_check_mobile($data['username'])) {
            $user['mobile']          = $data['username'];
            $findUserWhere['mobile'] = $data['username'];
        } else {
            $this->error("请输入正确的手机或者邮箱格式!");
        }

        $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }
        $model = new UserModel;
        $findUser = $model->where($findUserWhere)->find();

        //注册
        if (empty($findUser)) {
            $user['create_time'] = time();
            $user['user_status'] = 1;
            $user['user_type']   = 2;
            $user['user_nickname'] = 'user_'.$data['username'];   //昵称
            
            //邀请码
            if(!empty($data['promo_code'])){
                $user['f_id'] = Db::name('user')->where('promo_code',$data['promo_code'])->value('id');
            }

            $userId   = Db::name("user")->insertGetId($user);
            $findUser = Db::name("user")->where('id', $userId)->find();
        } else {
            switch ($findUser['user_status']) {
                case 0:
                    $this->error('您已被拉黑!');
                case 2:
                    $this->error('账户还没有验证成功!');
            }
            $userId = $findUser['id'];
        }


        $allowedDeviceTypes = $this->allowedDeviceTypes;

        if (empty($this->deviceType) && (empty($data['device_type']) || !in_array($data['device_type'], $this->allowedDeviceTypes))) {
            $this->error("请求错误,未知设备!");
        } else if (!empty($data['device_type'])) {
            $this->deviceType = $data['device_type'];
        }
        
        $token = cmf_generate_user_token($findUser['id'], $this->deviceType);

        if (empty($token)) {
            $this->error("登录失败!");
        }
        
        //更新登录信息
        Hook::exec('api\\user\\behavior\\LastInfo',$userId);

        $this->success("登录成功!", ['token' => $token, 'user' => $findUser]);


    }

    /**
     * 用户登录
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    // TODO 增加最后登录信息记录,如 ip
    public function login()
    {
        $validate = new \think\Validate([
            'username' => 'require',
            'password' => 'require'
        ]);
        $validate->message([
            'username.require' => '请输入手机号,邮箱或用户名!',
            'password.require' => '请输入您的密码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $findUserWhere = [];

        if (Validate::is($data['username'], 'email')) {
            $findUserWhere['user_email'] = $data['username'];
        } else if (cmf_check_mobile($data['username'])) {
            $findUserWhere['mobile'] = $data['username'];
        } else {
            $findUserWhere['user_login'] = $data['username'];
        }
        $model = new UserModel;
        $findUser = $model->where($findUserWhere)->find();

        if (empty($findUser)) {
            $this->error("用户不存在!");
        } else {

            switch ($findUser['user_status']) {
                case 0:
                    $this->error('您已被拉黑!');
                case 2:
                    $this->error('账户还没有验证成功!');
            }

            if (!cmf_compare_password($data['password'], $findUser['user_pass'])) {
                $this->error("密码不正确!");
            }
        }

        $allowedDeviceTypes = $this->allowedDeviceTypes;

        if (empty($this->deviceType) && (empty($data['device_type']) || !in_array($data['device_type'], $this->allowedDeviceTypes))) {
            $this->error("请求错误,未知设备!");
        } else if (!empty($data['device_type'])) {
            $this->deviceType = $data['device_type'];
        }

        $token = cmf_generate_user_token($findUser['id'], $this->deviceType);
        if (empty($token)) {
            $this->error("登录失败!");
        }
        $userId = $findUser['id'];
        
         //更新登录信息
        Hook::exec('api\\user\\behavior\\LastInfo',$userId);
        
        cmf_api_user_action('login',$findUser['id']);
        
        
        $this->success("登录成功!", ['token' => $token, 'user' => $findUser]);
    }

    /**
     * 用户退出
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function logout()
    {
        $userId = $this->getUserId();
        Db::name('user_token')->where([
            'token'       => $this->token,
            'user_id'     => $userId,
            'device_type' => $this->deviceType
        ])->update(['token' => '']);

        $this->success("退出成功!");
    }

    /**
     * 用户密码重置
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function passwordReset()
    {
        $validate = new \think\Validate([
            'username'          => 'require',
            'password'          => 'require',
            'verification_code' => 'require'
        ]);

        $validate->message([
            'username.require'          => '请输入手机号,邮箱!',
            'password.require'          => '请输入您的密码!',
            'verification_code.require' => '请输入数字验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userWhere = [];
        if (Validate::is($data['username'], 'email')) {
            $userWhere['user_email'] = $data['username'];
        } else if (cmf_check_mobile($data['username'])) {
            $userWhere['mobile'] = $data['username'];
        } else {
            $this->error("请输入正确的手机或者邮箱格式!");
        }

        $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        $userPass = cmf_password($data['password']);
        Db::name("user")->where($userWhere)->update(['user_pass' => $userPass]);

        $this->success("密码重置成功,请使用新密码登录!");

    }
    
    /*
     * 友盟智能认证
     */
    public function oneclick_register($data)
    {

        $host = "https://verify5.market.alicloudapi.com";
        $path = "/api/v1/mobile/info";
        $method = "POST";
        $accept = "application/json";
        $content_type = "application/json; charset=UTF-8";
        $appKey = "203858358"; //阿里云市场购买应用的appKey
        $appSecret = "870VQjqCZHugN47WzeKqk8zRGjb8tS6L"; //阿里云市场购买应用的appSecret

        if ($this->deviceType == "iphone"){
            $um_appkey = "5f4c4ce112981d3ca30aaa00";
        }elseif ($this->deviceType == "android"){
            $um_appkey = "5f5728dc7823567fd865ccdf";
        }else{
            $this->error('设备类型错误');
        }
         //友盟的appkey

        $header["Accept"] = $accept;
        $header["Content-Type"] = $content_type;
        $header["X-Ca-Version"] = "1";
        $header["X-Ca-Signature-Headers"] = "X-Ca-Key,X-Ca-Nonce,X-Ca-Stage,X-Ca-Timestamp,X-Ca-Version";
        $header["X-Ca-Stage"] = "RELEASE";
        $header["X-Ca-Key"] = $appKey; //请求的阿里云AppKey
        $header["X-Ca-Timestamp"] = strval(time() * 1000);
        mt_srand((double) microtime() * 10000);
        $uuid = strtoupper(md5(uniqid(rand(), true)));
        $header["X-Ca-Nonce"] = strval($uuid);
        //Headers
        $headers = "X-Ca-Key:" . $header["X-Ca-Key"] . "\n";
        $headers .= "X-Ca-Nonce:" . $header["X-Ca-Nonce"] . "\n";
        $headers .= "X-Ca-Stage:" . $header["X-Ca-Stage"] . "\n";
        $headers .= "X-Ca-Timestamp:" . $header["X-Ca-Timestamp"] . "\n";
        $headers .= "X-Ca-Version:" . $header["X-Ca-Version"] . "\n";
        //Url
        $url = $path . "?appkey=" . $um_appkey; //appkey为用户在友盟注册的应用分配的appKey,token和phoneNumber是app传过来的值
        //sign
        $str_sign = $method . "\n";
        $str_sign .= $accept . "\n";
        $str_sign .= "\n";
        $str_sign .= $content_type . "\n";
        $str_sign .= "\n";
        $str_sign .= $headers;
        $str_sign .= $url;
        $sign = base64_encode(hash_hmac('sha256', $str_sign, $appSecret, true)); //secret为APP的密钥
        $header['X-Ca-Signature'] = $sign;
        $post_data['token'] = $data['mobile_token'];
        //curl
        $curl_url = $host . $path . "?appkey=" . $um_appkey; //appkey为用户在友盟注册的应用分配的appKey
        $headerArray = array();
        foreach ($header as $k => $v) {
            array_push($headerArray, $k . ":" . $v);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $curl_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
        $result = curl_exec($curl);

        if (curl_error($curl)){
            $this->error('请求错误');
        }
        curl_close($curl);
        return $result;
    }

    /*
     * 友盟一键注册/登录
     */
    public function oneclick_login()
    {
        $validate = new \think\Validate([
            'mobile_token'          => 'require',
        ]);
        $validate->message([
            'mobile_token.require'          => '请输入mobile_token!',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userFindMobile =   json_decode($this->oneclick_register($data),true);

        if ($userFindMobile['code'] != "2001") $this->error('请切换登录方式');

        //取到手机号
        $findUserWhere = $userFindMobile['data']['mobile'];

        //查询用户是否注册
        $findUserCount = Db::name('user')->where(['mobile' => $findUserWhere])->count();

        if ($findUserCount > 0) {
            //已注册,登录返回用户信息

            $findUser = Db::name('user')->where(['mobile' => $findUserWhere])->find();

            //查询账户状态
            switch ($findUser['user_status']) {
                case 0:
                    $this->error('您已被拉黑!');
                case 2:
                    $this->error('账户还没有验证成功!');
            }
            $userId = $findUser['id'];

        }else {
            //未注册
            $user['create_time']    = time();
            $user['user_status']    = 1;
            $user['user_type']      = 2;
            $user['user_pass']      = cmf_password('123456789');  
            $user['mobile']         = $findUserWhere;
            $user['user_nickname']  = 'user_'.$findUserWhere; 

            //判断邀请码
            if(!empty($data['promo_code'])){
                $user['f_id'] = Db::name('user')->where('promo_code',$data['promo_code'])->value('id');
            }
            //给用户注册
            $res = Db::name('user')->insert($user);

            if (empty($res)) $this->error('注册失败');

            $findUser = Db::name('user')->where(['mobile' => $findUserWhere])->find();
            
        }

        $userId = $findUser['id'];

        //检测设备类型
        if (empty($this->deviceType) && (empty($data['device_type']) || !in_array($data['device_type'], $this->allowedDeviceTypes))) {
            $this->error("请求错误,未知设备!");
        } else if (!empty($data['device_type'])) {
            $this->deviceType = $data['device_type'];
        }

        //生成令牌
        $token = cmf_generate_user_token($findUser['id'], $this->deviceType);
        if (empty($token)) {
            $this->error("登录失败!");
        }
            
        //更新登录信息
        Hook::exec('api\\user\\behavior\\LastInfo',$userId);

        $this->success("登录成功!", ['token' => $token, 'user' => $findUser]);

    }
    
    
}
