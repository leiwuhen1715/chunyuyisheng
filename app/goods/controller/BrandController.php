<?php

namespace app\goods\controller;

use cmf\controller\HomeBaseController;
use app\goods\model\GoodsBrandModel;
use app\goods\service\ApiService;
use think\Db;
/**
 * 首页
 */
class BrandController extends HomebaseController {
	
	function _initialize() {
		parent::_initialize();
		$this->assign('act_title','品牌列表');
	}

    //首页 
	public function index() {
		
		$param = $this->request->param();
		$type_id   = $this->request->param('type_id',0,'intval');
		$style_id   = $this->request->param('style_id',0,'intval');
		$type_list = Db::name('goods_type')->order('id','desc')->select();
		$style_list = Db::name('goods_style')->order('id','desc')->select();
		if(empty($style_id) && empty($type_id)){
			$model = Db::name('goods_brand');
			$list = $model->order('id','desc')->paginate(12);
		}elseif(!empty($style_id) && empty($type_id)){
			$join = [
				['goods_brand b','s.bid = b.id']
			];
			$list = Db::name('brand_style')->alias('s')->join($join)->where('s.sid',$style_id)->order('s.id','desc')->paginate(12);
		}elseif(empty($style_id) && !empty($type_id)){
			$join = [
				['goods_brand b','t.bid = b.id']
			];
			$list = Db::name('brand_type')->alias('t')->join($join)->where('t.tid',$type_id)->order('t.id','desc')->paginate(12);

		}elseif(!empty($style_id) && !empty($type_id)){
			$join = [
				['goods_brand b','s.bid = b.id'],
				['brand_type t','t.bid = s.bid']
			];
			$where = [
				't.tid' => $type_id,
				's.sid' => $style_id
			];
			$list = Db::name('brand_style')->alias('s')->field('b.*')->join($join)->where($where)->order('s.id','desc')->paginate(12);
		}
		
		$list->appends($param);
		$page = $list->render();
		$data = [];
		if(!empty($style_id))$data['style_id'] = $style_id;
		if(!empty($type_id))$data['type_id'] = $type_id;

		$this->assign([
			'data' 			=> $data,
			'page' 			=> $page,
			'style_id'		=> $style_id,
			'type_id'		=> $type_id,
			'type_list'  	=> $type_list,
			'style_list' 	=> $style_list,
			'list'  		=> $list
		]);
    	return $this->fetch();
	}
	public function list(){

		$param   = $this->request->param();
		$id 	 = $this->request->param('id',0,'intval');
		$cate_id = $this->request->param('cate_id',0,'intval');
		$order = $norder  = $this->request->param('order');
		$price_id = $this->request->param('price_id',0,'intval');
		$order_date = ['store_count','price','time'];
		$price_date = ['全部','0-4194','4194-8388','8388-12582','12582-16776'];

		$where_date = $where1 = [];
		$orders = ['goods_id'=>'desc'];
		$where=["is_on_sale"=>1];

		if($id)$where_date['id'] = $where['brand_id'] = $id;
		if($cate_id){
			$where_date['cate_id']  = $cate_id;
			$all_catid = ApiService::getCatId($cate_id); //获取所有子类

			if(empty($all_catid)){
				$where['cat_id']=$cate_id;
			}else{
				$all_catid[]=$cate_id;
				$where['cat_id']=['in',$all_catid];
			}
		}
		if($price_id){
			$where_date['price_id'] = $price_id;
			$price_detail = explode('-', $price_date[$price_id]);
			$where['shop_price'] = ['>',$price_detail[0]];
			$where1['shop_price'] = ['<',$price_detail[1]];
		}
		if(in_array($order,$order_date)){
			if($norder=='price')$norder='shop_price';
			if($norder=='time')$norder='last_update';
			$orders = [$norder=>'desc'];
			$where_date['order'] = $order;
		}

		$list = Db::name('Goods')->where($where)->where($where1)->order($orders)->paginate(15);
		$list->appends($param);
		$page = $list->render();

		$hasbrand = false;
		$brand_model = new GoodsBrandModel;
		$brand = $brand_model->where('id',$id)->find();
		if($brand)$hasbrand = true;

		$goods_cate = Db::name('goods_category')->where(['parent_id'=>0,'is_show'=>1])->select();

		$is_collect = 0;
		if(cmf_is_user_login()){

        	$findFavoriteCount = Db::name("user_favorite")->where([
	            'object_id'  => $id,
	            'table_name' => 'goods_brand',
	            'user_id'    => cmf_get_current_user_id()
        	])->count();
        	if ($findFavoriteCount > 0)$is_collect = 1;

		}

		$this->assign([
			'act_title'	 => $brand['brandname'],
			'page'  	 => $page,
			'brand' 	 => $brand,
			'list'  	 => $list,
			'cate_id'    => $cate_id,
			'price_id'   => $price_id,
			'hasbrand'   => $hasbrand,
			'goods_cate' => $goods_cate,
			'where_date' => $where_date,
			'price_date' => $price_date,
			'is_collect' => $is_collect
		]);
		return $this->fetch();
	}

}


