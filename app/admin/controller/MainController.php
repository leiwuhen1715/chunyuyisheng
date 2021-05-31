<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\Menu;
use think\Db;

class MainController extends AdminBaseController
{

    /**
     *  后台欢迎页
     */
    public function index()
    {
        $dashboardWidgets = [];
        $widgets          = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub'           => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemCmfDocuments'     => ['name' => 'CmfDocuments', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors'     => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1'          => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2'          => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3'          => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4'          => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5'          => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }


        }

        $dashboardWidgetPlugins = [];

        $hookResults = hook('admin_dashboard');

        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }
		//数据统计
		$statistic  = [];
		$today_time = strtotime(date('Y-m-d'));
		$end_time   = strtotime(date('Y-m-d').' 23:59:59');
		
		$statistic['goods_total_num']  = Db::name('goods')->count();  //产品总数
		$statistic['goods_num']        = Db::name('goods')->where(['is_on_sale'=>1])->count();  //在售产品数量
		$statistic['user_total_num']  = Db::name('user')->where('user_type',2)->count();  //会员总数
		$statistic['user_today_num']  = Db::name('user')->where('user_type',2)->where('create_time','between',[$today_time,$end_time])->count();  //今日注册会员
		$statistic['pay_today_amount'] = Db::name('pay_log')->where('is_paid',1)->where('pay_time','between',[$today_time,$end_time])->sum('amount');//今日销售额
		$statistic['order_today_num'] = Db::name('order')->where('pay_status',1)->where('pay_time','between',[$today_time,$end_time])->count(); //今日订单数
		$statistic['wait_pay_order'] = Db::name('order')->where(['order_status'=>1,'pay_status'=>0])->count();   //待付款订单
		$statistic['wait_ship_order'] = Db::name('order')->where(['order_status'=>1,'pay_status'=>1,'shipping_status'=>0])->count(); //待发货订单
		
		//订单、销售额统计
		$order_data = $pay_data = [];
		$month_day = get_month_day(); //获取10天日期
		foreach($month_day as $key=>$vo){
			$start_time = strtotime($vo);  //开始时间
			$end_time   = strtotime($vo)+24*60*60;  //结束时间
			$order_data[$key] = Db::name('order')->where('pay_status',1)->where('pay_time','between',[$start_time,$end_time])->count();
			$pay_data[$key]   = Db::name('pay_log')->where('is_paid',1)->where('pay_time','between',[$start_time,$end_time])->sum('amount');
		}
		
		
        $smtpSetting = cmf_get_option('smtp_setting');
		
		$this->assign('month_day',$month_day);
		$this->assign('order_data',$order_data);
		$this->assign('pay_data',$pay_data);
		$this->assign('statistic',$statistic);
        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);

        return $this->fetch();
    }

    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets          = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success('更新成功!');

    }

}
