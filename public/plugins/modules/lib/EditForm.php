<?php
// +----------------------------------------------------------------------
// | 内容编辑-控件构造-中间件
// +----------------------------------------------------------------------
namespace plugins\modules\lib;

class EditForm{

    protected static $instance = NULL;

    /**
     * 初始化数据
     * @param array $content
     * @param array $fieldName
     * @param $table_name
     * @return EditForm|null
     */
    public static function init($fieldName=[], $content=[], $static_path=''){
        if (self::$instance == NULL) {
            self::$instance = new self();//实例化到静态属性
            self::$instance->onConstruct($fieldName, $content, $static_path);
        }

        return self::$instance;
    }

    /**
     * -将用于初始赋值
     */
    public function onConstruct($fieldName=[], $content=[], $static_path='') {
        $this->fieldName    = $fieldName;
        $this->content      = $content;
        $this->static_path  = $static_path;
    }


    /**
     * 统一调用函数
     * @param $tag
     * @param $table
     */
    public static function start($tag, $table=''){
        $type   = $tag['type'];
        $tag['alias']  = $tag['alias'] ?: (self::$instance->fieldName[$table][$tag['name']]?:$tag['name']);//别名
        $tag['value']  = isset(self::$instance->content[$table][$tag['name']]) ? self::$instance->content[$table][$tag['name']] : (isset($tag['value'])?$tag['value']:'');//值
        $tag['desc']   = empty($tag['desc']) ? '' : $tag['desc'];//说明描述
        $tag['postname'] = !empty($table) ? "post[{$table}][{$tag['name']}]" : "post[{$tag['name']}]"; // 控件name
        $tag['width']  = empty($tag['width']) ? '90%' : (ctype_alnum($tag['width'])? ($tag['width'].'px;') : $tag['width']);//控件width
        $tag['position']  = (isset($tag['position']) && $tag['position']=='right') ?'right' : '';//控件显示位置：左和右边

        //统一控制显示位置：左边是DIV，右边是table
        if($tag['type']!='system' && $tag['type']!='system_create'){
            if($tag['position']=='right'){
                echo
                    "<tr><td>{$tag['alias']}:</td></tr>".
                    "<tr><td>";
            }else{
                echo
                    '<div class="form-group row">
                    <label class="col-sm-2 control-label">'.$tag['alias'].':</label>
                    <div class="col-sm-10">
                ';
            }
        }

        switch($type){
            case 'text':
                self::$instance->text($tag);
                break;
            case 'system':
            case 'system_create':
                self::$instance->system($tag);
                break;
            default:
                self::$instance->$type($tag);
                break;
        }


        if($tag['type']!='system' && $tag['type']!='system_create') {
            if ($tag['position'] == 'right') {
                echo '</td></tr>';
            } else {
                echo '</div></div>';
            }
        }
    }


    /**
     * 输入框
     * @param $tag [name=>'',type=>'',width=>'']
     * @param alias 别名
     * @param value 默认值/字段值
     * @param desc 说明名称
     * @param postname 控件名称
     * @param width 控件宽度
     * @param table 表名（因为是多表提交）
     *
    <div class="form-group input-group">
        <span class="input-group-addon">{$comment}:</span>
        <input type="text" class="form-control" name="post[$table][{$tag['name']}]" value="{$content}" placeholder="{$desc}">
    </div>
     */
    public function text($tag){
        echo <<<parse
        <input type="text" class="form-control" name="{$tag['postname']}" value="{$tag['value']}" placeholder="{$tag['desc']}" style="width:{$tag['width']}">
parse;
    }

    /**
     * 密码框
     * @param 同输入框
     */
    public function password($tag){
        echo <<<parse
        <input type="password" class="form-control" name="{$tag['postname']}[value]" value="{$tag['value']}" placeholder="{$tag['desc']}" style="width:{$tag['width']}">
        <input type="hidden" name="{$tag['postname']}[type]" value="password">
parse;
    }


