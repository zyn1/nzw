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
	<div class="position"><span>系统</span><span>></span><span>第三方平台</span><span>></span><span>短信平台</span></div>
</div>
<div class="content_box">
	<div class="content form_content">
		<form action="#" method="post" name='sms_conf'>
			<table class="form_table">
				<colgroup>
					<col width="150px" />
					<col />
				</colgroup>
				<tr>
					<th>说明：</th>
					<td>
						立即接入短信平台！让您的客户把握第一手商城咨询和订单动态
						<a href="http://www.aircheng.com/notice/75-hsms" target="_blank" class="orange">如何使用？</a>
						<p>商城所用的短信内容模板在【/classes/smstemplate.php】文件中，尽量用原始的短信模板，否则会导致短信发送延迟等问题</p>
						<p>如果想关闭某个短信发送环节，可以直接把相应方法的返回值设置为空</p>
					</td>
				</tr>
				<tr>
					<th>管理员手机号：</th>
					<td><label class="red">【系统】——【网站设置】——【手机号】</label></td>
				</tr>
				<tr>
					<th>短信平台：</th>
					<td>
						<select name="sms_platform" class="normal">
							<option value="zhutong">ZT短信平台</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>商户ID：</th>
					<td><input type='text' class='normal' name='sms_userid' alt='' /><label>购买后分配的<用户ID></label></td>
				</tr>
				<tr>
					<th>用户名：</th>
					<td><input type='text' class='normal' name='sms_username' pattern='required' alt='' /><label>购买后分配的<用户帐号></label></td>
				</tr>
				<tr>
					<th>密码：</th>
					<td><input type='text' class='normal' name='sms_pwd' pattern='required' alt='' /><label>购买后分配的<用户账号密码></label></td>
				</tr>
				<tr>
					<th>测试手机号码：</th>
					<td><input type='text' class='normal' name='mobile' pattern='mobi' empty alt='填写正确的手机号码' /><label>必须先<保存>配置后，在测试短信发送的功能【可选】</label></td>
				</tr>
                <tr>
					<th></th>
					<td>
						<button type='button' class="submit" onclick="submitConfig();"><span>保 存</span></button>
                        <button class="submit" type='button' onclick="test_sendhsms(this);"><span id='testmobile'>测试短信发送</span></button>
					</td>
				</tr>
			</table>
		</form>
	</div>
</div>

<script type='text/javascript'>
jQuery(function()
{
	var formobj = new Form('sms_conf');
	formobj.init(<?php echo JSON::encode($this->_siteConfig->getInfo());?>);
});

//ajax提交信息
function submitConfig()
{
	var sendData = {};
	$('select,input[name^="sms_"]').each(function()
	{
		sendData[$(this).attr('name')] = $(this).val();
	});
	$.post("<?php echo IUrl::creatUrl("/system/save_conf");?>",sendData,function(content)
	{
		alert('保存成功');
	});
}

//测试短信发送
function test_sendhsms(obj)
{
	$('form[name="sms_conf"] input:text').each(function(){
		$(this).trigger('change');
	});

	if($('form[name="sms_conf"] input:text.invalid-text').length > 0)
	{
		return;
	}

	//按钮控制
	obj.disabled = true;
	$('#testmobile').html('正在测试发送请稍后...');

	var ajaxUrl = '<?php echo IUrl::creatUrl("/system/test_sendhsms/random/@random@");?>';
	ajaxUrl     = ajaxUrl.replace('@random@',Math.random());

	$.getJSON(ajaxUrl,$('form[name="sms_conf"]').serialize(),function(content){
		obj.disabled = false;
		$('#testmobile').html('测试短信发送');
		alert(content.message);
	});
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
