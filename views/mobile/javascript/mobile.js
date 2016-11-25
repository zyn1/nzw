htmlFontSize();
$(function(){
	// 设置网站基准 html fontsize
	htmlFontSize();
	$(window).resize(function(){
		htmlFontSize();
	});
	// 设置当前页面标题以及返回路径 开始
	var pageInfo = $("#pageInfo"),
		pageInfoTitle = pageInfo.data('title');
	if (pageInfoTitle) {
		$("#page_title").html(pageInfoTitle);
	};
});

// 跳转函数
function gourl(url){
	window.location.href = url;
}
// 设置基准 html fontsize 函数
function htmlFontSize(){
	var win = $(window),
		winH = win.height(),
		winW = win.width(),
		minSize;
	winW > winH ? minSize = winH : minSize = winW ;
	var hfs = ~~(minSize*100000/36)/10000+"px";
	$("html").css('font-size', hfs);
}
// 获取url参数函数
function getUrlParam(name){
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r!=null) return unescape(r[2]); return null;
}
// 隐藏底部导航
function hideNav(){
	$(".footer_nav").hide()
}


// 返回顶部JS
$(document).ready(function() {
    //首先将#back-to-top隐藏
    $("#back-to-top").hide();
    //当滚动条的位置处于距顶部100像素以下时，跳转链接出现，否则消失
    $(function() {
        $(window).scroll(function() {
            if ($(window).scrollTop() > 100) {
                $("#back-to-top").fadeIn(500);
            } else {
                $("#back-to-top").fadeOut(500);
            }
        });
        //当点击跳转链接后，回到页面顶部位置
        $("#back-to-top").click(function() {
            $('body,html').animate({
                scrollTop: 0
            },
            1000);
            return false;
        });
    });
});