<?php
namespace plugins\modules;

use think\template\TagLib;
use cmf\lib\Plugin;
use think\Db;

class Tag extends TagLib{

    /**
     * 定义标签列表
     */
    protected $tags   =  [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'close'     => ['attr' => 'time,format', 'close' => 0], //闭合标签，默认为不闭合
        'open'      => ['attr' => 'name,type', 'close' => 1],

        'module'    => ['attr' => 'module,level,where,cache', 'close' => 1],
        'modules'   => ['attr' => 'module,level,where,page,limit,cache', 'close' => 1]
    ];

    /**
     * 内容模型调用数据标签
     * @param module 模块拼音/ID
     * @param level  查询的关联级别：0表示只查主表，1表示查主表和一对一关联的所有表数据，2（默认）查所有模块表数据
     * @param cache 缓存时间
     */
    public function tagModule($tag, $content)
    {
        $level  = isset($tag['level']) && is_numeric($tag['level']) ? $tag['level'] : 0;
        $where = isset($tag['where']) && !empty($tag['where']) ? $tag['where'] : [];
        $where = json_encode($where);
        $cache = isset($tag['cache']) && !empty($tag['cache']) ? $tag['cache'] : 0;

        $parse = '<?php ';
        $parse .= '$__TAG__ = \'' . json_encode($tag) . '\';';
        $parse .= '$item = model("\plugins\modules\model\ContentModel")->getList(\''.$tag['module'].'\','.$level.','.$where.','.$cache.');';
        $parse .= ' ?>';
        $parse .= $content;

        return $parse;
    }

    /**
     * 内容模型调用数据标签
     * @param module 模块拼音/ID
     * @param level  查询的关联级别：0表示只查主表，1表示查主表和一对一关联的所有表数据，2（默认）查所有模块表数据
     * @param cache 缓存时间
     */
    public function tagModules($tag, $content)
    {
        $level  = isset($tag['level']) && is_numeric($tag['level']) ? $tag['level'] : 0;
        $where = isset($tag['where']) && !empty($tag['where']) ? $tag['where'] : [];
        $where = json_encode($where);
        $page  = (isset($tag['page']) && $tag['page'] > 0) ? intval($tag['page']) : 1;
        $limit = (isset($tag['limit']) && $tag['limit'] > 1) ? intval($tag['limit']) : 1;
        $cache = isset($tag['cache']) && !empty($tag['cache']) ? $tag['cache'] : 0;

        $parse = '<?php ';
        $parse .= '$__TAG__ = \'' . json_encode($tag) . '\';';
        $parse .= '$__LIST__ = model("\plugins\modules\model\ContentModel")->getLists(\''.$tag['module'].'\','.$level.','.$where.','.$page.','.$limit.','.$cache.');';
        $parse .= ' ?>';

        $parse .= '<foreach name="__LIST__" item="item" key="key">';
        $parse .= $content;
        $parse .= '</foreach>';

        return $parse;
    }

}