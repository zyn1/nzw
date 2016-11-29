// 点击打开关闭支付方式，配送方式，提交按钮
$(document).ready(function(){
  $("#mask,#type_close,#type_ok,#pas_xq").click(function(){
    var peis=$(".peis");
    var mask=$(".mask");
    var zf_way=$(".zf_way")
    var submit_ok=$(".submit_ok")
    var w_paswd=$(".w_paswd")
    peis.animate({bottom: '-2.7rem'},"slow");
    zf_way.animate({bottom: '-2.7rem'},"slow");
    submit_ok.animate({bottom: '-4.2rem'},"slow");
    w_paswd.animate({bottom: '-2.7rem'},"slow");
    mask.fadeOut(300);
  });
  //打开配送方式
  $("#psfs").click(function(){
    var peis=$(".peis");
    var mask=$(".mask");
    peis.animate({bottom: '0rem'},"slow");
    mask.fadeIn(300);
  });
  //打开支付方式
  $("#pay_way").click(function(){
    var zf_way=$(".zf_way");
    var mask=$(".mask");
    zf_way.animate({bottom: '0rem'},"slow");
    mask.fadeIn(300);
  });
  //确认提交按钮打开订单详情
  $(".cart_footer_fixed .sub_buy").click(function(){
    var submit_ok=$(".submit_ok");
    var mask=$(".mask");
    submit_ok.animate({bottom: '0rem'},"slow");
    mask.fadeIn(300);
    $("#ljzf").click(function(){
      var w_paswd=$(".w_paswd")
      var mask=$(".mask");
       w_paswd.animate({bottom: '0rem'},"slow");
       submit_ok.animate({bottom: '-4.2rem'},"slow");
       mask.fadeIn(300);
    })
  });
  //选择状态
  $(".ps_select ul li .box").click(function(){
    $(".ps_select ul li .box i").removeClass("check");
    $(this).find("i").addClass("check")
  });

});