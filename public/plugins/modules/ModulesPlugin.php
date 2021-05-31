<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------
namespace plugins\modules;
use cmf\lib\Plugin;
use think\db;

class ModulesPlugin extends Plugin
{

    public $info = [
        'name'        => 'Modules',
        'title'       => '内容模型',
        'description' => '内容模型',
        'status'      => 1,
        'author'      => '',
        'version'     => '1.0',
        'demo_url'    => '',
        'author_url'  => ''
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $prefix  = config('database.prefix');
        $charset = config('database.charset');
        $sqlArr  = cmf_split_sql($this->getPluginPath(). '/data/modules.sql', $prefix, $charset);
        foreach($sqlArr as $sql){
            db::execute($sql);
        }
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        $prefix = config('database.prefix');

        $sql = "DROP TABLE IF EXISTS `{$prefix}plugin_modules`;";
        db::execute($sql);
        $sql = "DROP TABLE IF EXISTS `{$prefix}plugin_modules_tables`; ";
        db::execute($sql);
        $sql = "DROP TABLE IF EXISTS `{$prefix}plugin_modules_citys`; ";
        db::execute($sql);
        $sql = "DROP TABLE IF EXISTS `{$prefix}plugin_modules_column`; ";
        db::execute($sql);
        $sql = "DROP TABLE IF EXISTS `{$prefix}plugin_modules_tables_content`; ";
        db::execute($sql);
        return true;//卸载成功返回true，失败false
    }

    public function adminDashboard(){

        $dayDataMap = [
            'legend' => [],
            'series' => []
        ];

        //获取最近15天数据
        $dayData = Db::name('plugin_modules_day_data')->field('time,sum(pub_num) as total_num')->group('time')->order('time asc')->limit(15)->select()->toArray();

        //获取管理员
        $admin_list = Db::name('user')->field('id,user_login,user_name')->where('user_type',1)->column('user_login','id');
      

        foreach ($dayData as $key=>$value){
            
            $dayDataMap['legend'][] = date('Y-m-d',$value['time']);

            //获取当天管理员数据
            $admin_time_list = Db::name('plugin_modules_day_data')->where('time',$value['time'])->group('admin_id')->column("sum(pub_num) as pubs_num",'admin_id');
            $admin_time_key = array_keys($admin_time_list);


            foreach ($admin_list as $k => $v) {
                if(in_array($k, $admin_time_key)){
                    $dayDataMap['series'][$k]['name']   = $v;
                    $dayDataMap['series'][$k]['data'][] = $admin_time_list[$k];
                }else{
                    $dayDataMap['series'][$k]['name']   = $v;
                    $dayDataMap['series'][$k]['data'][] = 0;
                }

            }

        }
        //统计文章数量

        $modules_list = Db::name('plugin_modules')->order('total_num','desc')->select()->toArray();
        foreach ($modules_list as $key => $value) {
            $table = 'item_'.$value['pinyin'];
            $modules_list[$key]['total_num']      = Db::name($table)->count();
            $modules_list[$key]['review_num']     = Db::name($table)->where('state',1)->count();
            $modules_list[$key]['no_review_num']  = Db::name($table)->where('state',2)->count();
            $modules_list[$key]['rej_review_num'] = Db::name($table)->where('state',3)->count();
        }
        $this->assign('modules_list',$modules_list);
        $this->assign('day_data_map',$dayDataMap);

        return [
            'width'  => 12,
            'view'   => $this->fetch('index'),
            'plugin' => 'Modules'
        ];

    }
}