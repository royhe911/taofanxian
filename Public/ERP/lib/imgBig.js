// 鼠标上移显示大图
	$('.cm-preview').mouseover(function (e) {
		//鼠标移上去 向body追加大图元素
		//大图的路径：当前连接的href属性值为大图的路径
		var $imgSrc = $(this).attr("src");
		var $maxImg = "<div id='show-max'><img src='" + $imgSrc + "'></div>";
		//在body中添加元素
		$("body").append($maxImg);
		//设置层的top和left坐标，并动画显示层
		$("#show-max").css("top", e.pageY -200).css("left", e.pageX + 10).show('slow');
	}).mouseout(function () {
		//鼠标移开删除大图所在的层
		$("#show-max").remove();
	}).mousemove(function (e) {
		//鼠标移动时改变大图所在的层的坐标
		$("#show-max").css("top", e.pageY -200).css("left", e.pageX + 10);
	});
$("td img").click(function(){
	var img_url = $(this).attr('src');
	layer.open({
	  type: 1,
	  title:'主图',
	  area: ['600px', '643px'], //宽高
	  content: '<img width="600" src="'+img_url+'">'
	});
})
