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
	<div class="position"><span>系统</span><span>></span><span>网站管理</span><span>></span><span><?php echo isset($themeTypeName)?$themeTypeName:"";?></span></div>
</div>

<form action="<?php echo IUrl::creatUrl("/system/applyTheme");?>" method="post">
<input type="hidden" name="type" value="<?php echo IReq::get('type');?>" />
<div class="content">
	<?php foreach($themeList as $theme => $item){?>
	<table class='list_table th_right'>
		<colgroup>
			<col width='175px' />
			<col width='60px' />
			<col />
		</colgroup>

		<tbody>
			<tr>
				<th rowspan='6'>
					<div class="thumbnail">
						<img src="<?php echo $item['thumb'];?>" width='160px' height='180px' />
						<?php if(themeroute::isThemeUsed($theme)){?>
						<div class="sel"><span>正在使用</span></div>
						<?php }?>
					</div>
				</th>
				<th>名称：</th><td><?php echo isset($item['name'])?$item['name']:"";?></td>
			</tr>
			<tr><th>目录：</th><td><?php echo IWeb::$app->getWebViewPath();?><?php echo isset($theme)?$theme:"";?></td></tr>
			<tr><th>版本：</th><td><?php echo isset($item['version'])?$item['version']:"";?></td></tr>
			<tr><th>时间：</th><td><?php echo isset($item['time'])?$item['time']:"";?></td></tr>
			<tr><th>简介：</th><td><?php echo isset($item['info'])?$item['info']:"";?></td></tr>
			<tr>
				<th>皮肤：</th>
				<td><a href='<?php echo IUrl::creatUrl("/system/conf_skin/theme/".$theme."");?>' class='orange' title='选择主题模板的皮肤颜色'>查看皮肤详情</a></td>
			</tr>
		</tbody>
	</table>
	<br />
	<?php }?>
</div>

<div class="pages_bar bold">
	<?php foreach(IClient::supportClient() as $key => $client){?>
	<?php echo isset($client)?$client:"";?><?php echo isset($themeTypeName)?$themeTypeName:"";?>模板：
	<select name="<?php echo isset($client)?$client:"";?>" title='当客户用<?php echo isset($client)?$client:"";?>端访问<?php echo isset($themeTypeName)?$themeTypeName:"";?>时候，此主题模板会进行呈现'>
		<?php foreach($themeList as $theme => $themeData){?>
			<?php foreach($themeData['skin'] as $skin => $skinData){?>
			<option value='{"<?php echo isset($theme)?$theme:"";?>":"<?php echo isset($skin)?$skin:"";?>"}' data="<?php echo isset($client)?$client:"";?><?php echo isset($theme)?$theme:"";?><?php echo isset($skin)?$skin:"";?>"><?php echo isset($themeData['name'])?$themeData['name']:"";?>【<?php echo isset($skinData['name'])?$skinData['name']:"";?>】</option>
			<?php }?>
		<?php }?>
	</select>
	&nbsp;&nbsp;&nbsp;
	<?php }?>
	<button type="submit" class="submit"><span>保存主题设置</span></button>
</div>
</form>

<script type="text/javascript">
//主题模板数据初始化
jQuery(function()
{
	var theme = <?php echo JSON::encode(IWeb::$app->config['theme']);?>;
	if(theme)
	{
		for(var k in theme)
		{
			var childObj = theme[k];
			for(var i in childObj)
			{
				var checkKey = k+i+childObj[i];
				$("option[data='"+checkKey+"']").prop("selected",true);
			}
		}
	}
});
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
