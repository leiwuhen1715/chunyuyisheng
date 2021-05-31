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
use api\goods\model\SeckillModel;
use api\goods\logic\GoodsLogic;
use api\goods\model\GoodsCommentModel;

class SeckillController extends RestBaseController
{
    
    
    // api 首页

    public function index()
    {
    	$model = new SeckillModel;
        $limit = request()->param('limit',10,'intval');
        $ids[]   = request()->param('ids');

    	$order=[
    		'list_order'=>'asc',
    		'id'	   =>'desc'
    	];

        $field = 'id,goods_id,goods_name,goods_img,shop_price,ot_price,start_time,end_time,add_time';
        //->where('id','in',($ids[0]))
    	$data  = $model->field($field)->order($order)->paginate($limit);
        $list = $data->items();

        $this->success('请求成功',$list);

    }


    public function getTimes()
    {
        $times =  SeckillModel::field('id,start_time,end_time')->select();

        $this->success('请求成功',$times);
    }
    
    
    
    //分时段查询
    public function seckillList()
    {
        
        $type  = $this->request->param('type',0,'intval');
        $page  = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',10,'intval');

        empty($page) ? 1 : $page;
        empty($limit) ? 10 : $limit;
        
        $time_arr = ['09','12','14','20','00'];
        $end_arr = [2,2,2,2,3];
        
        $start_time = strtotime(date("Y-m-d",time()).' '.$time_arr[$type].':00');
        $end_time   = $start_time + $end_arr[$type]*3600;
        
        $model = new SeckillModel;
        $order=['start_time'=>'asc','list_order'=>'asc','id'=>'desc'];
        $field = 'id,goods_id,goods_name,goods_img,shop_price,ot_price,start_time,end_time,add_time,store_count,sales_sum';
    	$data  = $model->field($field)->where('start_time','between',[$start_time, $end_time])->page($page,$limit)->order($order)->select();
        //$list = $data->items();

        $this->success('请求成功',$data);

    }
    
    


    /*
    // api 首页
    public function index()
    {
    	$model = new SeckillModel;
        $limit = request()->param('limit',10,'intval');
        $where = [];
    	$order=[
    		'list_order'=>'asc',
    		'id'	   =>'desc'
    	];
        $field = 'id,goods_id,goods_name,id,photo,goods_img,shop_price,ot_price,start_time,end_time';

    	$data  = $model->field($field)->where($where)->order($order)->paginate($limit);
        $list = $data->items();
        $this->success('ok!', $list);

    }*/
    

    /**
     * 获取指定的文章
     * @param int $id
     */
    public function detail()
    {
        $id = $this->request->get('id', 0, 'intval');

        if (intval($id) === 0) {
            $this->error('无效的产品id！');
        } else {
            $params                       = $this->request->get();

            $model = new SeckillModel;
            $data  = $model->where('id',$id)->find();
            $model->where('id', $id)->setInc('click_count');

            if (empty($data)) {
                $this->error('产品不存在！');
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
}
