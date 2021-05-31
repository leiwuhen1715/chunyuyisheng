<?php
// +----------------------------------------------------------------------
// | ThinkCMF
// +----------------------------------------------------------------------
// | 分类栏目
// +----------------------------------------------------------------------
namespace plugins\modules\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\modules\model\PluginModulesColumnModel;
use plugins\modules\model\PluginModulesModel;
use think\Db;
use tree\Tree;

class AdminColumnController extends PluginAdminBaseController
{
    protected $modules_id;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PluginModulesColumnModel();
        $authmodules = $this->model->getauth();

        if (empty($authmodules)){
            $this->error('请在内容模型里先新建一个模块！');
        }
        $this->assign('authmodules', $authmodules);
        $this->modules_id  = $this->request->param('modules_id', 0, 'intval');
        if ($this->modules_id ==0){
            $this->modules_id = $authmodules[0]['id'];
        }
        /* 去掉栏目权限管理，如果模块设置了不同管理员的访问权限，那么这时候栏目也应该设置权限，如下代码供参考
        else{
            $auth = array_column($authmodules,'id');
            if (!in_array($this->modules_id,$auth)){
                $this->error('你没有权限访问该目录！');
            }
        }
        */
        $this->assign('modules_id', $this->modules_id);
    }


    /**
     * 栏目列表
     */
    public function index()
    {
        $where['delete_time']=0;
        if ($this->modules_id > 0){
            $where['modules_id'] = $this->modules_id;
        }

        $result     = $this->model->where($where)->order(["list_order" => "ASC"])->select()->toArray();


        $modulesModel = new PluginModulesModel();
        $cate_level = $modulesModel->where('id',$this->modules_id)->value('cate_level');

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        $newMenus = [];
        foreach ($result as $m) {
            $newMenus[$m['id']] = $m;
        }
        foreach ($result as $key => $value) {

            $result[$key]['parent_id_node'] = ($value['parent_id']) ? ' class="child-of-node-' . $value['parent_id'] . '"' : '';
            $result[$key]['style']          = empty($value['parent_id']) ? '' : 'display:none;';
            $result[$key]['str_manage'] = '';
    
            if($value['level'] < $cate_level){
                $result[$key]['str_manage'] .= '<a class="btn btn-xs btn-success" href="' . cmf_plugin_url('Modules://AdminColumn/add', ["parent" => $value['id'], "modules_id" => $this->modules_id])
                . '">添加子分类</a> ';
            }
            $result[$key]['str_manage']     .=   '<a class="btn btn-xs btn-primary" href="' . cmf_plugin_url('Modules://AdminColumn/edit', ["id" => $value['id'],'modules_id'=>$this->modules_id])
                . '">编辑</a>  <a class="btn btn-xs btn-danger js-ajax-delete" href="' . cmf_plugin_url('Modules://AdminColumn/delete', ["id" => $value['id'],'modules_id'=>$this->modules_id]) . '">删除</a> ';
            $result[$key]['description']         = $value['description'] ;
            $result[$key]['keyword']         = $value['keyword'] ;
            $result[$key]['status']         = $value['status'] ? lang('DISPLAY') : lang('HIDDEN');

        }

        $tree->init($result);
        $str = "<tr id='node-\$id' \$parent_id_node style='\$style'>
                        <td style='padding-left:20px;'><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='input input-order'></td>
                        <td>\$id</td>
                        <td>\$spacer\$name</td>
                       <td>\$description</td>
                       <td>\$keyword</td>
                        <td>\$status</td>
                        <td>\$str_manage</td>
                    </tr>";
        $category = $tree->getTree(0, $str);
        $this->assign('category', $category);
        return $this->fetch();
    }
    /**
     * 后台所有栏目列表
     */
    public function lists()
    {
        $where['delete_time']=0;
        if ($this->modules_id > 0){
            $where['modules_id'] = $this->modules_id;
        }
        $modulesModel = new PluginModulesModel();
        $ParentModulesId = $modulesModel->field('id,name')->order("id ASC")->where(['state' => 1])->select()->toArray();
        foreach ($ParentModulesId as $k=>$v){
            $newModules[$v['id']] = $v['name'];
        }
        $result = $this->model->order(["list_order" => "DESC"])->where($where)->select();
        $this->assign("column", $result);
        $this->assign("modules", $newModules);
        $this->assign("modules_id", $this->modules_id);
        return $this->fetch();
    }
    /**
     * 添加文章栏目
     */
    public function add()
    {
        $parentId            = $this->request->param('parent', 0, 'intval');
        $categoriesTree      = $this->model->ColumnTree($parentId,0,$this->modules_id);
        
        $parent = Db::name('plugin_modules_column')->field('id,name')->where(['id'=>$parentId,'modules_id'=>$this->modules_id])->find();
      
        $parentModulesId = 0;
        if ($parentId >0){
            $parentModulesId = $this->model->where('id', $parentId)->value('modules_id');
        }
        if ($this->modules_id >0){
            $parentModulesId = $this->modules_id;
        }
        $modules_status = '';
        if ($parentId >0 || $this->modules_id >0){
            $modules_status = 'disabled';
        }
        $column_status = '';
        if ($parentId >0){
            $column_status = 'disabled';
        }
        $modules = $this->model->modulesTree($parentModulesId);
        $this->assign('modules_id_save',$parentModulesId);
        $this->assign('modules', $modules);
        $this->assign('parent', $parent);
        $this->assign('modules_status', $modules_status);
        $this->assign('column_status', $column_status);
        $this->assign('categories_tree', $categoriesTree);
        $this->assign('modules_id', $this->modules_id);
        return $this->fetch();
    }

    /**
     * 添加文章栏目提交
     */
    public function addPost()
    {
        $data = $this->request->param();
        if (!isset($data['modules_id'])){
            $data['modules_id'] = $data['modules_id_save'];
        }
        if (!isset($data['parent_id'])){
            $data['parent_id'] = $data['parent_save'];
            $level = Db::name('plugin_modules_column')->where('id',$data['parent_id'])->value('level');
            $data['level'] = $level + 1;
        }
        $data['ctime'] = time();
        $result = $this->validate($data, 'AdminColumn');

        if ($result !== true) {
            $this->error($result);
        }
        $result = $this->model->addCategory($data);
        if ($result === false) {
            $this->error($result['msg']);
        }
        $this->model->DeleteColumnFileCache($data['modules_id']);
        $this->success('添加成功!', cmf_plugin_url('Modules://AdminColumn/index',array('modules_id'=>$this->modules_id)));

    }

    /**
     * 编辑栏目
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id > 0) {
            $category = PluginModulesColumnModel::get($id)->toArray();
            if (isset($category['def_keyword']) && !empty($category['def_keyword'])) {
                $category['def_keyword'] = unserialize($category['def_keyword']);

                foreach ($category['def_keyword'] as $k => &$value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                }
                $category['def_keyword'] = implode('#',$category['def_keyword']);
            }
            $modules_status = '';
            if ($category['parent_id'] >0 || $this->modules_id >0 || ($category['parent_id'] >0 && $category['modules_id'] >0)){
                $modules_status = 'disabled';
            }
            $column_status = '';
            if ($category['parent_id'] >0){
                $column_status = 'disabled';
            }
            //$category['more'] = unserialize($category['more']);
            $ColumnTree      = $this->model->ColumnTree($category['parent_id'], $id,$this->modules_id);

            $modules = $this->model->modulesTree($category['modules_id']);

            $parent = Db::name('plugin_modules_column')->field('id,name')->where(['id'=>$category['parent_id']])->find();


            $this->assign('modules', $modules);
            $this->assign('column_status', $column_status);
            $this->assign('modules_status', $modules_status);
            $this->assign($category);
            $this->assign('parent',$parent);
            $this->assign('column_Tree', $ColumnTree);

            $this->assign('modules_id', $this->modules_id);
            return $this->fetch();
        } else {
            $this->error('操作错误!');
        }

    }

    /**
     * 编辑栏目提交
     */
    public function editPost()
    {
        $data = $this->request->param();
        $result = $this->validate($data, 'AdminColumn');
        if ($result !== true) {
            $this->error($result);
        }
        if (!isset($data['modules_id'])){
            $data['modules_id'] = $data['modules_id'];
        }
        if(!empty($data['parent_id'])){
            $level = Db::name('plugin_modules_column')->where('id',$data['parent_id'])->value('level');
            $data['level'] = $level + 1;
        }
        
            
         
        $result = $this->model->editCategory($data);

        if ($result['status'] === false) {
            $this->error($result['msg']);
        }
        $this->model->DeleteColumnFileCache($data['modules_id']);
        $this->success($result['msg']);
    }

    /**
     * 栏目选择对话框
     */
    public function select()
    {
        $ids                 = $this->request->param('ids');
        $modulesId           = $this->request->param('modules_id');
        $selectedIds         = explode(',', $ids);

        $tpl = <<<tpl
<tr class='data-item-tr'>
    <td>
        <input type='checkbox' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]'
               value='\$id' data-name='\$name' \$checked>
    </td>
    <td>\$id</td>
    <td>\$spacer <a href='\$url' target='_blank'>\$name</a></td>
