
        $(function() {
            //是否添加sku
            $("#form-article-add").on('change', '.keycon .select',function() {
                //切换按钮
                var html = '<div class="formControls addsku">' +
				'<button class="btn btn-success radius" type="button" onclick="addSku(this)">添加</button> ' +
                '<span class="cm-space">备注:</span> ' +
                    '<input type="text" name="goods_attr['+i+'][s][]" id="" placeholder="输入备注" value="" class="input-text" style="width:130px;" required oninvalid="setCustomValidity(\'请输入备注\');" oninput="setCustomValidity(\'\');"> ' +
                    '</div> ';
                if(this.value === 'yes') {
                    $(this).parents('.back').find(".row2 .sku").removeClass('sku-no').addClass('sku-yes').find('.price-input').before(html);
                } else {
                    $(this).parents('.back').find(".row2 .sku").removeClass('sku-yes').addClass('sku-no').children('.addsku').remove();
                    if($(this).parents('.back').find(".skumore").length > 0) {
                        $(this).parents('.back').find('.skumore:not(".skumore:first")').remove();
                    }
                }
            });
        })
        //创建元素
        function creatHtml(type) {
            //添加搜索关键词
            var skuType = type === 'sku' ? 'yes' : 'no';
            var htmlfront = '<div class="row cl skumore row2">' +
                '<label class="form-label col-xs-3 col-sm-3"></label>' +
                '<div class="formControls col-xs-9 col-sm-9 sku sku-' + skuType + '">' +
                '<div class="formControls addsku">' +
                '<button class="btn btn-danger radius" type="button" onclick="deleteSku(this)">删除</button> '+
                '<span class="cm-space">备注: </span> ' +
                '<input type="text" name="goods_attr['+i+'][s][]" id="" placeholder="输入备注" value="" class="input-text" style=" width:130px" required oninvalid="setCustomValidity(\'请输入备注\');" oninput="setCustomValidity(\'\');" > </div>'+
                ' <input type="number" name="goods_attr['+i+'][p][]" step="0.01" min="1" placeholder="价格" pattern="^[1-9]+(.[0-9]{1,2})?$" value="" class="input-text price-input" required oninvalid="setCustomValidity(\'请输入价格\');" oninput="setCustomValidity(\'\');" onfocusout="calCost(this)"> 元    ' +
                '<input type="number" name="goods_attr['+i+'][n][]" id="" step="1" min="1" placeholder="份数" value="" onkeyup="value=value.replace(/[^\\d]/g,\'\')" class="input-text num-input" required oninvalid="setCustomValidity(\'请输入份数\');" oninput="setCustomValidity(\'\');" onfocusout="calCost(this)"> 份   ' +
                '<span class="calcost"></span>';
            var htmllater = '</div></div>';
            if(type === 'sku') {
                //创建可删除sku块

                return htmlfront + htmllater;
            } else if(type === 'keywords') {
                //创建可删除长尾搜索关键词块
                i++;

                var htmlKeywords = '<div class="row cl keycon">' +
                    '<div class="col-xs-4 col-sm-2"></div>' +
                    '<div class="col-xs-8 col-sm-9 back">' +
                    '<span class="close-keyword" onclick="delKeywords(this)" title="删除"></span>' +
                    '<div class="row cl row1">' +
                    '<label class="form-label col-xs-3 col-sm-3" ">输入搜索关键词：</label>' +
                    '<div class="formControls col-xs-8 col-sm-6">' +
                    '<input type="text" name="goods_attr['+i+'][k]" id="" placeholder="" value="" class="input-text" required oninvalid="setCustomValidity(\'请输入搜索关键词\');" oninput="setCustomValidity(\'\');">' +
                    '</div>' +
                    '</div>' +
                    '<div class="row cl skumore row2">' +
                    '<label class="form-label col-xs-3 col-sm-3">'+
					'<label class="cm-label">增添备注</label>'+
					'<select name="sku[0]" class="select" style="width: 40px;">'+
					'<option value="yes">是</option>'+
					'<option value="no" selected="selected">否</option>'+
					'</select>'+
                    '</label>' +
                    '<div class="formControls col-xs-9 col-sm-9 sku sku-' + skuType + '">' +
                    '<input type="number" name="goods_attr['+i+'][p][]" id="" step="0.01" min="1" placeholder="价格" pattern="^[1-9]+(.[0-9]{1,2})?$" value="" class="input-text price-input" required oninvalid="setCustomValidity(\'请输入价格，最多两位小数\');" oninput="setCustomValidity(\'\');" onfocusout="calCost(this)"> 元    ' +
                    '<input type="number" name="goods_attr['+i+'][n][]" id="" step="1" min="1" placeholder="份数" value="" onkeyup="value=value.replace(/[^\\d]/g,\'\')" class="input-text num-input" required oninvalid="setCustomValidity(\'请输入份数\');" oninput="setCustomValidity(\'\');" onfocusout="calCost(this)"> 份   ' +
                    '<span class="calcost"></span>'+
                    '</div>' +
                    '</div>';

                return htmlKeywords;
            }
        }
        //添加长尾搜索关键词
        function addkeywords() {
            $(".form-con").append(creatHtml('keywords'));
        }
        //添加sku
        function addSku(el) {
            $(el).parents(".back").append(creatHtml('sku'));
        }
        //删除sku
        function deleteSku(el) {
            $(el).parents('.skumore').remove();
        }
        //删除长尾搜索关键词
        function delKeywords(el) {
            $(el).parents('.keycon').remove();
        }
        //计算费用
        function calCost(el){
        	if($(el).hasClass('price-input')){
        		var simple_price = $.trim($(el).val()); //单价
        		var sku_num = $.trim($(el).siblings('.num-input').val());
        	}else{
        		var simple_price = $.trim($(el).siblings('.price-input').val()); //单价
        		var sku_num = $.trim($(el).val());
        	}
        	var cost = getCost(simple_price);
        	$(el).siblings(".calcost").html("<font color='red'>本金：<span class='money'>"+simple_price*sku_num+"</span>元，服务费：<span class='money'>"+cost*sku_num+"</span>元")

        }
        function getCost(val){
        	var costval = 0;
        	if(val<100 && val>0){
				costval = 16;
        	}else if(val<200){
        		costval = 18;
        	}else if(val<300){
        		costval = 22;
        	}else if(val<400){
        		costval = 25;
        	}else{
        		costval = Math.floor(val*0.1);
        	}
        	return costval;
        }
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
         //提交表单
  		function productCheck() {
  			if(!isFile){
  				layer.msg('请上传主图',{icon:2,time:1000});
  				return false;
  			}
			var total = 0 ,num_total = 0;
			for(var i = 0; i < $(".sku").length; i++){
				var t_price = parseInt($(".sku").eq(i).find(".price-input").val());
				var t_cost = getCost(t_price);
				var t_num = parseInt($(".sku").eq(i).find(".num-input").val());
				total += (t_price+t_cost)*t_num;
				num_total += t_num;
			}
			var now_hour = new Date().getHours();
			if(now_hour >= 10 && now_hour < 12 && num_total > 50){
				layer.msg('每天10点至12点之间发布的任务，总份数不得大于50单');
	  			return false;
			}
  			var isFirst = '',isEnough = 0 ,isYuFu = 0;
  			$.ajax({
  				type:"post",
  				url:url2,
  				async:false,
  				data:{total:total},
  				success:function(data){
					isFirst = data.msg;
					 isEnough = data.info;
  				}
  			});
  			if(parseInt(isEnough) < 3000){
	  			if(isFirst == 0){
	  				if(total > 3000){
	  					layer.msg('初次发布任务，总费用不得超过3000元！');
	  					return false;
	  				}
	  			}
  			}else{
  				if(isFirst == 0){
  					if(parseInt(isEnough)<total){
	  					layer.msg('余额不足，请充值');
		  				return false;
	  				}
  				}
  			}
  			return true;
  		}