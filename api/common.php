<?php
use think\Db;

function deep_in_array($value, $array) {
    foreach($array as $item) {
        if(!is_array($item)) {
            if ($item == $value) {
                return $item;
            } else {
                continue;
            }
        }

        if(in_array($value, $item)) {
            return $item;
        } else if(deep_in_array($value, $item)) {
            return $item;
        }
    }
    return false;
}

function get_small_images($img,$width,$height){
    if (strpos($img, "http") === 0) {
        return $img;
    } else if (strpos($img, "https") === 0) {
        return $img;
    }

    $path = WEB_ROOT."upload/";

    if(!file_exists($path.$img)){
        return;
    }

    //$thumb_path = $path.'small/thumb/';
    $thumb_name = "small/".str_replace('/','-',substr($img,0,-4))."small_{$width}_{$height}.jpg";


    if(file_exists($path.$thumb_name)){
        return cmf_get_image_url($thumb_name);
    }
    /*
    if(!is_dir($thumb_path)){
         mkdir($thumb_path,0777,true);
    }*/

    if(file_exists($path.$img)){
        $image        = \think\Image::open($path.$img);

        $image->thumb($width, $height,\think\Image::THUMB_CENTER)->save($path.$thumb_name);
    }

    return cmf_get_image_url($thumb_name);
}

function del0($s)   
{   
    $s = trim(strval($s));   
    if (preg_match('#^-?\d+?\.0+$#', $s)) {   
        return preg_replace('#^(-?\d+?)\.0+$#','$1',$s);   
    }    
    if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {   
        return preg_replace('#^(-?\d+\.[0-9]+?)0+$#','$1',$s);   
    }   
    return $s;   
}

function cmf_api_user_action($action,$userId=0)
{

    if (empty($userId)) {
        return;
    }
    $findUserAction = Db::name('user_action')->where('action', $action)->find();

    if (empty($findUserAction)) {
        return;
    }
    $changeScore = false;

    if ($findUserAction['cycle_type'] == 0) {
        $changeScore = true;
    } elseif ($findUserAction['reward_number'] > 0) {
        $findUserScoreLog = Db::name('user_score_log')->where(['user_id'=>$userId,'action'=>$action])->order('create_time DESC')->find();
        if (!empty($findUserScoreLog)) {
            $cycleType = intval($findUserAction['cycle_type']);
            $cycleTime = intval($findUserAction['cycle_time']);
            switch ($cycleType) {//1:按天;2:按小时;3:永久
                case 1:
                    $firstDayStartTime = strtotime(date('Y-m-d', $findUserScoreLog['create_time']));
                    $endDayEndTime     = strtotime(date('Y-m-d', strtotime("+{$cycleTime} day", $firstDayStartTime)));

                    $findUserScoreLogCount = Db::name('user_score_log')->where([
                        ['user_id' ,'=',$userId],
                        ['create_time' ,['>', $firstDayStartTime], ['<', $endDayEndTime]],
                    ])->count();
                    if ($findUserScoreLogCount < $findUserAction['reward_number']) {
                        $changeScore = true;
                    }
                    break;
                case 2:
                    if (($findUserScoreLog['create_time'] + $cycleTime * 3600) < time()) {
                        $changeScore = true;
                    }
                    break;
                case 3:

                    break;
            }
        } else {
            $changeScore = true;
        }
    }

    if ($changeScore) {

        if($findUserAction['score'] > 0){
            log_score_change($userId, $findUserAction['name'],$findUserAction['score'],$action);
        }
    }

}
