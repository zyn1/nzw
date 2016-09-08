<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="UTF-8">
	<title><?php echo $this->_siteConfig->name;?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/jquery/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/form/form.js"></script>
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/autovalidate/validate.js"></script><link rel="stylesheet" type="text/css" href="/nzw/runtime/_systemjs/autovalidate/style.css" />
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artdialog/artDialog.js"></script><script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artdialog/plugins/iframeTools.js"></script><link rel="stylesheet" type="text/css" href="/nzw/runtime/_systemjs/artdialog/skins/aero.css" />
	<script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artTemplate/artTemplate.js"></script><script type="text/javascript" charset="UTF-8" src="/nzw/runtime/_systemjs/artTemplate/artTemplate-plugin.js"></script>
	<!--[if IE]><script src="<?php echo $this->getWebViewPath()."javascript/html5.js";?>"></script><![endif]-->
	<script src="<?php echo $this->getWebViewPath()."javascript/common.js";?>"></script>
	<script src='<?php echo $this->getWebViewPath()."javascript/site.js";?>'></script>
	<link rel="stylesheet" href="<?php echo $this->getWebSkinPath()."style/style.css";?>">
</head>
<body>
<!--

模版使用字体图标为优化过的 awesome 3.0 图标字体库

使用帮助见:http://www.bootcss.com/p/font-awesome/

 -->