    /**
     * 文本域
     * @param 同输入框
     */
    public function textarea($tag){
        echo <<<parse
        <textarea class="form-control" name="{$tag['postname']}" placeholder="{$tag['desc']}" style="width:{$tag['width']}">{$tag['value']}</textarea>
parse;
    }



    /**
     * 单选框
     * @param 同输入框
     * @update 2018-04-02
     * @desc  格式：  0:隐藏|1:正常|2:推荐
     */
    public static function radio($tag){
        $value = $tag['value'];
        if(isset($tag['config'])){
            if(strpos($tag['config'],',')){
                $tag['config'] = explode(',', $tag['config']);
            }elseif(strpos($tag['config'],'|')){
                $tag['config'] = explode('|', $tag['config']);
            }
        }
        if(empty($tag['config']) || !is_array($tag['config'])){
            $tag['config'] = ['0:隐藏','1:正常','2:推荐'];
        }

        $admin_role_id = cookie('admin_role_id');
        $disabled = ($admin_role_id == 1)?'':'disabled';

        $value    = empty($value)?($admin_role_id == 1?1:2):$value;
        
        foreach($tag['config'] as $v){
            if (!strpos($v, ':')) {
                $key = $val = str_replace(['\'','\"'],'',$v);
            } else {
                list($key, $val) = explode(':', $v);
            }
            $check = ($key == $value) ? 'checked' : '';
            echo "
                <label class=\"radio-inline\">
                    <input type=\"radio\" name=\"{$tag['postname']}\" {$disabled} value=\"{$key}\" {$check}>{$val}
                </label>";
        }

    }

    /**
     * 下拉选择框
     * @param 同输入框
     * @update 2018-04-02
     * @desc  格式：  0:隐藏|1:正常|2:推荐
     */
    public static function select($tag){
        $value = $tag['value'];
        if(isset($tag['config'])){
            if(strpos($tag['config'],',')){
                $tag['config'] = explode(',', $tag['config']);
            }elseif(strpos($tag['config'],'|')){
                $tag['config'] = explode('|', $tag['config']);
            }
        }
        if(empty($tag['config']) || !is_array($tag['config'])){
            $tag['config'] = ['0:隐藏','1:正常','2:推荐'];
        }
        $tag['width'] = empty($tag['width']) ? 'auto' : $tag['width'];

        echo <<<parse
<select class='form-control' name='{$tag['postname']}' style="width:{$tag['width']}">
    <option value=''>不设置</option>
parse;
        foreach($tag['config'] as $v){
            if (!strpos($v, ':')) {
                $key = $val = str_replace(['\'','\"'],'',$v);;
            } else {
                list($key, $val) = explode(':', $v);
            }
            $check = ($key == $value) ? 'selected="selected"' : '';
            echo <<<parse
    <option value="{$key}"  $check>{$val}</option>
parse;
        }
        echo <<<parse
</select>
parse;
    }

    /**
     * 复选框
     * @param 同输入框
     * @update 2018-04-02
     * @desc  格式：  0:隐藏|1:正常|2:推荐
     */
    public  function checkbox($tag){
        $value = explode(',',$tag['value']);
        if(isset($tag['config']) && !empty($tag['config'])){
            if(strpos($tag['config'],',')){
                $tag['config'] = explode(',', $tag['config']);
            }elseif(strpos($tag['config'],'|')){
                $tag['config'] = explode('|', $tag['config']);
            }
        }
        if(empty($tag['config']) || !is_array($tag['config'])){
            $tag['config'] = ['1:请按规定格式配置'];
        }

        foreach($tag['config'] as $v){
            list($key, $val) = explode(':', $v);
            $select = in_array($key,$value) ? 'checked="checked" ' : '';
            echo <<<parse
            <label class="checkbox-inline">
                <input type="checkbox" name="{$tag['postname']}[]" value="{$key}" {$select}>{$val}
            </label>
parse;
        }
    }


