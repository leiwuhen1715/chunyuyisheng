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
use api\home\model\SlideModel;
use cmf\controller\RestBaseController;
use api\goods\model\GoodsCategoryModel;
use api\goods\model\GoodsModel as Goods;

class ListController extends RestBaseController
{
    // api 首页

    public function index(){

        $input     =  $this->request->param();
        $id        =  $this->request->param('category_id', 0, 'intval');
        $brand_id  =  $this->request->param('brand_id', 0, 'intval');
        $recommend =  $this->request->param('recommend', 0, 'intval');
        $new       =  $this->request->param('new', 0, 'intval');
        $limit     =  $this->request->param('limit', 10, 'intval');
        $hot       =  $this->request->param('hot', 0, 'intval');
        $current   =  $this->request->param('current',0,'intval');
        $special   =  $this->request->param('special', 0, 'intval');
        $keyword   =  $this->request->param('keyword');
        $order     =  $this->request->param('order');
        $sort      =  $this->request->param('sort');

        $goods                 = new Goods();

        $limit = empty($limit)?10:$limit;
        
        $goods                 = new Goods();
        $where = [];

        if($id){
          
            $c_where = ['parent_id'=>$id,'is_show'=>1];
            $cat_list = Db::name('goods_category')->where($c_where)->column('id');
            if(empty($cat_list)){
                $where[] = ['cat_id','=',$id];
            }else{
                $cat_list[] = $id;
                $where[] = ['cat_id','in',$cat_list];
            }
            
        }
        
        if($recommend)    $where[]  = ['is_recommend','=',1];
        if($special)      $where[]  = ['is_special','=',1];
        if($keyword)      $where[]  = ['goods_name','like',"%".$keyword."%"];
        $asc = $sort=='-'?'asc':'desc';
        switch ($order) {
            case 'shop_price':
                $goods_order = ['shop_price' => $asc,'goods_id'   => 'desc'];
                break;
            case 'sales_sum':
                $goods_order = ['sales_sum'  => $asc,'goods_id'   => 'desc'];
                break;
            case 'update_time':
                $goods_order = ['update_time' => $asc,'goods_id'   => 'desc'];
                break;
            default:
                $goods_order = ['list_order'  => 'asc','goods_id'   => 'desc'];
                ;
                
        }
        
        $data = $goods->field('goods_id,goods_img,goods_name,shop_price,market_price,sales_sum')->where($where)->order($goods_order)->paginate($limit);
        $list = $data->items();

        $this->success('产品获取成功!', $list);


    }
    // api 首页

    public function goodsList(){

        $input     =  $this->request->param();
        $id        =  $this->request->param('category_id', 0, 'intval');
        $brand_id  =  $this->request->param('brand_id', 0, 'intval');
        $recommend =  $this->request->param('recommend', 0, 'intval');
        $new       =  $this->request->param('new', 0, 'intval');
        $limit     =  $this->request->param('limit', 10, 'intval');
        $hot       =  $this->request->param('hot', 0, 'intval');
        $current   =  $this->request->param('current',0,'intval');
        $special   =  $this->request->param('special', 0, 'intval');
        $keyword   =  $this->request->param('keyword');
        $order     =  $this->request->param('order');
        $sort      =  $this->request->param('sort');

        $goods                 = new Goods();

        if (!empty($input['page'])) {
            $map['page'] = $input['page'];
        }
        $limit = empty($limit)?10:$limit;
        $map['limit'] = $limit;
        $cate_info=[];
        $map['where']=[];
        $map['field']=['goods_id,goods_name,cat_id,is_recommend,goods_img,photo,market_price,shop_price,goods_remark,sales_sum,click_count,store_count'];
        if($id){
            $where = ['parent_id'=>$id,'is_show'=>1];
            $cat_list = Db::name('goods_category')->where($where)->column('id');
            if(empty($cat_list)){
                $map['where']['cat_id']=$id;
            }else{
                $cat_list[] = $id;
                $map['where']['cat_id']=['in',$cat_list];
            }

        }

        if($recommend){
            $map['where']['is_recommend']=1;
        }
        if($hot){
            $map['where']['is_hot']=1;
        }
        if($special){
            $map['where']['is_special']=1;
        }
        if($brand_id){
            $map['where']['brand_id']=$brand_id;
        }

        if($keyword){
            $map['where']['goods_name']=['like',"%".$keyword."%"];
        }

        /*if(empty($order))$order  = 'list_order';
      	if($order == 'update_time')$order='last_update';
        if($sort != '+')$sort    = '-';
        $map['order']            = "$sort"."$order,-goods_id";*/
        $map['order']            = "+list_order,-goods_id";
        switch ($current) {
            case '1':
                $map['order']            = "-last_update,-goods_id";
                break;
            case '2':
                $map['order']            = "-click_count,-goods_id";
                break;
        }

        $data = $goods->getDatas($map);
        $response = $data;

        $this->success('产品获取成功!', $response);


    }



}
