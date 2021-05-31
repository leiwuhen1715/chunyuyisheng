<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;
use app\goods\model\GoodsBrandModel;

use think\db;

class AdminBrandController extends AdminbaseController {

	protected $brand_model;

	function _initialize() {
		parent::_initialize();
		$this->brand_model = new GoodsBrandModel;;

	}

	// 后台产品分类列表
    public function index(){
		$type_list = $this->brand_model->select();
		$this->assign('type_list',$type_list);
		return $this->fetch();
	}


	public function add(){
		return $this->fetch();
	}

	public function add_post(){
		if ($this->request->isPost()) {

			$request = $this->request->param();

			$data = $request['post'];
			if($data['brandname'] == ''){
				$this->error("品牌名称不能为空！");
			}
			$brandmodel = new GoodsBrandModel();
			if(isset($data['subname']) && trim($data['subname'])!=''){
				$data['sorts'] = substr( trim($data['subname']), 0, 1 );
			}
			$result = $brandmodel->insertGetId($data);
			if ($result) {

				$this->success("添加成功！",url('goods/admin_brand/index'));
			} else {
				$this->error("添加失败！");
			}
		}
	}

	public function edit(){
		$id = input('param.id', 0, 'intval');

		$data=$this->brand_model->where(array("id" => $id))->find();
		$this->assign("data",$data);
		return $this->fetch();
	}

	public function edit_post(){
		$request = $this->request->param();

		$data = $request['post'];
		if($data['brandname'] == ''){
			$this->error("品牌名称不能为空！");
		}
      	$id = $request['post']['id'];

		$brandmodel = new GoodsBrandModel();
		if(isset($data['subname']) && trim($data['subname'])!=''){
			$data['sorts'] = substr( trim($data['subname']), 0, 1 );
		}
		$result=$this->brand_model->edits($data);
		if ($result) {
			$this->success("保存成功！",url('goods/admin_brand/index'));
		} else {
			$this->error("保存失败！");
		}
	}

	public function delete(){
		$id = input('param.id', 0, 'intval');

		$result=$this->brand_model->where(array("id" => $id))->delete();
		
		if($result !== false){
			$this->success("删除成功！");
		}else{
			$this->error("删除失败！");
		}
	}

}
