<?php $menuData=menu::init($this->admin['role_id']);?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>后台管理</title>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="<?php echo $this->getWebSkinPath()."css/admin.css";?>" />
	<meta name="robots" content="noindex,nofollow">
	<link rel="shortcut icon" href="<?php echo IUrl::creatUrl("")."favicon.ico";?>" />
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/jquery/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artdialog/artDialog.js"></script><script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artdialog/plugins/iframeTools.js"></script><link rel="stylesheet" type="text/css" href="/nzw/runtime/_systemjs/artdialog/skins/aero.css" />
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/form/form.js"></script>
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/autovalidate/validate.js"></script><link rel="stylesheet" type="text/css" href="/nzw/runtime/_systemjs/autovalidate/style.css" />
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artTemplate/artTemplate.js"></script><script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artTemplate/artTemplate-plugin.js"></script>
	<script type='text/javascript' src="<?php echo $this->getWebViewPath()."javascript/common.js";?>"></script>
	<script type='text/javascript' src="<?php echo $this->getWebViewPath()."javascript/admin.js";?>"></script>
</head>
<body>
	<div class="container">
		<div id="header">
			<div class="logo">
				<a href="<?php echo IUrl::creatUrl("/system/default");?>"><img src="<?php echo $this->getWebSkinPath()."images/admin/logo.png";?>" width="303" height="43" /></a>
			</div>
			<div id="menu">
				<ul name="topMenu">
					<?php foreach(menu::getTopMenu($menuData) as $key => $item){?>
					<li>
						<a hidefocus="true" href="<?php echo IUrl::creatUrl("".$item."");?>"><?php echo isset($key)?$key:"";?></a>
					</li>
					<?php }?>
				</ul>
			</div>
			<p><a href="<?php echo IUrl::creatUrl("/systemadmin/logout");?>">退出管理</a> <a href="<?php echo IUrl::creatUrl("/system/admin_repwd");?>">修改密码</a> <a href="<?php echo IUrl::creatUrl("/system/default");?>">后台首页</a> <a href="<?php echo IUrl::creatUrl("");?>" target='_blank'>商城首页</a> <span>您好 <label class='bold'><?php echo isset($this->admin['admin_name'])?$this->admin['admin_name']:"";?></label>，当前身份 <label class='bold'><?php echo isset($this->admin['admin_role_name'])?$this->admin['admin_role_name']:"";?></label></span></p>
		</div>
		<div id="info_bar">
			<label class="navindex"><a href="<?php echo IUrl::creatUrl("/system/navigation");?>">快速导航管理</a></label>
			<span class="nav_sec">
			<?php $adminId = $this->admin['admin_id']?>
			<?php $query = new IQuery("quick_naviga");$query->where = "admin_id = $adminId and is_del = 0";$items = $query->find(); foreach($items as $key => $item){?>
			<a href="<?php echo isset($item['url'])?$item['url']:"";?>" class="selected"><?php echo isset($item['naviga_name'])?$item['naviga_name']:"";?></a>
			<?php }?>
			</span>
		</div>

		<div id="admin_left">
			<ul class="submenu">
				<?php $leftMenu=menu::get($menuData,IWeb::$app->getController()->getId().'/'.IWeb::$app->getController()->getAction()->getId())?>
				<?php foreach(current($leftMenu) as $key => $item){?>
				<li>
					<span><?php echo isset($key)?$key:"";?></span>
					<ul name="leftMenu">
						<?php foreach($item as $leftKey => $leftValue){?>
						<li><a href="<?php echo IUrl::creatUrl("".$leftKey."");?>"><?php echo isset($leftValue)?$leftValue:"";?></a></li>
						<?php }?>
					</ul>
				</li>
				<?php }?>
			</ul>
			<div id="copyright"></div>
		</div>

		<div id="admin_right">
			<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artTemplate/artTemplate.js"></script><script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artTemplate/artTemplate-plugin.js"></script>
