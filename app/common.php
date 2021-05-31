<?php


//获取当月时间
function get_month_day(){
	
	
	$start_time = strtotime("-0 year -0 month -10 day");
    $xdata = [];
    for($i=0;$i<10;$i++)
    {
        $xdata[] = date('Y-m-d',$start_time+$i*86400); //每隔一天赋值给数组
    }
	
	
	return $xdata;
}
