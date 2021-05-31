<?php

// +----------------------------------------------------------------------
namespace api\doctor\controller;

use api\user\service\SpringService;
use cmf\controller\RestBaseController;
use cmf\controller\RestUserBaseController;

class IndexController extends RestBaseController
{

    //医生列表
    public function list(){
        $user_id  = $this->getUserId();
    	$service = new SpringService($user_id);
    	$clinic_no = request()->param('clinic_no', '3', 'trim');  //科室编号
    	$famous_doctor = $this->request->param('famous_doctor',0,'intval');  //是否筛选名医
        $page    = request()->param('page', 0, 'intval');    //页码
        $limit   = request()->param('limit', 10, 'intval');  //每页数量
        $province = $this->request->param('province','',''); //省份
        $city = $this->request->param('city','',''); //城市
        $service_type = $this->request->param('service_type','','trim'); //不填为默认获取开通图文服务的医生，值为inquiry表示获取开通电话服务的医生
        

        $page    = $page==0?1:$page;
        $start   = ($page-1)*$limit;

    	$res = $service->get_doctors($start,$limit,$clinic_no,$service_type,$famous_doctor,$province,$city);

        if($res['error'] == 0){
            $this->success('ok', $res['doctors']);
        }else{
            $this->error($res['error_msg']);
        }
        
    }
    //搜索医生
    public function search(){
        $user_id  = $this->getUserId();
        
        $page    = request()->param('page',1, 'intval');    //页码
        $keyword = request()->param('keyword','', 'trim');  //关键字

        $service = new SpringService($user_id);
        if(empty($keyword))    $this->error('请输入关键字');
       
        $res = $service->search_doctors($keyword,$page);
     
        if($res['error'] == 0){
            $this->success('ok', $res['doctors']);
        }else{
            $this->error($res['error_msg']);
        }
        
    }
    //推荐医生
    public function recommend(){
        $ask = request()->param('ask','','trim');
        if(empty($ask))     $this->error('请填写问题');

        $user_id  = $this->getUserId();
        $service = new SpringService($user_id);
        $res = $service->recommend_doctors($ask);
     
        if($res['error'] == 0){
            $this->success('ok', $res['doctors']);
        }else{
            $this->error($res['error_msg']);
        }

    }
    /*医生详情*/
    public function detail(){
        $user_id  = $this->getUserId();
        $doctor_id = request()->param('doctor_id');
        $service = new SpringService($user_id);
        
        $res = $service->doctor_detail($doctor_id);
       
        if($res['error'] == 0){
            $this->success('ok', $res);
        }else{
            $this->error($res['error_msg']);
        }
    }
    
    /*问题详情*/
    public function problemDetail()
    {
        $order_sn = request()->param('order_sn');
        $user_id  = $this->getUserId();
        $service = new SpringService($user_id);

        $res = $service->problem_detail($order_sn);

        if ($res['error'] == 0){
            $this->success('ok',$res);
        }else{
            $this->error($res['error_msg']);
        }

    }
    
    /*获取医生电话信息*/
    public function getDoctorPhoneInfo()
    {
        $doctor_id = $this->request->param('doctor_id'); //医生id
        $user_id = $this->getUserId();

        $service = new SpringService($user_id);

        $res = $service->get_doctor_phone_info($doctor_id,$user_id);

        if ($res['error'] == 0){
            $this->success('ok',$res);
        }else{
            $this->error($res['error_msg']);
        }

    }

}

