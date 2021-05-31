<?php
// +----------------------------------------------------------------------
// | 内容模型-模块控制器
// +----------------------------------------------------------------------
// | 现在 < 244100330@qq.com>
// +----------------------------------------------------------------------
namespace plugins\modules\controller;

use think\Db;
use cmf\controller\PluginAdminBaseController;
use plugins\modules\model\PluginModulesModel as ModulesModel;
use plugins\modules\model\PluginModulesTablesModel as ModuleTablesModel;

/**
 * 模块管理
 * 主要列表控制器
 */
class AdminIndexController extends PluginAdminBaseController
{

    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
    }

    public function __construct()
    {
        parent::__construct();
        if(!$modules_id = $this->request->param('modules_id',0)){
            $this->model = new ModulesModel();
        }else{
            $this->model = ModulesModel::get( $modules_id );
        }
    }


    /**
     * 内容模型
     * @adminMenu(
     *     'name'   => '内容模型',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '内容模型列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {

        
        $modules   =  $this->model->where("state=1")->select();
        $this->assign('modules', $modules);

        return $this->fetch();
    }

    /**
     * 插件设置
     * @return mixed
     */
    public function setting()
    {
    }


    /**
     * 列表页AJAX修改提交
     */
    public function ajaxPost(){
        $modules_id = $this->request->param('modules_id');
        $key = $this->request->param('name');
        $value = $this->request->param('value');

        if($modules_id){
            $this->model->where('id',$modules_id)->update([$key=>$value]);
            return true;
        }
        return false;
    }

    /**
     * 添加模块
     */
    public function add()
    {
        return $this->fetch();
    }


    /**
     * 添加一个模块
     * 下一步
     */
    public function addStepTwo(){
        $param = $this->request->except(['_plugin','_controller','_action']);

        if(empty($param['name']) || empty($param['pinyin'])){
            return $this->error('模块名称或者拼音标识必须填写!');
        }else{
            $module = $this->model->where('name',$param['name'])->whereOr('pinyin',$param['pinyin'])->find();
            if($module){
                return $this->error('模块名称或者拼音标识已经存在!');
            }
        }

        $this->assign('tables_example', \plugins\modules\lib\ControlForm::init()->tables_example);

        $this->assign('table_rule', $this->model->getTableRule($param['pinyin']));
        $this->assign('module',$param);
        return $this->fetch();
    }

    /**
     * 添加模块
     * 提交
     */
    public function addPost(){
        $param = $this->request->except(['_plugin','_controller','_action']);
        $param['module']['version'] = (isset($param['module']['version']) && $param['module']['version']) ? 1 : 0;
       
        

        try {
            //执行添加
            $id = $this->model->insertGetId($param['module']);
        } catch (\Exception $e) {
            $this->error('模块创建失败!', cmf_plugin_url('Modules://AdminIndex/index'));
        }

        try {
            //创建表
            $param['id'] = $id;
            $tablesModel = new ModuleTablesModel();
            if($param['module']['version']){
                $tablesModel->addfast($param);//快速建表
            }else{
                $tablesModel->add($param);//初始主表
            }

            /*$pinyin = $param['module']['pinyin'];
            $new_controller = ucwords($pinyin).'Controller';
            //创建控制器
            $path = APP_PATH.'portal/controller';
            $plugin_path = PLUGINS_PATH.'modules';
            copy($plugin_path.'/controller/DefaultController.php', $path.'/'.$new_controller.'.php');

            //更改控制器
            $origin_str = file_get_contents($path.'/'.$new_controller.'.php');
            $dai_attr   = ['$act','$posti','$moduleid','item_default','DefaultController'];

            $zui_attr   = ['\''.$pinyin.'\'','\''.$param['module']['name'].'\'',$id,'item_'.$pinyin,$new_controller];
            $update_str = str_replace($dai_attr, $zui_attr, $origin_str);
            file_put_contents($path.'/'.$new_controller.'.php', $update_str);

            //创建模板文件
            dir_copy($plugin_path.'/view/default',WEB_ROOT.'themes/simpleboot3/portal/'.$pinyin);*/


        } catch (\Exception $e) {
            $this->model->where('id', $id)->delete();
            $this->error('表创建失败! 建议请删除所有表和模块后再试！', cmf_plugin_url('Modules://AdminIndex/index'), '',10);
        }


        try {
        //动态添加一个内容菜单
            $this->model->add_menu([
                'parent_id'     => $this->model->getContentNodeId(),
                'app'           => 'plugin/modules',
                'controller'    => 'content',
                'action'        => 'index',
                'param'         => (function_exists('cmf_version') && \cmf_version() >= '5.1.0' ? '?' : '').'modules_id='.$param['id'],
                'name'          => $param['module']['name'],
                'status'        => 1,
            ]);
        } catch (\Exception $e) {
            $this->error('菜单添加失败！,请自行手动补充菜单,并添加参数(param=)'.'modules_id='.$param['id'], cmf_plugin_url('Modules://AdminIndex/index'), '',6);
        }

        $this->success(lang("EDIT_SUCCESS"), cmf_plugin_url('Modules://AdminIndex/index'));
    }


    /**
     * 删除一个模块
     */
    public function delete(){
        $modules_id = $this->request->param('modules_id');

        if($this->model->id){
            $tableList = $this->model->query("show table status like '{$this->model->getTableRule()}%'");
            if(isset($tableList[0]['Name']) && !empty($tableList[0]['Name'])){
                return $this->error('检测到有未删除的表，请删除所有表后再试！');
            }
        }

        //执行删除
        $this->model->where('id',$modules_id)->delete();

        //删除菜单
        $this->model->del_menu($modules_id);

        $this->success('删除完成！');
    }

    /**
     * 编辑
     */
    public function edit(){
        $id = $this->request->param('id',0,'intval');
        $modle = new ModulesModel();

        $data = $modle->where('id',$id)->find();

        $this->assign('data',$data);
        return $this->fetch();

    }
    /**
     * 编辑提交
     */
    public function editPost(){
        $id = $this->request->param('id',0,'intval');
        $param = $this->request->param();
        $data = [
            'name'         => $param['name'],
            'is_category'  => $param['is_category'],
            'is_del'       => $param['is_del'],
            'cate_level'   => $param['cate_level'],
            'remark'       => $param['remark']
        ];
        $model = new ModulesModel();
        $model->where('id',$id)->update($data);

        $this->success('编辑成功！');

    }

    /**
     * 更新发布数量
     */
    public function update_num(){
        $modules_list = Db::name('plugin_modules')->field('id,pinyin')->order('total_num','desc')->select();
        foreach ($modules_list as $key => $value) {
            $table = 'item_'.$value['pinyin'];
            $total_num       = Db::name($table)->count();

            Db::name('plugin_modules')->where('id',$value['id'])->update([
                'total_num'      => $total_num
            ]);
        }

        $this->success('更新成功');
    }

}

function dir_copy($src = '', $dst = '')
{
    if (empty($src) || empty($dst))
    {
        return false;
    }
 
    $dir = opendir($src);
    dir_mkdir($dst);
    while (false !== ($file = readdir($dir)))
    {
        if (($file != '.') && ($file != '..'))
        {
            if (is_dir($src . '/' . $file))
            {
                dir_copy($src . '/' . $file, $dst . '/' . $file);
            }
            else
            {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
 
    return true;
}
/**
 * 创建文件夹
 *
 * @param string $path 文件夹路径
 * @param int $mode 访问权限
 * @param bool $recursive 是否递归创建
 * @return bool
 */
function dir_mkdir($path = '', $mode = 0777, $recursive = true)
{
    clearstatcache();
    if (!is_dir($path))
    {
        mkdir($path, $mode, $recursive);
        return chmod($path, $mode);
    }
 
    return true;
}
