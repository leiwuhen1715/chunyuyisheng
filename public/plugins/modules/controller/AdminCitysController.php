<?php
// +----------------------------------------------------------------------
// 城市信息控制器
// +----------------------------------------------------------------------
namespace plugins\modules\controller;

use cmf\controller\PluginAdminBaseController;
use plugins\modules\model\PluginModulesCitysModel;

class AdminCitysController extends PluginAdminBaseController
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PluginModulesCitysModel();
    }

    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->except(['_plugin','_controller','_action']);
        $data = $this->model->getList($param);
        $data->appends($param);

        //$this->assign('level3',array('未设置','直辖市','省会行政中心','有分区的大城市', '县城和郊区', '城区'));
        $this->assign('level3',array('','直辖市','省会','地级市','区县 ','主城区'));
        if(count($param)) $this->assign($param);
        $this->assign('data', $data->items());
        $this->assign('page', $data->render());
        return $this->fetch();
    }



    /**
     * 编辑城市
     */
    public function edit()
    {
        $param = $this->request->except(['_plugin','_controller','_action']);
        if(isset($param['id'])){
            $data = $this->model->where('id',$param['id'])->find();
            $this->assign('data',$data);
        }else{
            $this->error('无效的操作');
        }

        if(isset($_SERVER["HTTP_REFERER"])){
            $this->assign('referer',$_SERVER["HTTP_REFERER"]);//编辑前来源页面
        }

        return $this->fetch();
    }

    /**
     * 编辑城市提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->except(['_plugin','_controller','_action']);
            $oid    = $data['oid'];
            $post   = $data['post'];
            if(!$post['id'])        $this->error('无效的ID');
            //修改ID的特殊处理
            if($post['id'] != $oid){
                $newId = $post['id'];
                $exist = $this->model->where('id',$newId)->value('id');
                if($exist){
                    $this->error('更改的【ID】已经存在');
                }
            }
            //验证数据
            if($post['level']=='0'){
                $post['level'] = $this->model->getLevelById($post['id']);
            }
            //检查同级拼音重复
            $pinyin = $this->model->where('level',$post['level'])->where('pinyin',$post['pinyin'])->value('id');
            if($pinyin && $pinyin !=$post['id'] ){
                $this->error('更改的【拼音】已经存在！');
            }

            foreach($post as $k=>$v){
                if(is_array($v)) $post[$k] = implode(',',$v);
            }
            $this->model->where('id',$oid)->update($post);

            $this->success('保存成功!',html_entity_decode($data['referer']),'',2);
        }
    }


    /**
     * 添加城市
     */
    public function add()
    {
        return $this->fetch();
    }


    /**
     * 添加城市提交
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->except(['_plugin','_controller','_action']);
            $post   = $data['post'];
            if(!$post['id']){
                $this->error('无效的城市ID');
            }

            //修改ID的特殊处理
            if($post['id']){
                $newId = $post['id'];
                $exist = $this->model->where('id',$newId)->value('id');
                if($exist){
                    $this->error('添加的【ID】已经存在');
                }
            }
            //验证数据
            if($post['level']=='0'){
                $post['level'] = $this->model->getLevelById($post['id']);
            }
            //检查同级拼音重复
            $pinyin = $this->model->where('level',$post['level'])->where('pinyin',$post['pinyin'])->value('id');
            if($pinyin){
                $this->error('添加的【拼音】已经存在！');
            }

            foreach($post as $k=>$v){
                if(is_array($v)) $post[$k] = implode(',',$v);
            }
            $this->model->insert($post);

            $this->success('保存成功!');
        }
    }


    /**
     * 删除城市/隐藏城市
     */
    public function delete()
    {
        $param = $this->request->except(['_plugin','_controller','_action']);

        if(isset($param['id']) && $param['id']){
            $result = $this->model->where('id', intval($param['id']))->update(['state' => 0]);
        }
        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $this->model->where(['id' => ['in', $ids]])->update(['state' => 0]);
        }

        if (isset($result)) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败');
        }
    }


    /**
     * AJAX设置3级城市属性
     */
    public function setLevel3()
    {
        $param = $this->request->except(['_plugin','_controller','_action']);

        if(isset($param['id']) && $param['id']){
            $result = $this->model->where('id', intval($param['id']))->update(['level3' => intval($param['level3'])]);
        }
        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $this->model->where(['id' => ['in', $ids]])->update(['level3' => intval($param['level3'])]);
        }

        if (isset($result)) {
            $this->success('设置成功!');
        } else {
            $this->error('保存失败');
        }
    }




    /**
     * 获取子级城市HTML控件列表（FORM控件使用）
     * @param pid 父级城市ID
     * @param selectName2 选中的2级城市名称
     * @param selectName3 选中的3级城市名称
     */
    public function getAjaxChildCity(){
        $param = $this->request->except(['_plugin','_controller','_action']);
        $reStr = '';
        if($param['pid'] > 0 ){
            $city = $this->model->field('id,level,name')->where('pid',$param['pid'])->select();
            foreach($city as $v){
                if($v['level']==2 && isset($param['selectName2']) && ($v['name']==$param['selectName2'] || $v['id']==$param['selectName2']) ||
                    $v['level']==3 && isset($param['selectName3']) && ($v['name']==$param['selectName3'] || $v['id']==$param['selectName3'])){
                    $selectName3 = isset($param['selectName3'])?$param['selectName3']:'';
                    $reStr .= "<a onclick=\"checkcity({$v['level']},{$v['id']},'{$v['name']}','{$param['selectName2']}','{$selectName3}');\" id=\"a{$v['id']}\" class=\"on\">{$v['name']}</a>";
                    $reStr .= "<script>checkcity({$v['level']},{$v['id']},'{$v['name']}','{$param['selectName2']}','{$selectName3}')</script>";
                }else{
                    $reStr .= "<a onclick=\"checkcity({$v['level']},{$v['id']},'{$v['name']}');\" id=\"a{$v['id']}\" >{$v['name']}</a>";
                }
            }
        }
        return $reStr;
    }




    //根据ID获取城市信息
    public function getcity($id){
        if(!$id){
            return array();
        }
        $data = $this->model->field('id,level,name')->where(['id' => ['in', $id]])->select()->toArray();
        $data = array_column($data, 'name', 'id');

        return $data;
    }
    
    public function hot(){
        $id     = request()->param('id',0,'intval');
        $is_hot = request()->param('is_hot',0,'intval');
        
        $this->model->where('id',$id)->update(['is_hot'=>$is_hot]);
        $this->success('ok');
    }



}
