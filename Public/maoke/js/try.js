/**
 * @tryType 1 人气产品  2 高价值产品  3 综合产品  4 内容营销试用  5 竞品搜索试用
 * @delay   表示只展示的天数（即延迟开奖的天数）
 */

// 初始化试用类型，展示时间
var type = {'tryType': tryType, 'delay': delay};

//会员类型   level : 1(试用会员)  2(正式会员) 3.非会员 4.过期会员
var userType = level;

//服务器的当天时间戳
var today_Time_server = now_time;

(function () {

    // 初始化传入商品数据，计算该投放数量下限
    var pro_info = {'category': product_classify, 'price': price};

    init(type, pro_info);
    bindEvent(type);


})();



function init(type, pro_info) {
    // 根据类型展示页面对应模块
    $('div[data-tryType="' + type.tryType + '"]').show();

    // 如果用户之前填写保存了延迟6天展示的展示，则默认进这个页面展示延迟6天
    if (type.tryType == 2 && type.delay == 6) {
        $('#delay4').removeAttr('checked');
        $('#delay6').attr('checked', 'checked');

    }


    // 计算用户选择的产品分类下，最小应该投放的数量
    var lessnum = _limitLessOrder(pro_info);
    $('#limit_order').html(lessnum + '单');


    // 页面默认今天日历;
    var today = new Date(today_Time_server);
    var hour = today.getHours();
    var tomorrow = new Date(new Date().setTime(today.getTime() + 24 * 60 * 60 * 1000));

    var todayTime = getNowFormatDate(today);
    var tomorrowTime = getNowFormatDate(tomorrow);

    if ((type.tryType == 3 || type.tryType == 4) && hour >= 16) {
        // 如果是综合日历，且在16:00以后进来的,显示明天开始
        whichDay(tomorrow, type);
        $('#chooseTime').val(tomorrowTime);
    } else {
        whichDay(today, type);
        $('#chooseTime').val(todayTime);
    }


    // 开始投放时，检查用户有没有填写过日历，如果有则显示用户写过的
    isWrited(type);

}