    /**
     * 时间
     * @param 同输入框
     * @desc type=ctime 表示 到后台需要做ctime转换处理
     * @update 2018-04-02
     */
    public  function ctime($tag){
        $tag['value'] = ctype_alnum($tag['value'])? date('Y-m-d H:i', $tag['value']) : $tag['value'];
        if(empty($tag['value'])){
            $tag['value'] =date('Y-m-d H:i', time());
        }
        echo <<<parse
            <input class="form-control js-datetime" type="text" name="{$tag['postname']}[value]" value="{$tag['value']}">
            <input type="hidden" name="{$tag['postname']}[type]" value="ctime">
parse;
    }



    /**
     * 日期
     * @param 同输入框
     * @desc type=cdate 表示 到后台需要做cdate转换处理
     * @update 2018-04-02
     */
    public  function cdate($tag){
        echo <<<parse
            <input class="form-control js-bootstrap-date" type="text" name="{$tag['postname']}[value]" value="{$tag['value']}">
            <input type="hidden" name="{$tag['postname']}[type]" value="cdate">
parse;
    }

    /**
     * 获取系统信息
     * @param 同输入框
     * @desc 配置值格式：admin_id(表示后台管理员ID)  system_time/time（表示提交时间戳）
     * @update 2018-05-08
     */
    public  function system($tag){
        $type = isset($tag['config']) ? $tag['config'] : '';
        echo <<<parse
            <input type="hidden" name="{$tag['postname']}[value]" value="{$type}">
            <input type="hidden" name="{$tag['postname']}[type]" value="{$tag['type']}">
parse;
    }


    /**
     * 图片上传(多图以逗号分隔) -- 这个备用
     * @param 同输入框
     * @update 2018-04-02
     */
    public function uploadImg1($tag){
        $img_arr = explode(',',$tag['value']);
        $name    = $tag['name'];
        echo <<<parse
            <th>相册</th>
            <td>
                <ul id="photos-{$name}" class="pic-list list-unstyled form-inline">
parse;

        if(!empty($img_arr)){
            foreach($img_arr as $key=> $vo){
                $img_url=$vo? cmf_get_image_preview_url($vo): '__TMPL__/public/assets/images/default-thumbnail.png';
                $uurl = isset($vo['url'])? $vo['url']: '';
                $uname = isset($vo['name'])? $vo['name']: '';
                echo <<<parse
                    <li id="saved-image{$key}">
                        <input id="photo-{$key}" type="hidden" name="{$tag['postname']}[]"
                               value="{$uurl}">
                        <input class="form-control" id="photo-{$key}-name" type="text"
                               name="img[{$name}][]"
                               value="{$uname}" style="width: 200px;" title="图片名称">
                        <img id="photo-{$key}-preview"
                             src="{$img_url}"
                             style="height:36px;width: 36px;"
                             onclick="parent.imagePreviewDialog(this.src);">
                        <a href="javascript:uploadOneImage('图片上传','#photo-{$key}');">替换</a>
                        <a href="javascript:(function(){\$('#saved-image{$key}').remove();})();">移除</a>
                    </li>
parse;
            }
        }else{//添加
            echo <<<parse
                <script type="text/html" id="photos-item-tpl-{$name}">
                    <li id="saved-image-{$name}">
                    <input id="photo-{id}" type="hidden" name="{$tag['postname']}[]" value="{filepath}">
                    <input class="form-control" id="photo-{id}-name" type="text" name="img[{$name}][]" value="{name}"
                    style="width: 200px;" title="图片名称">
                    <img id="photo-{id}-preview" src="{url}" style="height:36px;width: 36px;"
                    onclick="imagePreviewDialog(this.src);">
                    <a href="javascript:uploadOneImage('图片上传','#photo-{id}');">替换</a>
                    <a href="javascript:(function(){\$('#saved-image{id}').remove();})();">移除</a>
                    </li>
                </script>
parse;
        }

        echo <<<parse
                </ul>
                <a href="javascript:uploadMultiImage('图片上传','#photos-{$name}','photos-item-tpl');"
                   class="btn btn-sm btn-default">选择图片</a>
            </td>
parse;

    }

