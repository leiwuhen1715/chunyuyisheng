<?php
namespace api\goods\model;

use think\Model;

class GoodsCommentModel extends Model {

	protected $type = [
        'photo' => 'array',
    ];
	protected $relationFilter = ['user', 'to_user'];

    public function getImgAttr($value)
    {
        
        if (!empty($value)) {
            $more = explode(',', $value);
            foreach ($more as $key => $value) {
                $more[$key] = cmf_get_image_url($value);
            }
        }else{
            $more = [];
        }
        
        return $more;
    }

    /**
     * 关联 user表
     * @return $this
     */
    public function user()
    {
        return $this->belongsTo('api\user\model\UserModel', 'user_id')->field('id,user_nickname,avatar');
    }
    
}