<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace api\user\service;

use think\Db;
use think\Log;
use think\Loader;
use think\db\Query;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;

class AliPayService
{
    protected $appid        = '2021002125626503';
    protected $pid          = "2088631583882956";
    protected $PrivateKey   = 'MIIEpAIBAAKCAQEArxwh2LQeTHZ8LAMtlsRo3iLQyf1dt3hYcJCBWhjo5ADD4kv5nzFk/MyTmz05dflnofl5BGcmbUAmzlWCjDagNOGvstNJWbfLb/Cd1iSRrtTXwqYLsFxPu8tB3gDldR+SOh1IrhXLJa+fdk4VM2XLmi/7vfvLappp/RERs4zLwP5/wX+hCGLjtjHW0gk77xHeLOVNeQGgAMBT1Dg05DHrhP1HvMmRq9dr/dxlrx1sR2mwoMVfTTHeH4ZVmUs9yZE/7xvYXeDebT7syq+7zojv49fUH6wXL3Y0GZCAS/fqbNZWrwWv0Fcwnio8JG/wd6EVYypMendR7ORfnkrDpF1ZgQIDAQABAoIBAEsuvvF11BRsQr/61VYIGiZVuGMhH12olAAwavkt/L/3/CJrEE/jO0K26yEZ1lPqsy3+GJFlYEBD7OVmHVjqEzrhMziKMtIMcZIsMvhAVzRDOifeduJPoAQ3Lp6Mr/friDLfY9cqkEAr+UEoIT4iFJqsw188fofUqHD5JSp75veDF4fZ7lH7IoP3QOJZXY3LwyvcB1PUdQ+pfIJWb5ad0n7xvvtC2O+WQy546h5DjxNUVJqDuptfWUAU7jsjg65L7u6vAm7yj7YDJ9fV6W8vS3bBAK2gD9qvQ3DFIRUWaGFJ63317IVloca37PERQnvj6ZBeWd56YCoUyAyW8Up+sMkCgYEA3Rw1WdQ6QeERKCUBcVemT0bQ/CwmwSdzhDPyd6RrfT0NJSNtEqFYEGINZEfEZqYCG5o2QIOGuTWuDaOQi/rfGsK6MnjutsSeLLbxIv/ajcQsjoImohZmziPtPafYn3FPDhRcMKK6Cg5N4wG6qJUoV6jceAuhFNJaE3RV1vMnSmsCgYEAyr27eV76nCCNZVl403XoxkXQ4/YnHzmt8J34m2BxPrpQ66f0wkV6ZsrhgWbuosNrHN6Y+Olrx6n5oa8TjnjxBP9GJMaRU+ynry6j9uzqlovJHlxaxjWCMie1Qe3yvFQRT8ZD3hhJZt0RuGH9+mCVsiuz1amplGwavIe2PYYtfsMCgYEAqoUMqABCBXh7cMhJOo3sFot4tGGgaanxLm2Zeqd0thsVPiT6Azom3kugJEwsrTUtvvfJCyvkBIXfe2k/GpUY5lxymGYnQxCTI8zaXrGObXJzIlxBSxOXqTaPydLd/ui1nRgamO+GJict7fnxZ/3QJPp3PKVLTvGCwiof4SuYi40CgYB5gHOWLWGrp6aVkqVitUsnaDfkJmUTxZGEnmebVpUHrslIMYPx2YmdiQDUeEeTq+HJj23F3r4FDszYJQBnZfurrwDukUIPNcjYp+8D0MBWMLf7RgXwu6inPjzWoi/Sxd8KHBwYQh3bU6mHAtMe9jtAaAiCiAdlN9ReIEcLYBpr2QKBgQCFXOQsQKvjjB8VJBP5gVToxM6uZyklHk6Zjp7UwGz3T4HNFYYLaWbZLsy/tb0voJBR4ubzcHQ67UsRxxU4Kq5hDsFsJZBASj92nW8N8k6rFN/pCYLR3DojLl+j5/Gvwu7FaYPUgCoF0XzlBhKGkzXl1YYNbDJDBGmmQoDEGDzeQA==';
    protected $PublicKey      = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAk5bjTNiYnEtfTvq0aaqa8hnU78fPEsLrVkRavwtY8RVqFcYR//fp9axob7fM0C1WLMW80gjeX+K+VPfj7BRFThQKc+jteE9P4CQwJe3kP6H+Jsk7Ntj5dULNjk8NkquhKd3r6RxWMaus+fHfdtU1iLhuRd1kgR/gfBEj6wBV1Kn+cI+1k5Kh7CYiDvI0HYcU31XP6yvzqbvdFPHhSrQtmLEAuklGV+J5EmY3t8aDFiQyPMwl1MGyJUnkmGyYGkAvIsUhG4HoDCBYBFxqwJeUehri+pwo0Ye5Bj6m2k8wfzvkNfMPo9RlmQqz/wZA9ceDRraP9/EeB8ciG5pZ0znr4wIDAQAB";
    protected $options;
	function __construct() {
        $this->options = $this->getOptions();

    }

