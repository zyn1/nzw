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
      /*购物车编辑和完成div切换*/
      $(".cart_edit .name .sel_cz_wc").click(function(){
            $(this).parent(".cart_sel").addClass("on")
             $(this).parent().parent().parent().parent(".cart_sel").find(".cart_goods").show();
             $(this).parent().parent().parent().parent(".cart_sel").find(".cart_edit").hide();
      })
      $(".cart_goods .name .sel_cz_bj").click(function(){
             $(this).parent().parent().parent().parent(".cart_sel").find(".cart_edit").show();
             $(this).parent().parent().parent().parent(".cart_sel").find(".cart_goods").hide();
      })
         /*购物车选择*/
      $(".box").click(function(){
        alert("dd")
        $(this).find("i").addClass("check");
      });

});
 $(document).ready(function(){
    /*购物车 数量加减*/
$("#add").click(function(){
  var n=$("#num").val();
  var num=parseInt(n)+1;
 if(num==0){alert("cc");}
  $("#num").val(num);
});
$("#jian").click(function(){
  var n=$("#num").val();
  var num=parseInt(n)-1;
 if(num==0){alert("不能为0!"); return}
  $("#num").val(num);
  });

});