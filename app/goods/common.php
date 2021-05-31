<?php

/**
 * 返回指定分类
 * @param int $term_id 产品分类id
 * @return array 返回符合条件的分类
 */
use think\Image;
function sp_get_term($term_id){
    return ApiService::term($term_id);
}


/**
 * 获取Goods应用当前模板下的模板列表
 * @return array
 */
function sp_admin_get_tpl_file_list(){
	$template_path=C("SP_TMPL_PATH").C("SP_DEFAULT_THEME")."/Goods/";
	$files=sp_scan_dir($template_path."*");
	$tpl_files=array();
	foreach ($files as $f){
		if($f!="." || $f!=".."){
			if(is_file($template_path.$f)){
				$suffix=C("TMPL_TEMPLATE_SUFFIX");
				$result=preg_match("/$suffix$/", $f);
				if($result){
					$tpl=str_replace($suffix, "", $f);
					$tpl_files[$tpl]=$tpl;
				}else if(preg_match("/\.php$/", $f)){
				    $tpl=str_replace($suffix, "", $f);
				    $tpl_files[$tpl]=$tpl;
				}
			}
		}
	}
	return $tpl_files;
}

/**
 *  获取面包屑数据
 * @param int $term_id 当前文章所在分类,或者当前分类的id
 * @param boolean $with_current 是否获取当前分类
 * @return array 面包屑数据
 */
function sp_get_breadcrumb($term_id,$with_current){
    return ApiService::breadcrumb($term_id,$with_current);
}

/**
 * 获取分类下的所有产品
 */
function sp_get_all_catid($id){
	return ApiService::getCatId($id);
}

/**
 * 是否存在二维数组
 * @deep in array
 */
	function deep_in_array($value, $array) {   
			foreach($array as $item) {   
				if(!is_array($item)) {   
					if ($item == $value) {  
						return true;  
					} else {  
						continue;   
					}  
				}   
					
				if(in_array($value, $item)) {  
					return true;      
				} else if(deep_in_array($value, $item)) {  
					return true;      
				}  
			}   
			return false;   
		}
		
/**
 * 点击标签
 * @sp tags
 */
 function sp_tags($tag_id){
    return ApiService::tagPosts($tag_id);
}

/**
 * 查看某个用户购物车中产品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function cart_goods_num($user_id = 0,$session_id = '')
{
    $where = " session_id = '$session_id' ";
    $user_id && $where .= " or user_id = $user_id ";
    // 查找购物车数量
    $cart_count =  M('Cart')->where($where)->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}

/**
 *  产品缩略图 给于标签调用 拿出产品表的 goods_img 原始图来裁切出来的
 * @param type $goods_id  产品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thumb_images($goods_id,$width,$height){
	if(empty($goods_id))
		 return '';
	
	$path = "../public/upload/";
	$thumb_path = $path.'goods/thumb/'.$goods_id.'/';
	$thumb_name = "{$goods_id}_goods_thumb_{$width}_{$height}";
		
	
	if(file_exists($thumb_path.$thumb_name.'.jpg')){		
		return cmf_get_image_url('goods/thumb/'.$goods_id.'/'.$thumb_name.'.jpg');		
	}
	
	$goods_img = M('Goods')->where("goods_id = $goods_id")->value('goods_img');
	
	if(empty($goods_img)){ //没有产品图片
		return '';
	}
	
	if(!is_dir($thumb_path)){
		 mkdir($thumb_path,0777,true);
	}
    
	if(file_exists($path.$goods_img)){ 
		$image        = \think\Image::open($path.$goods_img);
	    
		$image->thumb($width, $height,\think\Image::THUMB_FIXED)->save($thumb_path.$thumb_name.'.jpg');
	}	
	
	return cmf_get_image_url('goods/thumb/'.$goods_id.'/'.$thumb_name.'.jpg');
}

//删除文件夹以及目录下的文件
function delDir($directory){//自定义函数递归的函数整个目录  
    if(file_exists($directory)){//判断目录是否存在，如果不存在rmdir()函数会出错  
        if($dir_handle=@opendir($directory)){//打开目录返回目录资源，并判断是否成功  
            while($filename=readdir($dir_handle)){//遍历目录，读出目录中的文件或文件夹  
                if($filename!='.' && $filename!='..'){//一定要排除两个特殊的目录  
                    $subFile=$directory."/".$filename;//将目录下的文件与当前目录相连  
                    if(is_dir($subFile)){//如果是目录条件则成了  
                        delDir($subFile);//递归调用自己删除子目录  
                    }  
                    if(is_file($subFile)){//如果是文件条件则成立  
                        unlink($subFile);//直接删除这个文件  
                    }  
                }  
            }  
            closedir($dir_handle);//关闭目录资源  
            rmdir($directory);//删除空目录  
        }  
    }  
}  

/**
 * 多个数组的笛卡尔积
*
* @param unknown_type $data
*/
function combineDika() {
	$data = func_get_args();

	$data = current($data);
	$cnt = count($data);
	$result = array();
    $arr1 = array_shift($data);
    $arr1 = $arr1?$arr1:[];
	foreach($arr1 as $key=>$item) 
	{
		$result[] = array($item);
	}		

	foreach($data as $key=>$item) 
	{                                
		$result = combineArray($result,$item);
	}
	return $result;
}


/**
 * 两个数组的笛卡尔积
 * @param unknown_type $arr1
 * @param unknown_type $arr2
*/
function combineArray($arr1,$arr2) {		 
	$result = array();
	foreach ($arr1 as $item1) 
	{
		foreach ($arr2 as $item2) 
		{
			$temp = $item1;
			$temp[] = $item2;
			$result[] = $temp;
		}
	}
	return $result;
}

/**
 * 获取规格项图片
 * @$goods_id 产品id
 * @$item_id 规格项id
*/
function getItemImg($goods_id,$item_id){
	$img = M('Spec_img')->where(array("goods_id"=>$goods_id,"spec_item_id"=>$item_id))->find();	
	return $img['img'];
}

/**
 * 获取规格名称
 * @$goods_id 产品id
 * @$spec_id 规格id
*/
function getSpecName($goods_id,$spec_id){
	$spec = M('Spec')->where(array("goods_id"=>$goods_id,'id'=>$spec_id))->find();
	return $spec['spec_name'];
}

/**
 * 获取产品库存
 * @param type $goods_id 产品id
 * @param type $key  库存规格规则
 */
function getGoodNum($goods_id,$key)
{
    if(!empty($key)){
        return  M("GoodsSku")->where(array("goods_id"=>$goods_id,"item_path"=>$key))->value('store_count');
    }else{
        return  M("Goods")->where(array("goods_id"=>$goods_id))->value('store_count');
	}
}

