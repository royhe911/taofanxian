function checkPage() {

    $("#J_logbuy").click(function (event) {
        event.stopPropagation();
    });
    $("#J_logcart").click(function (event) {
        event.stopPropagation();
    });
    $("#J_usertag").click(function (event) {
        event.stopPropagation();
    });
    $("#J_comment2OtherB").click(function (event) {
        event.stopPropagation();
    });
    $("#J_other2youB").click(function (event) {
        event.stopPropagation();
    });
    $("#J_logrefund").click(function (event) {
        event.stopPropagation();
    });
    $("#J_inA").click(function (event) {
        event.stopPropagation();
    });
    $("#J_outA").click(function (event) {
        event.stopPropagation();
    });
}

$(document).ready(function () {
    checkPage();
    $('#J_zbfdesc').hide();
    var data = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {width: 150, text: '操作日期', type: 'string', flex: true, colClass: 'text-center'},
            {width: 150, text: '商品图片', type: 'string', flex: true, colClass: 'text-center '},
            {width: 250, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品数量', type: 'number', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品价格', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '交易状态', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: []
    };

    $('#buyedGoodsTable').datatable({
        data: data,
        checkable: false,
        sortable: false
    });
    $('#cartGoodsTable').datatable({
        data: data,
        checkable: false,
        sortable: false
    });

    $('#refundGoodsTable').datatable({
        data: data,
        checkable: false,
        sortable: false
    });

    var data1 = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {sort: false, width: 200, text: '日期', type: 'string', flex: true, colClass: 'text-center '},
            {width: 250, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '价格', type: 'number', flex: true, colClass: 'text-center'},
            {width: 300, text: '评论', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '评价结果', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: []
    };
    $('#comment2Other').datatable({
        data: data1,
        checkable: false,
        sortable: false
    });
    $('#other2you').datatable({
        data: data1,
        checkable: false,
        sortable: false
    });
    $('#mask').click(function () {
        if($(this).css('display') === 'block'){
            // $("#J_buyedGoods").hide();
            $("#J_cartGoods").hide();
            $("#J_userTag").hide();
            $("#J_comment2Other").hide();
            $("#J_other2You").hide();
            $("#J_refundGoods").hide();
            $("#J_inOutOfLine").hide();
            $("#J_outOutOfLine").hide();
            $("#mask").hide();
            $("#J_body").css("height", $(document).height() - 100).css("overflow", "auto");
            isScroll();
        }
        
    });
});

