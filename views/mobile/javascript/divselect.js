
$(function(){

	$("#dropdown p").click(function(){
		var ul = $("#dropdown ul");
		if(ul.css("display")=="none"){
			ul.slideDown("fast");
		}else{
			ul.slideUp("fast");
		}
	});
	
	$("#dropdown ul li a").click(function(){
		var txt = $(this).text();
		$("#dropdown p").html(txt);
		var value = $(this).attr("rel");
		$("#dropdown ul").hide();
		/*$("#result").html("您选择了"+txt+"，值为："+value);*/
	});
	

});