<header class="header web">
	<div class="top_line">
		<div class="welcome">
			欢迎您来到<?php echo $this->_siteConfig->name;?>！
			<?php if($this->user){?>
				<a href="<?php echo IUrl::creatUrl("/ucenter/index");?>">个人中心</a>
				<a href="<?php echo IUrl::creatUrl("/simple/logout");?>">退出</a>
			<?php }else{?>
				<a href="<?php echo IUrl::creatUrl("/simple/login");?>">登录</a>
				<a class="reg" href="<?php echo IUrl::creatUrl("/simple/reg");?>">免费注册</a>
			<?php }?>
		</div>
		<ul class="top_tool">
			<li>
				<a href="<?php echo IUrl::creatUrl("ucenter/index");?>">个人中心</a>
				<dl>
					<dd><a href="<?php echo IUrl::creatUrl("ucenter/order");?>">我的订单</a></dd>
					<dd><a href="<?php echo IUrl::creatUrl("ucenter/address");?>">我的收货地址</a></dd>
					<dd><a href="<?php echo IUrl::creatUrl("ucenter/integral");?>">我的积分</a></dd>
					<dd><a href="<?php echo IUrl::creatUrl("ucenter/account_log");?>">我的资金</a></dd>
				</dl>
			</li>
			<li><a href="<?php echo IUrl::creatUrl("/simple/seller");?>">申请开店</a></li>
			<li><a href="<?php echo IUrl::creatUrl("/seller/index");?>">商家管理</a></li>
			<li><a href="<?php echo IUrl::creatUrl("/site/help_list");?>">使用帮助</a></li>
			<li>客服电话：<em>4008-669-889</em></li>
		</ul>
	</div>
	<div class="header_main">
		<h1 class="logo">
			<!-- 这里的LOGO图片会自动靠左居中.因此只需要制作一个透明的LOGO图片即可 LOGO最大尺寸 400*90 -->
			<a href="<?php echo IUrl::creatUrl("");?>">
				<img src="<?php if($this->_siteConfig->logo){?><?php echo IUrl::creatUrl("")."".$this->_siteConfig->logo."";?><?php }else{?><?php echo $this->getWebSkinPath()."image/logo.png";?><?php }?>">
			</a>
		</h1>
		<div class="header_search">
			<form method='get' action='<?php echo IUrl::creatUrl("/");?>'>
				<input type='hidden' name='controller' value='site'>
				<input type='hidden' name='action' value='search_list'>
				<div class="search_box">
					<input class="input_keywords" type="text" name='word' autocomplete="off" placeholder="输入关键词">
					<input class="input_submit" type="submit" value="搜索">
				</div>
			</form>
			<div class="hot_words">
				<?php foreach(Api::run('getKeywordList') as $key => $item){?>
				<?php $tmpWord = urlencode($item['word']);?>
				<a href="<?php echo IUrl::creatUrl("/site/search_list/word/".$tmpWord."");?>"><?php echo isset($item['word'])?$item['word']:"";?></a>
				<?php }?>
			</div>
		</div>
		<div class="header_cart" name="mycart">
			<em class="count" name="mycart_count"]>0</em>
			<i class="icon-shopping-cart"></i>
			<a href="<?php echo IUrl::creatUrl("/simple/cart");?>" class="go_cart">去购物车结算</a>
			<div class="cart_simple" id="div_mycart"></div>
		</div>
		<!--购物车模板 开始-->
		<script type='text/html' id='cartTemplete'>
		<div class='cart_panel'>
			<ul class='cart_list'>
				<%for(var item in goodsData){%>
				<%var data = goodsData[item]%>
				<li id="site_cart_dd_<%=item%>">
					<em>共<%=data['count']%>件</em>
					<a target="_blank" href="<?php echo IUrl::creatUrl("/site/products/id/<%=data['goods_id']%>");?>">
						<img src="<?php echo IUrl::creatUrl("")."<%=data['img']%>";?>">
						<h5><%=data['name']%></h5>
					</a>
					<span>￥ <%=data['sell_price']%></span>
					<del onclick="removeCart('<%=data['id']%>','<%=data['type']%>');$('#site_cart_dd_<%=item%>').hide('slow');">删除</del>
				</li>
				<%}%>
				<%if(goodsCount){%>
				<div class="cart_total">
					<p>共<span name="mycart_count"><%=goodsCount%></span>件商品</p>
					<p>商品总额：<em name="mycart_sum">￥<%=goodsSum%></em></p>
					<a href="<?php echo IUrl::creatUrl("simple/cart");?>">去购物车结算</a>
				</div>
				<%}else{%>
				<div class='cart_no'>购物车空空如也~</div>
				<%}%>
			</ul>
		</div>
		</script>
	</div>
	<nav class="header_nav">
		<div class="goods_nav">
			<h2>全部商品分类</h2>
			<ul class="cat_list none">
				<?php foreach(Api::run('getCategoryListTop') as $key => $first){?>
				<li>
					<!--
						这里使用了CSS雪碧图的设计来制作导航的小图标.因为每个商城的图标是不一样的,因此这些小图标需要自己制作.
						我提供了小图标的 PSD文件 位于 skin/default/image/ico_cat.psd 文件
						请仿照 PSD源文件制作属于你的小图标 图标尺寸为 18*18 以20px为区间 靠左上角作图
					 -->
					<h3 class="cat_type_<?php echo $key+1;?>"><a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$first['id']."");?>"><?php echo isset($first['name'])?$first['name']:"";?></a></h3>
					<div class="cat_second">
						<?php foreach(Api::run('getCategoryByParentid',array('#parent_id#',$first['id'])) as $key => $second){?>
						<a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$second['id']."");?>"><?php echo isset($second['name'])?$second['name']:"";?></a>
						<?php }?>
					</div>
					<div class="cat_more">
						<?php foreach(Api::run('getCategoryByParentid',array('#parent_id#',$first['id'])) as $key => $second){?>
						<dl>
							<dt><a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$second['id']."");?>"><?php echo isset($second['name'])?$second['name']:"";?></a></dt>
							<dd>
								<?php foreach(Api::run('getCategoryByParentid',array('#parent_id#',$second['id'])) as $key => $third){?>
								<a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$third['id']."");?>"><?php echo isset($third['name'])?$third['name']:"";?></a>
								<?php }?>
							</dd>
						</dl>
						<?php }?>
					</div>
				</li>
				<?php }?>
			</ul>
		</div>
		<ul class="site_nav">
			<li><a href="<?php echo IUrl::creatUrl("/site/index");?>">首页</a></li>
			<?php foreach(Api::run('getGuideList') as $key => $item){?>
			<li><a href="<?php echo IUrl::creatUrl("".$item['link']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a></li>
			<?php }?>
		</ul>
	</nav>
</header>