//获取当前时间，格式YYYY-MM-DD
function getNowFormatDate(date) {
    var seperator1 = "/";
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var strDate = date.getDate();
    if (month >= 1 && month <= 9) {
        month = "0" + month;
    }
    if (strDate >= 0 && strDate <= 9) {
        strDate = "0" + strDate;
    }
    var currentdate = year + seperator1 + month + seperator1 + strDate;
    return currentdate;
}
function _limitLessOrder(pro_info) {
    // 根据分类最小值表格
    var LessList = {
        // 女装
        '1': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 男装
        '2': {'L1': 18, 'L2': 14, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 护肤美妆
        '3': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 鞋包
        '4': {'L1': 18, 'L2': 14, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 居家生活
        '5': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 数码电器
        '6': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 母婴儿童
        '7': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 户外运动
        '8': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 食品酒水
        '9': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1},
        // 其他试用
        '10': {'L1': 20, 'L2': 15, 'L3': 12, 'L4': 8, 'L5': 6, 'L6': 4, 'L7': 3, 'L8': 1}
    }


    var category = pro_info.category;
    var price = pro_info.price;
    // 返回值
    var num;

    switch (true) {
        case price <= 49:
            num = LessList[category]['L1'];
            break;

        case price <= 99:
            num = LessList[category]['L2'];
            break;

        case price <= 199:
            num = LessList[category]['L3'];
            break;

        case price <= 299:
            num = LessList[category]['L4'];
            break;

        case price <= 499:
            num = LessList[category]['L5'];
            break;

        case price <= 799:
            num = LessList[category]['L6'];
            break;

        case price <= 999:
            num = LessList[category]['L7'];
            break;

        case price >= 1000:
            num = LessList[category]['L8'];
            break;

    }

    return num;

}


function bindEvent(type) {

    // 获取用户选择日期
    chooseTime(type);

    // 切换高价值日历，延迟展示时间
    var today = new Date(today_Time_server);
    $('.choice > label').on('click', function () {
        type.delay = $(this).data('value');
        // 获取当前用户选择的天数
        var chooseDay = $('#chooseTime').val();
        chooseDay ? whichDay(new Date(chooseDay), type) : whichDay(today, type);

        schedule(type);

    })

    // 开始投放
    schedule(type);

    // 各种异常判断提示
    tips(type);


}


function tips(type) {
    // 总投放计划少于lessnum，兑换订单不足提示
    lessTips(type);

    //如果是竞品打标搜索，每天投放份数不得超过十份
    type.tryType == 5?limitSetDayTips(type):null;

}

/**
 * 如果是竞品打标搜索，限制每天投放份数不得超过十份
 * @param type 日历类型
 */
function limitSetDayTips(type){

    //直接输入超过10
    $('.calendar-try .p-btn').on('change', function () {
        var input_num = parseInt($(this).val());
        if(input_num > 10 ){
            Shike.alert('每天投放份数不得超过10份','确认');
            $(this).val(10)
        }
    });

    //点击+号超过10
    $('.calendar-try .add_btn').on('click', function () {
        var input_num = parseInt($(this).siblings('.p-btn').val());
        if(input_num > 10 ){
            Shike.alert('每天投放份数不得超过10份','确认');
            $(this).siblings('.p-btn').val(10)
        }
    })

}


function lessTips(type) {

    // 不得少于最小值
    var lessnum = parseInt($('#limit_order').html());
    var flag = 0;

    $('#next_btn').on('mouseenter', function () {

        var _input_box = $('.calendar-try .p-choose .p-btn');
        var sumOrder = 0;
        var excOrder = 0;

        _input_box.each(function (i) {
            sumOrder += isNaN(parseInt(_input_box[i].value)) ? 0 : parseInt(_input_box[i].value);

        })

        var _exc_input_box = $('.calendar-exchange .p-choose .p-btn');

        _exc_input_box.each(function (i) {
            excOrder += isNaN(parseInt(_exc_input_box[i].value)) ? 0 : parseInt(_exc_input_box[i].value);
        });


        if (sumOrder >= lessnum) {
            if (excOrder == Math.round(sumOrder * 0.2)) {
                //如果都满足条件
                flag = 1
            } else {
                //兑换试用单不足
                flag = 2;
            }
        } else {
            //发单数不足
            flag = 3;
        }

        //如果是新版内容营销试用或竞品打标搜索试用，则无需判断兑换试用单不足情况
        if((tryType == 4 || tryType == 5) && flag == 2 ){
            flag = 1;
        }

    }).on('click', function () {
        if (flag == 1) {
            next_step();
        } else if (flag == 2) {

            var excOrder = parseInt($('#sum-exchangeOrder >b').html());
            Shike.alert('兑换试用单数不能少于' + excOrder + '单！','确认');

        } else if (flag == 3) {
            Shike.alert('总计发放单数不得低于' + lessnum + '单！','确认');

        }
    })


}


function chooseTime(type) {


    var hour = new Date(today_Time_server).getHours();

    if (type.tryType == 3 && hour >= 16) {

        laydate({
            elem: '#chooseTime',
            format: 'YYYY-MM-DD',
            festival: true,
            istime: false,
            min: laydate.now(+1),
            // max: laydate.now(+60),
            choose: function (times) { //选择日期完毕的回调
                whichDay(times, type);
                schedule(type);
            }
        });

    } else {

        laydate({
            elem: '#chooseTime',
            format: 'YYYY-MM-DD',
            festival: true,
            istime: false,
            min: laydate.now(),
            choose: function (times) {
                whichDay(times, type);
                schedule(type);
            }
        });

    }

}


function whichDay(times, type) {

    // 绘制日历范围
    var scope = 22;

    // 获得用户选择的时间
    var time = new Date(times);

    // 绘制日历
    timeTable(time, type);

    // 绘制日历数字
    numTable(time, scope);

    // 修改表头信息
    titleTable(time, scope);

}

// 判断是否跨月
function isOverMounth(time, scope) {


    // 获得当月共有多少天
    var now_date_sum = new Date(time.getFullYear(), time.getMonth() + 1, 0).getDate();
    // 获得选择的是哪一号
    var num_date = time.getDate();

    return num_date + scope - 1 > now_date_sum;
}


function isOverYear(time, scope) {

    var flag = 0;
    // 获得选择的是哪个月
    var now_mouth = time.getMonth() + 1;
    if (now_mouth == 12) {
        if (isOverMounth(time, scope)) {
            flag = 1;
        }
    }

    return flag;
}


function numTable(time, scope) {

    var dateList = _numDateArr(time, scope);

    // 填充数据
    _fillDate($('#row_one_sum >li'), dateList['one']);
    _fillDate($('#row_two_sum >li'), dateList['two']);
    _fillDate($('#row_three_sum >li'), dateList['three']);
    _fillDate($('#row_four_sum >li'), dateList['four']);


    _fillDate($('#row_one_exc >li'), dateList['one']);
    _fillDate($('#row_two_exc >li'), dateList['two']);
    _fillDate($('#row_three_exc >li'), dateList['three']);
    _fillDate($('#row_four_exc >li'), dateList['four']);

}

// 填充日期数据
function _fillDate(ele, data) {
    var len = ele.length;
    var j = 0;
    for (var i = 0; i < len; i++) {

        if ($(ele[i]).html() != '') {
            // 获取日期显示的p
            var p_box = $(ele[i]).find('.dateNum');
            p_box.html(data[j]);
            j++;

        }
    }

}


// 返回每行需要的日期数字对象
function _numDateArr(time, scope) {
    var dateObject = {};
    var arr = [];

    var auto = 0;
    // 获得当月有多少天
    var now_date_sum = new Date(time.getFullYear(), time.getMonth() + 1, 0).getDate();
    // 获得开始天数,几号
    var start_date = time.getDate();

    if (isOverMounth(time, scope)) {

        // 计算从本月到月底占用了多少天
        var overNum = now_date_sum - start_date + 1;

        // 还剩多少天
        var restNum = scope - overNum;

        // 游标数字
        var autoNum = start_date;
        for (var i = start_date; i <= now_date_sum; i++) {
            arr.push(autoNum);
            autoNum += 1;
        }

        for (var i = 1; i <= restNum; i++) {
            arr.push(i);
        }

    } else {
        var autoNum = start_date;
        for (var i = start_date; i <= start_date + scope - 1; i++) {
            arr.push(autoNum);
            autoNum += 1;
        }

    }


    var rowOne_num = 7 - time.getDay();

    dateObject.one = arr.slice(0, rowOne_num);
    dateObject.two = arr.slice(rowOne_num, rowOne_num + 7);
    dateObject.three = arr.slice(rowOne_num + 7, rowOne_num + 14);
    dateObject.four = arr.slice(rowOne_num + 14);

    return dateObject;

}

// 修改表头信息
function titleTable(time, scope) {

    var now_year = time.getFullYear();
    var now_mouth = time.getMonth() + 1;

    var str = '';

    // 如果跨年
    if (isOverYear(time, scope)) {
        str = now_year + '年<b>' + now_mouth + '月</b>-' + (now_year + 1) + '年<b>1月</b>';

    } else if (isOverMounth(time, scope)) {
        str = now_year + '年<b>' + now_mouth + '月-' + (now_mouth + 1) + '月</b>';

    } else {
        str = now_year + '年<b>' + now_mouth + '月';
    }
    $('._timeScope').html(str);
}


function timeTable(time, type) {


    var rowOne_none = time.getDay();
    var rowOne_onlyShow = type.delay;

    // 第一行可用盒子
    var rowOne_all = 7 - rowOne_none;

    // 第一行正常盒子
    var rowOne_valid = 0;
    // 第二行仅展示盒子
    var rowTwo_onlyShow = 0;

    if (rowOne_all >= type.delay) {
        // 如果第一行可以容纳下
        rowOne_valid = rowOne_all - type.delay;
    } else {
        // 如果容不下
        rowOne_valid = 0;
        rowOne_onlyShow = 7 - rowOne_none;
        rowTwo_onlyShow = type.delay - rowOne_all;
    }

    var rowTwo_valid = 7 - rowTwo_onlyShow;

    var rowFour_none = rowOne_all - 1;
    var rowFour_valid = 8 - rowOne_all;

    var listNum = {
        'one': {
            'none': rowOne_none,
            'onlyShow': rowOne_onlyShow,
            'valid': rowOne_valid
        },
        'two': {
            'onlyShow': rowTwo_onlyShow,
            'valid': rowTwo_valid
        },
        'three': {
            'valid': 7
        },
        'four': {
            'none': rowFour_none,
            'valid': rowFour_valid
        }
    }


    listBox(listNum);

}


// 生成日历盒子
function listBox(data) {

    rowOne(data.one.none, data.one.onlyShow, data.one.valid);
    rowTwo(data.two.onlyShow, data.two.valid);
    rowThree(data.three.valid);
    rowFour(data.four.none, data.four.valid);

    /*如果是试用会员，则限制只能发布3天内的任务*/
    if (userType === 1) {
        $('.calendar-try li.row li[class]').each(function (index, el) {
            index > 2 ? $(el).prepend('<div class="box-mask"></div>') : null;
        });
    }
}

// 绘制第一行
function rowOne(noneNum, onlyShowNum, validNum) {

    var html = '';
    var ex_html = '';
    html += _noneBox(noneNum);
    html += _onlyShowBox(onlyShowNum);
    html += _validBox(validNum);

    ex_html += _noneBox(noneNum);
    ex_html += _onlyShowBox(onlyShowNum);
    ex_html += _validExchangeBox(validNum);

    $('#row_one_sum').html(html);
    $('#row_one_exc').html(ex_html);

}


function rowTwo(onlyShowNum, validNum) {
    var html = '';
    var ex_html = '';
    html += _onlyShowBox(onlyShowNum);
    html += _validBox(validNum);

    ex_html += _onlyShowBox(onlyShowNum);
    ex_html += _validExchangeBox(validNum);

    $('#row_two_sum').html(html);
    $('#row_two_exc').html(ex_html);

}


function rowThree(validNum) {
    var html = '';
    var ex_html = '';
    html += _validBox(validNum);

    ex_html += _validExchangeBox(validNum);


    $('#row_three_sum,#row_three_sum').html(html);
    $('#row_three_exc,#row_three_exc').html(ex_html);

}


function rowFour(noneNum, validNum) {
    var html = '';
    var ex_html = '';
    html += _validBox(validNum);
    html += _noneBox(noneNum);


    ex_html += _validExchangeBox(validNum);
    ex_html += _noneBox(noneNum);


    $('#row_four_sum').html(html);
    $('#row_four_exc').html(ex_html);

}


/*投放日历盒子*/

// 绘制空盒子
function _noneBox(num) {
    var str = '';
    for (var i = 0; i < num; i++) {
        str += '<li></li>';
    }
    return str;
}

// 绘制仅展示盒子
function _onlyShowBox(num) {
    var str = '';
    for (var i = 0; i < num; i++) {
        str += '<li class="only-show">';
        str += '<p class="only-show-day dateNum"></p>';
        str += '<p>试用展示时间</p>';
        str += '</li>';
    }


    return str;
}


// 绘制有效盒子
function _validBox(num) {
    var str = '';
    for (var i = 0; i < num; i++) {
        str += _vaildBox();
    }
    return str;
}

//绘制兑换日历的盒子
function _validExchangeBox(num) {
    var str = '';
    for (var i = 0; i < num; i++) {
        str += _invalidExcBox();
    }
    return str;
}


function _vaildBox() {
    var str = '';
    str += '<li class="validDay">';
    str += '<p class="date-day dateNum"></p>';
    str += '<div class="p-choose">';
    str += '<input type="button" href="javascript:;" class="change-num min_btn" value="-">';
    str += '<input type="text" class="p-btn" placeholder="投放数量">';
    str += '<input type="button" href="javascript:;" class="change-num add_btn" value="+">';
    str += '</div>';

    // 综合和新版内容营销活动 显示转化率
    if (type.tryType == 3 || type.tryType == 4) {

        str += '<div class="rate-box">';
        str += '<input type="text" class="rate" placeholder="日转化率：不限">';
        str += '<i>%</i>';
        str += '</div>';
        str += '<p class="limit_people">进店人数<b>最大化</b></p>';

    } else {
        // 人气和高价值、竞品活动 不显示转化率
        str += '<div class="rate-box" style="visibility:hidden;">';
        str += '<input type="text" class="rate" placeholder="日转化率：不限">';
        str += '<i>%</i>';
        str += '</div>';
        str += '<p class="limit_people" style="visibility:hidden;">进店人数<b>最大化</b></p>';



    }

    str += '</li>';

    return str;
}


function _validExcBox() {
    var str = '';
    str += '<li class="validDay_exc">';
    str += '<p class="date-day dateNum"></p>';
    str += '<div class="p-choose">';
    str += '<input type="button" href="javascript:;" class="change-num min_btn" value="-">';
    str += '<input type="text" class="p-btn" placeholder="兑换数量" readonly>';
    str += '<input type="button" href="javascript:;" class="change-num add_btn" value="+">';
    str += '</div>';
    str += '<p class="_maxNum">当日剩余可兑换<b>0单</b></p>';
    str += '</li>';

    return str;
}

function _invalidExcBox() {

    var str = '';
    str += '<li class="validDay_exc disable">';
    str += '<div class="box-mask"></div>';
    str += '<div class="box-main">';
    str += '<p class="date-day dateNum"></p>';
    str += '<div class="p-choose">';
    str += '<input type="button" href="javascript:;" class="change-num min_btn" value="-">';
    str += '<input type="text" class="p-btn" placeholder="兑换数量" readonly>';
    str += '<input type="button" href="javascript:;" class="change-num add_btn" value="+">';
    str += '</div>';
    str += '<p class="_maxNum">当日剩余可兑换<b>0单</b></p>';
    str += '</div>';
    str += '</li>';

    return str;


}


/**
 * 下面日历操作的部分：分为投放日历操作和兑换日历操作
 *
 */




// 开始投放计划
function schedule(type) {


    var order = {'sumOrder': 0, 'excOrder': 0, 'excActiveOrder': 0, 'maxOrder': {}, 'maxActiveOrder': {}}

    // 重新执行schedule前需要对页面上上一次的统计数据进行清空
    clearSumData();

    // 投放日历操作
    planSchedule(type, order);

    // 兑换日历操作
    exchangeSchedule(type, order);

}


function clearSumData() {

    $('#sum-tryOrder >b').html('0单');
    $('#sum-exchangeOrder >b').html('0单');
    $('#sum-tryOrder >span >b').html('0单');
    $('#sum-exc-tryOrder > b').html('0单');


}

// 通用交互操作add：添加按钮，min减少按钮，planInput输入框，rateInput比率框
function commonInteraction(add, min, planInput, rateInput) {

    var val_num = 0;
    var _this;

    add.on('click', function () {

        _this = $(this);
        var this_planInput = _this.parent().children('.p-btn');

        val_num = parseInt(this_planInput.val());

        val_num = isNaN(val_num) ? 1 : val_num + 1;
        valJudge(_this, val_num);

    });

    min.on('click', function () {
        _this = $(this);

        var this_planInput = _this.parent().children('.p-btn');
        val_num = parseInt(this_planInput.val());

        val_num = isNaN(val_num) ? -1 : val_num - 1;
        valJudge(_this, val_num);

    });

    planInput.on('change', function () {
        _this = $(this);

        val_num = parseInt(_this.val());
        valJudge(_this, val_num);

    });

    //如果转化率框存在，则执行
    if (rateInput) {
        rateInput.on('change', function () {
            valJudge($(this));
        })

    }

}

function valJudge(_this, num) {

    // 判断投放数量输入的合法性
    if (arguments.length == 2) {

        var _parent = _this.parent();
        if (num < 1 || isNaN(num)) {

            _parent.children('.p-btn').val("");
            _parent.children('.min_btn').css({disabled: 'true', cursor: 'not-allowed'});

        } else if (num >= 1000) {

            _parent.children('.p-btn').val("1000");
            _parent.children('.add_btn').css({disabled: 'true', cursor: 'not-allowed'});

        } else {
            _parent.children('.p-btn').val(num);
            _parent.children('.add_btn,.min_btn').css({disabled: 'false', cursor: 'pointer'});
        }

    } else if (arguments.length == 1) {
        // 判断投日转化率输入的合法性

        var rateNum = Number(parseFloat(_this.val()).toFixed(1));

        if (isNaN(rateNum) || rateNum <= 0) {
            _this.val('');
        } else if (rateNum > 100) {
            _this.val('100');
        } else {
            _this.val(rateNum);
        }
    }
}


function planSchedule(type, order) {

    var _addBtn = $('.calendar .add_btn');
    var _minBtn = $('.calendar .min_btn');
    var _planInput = $('.calendar .p-btn');
    var _rateInput = $('.calendar .rate');

    // 先执行通用的验证方式
    commonInteraction(_addBtn, _minBtn, _planInput, _rateInput);

    //自动补全的类型
    var autoType = 0;
    var vals_arr = [];


    _addBtn.on('click', function () {
        var _this = $(this);
        autoType = 1;
        // 可以应该判断文本框是从无到有，才执行isBounce，这样提高效率，后续在加。
        isBounce(autoType, _this, vals_arr);
        calcSumOrder(vals_arr, order);
        limitPeople();
        // 清空兑换日历之前算的值
        $('#sum-exc-tryOrder >b').html('0单');
        // 兑换日历
        exchangeSchedule(type, order);


    });

    _minBtn.on('click', function () {
        var _this = $(this);
        autoType = -1;
        isBounce(autoType, _this, vals_arr);
        calcSumOrder(vals_arr, order);
        limitPeople();
        // 清空兑换日历之前算的值
        $('#sum-exc-tryOrder >b').html('0单');
        exchangeSchedule(type, order);
    });

    _planInput.on('change', function () {
        var _this = $(this);
        autoType = $(this).val() == "" ? -1 : 1;
        isBounce(autoType, _this, vals_arr);
        calcSumOrder(vals_arr, order);
        limitPeople();
        // 清空兑换日历之前算的值
        $('#sum-exc-tryOrder >b').html('0单');
        exchangeSchedule(type, order);

    });

    _rateInput.on('change', function () {
        limitPeople();
    })


}


function isBounce(autoType, _this, vals_arr) {

    // 获取所有的有效盒子input
    var _validInputs = $('.validDay >.p-choose > .p-btn');
    var len = _validInputs.length;

    // 找到当前的盒子
    var _thisInput = _this.parent().children('.p-btn');
    // 找到当前盒子的序号
    var this_index = _validInputs.index(_thisInput);

    for (var i = 0; i < len; i++) {
        vals_arr[i] = isNaN(parseInt(_validInputs[i].value)) ? '' : parseInt(_validInputs[i].value);
    }

    if (autoType == 1) {
        //需要自动补全
        for (var i = this_index - 1; i > -1; i--) {
            if (!vals_arr[i]) {
                vals_arr[i] = 1;
            }
        }
    } else if (autoType == -1) {
        // 需要自动减少
        if (!_thisInput.val()) {
            for (var i = this_index + 1; i < len; i++) {

                vals_arr[i] = '';
            }
        }
    }
    // 把数组的值赋值给input框
    for (var i = 0; i < len; i++) {
        _validInputs[i].value = vals_arr[i];
    }


}


// 计算当天限制进店人数
function limitPeople() {

    // type.tryType 为1、2、5时没有转化率（人气，高价值，竞品）
    if (type.tryType == 3 || type.tryType == 4) {

        // 获取所有的投放数量盒子input
        var _validInputs = $('.validDay >.p-choose > .p-btn');
        var len = _validInputs.length;

        // 获取所有的有效转化率盒子input
        var _rateNums = $('.validDay >.rate-box > .rate');

        // 获得显示限制人数的
        var limit_people = $('.validDay >.limit_people');

        var inputNums = [];
        var rateNums = [];

        for (var i = 0; i < len; i++) {
            inputNums[i] = parseInt(_validInputs[i].value);
            rateNums[i] = parseFloat(_rateNums[i].value);

            if (!isNaN(inputNums[i]) && !isNaN(rateNums[i])) {

                /*   console.log(inputNums[i]);
                 console.log(rateNums[i]);
                 console.log(rateNums[i] / 100);
                 console.log(inputNums[i] / (rateNums[i] / 100));*/
                var limitNum = Math.ceil(inputNums[i] / (rateNums[i] / 100));
                $(limit_people[i]).html('限制进店人数<b>' + limitNum + '</b>人');

            } else {
                $(limit_people[i]).html('进店人数<b>最大化</b>');
            }
        }

    }


}

// 计算试用订单、兑换总数
function calcSumOrder(vals_arr, order) {
    var sumOrder = 0;
    for (var i = 0, len = vals_arr.length; i < len; i++) {
        if (vals_arr[i])
            sumOrder += vals_arr[i];
    }

    order.sumOrder = sumOrder;
    if(type.tryType == 4 || type.tryType == 5){
        order.excOrder = 0;
    } else {
        order.excOrder = Math.round(sumOrder * 0.2);
    }

    order.excActiveOrder = order.excOrder;

    $('#sum-tryOrder >b').html(order.sumOrder + '单');
    $('#sum-exchangeOrder >b').html(order.excOrder + '单');
    $('#sum-tryOrder >span >b').html(order.excOrder + '单');
}


function exchangeSchedule(type, order) {

    // 打开对应兑换盒子
    openExcBox(type);

    // 计算每个兑换盒子的最大值
    calcMax(order);
    // 兑换盒子输入
    excWrite(order);

}


function openExcBox(type) {

    // 获取所有的投放数量盒子input
    var _validInputs = $('.validDay >.p-choose > .p-btn');
    var validLen = _validInputs.length;

    // 获取所有的兑换日历的同步输入的盒子
    var _excBoxes = $('.calendar-exchange li.validDay_exc');

    var inputNums = [];


    // 如果投放盒子有值，把对应的兑换盒子打开
    for (var i = 0; i < validLen; i++) {
        inputNums[i] = parseInt(_validInputs[i].value);

        if (!isNaN(inputNums[i])) {
            $(_excBoxes[i]).replaceWith(_validExcBox());
        } else {
            $(_excBoxes[i]).replaceWith(_invalidExcBox());
        }
    }


    //兑换盒子日历数字显示

    // 获取当前用户选择的天数
    var scope = 22;

    var chooseDay = $('#chooseTime').val();

    var today = new Date(today_Time_server);
    var hour = today.getHours();
    var tomorrow = new Date(new Date().setTime(today.getTime() + 24 * 60 * 60 * 1000));

    if (type.tryType == 3 && hour >= 16) {

        chooseDay ? numTable(new Date(chooseDay), scope) : numTable(tomorrow, scope);

    } else {

        chooseDay ? numTable(new Date(chooseDay), scope) : numTable(today, scope);
    }


}


function calcMax(order, index, clickType) {
    // 总共可兑换数量
    var excActiveOrder = order.excActiveOrder;

    // 获取所有的投放数量盒子input
    var _validInputs = $('.validDay >.p-choose > .p-btn');
    var validLen = _validInputs.length;


    var _excInput = $('.calendar-exchange .p-btn');

    var maxInput = $('.calendar-exchange li.validDay_exc ._maxNum > b');


    var inputNums = [];
    var exc_inputNums = [];
    var maxNum = [];

    var currentMax;


    for (var i = 0; i < validLen; i++) {
        // 获取当前用户输入的兑换的数字
        exc_inputNums[i] = isNaN(parseInt(_excInput[i].value)) ? 0 : parseInt(_excInput[i].value);
        // 如果是通过兑换按钮改变的，则除了改变的maxNum不在这里改变（因为在点击的时候改变），其他的是按照最小值和投放日历比较得到
        if (!clickType) {
            // 页面初始化
            inputNums[i] = isNaN(parseInt(_validInputs[i].value)) ? 0 : parseInt(_validInputs[i].value); //5
            maxNum[i] = Math.min(inputNums[i], excActiveOrder);

            currentMax = Math.min(maxNum[i], inputNums[i]);
            currentMax = currentMax < 0 ? 0 : currentMax;
            // 记录所有兑换盒子原始的最大值
            order.maxOrder[i] = currentMax;

            order.maxActiveOrder[i] = order.maxOrder[i];

        } else if (clickType == 1) {
            // 如果点击的是+
            if (i != index) {
                // 判断如果不是点击的盒子，判断剩余兑换的最大值和当前最大值比较
                var param = Math.min(excActiveOrder, order.maxActiveOrder[i]);
                order.maxActiveOrder[i] = param;
            } else if (i == index) {
                // 如果是当前盒子，则最大值就是当前最大的
                param = order.maxActiveOrder[i];
            }
        } else if (clickType == 2) {
            // 如果点击的是-
            if (i != index) {
                // 判断如果不是点击的盒子，判断剩余兑换的最大值和当前最大值比较

                // 当前的所有盒子活动的最大值+1
                var param = order.maxActiveOrder[i] + 1;

                // 如果兑换的数字+当天限制的日期+1  超过了 原始的最大值，让最大值等于原始值
                if ((exc_inputNums[i] + param) > order.maxOrder[i]) {
                    order.maxActiveOrder[i] = order.maxActiveOrder[i];
                } else {
                    order.maxActiveOrder[i] = param;
                }
            }
        }

        $(maxInput[i]).html(order.maxActiveOrder[i] + '单');
    }

}


function excWrite(order) {

    var _addBtn = $('.calendar-exchange .add_btn');
    var _minBtn = $('.calendar-exchange .min_btn');
    var _planInput = $('.calendar-exchange .p-btn');

    _addBtn.on('click', function () {

        _this = $(this);
        var this_planInput = _this.parent().children('.p-btn');

        val_num = parseInt(this_planInput.val());

        val_num = isNaN(val_num) ? 0 : val_num;
        maxJudge(_this, val_num, order);
        sumExcOrder();
    });

    _minBtn.on('click', function () {
        _this = $(this);

        var this_planInput = _this.parent().children('.p-btn');
        val_num = parseInt(this_planInput.val());

        val_num = isNaN(val_num) ? 0 : val_num;
        maxJudge(_this, val_num, order);
        sumExcOrder();
    });

    _planInput.on('change', function () {
        _this = $(this);

        val_num = parseInt(_this.val());

        maxJudge(_this, val_num, order);
        sumExcOrder();
    });
}


function maxJudge(_this, num, order) {


    var excActiveOrder = order.excActiveOrder;

    var _addBtn = $('.calendar-exchange .add_btn');
    var _minBtn = $('.calendar-exchange .min_btn');
    var _planInput = $('.calendar-exchange .p-btn');
    // 放最大值的盒子
    var _maxNum = $('.calendar-exchange ._maxNum > b');

    // 找到我点击的盒子的序号,判断的点击的类型
    var index = 0;
    var clickType = 0;
    if (_addBtn.index(_this) > -1) {
        index = _addBtn.index(_this);
        clickType = 1;
    } else if (_minBtn.index(_this) > -1) {
        index = _minBtn.index(_this);
        clickType = 2;
    } else if (_planInput.index(_this) > -1) {
        index = _planInput.index(_this);
        clickType = 3;
    }


    var _parent = _this.parent();
    // 如果点击的是+
    if (clickType == 1) {
        if (parseInt(_maxNum[index].innerHTML) == 0) {
            _parent.children('.add_btn').css({disabled: 'true', cursor: 'not-allowed'});
        } else {
            _parent.children('.add_btn').css({disabled: 'false', cursor: 'pointer'});

            excActiveOrder -= 1;
            order.excActiveOrder = excActiveOrder;
            order.maxActiveOrder[index] -= 1;
            calcMax(order, index, clickType);

            num += 1;

        }
        // 如果点击的是-
    } else if (clickType == 2) {
        if (num == 0) {
            _parent.children('.min_btn').css({disabled: 'true', cursor: 'not-allowed'});
        } else {
            _parent.children('.min_btn').css({disabled: 'false', cursor: 'pointer'});

            excActiveOrder += 1;
            order.excActiveOrder = excActiveOrder;
            order.maxActiveOrder[index] += 1;
            calcMax(order, index, clickType);
            num -= 1;
        }

    }
    // 如果是直接输入的input
    /* else if (clickType == 3) {
     if (isNaN(num)) {
     num = 0;
     } else if (num >= order.maxcAtiveOrder[index]) {
     num = order.maxcAtiveOrder[index];
     order.maxcAtiveOrder[index] = 0;
     excActiveOrder -= num;
     order.excActiveOrder = excActiveOrder;
     calcMax(order, index);
     } else {
     order.maxcAtiveOrder[index] = order.maxcAtiveOrder[index] - num;
     excActiveOrder -= num;
     order.excActiveOrder = excActiveOrder;
     calcMax(order, index);
     }

     }*/
    _planInput[index].value = num <= 0 ? "" : num;


}

function sumExcOrder() {
    // 显示已经兑换多少
    var _planInput = $('.calendar-exchange .p-btn');
    var sumExcOrder = 0;
    for (var i = 0, len = _planInput.length; i < len; i++) {
        sumExcOrder += isNaN(parseInt(_planInput[i].value)) ? 0 : parseInt(_planInput[i].value);
    }
    $('#sum-exc-tryOrder > b').html(sumExcOrder + '单');
}


/**
 * 如果用户有填写过，则日历显示用户有的值
 *
 */

function isWrited(type) {


    // 当获取用户有填写日历值时，填充日历
    $.ajax({
        url: '/Merchant_issue_try4/getData',
        type: 'POST',
        data: {
            act_id: act_id,
            old_act_id: old_act_id,
            act_type: type.tryType
        },
        success: function (data) {
            if (data) {
                var data = $.parseJSON(data);
                // 如果有填写
                if (data.trial_info.trial_num != '') {
                    // 如果信息是今天之前保存的，则不用读取（不允许修改保存今天之前的信息）
                    var toPublish = data.trial_info.toPublish_time * 1000;
                    var fillRecord_flag = isEmptyRecord(toPublish,today_Time_server,type);

                    //1 不填充  0 填充
                    fillRecord_flag == 0 ?reproduction(data, type):null;


                }
            }
        }
    })
}


/**
 * 判断是否需要重新渲染用户填写过的数据
 * return  1 不填充  0 填充
 */
function isEmptyRecord(publish_time,server_time,type){
    var flag = 0;
    var publish_time_date = getNowFormatDate(new Date(publish_time)).match(/\d/g).join('') - 0; //例如：20170808
    var server_time_date = getNowFormatDate(new Date(server_time)).match(/\d/g).join('') - 0;  //例如：20170807


    //高人气、高价值、竞品
    //如果发布时间 < 当前时间 ，则清空数据
    if(type.tryType == 1 || type.tryType == 2 || type.tryType == 5){
        flag = publish_time_date < server_time_date ? 1 : 0;

    }

    //综合、内容营销
    if(type.tryType == 3 || type.tryType == 4){

        var server_time_h = new Date(server_time * 1000).getHours();
        //如果当前时间在16点之前,如果发布时间 < 当前时间 ，则清空数据
        if(server_time_h < 16){
            flag = publish_time_date < server_time_date ? 1 : 0;

        }else if(server_time_h >= 16){
            //如果当前时间在16点之后,如果发布时间 <= 当前时间 ，则清空数据
            flag = publish_time_date <= server_time_date ? 1 : 0;
        }
    }

    return flag;
}


// 根据用户填写过的内容，填充日历
function reproduction(data, type) {

    //虚拟一个初始化order跑流程
    var order = {'sumOrder': 0, 'excOrder': 0, 'excActiveOrder': 0, 'maxOrder': {}, 'maxActiveOrder': {}}

    // 重置时间
    reTime(data, type);

    // 重置试用订单日历
    reOrderCalc(data, type, order);

    // 重置兑换订单日历
    reExcOrderCalc(data, type, order);

    // 重新限制其它错误提示
    //如果是竞品打标搜索，每天投放份数不得超过十份
    type.tryType == 5?limitSetDayTips(type):null;

}


function reTime(data, type) {
    var toPublish_time = data.trial_info.toPublish_time;
    var time = new Date(parseInt(toPublish_time) * 1000);
    time = getNowFormatDate(time);
    $('#chooseTime').val(time);
    whichDay(new Date(time), type);

}


function reOrderCalc(data, type, order) {

    var trial_num = data.trial_info.trial_num;
    var conversion_rate = data.trial_info.conversion_rate;


    var trial_num_arr = trial_num.split(',');

    trial_num_arr = trial_num_arr.map(function (num) {
        return parseInt(num);
    })

    var rate_arr = conversion_rate.split(',');

    var _input_box = $('.calendar-try .p-choose .p-btn');
    var _rate_box = $('.calendar .rate-box .rate');

    // 填充试用订单日历
    _input_box.each(function (i) {
        $(this).val(trial_num_arr[i]);
    });

    _rate_box.each(function (i) {
        rate_arr[i] != 0 ? $(this).val(rate_arr[i]) : null;
    });

    limitPeople();

    // 计算试用订单总数
    calcSumOrder.call(null, trial_num_arr, order);
    planSchedule(type, order)

}


function reExcOrderCalc(data, type, order) {

    var exchange_num = data.trial_info.exchange_num;
    var exchange_num_arr = exchange_num.split(',');

    // 执行打开兑换盒子
    exchangeSchedule(type, order);
    // 填充兑换盒子
    var _exc_input_box = $('.calendar-exchange .p-choose .p-btn');

    _exc_input_box.each(function (i) {
        exchange_num_arr[i] != 0 ? $(this).val(exchange_num_arr[i]) : null;
    });

    sumExcOrder();


    // 计算每个盒子最大值
    calcMax.call(null, order);

    var maxActiveOrder = order.maxActiveOrder;
    var maxInput = $('.calendar-exchange li.validDay_exc ._maxNum > b');

    // 重置每个兑换盒子maxoActiveOrder
    for (var i in maxActiveOrder) {
        maxActiveOrder[i] = 0;
        maxInput[i].innerHTML = maxActiveOrder[i] + '单';
    }

    // 重置兑换总数order数量
    order.excActiveOrder = 0;

}

//下一步
function next_step() {
    //var seller_id = <?php echo $seller_id ?>;

    var type = Number(tryType);
    var show_day = 0;
    if (type == 1 || type == 5) {
        show_day = 1;
    } else if (type == 2) {
        show_day = ($("#delay4").is(':checked')) ? 4 : 6;
    } else if (type == 3) {
        show_day = 0;
    }
    var apply_amount = parseInt($("#apply_amount").text());
 
    var exchange = $("#sum-exchangeOrder b").text();
    exchange = exchange.substring(0, exchange.length - 1);
    var toPublish_time = $("#chooseTime").val();
    var trial_num = '';
    var conversion_rate = '';
    var exchange_num = '';
    var i = 0;

    $(".calendar").eq(0).find(".p-btn").each(function () {
        if ($(this).val()) {
            trial_num = trial_num + $(this).val() + ',';
            i++;
        }
    });

    $(".calendar").eq(0).find(".rate").each(function (k) {
        if (k < i)
            conversion_rate = conversion_rate + (($(this).val()) ? $(this).val() : 0) + ',';
    });
    $(".calendar").eq(1).find(".p-btn").each(function (index) {
        if (index < i) {
            exchange_num = exchange_num + (($(this).val()) ? $(this).val() : 0) + ',';
        }
    });

    if (toPublish_time == '0000-00-00') {
        alert('请设置任务上线时间');
        return false;
    } else if (toPublish_time.length == 0) {
        alert('请设置任务上线时间');
        return false;
    }

    trial_num = trial_num.substring(0, trial_num.length - 1);
    conversion_rate = conversion_rate.substring(0, conversion_rate.length - 1);
    exchange_num = exchange_num.substring(0, exchange_num.length - 1);

    $.ajax({
        url: '',
        data: {
            act_id: act_id,
            apply_amount: apply_amount,
            exchange_total: exchange,
            show_day: show_day,
            trial_num: trial_num,
            conversion_rate: conversion_rate,
            exchange_num: exchange_num,
            toPublish_time: toPublish_time
        },
        type: 'post',
        cache: false,
        success: function(data) {
            var to = ((type == 4 || type == 5) ? 6: 5);
            if(old_act_id){
                //location.href = "/merchant_issue_try_add?act_id=" + act_id + '&old_act_id=' + old_act_id;
                location.href = "/merchant_issue_try" + to + "?act_id=" + act_id + '&old_act_id=' + old_act_id;
            }else{
                //location.href = "/merchant_issue_try_add?act_id=" + act_id;
                location.href = "/merchant_issue_try" + to + "?act_id=" + act_id;
            }
        },
        error: function (XMLHttpRequest, textStatus) {
        }
    })
}
