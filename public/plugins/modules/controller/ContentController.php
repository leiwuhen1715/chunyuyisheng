<?php
// +----------------------------------------------------------------------
// | 内容管理-列表-编辑
// | 后台操作内容列表，在内容模型里配置后使用
// +----------------------------------------------------------------------
namespace plugins\modules\controller;

use think\Db;
use cmf\controller\PluginAdminBaseController;
use plugins\modules\model\ContentModel;
use plugins\modules\model\PluginModulesModel as ModulesModel;
use plugins\modules\model\PluginModulesTablesModel as ModuleTablesModel;

class ContentController extends PluginAdminBaseController
{

    public $modules_id;//模块ID
    public $table_name;//表名

    public function __construct()
    {
        parent::__construct();

        $admin_id = cmf_get_current_admin_id();
        $admin_role_id = session('admin_role_id');
        if(empty($admin_role_id)){
          if($admin_id == 1){
            $role_id = 1;
          }else{
            $role_id = Db::name('role_user')->where('user_id',$admin_id)->order('role_id','asc')->value('role_id');
          }
          session('admin_role_id',$role_id);
        }
        
        
        $this->modules_id = $this->request->param('modules_id',0);
        $this->table_name = $this->request->param('table_name','');
        $this->assign('modeles_date',$this->modulesModel);
    }

    public function __get($name){
        if($name=='modulesModel'){
            if(!$this->modules_id){
                return $this->modulesModel = new ModulesModel();//模块模型
            }else{
                return $this->modulesModel = ModulesModel::get( $this->modules_id );//模块模型-直接获取数据
            }
        }elseif($name=='tablesModel'){
            return $this->tablesModel = new ModuleTablesModel();//表模型
        }elseif($name=='model'){
            return $this->model = new ContentModel();//内容模式
        }
    }

    /**
     * 内容管理
     * @adminMenu(
     *     'name'   => '内容管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '内容管理列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $this->assign($param = $this->request->except(['_plugin','_controller','_action']));//批量赋值URL参数
        //查询表配置
        $where = empty($this->table_name) ? ['pinyin'=>'']: ['table_name'=>$this->table_name];
        $tableDatas = $this->tablesModel->where('modules_id', $this->modules_id)->where($where)->cache(true, 60)->find();

        if(empty($tableDatas)){
            return '<font style="color: red"><br/>模块不存在,请检查模块配置！</font>
                    <br/><br/><font style="color: green">如是已删除模块，请在菜单管理里删除此无效菜单！</font>';
        }

        $this->assign('table_name',     $table_name = $tableDatas['table_name']);
        $this->assign('relate_level',   $tableDatas['relate_level']);
        $this->assign('control_search', $tableDatas['control_search']? $tableDatas['control_search']: []);
        $this->assign('control_list',   $tableDatas['control_list']? $tableDatas['control_list']: []);
        $this->assign('is_main_table',  $tableDatas['pinyin']? 0: 1);//是否是主表

        //内容查询
        $data = $this->model->getContent($param, $table_name, $tableDatas);
        $this->assign('items', $data->items());//内容
        $this->assign('page', $data->render());//分页

        //模块下所有表的所有字段名,格式：[tableName=>[field=>'name']]
        $this->assign('allFieldName',$allFieldName = $this->modulesModel->getAllFieldName(true));
        //真实获取模块下的所有表
        $this->assign('allTables', $allTables = $this->modulesModel->getTables(true));
        //搜索配置初始化
        \plugins\modules\lib\SearchForm::init($allFieldName, $param, $tableDatas['control_edit']);//control_edit -> 搜索自动匹配使用
        //列表配置初始化
        \plugins\modules\lib\ListForm::init($allFieldName, $tableDatas['control_edit']);

        return $this->fetch();
    }



    /**
     * 内容/文章添加
     */
    public function edit()
    {
        $this->assign($param = $this->request->except(['_plugin','_controller','_action']));//批量赋值URL参数

        //查询该表配置
        $where = empty($this->table_name) ? ['pinyin'=>'']: ['table_name'=>$this->table_name];
        $tableDatas = $this->tablesModel->where('modules_id', $this->modules_id)->where($where)->cache(true, 60)->find();

        $this->assign('table_name',     $table_name = $tableDatas['table_name']);
        $this->assign('relate_level',   $tableDatas['relate_level']);
        $this->assign('is_main_table',  $is_main_table = $tableDatas['pinyin']? 0: 1);//是否是主表

        $control_edit = $tableDatas['control_edit'];
        //如果是主表还需要读取一对一关联表的配置字段
        $setFields = [$table_name => $control_edit];
        if($is_main_table){
            $setTmp = $this->tablesModel->field('table_name,control_edit')->where('modules_id', $this->modules_id)->where('relate_level',1)->cache(true, 60)->select();
            if(!empty($setTmp))
            foreach($setTmp as $v){
                $setFields[$v['table_name']] = $v['control_edit'];
            }
        }
        $this->assign('setFields', $setFields);//编辑配置数组：[tableName=>[0=>[name=>'id']]]

        //真实获取模块下的所有表
        $this->assign('allTables', $allTables = $this->modulesModel->getTables(true));
        //模块下所有表的所有字段名,格式：[tableName=>[field=>'name']]
        $this->assign('allFieldName',$allFieldName = $this->modulesModel->getAllFieldName(true));

        //内容-该id相关所有内容
        if(isset($param['id'])){
            $content = $this->model->getEditContent($this->modules_id, $param['id'], $table_name, $is_main_table);
            $this->assign('act','edit');
        }else{
            $content = [];
            $this->assign('act','add');
        }
        //关联表编辑时，传递关联字段值过去。
        if(isset($param['itemid'])) $content[$table_name]['itemid'] = $param['itemid'];

        $this->assign('content', $content);


        return $this->fetch();
    }

