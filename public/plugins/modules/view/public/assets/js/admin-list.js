
$(".ajax-change-submit-text").dblclick(
    function(){
        var dataname = "data-name-" + $(this).attr('data-name');
        var datavalue = $.trim($(this).html());
        var dataurl = $(this).attr('data-action');
        $(this).html("<textarea  class='"+dataname+"' onblur='change_textarea(\""+dataurl+"\",\""+$(this).attr('data-name')+"\")'></textarea>");
        $("."+dataname).focus().val(datavalue);
    }
);

function change_textarea(url, name){
    var dataname = "data-name-" + name;
    var datavalue = $("."+dataname).val();
    $.ajax({
        url: url,
        type: 'post',
        data:{name:name,value:datavalue},
        success: function (data) {
            if(data==true){
                $('.ajax-change-submit-text[data-action="'+url+'"]').html(datavalue);
            }else{
                alert('修改失败，刷新后重试！');
            }
        }
    })
}