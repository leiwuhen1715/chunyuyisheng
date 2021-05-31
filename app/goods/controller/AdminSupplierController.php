<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;

use think\db;

class AdminSupplierController extends AdminbaseController {


	// 后台产品分类列表
    public function index(){
		$list = Db::name('supplier')->select();
		$this->assign('list',$list);
		return $this->fetch();
	}


	public function add(){
		return $this->fetch();
	}

	public function add_post(){
		if ($this->request->isPost()) {

			$request = $this->request->param();

			$data = $request['post'];
			if($data['name'] == ''){
				$this->error("名称不能为空！");
			}

			$result = Db::name('supplier')->insertGetId($data);
			if ($result) {

				$this->success("添加成功！",url('goods/admin_supplier/index'));
			} else {
				$this->error("添加失败！");
			}
		}
	}

	public function edit(){
		$id = request()->param('id',0,'intval');

		$data=Db::name('supplier')->where("id",$id)->find();
		$this->assign("data",$data);
		return $this->fetch();
	}

	public function edit_post(){
		$request = request()->param();

		$data = $request['post'];
		if(empty($data['name'])){
			$this->error("名称不能为空！");
		}
      	$id = $data['id'];

		$result=Db::name('supplier')->where('id',$id)->update($data);

		if ($result) {
			$this->success("保存成功！",url('goods/admin_supplier/index'));
		} else {
			$this->error("名称为改变！");
		}
	}

	public function delete(){
		$id = request()->param('id',0,'intval');

		$result=Db::name('supplier')->where("id",$id)->delete();
		
		if($result !== false){
			$this->success("删除成功！");
		}else{
			$this->error("删除失败！");
		}
	}

}
