<?php
namespace Goods\Service;

class ApiService {
		
    /**
     * 返回指定分类
     * @param int $term_id 产品分类id
     * @return array 返回符合条件的分类
     */
    public static function term($term_id){
    	$terms=F('all_category');
    	if(empty($terms)){
    		$terms_model= M("Goods_category");
    		$terms=$terms_model->where("is_show=1")->select();
    		$mterms=array();
    		
    		foreach ($terms as $t){
    			$tid=$t['id'];
    			$mterms["t$tid"]=$t;
    		}
    		
    		F('all_category',$mterms);
    		return $mterms["t$term_id"];
    	}else{
    		return $terms["t$term_id"];
    	}
    }   
    
    /**
     * 获取面包屑数据
     * @param int $term_id 当前文章所在分类,或者当前分类的id
     * @param boolean $with_current 是否获取当前分类
     * @return array 面包屑数据
     */
    public static function breadcrumb($term_id,$with_current=false){
        $category_model= M("Goods_category");
        $data=array();
        $path=$category_model->where(array('id'=>$term_id))->getField('parent_id_path');
        if(!empty($path)){
            $parents=explode('-', $path);
            if(!$with_current){
                array_pop($parents);
            }
          
            if(!empty($parents)){
                $data=$category_model->where(array('id'=>array('in',$parents)))->order('parent_id_path ASC')->select();
            }
        }
		
        return $data;
    }
	
	/**
	 * 获取所有分类id
	 * @param $id 分类id
	 */
	public static function getCatId($id){
		$arr = M('GoodsCategory')->where("parent_id = $id")->getField('id',true);
		return $arr;				
	}
}