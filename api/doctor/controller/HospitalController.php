<?php

// +----------------------------------------------------------------------
namespace api\doctor\controller;

use think\Db;
use api\user\service\SpringService;
use api\doctor\model\HospitalModel;
use cmf\controller\RestBaseController;
use cmf\controller\RestUserBaseController;

class HospitalController extends RestBaseController
{

    //医院列表
    public function index(){
        $limit    = request()->param('limit',10,'intval');
        $city_id  = request()->param('city',0,'intval');

        $is_three = request()->param('is_three',0,'intval');
        $is_pulic = request()->param('is_three',0,'intval');
        $origin_lat = request()->param('origin_lat',0,'trim');
        $origin_lng = request()->param('origin_lng',0,'trim');

        $model = new HospitalModel;

        $where[] = ['state' ,'=', 1];

        if (!empty($is_three) || !empty($is_pulic)){
            $where[] = ['type_level', '=' ,'三级甲等'];
        }
        if($city_id){
            $city = Db::name('PluginModulesCitys')->where('code',$city_id)->find();
            if($city){
                $name = $city['name'];
                if($city['level'] == 1){
                    $where[] = ['citys','like',$name.',%'];
                }elseif($city['level'] == 2){
                    $where[] = ['citys','like','%,'.$name.',%'];
                }else{
                    $where[] = ['citys','like','%,'.$name];
                }
            }
        }

        $data = $model->field('id,list_order,title,picture,type_level,pv,lat,lng,address')
            ->where($where)
            ->order(['list_order'=>'asc','id'=>'desc'])->paginate($limit);
        $list = $data->items();

        foreach ($list as  $k=>$v){
            $v['range'] = $this->getWalkingDistance($origin_lat,$origin_lng,$v['lat'],$v['lng']);
        }

        $this->success('产品获取成功!', $list);
    }


    //医院详情
    public function detail(){
       $id = request()->param('id',0,'intval');
       $model = new HospitalModel;
       $data = $model->relation('detail')->where(['id'=>$id,'state'=>1])->find();
     
       $this->success('ok',$data);
    }
    //医院医生
    public function doctor(){
        
        $id    = request()->param('id',0,'intval');
        $model = new HospitalModel;
        $name  = $model->where(['id'=>$id,'state'=>1])->value('title');
        if($name){
            $user_id  = $this->getUserId();
          
            $page    = request()->param('page',1, 'intval');    //页码
            
            $service = new SpringService($user_id);
         
            $res = $service->search_doctors($name,$page);
         
            if($res['error'] == 0){
                $this->success('ok', $res['doctors']);
            }else{
                $this->error($res['error_msg']);
            }
        }else{
            $this->error('医院不存在');
        }
    }


    /*
     * 根据客户端经纬度获取公里数
     * @param $origin_lat  sting  客户端纬度
     * @param $origin_lng  string 客户端经度
     * @prram $lat   医院维度
     * @param $lng   医院经度
     * @return  路程(千米)
     */
    public function getWalkingDistance($origin_lat,$origin_lng,$lat,$lng)
    {
        $origin_lat = (double)$origin_lat;
        $origin_lng = (double)$origin_lng;
        $lat = (double)$lat;
        $lng = (double)$lng;
        $key = "fOMFdGtroP5505O68XxYof1pa2RsBlNv";

        $url = 'http://api.map.baidu.com/directionlite/v1/driving?origin='.$origin_lat.','.$origin_lng.'&destination='.$lat.','.$lng.'&ak='.$key;

        $res = cmf_curl_get($url);

        $data = json_decode($res,true);

        if ($data['status'] == 0){
            //return ($data['result']['routes'][0]['distance'])/1000;

            $gongli = ($data['result']['routes'][0]['distance'])/1000;
            return $gongli;

        }else{
            return  '暂时无法获取';
        }

    }
}

