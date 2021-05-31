<?php

namespace app\goods\controller;

use cmf\controller\AdminBaseController;
use app\goods\model\GoodsMoldModel;
use app\goods\model\SpecModel;
use think\db;

class AdminSpecController extends AdminbaseController {

	protected $spec_model;
	function initialize() {
		parent::initialize();
		$this->goods_type = new GoodsMoldModel;
		$this->spec_model = new SpecModel;
	}

	// 后台产品分类列表
    public function index(){

		$typelist = $this->goods_type->select();
		
		$request = $this->request->param();
		$type_id = $this->request->param('type_id',0,'intval');
		$keyword = $this->request->param('keyword');
		$where   = [];
        if($keyword)$where[] = ['spec_name','like',"%$keyword%"];
		if($type_id)$where[] = ['type_id','=',$type_id];
		
		$join = [
            ['goods_mold b', 'a.type_id = b.id','left']
        ];

		$speclist=$this->spec_model->field('a.*,b.name')->alias('a')->join($join)->where($where)->order("a.id desc")->paginate(10);

		$speclist->appends($request);
        // 获取分页显示
        $page = $speclist->render();

		foreach($speclist as $key=>$v){
			$speclist[$key]['items'] = $this->specItem($v['id']);
		}

		$this->assign('page', $page);
		$this->assign('type_id',$type_id);
		$this->assign('typelist',$typelist);
		$this->assign('speclist',$speclist);
		return $this->fetch();
	}

	//获取规格
	public function specItem($id){
        $item = DB::name('SpecItem')->where("spec_id",$id)->order('id')->select();
		$arr2 = array();
		foreach($item as $key => $val){
			$arr2[$val['id']] = $val['item'];
		}
		$items = implode(',',$arr2);
        return $items;
	}

	//添加
	public function add(){
		$typelist = $this->goods_type->select();
		$this->assign('typelist',$typelist);
		return $this->fetch();
	}

	public function add_post(){
		if ($this->request->isPost()) {
			$request = $this->request->param();
			$data = $request['post'];
			if(empty($data['spec_name']))$this->error("属性名称不能为空！");
			$transStatus = 0;
			try {
	            //修改空间

	            $result=$this->spec_model->add($data);
				$item = explode(PHP_EOL,$data['item']);
				foreach ($item as $key => $val)  // 去除空格
				{
					$val = str_replace('_', '', $val); // 替换特殊字符
					$val = str_replace('@', '', $val); // 替换特殊字符

					$val = trim($val);
					if(empty($val))
						unset($item[$key]);
					else
						$item[$key] = $val;
				}

				foreach($item as $key => $val){
					$dataList[] = ['spec_id'=>$result['id'],'item'=>$val];
				}
				$result2 = DB::name('SpecItem')->insertAll($dataList);

	            $transStatus = 1;
	            // 提交事务
	            Db::commit();
	        } catch (\Exception $e) {
	            $Message= $e->getMessage();
	            $err=$Message;
	            // 回滚事务
	            Db::rollback();
        	}

			if ($transStatus==1) {
				$this->success("添加成功！");
			} else {
				$this->error($err);
			}

		}
	}

	//编辑
	public function edit(){
		$id = input('param.id', 0, 'intval');
		$data = $this->spec_model->where("id",$id)->find();
		$typelist = $this->goods_type->select();
		$item = DB::name('SpecItem')->where("spec_id",$id)->order('id')->select();
		$arr2 = array();
		foreach($item as $key => $val){
			$arr2[$val['id']] = $val['item'];
		}
		$items = implode(PHP_EOL,$arr2);

		$this->assign('typelist',$typelist);
		$this->assign('data',$data);
		$this->assign('items',$items);
		return $this->fetch();
	}

	public function edit_post(){
		if ($this->request->isPost()) {
			$request = $this->request->param();
			$data = $request['post'];
			if(empty($data['spec_name']))$this->error("规格名称不能为空！");
			
			$transStatus = 0;
			try {
	            //修改空间
	            $item = explode(PHP_EOL,$data['item']);

				foreach ($item as $key => $val)  // 去除空格
				{
					$val = str_replace('_', '', $val); // 替换特殊字符
					$val = str_replace('@', '', $val); // 替换特殊字符

					$val = trim($val);
					if(empty($val))
						unset($item[$key]);
					else
						$item[$key] = $val;
				}
				$result=$this->spec_model->edit($data);

				$db_items = DB::name('SpecItem')->where(array("spec_id" => $data['id']))->column('id,item');
				$dataList=[];
				foreach($item as $key => $val){
					 if(!in_array($val, $db_items)){//post的规格是否存在数据库
						 $dataList[] = array('spec_id'=>$data['id'],'item'=>$val);//不存在则添加
					 }
				}

				$result2 = DB::name('SpecItem')->insertAll($dataList);
				foreach($db_items as $k=>$v){
					if(!in_array($v,$item)){//数据库是否存在post的规格
						DB::name('SpecItem')->where(array("id"=>$k))->delete();//不存在则删除
					}
				}
	            $transStatus = 1;
	            // 提交事务
	            Db::commit();
	        } catch (\Exception $e) {
	            $Message= $e->getMessage();
	            $err=$Message;
	            // 回滚事务
	            Db::rollback();
        	}
			if ($transStatus==1) {
				$this->success("保存成功！");
			} else {
				$this->error($err);
			}

		}
	}


	//删除
	public function delete(){
		$id = input('param.id', 0, 'intval');
		$result = $this->spec_model->where("id",$id)->delete();
		DB::name('SpecItem')->where("spec_id",$id)->delete();
		if($result!==false){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	public function delete_all(){
		$request = $this->request->param();
		if(isset($request['ids'])){
			$ids = $request['ids'];
			
			if ($this->spec_model->where('id','in',$ids)->delete()!==false) {
				DB::name('SpecItem')->where("spec_id",'in',$ids)->delete();
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}else{
			$this->error("请选择删除项");
		}
	}

	public function listOrder()
    {
        parent::listOrders('spec');
        $this->success("排序更新成功！", '');
    }

}