function queryData() {
    $("#J_cartTableData  tr:not(:first)").html("");
    $("#J_tagTableData  tr:not(:first)").html("");
    $("#J_tableData  tr:not(:first)").html("");
    $("#J_inOutOfLineTableData  tr:not(:first)").html("");
    $("#J_outOutOfLineTableData  tr:not(:first)").html("");
    
    var nick = $.trim($('#J_nick').val());
    if(!nick){
        layer.msg('请输入买家旺旺帐号！');
        return ;
    }
    var param = {
        'nick': nick
    };
    layer.msg('获取中', {
        time: 20000, //20s后自动关闭
        //btn: ['明白了', '知道了', '哦']
    });
    $.ajax({
        type: "post",
        url: post_url,
        dataType: "json",
        data: param,
        success: function (data) {
            layer.closeAll();
            if (data.error_message != undefined) {
                var message = data.error_message;
                layer.open({
                    title: '提示',
                    content: message
                });
            } else {
                if (data.success != undefined && data.success == 'success') {
                    $('#J_resultReal').show();
                    var sellerInfo = data.sellerInfo;
                    $('#J_honorPic').attr('src', '');
                    if (sellerInfo.desc != null && sellerInfo.desc != '' && sellerInfo.desc.indexOf('宝宝是个健康的消费者') != -1) {
                        $('#J_color1').hide();
                        $('#J_color').show();
                        $('#J_desc').html(sellerInfo.desc);
                        $('#J_color').removeClass('text-danger');
                        $('#J_color').addClass('text-success');
                    } else {
                        $('#J_color').hide();
                        $('#J_color1').show();
                        $('#J_inNumId').html(sellerInfo.inNum);
                        $('#J_outNumId').html(sellerInfo.outNum);
                        if (sellerInfo.inNum > 0) {
                            $('#J_inDetailData').show();
                            $.each(sellerInfo.inOutOfLine, function (i, n) {
                                var inRow = $("#J_inDetailData").clone();
                                inRow.find("#J_inNum").text(i + 1);
                                inRow.find("#J_inTitle").text(n.title);
                                inRow.find("#J_inTips").text(n.tips);
                                inRow.find("#J_inDesc").text(n.desc);
                                inRow.appendTo("#J_inOutOfLineData");//添加到模板的容器中
                            });
                            $('#J_inDetailData').hide();
                        }
                        if (sellerInfo.outNum > 0) {
                            $('#J_outDetailData').show();
                            $.each(sellerInfo.outOfLined, function (i, n) {
                                var outRow = $("#J_outDetailData").clone();
                                outRow.find("#J_outNum").text(i + 1);
                                outRow.find("#J_outTitle").text(n.title);
                                outRow.find("#J_outTips").text(n.tips);
                                outRow.find("#J_outDesc").text(n.desc);
                                outRow.appendTo("#J_outOutOfLineData");////添加到模板的容器中
                            });
                            $('#J_outDetailData').hide();
                        }
                    }
                    if (sellerInfo.headUrl != null && sellerInfo.headUrl != '') {
                        $("#J_headPic").attr("src", sellerInfo.headUrl);
                    } else {
                        $("#J_headPic").attr("src", "https://gw.alicdn.com/tps/i3/TB1yeWeIFXXXXX5XFXXuAZJYXXX-210-210.png");
                    }
                    if (sellerInfo.isSafe != null && sellerInfo.isSafe != '') {
                        if (sellerInfo.isSafe == 'true') {
                            $('#J_recommad').show();
                            $('#J_safe').show();
                            $('#J_notSafe').hide();
                            $('#J_notsafeDesc').hide();
                            $('#J_notSafeReason').hide();
                            if (sellerInfo.isLiar!=null && sellerInfo.isLiar >0){
                                $('#J_liar').show();
                                $('#J_liarDesc').show();
                                $('#J_liarDesc').html('<a onclick="gotoZhjc();" style="color: red;font-weight:bold;">该账号一共被 '+sellerInfo.isLiar+' 个商家举报。点击查看举报内容！</a>');
                            }else{
                                $('#J_liar').hide();
                                $('#J_liarDesc').hide();
                            }
                            if (sellerInfo.category != null && sellerInfo.category != '') {
                                $('#J_fitCategoryDesc').show();
                                $('#J_fitCategory').show();
                                $('#J_fitCategory').html(sellerInfo.category);
                            } else {
                                $('#J_fitCategoryDesc').hide();
                                $('#J_fitCategory').hide();
                            }
                        }
                        if (sellerInfo.isSafe == 'false') {
                            $('#J_fitCategoryDesc').hide();
                            $('#J_fitCategory').hide();
                            $('#J_recommad').show();
                            $('#J_safe').hide();
                            $('#J_notSafe').show();
                            if (sellerInfo.notSafeReason!=null && sellerInfo.notSafeReason!='') {
                                $('#J_notsafeDesc').show();
                                $('#J_notSafeReason').show();
                                $('#J_notSafeReason').html(sellerInfo.notSafeReason);
                            }else {
                                $('#J_notsafeDesc').hide();
                                $('#J_notSafeReason').hide();
                            }
                            if (sellerInfo.isLiar!=null && sellerInfo.isLiar >0){
                                $('#J_liar').show();
                                $('#J_liarDesc').show();
                                $('#J_liarDesc').html('<a onclick="gotoZhjc();" style="color: red;font-weight:bold;">该账号一共被 '+sellerInfo.isLiar+' 个商家举报。点击查看举报内容！</a>');
                            }else{
                                $('#J_liar').hide();
                                $('#J_liarDesc').hide();
                            }
                        }
                    } else {
                        $('#J_recommad').hide();
                    }
                    if (sellerInfo.averageFee != null && sellerInfo.averageFee != '') {
                        if (sellerInfo.averageFee == '订单数量过多，计算可能不准确') {
                            $('#J_averageFee').html(sellerInfo.averageFee);
                            document.getElementById("J_averageFee").style.color = 'black';
                        } else {
                            $('#J_averageFee').html(sellerInfo.averageFee + '元');
                            document.getElementById("J_averageFee").style.color = '#38b03f';
                        }
                    } else {
                        $('#J_averageFee').html('未计算');
                        document.getElementById("J_averageFee").style.color = 'black';
                    }
                    $('#J_sellerNick').html(sellerInfo.nick);
                    $('#J_sellerAge').html(sellerInfo.sellerAge);
                    $('#J_sex').html(sellerInfo.sex);
                    $('#J_time').html(sellerInfo.lastUpdateTime);
                    $('#J_birthday').html(sellerInfo.birthday);
                    $('#J_mail').html(sellerInfo.mail);
                    $('#J_shiming').html((sellerInfo.shiMing == null || sellerInfo.shiMing == '') ? '--' : sellerInfo.shiMing);
                    $('#J_zhifubao').html((sellerInfo.zhifubao == null || sellerInfo.zhifubao == '') ? '--' : sellerInfo.zhifubao);
                    $('#J_waitPay').html(sellerInfo.waitPay);
                    $('#J_waitConsign').html(sellerInfo.waitConsign);
                    $('#J_waitSign').html(sellerInfo.waitSign);
                    $('#J_waitRate').html(sellerInfo.waitRate);
                    if (sellerInfo.huabei != null && sellerInfo.huabei != '' && parseInt(sellerInfo.huabei) > 0) {
                        document.getElementById("J_huabei").style.color = '#66CD00';
                        $('#J_huabei').html("已开通");
                    } else {
                        document.getElementById("J_huabei").style.color = 'red';
                        $('#J_huabei').html("未开通");
                    }
                    $('#J_inRefund').html(sellerInfo.inRefund);
                    if (sellerInfo.taoAge == null || sellerInfo.taoAge == '') {
                        $('#J_failed').show();
                    } else {
                        $('#J_failed').hide();
                    }
                    $('#J_taoAge').html((sellerInfo.taoAge == null || sellerInfo.taoAge == '') ? '--' : sellerInfo.taoAge);
                    $('#J_startDate').html((sellerInfo.startDate == null || sellerInfo.startDate == '') ? '--' : sellerInfo.startDate);
                    $('#J_countDays').html((sellerInfo.countDays == null || sellerInfo.countDays == '') ? '--' : sellerInfo.countDays);
                    $('#J_countLikes').html((sellerInfo.countLikes == null || sellerInfo.countLikes == '') ? '--' : sellerInfo.countLikes);
                    $('#J_countCity').html((sellerInfo.countCity == null || sellerInfo.countCity == '') ? '--' : sellerInfo.countCity);
                    $('#J_amount').html((sellerInfo.useAccount == null || sellerInfo.useAccount == '') ? '--' : sellerInfo.useAccount);
                    $('#J_orderNum').html((sellerInfo.orderNum == null || sellerInfo.orderNum == '') ? '--' : sellerInfo.orderNum);
                    $('#J_averagePay').html((sellerInfo.averagePay == null || sellerInfo.averagePay == '') ? '--' : sellerInfo.averagePay);
                    $('#J_registerAddr').html(sellerInfo.registerAddr);
                    $('#J_registerIp').html(sellerInfo.ip);
                    $('#J_registerIpTimes').html(sellerInfo.ipTimes);
                    $('#J_refundRate').html("近3月售中退款率:" + sellerInfo.refundRate + "%,总单量:" + sellerInfo.threeMonthOrder);
                    if (sellerInfo.goodsJudgeVO != null) {
                        $('#J_goodWeek').html(sellerInfo.goodsJudgeVO.week);
                        $('#J_goodOneMonth').html(sellerInfo.goodsJudgeVO.oneMonth);
                        $('#J_goodSixMonth').html(sellerInfo.goodsJudgeVO.sixMonth);
                        $('#J_goodBeforSixMonth').html(sellerInfo.goodsJudgeVO.beforSixMonth);
                        $('#J_goodTotal').html(sellerInfo.goodsJudgeVO.total);
                    } else {
                        $('#J_goodWeek').html(0);
                        $('#J_goodOneMonth').html(0);
                        $('#J_goodSixMonth').html(0);
                        $('#J_goodBeforSixMonth').html(0);
                        $('#J_goodTotal').html(0);
                    }
                    if (sellerInfo.normalJudgeVO != null) {
                        $('#J_normalWeek').html(sellerInfo.normalJudgeVO.week);
                        $('#J_normalOneMonth').html(sellerInfo.normalJudgeVO.oneMonth);
                        $('#J_normalSixMonth').html(sellerInfo.normalJudgeVO.sixMonth);
                        $('#J_normalBeforSixMonth').html(sellerInfo.normalJudgeVO.beforSixMonth);
                        $('#J_normalTotal').html(sellerInfo.normalJudgeVO.total);
                    } else {
                        $('#J_normalWeek').html(0);
                        $('#J_normalOneMonth').html(0);
                        $('#J_normalSixMonth').html(0);
                        $('#J_normalBeforSixMonth').html(0);
                        $('#J_normalTotal').html(0);
                        $('#J_badWeek').html(0);
                    }
                    if (sellerInfo.badJudgeVO != null) {
                        $('#J_badWeek').html(sellerInfo.normalJudgeVO.week);
                        $('#J_badOneMonth').html(sellerInfo.badJudgeVO.oneMonth);
                        $('#J_badSixMonth').html(sellerInfo.badJudgeVO.sixMonth);
                        $('#J_badBeforSixMonth').html(sellerInfo.badJudgeVO.beforSixMonth);
                        $('#J_badTotal').html(sellerInfo.badJudgeVO.total);
                    } else {
                        $('#J_badWeek').html(0);
                        $('#J_badOneMonth').html(0);
                        $('#J_badSixMonth').html(0);
                        $('#J_badBeforSixMonth').html(0);
                        $('#J_badTotal').html(0);
                    }
                    if (sellerInfo.totalJudgeVO != null) {
                        $('#J_totalWeek').html(sellerInfo.totalJudgeVO.week);
                        $('#J_totalOneMonth').html(sellerInfo.totalJudgeVO.oneMonth);
                        $('#J_totalSixMonth').html(sellerInfo.totalJudgeVO.sixMonth);
                        $('#J_totalBeforSixMonth').html(sellerInfo.totalJudgeVO.beforSixMonth);
                        $('#J_totalTotal').html(sellerInfo.totalJudgeVO.total);
                    } else {
                        $('#J_totalWeek').html(0);
                        $('#J_totalOneMonth').html(0);
                        $('#J_totalSixMonth').html(0);
                        $('#J_totalBeforSixMonth').html(0);
                        $('#J_totalTotal').html(0);
                    }

                    $('#J_tmallPoint').html(sellerInfo.tmallPoint);
                    $('#J_vipLevl').html(sellerInfo.vipLevl);
                    $('#J_reputationDesc').html(sellerInfo.reputationDesc);
                    $('#J_collectGoods').html(sellerInfo.collectGoods);
                    $('#J_collectShop').html(sellerInfo.collectShop);
                    $('#J_everGo').html(sellerInfo.everGo);
                    $('#J_score').html(sellerInfo.score);
                    $('#J_tradeScore').html((sellerInfo.tradeScore == null || sellerInfo.tradeScore == '') ? '--' : sellerInfo.tradeScore);
                    $('#J_bonusScore').html((sellerInfo.bonusScore == null || sellerInfo.bonusScore == '') ? '--' : sellerInfo.bonusScore);
                    $('#J_basicScoreLast').html((sellerInfo.basicScoreLast == null || sellerInfo.basicScoreLast == '') ? '--' : sellerInfo.basicScoreLast);
                    $('#J_explainTips').html((sellerInfo.explainTips == null || sellerInfo.explainTips == '') ? '--' : sellerInfo.explainTips);
                    $('#J_qqhBonusScore').html((sellerInfo.qqhBonusScore == null || sellerInfo.qqhBonusScore == '') ? '--' : sellerInfo.qqhBonusScore);
                    $('#J_buyMoneyConvertCur').html((sellerInfo.buyMoneyConvertCur == null || sellerInfo.buyMoneyConvertCur == '') ? '--' : sellerInfo.buyMoneyConvertCur);
                    $('#J_buyMoneyConvertLast').html((sellerInfo.buyMoneyConvertLast == null || sellerInfo.buyMoneyConvertLast == '') ? '--' : sellerInfo.buyMoneyConvertLast);
                    $('#J_tradeBonusScore').html((sellerInfo.tradeBonusScore == null || sellerInfo.tradeBonusScore == '') ? '--' : sellerInfo.tradeBonusScore);
                    $('#J_interactBonusScoreLast').html((sellerInfo.interactBonusScoreLast == null || sellerInfo.interactBonusScoreLast == '') ? '--' : sellerInfo.interactBonusScoreLast);
                    $('#J_buyerFlag').val(sellerInfo.buyerFlag);
                    var picUrl = sellerInfo.honorPicUrl;
                    if (picUrl == null || picUrl == '') {
                        $('#J_honorPic').attr('src', '');
                        $('#J_honorPic1').attr('src', '');
                    } else {
                        if (picUrl.indexOf("https") != -1) {
                            picUrl = picUrl.substr(6);
                            $('#J_honorPic').attr('src', picUrl);
                            $('#J_honorPic1').attr('src', picUrl);
                        } else {
                            $('#J_honorPic').attr('src', picUrl);
                            $('#J_honorPic1').attr('src', picUrl);
                        }
                    }
                    if (sellerInfo.shiMing == null || sellerInfo.shiMing == '' && sellerInfo.shiMing.indexOf("实名认证") == -1) {
                        $('#J_zfbno').show();
                        $('#J_zbfdesc').hide();
                    } else {
                        $('#J_zfbno').hide();
                        $('#J_zbfdesc').show();
                    }
                    //if (sellerInfo.vipUrl == null || sellerInfo.vipUrl==''){
                    //    $('#J_vipDesc').html("无");
                    //}else{
                    //    $('#J_vipUrl').attr('src', sellerInfo.vipUrl);
                    //    fixPic("J_vipUrl");
                    //}
                    $('#J_honorPoint').html(sellerInfo.honorPoint);
                    $('#J_goodRate').html((sellerInfo.goodRate == null || sellerInfo.goodRate == '') ? '0.00%' : sellerInfo.goodRate);

                    if (sellerInfo.deleteCommentNum != null && sellerInfo.deleteCommentNum != '' && parseInt(sellerInfo.deleteCommentNum) > 0) {
                        $('#J_deleteNum').html("【" + sellerInfo.deleteCommentNum + "】")
                    } else {
                        $('#J_deleteNum').html("")
                    }

                    var gradeData = [];
                    $.each(sellerInfo.dimensionsVO, function (i, n) {
                        gradeData[i] = parseInt(n.value * 100);
                    });

                    $('#buyedGoodsTable').html('');
                    if (sellerInfo.buyedGoodsList != null) {
                        var buyerGoodsRowDatas = [];
                        $.each(sellerInfo.buyedGoodsList, function (i, n) {
                            var rowData = {};
                            var row = [];
                            row[0] = i + 1;
                            row[1] = (n.createTime == null || n.createTime == '') ? '' : n.createTime;
                            row[2] = '<img style="width:20%;" src="' + n.pic + '"/>';
                            row[3] = '<a class="sit-preview" onclick="gotourl(this)" name="' + n.url + '"> <font color="blue">' + n.goodsTitle + '</font></a>';
                            row[4] = (n.seller == null || n.seller == '') ? '' : n.seller;
                            row[5] = (n.num == null || n.num == '') ? '' : n.num;
                            row[6] = (n.price == null || n.price == '') ? '' : n.price;
                            row[7] = (n.status == null || n.status == '') ? '' : n.status;
                            rowData.data = row;
                            buyerGoodsRowDatas[i] = rowData;
                        });
                        initTable(buyerGoodsRowDatas);
                    } else {
                        $('#buyedGoodsTable').html('<span style="font-size: 20px;margin 30%;text-align: center;display:block;">暂无数据!</span>');
                    }

                    $('#cartGoodsTable').html('');
                    if (sellerInfo.cartGoodsList != null) {
                        var cartGoodsRowDatas = [];
                        $.each(sellerInfo.cartGoodsList, function (i, n) {
                            var rowData1 = {};
                            var row1 = [];
                            row1[0] = i + 1;
                            row1[1] = "--";
                            row1[2] = '<img style="width:20%;" src="' + n.pic + '"/>';
                            row1[3] = '<a class="sit-preview" onclick="gotourl(this)" name="' + n.url + '"> <font color="blue">' + n.goodsTitle + '</font></a>';
                            row1[4] = (n.seller == null || n.seller == '') ? '' : n.seller;
                            row1[5] = (n.num == null || n.num == '') ? '' : n.num;
                            row1[6] = (n.price == null || n.price == '') ? '' : n.price;
                            row1[7] = (n.status == null || n.status == '') ? '' : n.status;
                            rowData1.data = row1;
                            cartGoodsRowDatas[i] = rowData1;
                        });
                        initCartTable(cartGoodsRowDatas);
                    } else {
                        $('#cartGoodsTable').html('<span style="font-size: 20px;margin 30%;text-align: center;display:block;">暂无数据!</span>');
                    }

                    $('#refundGoodsTable').html('');
                    if (sellerInfo.refundGoodsList != null) {
                        var refundGoodsRowDatas = [];
                        $.each(sellerInfo.refundGoodsList, function (i, n) {
                            var rowData4 = {};
                            var row4 = [];
                            row4[0] = i + 1;
                            row4[1] = (n.pic == null || n.pic == '') ? '' : '<img style="width:20%;" src="' + n.pic + '"/>';
                            row4[2] = n.goodsTitle;
                            row4[3] = n.seller;
                            row4[4] = n.num;
                            row4[5] = (n.category == null || n.category == '') ? '' : n.category;
                            row4[6] = (n.price == null || n.price == '') ? '' : n.price;
                            row4[7] = n.status;
                            rowData4.data = row4;
                            refundGoodsRowDatas[i] = rowData4;
                        });
                        initRefundTable(refundGoodsRowDatas);
                    } else {
                        $('#refundGoodsTable').html('<span style="font-size: 20px;margin 30%;text-align: center;display:block;">暂无数据!</span>');
                    }

                    $('#J_tag1').show();
                    $.each(sellerInfo.buyerFlag, function (i, n) {
                        var row = $("#J_tag1").clone();
                        row.find("#J_tag").text(n);
                        row.appendTo("#J_tagDatas");//添加到模板的容器中
                    });
                    $('#J_tag1').hide();

                    $('#comment2Other').html('');
                    if (sellerInfo.comment2Ohter != null) {
                        var comment2OhterDatas = [];
                        $.each(sellerInfo.comment2Ohter, function (i, n) {
                            var rowData2 = {};
                            var deleteDesc = getDeleteComment(n.deleteReason);
                            var row2 = [];
                            var comment = (n.comment == null || n.comment == '') ? '' : n.comment;
                            row2[0] = i + 1;
                            row2[1] = (n.date == null || n.date == '') ? '' : n.date;
                            row2[2] = (n.goodsTitle == null || n.goodsTitle == '') ? '' : n.goodsTitle;
                            row2[3] = (n.seller == null || n.seller == '') ? '' : n.seller;
                            row2[4] = (n.price == null || n.price == '') ? '' : n.price;
                            row2[5] = n.delete ? '<span style="color: red;">【' + n.deleteReason + '' + deleteDesc + '】</span><span>"' + comment + '"</span>' : comment;
                            row2[6] = (n.title == null || n.title == '') ? '' : n.title;
                            rowData2.data = row2;
                            comment2OhterDatas[i] = rowData2;
                        });
                        initComment2OtherTable(comment2OhterDatas);
                    } else {
                        $('#comment2Other').html('<span style="font-size: 20px;margin 30%;text-align: center;display:block;">暂无数据!</span>');
                    }

                    $('#other2you').html('');
                    if (sellerInfo.other2You != null) {
                        var other2youDatas = [];
                        $.each(sellerInfo.other2You, function (i, n) {
                            var rowData3 = {};
                            var row3 = [];
                            row3[0] = i + 1;
                            row3[1] = (n.date == null || n.date == '') ? '' : n.date;
                            row3[2] = (n.goodsTitle == null || n.goodsTitle == '') ? '' : n.goodsTitle;
                            row3[3] = (n.seller == null || n.seller == '') ? '' : n.seller;
                            row3[4] = (n.price == null || n.price == '') ? '' : n.price;
                            row3[5] = (n.comment == null || n.comment == '') ? '' : n.comment;
                            row3[6] = (n.title == null || n.title == '') ? '' : n.title;
                            rowData3.data = row3;
                            other2youDatas[i] = rowData3;
                        });
                        initOther2YouTable(other2youDatas);
                    } else {
                        $('#other2you').html('<span style="font-size: 20px;margin 30%;text-align: center;display:block;">暂无数据!</span>');
                    }

                    $('#J_result').hide();

                    if (data.point != undefined && data.point != 0) {
                        layer.msg('本次使用积分:-' + data.point, {
                            time: 2000, //20s后自动关闭
                            //btn: ['明白了', '知道了', '哦']
                        });
                    }
                }
            }
        },
        error: function (data) {
            layer.closeAll();
            if (data.error_message != undefined) {
                var message = data.error_message;
                layer.open({
                    title: '提示'
                    , content: data.error_messag
                });
            } else {
                layer.open({
                    title: '提示'
                    , content: "系统异常了,请重试!"
                });
            }
        }
    });
}
function isScroll(){
    document.getElementsByClassName('Hui-article-box')[0].style.overflow= "scroll";
}
function disScrollX() {
    document.getElementsByClassName('Hui-article-box')[0].style.overflow= "hidden";
}
function demoBuyList(e, left) {
    showMask();
    disScrollX();
    var x, y;
    x = e.clientX;
    y = e.clientY;
    document.getElementById("J_buyedGoods").style.left = 54 + '%';
    document.getElementById("J_buyedGoods").style.transform = 'translateX(-50%)';
    document.getElementById("J_buyedGoods").style.top = '20%';
    document.getElementById("J_buyedGoods").style.display = "";
}
function demoCartList(e, left) {

    showMask();
    disScrollX();
    var x, y;
    x = e.clientX;
    y = e.clientY;
    document.getElementById("J_cartGoods").style.left = 54 + '%';
    document.getElementById("J_cartGoods").style.transform = 'translateX(-50%)';
    document.getElementById("J_cartGoods").style.top = '20%';
    document.getElementById("J_cartGoods").style.display = "";
}

