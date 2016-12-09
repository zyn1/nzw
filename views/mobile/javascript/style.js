 $(document).ready(function(){
    //点击显示，点击隐藏
      $(".a_gd").click(function(){
      $(".s_nav").toggle();
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
      $(".checkbox").click(function(){
        $(this).find("i").toggleClass("check");
        var all_boxi=$(this).parent().parent("ul").find("li .checkbox i");
        var sel_all_i=$(this).parent().parent().parent(".cart_goods").find(".cart_title .sel_all i");
        var all_checki=$(this).parent().parent("ul").find("li .checkbox .check");
        //.cart_list 下的class="box"下的i标签存在class="check"的值
        var box_all_checki= $(".cart_goods").find(".checkbox .check");
        //.cart_list 下的class="box"下的i标签
        var box_all_onchecki=$(".cart_goods").find(".checkbox i")
        //全选按钮
        var box_all_i = $(".box_all i");
        if(all_boxi.length == all_checki.length){
          sel_all_i.addClass('check');
        }else{
          sel_all_i.removeClass("check");
        }
        if(box_all_onchecki.length == box_all_checki.length ){
          box_all_i.addClass('check');
        }else{
          box_all_i.removeClass("check");
        }
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