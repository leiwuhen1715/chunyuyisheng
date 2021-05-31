<?php
/**
 * UserController.php
 * 文件描述:
 * Created on 2021/5/14 11:17
 * Create  by peipei.song
 */
namespace api\doctor\controller;

use api\doctor\model\DoctorOrderModel;
use cmf\controller\RestBaseController;
use think\Db;

class UserController extends RestBaseController {
    //历史患者
    public function index()
    {
        $userId = $this->getUserId();
        
        $field = 'patient_name,patient_sex,patient_age';
        /*
        $res = Db::name('doctor_order')
            ->field($field)
            ->group('patient_name')
            ->where('user_id',$userId)
            ->where('patient_name','<>','')
            ->order('add_time  desc')->limit(10)->select();
        */
        $where = [
            ['user_id','=',$userId],
            ['order_type','=',1]
        ];
        $data = DoctorOrderModel::where('user_id',$userId)->field($field)->where($where)->group('patient_name')->order('id','desc')->limit(10)->select();

        if ($data){
            $this->success('ok',$data);
        }else{
            $this->error('0');
        }
    }
    
    
    //症状标签
    public function getLabelList()
    {
        $field = 'label_name';

        $res = Db::name('disease_label')->field($field)->select();

        if ($res){
            $this->success('ok',$res);
        }else{
            $this->error('error');
        }
    }




}