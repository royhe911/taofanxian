var reg=/^\d+$/;
// 只允许输入整数
function onlyNum() {
    var keyCode = event.keyCode;
    if ((keyCode >= 48 && keyCode <= 57))
    {
        event.returnValue = true;
    } else {
        event.returnValue = false;
    }
}

// 清除好评关键字相关选项内容
function clear_hp_kwds(){
    $("#hp_kwds_error").text("");
    $("#hp_num_error").text("");
    $('#hp_num').val('');
    $('#hp_num').attr('disabled', 'disabled');
    $('#hp_kwds1').val('');
    $('#hp_kwds1').attr('disabled', 'disabled');
    $('#hp_kwds2').val('');
    $('#hp_kwds2').attr('disabled', 'disabled');
    $('#hp_kwds3').val('');
    $('#hp_kwds3').attr('disabled', 'disabled');
    $('.set_hp_num').text('0单');
}
function showSearch(id){
    var oDiv=$("#"+id);
    var t=document.createElement("input");
    t.type="text";
    oDiv.prepend(t);
    t.focus();
    t.remove();
}
$(function(){
    // 初始禁止老用户中奖设置
    if(is_first_add){
        safe_day_15();
    }else{
        if(0 == safe_day){
            cancel_safe_day();
        }else if(15 == safe_day){
            safe_day_15();
        }else if(45 == safe_day){
            safe_day_45();
        }else{
            safe_day_30();
        }
    }

    // 初始处理普通文字好评
    if(pic_text){
        $('#choose_pic_text').text('');
    }else{
        $('#choose_pic_text').text('选择普通文字好评后，才能设置好评关键词');
        $('#hp_kwds > i').removeClass('search_active');
        hp_kwds = 0;
        clear_hp_kwds();
    }

    $('.try_manage ul>li').find('a').eq(0).addClass('leftNav_active');
    //$('.part_two').find('input').attr('readonly','readonly');
    /*$('.checkbox_title').click(function(){
     $(this).toggleClass('search_active');
     });*/
    $('#red_packet>i').click(function () {
        $(this).toggleClass('search_active');
        red_packet = ($(this).hasClass('search_active'))?1:0;
        if(0 == red_packet){
            $('#red_packet_price_pre_error').text('');
            $('#red_packet_pre_price').val('');
        }
    });
    $('#pic_text > i').click(function(){
        $(this).toggleClass('search_active');
        pic_text = ($(this).hasClass('search_active'))?1:0;

        if(!pic_text){
            $('#choose_pic_text').text('选择普通文字好评后，才能设置好评关键词');
            $('#hp_kwds > i').removeClass('search_active');
            hp_kwds = 0;
            clear_hp_kwds();
        }else{
            $('#choose_pic_text').text('');
        }
    });
    $('#pictures_praise>i').click(function(){
        $(this).toggleClass('search_active');
        print_praise = ($(this).hasClass('search_active'))?1:0;
        if(0 == print_praise){
            $('#print_praise_num_error').text('');
            $('#print_praise_num').val('');
            $('.set_pictures_praise_num').text('0单');
        }
    });

    $('#hp_kwds>i').click(function(){
        if(pic_text){
            $(this).toggleClass('search_active');
            hp_kwds = ($(this).hasClass('search_active'))?1:0;
            if(!hp_kwds)
            {
                clear_hp_kwds();
            }
        }
    });

    $('#specification>i').click(function(){
        $(this).toggleClass('search_active');
        specification = ($(this).hasClass('search_active'))?1:0;
        //console.log(hp_kwds);
    });

    $('#safe').off('click');
    // $('#imageContent').off('click');

    // 红包加赏验证
    $('#red_packet_pre_price').blur(function () {
        red_packet_pre_price = ($(this).val()==='')?0:$(this).val();

        if(red_packet)
        {
            if(!(reg.test(red_packet_pre_price))){
                $('#red_packet_price_pre_error').html("<span class='icon iconfont icon-cuowu'></span>红包金额格式不正确");
                showSearch('focus1');
            }else if(red_packet_pre_price < 2){
                $('#red_packet_price_pre_error').html("<span class='icon iconfont icon-cuowu'></span>不得小于2元");
                showSearch('focus1');
            }else{
                red_packet_pre_price = parseInt(red_packet_pre_price);

                $('#red_packet_price_pre_error').html('');
            }
        }
    });
    // 晒图单数验证
    $('#print_praise_num').blur(function () {
        print_praise_num = ($(this).val()==='')?0:$(this).val();

        if(print_praise)
        {
            if(print_praise_num == 0)
            {
                $('#print_praise_num_error').html("<span class='icon iconfont icon-cuowu'></span>请填写单数");
                showSearch('focus2');
            }
            else if(!(reg.test(print_praise_num))){
                $('#print_praise_num_error').html("<span  class='icon iconfont icon-cuowu'></span>单数格式不正确");
                showSearch('focus2');
            }
            else if(print_praise_num > apply_amount)
            {
                $('#print_praise_num_error').html("<span class='icon iconfont icon-cuowu'></span>单数不得大于总试用单数");
                showSearch('focus2');
            }else
            {
                print_praise_num = parseInt(print_praise_num);
                $('#print_praise_num_error').html('');
                $('.set_pictures_praise_num').text(print_praise_num+'单');
            }
        }
    });

    // 好评验证
    $('#hp_num').blur(function(){
        hp_num = ($(this).val()==='')?0:$(this).val();

        if(hp_kwds)
        {
            if(hp_num == 0)
            {
                $('#hp_num_error').html("<span class='icon iconfont icon-cuowu'></span>请填写单数");
                showSearch('focus2');
            }
            else if(!(reg.test(hp_num))){
                $('#hp_num_error').html("<span class='icon iconfont icon-cuowu'></span>单数格式不正确");
                showSearch('focus2');
            }
            else if(hp_num > apply_amount)
            {
                $('#hp_num_error').html("<span  class='icon iconfont icon-cuowu'></span>单数不得大于总试用单数");
                showSearch('focus2');
            }else
            {
                hp_num = parseInt(hp_num);
                $('#hp_num_error').html('');
                $('.set_hp_num').text(hp_num+'单');
            }
        }
    });

    // 取消禁止老用户中奖
    $('#safe_day_all>i').bind('click', function(){
        $(this).toggleClass('search_active');
        safe_day_all = ($(this).hasClass('search_active'))?1:0;
        $('#safe_day_all>i').addClass('search_active');
        if(safe_day_all){
            safe_day_15();
        }
        else{
            cancel_safe_day(); 
        }
    });

    // 禁止老用户中奖45天防护
    $('#safe_day_ten>i').bind('click', function(){
        safe_day_45();
    });

    // 禁止老用户中奖30天防护
    $('#safe_day_fee>i').bind('click', function(){
        safe_day_30();
    });

    // 禁止老用户中奖15天防护
    $('#safe_day_free>i').bind('click', function(){
        safe_day_15();
    });
    selected();

});

