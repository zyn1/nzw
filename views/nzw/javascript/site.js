//商品移除购物车
function removeCart(goods_id,type)
{
	var goods_id = parseInt(goods_id);
	$.getJSON(creatUrl("simple/removeCart"),{goods_id:goods_id,type:type},function(content){
		if(content.isError == false)
		{
			$('[name="mycart_count"]').html(content.data['count']);
			$('[name="mycart_sum"]').html(content.data['sum']);
		}
		else
		{
			alert(content.message);
		}
	});
}

//添加收藏夹
function favorite_add_ajax(goods_id,obj)
{
	$.getJSON(creatUrl("simple/favorite_add"),{"goods_id":goods_id,"random":Math.random()},function(content){
		alert(content.message);
	});
}

//购物车展示
function showCart()
{
	$.getJSON(creatUrl("simple/showCart"),{sign:Math.random()},function(content)
	{
		var cartTemplate = template.render('cartTemplete',{'goodsData':content.data,'goodsCount':content.count,'goodsSum':content.sum});
		$('#div_mycart').html(cartTemplate);
		$('#div_mycart').show();
	});
}


//dom载入成功后开始操作
$(function(){
	//购物车数量加载
	if($('[name="mycart_count"]').length > 0)
	{
		$.getJSON(creatUrl("simple/showCart"),{sign:Math.random()},function(content)
		{
			$('[name="mycart_count"]').html(content.count);
		});

		//购物车div层显示和隐藏切换
		var mycartLateCall = new lateCall(200,function(){showCart();});
		$('[name="mycart"]').hover(
			function(){
				mycartLateCall.start();
			},
			function(){
				mycartLateCall.stop();
				$('#div_mycart').hide('slow');
			}
		);
	}
});

//[ajax]加入购物车
function joinCart_ajax(id,type)
{
	$.getJSON(creatUrl("simple/joinCart"),{"goods_id":id,"type":type,"random":Math.random()},function(content){
		if(content.isError == false)
		{
			var count = parseInt($('[name="mycart_count"]').html()) + 1;
			$('[name="mycart_count"]').html(count);
			alert(content.message);
		}
		else
		{
			alert(content.message);
		}
	});
}

//列表页加入购物车统一接口
function joinCart_list(id)
{
	$.getJSON(creatUrl("/simple/getProducts"),{"id":id},function(content)
	{
		if(!content || content.length == 0)
		{
			joinCart_ajax(id,'goods');
		}
		else
		{
			artDialog.open(creatUrl("/block/goods_list/goods_id/"+id+"/type/radio/is_products/1"),{
				id:'selectProduct',
				title:'选择货品到购物车',
				okVal:'加入购物车',
				ok:function(iframeWin, topWin)
				{
					var goodsList = $(iframeWin.document).find('input[name="id[]"]:checked');

					//添加选中的商品
					if(goodsList.length == 0)
					{
						alert('请选择要加入购物车的商品');
						return false;
					}
					var temp = $.parseJSON(goodsList.attr('data'));

					//执行处理回调
					joinCart_ajax(temp.product_id,'product');
					return true;
				}
			})
		}
	});
}


// 分类小图标图片切换
(function($){
	$(function(){
		var iconImg = $(".header .header_nav .goods_nav .cat_list > li");
		var pcImgRed = $("#icon_list h3 .pc_show_red");
	    var pcImgWhi = $("#icon_list h3 .pc_hide_white")
			$(iconImg).each(function(){
				var index = $(iconImg).index(this);	 
				$(this).hover(function() {
		        	 	    $(pcImgWhi).eq(index).show();
				            $(pcImgRed).eq(index).hide();
				            
				        }, function() {
				        	$(pcImgRed).eq(index).show();
				            $(pcImgWhi).eq(index).hide();
				        });
			})
	})
}) (jQuery);




//滚动楼层JS
(function($){
	$(function(){
var oNav = $('#LoutiNav');//导航壳
		   var aNav = oNav.find('li');//导航
		   var aDiv = $('#main .louceng');//楼层
		   var oTop = $('#goTop');
			//回到顶部
			$(window).scroll(function(){
				 var winH = $(window).height();//可视窗口高度
				 var iTop = $(window).scrollTop();//鼠标滚动的距离
				 
				 if(iTop>=$('#header').height()){
				 	oNav.fadeIn();
				 	oTop.fadeIn();
				 //鼠标滑动式改变	
				 aDiv.each(function(){
				 	if(winH+iTop - $(this).offset().top>winH/2){
				 		aNav.removeClass('active');
				 		aNav.eq($(this).index()).addClass('active');
				 		changeImg();
				 	}
				 })
				 }else{
				 	oNav.fadeOut();
				 	oTop.fadeOut();
				 }
			})
			//点击top回到顶部
			oTop.click(function(){
				$('body,html').animate({"scrollTop":0},10)
			})
			//点击回到当前楼层
			aNav.click(function(){
				var t = aDiv.eq($(this).index()).offset().top;
				$('body,html').animate({"scrollTop":t},10);
				$(this).addClass('active').siblings().removeClass('active');
				changeImg();
			});

			function changeImg()
			{
				$('#LoutiNav').find('li').each(function(){
					if($(this).hasClass('active'))
					{
						$(this).find('img').attr('src', $(this).find('img').attr('js_data'))
					}
					else
					{
						$(this).find('img').attr('src', $(this).find('img').attr('js_data_src'))
					}
				})
			}
	})
}) (jQuery);




/*右侧浮动楼层*/
$(function(){
    $(".fhdb_a").mouseover(function (){  
            $(this).parent(".show_div").find(".hover_div").show();  
        }).mouseout(function (){  
           $(this).parent(".show_div").find(".hover_div").hide();  
        }); 
})