<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/my97date/wdatepicker.js"></script>
<div class="headbar">
	<div class="position"><span>商品</span><span>></span><span>商品管理</span><span>></span><span>商品列表</span></div>
	<div class="operating">
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="window.location.href='<?php echo IUrl::creatUrl("/goods/goods_edit");?>'"><span class="addition">添加商品</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="selectAll('id[]')"><span class="sel_all">全选</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goods_del()"><span class="delete">删除</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goods_stats('up')"><span class="import">上架</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goods_stats('down')"><span class="export">下架</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goods_stats('check')"><span class="stop">待审</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="window.location.href='<?php echo IUrl::creatUrl("/goods/goods_recycle_list");?>'"><span class="recycle">回收站</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goodsCommend();"><span class="grade">商品推荐</span></button></a>
		<a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goodsShare();" title="开启/取消商品共享" alt="开启/取消商品共享"><span class="filter">商品共享</span></button></a>
        <a href="javascript:void(0);"><button class="operating_btn" type="button" onclick="goodsSetting();" title="批量设置" alt="批量设置"><span class="remove">批量设置</span></button></a>
	</div>

	<div class="searchbar">
		<form action="<?php echo IUrl::creatUrl("/");?>" method="get" name="searchListForm">
			<input type='hidden' name='controller' value='goods' />
			<input type='hidden' name='action' value='goods_list' />
			<select class="auto" name="search[seller_id]">
				<option value="">选择商品所属</option>
				<option value="=0">平台商品</option>
				<option value="!=0">商户商品</option>
			</select>
			<select class="auto" name="search[is_del]">
				<option value="">选择上下架</option>
				<option value="0">上架</option>
				<option value="2">下架</option>
				<option value="3">待审</option>
			</select>
			<select class="auto" name="search[store_nums]">
				<option value="">选择库存</option>
				<option value="go.store_nums < 1">无货</option>
				<option value="go.store_nums >= 1 and go.store_nums < 10">低于10</option>
				<option value="go.store_nums <= 100 and go.store_nums >= 10">10-100</option>
				<option value="go.store_nums >= 100">100以上</option>
			</select>
			<select class="auto" name="search[commend_id]">
				<option value="">选择商品标签</option>
				<option value="1">最新商品</option>
				<option value="2">特价商品</option>
				<option value="3">热卖商品</option>
				<option value="4">推荐商品</option>
			</select>
			<select class="auto" name="search[name]">
				<option value="go.name">商品名</option>
				<option value="go.goods_no">商品货号</option>
				<option value="seller.true_name">商户真实名称</option>
			</select>
			<input class="small" name="search[keywords]" type="text" value="" />

			<!--分类数据显示-->
			<span id="__categoryBox" style="margin-bottom:8px"></span>
			<button class="btn" type="button" name="_goodsCategoryButton"><span class="add">设置分类</span></button>
			<?php plugin::trigger('goodsCategoryWidget',array("name" => "search[category_id]","value" => isset($this->search['category_id']) ? $this->search['category_id'] : ""))?>

			<button class="btn" type="button" onclick='initSearchbar(1)'><span class="add">更 多</span></button>
			<button class="btn" type="submit"  onclick='changeAction(false)'><span class="sel">筛 选</span></button>
			<button class="btn" onclick='changeAction(true)'><span class="sel">导出Excel</span></button>
			<input type="hidden" name="search[adv_search]" value="" />
			<input type="hidden" name="search[brand_id]" value="" />
			<input type="hidden" name="search[sell_price]" value="" />
			<input type="hidden" name="search[create_time]" value="" />
		</form>
	</div>
	<div class="searchbar" id="adv_searchbar" style="display:none;">
		<select class="auto" name="brand_id" id="brand_id">
			<option value="">选择商品品牌</option>
			<?php $query = new IQuery("brand");$items = $query->find(); foreach($items as $key => $item){?>
			<option value="<?php echo isset($item['id'])?$item['id']:"";?>"><?php echo isset($item['name'])?$item['name']:"";?></option>
			<?php }?>
		</select>
		销售价格：<input type="text" class="tiny" name="sell_price_start" id="sell_price_start" pattern="float" value="" />-
		<input type="text" class="tiny" name="sell_price_end" id="sell_price_end" pattern="float" value="" />
		创建时间：<input class="small" type="text" name="create_time_start" id="create_time_start" onfocus="WdatePicker()" value="" />-
		<input class="small" type="text" name="create_time_end" id="create_time_end" onfocus="WdatePicker()" value="" />
	</div>
</div>

