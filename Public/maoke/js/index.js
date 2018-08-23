var t;
var n=0;
var count;
function showAuto() {
	n = n >= (count - 1) ? 0 : ++n;
	$(".ban>li").filter(":visible").fadeOut(500).parent().children().eq(n).fadeIn(1000);
}
//数字自增
var timerID1,timerID2,timerID3,timerID4;
function numAdd(obj,num,clearInt) {
    if (Number(obj.html()) >= num) {
        clearInterval(clearInt);
    }
    else{
        obj.html(Number(obj.html()) + 1);
	}
}
var m=1;
$(function(){
	//顶部导航
	var offset = $('.zs_nav').offset();
    $(window).scroll(function () {
        var scrollTop = $(window).scrollTop();
        if (offset.top < scrollTop) {
            $('.zs_nav').addClass('fixed-box');   
        } else {
            $('.zs_nav').removeClass('fixed-box'); 
        }
    });
	//导航点击效果
    $(".zs_menu").click(function () {
        $(this).siblings().removeClass("zs_menu_choosed");
        $(this).addClass("zs_menu_choosed");
    });
    //返回顶部
    $(".zs_left3").click(function(){
    	$(".sy").siblings().removeClass("zs_menu_choosed");
    	$(".sy").addClass("zs_menu_choosed");
    })
    //轮播
    count = $(".ban li").length;
	
	$(".ban>li:not(:first-child)").hide();
	t = setInterval("showAuto()", 3000);
    $("#banner").hover(function() {
        clearInterval(t);
    },
    function() {
        t = setInterval("showAuto()", 3000);
    }); 
    $(".ban-l").click(function(){
    	var index = $(".ban>li").filter(":visible").index()-1;
    	n =  index<0?(count - 1):index;
		$(".ban>li").filter(":visible").stop().fadeOut(500).parent().children().eq(n).fadeIn(1000);
    });
    $(".ban-r").click(function(){
    	var index = $(".ban>li").filter(":visible").index()+1;
    	n =  index>(count - 1)?0:index;
		$(".ban>li").filter(":visible").stop().fadeOut(500).parent().children().eq(n).fadeIn(1000);
    });
        
    //数字动画    
    $(document).scroll(function(){
		if($(document).scrollTop() >= 2650 && m==1){
			$("<link>").attr({ 
				rel: "stylesheet",
				type: "text/css",
				href: "Public/maoke/css/animate.css"
			}).appendTo("head");
			m=0;
			$(".animateNum1").html("0");
			$(".animateNum2").html("0");
			$(".animateNum3").html("0");
			$(".animateNum4").html("0");
            timerID1 = setInterval("numAdd($('.animateNum1'),120,timerID1)", 12);
            timerID2 = setInterval("numAdd($('.animateNum2'),48,timerID2)", 20);         
            timerID3 = setInterval("numAdd($('.animateNum3'),188,timerID3)", 8);   
            timerID4 = setInterval("numAdd($('.animateNum4'),258,timerID4)", 5);	
		}  	
	});
});
		