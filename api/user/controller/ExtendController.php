<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use api\user\service\CreatImgService;
use api\user\model\UserModel;
use think\Validate;
use think\Db;

class ExtendController extends RestUserBaseController
{

    public function getCode(){
        $user_id    = $this->getUserId();
        $promo_code = Db::name('user')->where('id',$user_id)->value('promo_code');
        if(empty($promo_code)){
            
            $promo_code = $this->make_coupon_card();
            Db::name('user')->where('id',$user_id)->update(['promo_code'=>$promo_code]);
            
        }
        $filename = 'qrcode/'.$user_id.'.png';
        if(!file_exists(WEB_ROOT.'/upload/'.$filename)){
            
            $value = cmf_get_domain().cmf_url('portal/index/index').'?promo_code='.$promo_code;         //二维码内容
           
            //设置二维码文件名
            $filename = 'qrcode/'.$user_id.'.png';
            //生成二维码
            
            require(CMF_ROOT.'/extend/phpqrcode/phpqrcode.php');
            \QRcode::png($value,WEB_ROOT.'/upload/'.$filename ,'L', 5, 2);
        }
        
        $result = [
            'promo_code' => $promo_code,
            'promo_img'  => cmf_get_image_url($filename)
        ];
        $this->success('ok',$result);
    }
    public function make_coupon_card() {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)].strtoupper(dechex(date('m'))).date('d').substr(time(),-5).substr(microtime(),2,5).sprintf('%02d',rand(0,99));
        for(
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 8;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }
    /**
     * 下线
     */
    public function tui()
    {
        $user_id = $this->getUserId();


        $type = $this->request->get('type', 0, 'intval');
        $page = $this->request->get('page', 1, 'intval');
        $limit = $this->request->get('limit', 10, 'intval');
        if($type == 0){
            $model = new UserModel();
            $where = [
                'f_id'=>$user_id
            ];
            $res = $model->field('id,user_nickname,mobile,create_time,avatar')->where($where)->order('id','desc')->page($page, $limit)->select();

        }else{
            $where = [
                'user_id' => $user_id,
                //'type'    => 1
            ];
            $res = Db::name('user_balance_log')->where($where)->order('id','desc')->page($page, $limit)->select()->toArray();

            foreach ($res as $key => $value) {
                $res[$key]['create_time'] = date("Y-m-d H:i",$value['create_time']);
            }
        }

        $this->success('ok!', $res);

    }
    /**
     * 下线
     */
    public function lists()
    {
        $user_id = $this->getUserId();


        $type = $this->request->get('type', 0, 'intval');


        $where = [
            'user_id' => $user_id
        ];
        if($type == 0){
            $where['change'] = ['>',0];
        }else{
            $where['change'] = ['<',0];
        }

        $data=Db::name('user_balance_log')->where($where)->order('id','desc')->paginate(10);
        $lists =$data->items();
        // $reslut=[
        //     'list'=>$lists['data'],
        //     'score'=>$score
        // ];


        $this->success('ok!', $lists);

    }

}