    public function pay($data) {
        //统一下单接口
        //1. 设置参数（全局只需设置一次）
        
        Factory::setOptions($this->options);

        //2. 发起API调用（以支付能力下的统一收单交易创建接口为例）
        $result = Factory::payment()->app()->pay($data['goods_name'], $data['out_trade_no'], $data['total_fee']);
        

        //3. 处理响应或异常
        return $result;

    }
    public function refund($data){
	    // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        Factory::setOptions($this->options);
        
        $result =  Factory::payment()->common()->refund($data['order_sn'],$data['order_amount']);
        $res = [
            'code' => 0,
            'msg'  => ''
        ];
        
        if($result['code'] == '10000' && $result['msg'] == 'Success'){
            $res['code'] = 1;
        }else{
            $res['msg'] = $result['subMsg'];
        }
        

        return $res;
        
	}
    
    public function notify($params){
        
        Factory::setOptions($this->options);
        
    	$result = Factory::payment()->common()->verifyNotify($params);
    	return $result;
    }
    public function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = $this->appid;

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = $this->PrivateKey;//请填写您的应用私钥，例如：MIIEvQIBADANB ... ...

        //$options->alipayCertPath = '请填写您的支付宝公钥证书文件路径，例如：/foo/alipayCertPublicKey_RSA2.crt ';
        //$options->alipayRootCertPath = '请填写您的支付宝根证书文件路径，例如：/foo/alipayRootCert.crt';
        //$options->merchantCertPath = '<-- 请填写您的应用公钥证书文件路径，例如：/foo/appCertPublicKey_2019051064521003.crt -->';

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        $options->alipayPublicKey =$this->PublicKey;
        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = cmf_get_domain().'/api/home/notify/alipay';//"<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->"

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        $options->encryptKey = "";// 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA==



        return $options;
    }
    public function getAuthStr(){
    	
        $infoStr = http_build_query([
	        'apiname' => 'com.alipay.account.auth',
	        'method' => 'alipay.open.auth.sdk.code.get',
	        'app_id' => $this->appid,
	        'app_name' => 'mc',
	        'biz_type' => 'openservice',
	        'pid' => $this->pid,
	        'product_id' => 'APP_FAST_LOGIN',
	        'scope' => 'kuaijie',
	        'target_id' => mt_rand(999, 99999), //商户标识该次用户授权请求的ID，该值在商户端应保持唯一
	        'auth_type' => 'AUTHACCOUNT', // AUTHACCOUNT代表授权；LOGIN代表登录
	        'sign_type' => 'RSA2',
	    ]);
	    $infoStr .= '&sign='.$this->enRSA2($infoStr);
	    
    	return $infoStr;
    }
	/**
	 * enRSA2 RSA加密
	 * 
	 * @param String $data
	 * @return String
	 */
	private function enRSA2($data)
	{
	    $str = chunk_split(trim($this->PrivateKey), 64, "\n");
	    $key = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
	    // $key = file_get_contents(storage_path('rsa_private_key.pem')); 为文件时这样引入
	    $signature = '';
	    $signature = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256)?base64_encode($signature):NULL;
	    return $signature;
	}
	/**
	 *登录
	 */
	public function login($param,$device_Type){
		$authCode = $param['authCode'];
		$alipayOpenId = $param['alipayOpenId'];
		$end_result = [
			'status' => 0,
			'openid' => $alipayOpenId,
			'app_id' => $this->appid
		];
		// Factory::setOptions($this->options);
		// $res = Factory::Base()->OAuth()->getToken($authCode);
		// $httpBody = json_decode($res->httpBody, true);;
		// $userId = $res->userId;
		// $accessToken = $res->accessToken;
		
		
		// Log::write($res,'fanhuojieguo');
		// Log::write($userId,'支付宝userId');
		// Log::write($accessToken,'支付宝accessToken');
		// Log::write($res->httpBody,'支付宝httpBod');
		// $sign = $httpBody['sign'];
	
		
		//初始化
		Loader::import('aop.AopClient');
		Loader::import('aop.request.AlipaySystemOauthTokenRequest');
		Loader::import('aop.request.AlipayUserInfoShareRequest');
		$aop = new \AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->appid;
		$aop->rsaPrivateKey = $this->PrivateKey;
		$aop->alipayrsaPublicKey=$this->PublicKey;
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='UTF-8';
		$aop->format='json';
		
		//获取access_token
		$request = new \AlipaySystemOauthTokenRequest();
		$request->setGrantType("authorization_code");
		$request->setCode($authCode);//这里传入 code
		$result = $aop->execute($request);
		
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$access_token = $result->$responseNode->access_token;
		$end_result['user_id'] = $result->$responseNode->user_id;
		//获取用户信息
		$request_a = new \AlipayUserInfoShareRequest();
		$result_a = $aop->execute($request_a,$access_token); //这里传入获取的access_token

		$responseNode_a = str_replace(".", "_", $request_a->getApiMethodName()) . "_response";
		Log::write($result_a->$responseNode_a,'支付宝2');
		
		$end_result['nickname'] = $result_a->$responseNode_a->nick_name;
		$end_result['headimgurl']		 = $result_a->$responseNode_a->avatar;
		$end_result['sex']		 = 0;
		
		$end_result['status'] = 1;	
		return	$end_result;
		

	}

}
