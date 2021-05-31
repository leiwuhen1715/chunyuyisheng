<?php
namespace app\goods\model;

use think\Db;
use think\Model;

class GoodsCategoryModel extends Model {
	protected $type = [
        'cat_img' => 'string',
    ];
    public function getCatImgAttr($value)
    {
        return cmf_get_image_url($value);
    }
	protected static function init()
    {
        self::afterInsert(function ($data) {
            $id=$data['id'];		
			$parent_id=$data['parent_id'];
			if($parent_id==0){
				$d['parent_id_path']="0-$id";
			}else{
				$parent=DB::name('GoodsCategory')->where("id=$parent_id")->find();
				$d['parent_id_path']=$parent['parent_id_path'].'-'.$id;
			}
			DB::name('GoodsCategory')->where("id=$id")->update($d);
        });
        self::afterUpdate(function ($data) {
            if(isset($data['parent_id'])){
				$id=$data['id'];
				$parent_id=$data['parent_id'];
				if($parent_id==0){
					$d['parent_id_path']="0-$id";
				}else{
					$parent=DB::name('GoodsCategory')->where("id=$parent_id")->find();
					$d['parent_id_path']=$parent['parent_id_path'].'-'.$id;
				}
				$result=DB::name('GoodsCategory')->where("id=$id")->update($d);
				if($result){
					$children=DB::name('GoodsCategory')->where(array("parent_id"=>$id))->select();
					foreach ($children as $child){
						DB::name('GoodsCategory')->where(array("id"=>$child['id']))->update(array("parent_id"=>$id,"id"=>$child['id']));
					}
				}
			}
        });
    }
	protected function _after_insert($data,$options){
		parent::_after_insert($data,$options);
		$id=$data['id'];		
		$parent_id=$data['parent_id'];
		if($parent_id==0){
			$d['parent_id_path']="0-$id";
		}else{
			$parent=$this->where("id=$parent_id")->find();
			$d['parent_id_path']=$parent['parent_id_path'].'-'.$id;
		}
		$this->where("id=$id")->save($d);
	}
	
	protected function _after_update($data,$options){
		echo 20;
		parent::_after_update($data,$options);
		
		if(isset($data['parent_id'])){
			$id=$data['id'];
			$parent_id=$data['parent_id'];
			if($parent_id==0){
				$d['parent_id_path']="0-$id";
			}else{
				$parent=$this->where("id=$parent_id")->find();
				$d['parent_id_path']=$parent['parent_id_path'].'-'.$id;
			}
			$result=$this->where("id=$id")->save($d);
			if($result){
				$children=$this->where(array("parent_id"=>$id))->select();
				foreach ($children as $child){
					$this->where(array("id"=>$child['id']))->save(array("parent_id"=>$id,"id"=>$child['id']));
				}
			}
		}
		
	}
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
	public function add($data)
    {
        //$data['user_id'] = cmf_get_current_admin_id();

        $post['add_time']=time();
        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        return $this;
    }
    public function edit($data)
    {

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        return $this;

    }

}