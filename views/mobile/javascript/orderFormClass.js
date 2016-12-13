/**
 * 订单对象
 * address:收货地址; delivery:配送方式; payment:支付方式;
 */
$(function(){
    //确认订单页面地址有无的切换
    $(".con_order .no_adderss").click(function(){

        $(".show_adderss").css({'display':'block'});
        $(".no_adderss").css({'display':'none'}); 
    });
    $(".con_order .show_adderss").click(function(){
        $(".show_adderss").css({'display':'none'});
        $(".no_adderss").css({'display':'block'}); 
    });
      //确认订单页面地址有无的切换end
    //
    $(".cart_2_bj em").click(function(){
    	if ($(this).hasClass("on")) {
    		$(this).removeClass("on").addClass("no");
    	}else{
    		$(this).removeClass("no").addClass("on");
    	}
    });
});

