<?php
// +----------------------------------------------------------------------
// | 模块管理 和 新模块管理 的MODEL类
// +----------------------------------------------------------------------
namespace plugins\modules\model;

use app\admin\model\AdminMenuModel;
use think\Model;

class PluginModulesModel extends Model
{

    public $nodeId = 0;//内容管理根节点
    public $modulesId = 0;//模块ID

    /**
     * 获取所属根节点,这里命名为【内容管理】
     * @desc 由于每添加一个新模块将会生成一个新菜单，将统一放到该根节点下
     */
    public function getContentNodeId(){
        if(!$this->nodeId){
            $adminMenuModel = new AdminMenuModel();
            $this->nodeId   = $adminMenuModel->where(['parent_id'=>0, 'name'=>'内容管理'])->value('id');
        }
        if(empty($this->nodeId)){
            $this->nodeId = $adminMenuModel->insert([
                'parent_id' =>0,
                'app'       =>'plugin/modules',
                'controller'=>'content',
                'action'    =>'index',
                'name'      =>'内容管理',
                'status'    =>1,
            ], false, true);
        }
        return $this->nodeId;
    }

    /**
     * 添加一个菜单
     */
    public function add_menu( $data ){
        $adminMenuModel = new AdminMenuModel();
        //添加数据
        $adminMenuModel->strict(false)->field(true)->insert($data);
        //添加权限
        //cmf这里有问题，规则验证重复时应该增加param字段验证。所以如需单个模块不同权限，请自行修改
        /*$app          = $data["app"];
        $controller   = $data["controller"];
        $action       = $data["action"];
        $authRuleName = "$app/$controller/$action";
        $param = (isset($data["param"]) && $data["param"])?$data["param"]:'';*/
        //检查是否存在
        /*$findAuthRuleCount = $adminMenuModel->name('auth_rule')->where([
            'app'  => $app,
            'name' => $authRuleName,
            'param' => $param,
            'type' => 'admin_url'
        ])->count();*/

        //添加规则
        /*if (empty($findAuthRuleCount)) {
            $adminMenuModel->name('auth_rule')->insert([
                "name"  => $authRuleName,
                "app"   => $app,
                "type"  => "admin_url", //type 1-admin rule;2-user rule
                "title" => $data["name"],
                'param' => $param
            ]);
        }*/
    }

    /**
     * 删除一个模块菜单
     */
    public function del_menu($modules_id){
        $adminMenuModel = new AdminMenuModel();
        $adminMenuModel->where([
            'parent_id'=>$this->getContentNodeId(),
            'app'       =>'plugin/modules',
            'controller'=>'content',
            'action'    =>'index'])
            ->where('param', 'like', 'modules_id='.$modules_id)->delete();
    }

    /**
     * 获取表的拼音标识
     * @param $table_name 表全名
     * @return pinyin 该表的拼音标识
     */
    public function getPinyin($table_name){
        $arr = explode('_',$table_name,4);
        if(isset($arr[3])){
            return $arr[3];
        }else{
            return '';//主表
        }
    }

    /**
     * 获取模块建表的规则（内容表前缀）
     * @param $modules_pinyin 模块拼音
     * 内容表规则如：cmf_item_{modules.pinyin}_{pinyin}
     * @return string 前缀部分|主表
     */
    public function getTableRule($modules_pinyin=''){
        $prefix = config('database.prefix');
        if($modules_pinyin){
            return $prefix.'item_'.$modules_pinyin;
        }elseif($pinyin = $this->getAttr('pinyin')){
            return $prefix.'item_'.$pinyin;
        }else{
            return $prefix.'item_';
        }
    }


    /**
     * 真实获取某个模块下的所有表
     * @return [[Name,Comment,pinyin,Auto_increment,Engine,Rows,Create_time]]
     */
    public function getTables($cache = false){
        if($cache){
            $rs = cache('PluginModulesModel.getTables'. $this->id);
            if(!empty($rs)) return $rs;
        }

        $tableList = $this->query("show table status like '{$this->getTableRule()}%'");

        //查询配置表
        $tables = $this->name('plugin_modules_tables')->where('modules_id', $this->id)->field('table_name,relate_field,relate_level,remark')->select();
        $table_tmp = [];
        foreach($tables as $v){
            $table_tmp[$v['table_name']] = $v;
        }

        $rs = [];
        foreach($tableList as $v){
            $v['pinyin'] = $this->getPinyin($v['Name']);
            if(isset($table_tmp[$v['Name']])){
                $v['relate_field'] = $table_tmp[$v['Name']]['relate_field'];
                $v['relate_level'] = $table_tmp[$v['Name']]['relate_level'];
                $v['remark'] = $table_tmp[$v['Name']]['remark'];
            }
            $rs[$v['Name']] = $v;
        }

        if($cache) cache('PluginModulesModel.getTables'. $this->id, $rs, 60);
        return $rs;
    }


