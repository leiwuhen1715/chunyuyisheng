<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;
use app\goods\model\GoodsModel;
use app\goods\model\StoreSeckillModel;
use think\db;
use tree;

class AdminSeckillController extends AdminbaseController {

	protected $goods_model;


	// 后台产品分类列表
    public function index(){

		return $this->fetch();
	}
	/**
	 * ajax 列表
	 */
	public function ajax(){

		$limit     = request()->param('limit',10,'intval');
		$title     = request()->param('title');
		$status    = request()->param('status');
		$startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
		$endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time'].' 23:59');

		$order = 'id';
		$src   = "desc";
		$where = [];
		if($status != '')        $where[] = ['is_on_sale','=',$status];
		if(!empty($startTime))   $where[] = ['start_time','>',$startTime];
		if(!empty($endTime))     $where[] = ['end_time','<',$endTime];
		if(!empty($title))       $where[] = ['goods_name','like','%'.$title.'%'];

		$count = Db::name('StoreSeckill')->where($where)->count();
		$model  = new StoreSeckillModel;
		$data   = $model->where($where)->order($order,$src)->paginate($limit);
		$list   = $data->items();

		$result = ['code'=>0,'count'=>$count,'data'=>$list];
		die(json_encode($result));
	}

	public function add(){

		$id    = request()->param('id',0,'intval');


		$model = new GoodsModel;
		$data  = $model->where("goods_id",$id)->find();

		$this->_getTree($data['cat_id']);
		$this->assign("data",$data);

		return $this->fetch();

	}


	public function add_post(){


		if ($this->request->isPost()) {

				$request = $this->request->param();
				$data=$request['post'];
				if(!$data['goods_name'])$this->error("请填写活动标题");
				$data['is_on_sale'] = $this->request->param('post.is_on_sale',0,'intval');
				$model  = new StoreSeckillModel;
				$result = $model->add($data);

				if ($result) {
					$this->success("添加成功！",url('AdminSeckill/index'));
				} else {
					$this->error("添加失败！");
				}
		}
	}

	public function edit(){

		$id = request()->param('id',0,'intval');

		$model  = new StoreSeckillModel;
		$data   = $model->where('id' ,$id)->find();
		$this->_getTree($data['cat_id']);
		$this->assign("data",$data);
		return $this->fetch();
	}

	public function edit_post(){
		if ($this->request->isPost()) {
			$request = $this->request->param();
			$data = $request['post'];
			if(!$data['goods_name'])$this->error("活动名称不能为空！");
			
			$data['is_on_sale'] = $this->request->param('post.is_on_sale',0,'intval');
			$model  = new StoreSeckillModel;
			$result = $model->edit($data);

			if ($result) {
				$this->success("保存成功！");
			} else {
				$this->error("保存失败！");
			}
		}
	}

	//放入回收站
	public function delete(){

		$id     = request()->param('id',0,'intval');

		$model  = new StoreSeckillModel;
		$result = $model->where("id",$id)->delete();

		if($result){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	public function status(){
		$value  = request()->param('value',0,'intval');
		$id 	= request()->param('id',0,'intval');
		$field  = request()->param('field'); // 修改哪个字段
		Db::name('StoreSeckill')->where('id',$id)->update([$field=>$value]);
		$this->success('ok');
	}

    public function listOrder()
    {
        parent::listOrders('store_seckill');
        $this->success("排序更新成功！", '');
    }

    public function _getTree($cat_id =0){

		$tree = new \tree\Tree();
	 	$tree->icon = array('│ ', '├─ ', '└─ ');
	 	$tree->nbsp = ' ';
	 	$category = Db::name('goodsCategory')->order("parent_id_path","asc")->select()->toarray();
	 	$new_category=array();
	 	foreach ($category as $r) {
	 		$r['id']        = $r['id'];
	 		$r['parentid']  = $r['parent_id'];
	 		$r['selected']  = $r['id']==$cat_id? "selected":"";
	 		$new_category[] = $r;
	 	}
	 	$tree->init($new_category);
	 	$tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
	 	$tree=$tree->getTree(0,$tree_tpl);
	 	$this->assign("category_tree",$tree);
	}

}