function refundList(e, left) {

    showMask();
    disScrollX();
    var x, y;
    x = e.clientX;
    y = e.clientY;
    document.getElementById("J_refundGoods").style.left = 54 + '%';
    document.getElementById("J_refundGoods").style.transform = 'translateX(-50%)';
    document.getElementById("J_refundGoods").style.top = '20%';
    document.getElementById("J_refundGoods").style.display = "";
}

function comment2OhterList(e, left) {

    showMask();
    disScrollX();
    var x, y;
    x = e.clientX;
    y = e.clientY;
    document.getElementById("J_comment2Other").style.left = 54 + '%';
    document.getElementById("J_comment2Other").style.transform = 'translateX(-50%)';
    document.getElementById("J_comment2Other").style.top = '20%';
    document.getElementById("J_comment2Other").style.display = "";
}

function other2youList(e, left) {

    showMask();
    disScrollX();
    var x, y;
    x = e.clientX;
    y = e.clientY;
    document.getElementById("J_other2You").style.left = 54 + '%';
    document.getElementById("J_other2You").style.transform = 'translateX(-50%)';
    document.getElementById("J_other2You").style.top = '20%';
    document.getElementById("J_other2You").style.display = "";
}

function demoTagList(e, left) {

    showMask();
    disScrollX();
    var x, y;
    x = e.clientX;
    y = e.clientY;
    document.getElementById("J_userTag").style.left = 54 + '%';
    document.getElementById("J_userTag").style.transform = 'translateX(-50%)';
    document.getElementById("J_userTag").style.top = '25%';
    document.getElementById("J_userTag").style.display = "";
}