    /**
     * 图片上传(多图以逗号分隔)
     * @param 同输入框
     * @update 2018-04-02
     */
    public static function uploadImg($tag){
        $img_arr = explode(',',$tag['value']);
        $name    = $tag['name'];
        if (!empty($img_arr)){//编辑
            foreach ($img_arr as $key=>$vo){
                if(empty($vo)) continue;
                $img_url=$vo? cmf_get_image_preview_url($vo): '__TMPL__/public/assets/images/default-thumbnail.png';
                echo <<<parse
        <div style="text-align: center;" id="saved-image-{$name}-{$key}">
            <input class="form-control" id="photo-{$key}" type="text" name="{$tag['postname']}[]" value="{$vo}" style="width: 200px;" title="图片名称">
            <img id="photo-{$key}-preview"  src="{$img_url}"  onclick="parent.imagePreviewDialog(this.src);" width="135" style="cursor: pointer">
            <a href="javascript:uploadOneImage('图片上传','#photo-{$key}');">替换</a>
            <a href="javascript:(function(){\$('#saved-image-{$name}-{$key}').remove();})();">移除</a>
        </div>
parse;
            }
        }

        echo <<<parse
            <script type="text/html" id="photos-item-tpl-{$name}">
                <li id="saved-image-{$name}">
                <input id="photo-{id}" type="hidden" name="{$tag['postname']}[]" value="{filepath}">
                <img id="photo-{id}-preview" src="{url}" style="height:36px;width: 36px;"
                onclick="imagePreviewDialog(this.src);">
                <a href="javascript:uploadOneImage('图片上传','#photo-{id}');">替换</a>
                <a href="javascript:(function(){\$('#saved-image{id}').remove();})();">移除</a>
                </li>
            </script>

            <div>
                <ul id="saved-image-{$name}" class="pic-list list-unstyled form-inline"></ul>
                <a href="javascript:uploadMultiImage('图片上传','#saved-image-{$name}','photos-item-tpl-{$name}','','','{$tag['postname']}[]');"  class="btn btn-default btn-sm">选择图片</a>
            </div>
parse;
    }
    /**
     * 图片上传(多图以逗号分隔)
     * @param 同输入框
     * @update 2018-04-02
     */

    public static function uploadOneImg($tag){
        $img = $tag['value'];
        $name    = $tag['name'];
        $img_url=$img? cmf_get_image_preview_url($img): '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png';
        $old_img="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png";

        echo <<<parse
            <div>
                <input type="hidden" name="{$tag['postname']}" id="{$name}"
                       value="{$img}">
                <a href="javascript:uploadOneImage('图片上传','#{$name}');">
                    <img src="{$img_url}"
                             id="{$name}-preview"
                             width="135" style="cursor: pointer"/>
                </a>
                <input type="button" class="btn btn-sm" onclick="cancel_{$name}_1()" value="取消图片">
            </div>
            <script>
                function cancel_{$name}_1(){
                    $('#{$name}-preview').attr('src', '{$old_img}');
                    $('#{$name}').val('');
                }
            </script>

parse;
    }
    /**
     * 图片上传(多图以逗号分隔)
     * @param 同输入框
     * @update 2018-04-02
     */

    public static function uploadOneFile($tag){
        $file = $tag['value'];
        $name    = $tag['name'];
        $width = empty($tag['width'])?'30':$tag['width'];

        $file_url = $file? '<a target="_blank" href="'.cmf_get_image_preview_url($file).'">下载</a>': '';
        echo <<<parse
            <div>
                <input type="text" class='form-control' style="width:{$width};display:inline-block" name="{$tag['postname']}" id="{$name}"
                       value="{$file}">
                <a href="javascript:javascript:uploadOne('文件上传','#{$name}','file');">
                    上传
                </a>{$file_url}
            </div>

parse;
    }
    

