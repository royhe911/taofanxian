/**
 * Created by 二更 on 2017/4/26.
 */
$(function(){
    //切换支持花呗选项
    $('.is_huabei_checkbox').on('click', function () {
        $(this).toggleClass('actived')
    });
        $('.try_manage ul>li').find('a').eq(0).addClass('leftNav_active');
    $('.h1_title').on('click', function () {
        bind_h1(this);
    });

    $('.p_title').on('click', function () {
        bind_p(this);
    });

    function bind_h1(obj) {
        $(obj).css({'background': 'url(../../images/merchant/spot.png) left center no-repeat'});
        $(obj).siblings().css({'background': 'url(../../images/merchant/sj_fbsy_icon_quan_default.png) left center no-repeat'});
        $(obj).next('p').css({'background': 'url(../../images/merchant/spot.png) left center no-repeat'});
    }

    function bind_p(obj) {
        $(obj).css({'background': 'url(../../images/merchant/spot.png) left center no-repeat'});
        $(obj).siblings('p').css({'background': 'url(../../images/merchant/sj_fbsy_icon_quan_default.png) left center no-repeat'});
        $(obj).prevAll('h1').css({'background': 'url(../../images/merchant/spot.png) left center no-repeat'});
        $(obj).nextAll('h1').css({'background': 'url(../../images/merchant/sj_fbsy_icon_quan_default.png) left center no-repeat'})
    }



        $('.pinkage').bind('click',function(){
            $(this).css({'background':'url(images/merchant/spot.png) left center no-repeat'});
            $(this).siblings().css({'background':'url(images/merchant/sj_fbsy_icon_quan_default.png) left center no-repeat'});
            $(this).addClass("ifchoose").siblings().removeClass("ifchoose");
            if($("#baoyou").hasClass('ifchoose')){
                $("#freight").attr("disabled", true);
                $('#freight_error_notice').text("");
            }else{
                $("#freight").attr("disabled", false);
            }
        });

        if($('#freight').val() > 0){
            var freight_init = 1;
        }else{
            var freight_init = 0;
        }

        if(freight_init)
        {
            $("#yunfei").click();
        }else
        {
            $("#baoyou").click();
        }

});

/*
 * author: bingbing
 * time: 2017年6月12日19:06:10
 * introduce: 页面出错自动定位到错误位置
 */
function showSearch(id){
    var oDiv=$("#"+id);
    var t=document.createElement("input");
    t.type="text";
    oDiv.prepend(t);
    t.focus();
    t.remove();
}
// 开始本地上传
$('.fileToUpload').on('change', function () {
    var fileToUpload = $(this)[0];
    var compressValue = $(this).parent().find('.compressValue')[0];
    var imgBase64 = $(this).parent().find('.imgBase64')[0];
    var $imgList = $(this).parent().find('.imgList')

    tool.uploadBtnClick(fileToUpload, compressValue, imgBase64, $imgList)

})

// 上传后图片判断
$('.imgList').load(function () {
    var $input_file = $(this).parent().find('.fileToUpload');
    var $error_tips = $(this).parent().parent().find('.img_tips');
    /*获取字节数*/
    var size = $input_file[0].files[0].size;

    /*工具中获取长宽像素*/
    var width = tool.up_img.width;
    var height = tool.up_img.height;

    if (width != 800 || height != 800) {
        //图片尺寸必须是800*800
        $error_tips.show().removeClass('icon-chenggong correct').addClass('icon-cuowu error').html('图片尺寸必须为800*800，请重新上传！')
        return false;

    } else if (!/\.(jpg|JPG|png|PNG|JPEG|jpeg|gif|GIF)$/.test($input_file.val())) {
        // 格式为：jpg、png、gif
        $error_tips.show().removeClass('icon-chenggong correct').addClass('icon-cuowu error').html('图片格式必须为jpg、png、jpeg、gif！')
        return false;

    } else if (size > 1048576) {
        //不能超过1M
        $error_tips.show().removeClass('icon-chenggong correct').addClass('icon-cuowu error').html('图片大小必须小于1M！')
        return false;
    } else {
        $error_tips.show().removeClass('icon-cuowu error').addClass('icon-chenggong correct').html('上传成功！')
    }

});