function inOutOfLine(e, left) {

    var inNum = $('#J_inNumId').text();
    if (inNum == 0) {
        layer.msg('无违规数据!', {
            time: 2000, //20s后自动关闭
            //btn: ['明白了', '知道了', '哦']
        });
    } else {

        showMask();
        disScrollX();
        var x, y;
        x = e.clientX;
        y = e.clientY;
        document.getElementById("J_inOutOfLine").style.left = 54 + '%';
        document.getElementById("J_inOutOfLine").style.transform = 'translateX(-50%)';
        document.getElementById("J_inOutOfLine").style.top = '25%';
        document.getElementById("J_inOutOfLine").style.display = "";
    }

}


function outOutOfLine(e, left) {

    var outNum = $('#J_outNumId').text();
    if (outNum == 0) {
        layer.msg('无违规数据!', {
            time: 2000, //20s后自动关闭
            //btn: ['明白了', '知道了', '哦']
        });
    } else {
        showMask();
        disScrollX();
        var x, y;
        x = e.clientX;
        y = e.clientY;
        document.getElementById("J_outOutOfLine").style.left = 54 + '%';
        document.getElementById("J_outOutOfLine").style.transform = 'translateX(-50%)';
        document.getElementById("J_outOutOfLine").style.top = '25%';
        document.getElementById("J_outOutOfLine").style.display = "";
    }
}