<form action="" method="post" name="orderForm">
	<div class="content">
		<table class="list_table">
			<colgroup>
				<col width="40px" />
				<col width="360px" />
				<col width="180px" />
				<col width="90px" />
				<col width="70px" />
				<col width="80px" />
				<col width="70px" />
				<col width="110px" />
			</colgroup>

			<thead>
				<tr>
					<th>选择</th>
					<th>商品名称</th>
					<th>分类</th>
					<th>销售价</th>
					<th>库存</th>
					<th>状态</th>
					<th>排序</th>
					<th>操作</th>
				</tr>
			</thead>

			<tbody>
				<?php foreach($this->goodsHandle->find() as $key => $item){?>
				<tr>
					<td><input name="id[]" type="checkbox" value="<?php echo isset($item['id'])?$item['id']:"";?>" /></td>
					<td><img src='<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/100/h/100");?>' class='ico' /><a class="orange" href="javascript:jumpUrl('<?php echo isset($item['is_del'])?$item['is_del']:"";?>','<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>')" title="<?php echo htmlspecialchars($item['name']);?>"><?php echo isset($item['name'])?$item['name']:"";?></a></td>
					<td>
					<?php $catName = array()?>
					<?php $query = new IQuery("category_extend as ce");$query->join = "left join category as cd on cd.id = ce.category_id";$query->fields = "cd.name";$query->where = "ce.goods_id = $item[id]";$query->order = "cd.id asc";$items = $query->find(); foreach($items as $key => $catData){?>
						[<?php echo isset($catData['name'])?$catData['name']:"";?>]
					<?php }?>
					</td>
					<td><a href="javascript:quickEdit(<?php echo isset($item['id'])?$item['id']:"";?>,'price');" class="orange" title="点击更新价格" id="priceText<?php echo isset($item['id'])?$item['id']:"";?>"><?php echo isset($item['sell_price'])?$item['sell_price']:"";?></a></td>
					<td><a href="javascript:quickEdit(<?php echo isset($item['id'])?$item['id']:"";?>,'store');" class="orange" title="点击更新库存" id="storeText<?php echo isset($item['id'])?$item['id']:"";?>"><?php echo isset($item['store_nums'])?$item['store_nums']:"";?></a></td>
					<td>
						<select onchange="changeIsDel(<?php echo isset($item['id'])?$item['id']:"";?>,this)">
							<option value="up" <?php echo $item['is_del']==0 ? 'selected':'';?>>上架</option>
							<option value="down" <?php echo $item['is_del']==2 ? 'selected':'';?>>下架</option>
							<option value="check" <?php echo $item['is_del']==3 ? 'selected':'';?>>待审</option>
						</select>
					</td>
					<td><input type="text" class="tiny" value="<?php echo isset($item['sort'])?$item['sort']:"";?>" onchange="changeSort(<?php echo isset($item['id'])?$item['id']:"";?>,this);" /></td>
					<td>
						<a href="<?php echo IUrl::creatUrl("/goods/goods_edit/id/".$item['id']."");?>"><img class="operator" src="<?php echo $this->getWebSkinPath()."images/admin/icon_edit.gif";?>" alt="编辑" /></a>
						<a href="javascript:void(0)" onclick="delModel({link:'<?php echo IUrl::creatUrl("/goods/goods_del/id/".$item['id']."");?>'})" ><img class="operator" src="<?php echo $this->getWebSkinPath()."images/admin/icon_del.gif";?>" alt="删除" /></a>
						<?php if($item['seller_id']){?>
						<a href="<?php echo IUrl::creatUrl("/site/home/id/".$item['seller_id']."");?>" target="_blank"><img src="<?php echo $this->getWebSkinPath()."images/admin/seller_ico.png";?>" /></a>
						<?php }?>

						<?php if($item['is_share'] == 1){?>
						<img title="商品共享" src="<?php echo $this->getWebSkinPath()."images/admin/icn_audio.png";?>" />
						<?php }?>
					</td>
				</tr>
				<?php }?>
			</tbody>
		</table>
	</div>
</form>

<?php echo $this->goodsHandle->getPageBar();?>

<!--库存更新模板-->
<script id="goodsStoreTemplate" type="text/html">
<form name="quickEditForm">
<table class="border_table" style="width:100%">
	<thead>
		<tr>
			<th>商品</th>
			<th>库存量</th>
		</tr>
	</thead>
	<tbody>
	<%for(var item in templateData){%>
		<%item=templateData[item]%>
		<tr>
			<td>
				<%=item['name']%>
				&nbsp;&nbsp;&nbsp;
				<%if(item['spec_array']){%>
					<%var specArrayList = parseJSON(item['spec_array'])%>
					<%for(var result in specArrayList){%>
						<%result = specArrayList[result]%>
						<%if(result['type'] == 1){%>
							<%=result['value']%>
						<%}else{%>
							<img class="img_border" width="30px" height="30px" src="<?php echo IUrl::creatUrl("")."<%=result['value']%>";?>">
						<%}%>
						&nbsp;&nbsp;&nbsp;
					<%}%>
				<%}%>
			</td>
			<td>
				<input type="text" class="small" name="data[<%=item['id']%>]" value="<%=item['store_nums']%>" />
			</td>
		</tr>
	<%}%>
	</tbody>