</tr>
tpl;
        $categoryTree = $this->model->adminColumnTableTree($selectedIds, $tpl,$modulesId);

        $where['delete_time']      = 0;
        if ($modulesId>0){
            $where['modules_id']      = $modulesId;
        }
        $categories = $this->model->where($where)->select();
        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        
        return $this->fetch();
    }

    public function selectuser(){

        $request = input('request.');

        $ids                 = $this->request->param('ids');
        $modulesId           = $this->request->param('modules_id');
        $selectedIds         = explode(',', $ids);

        $where = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $where['user_login|user_nickname|user_email|mobile']    = ['like', "%$keyword%"];
        }

        $list = Db::name('user')->field('id,user_nickname,mobile')->where($where)->where('id','>',1)->order("create_time DESC")->paginate(10);

        $list->appends($request);
        // 获取分页显示
        $page = $list->render();

        $this->assign('selectedIds', $selectedIds);
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();

    }

    /**
     * 栏目排序
     */
    public function listOrder()
    {
        parent::listOrders($this->model);
        $this->model->DeleteColumnFileCache();
        $this->success("排序更新成功！", '');
    }

    /**
     * 删除栏目
     */
    public function delete()
    {
        $id = $this->request->param('id');
        //获取删除的内容
        $findColumn = $this->model->where('id', $id)->find();

        if (empty($findColumn)) {
            $this->error('栏目不存在!');
        }

        $ColumnChildrenCount = $this->model->where('parent_id', $id)->count();

        if ($ColumnChildrenCount > 0) {
            $this->error('此栏目有子栏目无法删除!');
        }

        /*$data   = [
            'object_id'   => $findColumn['id'],
            'create_time' => time(),
            'table_name'  => 'portal_category',
            'name'        => $findColumn['name']
        ];*/
        $result = $this->model->where('id', $id)->delete();
        if ($result) {
            // $this->model->DeleteColumnFileCache();
            // $this->model->name('recycleBin')->insert($data);
            $this->success('删除成功!');
        } else {
            $this->error('删除失败');
        }
    }


    /**
     * 栏目默认关键词选择对话框
     */
    public function selectkeywords()
    {
        $names             = $this->request->param('names');
        $modulesId           = $this->request->param('modules_id');
        $selected         = explode(',', $names);

        $re = $this->model->getDefkeywordsbymodulesId($modulesId);
        $this->assign('keywords', $re);

        $this->assign('selected', $selected);

        return $this->fetch();
    }
}
