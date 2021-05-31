<?php

namespace app\goods\controller;

use cmf\controller\HomeBaseController;
use app\goods\service\ApiService;
use app\goods\model\GoodsModel;
use think\Db;

class DetailController extends HomebaseController {
	
	function _initialize() {
		parent::_initialize();
		
	}
	
	// 前台列表
	public function index() {

	    $goods_id=$this->request->param('id',0,'intval');;
	    $join = [
	    	['goods_brand b','g.brand_id = b.id']
	    ];
	    $model = new GoodsModel;
	    $goods = $model->alias('g')->field('g.*,b.subname')->join($join)->where("g.goods_id",$goods_id)->find();
		$id = $goods['cat_id'];
		
		if(empty($goods)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    $this->display(":404");
		    return;
		}

		if($goods['is_on_sale'] == 0){
			$this->error('该商品已经下架',cmf_url('Index/index'));
		}
			
		
		Db::name('Goods')->where("goods_id",$goods_id)->setInc('click_count'); //统计点击数
		
		$crumbs = ApiService::breadcrumb($id,1);//面包屑
		$mobile = '';
		$is_login = $is_collect = 0;
		if(cmf_is_user_login()){
			$mobile = session('user.mobile');

			$id    = $this->request->param('id', 0, 'intval');
        	$table = $this->request->param('table');
        	$findFavoriteCount = Db::name("user_favorite")->where([
            'object_id'  => $goods_id,
            'table_name' => 'goods',
            'user_id'    => cmf_get_current_user_id()
        ])->count();
        	if ($findFavoriteCount > 0)$is_collect = 1;
			$is_login = 1;
		}
		$join = [
			['goods_type s','s.id = b.tid'],
		];

		$brand_type  = Db::name('brand_type')->alias('b')->field('s.*')->join($join)->where('b.bid',$goods['brand_id'])->find();
		$join = [
			['goods_style s','s.id = b.sid'],
		];
		$brand_style = Db::name('brand_style')->alias('b')->field('s.*')->join($join)->where('b.bid',$goods['brand_id'])->find();

		$this->assign('crumbs', $crumbs);
		$this->assign('act_title', $goods['goods_name']);
		$this->assign('goods', $goods);
		$this->assign('is_login',$is_login);
		$this->assign('is_collect',$is_collect);
		$this->assign('brand_type',$brand_type);
		$this->assign('brand_style',$brand_style);
		$this->assign('mobile', $mobile);
		
    	return $this->fetch();
	}

	public function special(){
		
		$goods_id = Db::name('goods')->where(['is_on_sale'=>1,'is_special'=>1])->order('goods_id','desc')->value('goods_id');
		if(empty($goods_id)){
			$goods_id = Db::name('goods')->where(['is_on_sale'=>1])->order('goods_id','desc')->value('goods_id');
		}
	    $join = [
	    	['goods_brand b','g.brand_id = b.id']
	    ];

	    $model = new GoodsModel;
	    $goods = $model->alias('g')->field('g.*,b.subname')->join($join)->where("g.goods_id",$goods_id)->find();
		$id = $goods['cat_id'];
		
		if(empty($goods)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    $this->display(":404");
		    return;
		}

		if($goods['is_on_sale'] == 0){
			$this->error('该商品已经下架',cmf_url('Index/index'));
		}
			
		
		Db::name('Goods')->where("goods_id",$goods_id)->setInc('click_count'); //统计点击数
		
		$crumbs = ApiService::breadcrumb($id,1);//面包屑
		$mobile = '';
		$is_login = $is_collect = 0;
		if(cmf_is_user_login()){
			$mobile = session('user.mobile');

			$id    = $this->request->param('id', 0, 'intval');
        	$table = $this->request->param('table');
        	$findFavoriteCount = Db::name("user_favorite")->where([
            'object_id'  => $goods_id,
            'table_name' => 'goods',
            'user_id'    => cmf_get_current_user_id()
        ])->count();
        	if ($findFavoriteCount > 0)$is_collect = 1;
			$is_login = 1;
		}
		
      	$join = [
			['goods_type s','s.id = b.tid'],
		];

		$brand_type  = Db::name('brand_type')->alias('b')->field('s.*')->join($join)->where('b.bid',$goods['brand_id'])->find();
		$join = [
			['goods_style s','s.id = b.sid'],
		];
		$brand_style = Db::name('brand_style')->alias('b')->field('s.*')->join($join)->where('b.bid',$goods['brand_id'])->find();

		$this->assign('crumbs', $crumbs);
		$this->assign('act_title', $goods['goods_name']);
		$this->assign('goods', $goods);
		$this->assign('is_login',$is_login);
		$this->assign('is_collect',$is_collect);
		$this->assign('act_index','special');
      	$this->assign('brand_type',$brand_type);
		$this->assign('brand_style',$brand_style);
		$this->assign('mobile', $mobile);
		
    	return $this->fetch('index');
	}
}
