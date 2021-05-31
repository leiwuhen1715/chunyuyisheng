<?php
namespace app\goods\model;

use think\Db;
use think\Model;

class GoodsImagesModel extends Model {
	
  	protected $type = [
        'image_url' => 'string',
    ];
	/**
     * image 自动转化
     * @param $value
     * @return array
     */
    public function getImageUrlAttr($value)
    {
        return cmf_get_image_url($value);
    }
}