function initTable(rowData) {
    // 准备数据
    var data = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {width: 150, text: '操作日期', type: 'string', flex: true, colClass: 'text-center'},
            {width: 150, text: '商品图片', type: 'string', flex: true, colClass: 'text-center '},
            {width: 200, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品数量', type: 'number', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品价格', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '交易状态', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: rowData
    };
    // 初始化数据表格，empty方法作用是清空占位内容
    $('#buyedGoodsTable').datatable('load', data);
}
function initCartTable(rowData) {
    // 准备数据
    var data = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {width: 150, text: '操作日期', type: 'string', flex: true, colClass: 'text-center'},
            {width: 150, text: '商品图片', type: 'string', flex: true, colClass: 'text-center '},
            {width: 250, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品数量', type: 'number', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品价格', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '交易状态', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: rowData
    };

    // 初始化数据表格，empty方法作用是清空占位内容
    $('#cartGoodsTable').datatable('load', data);
}

function initRefundTable(rowData) {
    // 准备数据
    var data = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {sort: false, width: 150, text: '商品图片', type: 'string', flex: true, colClass: 'text-center '},
            {width: 250, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '商品数量', type: 'number', flex: true, colClass: 'text-center'},
            {width: 150, text: '商品规格', type: 'string', flex: true, colClass: 'text-center '},
            {width: 80, text: '商品价格', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '交易状态', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: rowData
    };

    // 初始化数据表格，empty方法作用是清空占位内容
    $('#refundGoodsTable').datatable('load', data);
}

