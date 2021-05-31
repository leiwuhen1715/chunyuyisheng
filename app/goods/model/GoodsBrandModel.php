<?php
namespace app\goods\model;

use think\Db;
use think\Model;

class GoodsBrandModel extends Model {
	
  	protected $type = [
		'description'=>'string'
    ];
	/**
     * image 自动转化
     * @param $value
     * @return array
     */
    
	public function getDescriptionAttr($value)
  {
      return cmf_replace_content_file_url(htmlspecialchars_decode($value));
  }
  
	public function adds($data)
    {
        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        return $this;
    }
    public function edits($data)
    {

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        return $this;

    }
}
