<?php
namespace app\goods\model;

use think\Db;
use think\Model;

class GoodsTypeModel extends Model {
	
	public function add($data)
    {
        //$data['user_id'] = cmf_get_current_admin_id();

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        return $this;
    }
    public function edit($data)
    {

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        return $this;

    }
}
