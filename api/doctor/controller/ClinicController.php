<?php

// +----------------------------------------------------------------------
namespace api\doctor\controller;

use think\Db;
use api\user\service\SpringService;
use cmf\controller\RestBaseController;

class ClinicController extends RestBaseController
{
    

    public function index(){

        $parent_id = request()->param('parent_id', 0, 'intval');    //上级id
        $list      = Db::name('cy_clinic')->field('id,name,clinic_no')->where('parent_id',$parent_id)->select();
        
        $this->success('ok', $list);
    }

}
