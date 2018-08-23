var type = {'tryType': tryType, 'delay': 0};
(function () {
        bindEvent(1);
})();
function bindEvent() {

    // 获取用户选择日期
    chooseTime();

    // 切换高价值日历，延迟展示时间
    var today = new Date(now_time);
    // 获取当前用户选择的天数
    var chooseDay = $('#chooseTime').val();
    chooseDay ? whichDay(new Date(chooseDay)) : whichDay(today);

    schedule();

}
function chooseTime() {
    var hour = new Date(now_time*1000).getHours();
    if ( hour >= 12) {
    	type.delay = 1;
        laydate({
            elem: '#chooseTime',
            format: 'YYYY-MM-DD',
            festival: true,
            istime: false,
            min: laydate.now(+1),
            // max: laydate.now(+60),
            choose: function (times) { //选择日期完毕的回调
            	if(Date.parse(new Date(times)) > Date.parse(new Date(now_time))){
            		type.delay = 0;
            	}
                whichDay(times);
                schedule();
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
            	type.delay = 0;
                whichDay(times);
                schedule();
            }
        });

    }
}
function whichDay(times) {

        // 绘制日历范围
        var scope = 22;

        // 获得用户选择的时间
        var time = new Date(times);

        // 绘制日历
        timeTable(time);

        // 绘制日历数字
        numTable(time, scope);

        // 修改表头信息
        titleTable(time, scope);

}

    // 开始投放计划
    function schedule() {
        var order = {'sumOrder': 0, 'maxOrder': {}, 'maxActiveOrder': {}}
        // 重新执行schedule前需要对页面上上一次的统计数据进行清空
        clearSumData();
        // 投放日历操作
        planSchedule(order);

    }
    function clearSumData() {
        $('#sum-tryOrder >b').html('0单');
    }

function planSchedule(order) {

    var _addBtn = $('.calendar .add_btn');  //"+"
    var _minBtn = $('.calendar .min_btn');  //"-"
    var _planInput = $('.calendar .p-btn'); //投放数量
   	var _rateInput = $('.calendar .rate');

    // 先执行通用的验证方式
    commonInteraction(_addBtn, _minBtn, _planInput,_rateInput);

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
    });

    _minBtn.on('click', function () {
        var _this = $(this);
        autoType = -1;
        isBounce(autoType, _this, vals_arr);
        calcSumOrder(vals_arr, order);
        limitPeople();

    });

    _planInput.on('change', function () {
        var _this = $(this);
        autoType = $(this).val() == "" ? -1 : 1;
        isBounce(autoType, _this, vals_arr);
        calcSumOrder(vals_arr, order);
        limitPeople();
    });
	_rateInput.on('change', function () {
        limitPeople();
    })

}
// 计算当天限制进店人数
function limitPeople() {

    // type.tryType 为1时没有转化率
    if (type.tryType == 2 ) {

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
                var limitNum = Math.ceil(inputNums[i] / (rateNums[i] / 100));
                $(limit_people[i]).html('限制进店人数<b>' + limitNum + '</b>人');

            } else {
                $(limit_people[i]).html('进店人数<b>最大化</b>');
            }
        }

    }


}
// 通用交互操作add：添加按钮，min减少按钮，planInput输入框，rateInput比率框
function commonInteraction(add, min, planInput,rateInput) {

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
// 计算试用订单
function calcSumOrder(vals_arr, order) {
    var sumOrder = 0;
    for (var i = 0, len = vals_arr.length; i < len; i++) {
        if (vals_arr[i])
            sumOrder += vals_arr[i];
    }
    order.sumOrder = sumOrder;
    $('#sum-tryOrder >b').html(order.sumOrder + '单');
}
function timeTable(time) {

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
        //暂时注释
        /*如果是试用会员，则限制只能发布3天内的任务*/
        // if (userType === 1) {
        //     $('.calendar-try li.row li[class]').each(function (index, el) {
        //         index > 2 ? $(el).prepend('<div class="box-mask"></div>') : null;
        //     });
        // }
    }

    // 绘制第一行
    function rowOne(noneNum, onlyShowNum, validNum) {

        var html = '';
        html += _noneBox(noneNum);
        html += _onlyShowBox(onlyShowNum);
        html += _validBox(validNum);
        $('#row_one_sum').html(html);
    }


    function rowTwo(onlyShowNum, validNum) {
        var html = '';
        html += _onlyShowBox(onlyShowNum);
        html += _validBox(validNum);
        $('#row_two_sum').html(html);
    }


    function rowThree(validNum) {
        var html = '';
        html += _validBox(validNum);
        $('#row_three_sum,#row_three_sum').html(html);
    }


    function rowFour(noneNum, validNum) {
        var html = '';
        html += _validBox(validNum);
        html += _noneBox(noneNum);
        $('#row_four_sum').html(html);
    }

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
function _vaildBox() {
    var str = '';
    str += '<li class="validDay">';
    str += '<p class="date-day dateNum"></p>';
    str += '<div class="p-choose">';
    str += '<input type="button" href="javascript:;" class="change-num min_btn" value="-">';
    str += '<input type="text" class="p-btn" placeholder="投放数量" name="num[]"> <input type="hidden" name="day[]">';
    str += '<input type="button" href="javascript:;" class="change-num add_btn" value="+">';
    str += '</div>';

    // 综合和新版内容营销活动 显示转化率
    if (type.tryType == 2) {

        str += '<div class="rate-box">';
        str += '<input type="text" class="rate" placeholder="日转化率：不限" name="rate[]"> ';
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
function numTable(time, scope) {
        var dateList = _numDateArr(time, scope);
        // 填充数据
        _fillDate($('#row_one_sum >li'), dateList['one']);
        _fillDate($('#row_two_sum >li'), dateList['two']);
        _fillDate($('#row_three_sum >li'), dateList['three']);
        _fillDate($('#row_four_sum >li'), dateList['four']);
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
            $(ele[i]).find('.p-btn').attr('id','d_'+data[j]);
            $(ele[i]).find('.rate').attr('id','r_'+data[j]);
            $(ele[i]).find('.limit_people').attr('id','p_'+data[j]);
            $(ele[i]).find('input[type=hidden]').val(data[j]);
            j++;

        }
    }

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
    //返回每行需要的日期数字对象
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

