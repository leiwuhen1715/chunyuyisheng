<?php
namespace app\goods\model;

use think\Db;
use think\Model;

class OrderModel extends Model {

	public function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i',$value);
    }
}
