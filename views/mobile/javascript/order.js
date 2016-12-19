// 点击打开关闭支付方式，配送方式，提交按钮
$(document).ready(function(){
  $("#mask,#type_close,#type_ok,#pas_xq,#ad_qx").click(function(){
    var peis=$(".peis");
    var mask=$(".mask");
    var zf_way=$(".zf_way")
    var submit_ok=$(".submit_ok")
    var w_paswd=$(".w_paswd")
    var addre_qr_del=$(".addre_qr_del"); 
    peis.animate({bottom: '-2.7rem'},"slow");
    zf_way.animate({bottom: '-2.7rem'},"slow");
    submit_ok.animate({bottom: '-4.2rem'},"slow");
    w_paswd.animate({bottom: '-2.7rem'},"slow");
    addre_qr_del.animate({bottom: '-1.7rem'},"slow");
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
  /*shi*/
      $(".input_li .but_del").click(function(){
        var addre_qr_del=$(".addre_qr_del");
        var mask=$(".mask");
        addre_qr_del.animate({bottom: '1rem'},"slow");
        mask.fadeIn(300);
      })
  //选择状态
  $(".ps_select ul li .g_checkbox").click(function(){
    $(".ps_select ul li .g_checkbox").removeClass("check");
    $(this).addClass("check")
  });
  // 编辑地址默认
      $(".default em").click(function(){
       if($(this).hasClass("on")){
        $(this).removeClass("on").addClass("no");
        $('input[name=is_default]').val(0);
       }else{
        $(this).removeClass("no").addClass("on");
        $('input[name=is_default]').val(1);
       }
      });
      

});



