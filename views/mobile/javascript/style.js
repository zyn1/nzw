 $(document).ready(function(){
    //点击显示，点击隐藏
      $(".a_gd").click(function(){
      $(".s_nav").toggle();
      });
      /*购物车选择 end*/
      //发票类型选择
      $(".invoic_top .top_title .sj_de").click(function(){
        $(".invoic_zp").hide();
        $(this).addClass("on");
        $(".invoic_top .sj_good").removeClass("on");
        $('div.invoic_center').find('input[type=hidden][name=fapiao_type]').val(0);
      });
      $(".invoic_top .top_title .sj_good").click(function(){
        $(".invoic_zp").show();
        $(this).addClass("on");
        $(".invoic_top .sj_de").removeClass("on");
        $('div.invoic_center').find('input[type=hidden][name=fapiao_type]').val(1);
      });
//修改placeholder字体颜色
 $('[placeholder]').focus(function() { 
    var input = $(this); 
    if (input.val() == input.attr('placeholder')) { 
    input.val(''); 
    input.removeClass('placeholder'); 
    } 
    }).blur(function() { 
    var input = $(this); 
    if (input.val() == '' || input.val() == input.attr('placeholder')) { 
    input.addClass('placeholder'); 
    input.val(input.attr('placeholder')); 
    } 
    }).blur(); 
});