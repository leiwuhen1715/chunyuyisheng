<?php
namespace api\goods\model;

use think\Model;

class SeckillModel extends Model {

	protected $name = 'store_seckill';

	protected $type = [
        'photo' => 'array',
    ];
	protected function base($query)
    {
        $query->where('is_show', 1);
    }

    public function getGoodsImgAttr($value)
    {
        return cmf_get_image_url($value);
    }
		public function getShopPriceAttr($value){
			return del0($value);
		}
    public function getPhotoAttr($value)
    {
        $more = json_decode($value, true);

        if (!empty($more)) {
            foreach ($more as $key => $value) {
                $more[$key] = cmf_get_image_url($value);
            }
        }

        return $more;
    }
    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function getGoodDescAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }


    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function setGoodDescAttr($value)
    {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }


}
