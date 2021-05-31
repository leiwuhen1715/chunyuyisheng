<?php

namespace app\goods\controller;
use cmf\controller\HomeBaseController;
use app\goods\service\ApiService;
use think\Db;

class ListController extends HomebaseController {

	// 前台产品列表
	public function index() {

	    $id = $this->request->param('id',0,'intval');
		$category=ApiService::term($id);

		$all_catid = ApiService::getCatId($id); //获取所有子类
						
		if(empty($category)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    $this->display(":404");
		    return;
		}
		$allcategory=ApiService::terms();
		$crumbs = ApiService::breadcrumb($id);

		$order = [
			'list_order' => 'asc',
			'on_time'    => 'desc'
		];
		if(empty($all_catid)){
			$where=array("cat_id"=>$id,"is_on_sale"=>1,"is_delete"=>"neq 3");
		}else{
			$all_catid[]=$id;
			$where=array("cat_id"=>array('in',implode(',',$all_catid)),"is_on_sale"=>1,"is_delete"=>"neq 3");
		}

		$list = Db::name('Goods')
		->where($where)
		->order($order)
		->paginate(15);
		$page = $list->render();
		
		
    	$this->assign('crumbs', $crumbs);    	
    	$this->assign('category', $category);
    	$this->assign('allcategory', $allcategory);


		$this->assign('list', $list);
		$this->assign("page", $page);
		$this->assign('cat_id', $id);
		$this->assign('act_title', '产品列表');
		$this->assign('act', 'goods');
    	return $this->fetch();
	}
	public function ajax() {
	    $id=I('get.id',0,'intval');
		$category=ApiService::term($id);

		$all_catid = ApiService::getCatId($id); //获取所有子类
						
		if(empty($category)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    $this->display(":404");
		    return;
		}
		$allcategory=ApiService::terms();
		$crumbs = ApiService::breadcrumb($id);

		$sort = $_GET['order'] == 'desc' ? 'asc' : 'desc';
		
		if($sort == 'desc' && isset($_GET['order'])){
			$sort_icon = '<i class="fa fa-long-arrow-up"></i>';
		}else if($sort == 'asc' && isset($_GET['order'])){
			$sort_icon = '<i class="fa fa-long-arrow-down"></i>';
		}
		
		if(isset($_GET['sort']) && isset($_GET['order'])){			
			$order = "{$_GET['sort']} {$_GET['order']},on_time DESC";
		}
		
		if(empty($all_catid)){
			$where=array("cat_id"=>$id,"is_on_sale"=>1,"type"=>0,"is_delete"=>"neq 3");
		}else{
			$all_catid[]=$id;
			$where=array("cat_id"=>array('in',implode(',',$all_catid)),"is_on_sale"=>1,"type"=>0,"is_delete"=>"neq 3");
		}
		$count=M('Goods')->where($where)->count();
		$list = M('Goods')
		->where($where)
		->order($order)
		->paginate(15);
		$page = $list->render();
		$str='';
		foreach ($list as $key => $vo) {
			$str.='<li class="item01">
                        <a href="'.U('goods/article/index',array('g_id'=>$vo['goods_id'])).'" class="pic"><img src="'.cmf_get_image_url($vo[goods_img]).'" alt="'.$vo[goods_name].'"></a>
                        <div class="txt">
                            <p>'.$vo[goods_name].'</p>
                            <div class="icon">
                                <p>
                                    <i class="iconfont">&#xe66b;</i>
                                    <span>'.$vo[click_count].'</span>
                                </p>
                                <p class="message">
                                    <i class="iconfont">&#xe6c9;</i>
                                    <span>'.$vo[comment_count].'</span>
                                </p>
                                <p class="praise">
                                    <i class="iconfont">&#xe70b;</i>
                                    <span>'.$vo[praise_count].'</span>
                                </p>
                            </div>
                        </div>
                    </li>';
		}
		$data=[
			'count'	=>$count,
			'str'	=>$str
		];
		die(json_encode($data));
	}
	
}
