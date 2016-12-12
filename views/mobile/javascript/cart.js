$(function(){
    $("input[name=box_all]").click(function(){
        $(this).parent("label").toggleClass("check");
        if($(this).parent("label").hasClass("check")){
          $(".cart_list").find(".box").addClass("check");
        }else{
          $(".cart_list").find(".box").removeClass("check");
        }
        $(".cart_list").find("input[name^='sub']").prop('checked', $(this).parent("label").hasClass("check"));
            if(!this.checked) {
                $('.js_goods_list').find('input[type=checkbox]').removeAttr('checked');    
                $('#sum_price').text(0);    
            }else{
                var weight = origin_price = total_discount = promotion_price = sum_price = 0;
                $("input[name^='sub']").each(function(i){
                    var json = JSON.parse($(this).attr('data-json'));
                    var num = $('#js_data_'+json.goods_id+'_'+json.product_id).val();
                    origin_price +=mathMul(parseFloat(json.sell_price),num);
                    total_discount += mathMul(parseFloat(json.reduce),num);
                    
                })    
                $('#sum_price').text(mathSub(origin_price, total_discount,2));    
                    
            }
    })
    $('input[name^=sub]').click(function(){
        var $subs = $("input[name^='sub']");
        $(this).parent("label").toggleClass("check");
        var _li = $(this).closest('div.js_sel_goods').find('li.js_show_goods_det')
        ,_checked = $(this).closest('ul').find('label.check')
        ,_all = $(this).closest('div.js_sel_goods').find('label.s_sel_all');
        if(_li.length == _checked.length)
        {
          _all.addClass('check');
        }
        else
        {
          _all.removeClass('check');
        }
        if($('div.cart_sel').length == $('div.cart_title label.check').length)
        {
          $('label.box_all').addClass('check');
        }
        else
        {
          $('label.box_all').removeClass('check');
        }
        check_goods(this);
    })
  
    $("input[name=sel_all]").click(function(){
        $(this).parent("label").toggleClass("check");
        var _box = $(this).closest('div.js_sel_goods').find("ul .box")
            ,_sub = _box.find("input[name^='sub']");
        if($(this).parent('label').hasClass('check'))
        {
            _box.addClass("check");
        }
        else
        {
            _box.removeClass("check");
        }
        _sub.prop('checked', $(this).parent('label').hasClass('check'));
        _sub.each(function(){
            check_goods(this)
        })
        var _div = $(this).closest('section.cart_list').find('div.cart_sel')
        ,_checked = $(this).closest('section.cart_list').find('div.cart_title label.check')
        ,_all = $('label.box_all');
        if(_div.length == _checked.length)
        {
            _all.addClass('check');
        }
        else
        {
            _all.removeClass('check');
        }
    })
})
function check_goods(_this){
    var data = $(_this).attr('data-json');
    var dataObj = JSON.parse(data);
    var origin_price = parseFloat( $('#origin_price').val());
    var discount_price = parseFloat($('#discount_price').val());
    var new_count = parseInt($('#js_data_'+dataObj.goods_id+'_'+dataObj.product_id).val());                 
    var goods_price = mathMul(parseFloat(dataObj.sell_price),new_count);//选中商品的价格*数量
    var goods_reduce = mathMul(parseFloat(dataObj.reduce),new_count);
    if($(_this).prop('checked')){
        $('#origin_price').val(mathAdd(origin_price,goods_price,2).toFixed(2));
        $('#discount_price').val(mathAdd(discount_price,goods_reduce,2).toFixed(2));
        $('#sum_price').text(mathSub(mathAdd(origin_price,goods_price,2), mathAdd(discount_price,goods_reduce,2),2));  
    }else{
        $('#origin_price').val(mathSub(origin_price,goods_price,2));
        $('#discount_price').val(mathSub(discount_price,goods_reduce,2));
        $('#sum_price').text(mathSub(mathSub(origin_price,goods_price,2), mathSub(discount_price,goods_reduce,2),2));  
    }
    
}


/*购物车编辑和完成div切换*/
$(document).on('click', '.sel_cz_wc', function(){
    var _t = $(this);
    _t.addClass('sel_cz_bj').removeClass('sel_cz_wc').text('编辑').closest('.js_sel_goods').addClass('cart_goods').removeClass('cart_edit');
    _t.closest('.js_sel_goods').find('li.js_show_goods_det').each(function(){
        var _item = jQuery.parseJSON($(this).find("input[name^='sub']").attr('data-json'));
        var _n = $(this).find('div.goods_num_adjust input').val();
        $(this).find('div.goods_num_adjust').remove();
        $(this).find('div.cart_list_info').prepend('<h3 class="cart_list_info_title"><a href="{url:/site/products/id/'+_item.goods_id+'">'+_item.name+'</a></h3>');
        $(this).find('div.cart_list_info').append('<p class="cart_list_info_price "><em>￥'+_item.sell_price+'</em><del class="old_price">￥'+_item.market_price+'</del></p>');
        $(this).find('div.dele').remove();
        $(this).find('div.cart_list_info').after('<div class="good_num">×'+_n+'</div>');
        $(this).find('input.js_buy_num').val(_n);
    })
})
$(document).on('click', '.sel_cz_bj', function(){
    var _t = $(this);
    _t.addClass('sel_cz_wc').removeClass('sel_cz_bj').text('完成').closest('.js_sel_goods').addClass('cart_edit').removeClass('cart_goods');
    _t.closest('.js_sel_goods').find('li.js_show_goods_det').each(function(){
        var _item = jQuery.parseJSON($(this).find("input[name^='sub']").attr('data-json'));
        $(this).find('h3.cart_list_info_title').remove();
        var _html = '<div class="goods_num_adjust"><span onclick="cart_reduce(this);">-</span><input type="text" onchange="cartCount(this);" id="count_'+_item.goods_id+'_'+_item.product_id+'" value="'+_item.count+'"><span onclick="cart_increase(this);">+</span></div>';
        $(this).find('div.cart_list_info').prepend(_html);
        $(this).find('p.cart_list_info_price').remove();
        $(this).find('div.good_num').remove();
        var _n = $(this).find('div.goods_num_adjust input').val();
        $(this).find('div.cart_list_info').after('<div class="dele" onclick="removeCartByJSON(this);">删除</div>');
    })
})


