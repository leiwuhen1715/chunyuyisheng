<?php
// +----------------------------------------------------------------------
// | ThinkCMF
// +----------------------------------------------------------------------
// | 分类栏目
// +----------------------------------------------------------------------
namespace plugins\modules\model;

use think\Model;
use tree\Tree;
use think\cache;
use think\db;

class PluginModulesColumnModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    //获取当前用户拥有的权限数据
    public function getauth($modules_id=''){
        return db::name('plugin_modules')->field('id,name,pinyin,remark')->select()->toArray();

        $admin_id =  cmf_get_current_admin_id();
        //获取用户内容里面模块菜单的权限
        $groups = db::name('RoleUser')
            ->alias("a")
            ->join('__ROLE__ r', 'a.role_id = r.id')
            ->where(["a.user_id" => $admin_id, "r.status" => 1])
            ->column("role_id");

        if($admin_id == 1 || in_array(1,$groups)){
            $where = [ "status" => 1];
            $rules = db::name('AuthRule')->where($where)->select()->toArray();
        }else{
            $where = ["a.role_id" => ["in", $groups], "b.status" => 1];
            $rules = db::name('AuthAccess')
                ->alias("a")
                ->join('__AUTH_RULE__ b ', ' a.rule_name = b.name')
                ->where($where)
                ->select()->toArray();
        }

        if($rules) {
            foreach ($rules as $k => $v) {
                if (!$v['param']) continue;
                parse_str($v['param'], $urlParam);
                if (isset($urlParam['modules_id'])) {
                    $mid[] = $urlParam['modules_id'];
                }
            }
            if ($modules_id) {
                if (in_array($modules_id, $mid) || in_array(1, $groups)) {
                    return true;
                } else {
                    return false;
                }
            } else if (!empty($mid)) {
                $re = db::name('plugin_modules')->where(["id" => ['in', $mid], "state" => 1])->field('id,name,pinyin,remark')->select()->toArray();
                return $re;
            }
        }

        return false;
    }

    /**
     * 生成栏目 select树形结构
     * @param int $selectId 需要选中的栏目 id
     * @param int $currentCid 需要隐藏的栏目 id
     * @return string
     */
    public function ColumnTree($selectId = 0, $currentCid = 0, $modules_id = 0)
    {
        $this->where('delete_time',0);
        $where = [];
        $where['delete_time'] = 0;
        if (!empty($currentCid)) {
            //$this->where('id','!=',$currentCid);
            $where['id'] = ['<>',$currentCid];
        }

        if (!empty($modules_id)) {
            $where['modules_id'] = $modules_id;
        }
        $categories = $this->order("list_order ASC")->where($where)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        $newCategories = [];
        foreach ($categories as $item) {
            $item['selected'] = $selectId == $item['id'] ? "selected" : "";

            array_push($newCategories, $item);
        }

        $tree->init($newCategories);
        $str     = '<option value=\"{$id}\" {$selected}>{$spacer}{$name}</option>';
        $treeStr = $tree->getTree(0, $str);

        return $treeStr;
    }

    /**
     * 添加文章分类
     * @param $data
     * @return bool
     */
    public function addCategory($data)
    {

        $re = $this->where(array('modules_id'=>$data['modules_id'],'name'=>$data['name']))->find();
        if ($re){
            return array('status'=>false,'msg'=>'该模块下已经存在该名称!');
        }
        /*$re = $this->where(array('modules_id'=>$data['modules_id'],'catalog'=>$data['catalog']))->find();
        if ($re){
            return array('status'=>false,'msg'=>'该模块下已经存在该目录!');
        }*/
        $data['def_keyword'] =explode('#',$data['def_keyword']);
        foreach ($data['def_keyword'] as $k=>&$v){
            $v = explode(',',$v);
        }
        $data['def_keyword'] = serialize($data['def_keyword']);
        $data['more']['photos'] = [];
            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }
        //$data['more'] = serialize($data['more']);
        $result = $this->allowField(true)->save($data);
        if (!$result){
            $result = array('status'=>false,'msg'=>'添加失败!');
            return $result;
        }
        $id = $this->id;
        $colony_id = $data['modules_id'].$id;
        $this->where( ['id' => $id])->update(['colony_id' => $colony_id]);
        if (empty($data['parent_id'])) {
            $result = $this->where( ['id' => $id])->update(['path' => '0,' . $id]);
        } else {
            $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
            $result = $this->where( ['id' => $id])->update(['path' => "$parentPath,$id"]);

        }
        if (!$result){
            $result = array('status'=>false,'msg'=>'添加失败!');
            return $result;
        }

        return $result;
    }

    public function editCategory($data)
    {
        $result = array('status'=>true,'msg'=>'保存成功!');
        $id          = intval($data['id']);
        $parentId    = isset($data['parent_id'])?intval($data['parent_id']):0;

        $oldCategory = $this->where('id', $id)->find();
        if ($oldCategory['modules_id'] != $data['modules_id']){
            $result = array('status'=>false,'msg'=>'不能夸模块修改!');
            return $result;
        }
        if ($oldCategory['name']!==$data['name']){
            $re = $this->where(array('modules_id'=>$data['modules_id'],'name'=>$data['name']))->find();
            if ($re){
                $result = array('status'=>false,'msg'=>'该模块下已经存在该名称!');
                return $result;
            }
        }
        /*if ($oldCategory['catalog']!==$data['catalog']) {
            $re = $this->where(array('modules_id' => $data['modules_id'], 'catalog' => $data['catalog']))->find();
            if ($re) {
                $result = array('status' => false, 'msg' => '该模块下已经存在该目录!');
                return $result;
            }
        }*/
        $data['def_keyword'] =explode('#',$data['def_keyword']);
        foreach ($data['def_keyword'] as $k=>&$v){
            $v = explode(',',$v);
        }
        $data['def_keyword'] = serialize($data['def_keyword']);
        if (empty($parentId)) {
            $newPath = '0,' . $id;
        } else {
            $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
            if ($parentPath === false) {
                $newPath = false;
            } else {
                $newPath = "$parentPath,$id";
            }
        }
        if (empty($oldCategory) || empty($newPath)) {
            $result = array('status'=>false,'msg'=>'保存失败!');;
        } else {


            $data['path'] = $newPath;
            $data['more']['photos'] = [];
            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }
            //$data['more'] = serialize($data['more']);
            $this->isUpdate(true)->allowField(true)->save($data, ['id' => $id]);

            $children = $this->field('id,path')->where('path', 'like', "%,$id,%")->select();

            if (!empty($children)) {
                foreach ($children as $child) {
                    $childPath = str_replace($oldCategory['path'] . ',', $newPath . ',', $child['path']);
                    $this->isUpdate(true)->save(['path' => $childPath], ['id' => $child['id']]);
                }
            }

        }


        return $result;
    }


    /**
     * 生成模块 select树形结构
     * @param int $selectId 需要选中的模块 id
     * @param int $currentCid 需要隐藏的模块 id
     * @return string
     */
    public function modulesTree($selectId = 0, $currentCid = 0)
    {
        $pluginModulesModel = db::name('plugin_modules');
        $pluginModulesModel->where('state', 1);
        if (!empty($currentCid)) {
            $pluginModulesModel->where('id','!=',$currentCid);
        }
        $categories = $pluginModulesModel->order("id ASC")->select()->toArray();
        $str = '';
        foreach ($categories as $item) {
            if ($selectId==$item['id']){
                $str .= '<option value="'.$item['id'].'" selected>'.$item['name'].'</option>';
            }else{
                $str .= '<option value="'.$item['id'].'">'.$item['name'].'</option>';
            }

        }

        return $str;
    }

    /**
     * 获取栏目文件缓存
     * $type  模块id
     * 不传type  返回全部栏目 传了type 返回该模块下的全部栏目
     */
    public function GetColumnFileCache($type=0){
        if (intval($type)==0){
            $data = cache('ColumnFileCache');
            if (!empty($data)){
                return $data;
            }
            $categories = $this->where(['delete_time'=>0,'status'=>1])->order("list_order ASC")->select()->toArray();
            if (empty($categories)){
                $categories = array();
            }
            cache('ColumnFileCache',$categories);
        }else{
            $name = 'ColumnFileCache_'.$type;
            $data = cache($name);
            if (!empty($data)){
                return $data;
            }
            $categories = $this->where(['delete_time'=>0,'status'=>1,'modules_id'=>$type])->order("list_order ASC")->select()->toArray();
            if (empty($categories)){
                $categories = array();
            }
            cache($name,$categories);
        }

        return $categories;
    }

    /**
     * 删除栏目文件缓存并重新生成新的文件缓存
     */
    public function DeleteColumnFileCache($type=0)
    {
        if (intval($type)==0){
            $categories = $this->where(['delete_time'=>0,'status'=>1])->order("list_order ASC")->select()->toArray();
            if (empty($categories)){
                $categories = array();
            }
            cache('ColumnFileCache',$categories);
        }else{
            $name = 'ColumnFileCache_'.$type;
            $categories = $this->where(['delete_time'=>0,'status'=>1,'modules_id'=>$type])->order("list_order ASC")->select()->toArray();
            if (empty($categories)){
                $categories = array();
            }

            cache($name,$categories);
        }

        return $categories;
    }

    /**
     * @param int|array $currentIds
     * @param string $tpl
     * @return string
     */
    public function adminColumnTableTree($currentIds = 0, $tpl = '',$modulesId=0)
    {
        $where['delete_time']      = 0;
        if ($modulesId>0){
            $where['modules_id']      = $modulesId;
        }
        $categories = $this->where($where)->order("list_order ASC")->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        if (!is_array($currentIds)) {
            $currentIds = [$currentIds];
        }

        $newCategories = [];
        foreach ($categories as $item) {
            $item['checked'] = in_array($item['id'], $currentIds) ? "checked" : "";
            $item['url']     = cmf_plugin_url('Modules://Content/index', ['id' => $item['id']]);;
            $item['str_action'] =
                '<a href="' . cmf_plugin_url('Modules://AdminColumn/add', ["parent" => $item['id']]) .'">添加子分类</a> |'.
                '<a href="' . cmf_plugin_url('Modules://AdminColumn/edit', ["id" => $item['id']]) .'">编辑</a> | '.
                '<a class="js-ajax-delete" href="' . cmf_plugin_url('Modules://AdminColumn/delete', ["id" => $item['id']]) .'">删除</a> ';
            array_push($newCategories, $item);
        }

        $tree->init($newCategories);

        if (empty($tpl)) {
            $tpl = "<tr>
                        <td><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='input-order'></td>
                        <td>\$id</td>
                        <td>\$spacer <a href='\$url' target='_blank'>\$name</a></td>
                        <td>\$description</td>
                        <td>\$str_action</td>
                    </tr>";
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }

    /**
     * 获取栏目默认的关键字
     * $type  模块id
     * $id    栏目id
     */

    public function getDefkeywordsbymodulesId($type = 0){
        $data = $this->GetColumnFileCache($type);
        $re = '';
        foreach ($data as $k=>&$v){
            if ($v['parent_id'] ==0){
                $res = unserialize($v['def_keyword']);
                if (!empty($res)){
                    $re = $v['def_keyword'];
                }

            }
            break ;
        }

        return unserialize($re);
    }

    /**
     * 根据模块id和栏目id 获取相应栏目信息
     *   $type  模块id
     *  $id  栏目id
     */
    public function getColumnById( $type=0 ,$id=0){
        if ($id==0 || $type==0){
            return array();
        }
        $data = $this->GetColumnFileCache($type);
        $re = array();
        foreach ($data as $k=>&$v){
            if ($v['id'] ==$id){
                $re = $v;
                break ;
            }

        }
        return $re;
    }
}