    /**
     * 调用分类
     * @param 同输入框
     * @update 2018-04-02
     */
    public  function user($tag){
        if($tag['value']){
            $user_nickname = \app\user\model\UserModel::where(["id" => $tag['value']])->value('user_nickname');
        }else{
            $user_nickname = '';
        }

        $url = cmf_plugin_url('Modules://AdminColumn/selectUser');
        echo <<<parse
            <input class="form-control" type="text" style="width:400px;" required  value="{$user_nickname}"
                   placeholder="请选择用户" onclick="doSelectUser{$tag['name']}();" id="js-categories-name-input{$tag['name']}" readonly/>
            <input class="form-control" type="hidden"  value="{$tag['value']}" name="{$tag['postname']}"  id="js-categories-id-input{$tag['name']}"/>
            <script>//分类
                function doSelectUser{$tag['name']}() {
                    var selectedCategoriesId = $('#js-categories-id-input{$tag['name']}').val();
                    openIframeLayer("{$url}?ids=" + selectedCategoriesId, '请选择用户', {
                        area: ['1000px', '600px'],
                        btn: ['确定', '取消'],
                        yes: function (index, layero) {

                            var iframeWin          = window[layero.find('iframe')[0]['name']];
                            var selectedCategories = iframeWin.confirm();
                            console.log(selectedCategories)
                            $("#js-categories-id-input{$tag['name']}").val(selectedCategories.selectedCategoriesId.join(','));
                            $("#js-categories-name-input{$tag['name']}").val(selectedCategories.selectedCategoriesName.join(' '));
                            layer.close(index); //如果设定了yes回调，需进行手工关闭
                        }
                    });
                }
            </script>
parse;
    }

    /**
     * 调用分类
     * @param 同输入框
     * @update 2018-04-02
     */
    public  function category($tag){
        if($tag['value']){
            $column_name = \plugins\modules\model\PluginModulesColumnModel::where(["id" => $tag['value']])->value('name');
        }else{
            $column_name = '';
        }


        $modules_id = input('modules_id');
        $url = cmf_plugin_url('Modules://AdminColumn/select',['modules_id'=>$modules_id]);
        echo <<<parse
            <input class="form-control" type="text" style="width:400px;" required  value="{$column_name}"
                   placeholder="请选择分类" onclick="doSelectCategory{$tag['name']}();" id="js-categories-name-input{$tag['name']}" readonly/>
            <input class="form-control" type="hidden"  value="{$tag['value']}" name="{$tag['postname']}"  id="js-categories-id-input{$tag['name']}"/>
            <script>//分类
                function doSelectCategory{$tag['name']}() {
                    var selectedCategoriesId = $('#js-categories-id-input{$tag['name']}').val();
                    openIframeLayer("{$url}?ids=" + selectedCategoriesId, '请选择分类', {
                        area: ['700px', '400px'],
                        btn: ['确定', '取消'],
                        yes: function (index, layero) {
                            var iframeWin          = window[layero.find('iframe')[0]['name']];
                            var selectedCategories = iframeWin.confirm();

                            $("#js-categories-id-input{$tag['name']}").val(selectedCategories.selectedCategoriesId.join(','));
                            $("#js-categories-name-input{$tag['name']}").val(selectedCategories.selectedCategoriesName.join(' '));
                            layer.close(index); //如果设定了yes回调，需进行手工关闭
                        }
                    });
                }
            </script>
parse;
    }


