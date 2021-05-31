<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;
use app\goods\model\GoodsCategoryModel;
use think\db;
use tree;

class AdminCategoryController extends AdminbaseController {

	protected $category_model;

	function initialize() {
		parent::initialize();
		$this->category_model = new GoodsCategoryModel();
	}

	// 后台产品分类列表
    public function index(){
		$result = $this->category_model->order("list_order","asc")->select()->toArray();

		$tree = new \tree\Tree();
		$array=[];
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {

			$r['str_manage'] = '<a href="' . url("AdminCategory/add", array("parent" => $r['id'])) . '">添加子类</a> | <a href="' . url("AdminCategory/edit", array("id" => $r['id'])) . '">编辑</a> ';

			$r['str_manage'].='| <a class="js-ajax-delete" href="' . url("AdminCategory/delete", array("id" => $r['id'])) . '">删除</a> ';

			$url=url("AdminCategory/edit", array("id" => $r['id']));//前台修改
			$r['url'] = $url;
			$r['id']=$r['id'];
			$r['parentid']=$r['parent_id'];
			$r['is_show'] = $r['is_show']==1 ? '是' : '否';
			$array[] = $r;
		}

		$tree->init($array);

		$str = "<tr>
					<td><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='layui-input'></td>
					<td>\$id</td>
					<td>\$spacer <a href='\$url'>\$name</a></td>
	    			<td>\$is_show</td>
					<td>\$str_manage</td>
				</tr>";
		$taxonomys = $tree->getTree(0, $str);
		$this->assign("taxonomys", $taxonomys);
		return $this->fetch();
	}

	// 产品分类添加
	public function add(){

	 	$parentid = $this->request->param("parent",0,'intval');
	 	$this->_getTree($parentid);
	 	$this->assign("parent",$parentid);
	 	return $this->fetch();
	}

	// 产品分类添加提交
	public function add_post(){
		if ($this->request->isPost()) {
			$request = $this->request->param();
			$data=$request['post'];
			if(empty($data['name']))$this->error("分类名称为空！");
			$data['is_show'] = request()->param('post.is_show',0,'intval');
			$result=$this->category_model->add($data);
			if ($result) {

				$this->success("添加成功！",url("AdminCategory/index"));
			} else {
				$this->error("添加失败！");
			}

		}
	}

	// 产品分类编辑
	public function edit(){
		$id = $this->request->param("id",0,'intval');
		$data=$this->category_model->where("id",$id)->find();
		$this->_getTree($data['parent_id'],$id);
		$this->assign("data",$data);
		return $this->fetch();
	}

	// 产品分类编辑提交
	public function edit_post(){
		if ($this->request->isPost()) {
			$request = $this->request->param();
			$data=$request['post'];
			if($data['name'] == ''){
				$this->error("分类名称为空！");
			}
			$result=$this->category_model->edit($data);
			if ($result) {
				$this->success("修改成功！");
			} else {
				$this->error("修改失败！");
			}
		}
	}

	// 产品分类排序
	public function listOrder() {
		$status = parent::listOrders(Db::name('goods_category'));
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}

	// 删除产品分类
	public function delete() {
		$id = $this->request->param("id",0,'intval');
		$count = Db::name('goods_category')->where("parent_id",$id)->count();

		if ($count > 0) {
			$this->error("该菜单下还有子类，无法删除！");
		}

		if ($this->category_model->destroy($id)!==false) {
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	public function _getTree($partent_id=0,$cat_id =0){

		$tree = new \tree\Tree();
	 	$tree->icon = array('│ ', '├─ ', '└─ ');
	 	$tree->nbsp = ' ';
	 	$where = [];
	 	if($cat_id)$where[] = ['id','neq',$cat_id];
	 	$category = Db::name('goodsCategory')->where($where)->order("parent_id_path","asc")->select()->toarray();
	 	$new_category=array();
	 	foreach ($category as $r) {
	 		$r['id']=$r['id'];
	 		$r['parentid']=$r['parent_id'];
	 		$r['selected']= (!empty($partent_id) && $r['id']==$partent_id)? "selected":"";
	 		$new_category[] = $r;
	 	}
	 	$tree->init($new_category);
	 	$tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
	 	$tree=$tree->getTree(0,$tree_tpl);
	 	$this->assign("category_tree",$tree);
	}
	

}