</table>
<input type='hidden' name='goods_id' value="<%=item['goods_id']%>" />
</form>
</script>

<!--价格更新的模板-->
<script id="goodsPriceTemplate" type="text/html">
<form name="quickEditForm">
<table class="border_table" style="width:100%">
	<thead>
		<tr>
			<th>商品</th>
			<th>市场价</th>
			<th>销售价</th>
			<th>成本价</th>
		</tr>
	</thead>
	<tbody>
	<%for(var item in templateData){%>
		<%item=templateData[item]%>
		<tr>
			<td>
				<%=item['name']%>
				&nbsp;&nbsp;&nbsp;
				<%if(item['spec_array']){%>
					<%var specArrayList = parseJSON(item['spec_array'])%>
					<%for(var result in specArrayList){%>
						<%result = specArrayList[result]%>
						<%if(result['type'] == 1){%>
							<%=result['value']%>
						<%}else{%>
							<img class="img_border" width="30px" height="30px" src="<?php echo IUrl::creatUrl("")."<%=result['value']%>";?>">
						<%}%>
						&nbsp;&nbsp;&nbsp;
					<%}%>
				<%}%>
			</td>
			<td><input type="text" class="small" name="data[<%=item['id']%>][market_price]" value="<%=item['market_price']%>" /></td>
			<td><input type="text" class="small" name="data[<%=item['id']%>][sell_price]" value="<%=item['sell_price']%>" /></td>
			<td><input type="text" class="small" name="data[<%=item['id']%>][cost_price]" value="<%=item['cost_price']%>" /></td>
		</tr>
	<%}%>
	</tbody>
</table>
<input type='hidden' name='goods_id' value="<%=item['goods_id']%>" />
</form>
</script>

<!--推荐更新模板-->
<script id="goodsCommendTemplate" type="text/html">
<form name="commendForm">
<table class="border_table" style="width:100%">
	<thead>
		<tr>
			<th>商品</th>
			<th>推荐选项</th>
		</tr>
	</thead>
	<tbody>
	<%for(var item in templateData){%>
		<%item=templateData[item]%>
		<tr>
			<td>
				<input type="hidden" name="data[<%=item['id']%>][]" value="" />
				<%=item['name']%>
			</td>
			<td>
				<label class="attr"><input type="checkbox" name="data[<%=item['id']%>][]" value="1" <%if(item['commend'] && item['commend'][1]){%>checked="checked"<%}%> />最新商品</label>
				<label class="attr"><input type="checkbox" name="data[<%=item['id']%>][]" value="2" <%if(item['commend'] && item['commend'][2]){%>checked="checked"<%}%> />特价商品</label>
				<label class="attr"><input type="checkbox" name="data[<%=item['id']%>][]" value="3" <%if(item['commend'] && item['commend'][3]){%>checked="checked"<%}%> />热卖商品</label>
				<label class="attr"><input type="checkbox" name="data[<%=item['id']%>][]" value="4" <%if(item['commend'] && item['commend'][4]){%>checked="checked"<%}%> />推荐商品</label>
			</td>
		</tr>
	<%}%>
	</tbody>
</table>
</form>
</script>

<script type="text/javascript">
//DOM加载
$(function(){
	<?php if($this->search){?>
	var searchData = <?php echo JSON::encode($this->search);?>;
	for(var index in searchData)
	{
		$('[name="search['+index+']"]').val(searchData[index]);
	}
	<?php }?>
	$("#brand_id").blur(function(){
		var brand_id = $(this).val();
		$('input[name="search[brand_id]"]').val(brand_id);
	});
	$("#sell_price_start").blur(function(){
		setSellpriceVal();
	});
	$("#sell_price_end").blur(function(){
		setSellpriceVal();
	});
	initSearchbar(0);
});

