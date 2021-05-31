<?php
namespace api\goods\model;

use think\Model;

class GoodsBrandModel extends Model {

	

    public function getPicAttr($value)
    {
        return cmf_get_image_url($value);
    }
    public function getLogoAttr($value)
    {
        return cmf_get_image_url($value);
    }
    public function getDescriptionAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

}