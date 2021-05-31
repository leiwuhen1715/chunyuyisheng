<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\goods\controller;

use think\Db;
use cmf\controller\RestBaseController;
use api\goods\model\GoodsCategoryModel;
use api\goods\model\GoodsModel as Goods;
use api\goods\logic\GoodsLogic;
use api\goods\model\GoodsCommentModel;

class IndexController extends RestBaseController
{
    // api 首页
    public function index()
    {
    	$catrgory = new GoodsCategoryModel;
        $goods    = new Goods();
    	$order=[
    		'list_order'=>'asc',
    		'id'	   =>'asc'
    	];
        $field = 'goods_id,goods_name,goods_img,shop_price';
        $where = ['is_show'=>1,'parent_id'=>0];
    	$cat_list=$catrgory->field('id,name,cat_img')->where($where)->order($order)->limit(6)->select();
        foreach ($cat_list as $key => $value) {
            $cat_list[$key]['goods_list'] = $goods->field($field)->where('cat_id',$value['id'])->limit(10)->select();
        }
        $this->success("获取分类成功!", $cat_list);
    }
    /*首页产品*/
    public function getList(){

        $input = $this->request->param();
        $id = $this->request->param('category_id', 0, 'intval');
        $child_id = request()->param('child_id', 0, 'intval');
        $prom_type =  $this->request->param('prom_type', 0, 'intval');
        $is_recommend =  $this->request->param('is_recommend', 0, 'intval');
        $is_special = request()->param('is_special',0,'intval');
        $keyword    =  request()->param('keyword');


        $goods                 = new Goods();
        $where = [];

        if($id){
            if($child_id){
                $where['cat_id'] = $child_id;
            }else{
                $c_where = ['parent_id'=>$id,'is_show'=>1];
                $cat_list = Db::name('goods_category')->where($c_where)->column('id');
                if(empty($cat_list)){
                    $where[]=['cat_id','=',$id];
                }else{
                    $cat_list[] = $id;
                    $where[]=['cat_id','in',$cat_list];
                }
            }
        }
        if($prom_type)$where[]    = ['prom_type','=',1];
        if($is_recommend)$where[] = ['is_recommend','=',1];
        if($is_special)$where[]   = ['is_special','=',1];
        if($keyword)$where[]      = ['goods_name','like',"%".$keyword."%"];

        $order = [
            'list_order' => 'asc',
            'goods_id'   => 'desc'
        ];
        $data = $goods->field('goods_id,goods_img,goods_name,shop_price,market_price')->where($where)->order($order)->paginate(10);
        $list = $data->items();
        $this->success('ok!', $list);

    }
    /*秒杀产品*/
    public function getSeckill(){

        $input = $this->request->param();
        $id = $this->request->get('category_id', 0, 'intval');
        $is_recommend =  $this->request->get('is_recommend', 0, 'intval');

        $goods                 = new Goods();
        $where = [];
        if($id){
            $where=['cat_id'=>$id];
        }

        $where['prom_type']=1;

        if($is_recommend)$where['is_recommend'] = 1;

        $order = [
            'list_order' => 'asc',
            'goods_id'   => 'desc'
        ];
        $data = $goods->field('goods_id,goods_img,goods_name,shop_price,market_price')->where($where)->order($order)->paginate(10);
        $list = $data->items();
        $this->success('ok!', $list);

    }
    /**
     * 获取指定的文章
     * @param int $id
     */
    public function read()
    {
        $id = $this->request->get('id', 0, 'intval');

        if (intval($id) === 0) {
            $this->error('无效的产品id！',null);
        } else {
            $params                       = $this->request->get();

            $goods                 = new Goods();
            $data                         = $goods->where('goods_id',$id)->find();
            $goods->where('goods_id', $id)->setInc('click_count');

            if (empty($data)) {
                $this->error('产品不存在！',null);
            } else {
                $data        = $data->toArray();
                if(empty($data['photo'])){
                    $data['photo']=[$data['goods_img']];
                }else{
                     $data['photo'][]=$data['goods_img'];
                }
                $result = [
                    'goods'     => $data
                ];
                $this->success('请求成功!', $result);
            }

        }
    }
	/**
     * 获取指定的文章
     * @param int $id
     */
    public function getSku()
    {
        $goods_id = $this->request->param('goods_id', 0, 'intval');

        if (intval($goods_id) === 0) {
            $this->error('无效的产品id！');
        } else {


           $goods_logic = new GoodsLogic;
           $specList    = $goods_logic->getSpecList($goods_id);
           $goodssku = Db::name('GoodsSku')->field('item_path,sku_id,price,store_count')->where('goods_id',$goods_id)->select();

           if($goodssku){

                $specList = [
                    'spec_list' => $specList,
                    'sku_list'  => $goodssku
                ];
           		$this->success('ok',$specList);
           }else{
           		$this->error('无规格');
           }
        }
    }

    public function order(){
        $id = $this->request->get('id', 0, 'intval');

        if (intval($id) === 0) {
            $this->error('无效的产品id！');
        } else {
            $where = [
                's.goods_id'        => $id,
                'o.order_status'    => 1
            ];
            $join = [
                ['__ORDER__ o','s.order_id = o.order_id'],
                ['__USER__ u','u.id = o.user_id']
            ];
            $list = DB::name('order_sub')->alias('s')->field('s.order_id,o.add_time,u.user_nickname,u.avatar')->join($join)->where($where)->limit(10)->select()->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['add_time'] = date('Y-m-d H:i',$value['add_time']);
                $list[$key]['avatar'] = cmf_get_image_url($value['avatar']);
            }
           $this->success('请求成功!', $list);


        }
    }

    

    public function getComment(){

        $id = $this->request->param('id',0,'intval');
        $GoodsCommentModel = new GoodsCommentModel;
        $where = ['goods_id'=>$id];
        $articles        = $GoodsCommentModel->relation('user')->where($where)->order('id', 'DESC')->paginate(10);

        if ($articles->isEmpty()) {
            $this->error('数据为空');
        } else {
            $this->success('获取成功!', $articles);
        }
    }
    /**
     * 获取分类列表
     */
    public function getCategory(){
        $catrgory=new GoodsCategoryModel;
        $order=[
            'listorder'=>'asc',
            'id'       =>'asc'
        ];
        $cat_list=$catrgory->where(['parent_id'=>0,'is_show'=>1])->order($order)->select();
        $response['cat_list'] = $cat_list;
    }

}
