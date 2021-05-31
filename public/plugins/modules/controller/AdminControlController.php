<?php
// +----------------------------------------------------------------------
// | 内容模型-操作模型控制器
// +----------------------------------------------------------------------
// | 现在 < 244100330@qq.com>
// +----------------------------------------------------------------------
namespace plugins\modules\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\modules\model\PluginModulesModel as ModulesModel;
use plugins\modules\model\PluginModulesTablesModel as ModuleTablesModel;

/**
 * 模块管理 - 操作控制
 * 配置显示列表-搜索-编辑控件等（原字段设置）
 */
class AdminControlController extends PluginAdminBaseController
{


    public function __construct()
    {
        parent::__construct();
        if(!$modules_id = $this->request->param('modules_id',0)){
            $this->modulesModel = new ModulesModel();
        }else{
            $this->modulesModel = ModulesModel::get( $modules_id );
        }
        $this->tablesModel = new ModuleTablesModel();
    }


    /**
     * 操作控制
     */
    public function index(){
        $param = $this->request->except(['_plugin','_controller','_action']);
        $this->assign('control',    $control    = isset($param['control'])? $param['control']: 'list');//存储字段：编辑edit - 列表list - 搜索search
        $this->assign('tables',     $tables     = $this->modulesModel->getTables());//真实获取模块下的所有表
        $param['table_name'] = isset($param['table_name']) ? $param['table_name'] : current($tables)['Name'];
        $this->assign($param);
        //获取表配置数据行
        $tables_data = $this->tablesModel->where('modules_id', $param['modules_id'])->where('table_name', $param['table_name'])->find();
        if(empty($tables_data)){
            $tables_data = $this->tablesModel->real_sync_to_row($param['modules_id'], $param['table_name']);
        }
        $fieldDatas = $tables_data['control_'.$control];//三种操作配置
        $this->assign('table_pinyin',  $tables_data['pinyin']);//表拼音标识
        $this->assign('table_relate_level',  $tables_data['relate_level']);//表关联关系
        $this->assign('table_relate_field',  $tables_data['relate_field']);//表关联字段

        //真实该表下所有字段
        $allField   = $this->modulesModel->getFields($param['table_name'], 'COLUMN_NAME as name,COLUMN_COMMENT as comment');
        $tmp = [];
        if($fieldDatas)
        foreach($fieldDatas as $v){
            foreach($allField as $k=>$i){
                if($v['name']==$i['name']){
                    $v['comment'] = $i['comment'];
                    $tmp[] = $v;
                    unset($allField[$k]);
                    break;
                }
            }
        }
        $this->assign('allField', array_merge($tmp,$allField));

        return $this->fetch();
    }


    /**
     * 操作控制保存-提交
     */
    public function dopost(){
        $param = $this->request->except(['_plugin','_controller','_action']);
        $data = [];
        foreach($param['post'] as $k=>$v){
            if(isset($v['stat'])){//启用才保存
                $v['name'] = $k;
                if(isset($v['type']))   $v['type']      = rtrim($v['type']);
                if(isset($v['config'])) $v['config']    = rtrim($v['config']);
                if(isset($v['alias']))  $v['alias']     = rtrim($v['alias']);
                unset($v['stat']);
                if($param['control']=='edit' && empty($v['position']) && in_array($v['type'],['ctime','cdate','uploadImg','radio','checkbox'])){
                    $v['position'] = 'right';//未配置位置时自动检测放到右边展示的
                }
                $data[] = $v;
            }
        }
        $r = $this->tablesModel->saveControl($param['table_name'], $param['control'], $data);
        if($r){
            $this->success('保存成功');
        }else{
            $this->error('未修改成功');//cmf_plugin_url('Modules://AdminColumn/index',['modules_id'=>$param['modules_id'],'control'=>$param['control'],'table_name'=>$param['table_name']])
        }
    }


    /**
     * 字段设置提交
     */
    public function fieldsetpost(){
        $post = $this->request->except(['_plugin','_controller','_action']);
        if(!$post['modules_id']){
            $this->error(lang("NO_ID"));
        }
        foreach($post['items'] as $k=>$v){
            if(!$v['order']) unset($post['items'][$k]);//未设置排序的表示排除
        }
        foreach($post['item_infos'] as $k=>$v){
            if(!$v['order']) unset($post['item_infos'][$k]);//未设置排序的表示排除
        }
        if(isset($post['exts']))
        foreach($post['exts'] as $ke=>$ve){
            foreach($ve as $k=>$v){
                if(!$v['order']) unset($post['exts'][$ke][$k]);//未设置排序的表示排除
            }
        }
        $r = $this->modulesModel->saveSetingField($post['modules_id'], $post);
        if($r){
            $this->success(lang("EDIT_SUCCESS"));
        }else{
            $this->error(lang("EDIT_FAILED"));
        }
    }




}