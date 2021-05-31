<?php

namespace app\goods\controller;
use cmf\controller\HomeBaseController;
use think\Db;
use app\goods\service\ApiService;

class SearchController extends HomebaseController {

	// 搜索产品
	public function index() {
	    $keyword = $this->request->param('keyword');		
		if (empty($keyword)) {
			$this->error("关键词不能为空！请重新输入！");
		}
		$allcategory=ApiService::terms();
		
        $keywordComplex['goods_name|goods_sn|keywords']    = ['like', "%$keyword%"];
        
		$where=array("is_on_sale"=>1,"is_delete"=>"neq 3");
		
		$list = Db::name('Goods')->whereOr($keywordComplex)->where($where)->paginate(12);
		$page = $list->render();

		$this->assign('allcategory', $allcategory);
		$this->assign('list', $list);
		$this->assign('page',$page);
		$this->assign('keyword', $keyword);
		$this->assign('act_title', $keyword);
    	return $this->fetch();
	}
			
}
