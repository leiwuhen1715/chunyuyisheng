<?php
/**
 * DoctorOrder.php
 * 文件描述:
 * Created on 2021/5/15 18:19
 * Create  by peipei.song
 */
namespace api\doctor\model;

use think\Model;

class HospitalTextsModel extends Model{

    protected $name = 'item_hospital_texts';
    
    public function getTextAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }
}