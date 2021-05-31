<?php
// +----------------------------------------------------------------------
// +内容查询MODEL
// +----------------------------------------------------------------------
namespace plugins\modules\model;

use think\Model;

class ContentModel extends Model
{

    /**
     * 获取内容列表
     */
    public function getContent($filter, $table_name, $control)
    {
        $control_search = isset($control['control_search'])? $control['control_search'] : '';
        $control_list   = isset($control['control_list'])? $control['control_list'] : '';
        $control_edit_key   = isset($control['control_edit']) && is_array($control['control_edit']) ? array_column($control['control_list'],'name') : [];

        $model = $this->table($table_name);
        $admin_role_id = session('admin_role_id');
        if($admin_role_id != 1){
            $admin_id = cmf_get_current_admin_id();
            $model->where('editorid', '=',$admin_id);
        }
        //搜索-条件组合
        if($control_search){
            foreach($control_search as $v){
                if(isset($filter[$v['name']]) && !empty($filter[$v['name']])){
                    //未选择时-根据编辑配置自动选择执行哪种查询
                    if(empty($v['type']) || $v['type']=='auto'){
                        if(isset($control_edit_key[$v['name']])){
                            switch($control_edit_key[$v['name']]){
                                case 'textarea':
                                case 'editor':
                                    $v['type'] = 'like';break;
                                case 'checkbox':
                                    $v['type'] = 'in';break;
                                case 'ctime':
                                    $v['type'] = 'ctime';break;
                                case 'cdate':
                                    $v['type'] = 'cdate';break;
                                case 'citys':
                                    $v['type'] = 'match';break;
                                default:
                                    $v['type'] = '';break;
                            }
                        }
                        if($v['type']==''){
                            if( is_array($filter[$v['name']]) ){
                                $v['type'] = 'in';
                            }elseif( ctype_digit($filter[$v['name']]) ){
                                $v['type'] = 'eq';
                            }else{
                                $v['type'] = 'like';
                            }
                        }
                        $v['type'] = is_array($filter[$v['name']]) ? 'in' : 'eq';
                    }

                    switch($v['type']){
                        case 'eq':
                        case 'category':
                            $model->where($v['name'], '=',$filter[$v['name']]);
                            break;
                        case 'neq':
                            $model->where($v['name'], '<>', $filter[$v['name']]);
                            break;
                        case 'in':
                            $model->where($v['name'], 'in', $filter[$v['name']]);
                            break;
                        case 'like':
                            $model->where($v['name'], 'like', "%{$filter[$v['name']]}%");
                            break;
                        case 'match':
                            $this->where(" MATCH ({$v['name']}) AGAINST ({$filter[$v['name']]} IN BOOLEAN  MODE) ");
                            break;
                        case 'ctime':
                            if(is_array($filter[$v['name']])){
                                $start = isset($filter[$v['name']][0])? $filter[$v['name']][0]: 0;
                                $end = isset($filter[$v['name']][1])? $filter[$v['name']][1]: time();
                            }elseif(!empty($filter[$v['name']])){
                                $start = strtotime($filter[$v['name']]);
                                $end   = $start+24*3600;
                            }
                            if(isset($start) && isset($end)){
                                $start = is_numeric($start) ? $start : strtotime($start);
                                $end = is_numeric($end) ? $end : strtotime($end);
                                $this->where(" {$v['name']} between  {$start} and {$end}");
                            }
                            break;
                        case 'cdate':
                            $model->where($v['name'], '=', intval(str_replace('-','',$filter[$v['name']])));
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        if(isset($filter['itemid']) && $filter['itemid']){
            $model->where('itemid', '=', $filter['itemid']);
        }

        //列表
        /*$order = [];
        if($control_list){
            foreach($control_list as $v){
                if($v['name']=='id'){
                     $o_id = 'id';
                }
                if($v['name']=='itemid'){
                     $o_id = 'itemid';
                }
                if($v['name']=='list_order'){
                     $order['list_order'] = 'asc';
                }
            }
        }*/
        $order=[
            'list_order' => 'asc',
            'id'         => 'desc'
        ];
        $model = $model->order($order);

        //分页
        $limit = isset($filter['listRows']) ? $filter['listRows'] : 20;
        $data = $model->paginate($limit);
        return $data;
    }


    /**
     * 根据文章ID获取内容
     * @desc 如果是主表，则还需获取关联表内容
     * @param $id  主键ID
     */
    public function getEditContent($modules_id, $id, $table_name, $is_main_table){
        $table_datas = $this->name('plugin_modules_tables')->field('pinyin,table_name,relate_level')->where('modules_id',$modules_id)->select();
        $datas = [];
        $where = [];
        $admin_role_id = session('admin_role_id');
        if($admin_role_id != 1){
            $admin_id = cmf_get_current_admin_id();
            $where['editorid']  = $admin_id;
        }
        foreach($table_datas as $k=>$v){
            if($is_main_table){//如果是主表，则还需获取一对一关联表内容
                if($v['pinyin']==''){
                    $datas[$v['table_name']] = $this->table($v['table_name'])->where('id',$id)->find();
                }elseif($v['relate_level']==1){
                    $datas[$v['table_name']] = $this->table($v['table_name'])->where('itemid',$id)->find();
                }
            }elseif($table_name==$v['table_name']){
                $datas[$v['table_name']] = $this->table($v['table_name'])->where('id',$id)->where($where)->find();
            }
            if(isset($datas[$v['table_name']]) && is_object($datas[$v['table_name']]))  $datas[$v['table_name']] = $datas[$v['table_name']]->toArray();
        }

        return $datas;
    }

    /**
     * 标签调用获取内容
     * @desc 只返回一条数据
     * @param $module 模块拼音或者ID
     * @param level  查询的关联级别：0表示只查主表，1表示查主表和一对一关联的所有表数据，2（默认）查所有模块表数据
     * @param $where 外部传入的条件
     * @param cache 缓存时间
     */
    public function getList($modules_id, $level=0, $where=[], $cache=0){
        if($cache){
            $cachekey = $modules_id.'_'.$level.md5($where);
            $data = cache($cachekey);
            if(!empty($data)) return $data;
        }

        if(is_numeric($modules_id)){
            $module = $this->name('plugin_modules')->where('id', intval($modules_id))->cache(true)->find();
        }else{
            $module = $this->name('plugin_modules')->where('pinyin', $modules_id)->cache(true)->find();
        }
        if(empty($module)) return [];
        //查询模块的所属表
        $tables = $this->name('plugin_modules_tables')->where('modules_id', $module['id'])->order('relate_level asc')->cache(true, 60)->select()->toArray();
        //查询内容
        foreach($tables as $table){
            if($table['relate_level']=='0'){
                $model = $this->table($table['table_name'])->alias($module['pinyin'])->field($module['pinyin'].'.*');
            }elseif(isset($model) && $level>0 && $table['relate_level']==1){
                $control_edit = json_decode($table['control_edit'], true);
                foreach($control_edit as $edit_v){
                    $model->field($table['pinyin'].'.'.$edit_v['name'].' as '.$table['pinyin'].'000'.$edit_v['name']);
                }
                $model->join("{$table['table_name']} {$table['pinyin']}","{$module['pinyin']}.id = {$table['pinyin']}.itemid",'LEFT');
            }
        }
        //外部传入条件
        if(!empty($where)){
            if(is_string($where) && strpos($where,'{')===0) $where = json_decode($where, true);
            $model->where($where);
        }
        $data = $model->find()->toArray();

        //一对多单独查询
        $more = [];
        if($level=='2'){
            foreach($tables as $table){
                if($table['relate_level']!='2') continue;
                $control_list = json_decode($table['control_list'], true);
                if(empty($control_list)) continue;
                $field = array_column($control_list, 'name');
                $field[] = 'itemid';
                $more[$table['pinyin']] = $this->table($table['table_name'])->field(implode(',',$field))->where('itemid', $data['id'])->select()->toArray();
            }
        }

        //处理结果
        foreach($data as $f=>$v){
            if(strpos($f,'000')!==false){
                $farr = explode('000',$f);
                $data[$farr[0]][$farr[1]] = $v;
                unset($data[$f]);
            }
        }
        //一对多结果合并
        if(!empty($more))
        foreach($more as $pinyin=>$m){
            foreach($m as $v){
                if($data['id'] == $v['itemid']){
                    $data[$pinyin][] = $v;
                }
            }
        }
        //结果缓存
        if($cache && isset($cachekey)){
            cache($cachekey, $data);
        }

        return $data;
    }


    /**
     * 标签调用获取内容
     * @desc （三、四维返回版）一对多的表不支持条件查询;一对一根据编辑配置字段返回，一对多根据列表字段返回。
     * @param $module 模块拼音或者ID
     * @param level  查询的关联级别：0表示只查主表，1表示查主表和一对一关联的所有表数据，2（默认）查所有模块表数据
     * @param $where 外部传入的条件
     * @param cache 缓存时间
     */
    public function getLists($modules_id, $level=0, $where=[], $page=1, $limit=1, $cache=0){
        if($cache){
            $cachekey = $modules_id.'_'.$level.'_'.$page.'_'.$limit.md5($where);
            $datas = cache($cachekey);
            if(!empty($datas)) return $datas;
        }

        if(is_numeric($modules_id)){
            $module = $this->name('plugin_modules')->where('id', intval($modules_id))->cache(true)->find();
        }else{
            $module = $this->name('plugin_modules')->where('pinyin', $modules_id)->cache(true)->find();
        }
        if(empty($module)) return [];
        //查询模块的所属表
        $tables = $this->name('plugin_modules_tables')->where('modules_id', $module['id'])->order('relate_level asc')->cache(true, 60)->select()->toArray();
        //查询内容
        foreach($tables as $table){
            if($table['relate_level']=='0'){
                $model = $this->table($table['table_name'])->alias($module['pinyin'])->field($module['pinyin'].'.*');
            }elseif(isset($model) && $level>0 && $table['relate_level']==1){
                $control_edit = json_decode($table['control_edit'], true);
                foreach($control_edit as $edit_v){
                    $model->field($table['pinyin'].'.'.$edit_v['name'].' as '.$table['pinyin'].'000'.$edit_v['name']);
                }
                $model->join("{$table['table_name']} {$table['pinyin']}","{$module['pinyin']}.id = {$table['pinyin']}.itemid",'LEFT');
            }
        }
        //外部传入条件
        if(!empty($where)){
            if(is_string($where) && strpos($where,'{')===0) $where = json_decode($where, true);
            $model->where($where);
        }
        //外部传入分页
        if($limit>1){
            $datas = $model->limit(($page-1)*$limit, $limit)->select()->toArray();
        }else{
            $datas = $model->limit(1)->select()->toArray();
        }

        //一对多单独查询
        $more = [];
        if($level=='2'){
            $ids = array_column($datas, 'id');
            foreach($tables as $table){
                if($table['relate_level']!='2') continue;
                $control_list = json_decode($table['control_list'], true);
                if(empty($control_list)) continue;
                $field = array_column($control_list, 'name');
                $field[] = 'itemid';
                $more[$table['pinyin']] = $this->table($table['table_name'])->field(implode(',',$field))->where('itemid', 'in', $ids)->select()->toArray();
            }
        }

        //处理结果
        foreach($datas as $k=>$data){
            foreach($data as $f=>$v){
                if(strpos($f,'000')!==false){
                    $farr = explode('000',$f);
                    $datas[$k][$farr[0]][$farr[1]] = $v;
                    unset($datas[$k][$f]);
                }
            }
            //一对多结果合并
            if(!empty($more))
            foreach($more as $pinyin=>$m){
                foreach($m as $v){
                    if($data['id'] == $v['itemid']){
                        $datas[$k][$pinyin][] = $v;
                    }
                }
            }
        }
        //结果缓存
        if($cache && isset($cachekey)){
            cache($cachekey, $datas);
        }

        return $datas;
    }

}
