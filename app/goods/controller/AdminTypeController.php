<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;
use think\db;

class AdminTypeController extends AdminbaseController {

	protected $type_model;

	function initialize() {
		parent::initialize();
		$this->type_model = Db::name('goods_mold');

	}

	// 后台产品分类列表
    public function index(){
		$type_list = $this->type_model->select();

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
			if(empty($data['name']))$this->error("类型名称不能为空！");
			
			$result=$this->type_model->insert($data);
			if ($result) {
				$this->success("添加成功！",url('goods/admin_type/index'));
			} else {
				$this->error("添加失败！");
			}

		}
	}

	public function edit(){
		$id = input('param.id', 0, 'intval');

		$data=$this->type_model->where("id", $id)->find();
		$this->assign("data",$data);
		return $this->fetch();
	}

	public function edit_post(){
		$request = $this->request->param();
		$data = $request['post'];
		if(empty($data['name']))$this->error("类型名称不能为空！");
		
		$result=$this->type_model->where('id',$data['id'])->update($data);
		if ($result) {
			$this->success("保存成功！",url('goods/admin_type/index'));
		} else {
			$this->error("信息未改变！");
		}


	}

	public function delete(){
		$id = input('param.id', 0, 'intval');
		$count = Db::name('Spec')->where('type_id',$id)->count();
		if($count > 0)$this->error('请先删除对应规格');
		$result=$this->type_model->where("id" , $id)->delete();
		if($result !== false){
			$this->success("删除成功！");
		}else{
			$this->error("删除失败！");
		}
	}

}