// 是否重复发布商品活动，默认否
var is_repeat_publish = 0;

function next_step(){
    if(is_repeat_publish == 1){
        return;
    }

    var commodity_name = $("#commodity_name").val();
    if (commodity_name == '') {
        $("#product_name_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品名称不得为空");
        showSearch('focus');
        return;
    }
    if (commodity_name.length > 20) {
        $("#product_name_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品名称请控制在20个字符以内");
        showSearch('focus');
        return;
    }
    else {
        $("#product_name_error").html("");
    }
    var goods_name = $("#goods_name").val();
    if (goods_name == '') {
        $("#goods_name_error").html("<span class='icon iconfont icon-cuowu'></span>" + "淘宝商品完整标题不得为空");
        showSearch('focus');
        return;
    }
    if (goods_name.length > 100) {
        $("#goods_name_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品完整标题请控制在100个字符以内");
        showSearch('focus');
        return;
    }
    else {
        $("#goods_name_error").html("");
    }

    var shop_url = $("#shop_url").val();
    if (shop_url == '') {
        $("#product_link_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品链接不得为空");
        showSearch('focus');
        return;
    } else {
        $("#product_link_error").html("");
    }

    var reg = /.*[\u4e00-\u9fa5]+.*$/;
    if ((reg.test(shop_url))) {
        $("#product_link_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品链接中不得出现中文");
        showSearch('focus');
        return;
    } else {
        $("#product_link_error").html("");
    }


    var iftmall = shop_url.indexOf("tmall");
    // 药店的详情链接是  "detail.yao.95095.com"
    var ifyao = shop_url.indexOf(".yao.");
    var iftaobao = shop_url.indexOf("taobao");
    if(((iftaobao<0 || iftmall > 0) && platform_id == 1)||((iftmall<0 && ifyao < 0) && platform_id == 2)) {
        $("#product_link_error").html("<span class='icon iconfont icon-cuowu'></span>"+"商品链接不匹配");
        showSearch('focus');
        return;
    }else if(((iftmall < 0 && ifyao < 0) && iftaobao < 0)){
        $("#product_link_error").html("<span class='icon iconfont icon-cuowu'></span>"+"请输入正确的商品链接");
        showSearch('focus');
        return;
    }else if (!/(\?|\&)id=\d+/g.test(shop_url)) {
        $("#product_link_error").html("<span class='icon iconfont icon-cuowu'></span>"+"商品链接不匹配");
        showSearch('focus');
        return;
    }else{
        $("#product_link_error").html("");
    }

    var picture_urls = $('#picture_urls').val();

    if (picture_urls == '' && $('#picture_urls').data('img') == '') {
        $("#picture_url_error").removeClass('icon-chenggong correct').addClass('icon-cuowu error').text("请上传图片!");
        showSearch("product_name_error");
        return false;
    }
    if ($("#picture_url_error").hasClass('error')) {
        return false;
    }
    // 验证 PC 端价格
    var unit_price = $("#unit_price").val();
    if (unit_price == '' || unit_price == 0) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "单品售价不得为空或者为0");
        return;
    } else {
        $("#product_format_error").html("");
    }
    var reg = /\d+\.\d{0,2}|\d$/;
    if (!reg.test(unit_price)) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "单品售价格式错误");
        return;
    } else {
        $("#product_format_error").html("");

    }
    if (unit_price < 500 && type == 2) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "高价值商品单品售价必须大于等于500");
        return;
    } else {
        $("#product_format_error").html("");
    }

    // 验证手机淘宝价格
    var iphone_unit_price = $("#iphone_unit_price").val();
    if (iphone_unit_price == '' || iphone_unit_price == 0) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品搜索价格不得为空或者为0");
        return;
    } else {
        $("#product_format_error").html("");
    }
    var reg = /\d+\.\d{0,2}|\d$/;
    if (!reg.test(iphone_unit_price)) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "商品搜索价格格式错误");
        return;
    } else {
        $("#product_format_error").html("");
    }


    // 验证每单数量
    var buy_sum = $("#buy_sum").val();
    if (buy_sum == '' || buy_sum == 0) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "每单数量不得为空或者为0");
        return;
    } else {
        $("#product_format_error").html("");
    }
    var reg = /\d+\.\d{0,2}|\d$/;
    if (!reg.test(buy_sum)) {
        $("#product_format_error").html("<span class='icon iconfont icon-cuowu'></span>" + "每单数量格式错误");
        return;
    } else {
        $("#product_format_error").html("");
    }

    var freight = $("#freight").val();

    if($('#yunfei').hasClass('ifchoose') && (!freight || freight <=0)){
        $('#freight_error_notice').text("运费不得为空或者为0");
        return;
    }else{
        $('#freight_error_notice').text("");
    }
    if($("#baoyou").hasClass('ifchoose')){
        $("#freight").val(0);
	freight = 0;
    }else{
        var reg = /\d+\.\d{0,2}|\d*$/;
        if((!reg.test(freight))){
            $("#unit_price_error").text("运费格式错误");
            return;
        }else{
            $("#unit_price_error").text("");
        }
    }
    var act_id = $("#act_id").val();
    var old_act_id = $('#old_act_id').val();
    var shop_id = $("#shop_id").val();
    var shopname = $("#shopname").val();
    var commodity_classify = $("#commodity_classify").val();
    var thecolor = $("#thecolor").val();
    var notice_remark = $('#notice_remark').val();
    var margin = buy_sum * unit_price;
    var is_huabei = $(".is_huabei_checkbox").hasClass('actived') ? 1 : 0;

    var picture_url = $("#picture_url").attr("src");
    is_repeat_publish = 1;
    $.ajax({
        url: '/merchant_issue_try2/update_fake_activity2',
        data: {
            act_id: act_id,
            platform_id: platform_id,
            type: type,
            shop_id: shop_id,
            shopname: shopname,
            is_huabei: is_huabei,
            commodity_name: commodity_name,
            goods_name: goods_name,
            shop_url: shop_url,
            commodity_classify: commodity_classify,
            picture_url: picture_url,
            thecolor: thecolor,
            unit_price: unit_price,
            iphone_unit_price: iphone_unit_price,
            buy_sum: buy_sum,
            freight: freight,
            margin: margin,
            notice_remark: notice_remark
        },
        type: 'post',
        cache: false,
        async: false,
        success: function (data) {
            if (data == 0) {
                alert("商品信息添加失败");
            } else if (data == 500) {
                alert("高价值商品价格不得低于500");
            } else {
                var location_url = (old_act_id ? '&old_act_id=' + old_act_id : '');

                if(type == 4){
                    location.href = "/merchant_issue_try3_other?act_id="+data + location_url;
                }else if(type == 5){
                    location.href = "/merchant_issue_try3_compete?act_id="+data + location_url;
                }else if(type == 6){
                    location.href = "/merchant_issue_try3_day?act_id="+data + location_url;
                } else {
                    location.href = "/merchant_issue_try3?act_id=" + data + location_url;
                }
            }
        },
        error: function (XMLHttpRequest, textStatus) {
        }
    })
}

function check_unit_price() {

    if ($("#unit_price").val() == '' || $("#buy_sum").val() == '') {
        // $("#unit_price_error").css("display","inline-block");
        return;
    } else if ($("#unit_price").val() < 500 && type == 2) {
        $("#unit_price_error").text("高价值商品价格必须大于等于500");
        return;
    } else {
        $("#unit_price_error").empty();
        $("#unit_price_error").css("display", "none");
    }
    $("#xdzje").text(($("#unit_price").val() * $("#buy_sum").val()).toFixed(2));
}

// 只允许输入整数
function onlyNum() {
    var keyCode = event.keyCode;
    if ((keyCode >= 48 && keyCode <= 57)) {
        event.returnValue = true;
    } else {
        event.returnValue = false;
    }
}