    /**
     * 追加保存设置
     * @desc 主要针对序列化的字段，里的一二三级数组的值保存更新。
     * @param $modules_id
     * @param $content 设置的值
     * @param $field 获取的mysql根字段
     * @param $node_one 字段值内部数组一维key
     */
    public function saveControlNode($modules_id, $content, $field, $node_one='', $node_two='', $node_three=''){
        $data = $this->where('id',$modules_id)->value($field);//取出字段值
        //整合
        if(!empty($data)){
            $data = unserialize($data);
            if($node_three){
                if(!isset($data[$node_one][$node_two][$node_three])) $data[$node_one][$node_two][$node_three] = '';
                $tmp = &$data[$node_one][$node_two][$node_three];
            }elseif($node_two){
                if(!isset($data[$node_one][$node_two])) $data[$node_one][$node_two] = '';
                $tmp = &$data[$node_one][$node_two];
            }elseif($node_one){
                if(!isset($data[$node_one])) $data[$node_one] = '';
                $tmp = &$data[$node_one];
            }
            if(is_array($tmp) && is_array($content)){
                $tmp = array_merge($tmp,$content);
            }else{
                $tmp = $content;
            }
        }
        return $this->where('id', $modules_id)->update([$field=>serialize($data)]);//保存
    }

    /**
     * 真实获取某个表下的所有字段信息
     * @desc 并按配置的顺序返回
     * @param $table_name  表全名
     * @param string $field 结果所需字段
     * @return [[name=>'id',comment=>'ID']]
     */
    public function getFields($table_name, $field='*'){
        if(empty($table_name)) return [];
        //$fields = $this->model->query("desc {$tablename}");
        $database = config('database.database');
        $sql = "select {$field} from information_schema.columns where table_schema = '{$database}' and table_name = '{$table_name}';";

        return $this->query($sql);
    }

    /**
     * 真实获取某个模块下所有表的所有字段名
     * @return [tableName=>[field=>'name']]
     */
    public function getAllFieldName($cache = false){
        if($cache){
            $rs = cache('PluginModulesModel.getAllFieldName'. $this->getTableRule());
            if(!empty($rs)) return $rs;
        }

        $database = config('database.database');
        $sql = "select TABLE_NAME,COLUMN_NAME,COLUMN_COMMENT  from information_schema.columns where table_schema = '{$database}'
        and table_name like '{$this->getTableRule()}%' ";
        $datas = $this->query($sql);
        $rs = [];
        foreach($datas as $v){
            $rs[$v['TABLE_NAME']][$v['COLUMN_NAME']] = $v['COLUMN_COMMENT'];
        }

        if($cache) cache('PluginModulesModel.getAllFieldName'. $this->getTableRule(), $rs, 60);
        return $rs;
    }









    /**
     * 获取内容列表
     */
    public function getList($filter)
    {
        $main_table = $this->getTableRule();

        //搜索条件组合
        foreach($this->getAttr('search') as $table=>$data){
            foreach($data as $v){
                if(isset($filter[$v['name']]) && $filter[$v['name']]){
                    switch($v['type']){
                        case 'eq':
                            $this->where($table.'.'.$v['name'], '=',$filter[$v['name']]);
                            break;
                        case 'neq':
                            $this->where($table.'.'.$v['name'],'<>',$filter[$v['name']]);
                            break;
                    }
                }
            }
        }

        //根据列表配置-查询-所有表的结果集合
        $join = [];
        $fileds = '';
        foreach ($this->getAttr('list') as $table => $data) {
            if (empty($data)) continue;
            foreach ($data as $v) {
                $fileds .= $table . '.' . $v['name'] . ',';
            }
            if ($main_table == $table) {//检测是否是主表
                $this->table($main_table);
            } else {
                $join[] = [$table, $main_table.'.id = ' . $table . '.itemid', 'LEFT'];
            }
        }
        $fileds = rtrim($fileds, ',');
        $this->field($fileds);
        if (!empty($join)) {
            $this->join($join);
        }

        $limit = isset($filter['listRows']) ? $filter['listRows'] : 20;
        $data = $this->order($main_table.'.id desc')->paginate($limit);

        return $data;
    }


    /**
     * 根据文章ID获取内容
     * @param $itemid  文章ID
     */
    public function getEditContent($modules_id, $itemid){
        $table_datas = $this->name('plugin_modules_tables')->field('pinyin,table_name,relate_level')->where('modules_id',$modules_id)->select()->toArray();
        $datas = [];
        foreach($table_datas as $k=>$v){
            if($v['relate_level']=='0'){
                $datas[$v['table_name']] = $this->table($v['table_name'])->where('id',$itemid)->find()->toArray();
            }elseif($v['relate_level']=='1'){
                $datas[$v['table_name']] = $this->table($v['table_name'])->where('itemid',$itemid)->find()->toArray();
            }else{
                $datas[$v['table_name']] = $this->table($v['table_name'])->where('itemid',$itemid)->select()->toArray();
            }
        }

        return $datas;
    }


}