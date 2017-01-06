/**
 * 订单对象
 * address:收货地址; delivery:配送方式; payment:支付方式;
 */
function orderFormClass()
{
    _self = this;

    //商家信息
    this.seller = null;

    //默认数据
    this.deliveryId   = 0;

    //免运费的商家ID
    this.freeFreight  = [];

    //订单各项数据
    this.orderAmount  = 0;//订单金额
    this.goodsSum     = 0;//商品金额
    this.deliveryPrice= 0;//运费金额
    this.paymentPrice = 0;//支付金额
    this.taxPrice     = 0;//税金
    this.protectPrice = 0;//保价
    this.ticketPrice  = 0;//代金券

    /**
     * 算账
     */
    this.doAccount = function(abs)
    {
        if(abs)
        {
            if(abs == 'protected')
            {
                if ($('.cart_2_bj em').hasClass("on")) {
                    $('.cart_2_bj em').removeClass("on").addClass("no");
                    $('.cart_2_bj input:hidden').val(0);
                }else{
                    $('.cart_2_bj em').removeClass("no").addClass("on");
                    $('.cart_2_bj input:hidden').val( $('.cart_2_bj input:hidden').attr('js_data'));
                }
            }
        }
        
        //税金
        this.taxPrice = $('input:hidden[name="taxes"]').val();
        this.protectPrice = $('input:hidden[name="if_protected"]').val();
        //最终金额
        this.orderAmount = parseFloat(this.goodsSum) - parseFloat(this.ticketPrice) + parseFloat(this.deliveryPrice) + parseFloat(this.paymentPrice) + parseFloat(this.taxPrice) + parseFloat(this.protectPrice);

        this.orderAmount = this.orderAmount <=0 ? 0 : this.orderAmount.toFixed(2);

        //刷新DOM数据
        $('#final_sum').html(this.orderAmount);
        $('[name="ticket_value"]').html(this.ticketPrice);
        $('#delivery_fee_show').html(this.deliveryPrice);
        $('#protect_price_value').html(this.protectPrice);
        $('#payment_value').html(this.paymentPrice);
        $('#tax_fee').html(this.taxPrice);
    }
    
    //根据省份地区ajax获取配送方式和运费
    this.getDelivery = function(province)
    {
        //整合当前的商品信息
        var goodsId   = [];
        var productId = [];
        var num       = [];
        $('[id^="deliveryFeeBox_"]').each(function(i)
        {
            var idValue = $(this).attr('id');
            var dataArray = idValue.split("_");

            goodsId.push(dataArray[1]);
            productId.push(dataArray[2]);
            num.push(dataArray[3]);
        });

        //获取配送信息和运费
        $.getJSON(creatUrl("block/order_delivery"),{"province":province,"goodsId":goodsId,"productId":productId,"num":num,"random":Math.random()},function(json){
            for(indexVal in json)
            {
                var content = json[indexVal];
                //正常可以送达
                if(content.if_delivery == 0)
                {
                    for(var tIndex in _self.freeFreight)
                    {
                        var sellerId  = _self.freeFreight[tIndex];
                        content.price = parseFloat(content.price) - parseFloat(content.seller_price[sellerId]);
                    }
                    $('input[type="radio"][name="delivery_id"][value="'+content.id+'"]').data("protectPrice",parseFloat(content.protect_price));
                    $('input[type="radio"][name="delivery_id"][value="'+content.id+'"]').data("deliveryPrice",parseFloat(content.price));
                    $('input[type="radio"][name="delivery_id"][value="'+content.id+'"]').data("is_save_price",parseFloat(content.is_save_price));
                    var deliveryHtml = template.render("deliveryTemplate",{"item":content});
                    $("#deliveryShow"+content.id).html(deliveryHtml);
                    $('input[type="radio"][name="delivery_id"][value="'+content.id+'"]').prop("disabled",false);
                }
                //配送方式不能配送
                else
                {
                    $('input[type="radio"][name="delivery_id"][value="'+content.id+'"]').prop("disabled",true);
                    $('input[type="radio"][name="delivery_id"][value="'+content.id+'"]').prop("checked",false);
                    $("#deliveryShow"+content.id).html("<span class='red'>您选择地区部分商品无法送达</span>");
                }
            }
            var checkVal = $('input[type="radio"][name="delivery_id"]:checked');
            if(checkVal.length > 0)
            {
                _self.deliverySelected(checkVal.val());
            }
            else if(_self.deliveryId > 0 && $('input[type="radio"][name="delivery_id"][value="'+_self.deliveryId+'"]').prop('disabled') != "disabled")
            {
                $('input[type="radio"][name="delivery_id"][value="'+_self.deliveryId+'"]').trigger('click');
                $('.js_delivery_data').text($('input[type="radio"][name="delivery_id"][value="'+_self.deliveryId+'"]').attr('js_data'))
            }
        });
    }

    /**
     * delivery初始化
     */
    this.deliveryInit = function(defaultDeliveryId)
    {
        this.deliveryId = defaultDeliveryId;
    }

    /**
     * delivery选中
     * @param int deliveryId 配送方式ID
     */
    this.deliverySelected = function(deliveryId)
    {
        var deliveryObj = $('input[type="radio"][name="delivery_id"][value="'+deliveryId+'"]');
        $('input[name=if_protected]').attr('js_data', deliveryObj.data("protectPrice") > 0 ? deliveryObj.data("protectPrice") : 0)
        this.protectPrice  = deliveryObj.data("protectPrice") > 0 ? deliveryObj.data("protectPrice") : 0;
        this.deliveryPrice = deliveryObj.data("deliveryPrice")> 0 ? deliveryObj.data("deliveryPrice"): 0;
        if(deliveryObj.data("is_save_price") == 0)
        {
            $('.js_if_protected').hide();
        }
        else
        {
            $('.js_if_protected').show();
        }

        //先发货后付款
        if(deliveryObj.attr('paytype') == '1')
        {
            $('input[type="radio"][name="payment"]').prop('checked',false);
            $('input[type="radio"][name="payment"]').prop('disabled',true);
            $('.js_payment_selected').hide("slow");

            //支付手续费清空
            this.paymentPrice = 0;
        }
        else
        {
            $('input[type="radio"][name="payment"]').prop('disabled',false);
            $('.js_payment_selected').show("slow");
        }
        _self.doAccount();
    }

    /**
     * payment初始化
     */
    this.paymentInit = function(defaultPaymentId)
    {
        if(defaultPaymentId > 0)
        {
            _self.paymentSelected(defaultPaymentId);
        }
    }

    /**
     * payment选择
     */
    this.paymentSelected = function(paymentId)
    {
        var paymentObj = $('input[type="radio"][name="payment"][value="'+paymentId+'"]');
        paymentObj.trigger('click');
        this.paymentPrice = paymentObj.attr("alt");
        $('.js_payment_data').text($('input[type="radio"][name="payment"][value="'+paymentId+'"]').attr('js_data'))
        this.doAccount();
    }

    /**
     * 检查表单是否可以提交
     */
    this.isSubmit = function()
    {
        var addressObj  = $('input[type="hidden"][name="radio_address"]').val();
        var deliveryObj = $('input[type="radio"][name="delivery_id"]:checked');
        var paymentObj  = $('input[type="radio"][name="payment"]:checked');

        if(addressObj == 0)
        {
            alert("请选择收件人地址");
            return false;
        }

        if(deliveryObj.length == 0)
        {
            alert("请选择配送方式");
            return false;
        }

        if(deliveryObj.attr('paytype') == 2 && $('input[name="takeself"]').length == 0)
        {
            alert("请选择配送方式中的自提点");
            return false;
        }

        if(paymentObj.length == 0 && deliveryObj.attr('paytype') != "1")
        {
            alert("请选择支付方式");
            return false;
        }
        $.ajax({
             type: "post",
             url: submitUrl,
             data: $('form.js_order_form').serialize(),
             dataType: "json",
             success: function(content){
                if(content.code == 0)
                {
                    alert(content.msg);
                }
                else
                {
                    var submit_ok=$(".submit_ok");
                    var mask=$("#js_mask");
                    submit_ok.animate({bottom: '0rem'},"slow");
                    mask.fadeIn(300);
                    $('.js_order_no').text(content.js_order_num);
                    $('.js_payment_info').text(content.js_payment);
                    $('.js_delivery_info').text(content.js_delevery);
                    $('.js_acce_info').text(content.accept_name);
                    $('.js_mobile_info').text(content.mobile);
                    $('.js_fapiao_info').text(content.js_fapiao_type);
                    if(content.js_deliveryType != 1 &&content.js_paymentType == 1)
                    {
                        $('.js_pay_button').show();
                        _url = _url.replace("@id@",content.js_order_id);
                        $('.js_pay_click').click(function(){
                            if(content.pay_type == 1)
                            {
                                var w_paswd=$(".w_paswd")
                                w_paswd.animate({bottom: '0rem'},"slow");
                                submit_ok.animate({bottom: '-4.2rem'},"slow");
                                $('.js_pay_confirm').click(function(){
                                    $.ajax({
                                         type: "post",
                                         url: $(this).attr('js_data'),
                                         data: {pay_pwd:$('input[name=pay_pwd]').val()},
                                         dataType: "json",
                                         success: function(data){
                                            if(data.result == false)
                                            {
                                                alert(data.msg);
                                                return;
                                            }
                                            window.location.href=_url;
                                         }
                                     });
                                })
                            }
                            else
                            {
                                window.location = _url;
                            }  
                        })
                    }
                    else
                    {
                        $('.js_pay_button').hide();
                    }
                }
             }
        });
    }
    
    /**
     * 代金券显示
     */
    this.ticketShow = function()
    {
        var sellerArray = [];
        for(var seller_id in this.seller)
        {
            sellerArray.push(seller_id);
        }

        art.dialog.open(creatUrl("block/ticket/sellerString/"+sellerArray.join("_")),{
            title:'选择代金券',
            okVal:'使用',
            ok:function(iframeWin, topWin)
            {
                //动态创建代金券节点
                _self.getForm().find("input[name='ticket_id[]']").remove();

                var formObject   = iframeWin.document.forms["ticketForm"];
                var resultTicket = 0;
                $(formObject).find("[name='ticket_id']:checked").each(function()
                {
                    var sid    = $(this).attr('seller');
                    var tprice = parseFloat($(this).attr('price'));

                    //专用代金券
                    if(_self.seller[sid] > 0)
                    {
                        resultTicket += (tprice >= _self.seller[sid]) ? _self.seller[sid] : tprice;
                    }
                    //通用代金券
                    else if(sid == '0')
                    {
                        var maxPrice = 0;
                        for(var sellerId in _self.seller)
                        {
                            if(_self.seller[sellerId] > maxPrice)
                            {
                                maxPrice = _self.seller[sellerId];
                            }
                        }
                        resultTicket += (tprice >= maxPrice) ? maxPrice : tprice;
                    }
                    //动态插入节点
                    _self.getForm().prepend("<input type='hidden' name='ticket_id[]' value='"+$(this).val()+"' />");
                });
                _self.ticketPrice = resultTicket;
                _self.doAccount();
            },
            "cancel":true,
            "cancelVal":"取消",
        });
    }

    //获取form表单
    this.getForm = function()
    {
        return $('form[name="order_form"]').length == 1 ? $('form[name="order_form"]') : $('form:first');
    }
}