//购物车数量改动计算
function cartCount(_obj)
{
    var obj = jQuery.parseJSON(($(_obj).closest('li.js_show_goods_det')).find("input[name^='sub']").attr('data-json'));
    var countInput = $('#count_'+obj.goods_id+'_'+obj.product_id);
    var countInputVal = parseInt(countInput.val());
    var oldNum = countInput.data('oldNum') ? countInput.data('oldNum') : obj.count;
    //商品数量大于1件
    if(isNaN(countInputVal) || (countInputVal <= 0))
    {
        alert('购买的数量必须大于1件');
        countInput.val(1);
        countInput.change();
    }
    //商品数量小于库存量
    else if(countInputVal > parseInt(obj.store_nums))
    {
        alert('购买的数量不能大于此商品的库存量');
        countInput.val(parseInt(obj.store_nums));
        countInput.change();
    }
    else
    {
        var diff = parseInt(countInputVal) - parseInt(oldNum);
        if(diff == 0)
        {
            return;
        }

        var goods_id   = obj.product_id > 0 ? obj.product_id : obj.goods_id;
        var goods_type = obj.product_id > 0 ? "product"      : "goods";

        //更新购物车中此商品的数量
        $.getJSON(cartUrl,{"goods_id":goods_id,"type":goods_type,"goods_num":diff,"random":Math.random()},function(content){
            if(content.isError == true)
            {
                alert(content.message);
                countInput.val(1);
                countInput.change();
            }
            else
            {
                var goodsId   = [];
                var productId = [];
                var num       = [];
                $('[id^="count_"]').each(function(i)
                {
                    var idValue = $(this).attr('id');
                    var dataArray = idValue.split("_");

                    goodsId.push(dataArray[1]);
                    productId.push(dataArray[2]);
                    num.push(this.value);
                });
                countInput.data('oldNum',countInputVal);
                $.getJSON(promUrl,{"goodsId":goodsId,"productId":productId,"num":num,"random":Math.random()},function(content){
                    console.info(content)
                    if(content.promotion.length > 0)
                    {
                        $('#cart_prompt li').remove();

                        for(var i = 0;i < content.promotion.length; i++)
                        {
                            $('#cart_prompt ol').append( template.render('promotionTemplate',{"item":content.promotion[i]}) );
                        }
                        $('#cart_prompt').show();
                    }
                    else
                    {
                        $('#cart_prompt li').remove();
                        $('#cart_prompt').hide();
                    }

                    /*开始更新数据*/
                    var checkInput   = countInput.closest('li.js_show_goods_det').find('input[name^=sub]');
                    console.info(checkInput);
                    if(checkInput.prop('checked')){
                        $('#sum_price').html(content.final_sum);
                    }
                });
            }
        });
    }
}

//增加商品数量
function cart_increase(_obj){
    var obj = jQuery.parseJSON(($(_obj).closest('li.js_show_goods_det')).find("input[name^='sub']").attr('data-json'));
    //库存超量检查
    var countInput = $('#count_'+obj.goods_id+'_'+obj.product_id);
    if(parseInt(countInput.val()) + 1 > parseInt(obj.store_nums))
    {
        alert('购买的数量大于此商品的库存量');
    }
    else
    {
        countInput.val(parseInt(countInput.val()) + 1);
        countInput.change();
    }
}

//减少商品数量
function cart_reduce(_obj){
    var obj = jQuery.parseJSON(($(_obj).closest('li.js_show_goods_det')).find("input[name^='sub']").attr('data-json'));
    //库存超量检查
    var countInput = $('#count_'+obj.goods_id+'_'+obj.product_id);
    if(parseInt(countInput.val()) - 1 <= 0)
    {
        alert('购买的数量必须大于1件');
    }
    else
    {
        countInput.val(parseInt(countInput.val()) - 1);
        countInput.change();
    }
}

//移除购物车
function removeCartByJSON(_obj)
{
    var obj = jQuery.parseJSON(($(_obj).closest('li.js_show_goods_det')).find("input[name^='sub']").attr('data-json'));
    var goods_id   = obj.product_id > 0 ? obj.product_id : obj.goods_id;
    var goods_type = obj.product_id > 0 ? "product"      : "goods";
    $.getJSON(removeCartUrl,{"goods_id":goods_id,"type":goods_type,"random":Math.random()},function()
    {
        if($(_obj).closest('ul').find('li.js_show_goods_det').length > 1)
        {
            $(_obj).closest('li.js_show_goods_det').remove();
        }
        else
        {
            window.location.reload();
        }
    });
}