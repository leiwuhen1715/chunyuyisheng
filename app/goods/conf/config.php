<?php
return array(
	//更改googs、user config
	'ORDER_STATUS' => array(
        0 => '<span style="color:red;">待确认</span>',
        1 => '已确认',
        2 => '订单完成',
        3 => '<span style="color:red;">退货</span>',                
        4 => '<span style="color:red;">无效</span>',
    ),
    'SHIPPING_STATUS' => array(
        0 => '<span style="color:red;">未确认</span>',
        1 => '已确认',        
        2 => '已确认',        
    ),
    'PAY_STATUS' => array(
        0 => '<span style="color:red;">未支付</span>',
        1 => '已支付',
		3 => '货到付款',
    )
);