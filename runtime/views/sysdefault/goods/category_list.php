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
			<div class="headbar">
	<div class="position"><span>商品</span><span>></span><span>商品分类管理</span><span>></span><span>分类列表</span></div>
	<div class="operating">
		<a href="javascript:;"><button class="operating_btn" type="button" onclick="window.location='<?php echo IUrl::creatUrl("/goods/category_edit");?>'"><span class="addition">添加分类</span></button></a>
	</div>
</div>

<form action="<?php echo IUrl::creatUrl("/goods/category_sort");?>" method="post" name="category_list">
<div class="content">
	<table id="list_table" class="list_table">
		<colgroup>
			<col width="100px" />
			<col width="300px" />
			<col width="100px" />
			<col width="220px" />
		</colgroup>

		<thead>
			<tr>
				<th>排序</th>
				<th>分类名称</th>
				<th>首页显示</th>
				<th>操作</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach($category as $key => $item){?>
			<tr id="<?php echo isset($item['id'])?$item['id']:"";?>" parent=<?php echo isset($item['parent_id'])?$item['parent_id']:"";?>>
				<td><input class="tiny" id="s<?php echo isset($item['id'])?$item['id']:"";?>" size="2" type="text" onblur="toSort(<?php echo isset($item['id'])?$item['id']:"";?>);" value="<?php echo isset($item['sort'])?$item['sort']:"";?>" /></td>
				<td><img style='margin-left:<?php echo $item['floor']*20;?>px' class="operator" src="<?php echo $this->getWebSkinPath()."images/admin/close.gif";?>" onclick="displayData(this);" alt="关闭" /><?php echo isset($item['name'])?$item['name']:"";?></td>
				<td><?php if($item['visibility']=='1'){?><span class="green">是</span><?php }else{?><span class="brown">否</span><?php }?></td>
				<td>
					<a href="<?php echo IUrl::creatUrl("/goods/category_edit/cid/".$item['id']."");?>"><img class="operator" src="<?php echo $this->getWebSkinPath()."images/admin/icon_edit.gif";?>" alt="修改" title="修改" /></a>
					<a href="javascript:void(0)" onclick="delModel({link:'<?php echo IUrl::creatUrl("/goods/category_del/cid/".$item['id']."");?>'})"><img class="operator" src="<?php echo $this->getWebSkinPath()."images/admin/icon_del.gif";?>" alt="删除" title="删除" /></a>
				</td>
			</tr>
			<?php }?>
		</tbody>
	</table>
</div>
</form>

<script language="javascript">
//折叠展示
function displayData(_self)
{
	if(_self.alt == "关闭")
	{
		jqshow($(_self).parent().parent().attr('id'), 'hide');
		$(_self).attr("src", "<?php echo $this->getWebSkinPath()."images/admin/open.gif";?>");
		_self.alt = '打开';
	}
	else
	{
		jqshow($(_self).parent().parent().attr('id'), 'show');
		$(_self).attr("src", "<?php echo $this->getWebSkinPath()."images/admin/close.gif";?>");
		_self.alt = '关闭';
	}
}

function jqshow(id,isshow) {
	var obj = $("#list_table tr[parent='"+id+"']");
	if (obj.length>0)
	{
		obj.each(function(i) {
			jqshow($(this).attr('id'), isshow);
		});
		if (isshow=='hide')
		{
			obj.hide();
		}
		else
		{
			obj.show();
		}
	}
}
//排序
function toSort(id)
{
	if(id!='')
	{
		var va = $('#s'+id).val();
		var part = /^\d+$/i;
		if(va!='' && va!=undefined && part.test(va))
		{
			$.get("<?php echo IUrl::creatUrl("/goods/category_sort");?>",{'id':id,'sort':va}, function(data)
			{
				if(data=='1')
				{
					alert('修改商品分类排序成功!');
				}else
				{
					alert('修改商品分类排序错误!');
				}
			});
		}
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
