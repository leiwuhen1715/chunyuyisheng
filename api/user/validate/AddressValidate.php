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
namespace api\user\validate;

use think\Validate;

class AddressValidate extends Validate
{
    protected $rule = [
        'consignee' => 'require',
        'mobile'   => 'require|checkMob',
        // 'country' => 'require',
        // 'address'    => 'require',
    ];
    protected $message = [
        // 'address.require'    => '请填写详细地址!',
        'consignee.require' => '请填写收货人!',
        // 'country.require' => '请填写所在地区!',
        'mobile.require'   => '请填写手机号!',
        'mobile.checkMob'  => '手机格式不正确!'
    ];

    protected $scene = [
    ];

    // 验证url 格式
    protected function checkMob($value, $rule, $data)
    {
        $res =cmf_check_mobile($value);
        if ($res) {
            return true;
        }
        return '手机格式不正确!';
    }

}