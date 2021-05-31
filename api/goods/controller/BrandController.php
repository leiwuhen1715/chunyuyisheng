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
use api\goods\model\GoodsBrandModel;

class BrandController extends RestBaseController
{
    // api 首页
    
    public function index(){

        $param     = $this->request->param();
        $type_id   = $this->request->param('type_id',0,'intval');
        $style_id  = $this->request->param('style_id',0,'intval');
        $limit     = $this->request->param('limit',10,'intval');
        $limit = empty($limit)?10:$limit;
        $field = "b.id,b.brandname,b.subname,b.logo,b.pic,b.address";
        $model = new GoodsBrandModel;
        if(empty($style_id) && empty($type_id)){

            $list = $model->alias('b')->field($field)->order('id','desc')->paginate($limit);
        }elseif(!empty($style_id) && empty($type_id)){
            $join = [
                ['brand_style s','s.bid = b.id']
            ];
            $list = $model->alias('b')->field($field)->join($join)->where('s.sid',$style_id)->order('s.id','desc')->paginate($limit);
        }elseif(empty($style_id) && !empty($type_id)){
            $join = [
                ['brand_type t','t.bid = b.id']
            ];
            $list = $model->alias('b')->field($field)->join($join)->where('t.tid',$type_id)->order('t.id','desc')->paginate($limit);

        }elseif(!empty($style_id) && !empty($type_id)){
            $join = [
                ['brand_style s','s.bid = b.id'],
                ['brand_type t','t.bid = s.bid']
            ];
            $where = [
                't.tid' => $type_id,
                's.sid' => $style_id
            ];
            $list = $model->alias('b')->field($field)->join($join)->where($where)->order('s.id','desc')->paginate($limit);
        }
        


        if ($list->isEmpty()) {
            $this->error('数据为空');
        } else {
            $data = $list->items();
            $this->success('ok!', $data);
        }

        
    }

    public function detail(){
        $brand_id = $this->request->param('id');
        $model = new GoodsBrandModel;
        $data = $model->where('id',$brand_id)->find();
        if($data){
            $this->success('ok!', $data);
        }else{
            $this->error('error'); 
        }
        
    }
    public function typeList(){
        $type_list = Db::name('goods_type')->field('id,name')->order('id','desc')->select();
        $this->success('ok',$type_list);
    }
    public function styleList(){
        $style_list = Db::name('goods_style')->field('id,stylename')->order('id','desc')->select();
        $this->success('ok',$style_list);
    }

}
