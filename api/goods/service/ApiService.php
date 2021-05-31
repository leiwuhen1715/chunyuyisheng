<?php
namespace api\Goods\Service;
use think\Db;

class ApiService {
		
    /**
     * 返回指定分类
     * @param int $term_id 产品分类id
     * @return array 返回符合条件的分类
     */
    public static function term($term_id){
    	
		$terms_model= Db::name("Goods_category");
		$terms=$terms_model->where("is_show=1")->select();
		$mterms=array();
		
		foreach ($terms as $t){
			$tid=$t['id'];
			$mterms["t$tid"]=$t;
		}
		
		return $mterms["t$term_id"];
    	
    }  
    public static function terms($term_id=0){
        
        $terms_model= Db::name("Goods_category");
        $where=[
            'is_show'=>1,
            'parent_id'=>0
        ];
        if($term_id){
            $where['c_type']=1;
        }else{
            $where['c_type']=0;
        }
        $terms=$terms_model->field('name,id,cat_img')->where($where)->order('listorder asc')->select();
        $terms=$terms->ToArray();
        $mterms=array();
        
        foreach ($terms as $t){
            $cat=$terms_model->field('name,id,cat_img')->where(["is_show"=>1,'parent_id'=>$t['id']])->order('listorder asc')->select();
            $cat=$cat->ToArray();
            $t['cat']=$cat;
            $mterms[]=$t;
        }
        
        return $mterms;
        
    }   
    
    /**
     * 获取面包屑数据
     * @param int $term_id 当前文章所在分类,或者当前分类的id
     * @param boolean $with_current 是否获取当前分类
     * @return array 面包屑数据
     */
    public static function breadcrumb($term_id,$with_current=false){
        $category_model= Db::name("Goods_category");
        $data=array();
        $path=$category_model->where(array('id'=>$term_id))->value('parent_id_path');

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
		$arr = Db::name('GoodsCategory')->where("parent_id = $id")->column('id');
		return $arr;				
	}
}