//商品推荐标签
function goodsCommend()
{
	if($('input:checkbox[name="id[]"]:checked').length > 0)
	{
		var idString = [];
		$('input:checkbox[name="id[]"]:checked').each(function(i)
		{
			idString.push(this.value);
		});

		$.getJSON("<?php echo IUrl::creatUrl("/block/goodsCommend");?>",{"id":idString.join(',')},function(json)
		{
			var templateHtml = template.render("goodsCommendTemplate",{'templateData':json});
			art.dialog(
			{
				okVal:"保存",
			    content: templateHtml,
			    ok:function(iframeWin)
			    {
			    	var formObj = iframeWin.document.forms['commendForm'];
			    	$.getJSON("<?php echo IUrl::creatUrl("/goods/update_commend");?>",$(formObj).serialize(),function(content)
			    	{
			    		if(content.result == 'fail')
			    		{
			    			alert(content.data);
			    		}
			    	});
			    }
			});
		});
	}
	else
	{
		alert("请选择您要操作的商品");
	}
}

//展示库存
function quickEdit(gid,typeVal)
{
	var submitUrl    = "";
	var templateName = "";
	var freshArea    = "";

	switch(typeVal)
	{
		case "store":
		{
			submitUrl    = "<?php echo IUrl::creatUrl("/goods/update_store");?>";
			templateName = "goodsStoreTemplate";
			freshArea    = "storeText";
		}
		break;

		case "price":
		{
			submitUrl    = "<?php echo IUrl::creatUrl("/goods/update_price");?>";
			templateName = "goodsPriceTemplate";
			freshArea    = "priceText";
		}
		break;
	}

	$.getJSON("<?php echo IUrl::creatUrl("/block/getGoodsData");?>",{"id":gid},function(json)
	{
		var templateHtml = template.render(templateName,{'templateData':json});
		art.dialog(
		{
			okVal:"保存",
		    content: templateHtml,
		    ok:function(iframeWin)
		    {
		    	var formObj = iframeWin.document.forms['quickEditForm'];
		    	$.getJSON(submitUrl,$(formObj).serialize(),function(content)
		    	{
		    		if(content.result == 'success')
		    		{
		    			$("#"+freshArea+gid).text(content.data);
		    		}
		    		else
		    		{
		    			alert(content.data);
		    		}
		    	});
		    }
		});
	});
}

//修改排序
function changeSort(gid,obj)
{
	var selectedValue = obj.value;
	$.getJSON("<?php echo IUrl::creatUrl("/goods/ajax_sort");?>",{"id":gid,"sort":selectedValue});
}

//修改上下架
function changeIsDel(gid,obj)
{
	var selectedValue = $(obj).find('option:selected').val();
	$.getJSON("<?php echo IUrl::creatUrl("/goods/goods_stats");?>",{"id":gid,"type":selectedValue});
}

//upload csv file callback
function artDialogCallback(message)
{
	message ? alert(message) : window.location.reload();
}

//删除
function goods_del()
{
	var flag = 0;
	$('input:checkbox[name="id[]"]:checked').each(function(i){flag = 1;});
	if(flag == 0)
	{
		alert('请选择要删除的数据');
		return false;
	}
	$("form[name='orderForm']").attr('action','<?php echo IUrl::creatUrl("/goods/goods_del");?>');
	confirm('确定要删除所选中的信息吗？','formSubmit(\'orderForm\')');
}

//上下架操作
function goods_stats(type)
{
	if($('input:checkbox[name="id[]"]:checked').length > 0)
	{
		var urlVal = "<?php echo IUrl::creatUrl("/goods/goods_stats/type/@type@");?>";
		urlVal = urlVal.replace("@type@",type);
		$("form[name='orderForm']").attr('action',urlVal);
		confirm('确定将选中的商品进行操作吗？',"formSubmit('orderForm')");
	}
	else
	{
		alert('请选择要操作的商品!');
		return false;
	}
}

//商品详情的跳转连接
function jumpUrl(is_del,url)
{
	is_del == 0 ? window.open(url) : alert("该商品没有上架无法查看");
}
//商品导入或查询切换
function changeAction(excel)
{
	setCreatetimeVal();
	if(excel)
	{
		$("input[name=\"action\"]").val("goods_report");
		$("form[name=\"searchListForm\"]").attr("target", "_blank");
	}
	else
	{
		$("input[name=\"action\"]").val("goods_list");
		$("form[name=\"searchListForm\"]").attr("target", "_self");
	}
}

//商品批量共享
function goodsShare()
{
	if($('input:checkbox[name="id[]"]:checked').length > 0)
	{
		var idString = [];
		$('input:checkbox[name="id[]"]:checked').each(function(i)
		{
			idString.push(this.value);
		});

		$.get("<?php echo IUrl::creatUrl("/goods/goods_share");?>",{"id":idString.join(',')},function(json)
		{
			window.location.reload();
		});
	}
	else
	{
		alert("请选择您要操作的商品");
	}
}

