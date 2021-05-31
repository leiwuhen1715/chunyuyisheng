<?php
namespace app\goods\model;

use app\admin\model\RouteModel;
use think\Db;
use think\Model;

class GoodsModel extends Model {

    protected $autoWriteTimestamp = true;
    //类型转换
    protected $type = [
        'photo' => 'array',
    ];

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


	public function setStartTimeAttr($value)
    {
        return strtotime($value);
    }

    public function setEndTimeAttr($value)
    {
        return strtotime($value);
    }

    public function getLastUpdateAttr($value)
    {
        return date('Y-m-d H:i',$value);
    }


	//添加编辑规格
	public function addGoodsSku($goods_id){


        $item    = request()->param('item');

        if(!empty($item)){
            $old_sku_id = Db::name('GoodsSku')->where('goods_id',$goods_id)->column('sku_id');
            $sameCategoryIds       = array_intersect($item['sku_id'], $old_sku_id);
            $needDeleteCategoryIds = array_diff($old_sku_id, $sameCategoryIds);

            $dataList = [];
            foreach($item['sku'] as $key => $val){
                $data = [
                    'goods_id'      => $goods_id,
                    'item_path'     => $key,
                    'price'         => $item['price'][$key],
                    'store_count'   => $item['store_count'][$key],
                    'sku'           => $item['sku'][$key]
                ];
                if(empty($item['sku_id'][$key])){
                    $dataList[] = $data;
                }else{
                    DB::name('GoodsSku')->where('sku_id',$item['sku_id'][$key])->update($data);
                }

            }
            //删除规格
            if(!empty($needDeleteCategoryIds)){
                Db::name('GoodsSku')->where('sku_id','in',$needDeleteCategoryIds)->delete();
            }
            //添加规格
            if(!empty($dataList)){
                DB::name('GoodsSku')->insertAll($dataList);
            }
        
        }else{
            Db::name('GoodsSku')->where('goods_id',$goods_id)->delete();
        }
        

	}

	//添加编辑规格图片
	public function addSpecImg($goods_id){
        $request   = request()->param();
        $spec_img  = empty($request['spec_img'])?[]:$request['spec_img'];

		DB::name('SpecImg')->where("goods_id", $goods_id)->delete();
		foreach($spec_img as $key=>$v){
			DB::name('SpecImg')->add(['goods_id'=>$goods_id ,'spec_item_id'=>$key,'img'=>$v]);
		}
	}

	public function add($data)
    {
        //$data['user_id'] = cmf_get_current_admin_id();
        $data['add_time']=time();
        $this->save($data);
        return $this;
    }

    public function edit($data)
    {
    	
        $this->allowField(true)->save($data,['goods_id'=>$data['goods_id']]);

        return $this;

    }

}