    /**
     * 修改内容/文章提交
     */
    public function editPost()
    {
        $param = $this->request->except(['_plugin','_controller','_action']);
        if(isset($param['id'])){
            $param['post'] = \plugins\modules\lib\Filter::formatPost($param['post']);
        }else{
            $param['post'] = \plugins\modules\lib\Filter::formatPost($param['post'], true);
            $this->addPost($param);
            $this->success('添加成功!');
        }

        //表明
        $tables = $param['table_name'];
        $pinyin = str_replace("cmf_item_","",$tables);
        $param_data = $param['post'][$param['table_name']];

        $this->admin_statistics('edit');
        
        //先更新主表
        $this->model->table($param['table_name'])->where('id', $param['id'])->update($param['post'][$param['table_name']]);

        unset($param['post'][$param['table_name']]);

        //再更新附表(一对一的表)
        if($param['post'])
        foreach($param['post'] as $table => $list){
            if(isset($list['text'])){
                $content = $list['text'];
            }
            
            if($this->model->table($table)->where('itemid',$param['id'])->count()){
                if(isset($list['id'])){
                    $where = ['id'=>$list['id']];
                    unset($list['id']);
                }else{
                    $where = ['itemid'=>$param['id']];
                }
                $this->model->table($table)->where($where)->update( array_merge(['itemid'=>$param['id']], $list) );
            }else{
                $this->model->table($table)->insert( array_merge(['itemid'=>$param['id']], $list) );
            }

        }

        if(!empty($content)){
            $this->update_content($param_data,$content,$tables,$param['id']);
        }
        
        $insert_table_content = [
          'modules_id'  => $this->modules_id,
          'table'       => $pinyin,
          'object_id'   => $param['id'],
          'title'       => empty($param_data['title'])?'':$param_data['title'],
          'digest'      => empty($param_data['digest'])?'':$param_data['digest'],
          'picture'     => empty($param_data['picture'])?'':$param_data['picture'],
          'showtime'    => empty($param_data['showtime'])?time():$param_data['showtime'],
          'state'       => empty($param_data['state'])?1:$param_data['state'],
        ];
        Db::name('plugin_modules_tables_content')->where(['modules_id'=>$this->modules_id,'object_id'=>$param['id']])->update($insert_table_content);
        
        $this->success('修改成功!');
    }

    /**
     * 添加内容/文章提交
     */
    public function addPost($param)
    {
        $tables = $param['table_name'];
        $param_data = $param['post'][$param['table_name']];

        $pinyin = str_replace("cmf_item_","",$tables);

        //先取主表插入，然后取出自增ID再插附表
        $itemid     = $this->model->table($param['table_name'])->insert($param['post'][$param['table_name']], false, true);
        if($itemid){
            unset($param['post'][$param['table_name']]);
            foreach($param['post'] as $table => $list){
                if(isset($list['text'])){
                    $content = $list['text'];
                }
                $list['itemid'] = $itemid;//唯一关联
                $this->model->table($table)->insert($list);
            }
        }

        if(!empty($content)){
            $this->update_content($param_data,$content,$tables,$itemid);
        }
        if($this->modules_id != 15){
            $insert_table_content = [
              'modules_id'  => $this->modules_id,
              'table'       => $pinyin,
              'object_id'   => $itemid,
              'title'       => empty($param_data['title'])?'':$param_data['title'],
              'digest'      => empty($param_data['digest'])?'':$param_data['digest'],
              'picture'     => empty($param_data['picture'])?'':$param_data['picture'],
              'showtime'    => empty($param_data['showtime'])?time():$param_data['showtime'],
              'state'       => empty($param_data['state'])?2:$param_data['state'],
            ];
            Db::name('plugin_modules_tables_content')->insert($insert_table_content);
        }
        

        $this->admin_statistics('add');
        


        $this->success('添加成功!', cmf_plugin_url('Modules://Content/edit', ['id'=>$itemid, 'modules_id'=>$param['modules_id'], 'table_name'=>$param['table_name']]));
    }

