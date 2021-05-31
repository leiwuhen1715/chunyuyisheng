<?php

namespace app\goods\logic;

use think\Db;
use think\Model;
use think\model\Relation;

class GoodsLogic extends Relation
{
  public function getSpecInput($goods_id, $spec_arr)
    {
        $spec_arr_sort=[];
        $spec_arr2    =[];
        // 排序
        foreach ($spec_arr as $k => $v)
        {
            $spec_arr_sort[$k] = count($v);
        }
        //asort($spec_arr_sort);        
        foreach ($spec_arr_sort as $key =>$val)
        {
            $spec_arr2[$key] = $spec_arr[$key];
        }
     
         $clo_name = array_keys($spec_arr2); 
       
         $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积                 
          
         $spec = DB::name('Spec')->column('id,spec_name'); // 规格表
          
         $shop_price = DB::name('goods')->where('goods_id',$goods_id)->value('shop_price');
         $specItem   = DB::name('SpecItem')->column('id,item,spec_id');//规格项
         $goods_sku  = DB::name('GoodsSku')->where('goods_id',$goods_id)->column('item_path,price,store_count,sku,sku_id');//规格项
        

       $str = "<table class='table table-bordered layui-table' id='spec_input_tab' lay-filter='parse-table-demo'>";
       $str .="<thead><tr>";       
       // 显示第一行的数据
   
        foreach ($clo_name as $k => $v) 
        {
           $str .=" <td><b>{$spec[$v]}</b></td>";
        }    
        $str .="<td><b>编码</b></td>
        		<td><b>价格</b></td>
               <td><b>库存</b></td>              
             </tr></thead><tbody>";

       // 显示第二行开始 

       foreach ($spec_arr2 as $k => $v) 
       {
            $str .="<tr>";
            $item_key_name = array();
            
            foreach($v as $k2 => $v2)
            {
                $str .="<td>".$specItem[$v2]['item']."</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }   
            ksort($item_key_name);            
            $item_key = implode('-', array_keys($item_key_name));
            $item_sku = implode('0', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);
            if(empty($goods_sku[$item_key])){
              $goods_sku[$item_key]['price']        = $shop_price; // 价格默认为0
              $goods_sku[$item_key]['sku'] =  '0'.$goods_id.'0'.$item_sku;
              $goods_sku[$item_key]['store_count']  = 100; //库存默认为0
              $goods_sku[$item_key]['sku_id']  = 0; //库存默认为0

            }else{
              $goods_sku[$item_key]['price'] ? false : $goods_sku[$item_key]['price'] = $shop_price; // 价格默认为0
              $goods_sku[$item_key]['store_count'] ? false : $goods_sku[$item_key]['store_count'] = 100; //库存默认为0
              $goods_sku[$item_key]['sku'] ? false : $goods_sku[$item_key]['sku'] = '0'.$goods_id.'0'.$item_sku; //库存默认为0
              //$goods_sku[$item_key]['sku'] =  '0'.$goods_id.'0'.$item_sku;
              
            }
            
      			$str .="<td><input type='hidden' name='item[sku_id][$item_key]' value='{$goods_sku[$item_key]['sku_id']}'/><input class='layui-input' name='item[sku][$item_key]' value='{$goods_sku[$item_key]['sku']}' /></td>";
            $str .="<td><input class='layui-input' name='item[price][$item_key]' value='{$goods_sku[$item_key]['price']}' /></td>";
            $str .="<td><input class='layui-input' name='item[store_count][$item_key]' value='{$goods_sku[$item_key]['store_count']}'/></td>";            
            $str .="</tr>";           
          }
        $str .= "</tbody></table>";
       return $str;   
    }
  
  public function getSpecList($goods_id){
    //详细页获取规格
    //规格
    $sku = DB::name('GoodsSku')->where(array("goods_id"=>$goods_id))->value("GROUP_CONCAT(`item_path` SEPARATOR '-') AS items_id");
    $sku = array_unique(explode('-', $sku));
    
    $spec_item = DB::name('SpecItem')->order(array("item_order ASC"))->select();
    $spec_img = DB::name('SpecImg')->where(array("goods_id"=>$goods_id))->select();
    
    $arr = array();
    foreach($spec_item as $v){
      if(in_array($v['id'],$sku)){
        $arr[getSpecName($goods_id,$v['spec_id'])][] = array("item_id"=>$v['id'],"item"=>$v['item'],"img"=>getItemImg($goods_id,$v['id'])); 
          
      }
    }
    return $arr;
  }
}