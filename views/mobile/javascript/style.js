 $(document).ready(function(){
    //点击显示，点击隐藏
      $(".a_gd").click(function(){
      $(".s_nav").toggle();
      });
      /*商家详情切换*/
      $(".home_top .top_title .sj_de").click(function(){
      	$(".home_deal").show();
      	$(".home_goods").hide();
      	$(this).addClass("on");
      	$(".sj_good").removeClass("on");
      });
      $(".home_top .top_title .sj_good").click(function(){
      	$(".home_deal").hide();
      	$(".home_goods").show();
      	$(this).addClass("on");
      	$(".sj_de").removeClass("on");
      });

});