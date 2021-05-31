<?php
// +----------------------------------------------------------------------
// | 模块管理 - 表管理
// | 附表中的itemid为关联字段,所以不可以删除，但附表ID可以删除。
// +----------------------------------------------------------------------
namespace plugins\modules\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\modules\model\PluginModulesModel as ModulesModel;
use plugins\modules\model\PluginModulesTablesModel as ModuleTablesModel;

/**
 * 模块-表管理
 */
class AdminTablesController extends PluginAdminBaseController
{


    public function __construct()
    {
        parent::__construct();
        if(!$modules_id = $this->request->param('modules_id',0)){
            $this->modulesModel = new ModulesModel();
        }else{
            $this->modulesModel = ModulesModel::get( $modules_id );
        }
        $this->model = new ModuleTablesModel();
    }


    /**
     * 表管理
     * 不是单独的表，而是在模块表里字段序列化保存的
     */
    public function index(){
        $this->assign($this->request->except(['_plugin','_controller','_action']));
        $this->assign('modules_pinyin', $this->modulesModel->pinyin);
        $this->assign('modules_name', $this->modulesModel->name);
        $this->assign('tables', $tables = $this->modulesModel->getTables());//真实获取模块下的所有表

        return $this->fetch();
    }


    /**
     * 表管理 - 列表页AJAX修改提交
     */
    public function ajaxPostTable(){
        $key = $this->request->param('name');
        $value = $this->request->param('value');
        $tableName = $this->request->param('table_name','','trim');

        if($tableName && strtolower($key)=='comment'){
            $this->modulesModel = new ModuleTablesModel();
            $this->modulesModel->execute("ALTER TABLE `{$tableName}` COMMENT='{$value}';");
            return true;
        }
        return false;
    }


    /**
     * 表管理 - 添加一张表
     */
    public function add(){
        $postdata = $this->request->except(['_plugin','_controller','_action']);;
        $adddata = $postdata['add'];
        if($adddata['pinyin'] && $postdata['modules_id']){
            $tablename = $this->modulesModel->getTableRule() .'_'.$adddata['pinyin'];
            //检查表是否存在
            $sql = "SELECT table_name FROM information_schema.TABLES WHERE table_name ='{$tablename}';";
            $exist = $this->modulesModel->query($sql);
            if($exist){
                $this->error('表已经存在：'.$tablename);
            }else{
                $modules = ['id'=>$this->modulesModel->id, 'pinyin'=>$this->modulesModel->pinyin, 'name'=>$this->modulesModel->name];
                $this->model->add($modules, $adddata);
                $this->success('完成！');
            }
        }
        $this->error('创建失败！');
    }

    /**
     * 表管理 - 删除一张表
     */
    public function delete(){
        $tablename = $this->request->param('table_name');
        if($tablename){
            //先查询表数据是否清空，清空后才允许删除
            $data = $this->modulesModel->table("`{$tablename}`")->find();
            if(empty($data)){
                //执行删除
                $this->model->del($tablename);
                $this->success('完成！');
            }else{
                return $this->error('表里有数据');
            }
        }
        $this->error('删除失败！');
    }


// +----------------------------------------------------------------------
// | 模块管理 - 表管理 - 字段管理
// +----------------------------------------------------------------------

    /**
     * 字段管理 - 列表
     */
    public function fields(){
        $this->assign($param = $this->request->except(['_plugin','_controller','_action']));//modules_id | table_name表全称

        $fields = $this->modulesModel->getFields( $param['table_name'] );//真实查询表的字段信息
        $this->assign('fields', $fields);
        $this->assign('modules_name', $this->modulesModel->name);

        return $this->fetch();
    }


