/*管理员-增加*/
	function admin_add(title,url,w,h){
		layer_show(title,url,w,h);
	}

	/*管理员-编辑*/
	function admin_edit(title,url,id,w,h){
		layer_show(title,url,w,h);
	}

	/*管理员-删除*/
	function admin_del(obj,id){
		layer.confirm('确认要删除吗？',{
			  btn: ['确定','取消'] //按钮
			},function(index){
				layer.close(index);
		        $.post(url1, {id:id}, function(data) {
		        	if (1 == data.msg){
		    			$(obj).parents("tr").remove();
		    			layer.msg(data.info,{icon:1,time:1000});
		        	} else {
		        		layer.msg(data.info,{icon:2,time:1000});
		        	}
		        },'json');
			},function(index){
				layer.close(index);
			}
		);
	}



	//禁用/启用刷单员
    function admin_op(obj,id,type){
        layer.confirm('确认此操作？',{
                btn: ['确定','取消'] //按钮
            },function(index){
                layer.close(index);
                $.post(url2, {id:id,type:type}, function(data) {
					if(data.msg == 1){
	                    layer.msg(data.info,{icon: 1,time:1000});
	                    setTimeout(function () {
	                        window.location.reload();
	                    },1000);
					}
                },'json');
            },function(index){
                layer.close(index);
            }
        );
    }

	//审核
	function admin_shenhe(id){
		if(roleval != 1){
	    	layer.msg('无权限',{icon:2,time:1000});
			return false;
		}
		layer.confirm('确认审核通过吗？',{
                btn: ['通过','不通过','取消'] //按钮
            },function(index){
                layer.close(index);
                $.post(url3, {id:id,type:1}, function(data) {
					if(data.msg == 1){
                        layer.msg(data.info,{icon: 1,time:1000});
                        setTimeout(function () {
                            window.location.reload();
                        },1000);
					}else{
                        layer.msg(data.info,{icon: 5,time:1000});
                        setTimeout(function () {
                            window.location.reload();
                        },1000);
					}

                },'json');
            },function(index){
                layer.close(index);
                $.post(url3, {id:id,type:2}, function(data) {
                    if(data.msg == 1){
                        layer.msg(data.info,{icon: 5,time:1000});
                        setTimeout(function () {
                            window.location.reload();
                        },1000);
                    }else{
                        layer.msg(data.info,{icon: 5,time:1000});
                        setTimeout(function () {
                            window.location.reload();
                        },1000);
                    }
                },'json');
            },function(index){

            }
        );
	}
	/*批量删除*/
	function datadel(){
		var lenght = $("td input[type='checkbox']:checked").length;//勾选个数
		if (lenght > 0){
			var arr=[];
		       $("td input[type='checkbox']:checked").each(function(){
		          	if(!isNaN(this.value)){
		          		arr.push(this.value);
		          	}
		       });

	       	   $.post(url4, {arr:arr},function(data){
	       		    if (1 == data.msg){
	       		    	layer.msg(data.info,{icon:1,time:1000}, function(){window.location.reload();});
	       		    } else {
	       		    	layer.msg(data.info,{icon:2,time:1000});
	       		    }
	           });
		} else {
			layer.alert('请勾选数据');
		}
	}