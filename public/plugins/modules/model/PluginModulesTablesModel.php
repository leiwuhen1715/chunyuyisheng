<?php
// +----------------------------------------------------------------------
// | 模块管理 - 表管理MODEL
// +----------------------------------------------------------------------
namespace plugins\modules\model;

use think\Model;

class PluginModulesTablesModel extends Model
{
    protected $type = [
        'control_edit'      =>  'json',
        'control_list'      =>  'json',
        'control_search'    =>  'json',
    ];

    //内容表的（固定）前缀
    public function getTablePrefix(){
        if(!isset($this->tablePrefix)){
            $this->tablePrefix = config("database.prefix").'item_';
        }
        return $this->tablePrefix;
    }

    /**
     * 控制操作 - 重新保存设置
     * @param $table_name 表名
     * @param $control_type 类型：edit|list|search
     * @param $content 保存内容
     * @return false|int
     */
    public function saveControl($table_name, $control_type, $content){
        //保存
        return $this->where('table_name',$table_name)->update(['control_'.$control_type=>json_encode($content)]);
    }

    /**
     * 表管理 - 创建表
     * @param $modules 模块数据,接收数组格式：['name'=>'', 'pinyin'=>'']
     * @param $tables_data 表数据,接收数组格式：['name'=>'', 'pinyin'=>'', 'remark'=>'']
     */
    public function add($modules, $tables_data = []){
        $modules_pinyin = trim($modules['pinyin']);
        $modules_name   = $modules['name'];
        $tables_pinyin  = isset($tables_data['pinyin'])? trim($tables_data['pinyin']): '';
        $tables_remark  = isset($tables_data['remark'])? $tables_data['remark']: $modules['name'];
        $tables_relate  = isset($tables_data['relate'])? intval($tables_data['relate']): 0;

        $add = [];

        //真实添加表
        if(empty($tables_data['pinyin'])){//主(初始)表
            $add['table_name'] = $this->getTablePrefix().$modules_pinyin;
            $sql = "CREATE TABLE IF NOT EXISTS `{$add['table_name']}` (
                    `id` int(10) NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='".$modules_name."表';";
            $this->execute($sql);
        }
        else{//创建自定义添加表
            $add['table_name'] = $this->getTablePrefix().$modules_pinyin.'_'.$tables_pinyin;
            $add['relate_field'] = 'itemid';//关联字段暂时定死为itemid
            if($tables_relate==2 ){//一对多
                $sql = "CREATE TABLE IF NOT EXISTS `{$add['table_name']}` (
                    `id` int(10) NOT NULL AUTO_INCREMENT,
                    `itemid`  int(10) NOT NULL COMMENT '主表ID' ,
                    PRIMARY KEY (`id`),
                    INDEX `index_itemid` (`itemid`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='".$tables_remark."';";
            }
            elseif($tables_relate==1){//一对一
                $sql = "CREATE TABLE IF NOT EXISTS `{$add['table_name']}` (
                    `itemid`  int(10) NOT NULL COMMENT '主表ID' ,
                    PRIMARY KEY (`itemid`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='".$tables_remark."';";
            }
            else{//不关联
                $sql = "CREATE TABLE IF NOT EXISTS `{$add['table_name']}` (
                    `id` int(10) NOT NULL AUTO_INCREMENT,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='".$tables_remark."';";
            }
            $this->execute($sql);
        }

        //添加行
        $add['modules_id']  = $modules['id'];
        $add['pinyin']      = $tables_pinyin;
        $add['remark']      = $tables_remark;
        $add['relate_level']= $tables_relate;
        $this->insert($add);

        return true;
    }

    /**
     * 表管理 - 删除表
     * @param $tablename 完整的表名
     * 删除module_tables里的一行
     */
    public function del($tablename){
        //删除表
        $this->execute('drop table if exists `'.$tablename.'`;');

        //删除module_tables里的一行
        return $this->where('table_name', $tablename)->delete();
    }



    /**
     * 根据真实表同步添加一条数据到ModuleTables表里
     * @param $modules_id 模块ID
     * @param $table_name 完整表名
     */
    public function real_sync_to_row($modules_id, $table_name){
        $add = [];
        $add['modules_id']  = $modules_id;
        $arr = explode('_',$table_name);
        $add['pinyin']      = isset($arr[3])? $arr[3]: '';
        $add['table_name']  = $table_name;

        if(!empty($add['pinyin'])){//附表
            $add['relate_field'] = 'itemid';
            $data = $this->query(" desc {$table_name}");
            foreach($data as $k=>$v){
                if($v['Field']=='id' && $v['Key']=='PRI'){
                    $add['relate_level'] = 2;//一对多
                }elseif($v['Field']=='itemid' && $v['Key']=='PRI'){
                    $add['relate_level'] = 1;//一对一
                }
            }
        }

        //添加行
        $this->insert($add);
    }

    /**
     * 创建模块 - 初始化快捷创建筛选自带表
     */
    public function addfast($param){
        //添加到行的格式
        $add = [
            'modules_id' => $param['id'],
            'pinyin'     => '',
            'table_name' => $this->getTablePrefix().$param['module']['pinyin'],
            'remark'     => $param['module']['name'],
            'relate_field' => '',//暂时定死为itemid
            'relate_level' => 0,//不关联
        ];

        foreach(\plugins\modules\lib\ControlForm::init()->tables_example as $table){
            //主表或者提交了其它表字段的表
            if(isset($param['post']['modules']) || isset($param['post'][$table['name']])) {
                $this->execute_insert_sql($add, $param, $table);
            }
        }
    }

    /**
     * 执行表创建SQL
     */
    private function execute_insert_sql($main, &$param, $table){
        if(!empty($table['name']) && (!isset($param['table'][$table['name']]) || $param['table'][$table['name']]!='on')){
            return false;//未启用的表
        }
        $main['pinyin']         = $table['name'];
        $main['table_name']    .= $table['name']=='' ? '' : ('_'.$table['name']);
        $main['remark']        .= $table['remark'];
        $main['relate_level']   = $table['relate'];
        $sql = [];
        $key = [];
        $post_table = empty($main['pinyin']) ? 'modules' : $main['pinyin'];
        $control_list = [];
        $control_edit = [];
        $control_search = [];
        foreach($param['post'][$post_table] as $field => $data){
            //如果有ID，则必须为自增主键
            if($data['name'] == 'id') {
                $key[] = " PRIMARY KEY (`id`),";
                $sql[] = "`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '{$data['name']}',";
                continue;
            }

            //未启用字段，配置也清除
            $must = (isset($table['fields'][$field]['level']) && $table['fields'][$field]['level']==0) ? true : false;
            if(empty($data['stat']) && !$must){
                continue;
            }
            //加入字段配置
            if(is_array($table['list'])){
                foreach($table['list'] as $klist => $vlist){
                    if($vlist['name'] == $data['name']) $control_list[] = $vlist;
                }
            }
            if(is_array($table['edit'])){
                foreach($table['edit'] as $klist => $vlist){
                    if($vlist['name'] == $data['name']) $control_edit[] = $vlist;
                }
            }
            if(is_array($table['search'])){
                foreach($table['search'] as $klist => $vlist){
                    if($vlist['name'] == $data['name']) $control_search[] = $vlist;
                }
            }


            //索引设置
            if(isset($data['key'])){
                switch($data['key']){
                    case 'PRIMARY':
                        $key[] = " PRIMARY KEY (`{$data['name']}`),";break;
                    case 'INDEX':
                        $key[] = " INDEX `{$data['name']}` (`{$data['name']}`),";break;
                    case 'UNIQUE':
                        $key[] = " UNIQUE INDEX `{$data['name']}` (`{$data['name']}`),";break;
                    case 'FULLTEXT':
                        $key[] = " FULLTEXT INDEX `{$data['name']}` (`{$data['name']}`),";break;
                    default:
                        break;
                }
            }
            //$data = array_merge(\plugins\modules\lib\ControlForm::init()->fields_default, $table['fields'][$field], $data);
            //字段SQL放循环底部
            $data['name']    = '`'. $data['name']. '`';
            if($data['type']=='float' || $data['type']=='double'){
                $type        = $data['type']. '('.$data['len']. ','.$data['float'].')';
            }else{
                $type        = $data['type']. '('.$data['len']. ')';
            }
            $data['null']    = isset($data['null'])     ? " NOT NULL " : ' NULL ';
            $data['default'] = !empty($data['default']) ? " DEFAULT '{$data['default']}'" : '';
            $data['alias']   = " COMMENT '".$data['alias']."'";
            $sql[] = $data['name']. $type. $data['null']. $data['default']. $data['alias'].',';
        }

        $sql = implode(' ', $sql);
        $key = !empty($key) ? implode(' ', $key) : '';
        $insertsql = $sql.$key;
        $insert = "CREATE TABLE IF NOT EXISTS `{$main['table_name']}` (".
            rtrim($insertsql, ',').
            ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='{$main['remark']}表';";
        $this->execute($insert);


        $main['control_list']   = json_encode($control_list);
        $main['control_search'] = json_encode($control_search);
        $main['control_edit']   = json_encode($control_edit);
        $this->insert($main);//添加到行
    }


    /**
     * 创建模块 - 初始化创建4张自带表 - 初始化操作控制
     */
    public $controlInit =
        [
            //列表默认配置
            'list'=>[
                'main'  =>[
                    ['name'=>'id','alias'=>'ID','type'=>'text'],
                    ['name'=>'title','alias'=>'标题','type'=>'href','config'=>'http://www.baidu.com/[id].html'],
                    ['name'=>'editor','alias'=>'编辑','type'=>'text'],
                    ['name'=>'update_time','alias'=>'更新时间','type'=>'ctime','position'=>'right'],//date|Y-m-d H:i
                    ['name'=>'show_time','alias'=>'发布时间','type'=>'ctime','position'=>'right'],//date|Y-m-d H:i
                    ['name'=>'status','alias'=>'状态','type'=>'radio','position'=>'right','config'=>'0:隐藏,1:正常,2:推荐'],//|{0:隐藏,1:正常,2:推荐}
                ],
                'texts' =>[],
                'attachs'=>[
                    ['name'=>'id','alias'=>'ID','type'=>'text','width'=>'60'],
                    ['name'=>'itemid','alias'=>'列表ID','type'=>'text','width'=>'90'],
                    ['name'=>'width','alias'=>'宽','type'=>'text','width'=>'60'],
                    ['name'=>'height','alias'=>'高','type'=>'text','width'=>'60'],
                    ['name'=>'subtitle','alias'=>'小标题','type'=>'text','width'=>'300'],
                    ['name'=>'digest','alias'=>'正文','type'=>'textarea','width'=>'300'],
                    ['name'=>'orderby','alias'=>'排序','type'=>'text','width'=>'60'],
                    ['name'=>'favnum','alias'=>'收藏数','type'=>'text','width'=>'75'],
                ],
            ],
            //搜索默认配置
            'search'=>[
                'main'  =>[
                    ['name'=>'id',      'alias'=>'ID',    'type'=>'eq','width'=>'50'],
                    ['name'=>'title',   'alias'=>'标题',  'type'=>'like','width'=>'250'],
                    ['name'=>'editor',  'alias'=>'编辑',  'type'=>'eq'],
                    ['name'=>'update_time','alias'=>'更新时间','type'=>'date'],
                ],
                'texts' =>[],
                'attachs'=>[
                    ['name'=>'id','alias'=>'ID','type'=>'eq','width'=>'50'],
                    ['name'=>'itemid','alias'=>'列表ID','type'=>'eq','width'=>'50'],
                    ['name'=>'width','alias'=>'宽','type'=>'eq','width'=>'50'],
                    ['name'=>'height','alias'=>'高','type'=>'eq','width'=>'50'],
                ],
            ],
            //编辑默认配置
            'edit'=>[
                'main'  =>[
                    ['name'=>'category','alias'=>'栏目','type'=>'category'],
                    ['name'=>'list_order','alias'=>'排序','type'=>'int','null'=>'false', 'len' => 10, 'default' => 1000],
                    ['name'=>'title','alias'=>'标题','type'=>'text'],
                    ['name'=>'alias','alias'=>'别名','type'=>'text'],
                    ['name'=>'longtitle','alias'=>'长标题','type'=>'textarea'],
                    ['name'=>'citys','alias'=>'城市','type'=>'citys'],
                    ['name'=>'limitword','alias'=>'限定词','type'=>'textarea'],
                    ['name'=>'keywords','alias'=>'关键词','type'=>'textarea'],
                    ['name'=>'tags','alias'=>'标签','type'=>'checkbox','position'=>'right','config'=>'1:要闻,2:娱乐,3:体育,4:美食,5:健康'],
                    ['name'=>'pinyin','alias'=>'拼音','type'=>'text'],
                    ['name'=>'showtype','alias'=>'被推送标识','type'=>'radio','position'=>'right','config'=>'1:正常,2:推荐,3:置顶'],
                    ['name'=>'status','alias'=>'状态','type'=>'radio','position'=>'right','config'=>'0:隐藏,1:正常,2:推荐'],//|{0:隐藏,1:正常,2:推荐}
                    ['name'=>'author','alias'=>'文章来源作者','type'=>'text','width'=>'100'],
                    ['name'=>'source','alias'=>'文章来源地址','type'=>'text'],
                    ['name'=>'digest','alias'=>'摘要','type'=>'textarea'],
                    ['name'=>'editor','alias'=>'编辑人员','type'=>'text','width'=>'100','position'=>'right'],
                    ['name'=>'editorid','alias'=>'编辑人员ID','type'=>'system_create','width'=>'80','position'=>'right','config'=>'uid'],
                    ['name'=>'picture','alias'=>'缩略图片','type'=>'uploadImg','position'=>'right'],
                    ['name'=>'picture2','alias'=>'附图2','type'=>'uploadImg','position'=>'right'],
                    ['name'=>'picture3','alias'=>'附图3','type'=>'uploadImg','position'=>'right'],
                    ['name'=>'bigpicture','alias'=>'内容大图','type'=>'uploadImg','position'=>'right'],
                    ['name'=>'create_time','alias'=>'录入时间','type'=>'system_create','position'=>'right','config'=>'time'],
                    ['name'=>'update_time','alias'=>'更新时间','type'=>'system','position'=>'right','config'=>'time'],
                    ['name'=>'show_time','alias'=>'发布时间','type'=>'ctime','position'=>'right'],
                    ['name'=>'attachnum','alias'=>'附件总数','type'=>'text','width'=>'80'],
                    ['name'=>'pagenum','alias'=>'分页总数','type'=>'text','width'=>'80'],
                    ['name'=>'goodnum','alias'=>'点赞数','type'=>'text','width'=>'80'],
                    ['name'=>'favnum','alias'=>'收藏数','type'=>'text','width'=>'80'],
                    ['name'=>'commnum','alias'=>'评论数','type'=>'text','width'=>'80'],
                    ['name'=>'pv','alias'=>'总访问次数','type'=>'text','width'=>'80', 'default' => 1000],
                    ['name'=>'pv2','alias'=>'日访问次数','type'=>'text','width'=>'80'],
                    ['name'=>'pv3','alias'=>'周访问次数','type'=>'text','width'=>'80'],
                    ['name'=>'pv3','alias'=>'月访问次数','type'=>'text','width'=>'80'],
                    ['name'=>'orderby','alias'=>'排序值','type'=>'text','width'=>'80'],
                    ['name'=>'filename','alias'=>'访问路径','type'=>'text'],
                ],
                'texts' =>[
                    ['name'=>'subtitle','alias'=>'分页小标题','type'=>'textarea'],
                    ['name'=>'keywords','alias'=>'分页关键字','type'=>'text'],
                    ['name'=>'page','alias'=>'页码','type'=>'text','width'=>'100'],
                    ['name'=>'text','alias'=>'内容','type'=>'editor','desc'=>''],
                    //说明：此测试表设置为一对一关联,分页仅用_ueditor_page_break_tag_标识符断开。
                    //如果存在分页时,此表本该是一对多关系，请自行设置一对多关联的配置。
                ],
                'attachs'=>[
                    ['name'=>'itemid','alias'=>'列表ID','type'=>'text','width'=>'70'],
                    ['name'=>'width','alias'=>'宽','type'=>'text','width'=>'70'],
                    ['name'=>'height','alias'=>'高','type'=>'text','width'=>'70'],
                    ['name'=>'orderby','alias'=>'排序','type'=>'text','width'=>'70'],
                    ['name'=>'favnum','alias'=>'收藏数','type'=>'text','width'=>'70'],
                    ['name'=>'fileurl','alias'=>'地址','type'=>'uploadImg'],
                    ['name'=>'subtitle','alias'=>'小标题','type'=>'textarea','width'=>'300'],
                    ['name'=>'digest','alias'=>'正文','type'=>'textarea','width'=>'300'],
                ],
            ]
        ];

}