<!--主要模板内容 开始-->
<!-- 焦点图和选项卡插件 -->
<script src="<?php echo $this->getWebViewPath()."javascript/FengFocus.js";?>"></script>
<script src="<?php echo $this->getWebViewPath()."javascript/FengTab.js";?>"></script>
<section class="home_1">
	<!-- 通栏焦点图 -->
	<section id="home_fouse" class="home_fouse">
		<?php if($this->index_slide){?>
		<ul>
			<?php foreach($this->index_slide as $key => $item){?>
			<li><a href="<?php echo IUrl::creatUrl("".$item['url']."");?>"><img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."");?>"></a></li>
			<?php }?>
		</ul>
		<?php }?>
	</section>
	<section class="promise">
		<ul>
			<li><i class="icon-truck"></i><strong>15天退货</strong></li>
			<li><i class="icon-gift"></i><strong>满99包邮</strong></li>
			<li><i class="icon-time"></i><strong>长株潭次日达</strong></li>
		</ul>
	</section>
	<section class="promotion">
		<h3>限时抢购</h3>
		<?php foreach(Api::run('getPromotionList',1) as $key => $item){?> <?php $free_time = ITime::getDiffSec($item['end_time'])?> <?php $countNumsItem[] = $item['p_id'];?>
		<div class="times">
			<span>剩余</span>
			<em id="cd_hour_<?php echo isset($item['p_id'])?$item['p_id']:"";?>"><?php echo floor($free_time/3600);?></em>
			<em id='cd_minute_<?php echo isset($item['p_id'])?$item['p_id']:"";?>'><?php echo floor(($free_time%3600)/60);?></em>
			<i id='cd_second_<?php echo isset($item['p_id'])?$item['p_id']:"";?>'><?php echo $free_time%60;?></i>
			<span>结束</span>
		</div>
		<a title="<?php echo isset($item['name'])?$item['name']:"";?>" href="<?php echo IUrl::creatUrl("/site/products/id/".$item['goods_id']."/promo/time/active_id/".$item['p_id']."");?>">
			<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/200/h/200");?>">
		</a>
		<?php }?>
	</section>
	<section class="home_1_show">
		<ul>
			<li><?php echo Ad::show("首页导航下方广告1_240*160(bubugao)");?></li>
			<li><?php echo Ad::show("首页导航下方广告2_240*160(bubugao)");?></li>
			<li><?php echo Ad::show("首页导航下方广告3_240*160(bubugao)");?></li>
			<li><?php echo Ad::show("首页导航下方广告4_240*160(bubugao)");?></li>
		</ul>
	</section>
</section>

<section class="home_2">
	<div id="hot_goods" class="hot_goods">
		<ul class="tab">
			<li>热卖商品</li>
			<li>新品上架</li>
			<li>特价商品</li>
			<li>推荐商品</li>
		</ul>
		<div class="con">
			<ul class="hot_goods_list">
				<?php foreach(Api::run('getCommendHot',5) as $key => $item){?>
				<li>
					<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>">
						<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>">
						<h4><?php echo isset($item['name'])?$item['name']:"";?></h4>
						<em>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></em>
						<del>￥<?php echo isset($item['market_price'])?$item['market_price']:"";?></del>
					</a>
				</li>
				<?php }?>
			</ul>
			<ul class="hot_goods_list">
				<?php foreach(Api::run('getCommendNew',5) as $key => $item){?>
				<li>
					<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>">
						<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>">
						<h4><?php echo isset($item['name'])?$item['name']:"";?></h4>
						<em>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></em>
						<del>￥<?php echo isset($item['market_price'])?$item['market_price']:"";?></del>
					</a>
				</li>
				<?php }?>
			</ul>
			<ul class="hot_goods_list">
				<?php foreach(Api::run('getCommendPrice',5) as $key => $item){?>
				<li>
					<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>">
						<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>">
						<h4><?php echo isset($item['name'])?$item['name']:"";?></h4>
						<em>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></em>
						<del>￥<?php echo isset($item['market_price'])?$item['market_price']:"";?></del>
					</a>
				</li>
				<?php }?>
			</ul>
			<ul class="hot_goods_list">
				<?php foreach(Api::run('getCommendRecom',5) as $key => $item){?>
				<li>
					<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>">
						<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>">
						<h4><?php echo isset($item['name'])?$item['name']:"";?></h4>
						<em>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></em>
						<del>￥<?php echo isset($item['market_price'])?$item['market_price']:"";?></del>
					</a>
				</li>
				<?php }?>
			</ul>
		</div>
	</div>
	<div id="home_article" class="home_article">
		<ul class="tab">
			<li><a href="<?php echo IUrl::creatUrl("/site/notice");?>">商城公告</a></li>
			<li><a href="<?php echo IUrl::creatUrl("/site/article");?>">新闻动态</a></li>
		</ul>
		<div class="con">
			<ul>
				<?php foreach(Api::run('getAnnouncementList',4) as $key => $item){?>
				<li><a href="<?php echo IUrl::creatUrl("/site/notice_detail/id/".$item['id']."");?>"><?php echo isset($item['title'])?$item['title']:"";?></a></li>
				<?php }?>
			</ul>
			<ul>
				<ul class="news-list">
					<?php foreach(Api::run('getArtList',4) as $key => $item){?>
					<li><a href="<?php echo IUrl::creatUrl("/site/article_detail/id/".$item['id']."");?>"><?php echo Article::showTitle($item['title'],$item['color'],$item['style']);?></a></li>
					<?php }?>
				</ul>
			</ul>
		</div>
		<div class="show">
			<?php echo Ad::show("首页商城公告下方广告_240*140(bubugao)");?>
		</div>
	</div>