    /**
     * 编辑器
     * @param 同输入框
     * @update 2018-04-02
     */
    public function editor($tag){
        // $upload_url = cmf_url('user/asset/webuploader','','',true);
        $domain_url = cmf_get_domain().'/static/js/ueditor/php/upload.php';
        $session_id = session('session_id');
        if(empty($session_id)){
            $session_id = session_id();
            session('session_id',$session_id);
        }

        /**
            <link type="text/css" rel="Stylesheet" href="{$static_path}/js/ueditor/WordPaster/css/WordPaster.css"/>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/WordPaster/js/json2.min.js" charset="utf-8"></script>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/WordPaster/js/layer-3.1.1/layer.js" charset="utf-8"></script>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/WordPaster/js/w.edge.js" charset="utf-8"></script>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/WordPaster/js/w.app.js" charset="utf-8"></script>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/WordPaster/js/w.file.js" charset="utf-8"></script>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/WordPaster/js/w.layer.js" charset="utf-8"></script>
                    <script type="text/javascript">
                    $(function () {

                        WordPaster.getInstance({
                            PostUrl: "{$domain_url}",
                            Cookie: 'PHPSESSID={$session_id}'
                        }).Load();

                        var editorcontent = UE.getEditor("content_{$tag['name']}", {
                            textarea: "{$tag['postname']}[value]"
                        });

                        //修改后才提交检测
                        $("#content_{$tag['name']}").removeAttr("name");
                        editorcontent.addListener("contentChange",function(){
                                $("#content_{$tag['name']}").attr("name","{$tag['postname']}");
                        });
                        editorcontent.ready(function() {

                            //WordPaster快捷键 Ctrl + V
                            editorcontent.addshortcutkey({
                                "wordpaster": "ctrl+86"
                            });
                        });

                    });
                    </script>
        */
        
        echo <<<parse
            <input type="hidden" name="{$tag['postname']}[type]" value="editor">
            <script type="text/plain" id="content_{$tag['name']}" name="{$tag['postname']}[value]" style="width:{$tag['width']};min-height:400px;">{$tag['value']}</script>{$tag['desc']}
parse;
        if(!isset(self::$instance->editor_exist)){
            self::$instance->editor_exist = true;
            $static_path = self::$instance->static_path;
            echo <<<parse
                    <script type="text/javascript" src="{$static_path}/js/ueditor/ueditor.config.js"></script>
                    <script type="text/javascript" src="{$static_path}/js/ueditor/ueditor.all.min.js"></script>
                    
                    <script type="text/javascript">
                    $(function () {

                        var editorcontent = UE.getEditor("content_{$tag['name']}", {
                            textarea: "{$tag['postname']}[value]"
                        });

                        //修改后才提交检测
                        $("#content_{$tag['name']}").removeAttr("name");
                        editorcontent.addListener("contentChange",function(){
                                $("#content_{$tag['name']}").attr("name","{$tag['postname']}");
                        });

                    });
                    </script>
parse;
        }else{
            // echo "<font color='red'>警告：一个编辑页面目前只支持一个编辑器，如需多个请自行修改编辑器！</font>";

            echo <<<parse

                    <script type="text/javascript">
                    $(function () {
                        
                        var editorcontent = UE.getEditor("content_{$tag['name']}", {
                            textarea: "{$tag['postname']}[value]"
                        });

                        //修改后才提交检测
                        $("#content_{$tag['name']}").removeAttr("name");
                        editorcontent.addListener("contentChange",function(){
                                $("#content_{$tag['name']}").attr("name","{$tag['postname']}");
                        });
                    });
                    </script>
parse;
        }
    }



