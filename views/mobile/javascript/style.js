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
         /*购物车选择 start*/
      $("input[name^='checkbox']").click(function(){
         $(this).parent("label").toggleClass("check");
        var g_checkbox = $(".cart_goods ul li .g_checkbox")
        var check_g_checkbox = $(".cart_goods ul li .check")
        var sel_all_check = $(this).parent().parent().parent().parent("").find(".cart_title .s_sel_all")
        if(g_checkbox.length == check_g_checkbox.length){
          sel_all_check.addClass('check');
        }else{
          sel_all_check.removeClass('check');
        }
        /*if(box_all_onchecki.length == box_all_checki.length ){
          box_all_i.addClass('check');
        }else{
          box_all_i.removeClass("check");
        }*/
      });
      $(".sel_all").click(function(){
          $(this).find("i").toggleClass("check");
          var checkbox_i=$(this).parent().parent(".cart_goods").find("ul li .checkbox i")
          var sel_check_i=$(".cart_goods .cart_title").find(".sel_all .check");
          var sel_oncheck_i=$(".cart_goods .cart_title").find(".sel_all i");
          //全选按钮
          var box_all_i2 = $(".box_all i");
          if($(this).find("i").hasClass("check")){
            checkbox_i.addClass("check");
          }else{
            checkbox_i.removeClass("check");
          }
          if(sel_check_i.length == sel_oncheck_i.length){
            box_all_i2.addClass('check');
          }else{
            box_all_i2.removeClass("check");
          }
      })
      $(".box_all").click(function(){
        $(this).find("i").toggleClass("check");
        var all_box_checki=$(".cart_list").find(".box i")
        if($(this).find("i").hasClass("check")){
          all_box_checki.addClass("check");
        }else{
            all_box_checki.removeClass("check");
        }
      })
      /*购物车选择 end*/
      //发票类型选择
      $(".invoic_top .top_title .sj_de").click(function(){
        $(".invoic_zp").hide();
        $(this).addClass("on");
        $(".invoic_top .sj_good").removeClass("on");
      });
      $(".invoic_top .top_title .sj_good").click(function(){
        $(".invoic_zp").show();
        $(this).addClass("on");
        $(".invoic_top .sj_de").removeClass("on");
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