<?php
namespace api\user\model;

use think\Db;
use think\Model;

class OrderModel extends Model {

	public function getAddTimeAttr($value){
		if(!empty($value))
        return date('Y-m-d H:i',$value);
    }
	public function getPayTimeAttr($value){
		if(!empty($value))
        return date('Y-m-d H:i',$value);
    }
	public function getShippingTimeAttr($value){
		if(!empty($value))
        return date('Y-m-d H:i',$value);
    }

}