function initComment2OtherTable(rowData) {
    // 准备数据
    var data = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {sort: false, width: 200, text: '日期', type: 'string', flex: true, colClass: 'text-center '},
            {width: 250, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '价格', type: 'number', flex: true, colClass: 'text-center'},
            {width: 300, text: '评论', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '评价结果', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: rowData
    };
    // 初始化数据表格，empty方法作用是清空占位内容
    $('#comment2Other').datatable('load', data);
}
function initOther2YouTable(rowData) {
    // 准备数据
    var data = {
        cols: [ // 定义列
            {sort: 'down', width: 50, text: '序号', type: 'number', flex: true, colClass: 'text-center'},
            {sort: false, width: 200, text: '日期', type: 'string', flex: true, colClass: 'text-center '},
            {width: 250, text: '商品名称', type: 'string', flex: true, colClass: ''},
            {width: 80, text: '店铺', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '价格', type: 'number', flex: true, colClass: 'text-center'},
            {width: 300, text: '评论', type: 'string', flex: true, colClass: 'text-center'},
            {width: 80, text: '评价结果', type: 'string', flex: true, colClass: 'text-center '},
        ],
        rows: rowData
    };

    // 初始化数据表格，empty方法作用是清空占位内容
    $('#other2you').datatable('load', data);
}

function showMask() {
    //J_body
    $("#J_body").css("height", $(document).height() - 100).css("overflow", "hidden");
    $("#mask").css("height", $(document).height() * 4);
    $("#mask").css("width", $(document).width());
    $("#mask").show();
}
//隐藏遮罩层
function hideMask() {

    $("#mask").hide();
}
//评价已删除
function getDeleteComment(reason) {
    if (reason != null && reason != '') {
        var temp = reason;
        if (temp.indexOf("已删除") != -1) {
            return '';
        } else {
            return ",评价已删除";
        }
    }
    return '';
}
function imgdis(el){
    $(el).off('click');
}
// function fixPic(name) {

//     //$("#"+name).css("width",0);
//     //$("#"+name).css("height",0);

//     var screenImage = $("#" + name);
//     var theImage = new Image();
//     theImage.src = screenImage.attr("src");


//     theImage.onload = function () {
//         $("#" + name).css("width", theImage.naturalWidth);
//         $("#" + name).css("height", theImage.naturalHeight);
//         var imageWidth = theImage.naturalWidth;
//         var imageHeight = theImage.naturalHeight;
    
//     };
// }




