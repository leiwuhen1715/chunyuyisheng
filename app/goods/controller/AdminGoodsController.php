<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;
use app\goods\model\GoodsModel;
use app\goods\model\GoodsCategoryModel;
use app\goods\logic\GoodsLogic;
use think\db;

use tree;

class AdminGoodsController extends AdminbaseController {

	protected $goods_model;

	function initialize() {
		parent::initialize();
		$this->goods_model = new GoodsModel;
		$this->category_model = new GoodsCategoryModel;
	}

	// 后台产品分类列表
    public function index(){

		
		$this->_getTree();
		return $this->fetch();
	}

	public function ajax(){
		$param = request()->param();
		$limit = request()->param('limit',10,'intval');
		$title = request()->param('title');
		$status = request()->param('status');
		$cat_id = request()->param('cate_id');

		$order = 'goods_id';
		$src   = "desc";
		$where = [];
		if($status != '')   $where[] = ['is_on_sale','=',$status];
		if(!empty($cat_id)) $where[] = ['cat_id','=',$cat_id];
		if(!empty($title))  $where[] = ['goods_name','like','%'.$title.'%'];

		$join=[
        	['goods_category c','g.cat_id = c.id','left']
        ];
		$count = Db::name('goods')->where($where)->count();
		$model = new GoodsModel;
		$data  = $model->field('g.*,c.name as cate_name')->alias('g')->join($join)->where($where)->order($order,$src)->paginate($limit);
		
		$result = ['code'=>0,'count'=>$count,'data'=>$data->items()];
		die(json_encode($result));
	}


	public function _getTree($cat_id =0){

		$tree = new \tree\Tree();
	 	$tree->icon = array('│ ', '├─ ', '└─ ');
	 	$tree->nbsp = ' ';
	 	$category = Db::name('goodsCategory')->order("parent_id_path","asc")->select()->toarray();
	 	$new_category=array();
	 	foreach ($category as $r) {
	 		$r['id']=$r['id'];
	 		$r['parentid']=$r['parent_id'];
	 		$r['selected']= (!empty($cat_id) && $r['id']==$cat_id)? "selected":"";
	 		$new_category[] = $r;
	 	}
	 	$tree->init($new_category);
	 	$tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
	 	$tree=$tree->getTree(0,$tree_tpl);
	 	$this->assign("category_tree",$tree);
	}



	public function add(){
		
		//产品分类
		$this->_getTree();

		//产品类型
		$goods_type = Db::name("GoodsMold")->select();

		$this->assign("goods_type",$goods_type);
		return $this->fetch();
	}

	//ajax获取规格
	public function ajaxGetSpec(){
		$id       = $this->request->param('type_id',0,'intval');
		$goods_id = $this->request->param('id',0,'intval');

		$spec = DB::name('Spec')->where("type_id",$id)->order("list_order","asc")->select();
		$spec =$spec->toArray();
		foreach($spec as $key=>$v){
			$spec[$key]['items'] = DB::name('SpecItem')->where('spec_id',$v['id'])->column('id,item');
		}
		//获取规格id

		$items_id = DB::name('goods_sku')->where("goods_id",$goods_id)->value("GROUP_CONCAT(`item_path` SEPARATOR '-') AS items_id");

        $items_ids = explode('-', $items_id);

		$this->assign('spec',$spec);
		$this->assign('type_id',$id);
		$this->assign('items_ids',$items_ids);
		$this->assign("goods_id",$goods_id);
		return $this->fetch("ajaxspec");
	}

	/**
     * 动态获取产品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){
        $GoodsLogic = new GoodsLogic();
        $goods_id = $this->request->param('goods_id',0,'intval');
        $request  = $this->request->param();


		$spec_arr = empty($request['spec_arr'])?[]:$request['spec_arr'];
        $str = $GoodsLogic->getSpecInput($goods_id ,$spec_arr);
        exit($str);
    }

	public function add_post(){


		if ($this->request->isPost()) {

				$request = $this->request->param();
				
				$data=$request['post'];
				if(empty($data['goods_name']))$this->error("商品名称不能为空！");
				
				$data['last_update'] = time();
				$data['is_on_sale'] = $this->request->param('post.is_on_sale',0,'intval');
				$data['is_recommend'] = $this->request->param('post.is_recommend',0,'intval');
				$data['is_new'] = $this->request->param('post.is_new',0,'intval');
				$data['is_hot'] = $this->request->param('post.is_hot',0,'intval');
				

				$result=$this->goods_model->add($data);
				$this->goods_model->addGoodsSku($result);//添加规格
				$this->goods_model->addSpecImg($result);//添加规格图片

				if ($result) {
					$this->success("添加成功！",url('goods/adminGoods/index'));
				} else {
					$this->error("添加失败！");
				}
		}
	}

	public function edit(){

		$id = request()->param('id',0,'intval');
		$data=$this->goods_model->where("goods_id",$id)->find();

		//产品类型
		$goods_type = DB::name('GoodsMold')->select();

	 	$this->_getTree($data['cat_id']);
		$this->assign("data",$data);
		$this->assign("goods_type",$goods_type);
		return $this->fetch();
	}

	public function edit_post(){
		if ($this->request->isPost()) {
			$request = $this->request->param();
				$data = $request['post'];
				if(empty($data['goods_name']))$this->error("产品名称不能为空！");
				
				$data['last_update'] = time();

				$data['is_on_sale'] = $this->request->param('post.is_on_sale',0,'intval');
				$data['is_recommend'] = $this->request->param('post.is_recommend',0,'intval');
				$data['is_new'] = $this->request->param('post.is_new',0,'intval');
				$data['is_hot'] = $this->request->param('post.is_hot',0,'intval');
				
				$result=$this->goods_model->edit($data);
				$this->goods_model->addGoodsSku($data['goods_id']);//添加规格
				$this->goods_model->addSpecImg($data['goods_id']);//添加规格图片
				
				if ($result) {
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
		}
	}

	public function delete(){
		$request = $this->request->param();
		if(isset($request['ids'])){
		    
			$ids = $request['ids'];

			if ($this->goods_model->where('goods_id','in',$ids)->delete()!==false) {
				Db::name('goods_sku')->where('goods_id','in',$ids)->delete();
				Db::name('cart')->where('goods_id','in',$ids)->delete();
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}

	
	/**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeVal(){
    		$request = $this->request;

            $id = $request->param('id'); // 表主键id值
            $field  = $request->param('field'); // 修改哪个字段
            $value  = $request->param('value'); // 修改字段值
            DB::name('goods')->where("goods_id",$id)->update([$field=>$value]); // 根据条件保存修改的数据
    }

    public function listOrder()
    {
        parent::listOrders(Db::name('goods'));
        $this->success("排序更新成功！", '');
    }

}
