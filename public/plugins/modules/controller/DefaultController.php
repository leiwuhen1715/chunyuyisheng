<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class DefaultController extends HomeBaseController
{
    /***
     * 文章列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    protected function initialize()
    {   
        parent::initialize();

        $this->moduleid = $moduleid;
        $this->table='item_default';
        $this->filds='id,title,showtime,picture,digest';
        $this->assign('module_id',$this->moduleid);
        $this->assign('act',$act);
        $this->assign('posti',$posti);
    }

    public function index()
    {

        $id  = $this->request->param('id', 0, 'intval');

        $where=['state'=>1];
        if($id)$where['category'] = $id;

        $order=[
            'list_order'  => 'asc',
            'id'          => 'desc'
        ];
        //列表信息
        $list = Db::name($this->table)->field($this->filds)->where($where)->order($order)->paginate(8);

        //分类信息
        $where = [
            'modules_id' => $this->moduleid,
            'status'     => 1,
            'id'         => $id
        ];
        $category = DB::name('plugin_modules_column')->where($where)->find();

   
        // 获取分页显示
        $page = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('category',$category);

        return $this->fetch();
    }

    public function detail()
    {
        $id  = $this->request->param('id', 0, 'intval');
        
        $text_table = $this->table.'_texts';
        $join = [["$text_table t",'c.id = t.itemid','left']];
        //详情信息
        $data = Db::name($this->table)->alias('c')->join($join)->where(['id'=>$id,'state'=>1])->find();
        
        if (empty($data)) {
            abort(404, '文章不存在!');
        }

        //分类信息
        $category = db::name('plugin_modules_column')->field('id,name')->where(['id'=>$data['category'],'status'=>1])->find();
        $this->assign('category',$category);
        
        Db::name($this->table)->where(['id'=>$id,'state'=>1])->setInc('pv');
        //上一篇
        $prev = DB::name($this->table)->where('state',1)->where("id",'<',$id)->order("id","desc")->find();
        //下一篇
        $next = DB::name($this->table)->where('state',1)->where("id",'>',$id)->order("id","asc")->find();
      
        $this->assign('data', $data);
        $this->assign('next', $next);
        $this->assign('prev', $prev);

        return $this->fetch();
    }

}
