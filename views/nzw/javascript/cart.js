$(function(){
	$('[type=checkbox]').prop('checked',false);
	$('#ckAll').click(function(){
		$("input[name^='sub']").prop("checked", this.checked);
			if(!this.checked) {
                $('.js_goods_list').find('input[type=checkbox]').removeAttr('checked');
                $('#weight').text(0);
			    $('#origin_price').text(0);
                $('#discount_price').text(0);     
			    $('#sum_price').text(0);	
			}else{
					var weight = origin_price = total_discount = promotion_price = sum_price = 0;
                    
                    $('.js_goods_list').find('input').attr('checked', 'checked');
					$("input[name^='sub']").each(function(i){
						var json = JSON.parse($(this).attr('data-json'));
						var num = $('#count_'+json.goods_id+'_'+json.product_id).val();
                        weight +=mathMul(parseFloat(json.weight),num);
						origin_price +=mathMul(parseFloat(json.sell_price),num);
                        total_discount += mathMul(parseFloat(json.reduce),num);
                        
					})
                    $('#weight').text(weight);
                    $('#origin_price').text(origin_price.toFixed(2));
                    $('#discount_price').text(total_discount);      
                    $('#sum_price').text(mathSub(origin_price, total_discount));    
					
			}
	})
	$('input[name^=sub]').click(function(){
		var $subs = $("input[name^='sub']");
		$('#ckAll').prop("checked" , $subs.length == $subs.filter(":checked").length ? true :false);
		check_goods(this);
	})
  

})
function check_goods(_this){
		var data = $(_this).attr('data-json');
		var dataObj = JSON.parse(data);
		
		var weight_total = parseInt($('#weight').text());
		var origin_price = parseFloat( $('#origin_price').text());
        var discount_price = parseFloat($('#discount_price').text());
        var promotion_price = parseFloat($('#promotion_price').text());
		var delivery = parseFloat($('#delivery').text());
		var sum_price = parseFloat($('#sum_price').text());
        var new_count = parseInt($('#count_'+dataObj.goods_id+'_'+dataObj.product_id).val());                 
		var goods_price = mathMul(parseFloat(dataObj.sell_price),new_count);//选中商品的价格*数量
		var goods_reduce = mathMul(parseFloat(dataObj.reduce),new_count);
		if($(_this).prop('checked')){//
		    $('#weight').text(mathAdd(weight_total,mathMul(parseInt(dataObj.weight),new_count),2));
			$('#origin_price').text(mathAdd(origin_price,goods_price,2));
            $('#discount_price').text(mathAdd(discount_price,goods_reduce,2));
            $('#sum_price').text(mathSub(mathAdd(origin_price,goods_price,2), mathAdd(discount_price,goods_reduce,2)));  
		}else{
			$('#weight').text(mathSub(weight_total,parseInt(mathMul(dataObj.weight,new_count)),2));
			$('#origin_price').text(mathSub(origin_price,goods_price,2));
            $('#discount_price').text(mathSub(discount_price,goods_reduce,2));
            $('#sum_price').text(mathSub(mathSub(origin_price,goods_price,2), mathSub(discount_price,goods_reduce,2)));  
		}
		
	}


//购物车数量改动计算
function cartCount(obj)
{
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
                    if(content.promotion.length > 0)
                    {
                        $('#cart_prompt .indent').remove();

                        for(var i = 0;i < content.promotion.length; i++)
                        {
                            $('#cart_prompt').append( template.render('promotionTemplate',{"item":content.promotion[i]}) );
                        }
                        $('#cart_prompt').show();
                    }
                    else
                    {
                        $('#cart_prompt .indent').remove();
                        $('#cart_prompt').hide();
                    }
                    $('#sum_'+obj.goods_id+'_'+obj.product_id).html((obj.sell_price * countInputVal).toFixed(2));
                    var checkInput   = countInput.closest('tr').find('input[name^=sub]');
                    /*开始更新数据*/
                    if(checkInput.prop('checked')){//如果当前商品选中
                        var weight_total = parseInt($('#weight').text());
                        var origin_price = parseFloat( $('#origin_price').text());
                        var discount_price = parseFloat($('#discount_price').text());
                        var new_origin_price = mathAdd(origin_price,mathMul(parseFloat(obj.sell_price),diff),2);
                        var new_discount_price = mathAdd(discount_price,mathMul(parseFloat(obj.reduce),diff));
                        var new_weight = mathAdd(weight_total,mathMul(parseInt(obj.weight),diff),2);
                        $('#weight').text(new_weight);
                        $('#origin_price').text(new_origin_price);
                        $('#discount_price').text(new_discount_price);
                        $('#sum_price').text(mathSub(new_origin_price, new_discount_price));
                    }
                });
            }
        });
    }
}

//增加商品数量
function cart_increase(obj)
{
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
function cart_reduce(obj)
{
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
function removeCartByJSON(obj)
{
    var goods_id   = obj.product_id > 0 ? obj.product_id : obj.goods_id;
    var goods_type = obj.product_id > 0 ? "product"      : "goods";
    $.getJSON(removeCartUrl,{"goods_id":goods_id,"type":goods_type,"random":Math.random()},function()
    {
        window.location.reload();
    });
}