// 商品批量设置
function goodsSetting()
{
	if($('input:checkbox[name="id[]"]:checked').length > 0)
	{
		var idArray = [];
		var idString = '';
		$('input:checkbox[name="id[]"]:checked').each(function(i)
		{
			idArray.push(this.value);
		});
		idString = idArray.join(',');

		var urlVal = "<?php echo IUrl::creatUrl("/goods/goods_setting/id/@id@");?>";
		urlVal = urlVal.replace("@id@",idString);
		art.dialog.open(urlVal,{
			id:'goods_setting',
			title:'商品批量设置',
			okVal:'保存设置',
			ok:function(iframeWin, topWin){
				var formObject = iframeWin.document.forms[0];
				formObject.submit();
				loadding();
				return false;
			}
		});
	}
	else
	{
		alert("请选择您要操作的商品");
	}
}
// 设置销售价格的值
function setSellpriceVal()
{
	var sell_price_start = $('#sell_price_start').val();
	var sell_price_end = $('#sell_price_end').val();
	var sell_price = '';
	sell_price_start = parseFloat(sell_price_start);
	sell_price_end = parseFloat(sell_price_end);
	if(isNaN(sell_price_start))
	{
		sell_price_start = 0;
	}
	if(isNaN(sell_price_end))
	{
		sell_price_end = 0;
	}
	if(sell_price_start!=0 || sell_price_end!=0)
	{
		if(sell_price_start > sell_price_end)
		{
			sell_price = sell_price_end + ',' + sell_price_start;
		}
		else
		{
			sell_price = sell_price_start + ',' + sell_price_end;
		}
	}
	$('input[name="search[sell_price]"]').val(sell_price);
	return true;
}

// 设置创建时间的值
function setCreatetimeVal()
{
	var create_time_start = $('#create_time_start').val();
	var create_time_end = $('#create_time_end').val();
	var create_time = '';
	if('' != create_time_start && '' != create_time_end)
	{
		var start_time = Date.parse(create_time_start);
		var end_time = Date.parse(create_time_end);
		if(start_time > end_time)
		{
			create_time = create_time_end + ',' + create_time_start;
		}
		else
		{
			create_time = create_time_start + ',' + create_time_end;
		}
	}
	else if ('' != create_time_start)
	{
		create_time = create_time_start;
	}
	else if ('' != create_time_end)
	{
		create_time = create_time_end;
	}
	$('input[name="search[create_time]"]').val(create_time);
	return true;
}

// 初始化高级筛选
function initSearchbar(from)
{
	var adv_search = $('input[name="search[adv_search]"]').val();
	if(1 == from)
	{
		// 更多按钮
		adv_search = '1'==adv_search ? '' : '1';
		$('input[name="search[adv_search]"]').val(adv_search);
	}
	if('1' == adv_search)
	{
		$('#adv_searchbar').show();
		var brand_id = $('input[name="search[brand_id]"]').val();
		$("#brand_id").val(brand_id);

		var sell_price = $('input[name="search[sell_price]"]').val();
		if('' != sell_price)
		{
			var sell_price_arr = sell_price.split(",");
			$('#sell_price_start').val(sell_price_arr[0]);
			$('#sell_price_end').val(sell_price_arr[1]);
		}

		var create_time = $('input[name="search[create_time]"]').val();
		if('' != create_time)
		{
			var create_time_arr = create_time.split(",");
			var len = create_time_arr.length;
			switch(len)
			{
				case 1:
					$('#create_time_start').val(create_time_arr[0]);
					break;
				case 2:
					$('#create_time_start').val(create_time_arr[0]);
					$('#create_time_end').val(create_time_arr[1]);
					break;
			}
		}
	}
	else
	{
		$('#adv_searchbar').hide();
	}
}
</script>
		</div>
	</div>

	<script type='text/javascript'>
	//隔行换色
	$(".list_table tr:nth-child(even)").addClass('even');
	$(".list_table tr").hover(
		function () {
			$(this).addClass("sel");
		},
		function () {
			$(this).removeClass("sel");
		}
	);

	//按钮高亮
	var topItem  = "<?php echo key($leftMenu);?>";
	$("ul[name='topMenu']>li:contains('"+topItem+"')").addClass("selected");

	var leftItem = "<?php echo IUrl::getUri();?>";
	$("ul[name='leftMenu']>li a[href^='"+leftItem+"']").parent().addClass("selected");
	</script>
</body>
</html>
