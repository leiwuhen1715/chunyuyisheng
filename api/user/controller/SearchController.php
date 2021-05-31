<?php
/**
 * SearchController.php
 * 文件描述:
 * Created on 2021/4/16 9:50
 * Create  by peipei.song
 */

namespace api\user\controller;

use cmf\controller\RestBaseController;
use think\Db;

class SearchController extends RestBaseController
{
    public function index()
    {
        $goods_name = $this->request->param('name');
        $limit      = $this->request->param('limit', '10', 'intval');
        $order      = $this->request->param('order', 'goods_id');
        $page       = $this->request->param('page', '1', 'intval');
        $sort       = $this->request->param('sort');


        if (empty($goods_name)) {
            $this->error('无匹配结果');
        }
        $asc = $sort == '-' ? 'asc' : 'desc';
        switch ($order) {
            case 'sales_sum':
                $order = ['sales_sum' => $asc];  //销量
                break;
            case 'shop_price':
                $order = ['shop_price' => $asc];  //价格
                break;
            case 'click_count':
                $order = ['click_count' => $asc];   //热度
                break;
            case 'is_recommend':
                $order = ['is_recommend' => $asc];   //是否推荐
                break;
            default:
                $order = ['sales_sum' => 'asc', 'shop_price' => 'desc'];
                break;
        }

        $field = 'goods_id,goods_name,goods_img,shop_price,click_count,sales_sum,is_recommend';

        empty($page) ? '1' : $page;

        $goods = Db::name('goods')->field($field)
            ->where('goods_name|keywords', ['like', '%' . $goods_name . '%'], ['like', '%' . $goods_name . '%'], 'or')
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();

        foreach ($goods as $k => $v) {

            $goods[$k]['good']     = Db::name('goods_comment')->where([['goods_id', '=', $goods[$k]['goods_id']], ['goods_rank', 'egt', 3]])->count(); //好评
            $goods[$k]['ingood']   = Db::name('goods_comment')->where([['goods_id', '=', $goods[$k]['goods_id']], ['goods_rank', '=', 2]])->count();   //中评
            $goods[$k]['nogood']   = Db::name('goods_comment')->where([['goods_id', '=', $goods[$k]['goods_id']], ['goods_rank', '=', 1]])->count();   //差评
            $goods[$k]['comments'] = Db::name('goods_comment')->where('goods_id', $goods[$k]['goods_id'])->count();  //总评价数

            if($goods[$k]['good'] + $goods[$k]['ingood'] == 0){
                $goods[$k]['praise_rate']  = "100%";
            }else{
                $goods[$k]['praise_rate'] = round((($goods[$k]['good'] + $goods[$k]['ingood']) / $goods[$k]['comments']) * 100, 2) . '%';
            }
        }

        if ($goods) {
            $this->success('ok', $goods);
        } else {
            $this->error('无匹配结果');
        }
    }


    //商品详情评价统计
    public function getEvaluate()
    {

        $id = $this->request->param('goods_id', '0', 'intval');

        $data['good']     = Db::name('goods_comment')->where([['goods_id', '=', $id], ['goods_rank', 'egt', 3]])->count(); //好评
        $data['ingood']   = Db::name('goods_comment')->where([['goods_id', '=', $id], ['goods_rank', '=', 2]])->count();   //中评
        $data['noGood']   = Db::name('goods_comment')->where([['goods_id', '=', $id], ['goods_rank', '=', 1]])->count();   //差评
        $data['comments'] = Db::name('goods_comment')->where('goods_id', $id)->count();  //总评价数

        $data['praise_rate'] = round((($data['good'] + $data['ingood']) / $data['comments']) * 100, 2) . '%';

        if ($data) {
            $this->success('ok', $data);
        } else {
            $this->success('error');
        }
    }


}

