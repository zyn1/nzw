<script id="categoryButtonTemplate" type="text/html">
<ctrlArea>
	<input type="hidden" value="<%=templateData['id']%>" name="<?php echo isset($name)?$name:"";?>" />
	<button class="btn" type="button" onclick="return confirm('确定删除此分类？') ? $(this).parent().remove() : '';">
		<span><%=templateData['name']%></span>
	</button>
</ctrlArea>
</script>

<script type="text/javascript">
//插件value预设值
jQuery(function()
{
	//绑定UI按钮入口
	$(document).on("click","[name='_goodsCategoryButton']",selectGoodsCategory);

	//完整分类数据
	<?php $query = new IQuery("category");$query->order = "sort asc";$categoryData = $query->find(); foreach($categoryData as $key => $item){?><?php }?>
	art.dialog.data('categoryWhole',<?php echo JSON::encode($categoryData);?>);
	art.dialog.data('categoryParentData',<?php echo JSON::encode(goods_class::categoryParentStruct($categoryData));?>);

	<?php if(isset($default)){?>
	createGoodsCategory(<?php echo JSON::encode($default);?>);
	<?php }?>
});

/**
 * @brief 商品分类弹出框
 * @param string urlValue 提交地址
 * @param string categoryName 商品分类name值
 */
function selectGoodsCategory()
{
	//根据表单里面的name值生成分类ID数据
	var categoryName = "<?php echo isset($name)?$name:"";?>";
	var result = [];
	$('[name="'+categoryName+'"]').each(function()
	{
		result.push(this.value);
	});
	art.dialog.data('categoryValue',result);

	//URL地址
	<?php if(isset($type) && $type == "checkbox"){?>
	var urlValue = "<?php echo IUrl::creatUrl("/block/goods_category/type/checkbox");?>";
	<?php }else{?>
	var urlValue = "<?php echo IUrl::creatUrl("/block/goods_category/type/radio");?>";
	<?php }?>

	art.dialog.open(urlValue,{
		title:'选择商品分类',
		okVal:'确定',
		ok:function(iframeWin, topWin)
		{
			var categoryObject = [];
			var categoryWhole  = art.dialog.data('categoryWhole');
			var categoryValue  = art.dialog.data('categoryValue');
			for(var item in categoryWhole)
			{
				item = categoryWhole[item];
				if(jQuery.inArray(item['id'],categoryValue) != -1)
				{
					categoryObject.push(item);
				}
			}
			createGoodsCategory(categoryObject);
		},
		cancel:function()
		{
			return true;
		}
	})
}

//生成商品分类
function createGoodsCategory(categoryObj)
{
	if(!categoryObj)
	{
		return;
	}

	$('#__categoryBox').empty();
	for(var item in categoryObj)
	{
		item = categoryObj[item];
		var goodsCategoryHtml = template.render('categoryButtonTemplate',{'templateData':item});
		$('#__categoryBox').append(goodsCategoryHtml);
	}
}
</script>