    /**
     * 内容删除
     * @adminMenu(
     *     'name'   => '内容删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '内容模型内容数据删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $param = $this->request->except(['_plugin','_controller','_action']);

        if (isset($param['ids']) && isset($param['modules_id'])) {
            $ids = $this->request->param('ids/a');
            if(empty($ids))$this->error('请选择要删除的内容');

            if(isset($param['list_orders']))unset($param['list_orders']);
            unset($param['ids']);
            
            //先查询一对一和一对多关联表
            $relateTables = [];
            $relateField = '';
            $modulesDatas = $this->tablesModel->where('modules_id',$param['modules_id'])->field('id,pinyin,table_name,relate_field,relate_level')->select();

            foreach($modulesDatas as $data){
                if($data['table_name'] == $param['table_name']){//当前表
                    if(!empty($data['pinyin'])){//如果当前表不是主表，就不会有关联表
                        $relateTables = [];break;
                    }
                }else if($data['relate_level']>0){
                    $relateTables[] = $data['table_name'];
                    $relateField = $data['relate_field'];
                }
            }
            $nums = count($ids);
            $this->admin_statistics('del',$nums);

            //删除当前表数据
            $result = $this->modulesModel->table($param['table_name'])->where('id', 'in', $ids)->delete();
            Db::name('plugin_modules_tables_content')->where('modules_id',$param['modules_id'])->where('object_id', 'in', $ids)->delete();
            if($result){
                

                if(!empty($relateTables)){//删除关联表数据
                    $relateField = empty($relateField) ? 'itemid' : $relateField;//关联字段
                    foreach($relateTables as $table){
                        $this->modulesModel->table($table)->where($relateField, 'in', $ids)->delete();
                    }
                }
                $this->success("删除成功！", cmf_plugin_url('Modules://Content/index', $param));
            }

            $this->error("删除失败！", cmf_plugin_url('Modules://Content/index', $param));
        }
    }

    //排序
    public function listOrder()
    {
        $table_name = request()->param('table_name');
        $table_name = str_replace("cmf_","",$table_name);
        //$table = 
        parent::listOrders(Db::name("$table_name"));
        $this->success("排序更新成功！");
    }

    //更新内容
    private function update_content($param_data,$content,$tables,$id){
          $update_data = [];

          if(isset($param_data['digest']) && empty($param_data['digest'])){
                $html_string = htmlspecialchars_decode($content);
                //将空格替换成空
                $content = str_replace(" ","",$html_string);
                //函数剥去字符串中的HTML、XML以及PHP的标签,获取纯文本内容
                $contents = strip_tags($content);
                //返回字符串中的前60字符串长度的字符
                $text = mb_substr($contents,0,100,"utf-8");
                $param_data['digest'] = $update_data['digest'] = $text;
          }
          if(isset($param_data['picture']) && empty($param_data['picture'])){
                $pattern1 = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
                preg_match_all($pattern1,$content,$matchContent1);

                if(!empty($matchContent1[1][0])){
                    $rurl = $matchContent1[1][0];
                    $param_data['picture'] = $update_data['picture']=$rurl;
                }
          }

          if(!empty($update_data)){
              $this->model->table($tables)->where('id', $id)->update($update_data);
          }
    }
    /**
     * 管理员发布统计
     * $status  [add,edit,del]
     */
    private function admin_statistics($status,$nums=1){

      $day_time = strtotime(date('Y-m-d',time()));
      $admin_id = cmf_get_current_admin_id();

      $day_id    = Db::name('plugin_modules_day_data')->where(['module_id'=>$this->modules_id,'admin_id'=>$admin_id,'time'=>$day_time])->value('id');

      if(empty($day_id)){

        $day_id = Db::name('plugin_modules_day_data')->insertGetId([
          'module_id' => $this->modules_id,
          'admin_id'  => $admin_id,
          'time'      => $day_time
        ]);

      }
      switch ($status) {
        case 'add':
          Db::name('plugin_modules_day_data')->where('id',$day_id)->setInc('pub_num');
          Db::name('plugin_modules')->where('id',$this->modules_id)->setInc('total_num');
          break;
        case 'edit':
          Db::name('plugin_modules_day_data')->where('id',$day_id)->setInc('edi_num');
          break;
        case 'del':
          Db::name('plugin_modules_day_data')->where('id',$day_id)->setInc('del_num',$nums);
          Db::name('plugin_modules')->where('id',$this->modules_id)->setDec('total_num',$nums);
          break;
      }
    }

}
