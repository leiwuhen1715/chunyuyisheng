<?php
namespace api\user\model;

use think\Model;

class OrderSubModel extends Model {

    public function getGoodsImgAttr($value)
    {
        return cmf_get_image_url($value);
    }

}