    /**
     * 城市选择
     * @param 同输入框
     * @value 可配置值：默认""表示存储多个城市名;"cityid"表示存储单个城市ID;"cityids"表示存储多个城市ID;
     * @update 2018-04-02
     * @desc  一个页面只能有一个城市控件
    //先查出省份，展示出来
    //点击省份-> 写入input【prov】-> AJAX请求查询二级 ->展示出来
    //点击二级城市-> 写入input【level2】-> AJAX请求查询三级 ->展示出来
    //点击三级城市-> 写入input【level2】-> 完成
     */
    public function citys($tag){
        $citysModel = new \plugins\modules\model\PluginModulesCitysModel();
        $str1 = '';
        $prov = $citysModel->getProv();
        if($tag['value']){//初始选择城市
            if(ctype_alnum($tag['value']) && $tag['value']>'100'){//单个城市的存储
                if($tag['value']<'10000') $areas = [intval($tag['value']/100), $tag['value']];
                else{
                    $pid    = intval(substr($tag['value'],3,4));
                    $provid = intval($pid/100);
                    if(in_array($provid,[1,2,3,4,34])){
                        $pid = $provid*100 + 1;
                    }
                    $areas = [$provid,$pid,$tag['value']];
                }
            }else{
                $areas = explode(',',$tag['value']);//多个城市存储：四川,广安,邻水
            }
        }
        if(!isset($tag['config'])) $tag['config'] = '';
        //省级展开
        foreach($prov as $c1){
            if(isset($areas[0]) && ($c1['name']==$areas[0] || $c1['id']==$areas[0])){//当前已选择中的，把二级选中的值也传进去
                $cityname = isset($areas[2])?$areas[2]:'';//可能有些没有三级城市名称
                $pidname = isset($areas[1])?$areas[1]:'';//可能有些没有设置二级城市名称
                $str1 .= "<a onclick=\"checkcity({$c1['level']},{$c1['id']},'{$c1['name']}','{$pidname}','{$cityname}');\" id=\"a{$c1['id']}\"  class=\"on\" >{$c1['name']}</a>";
                $str1 .=  "<script>checkcity({$c1['level']},{$c1['id']},'{$c1['name']}','{$pidname}','{$cityname}')</script>";
            }else{//未选中的城市
                $str1 .= "<a onclick=\"checkcity({$c1['level']},{$c1['id']},'{$c1['name']}');\" id=\"a{$c1['id']}\">{$c1['name']}</a>";
            }
        }
        $str1 .= '<a onclick="checkcity(1,0,\'\');" id="a"></a>';

        if(!isset(self::$instance->citys_exist)){
            self::$instance->citys_exist = true;
            $url = cmf_plugin_url('Modules://AdminCitys/getAjaxChildCity');
            $saveVal = strpos($tag['config'],'cityid')!==false ? 'pid' : 'name';
            $areas = [
                isset($areas[0]) ? $areas[0] : '',
                isset($areas[1]) ? $areas[1] : '',
                isset($areas[2]) ? $areas[2] : '',
            ];
            echo <<<parse
            <script>
            	function checkcity(level,pid,name,selectName2,selectName3) {
                    $('#inorderby'+level).val({$saveVal});
                    var param = {};
                    param['level'] = level;
                    param['pid']   = pid;
                    if(selectName2){
                        param['selectName2'] = selectName2;
                    }
                    if(selectName3){
                        param['selectName3'] = selectName3;
                    }
                    if (level < 3){
                        $.ajax({
                            type: "POST",
                            url: "{$url}",
                            data: param,
                            success: function(result){
                                if (level == 1){
                                    $('#orderby2').html(result);
                                    $('#orderby3').html('');
                                }else if(level == 2){
                                    $('#orderby3').html(result);
                                }
                            }
                        });
                    }
                    $('#orderby'+level+' a').each(function() {
                        $(this).removeClass("on");
                    });
                    $('#a'+pid).addClass("on");
                }
            </script>
            <style>
            .checkcity a {float:left;min-width:40px;height:30px;line-height:30px;text-align:center;padding:0 10px;margin:0 2px 2px 0;border:1px solid #e3e3e3;background-color:#fff;border-radius:3px;cursor:pointer;}
            .checkcity a.on {border:1px solid #c00;background-color:#c00;color:#fff;}
            .checkcity a.jg {min-width:6px;background-color:#333;width:6px;padding:0;}
            </style>
            <tr>
                <th></th>
                <td>
                    <div class="row">
                        <div class="col-sm-1">省</div>
                        <div class="checkcity col-sm-11"  id="orderby1">{$str1}</div>
                        <input type="hidden" id="inorderby1" name="{$tag['postname']}[1]" value="{$areas[0]}">
                    </div>
                    <div class="row">
                        <div class="col-sm-1">市</div>
                        <div class="checkcity col-sm-11"   id="orderby2">
                        </div>
                        <input type="hidden" id="inorderby2" name="{$tag['postname']}[2]" value="{$areas[1]}">
                    </div>
                    <div class="row">
                        <div class="col-sm-1">县</div>
                        <div class="checkcity col-sm-11"   id="orderby3">
                        </div>
                        <input type="hidden" id="inorderby3" name="{$tag['postname']}[3]" value="{$areas[2]}">
                    </div>
                </td>
            </tr>
parse;
            if($tag['config']=='cityid'){
                echo <<<parse
                <input type="hidden" name="{$tag['postname']}[type]" value="cityid">
parse;
            }
        }else{
            echo "<font color='red'>警告：一个编辑页面目前只支持一个城市选择列表，如需多个请自行修改此处函数！</font>";
        }
    }

    public static function uploadOneVideo($tag){
        $video = $tag['value'];
        $name = $tag['name'];


        $video_url=$video? cmf_get_file_download_url($video, 3600, 'video'): '/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png';
        $old_img="/themes/admin_simpleboot3/public/assets/images/default-thumbnail.png";
        echo <<<parse
            <div id="saved-video-{$name}" style="margin-bottom:2px">
                <input class="form-control" id="video-{$name}"  name="{$tag['postname']}" value="{$video}" style="{$tag['width']}" title="视频文件名称">
                <video id="video-{$name}-preview" src="$video_url" style="width: 600px;cursor: pointer" controls="controls"></video>
                <a href="javascript:uploadOne('视频上传','#video-{$name}','video', null, 'video');">重新上传</a>
                <a href="javascript:;" onclick="cancel_{$name}_1()">取消</a>
            </div>
            <script>
                function cancel_{$name}_1(){
                    $('#video-{$name}-preview').attr('src', '{$old_img}');
                    $('#video-{$name}').val('');
                }
            </script>
parse;
    }

    /**
     * 视频上传
     * @param 同输入框
     * @add 2018-09-06
     */
    public static function uploadVideo($tag){
        $video_arr = explode(',',$tag['value']);
        $name = $tag['name'];
        if(!empty($video_arr)){//编辑
            foreach ($video_arr as $key=>$vo) {
                if (empty($vo)) continue;
                $video_url=$vo? cmf_get_file_download_url($vo, 3600, 'video'): '__TMPL__/public/assets/images/default-thumbnail.png';
                echo <<<parse
            <div id="saved-video-{$name}-{$key}" style="margin-bottom:2px">
                <input class="form-control" id="video-{$key}"  name="{$tag['postname']}[]" value="{$vo}" style="{$tag['width']}" title="视频文件名称">
                <video id="video-{$key}-preview" src="$video_url" style="width: 600px;cursor: pointer" controls="controls"></video>
                <a href="javascript:uploadOne('视频上传','#video-{$key}','video', null, 'video');">重新上传</a>
                <a href="javascript:(function(){\$('#saved-video-{$name}-{$key}').remove();})();">移除</a>
            </div>
parse;
            }
        }

        echo <<<parse
            <script type="text/html" id="video-item-tpl-{$name}">
            <div id="saved-video-{$name}-{id}" style="margin-bottom:2px">
                <input class="form-control" id="video-{id}"  name="{$tag['postname']}[]" value="{filepath}" style="width: {$tag['width']}" title="视频文件名称">
                <video id="video-{id}-preview" src="{url}" style="width: 600px;cursor: pointer" controls="controls"></video>
                <a href="javascript:uploadOne('视频上传','#video-{id}','video', null, 'video');">重新上传</a>
                <a href="javascript:(function(){\$('#saved-video-{$name}-{id}').remove();})();">移除</a>
            </div>
            </script>

            <div>
                <ul id="saved-video-{$name}" class="pic-list list-unstyled form-inline"></ul>
                <a href="javascript:uploadMultiFile('视频上传','#saved-video-{$name}','video-item-tpl-{$name}','video','{$tag['postname']}[]', 'video');"  class="btn btn-default btn-sm">选择视频</a>
            </div>
parse;
    }


}
