<?php
// +----------------------------------------------------------------------
// | 城市信息类
// +----------------------------------------------------------------------
namespace plugins\modules\model;

use think\Model;

class PluginModulesCitysModel extends Model
{
    /**
     * 获取城市列表
     */
    public function getList($filter){
        $where = [];

        $where_field = 'id,code,pid,level';
        $where_like_field = 'pinyin,name,areaname,lat,lng,is_hot';//like字段
        $where_findinset_field = '';//全文索引字段
        if(count($filter)){
            //等于
            $where_field = explode(',',$where_field);
            foreach($where_field as $v){
                if ( isset($filter[$v]) && $filter[$v]) {
                    $where[] = [$v,'=',$filter[$v]];
                }
            }
            //模糊
            $where_like_field = explode(',',$where_like_field);
            foreach($where_like_field as $v){
                if (isset($filter[$v]) && $filter[$v]) {
                    $where[] =  [$v,'like', "%{$filter[$v]}%"];
                }
            }
            //全文索引
            if($where_findinset_field){
                $where_findinset_field = explode(',',$where_findinset_field);
                foreach($where_findinset_field as $v){
                    if (isset($filter[$v]) && $filter[$v]) {
                        $this->where("MATCH ({$v}) AGAINST ({$filter[$v]} IN BOOLEAN  MODE)");
                    }
                }
            }
        }

        $limit = isset($filter['listRows'])?$filter['listRows']:30;
        return $this->where($where)->order('is_hot', 'desc')->order('id', 'asc')->paginate($limit);
    }



    //获取省份
    public function getProv(){
        return $this->field('id,level,name')->where('id<100')->where('level=1')->cache(true)->select();
    }


    //获取所有下级城市
    public function getChildren($pid){
        return $this->field('id,level,name')->where('pid='.$pid)->select();

    }


    //根据城市ID获取级别
    public function getLevelById( $id ){
        if($id<100) return 1;
        if($id<10000) return 2;

        return 3;
    }

}