    /**
     * 字段管理 - 修改
     */
    public function fieldUpdate(){
        $tablename = $this->request->param('name');
        $type = $this->request->param('type');
        $post = $this->request->except(['_plugin','_controller','_action']);;

        switch($type){
            case 'add':
                $postdata = $post['add'];//POST过来的数据
                if(empty($postdata['Field'])) $this->error('错误操作');

                $NONULL = (isset($postdata['Null']) && $postdata['Null']=='on')?'NULL':'NOT NULL';//是否空
                $DEFAULT = $postdata['Default']!= null ? 'DEFAULT \''.$postdata['Default'].'\'' : '';//默认值
                $COMMENT = !empty($postdata['Comment']) ? 'COMMENT \''.$postdata['Comment'].'\'' : '';//注释
                $sql = "ALTER TABLE `{$tablename}` ADD COLUMN `{$postdata['Field']}`  {$postdata['Type']} {$NONULL} {$DEFAULT} {$COMMENT};";
                $this->modulesModel->execute($sql);
                $this->success('完成！');
                break;

            case 'edit':
                $postdata = $post['edit'];//POST过来的数据
                if(empty($postdata['Field'])) $this->error('错误操作');

                $olddatas = $this->modulesModel->query(" desc {$tablename}");//查询原来数据
                $olddata = [];//当前列的老数据
                $oldkeys = [];//已经设置了的主键
                foreach($olddatas as $v){
                    if($v['Field']==$postdata['OldField'])  $olddata = $v;
                    if($v['Key']=='PRI')                    $oldkeys[$v['Field']] = '`'.$v['Field'].'`';
                }
                $Field = "`{$postdata['OldField']}`" . (($postdata['Field']!=$postdata['OldField'])?" `{$postdata['Field']}`":"");//字段名
                $FieldType = $postdata['Type'];//类型
                $NONULL = (isset($postdata['Null']) && $postdata['Null']=='on')?'NULL':'NOT NULL';//是否空
                $DEFAULT = ($postdata['Default'] != $olddata['Default']) ? 'DEFAULT \''.$postdata['Default'].'\'' : '';//默认值
                $COMMENT = 'COMMENT \''.$postdata['Comment'].'\'';//注释
                //主键
                if(isset($postdata['Key']) && $postdata['Key']=='on' && $olddata['Key']!='PRI')
                {//增加主键
                    $oldkeys[$postdata['Field']] = '`'.$postdata['Field'].'`';
                    $newkeys = implode(',',$oldkeys);
                    $Key = " ,DROP PRIMARY KEY,ADD PRIMARY KEY ({$newkeys}) ";
                }elseif(!isset($postdata['Key']) && $olddata['Key']=='PRI')
                {//去除主键
                    unset($oldkeys[$postdata['OldField']]);
                    $newkeys = implode(',',$oldkeys);
                    $Key = " ,DROP PRIMARY KEY,ADD PRIMARY KEY ({$newkeys}) ";
                }else{
                    $Key = '';//不变
                }
                $Extra = (isset($postdata['Extra']) && $postdata['Extra']=='on')?' AUTO_INCREMENT ':'';//自增
                $AlertType = ($postdata['Field']==$postdata['OldField']) ? 'MODIFY' : 'CHANGE';

                $sql = "ALTER TABLE `{$tablename}` {$AlertType} COLUMN {$Field} {$FieldType} {$NONULL} {$DEFAULT} {$Extra} {$COMMENT} {$Key};";

                $this->modulesModel->execute($sql);
                break;

            case 'del';
                if(empty($post['field'])){
                    $this->error('未选择字段！');
                }
                $exist = $this->modulesModel->table($tablename)->where("{$post['field']} is not null and {$post['field']} <> '0'")->count($post['field']);
                if(!$exist){//没有数据才允许执行删除
                    $this->modulesModel->execute("ALTER TABLE `{$tablename}` DROP COLUMN `{$post['field']}`");
                    $this->success('删除完成！', null, '', 1);
                }else{
                    $this->error('请清空该字段数据后再执行删除！');
                }
                break;

            default:
                $this->success('未知操作！');
                break;
        }
        $this->success('完成！');
    }


}