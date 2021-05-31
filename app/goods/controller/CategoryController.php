<?php

namespace app\goods\controller;
use cmf\controller\HomeBaseController;
use app\goods\service\ApiService;
use think\Db;
/**
 * 首页
 */
class CategoryController extends HomebaseController {
	
	protected function initialize()
    {
        parent::initialize();
        $this->assign('act_title', '分类');
        $this->assign('act_index','cate');
        
    }

    //首页 
	public function index() {
		
	    $allcategory=ApiService::terms();
	    
    	$this->assign('allcategory', $allcategory);
    	
		$this->assign('act', 'goods');

    	return $this->fetch();
	}
	public function ajax() {
	    $allcategory=ApiService::terms();
		$sort = $_GET['order'] == 'desc' ? 'asc' : 'desc';
		
		if($sort == 'desc' && isset($_GET['order'])){
			$sort_icon = '<i class="fa fa-long-arrow-up"></i>';
		}else if($sort == 'asc' && isset($_GET['order'])){
			$sort_icon = '<i class="fa fa-long-arrow-down"></i>';
		}
		
		if(isset($_GET['sort']) && isset($_GET['order'])){			
			$order = "{$_GET['sort']} {$_GET['order']},on_time DESC";
		}
		$tid=I('get.tagid',0,'intval');
		
		$where=array("is_on_sale"=>1,"type"=>0,"is_delete"=>"neq 3");
		if($tid){
			$tag           = Db::name('PortalTag')->where(['status'=>1,'id'=>$tid])->find();
			if($tag){
				$where['tag']=['like','%'.$tid.'%'];
			}
		}
		$count=M('Goods')->where($where)->count();
		$list = M('Goods')
		->where($where)
		->order($order)
		->paginate(15);
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


