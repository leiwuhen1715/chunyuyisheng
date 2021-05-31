<?php

namespace api\goods\logic;

use think\Db;
use think\Model;
use think\model\Relation;

class GoodsLogic extends Relation
{
  public function getSpecList($goods_id){
    //详细页获取规格
    //规格
    $sku = DB::name('GoodsSku')->where("goods_id",$goods_id)->value("GROUP_CONCAT(`item_path` SEPARATOR '-') AS items_id");
    $sku = array_unique(explode('-', $sku));

    $spec_item = DB::name('SpecItem')->order("item_order","ASC")->select();
    $spec_img = DB::name('SpecImg')->where("goods_id",$goods_id)->select();

    $arr = array();
    foreach($spec_item as $v){
      if(in_array($v['id'],$sku)){
        $key = $this->getSpecName($goods_id,$v['spec_id']);
        $arr[$v['spec_id']]['name'] = $key;
        $arr[$v['spec_id']]['item_list'][] = [
          "item_id" => $v['id'],
          "item"    => $v['item'],
          'selected'	=> false
        ];
        /*$arr[$key][] = [
          "item_id" => $v['id'],
          "item"    => $v['item'],
          'flag'	=> false
           "img"     => $this->getItemImg($goods_id,$v['id'])
        ];*/

      }
    }
    $new_arr = [];
    foreach($arr as $key=>$vo){
      $new_arr[] = $vo;
    }
    return $new_arr;
  }
  /**
   * 获取规格项图片
   * @$goods_id 产品id
   * @$item_id 规格项id
  */
  public function getItemImg($goods_id,$item_id){
  	$img = Db::name('Spec_img')->where(["goods_id"=>$goods_id,"spec_item_id"=>$item_id])->find();
  	return $img['img'];
  }

  /**
   * 获取规格名称
   * @$goods_id 产品id
   * @$spec_id 规格id
  */
  public function getSpecName($goods_id,$spec_id){
  	$spec = Db::name('Spec')->where('id',$spec_id)->find();
  	return $spec['spec_name'];
  }

  /**
   * 获取产品库存
   * @param type $goods_id 产品id
   * @param type $key  库存规格规则
   */
  public function getGoodNum($goods_id,$key)
  {
      if(!empty($key)){
          return  Db::name("GoodsSku")->where(["goods_id"=>$goods_id,"item_path"=>$key])->value('store_count');
      }else{
          return  Db::name("Goods")->where(["goods_id"=>$goods_id])->value('store_count');
  	}
  }
}
