$(function(){
	//附属服务
	$("body").on('change','.service-all .service input',function(){
		if($(this).attr('checked')){
			$(this).parents(".service").addClass("unselect").removeClass('selected');
			$(this).attr('checked',false);
		}else{
			$(this).parents(".service").addClass("selected").removeClass('unselect');
			$(this).attr("checked",true);
		}
	})
//	$(".lift").change(function(){
//		if(this.value == 0){
//			var str = '<select name="empty_cost" class="select select-box addp-select">'+
//							'<option value="4">任务金额100元内4元</option>'+
//							'<option value="5">任务金额100-200元5元</option>'+
//							'<option value="6">任务金额200-300元6元</option>'+
//							'<option value="8">任务金额300-400元8元</option>'+
//							'<option value="10">任务金额400以上10元</option>'+
//						'</select>';
//		}else{
//			var str = '<input type="text" name="goods_zeng" class="input-text addp-select-input" placeholder="建议赠送抽纸或卷纸" required oninvalid="setCustomValidity(\'请输入赠品名称\');" oninput="setCustomValidity(\'\');" /> <font color="red">（提示：禁止赠送有争议的产品，比如易碎、易破、产品质量超级垃圾等。）</font>';
//		}
//		$(".k-bag").html(str);
//	})
	$(".lift input").change(function(){
		if(this.value == 0){
			var str = '<select name="empty_cost" class="select select-box addp-select">'+
							'<option value="4">任务金额100元内4元</option>'+
							'<option value="5">任务金额100-200元5元</option>'+
							'<option value="6">任务金额200-300元6元</option>'+
							'<option value="8">任务金额300-400元8元</option>'+
							'<option value="10">任务金额400以上10元</option>'+
						'</select>';
		}else{
			var str = '<input type="text" name="goods_zeng" class="input-text addp-select-input" placeholder="建议赠送抽纸或卷纸" required oninvalid="setCustomValidity(\'请输入赠品名称\');" oninput="setCustomValidity(\'\');" /> <font color="red">（提示：禁止赠送有争议的产品，比如易碎、易破、产品质量超级垃圾等。）</font>';
		}
		$(".k-bag").html(str);
	})
	document.getElementById('addpronew').onsubmit = function(){
			if(!isFile){
				layer.msg('请上传主图',{icon:2,time:1000});
				return false;
			}
			for(var i = 0; i < $(".back-new").length; i++){
				var t_price = parseInt($(".back-new").eq(i).find(".price-input").val());
				var t_cost = getCost(t_price);
				var t_num = parseInt($(".back-new").eq(i).find(".num-input").val());
				total += (t_price+t_cost)*t_num;
				num_total += t_num;
			}
			var now_hour = new Date().getHours();
			if(now_hour >= 10 && now_hour < 12 && num_total > 50){
				layer.msg('每天10点至12点之间发布的任务，总份数不得大于50单');
				return false;
			}
			return true;
		};
})

