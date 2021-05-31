// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: LI <244100330@qq.com>
// +----------------------------------------------------------------------

ThinkCMF 内容模型


使用时需明白的几个概念：
1、先建立一个模块：
    可选择自带的4张示例文章模型，
    每个模块可以添加无限张表，
    每张表用表名规则绑定一起，
    一个模块必须有一张主表，
    表之间的关联关系：不关联、一对一、一对多

2、内容模型之表管理：
   可以对表做跟数据库类似的基本简单操作，添加删除修改字段等。
   也仅仅只是对表的管理，跟其它无关。

3、内容模型之字段操作配置：
   根据建立好的字段，配置后台的显示列表、搜索from控件、编辑控件等操作。
   其中编辑控件部分需要配置，如，有复选框值配置格式：0:隐藏,1:正常,2:推荐

4、以上步骤配置好后:
  会在某个菜单下生成一个为模块名的新菜单，
  现定（内容管理）菜单下，此菜单可随意移植到其它地方，如未生成，请清空缓存后再试。


插件表说明：
    核心表：
        模块-列表：cmf_plugin_modules
        模块内部表-列表:cmf_plugin_modules_tables
    资源表:
        城市表:cmf_plugin_modules_citys 用于Form控件选择城市
    内容表：
        该表由模块内生成或新建，无法确定表名。但都有一个统一的规则，如：cmf_item_{模块的拼音}_{表的拼音}，其中cmf_item_{模块的拼音}为模块的主表


备注：
    内容管理系统没内容模型怎能忍，于是本人借鉴其它系统，出了这个简洁且简单的内容模型，
    如果大家喜欢能得到大家的支持，我后续继续完善相关功能。



============================================================================

simplewind/cmf/common.php 里加入函数：

/**
 * PHP 5.5以前不支持array_column函数
 */
if (!function_exists('array_column'))
{
    function array_column($input, $column_key=null, $index_key=null)
    {
        $result = array();
        $i = 0;
        foreach ($input as $v)
        {
            $k = $index_key === null || !isset($v[$index_key]) ? $i++ : $v[$index_key];
            $result[$k] = $column_key === null ? $v : (isset($v[$column_key]) ? $v[$column_key] : null);
        }
        return $result;
    }
}
=============================================================================================



    <taglib name="plugins\modules\Tag" />

    <br/><br/>---单个调用示例---<br/><br/>
    <tag:module module="ces" level="1" cache="false">{:dump($item);}</tag:module>

    <br/><br/>---多个个调用示例---<br/><br/>
    <tag:modules module="ces" level="2" where=['ces.state'=>1,'infos.num1'=>5] page="1" limit="10" cache="false">{:dump([$key=>$item]);}</tag:modules>

     参数说明：
module(模块拼音)，
level（查询的关联级别：0表示只查主表，1表示查主表和一对一关联的所有表数据，2（默认）查所有模块表数据），
where (查询条件，数组形式,跨表查询时，字段需要带上表名或表拼音)
page/limit 分页/页条数
cache  缓存列表过期值，单位秒，默认不缓存。