</section>

<section class="home_show">
	<?php echo Ad::show("首页热卖商品下方通栏广告_1200*120(bubugao)");?>
</section>

<!-- 开始循环楼层 -->
<?php foreach(Api::run('getCategoryListTop') as $key => $first){?>
<section class="home_floor home_floor_<?php echo ($key%6)+1;?>">
	<header class="floor_head">
		<h2><?php echo isset($first['name'])?$first['name']:"";?></h2>
		<nav class="floor_nav">
			<ul>
				<?php foreach(Api::run('getCategoryByParentid',array('#parent_id#',$first['id'])) as $key => $second){?>
				<li><a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$second['id']."");?>"><?php echo isset($second['name'])?$second['name']:"";?></a></li>
				<?php }?>
				<li><a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$first['id']."");?>">更多</a></li>
			</ul>
		</nav>
	</header>
	<section class="floor_body">
		<div class="floor_show">
			<?php echo Ad::show("首页分类广告230*474(bubugao)",$first['id']);?>
		</div>
		<div class="floor_goods">
			<ul>
				<?php foreach(Api::run('getCategoryExtendList',array('#categroy_id#',$first['id']),10) as $key => $item){?>
				<li>
					<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>">
						<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>">
						<h4><?php echo isset($item['name'])?$item['name']:"";?></h4>
						<em>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></em>
						<del>￥<?php echo isset($item['market_price'])?$item['market_price']:"";?></del>
					</a>
				</li>
				<?php }?>
			</ul>
		</div>
	</section>
</section>
<?php }?>


<script>
//dom载入完毕执行
$(function(){
	// 调用焦点图
	$("#home_fouse").FengFocus({trigger : "mouseover"});
	// 热门商品等选项卡
	$("#hot_goods").FengTab();
	// 通知选项卡
	$("#home_article").FengTab();
	//显示抢购倒计时
	var cd_timer = new countdown();
	<?php if(isset($countNumsItem) && $countNumsItem){?>
	<?php foreach($countNumsItem as $key => $item){?>
		cd_timer.add(<?php echo isset($item)?$item:"";?>);
	<?php }?>
	<?php }?>
});
</script>
<!--主要模板内容 结束-->

<footer class="foot">
	<section class="help">
		<?php $catIco = array('help-new','help-delivery','help-pay','help-user','help-service')?>
		<?php foreach(Api::run('getHelpCategoryFoot') as $key => $helpCat){?>
		<dl class="help_<?php echo $key+1;?>">
			<dt><i class="icon"></i><?php echo isset($helpCat['name'])?$helpCat['name']:"";?></dt>
			<?php foreach(Api::run('getHelpListByCatidAll',array('#cat_id#',$helpCat['id'])) as $key => $item){?>
			<dd><a href="<?php echo IUrl::creatUrl("/site/help/id/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a></dd>
			<?php }?>
		</dl>
		<?php }?>
		<div class="contact">
			<span class="tel"><i class="icon-phone"></i>4008-669-889</span>
			<span class="mail"><i class="icon-envelope-alt"></i>cs@bubugao.com</span>
		</div>
	</section>
	<section class="service">
		<ul>
			<li class="item1">
				<i class="icon-star"></i>
				<strong>正品优选</strong>
				<span>共享集团供应链</span>
			</li>
			<li class="item2">
				<i class="icon-globe"></i>
				<strong>上市公司</strong>
				<span>诚信服务 品质保证</span>
			</li>
			<li class="item3">
				<i class="icon-group"></i>
				<strong>300家连锁门店</strong>
				<span>门店体验 网上下单</span>
			</li>
			<li class="item4">
				<i class="icon-plane"></i>
				<strong>长株潭次日达</strong>
				<span>专业物流 及时送达</span>
			</li>
			<li class="item5">
				<i class="icon-gift"></i>
				<strong>满99包邮</strong>
				<span>轻松购物，超值贴心</span>
			</li>
		</ul>
	</section>
	<section class="copy">
		<?php echo IFilter::stripSlash($this->_siteConfig->site_footer_code);?>
	</section>
</footer>

</body>
</html>
<script>
//当首页时隐藏分类
<?php if(IWeb::$app->getController()->getId() == 'site' && IWeb::$app->getController()->getAction()->getId() == 'index'){?>
$('.cat_list').removeClass('none');
<?php }?>

$(function(){
	$('input:text[name="word"]').val("<?php echo $this->word;?>");
});
</script>