//添加关键词
function addkeywords(){
	i++;
	var $html = '<div class="back-new">'+
					'<div class="close-keyword" onclick="removekey(this)"></div>'+
					'<div class="row cl">'+
						'<label class="form-label fl addp-label"><span class="c-red">*</span>搜索关键词</label>'+
						'<div class="formControls fl">'+
							'<input type="text" class="input-text addp-select-input" required name="k['+i+']" oninvalid="setCustomValidity(\'请输入搜索关键词\');" oninput="setCustomValidity(\'\');"/>'+
						'</div>'+
						'<div class="fr good-num" style="margin-right: 40px;">'+
							'<label class="form-label fl addp-label" style="width: 56px;"><span class="c-red">*</span>份数</label>'+
							'<div class="formControls fl">'+
								'<input type="number"  step="1" min="1" class="input-text addp-select-input num-input" name="n['+i+'] style="width: 80px;" required oninvalid="setCustomValidity(\'请输入份数\');" oninput="setCustomValidity(\'\');" onfocusout="calCost(this)"/>'+
							'</div>'+
						'</div>'+
						'<div class="fr good-price">'+
							'<label class="form-label fl addp-label" style="width: 110px;"><span class="c-red">*</span>下单实际价格</label>'+
							'<div class="formControls fl">'+
								'<input type="text" step="0.01" min="0.01" name="p['+i+']" class="input-text addp-select-input price-input" style="width: 104px;" required pattern="^(([1-9][0-9]*)|(([0]\.\\d{1,2}|[1-9][0-9]*\.\\d{1,2})))$" oninvalid="setCustomValidity(\'请输入下单价,最多两位小数\');" oninput="setCustomValidity(\'\');" onfocusout="calCost(this)"/>'+
							'</div>'+
						'</div>'+
					'</div>'+
					'<div class="row cl">'+
						'<label class="form-label fl addp-label">附属服务</label>'+
						'<div class="fl service-all" style="width: 700px;">'+
							'<div class="formControls fl">'+
								'<span class="service service-m unselect">'+
									'<span class="ser-text text-c">需要聊天</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]" value="需要聊天"/>'+
								'</span>'+
							'</div>'+
							'<div class="formControls fl">'+
								'<span class="service service-m unselect">'+
									'<span class="ser-text text-c">货比三家</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]" value="货比三家"/>'+
								'</span>'+
							'</div>'+
							'<div class="formControls fl">'+
								'<span class="service service-m unselect">'+
									'<span class="ser-text text-c">加购物车</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]" value="加购物车"/>'+
								'</span>'+
							'</div>'+
							'<div class="formControls fl">'+
								'<span class="service service-m unselect">'+
									'<span class="ser-text text-c">收藏宝贝</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]" value="收藏宝贝"/>'+
								'</span>'+
							'</div>'+
							'<div class="formControls fl">'+
								'<span class="service service-m unselect">'+
									'<span class="ser-text text-c">关注店铺</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]" value="关注店铺"/>'+
								'</span>'+
							'</div>'+
							'<div class="formControls fl">'+
								'<span class="service service-l unselect">'+
									'<span class="ser-text text-c">浏览店内两个宝贝以上</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]"  value="浏览店内两个宝贝以上"/>'+
								'</span>'+
							'</div>'+
							'<div class="formControls fl">'+
								'<span class="service service-l unselect">'+
									'<span class="ser-text text-c">浏览详情到底5分钟以上</span>'+
									'<input type="checkbox" class="ser-item" name="s['+i+'][]" value="浏览详情到底5分钟以上"/>'+
								'</span>'+
							'</div>'+
						'</div>'+
					'</div>'+
					'<div class="row cl">'+
						'<label class="form-label fl addp-label">其他要求</label>'+
						'<div class="formControls fl">'+
							'<input type="text" class="input-text addp-input" name="order['+i+']" placeholder="备注" />'+
						'</div>'+
					'</div>'+
					'<div class="row cl cost-tip">'+
						'<label class="form-label fl addp-label"></label>'+
						'<div class="formControls fl">'+
							'<span class="tip"></span>'+
						'</div>'+
					'</div>'+
				'</div>';
	$(".form-con").append($html);
}
//删除关键词
function removekey(el){
	$(el).parent(".back-new").remove();
}
function getCost(val){
	var costval = 0;
	if(val<100 && val>0){
		costval = 15;
	}else if(val<200){
		costval = 17;
	}else if(val<300){
		costval = 21;
	}else if(val<400){
		costval = 24;
	}else if (val < 500) {
		costval = 29;
	}else{
		costval = Math.floor(val*0.1);
	}
	return costval;
}
function calCost(el){
	if($(el).hasClass('price-input')){
		var simple_price = $.trim($(el).val()); //单价
		var sku_num = $.trim($(el).parents('.good-price').siblings('.good-num').find('.num-input').val());
	}else{
		var simple_price = $.trim($(el).parents('.good-num').siblings('.good-price').find('.price-input').val()); //单价
		var sku_num = $.trim($(el).val());
	}
	var cost = getCost(simple_price);
	$(el).parents('.row').siblings(".cost-tip").find(".tip").html("<font color='red'>本金：<span class='money'>"+simple_price*sku_num+"</span>元，服务费：<span class='money'>"+cost*sku_num+"</span>元");

}
//上传主图
function uploadFile(el,file_id){
	if(el.value){isFile = true;}
    $.ajaxFileUpload({
        url:picUrl,//上传图片处理文件
        secureuri:false,
        fileElementId:file_id,
        dataType: 'text',
        success: function (data){
            var data= strToObj(data);
            if(1 == data.status){
				var goods_pic='upload/'+data.savepath+data.name;
				var goods_thumb=data.thumb;
                $('#img').val(goods_pic);
                $('#thumb').val(goods_thumb);
                $('#thumb-preview').attr('src', './upload/'+data.savepath+'/'+data.name);
            } else {
                layer.msg(data.msg,{icon:2,time:1000});
            }
        },
        error: function (data){
            layer.msg('上传失败!',{icon:2,time:1000});
        }
    });
}
function strToObj(str) {
    return eval("(" + str + ")");
}
function productCheck() {


}