// 取消禁止老用户中奖防护设置
function cancel_safe_day()
{
    $('#safe_day_all>i').removeClass('search_active');
    $('#safe_day_fee>i').removeClass('search_active');
    $('#safe_day_free>i').removeClass('search_active');
    $('#safe_day_ten>i').removeClass('search_active');
    $('#safe_day').val(0);
    $('#safe_day_notice').css('display', 'none');
}

// 启用禁止老用户中奖 45 天防护设置
function safe_day_45()
{
    $('#safe_day_all>i').addClass('search_active');
    $('#safe_day_ten>i').addClass('search_active');
    $('#safe_day_fee>i').removeClass('search_active');
    $('#safe_day_free>i').removeClass('search_active');
    $('#safe_day').val(45);
    $('#set_safe_day').text('45天内');
    $('#safe_day_notice').css('display', 'block');
}

// 启用禁止老用户中奖 30 天防护设置
function safe_day_30()
{
    $('#safe_day_all>i').addClass('search_active');
    $('#safe_day_fee>i').addClass('search_active');
    $('#safe_day_free>i').removeClass('search_active');
    $('#safe_day_ten>i').removeClass('search_active');
    $('#safe_day').val(30);
    $('#set_safe_day').text('30天内');
    $('#safe_day_notice').css('display', 'block');
}

// 启用禁止老用户中奖 15 天防护设置
function safe_day_15()
{
    $('#safe_day_all>i').addClass('search_active');
    $('#safe_day_free>i').addClass('search_active');
    $('#safe_day_fee>i').removeClass('search_active');
    $('#safe_day_ten>i').removeClass('search_active');
    $('#safe_day').val(15);
    $('#set_safe_day').text('15天内');
    $('#safe_day_notice').css('display', 'block');
}

//    根据选择服务来判断input输入框是否能输入
function selected(){
    $('.checkbox_title').parent().find('input').attr('disabled',true);
    $('.checkbox_title').each(function () {
        if($(this).find('i').hasClass('search_active')){
            $(this).parent().find('input').removeAttr('disabled');
        }else{
            $(this).parent().find('input').attr('disabled', true);
        }
    });
    $('.checkbox_title>i').on('click',function(){
        if($(this).hasClass('search_active')){
            $(this).parent().parent().find('input').removeAttr('disabled');
        }else{
            $(this).parent().parent().find('input').attr('disabled',true);
        }
    })
}
