// 点击打开关闭支付方式，配送方式，提交按钮
$(document).ready(function(){
  $("#mask,#type_close,#type_ok").click(function(){
    var peis=$(".peis");
    var mask=$(".mask");
    var zf_way=$(".zf_way")
    peis.animate({bottom: '-2.7rem'},"slow");
    zf_way.animate({bottom: '-2.7rem'},"slow");
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
  //选择状态
  $(".ps_select ul li .box").click(function(){
    $(".ps_select ul li .box i").removeClass("check");
    $(this).find("i").addClass("check")
  });
});