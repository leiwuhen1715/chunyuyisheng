<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// |  分类栏目验证类
// +----------------------------------------------------------------------
namespace plugins\modules\validate;

use think\Validate;

class AdminColumnValidate extends Validate
{
    protected $rule = [
        //'catalog'  => 'require|checkcatalog',
        'name'  => 'require',
        'modules_id'  => 'checkmodules',
    ];
    protected $message = [
        'name.require' => '栏目名称不能为空',
    ];

    protected $scene = [
        'add'  => ['user_login,user_pass,user_email'],
        'edit' => ['user_login,user_email'],
    ];


    // 自定义验证规则
    protected function checkmodules($value, $rule, $data)
    {
        $value = intval($value);
        if ($value<1) {
            return "请选择绑定模块!";
        }
        return true;
    }

    // 自定义验证规则
    protected function checkcatalog($value, $rule, $data)
    {
        $value = trim($value);
        if (preg_match('/^[0-9a-zA_Z]+$/',$value)) {
            return true;
        }
        return '目录只能输入字母和数字!';
    }
}