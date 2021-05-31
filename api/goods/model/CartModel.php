<?php
namespace api\goods\model;

use think\Model;

class CartModel extends Model {


    public function getGoodsImgAttr($value)
    {
        return cmf_get_image_url($value);
    }


}
