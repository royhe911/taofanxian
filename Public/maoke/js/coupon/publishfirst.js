function checkCoupon() {
		var sid = $('#sid').val();//店铺ID
		var goodName = $("input[name=goods_name]").val(); //商品标题
		var classify = $("select[name=commodity_classify]").val(); //分类
		var goodImg = $("input[name=fileToUpload]").val(); //图片
		var goodValue = $.trim($("input[name=goods_value]").val()); //商品价值
		var couponValue = $.trim($("input[name=coupon_value]").val()); //优惠券价值
		var goodNum = $.trim($("input[name=goods_num]").val()); //份数
		var endTime = $.trim($("input[name=endtime]").val()); //结束时间
		if(goodName == '') {
			$("#goods_name_error").text('请填写商品标题');
			$("input[name=goods_name]").focus();
			return false;
		} else {
			$("#goods_name_error").text('');
		}
		if(classify == '') {
			$("#classify_error").text('请选择分类');
			$("select[name=commodity_classify]").focus();
			return false;
		} else {
			$("#classify_error").text('');
		}
		if(goodImg == '') {
			$("#picture_url_error").css({
				'display': 'inline-block',
				'color': '#f25f55',
				'font-size': '14px'
			}).text('请上传商品图片');
			return false;
		} else {
			$("#picture_url_error").text('');
		}
		if(goodValue == '') {
			$("#goods_value_error").text('请填写商品价值');
			$("input[name=goods_value]").focus();
			return false;
		} else if(goodValue <= 0) {
			$("#goods_value_error").text('商品价值必须大于0元');
			$("input[name=goods_value]").focus();
			return false;
		} else {
			$("#goods_value_error").text('');
		}
		if(couponValue == '') {
			$("#coupon_value_error").text('请填写优惠券价值');
			$("input[name=coupon_value]").focus();
			return false;
		} else if(couponValue <= 0) {
			$("#coupon_value_error").text('优惠券价值必须大于0元');
			$("input[name=coupon_value]").focus();
			return false;
		} else {
			$("#coupon_value_error").text('');
		}
		if(goodNum == '') {
			$("#goods_num_error").text('请填写发放份数');
			$("input[name=goods_num]").focus();
			return false;
		} else if(goodNum < 1) {
			$("#goods_num_error").text('发放份数至少1份');
			$("input[name=goods_num]").focus();
			return false;
		} else {
			$("#goods_num_error").text('');
		}
		var nowTime = new Date().getTime();
		if(endTime == '') {
			$("#endtime_error").text('请选择活动结束时间');
			return false;
		} else if(new Date(endTime) <= nowTime) {
			$("#endtime_error").text('活动结束时间必须大于当前时间');
			return false;
		} else {
			$("#endtime_error").text('');
		}
		return true;
	}

function showSearch(id){
    var oDiv=$("#"+id);
    var t=document.createElement("input");
    t.type="text";
    oDiv.prepend(t);
    t.focus();
    t.remove();
}