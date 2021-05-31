<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 All rights reserved.
// +----------------------------------------------------------------------

namespace plugins\admin_journal;

use cmf\lib\Plugin;
use think\Db;

class AdminJournalPlugin extends Plugin
{
    public $info = [
        'name'        => 'AdminJournal',
        'title'       => '操作日志',
        'description' => '后台操作日志',
        'status'      => 1,
        'author'      => 'lanlan',
        'version'     => '1.2.0',
        'demo_url'    => '',
        'author_url'  => ''
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    public function adminInit()
    {
        $url = $menuName = request()->path();

        $nums = (strpos($url,'plugin') !== false)?4:3;

        $str=explode('/',$url);
        $path = '';
        $adminId = cmf_get_current_admin_id();

        foreach ($str as $k => $v){
            if($k<$nums){
                $path .=str_replace('_','',$v);
            }
        }
        $path = strtolower($path);
        
        $menus = cache('menus_' . $adminId, '', null, 'menus');
    
        if(empty($menus)){
            $result = Db::name('AdminMenu')->field('app,name,controller,action')->order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
            $menusTmp['adminmainindex'] = '后台首页';
            foreach ($result as $item){
                $indexTmp = str_replace('/','',strtolower($item['app'].$item['controller'].$item['action']));
                $menusTmp[$indexTmp] = $item['name'];
            }
            cache('menus_' . $adminId, $menusTmp, null, 'menus');
        }else{
            if(!empty($menus[$path])){
                $menuName =  $menus[$path];
            }
        }
        $modules_id = request()->param('modules_id',0,'intval');
        if(!empty($modules_id)){
            $modules_tables = cache('modules_tables', '', null, 'modules_tables');
            $name_arr = [
                'content'       => '',
                'admin_column'  => '分类',
                'index'         => '',
                'add'           => '添加',
                'addpost'      => '添加保存',
                'edit'          => '编辑',
                'editpost'      => '编辑保存',
                'delete'        => '删除',
                'listorder'     => '排序',
                ''              => ''
            ];

            $controller = request()->param('_controller');
            $action     = request()->param('_action');
            
            if(empty($modules_tables)){

                $modules_tables = Db::name('plugin_modules')->column('name', 'id');
                cache('modules_tables', $modules_tables, null, 'modules_tables');
            }
            if(!empty($modules_tables[$modules_id])){
                $menuName = '';
                if(!empty($name_arr[$action])){
                    $menuName = $name_arr[$action];
                }
                $menuName .=  $modules_tables[$modules_id];
                if(!empty($name_arr[$controller])){
                    $menuName .= '-'.$name_arr[$controller];
                }
            }
        }
        $time=time();
        $this->assign("js_debug",APP_DEBUG?"?v=$time":"");
        $array_log = [$adminId,session('name'),date('H:i:s'),get_client_ip(),$menuName,request()->param()];
        $filename = CMF_ROOT . 'data/journal/';
        !is_dir($filename) && mkdir($filename, 0755, true);
        $file_hwnd=fopen($filename.date('Y-m-d').".log","a+");
        fwrite($file_hwnd,json_encode($array_log)."\r\n");
        fclose($file_hwnd);
    }

}
