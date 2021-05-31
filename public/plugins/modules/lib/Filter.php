<?php
// +----------------------------------------------------------------------
// | 内容编辑--文本中间处理类
// +----------------------------------------------------------------------
namespace plugins\modules\lib;


class Filter{

    public static $isCreate;//是否是添加

    /**
     * 提交内容后处理数据格式
     * @desc 如将"1999-12-30 23:59"处理成"时间戳"
     * @param $post，格式[ $table => [ $field=>[ 'value'=>xxx, 'type'=>'ctime'] ] ]
     * @return array
     */
    public static function formatPost($post, $isCreate=false){
        self::$isCreate = $isCreate;
        if(is_array($post))
            foreach($post as $table=>$list){
                if(is_array($list))
                    foreach($list as $key=>$val){
                        if(is_array($val)){
                            if(isset($val['type'])){
                                //编辑的时候，去掉添加内容时的系统值提交，如create_time字段值
                                if(!self::$isCreate && $val['type']=='system_create'){
                                    unset($post[$table][$key]);
                                }else{
                                    $post[$table][$key] = self::_format_value($val);
                                }
                            }else{
                                $post[$table][$key] = implode(',', array_filter($val));
                            }
                        }
                    }
            }

        return $post;
    }

    /**
     * 清除字符串(文章/内容等)中的样式和空标签
     * @param $data 字符串(文章/内容等)
     */
    public static function clearStyle($data=''){
        $data = htmlspecialchars_decode($data);
        preg_match_all('/height="(\d*)"?/', $data, $height);
        preg_match_all('/width="(\d*)"?/', $data, $width);
        preg_match_all('/height:([\s\S]*?);/', $data, $s_with);
        preg_match_all('/width:([\s\S]*?);/', $data, $s_height);
        preg_match_all('/font-size:([\s\S]*?);/', $data, $s_font);
        //preg_match_all('/style="([\s\S]*?)"/',$data,$style);//style="text-align:center" style="text-align:center;"
        $str = array('style="text-align:center"', 'style="text-align:center;"');
        $data = str_replace($str, 'align="center"', $data);
        preg_match_all('/<p>([<br\/>]*?)<\/p>/', $data, $p);
        $find = array_merge($height[1], $width[1], $p[0], $s_with[0], $s_height[0], $s_font[0]);
        $data = str_replace($find, '', $data);

        return $data;
    }


    //根据类型处理成期望结果类型
    private static function _format_value($val){
        $value = isset($val['value']) ? $val['value'] : '';
        $type  = $val['type'];
        unset($val['type']);
        switch($type){
            case 'ctime':
            case 'cdate':
                return strtotime($value);
            case 'system':
            case 'system_create':
                switch($value){
                    case 'admin_id':
                        return cmf_get_current_admin_id();
                    case 'time':
                    case 'system_time':
                        return time();
                    default:
                        if(strpos($value,'id')!==false) return cmf_get_current_admin_id();
                        elseif(strpos($value,'time')!==false) return time();
                        else return '';
                }
                break;
            case 'password':
                return strlen($value)==32 ? $value : md5($value);
            case 'editor':
                //这里是编辑器内容提交数据处理。CMF的函数cmf_replace_content_file_url报错，此处请自行按需修改。
                return htmlspecialchars_decode($value);
            case 'cityid':
                unset($val['value']);
                return end($val);
            default:
                return $value;
        }
    }

}