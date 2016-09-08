<?php
/**
 * @copyright (c) 2011 aircheng
 * @file seo.php
 * @brief seo处理
 * @author nswe
 * @date 2016/7/12 9:15:21
 * @version 4.5
 */
class seo extends pluginBase
{
	//插件注册
	public function reg()
	{
		//后台管理SEO
		plugin::reg("onSystemMenuCreate",function(){
			$link = "/plugins/seo_list";
			Menu::$menu["插件"]["插件管理"][$link] = $this->name();
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_list",function(){
			self::controller()->seo_list = function(){$this->seo_list();};
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_edit",function(){
			self::controller()->seo_edit = function(){$this->seo_edit();};
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_update",function(){
			self::controller()->seo_update = function(){$this->seo_update();};
		});

		plugin::reg("onBeforeCreateAction@plugins@seo_del",function(){
			self::controller()->seo_del = function(){$this->seo_del();};
		});

		//设置网页SEO信息
		plugin::reg("onFinishView",$this,"setSeo");
	}

	//seo变量信息
	public function seoVarInfo()
	{
		return array(
			"{name}" => "网页动态内容名称",
			"{title}" => "网页动态内容标题",
			"{keywords}" => "网页动态内容关键词",
			"{description}" => "网页动态内容描述",
			"{web_name}" => "网站首页名称",
			"{web_title}" => "网站首页标题",
			"{web_keywords}" => "网站首页关键词",
			"{web_description}" => "网站首页描述",
		);
	}

	//编辑SEO页面
	public function seo_edit()
	{
		$id     = IFilter::act(IReq::get('id'),'int');
		$seoRow = array();
		if($id)
		{
			$seoDB = new IModel('seo');
			$seoRow= $seoDB->getObj('id = '.$id);
		}
		$this->redirect('seo_edit',array('seoData' => $seoRow,'seoVar' => $this->seoVarInfo()));
	}

	//更新SEO信息
	public function seo_update()
	{
		$id          = IFilter::act(IReq::get('id'),'int');
		$name        = IFilter::act(IReq::get('name'));
		$pathinfo    = IFilter::act(IReq::get('pathinfo'));
		$title       = IFilter::act(IReq::get('title'));
		$keywords    = IFilter::act(IReq::get('keywords'));
		$description = IFilter::act(IReq::get('description'));

		$updateData  = array(
			'name'        => $name,
			'pathinfo'    => trim($pathinfo,"/"),
			'title'       => $title,
			'keywords'    => $keywords,
			'description' => $description,
		);

		$seoDB = new IModel('seo');
		$seoDB->setData($updateData);
		if($id)
		{
			$seoDB->update('id = '.$id);
		}
		else
		{
			$seoDB->add();
		}
		$this->redirect('seo_list',true);
	}

	//删除SEO信息
	public function seo_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$seoDB = new IModel('seo');
			$seoDB->del('id = '.$id);
		}
		$this->redirect('seo_list',true);
	}

	//SEO列表信息
	public function seo_list()
	{
		$this->redirect('seo_list');
	}

	public static function name()
	{
		return "SEO优化插件";
	}

	public static function description()
	{
		return "设置各个网页SEO信息，方便搜索引擎收录提升网站排名";
	}

	public static function install()
	{
		$seoDB = new IModel('seo');
		if($seoDB->exists())
		{
			return true;
		}
		$data = array(
			"comment" => self::name(),
			"column"  => array(
				"id"         => array("type" => "int(11) unsigned",'auto_increment' => 1),
				"name"       => array("type" => "varchar(255)","comment" => "伪静态名称"),
				"pathinfo"   => array("type" => "varchar(255)","comment" => "URL伪静态格式(控制器/动作)"),
				"title"      => array("type" => "varchar(255)","comment" => "SEO信息title"),
				"keywords"   => array("type" => "text","comment" => "SEO信息keywords"),
				"description"=> array("type" => "text","comment" => "SEO信息description"),
			),
			"index" => array("primary" => "id","key" => "pathinfo"),
		);
		$seoDB->setData($data);
		return $seoDB->createTable();
	}

	public static function uninstall()
	{
		$seoDB = new IModel('seo');
		return $seoDB->dropTable();
	}

	public static function configName()
	{
		return array(
			"webTitlePosition" => array("name" => "添加网站名称到网页标题","type" => "select","value" => array("后缀" => "behind","前缀" => "front","否" => "none")),
			"defaultSEO" => array("name" => "未设置网页的SEO规则","type" => "select","value" => array("采用默认首页SEO信息" => "defaultWeb","不显示" => "none")),
		);
	}

	//运行SEO网页渲染
	public function setSeo()
	{
		//获取插件配置
		$defConfig= $this->config();

		//获取当前pathinfo信息
		$pathinfo = $this->controller()->getId().'/'.$this->action()->getId();

		//查询SEO信息
		$seoDB    = new IModel('seo');
		$seoConfig= $seoDB->getObj('pathinfo = "'.$pathinfo.'"');
		if($seoConfig)
		{
			//网站名称缀处理
			switch($defConfig['webTitlePosition'])
			{
				case "front":
				{
					$seoConfig['title'] = $this->controller()->_siteConfig->name.' - '.$seoConfig['title'];
				}
				break;

				case "behind":
				{
					$seoConfig['title'] = $seoConfig['title'].' - '.$this->controller()->_siteConfig->name;
				}
				break;
			}
		}
		else if($defConfig['defaultSEO'] == 'defaultWeb')
		{
			$seoConfig = array(
				'title'       => $this->controller()->_siteConfig->index_seo_title,
				'keywords'    => $this->controller()->_siteConfig->index_seo_keywords,
				'description' => $this->controller()->_siteConfig->index_seo_description,
			);
		}

		//设置网页SEO信息
		if(isset($seoConfig) && $seoConfig)
		{
			//处理seo里面的变量
			foreach($seoConfig as $key => $seo)
			{
				$seoConfig[$key] = $this->replaceSEOVar($pathinfo,$seo);
			}
			$this->set($seoConfig);
		}
	}

	/**
	 * 在view里为iwebshop页面调整title、keywords、description
	 * @param array $config array('title'=>'','keywords'=>'','description'=>'')
	 */
	public static function set($config)
	{
		$html = ob_get_contents();
		ob_clean();
		preg_match("!<head>(.*?)</head>!ius",$html,$m);

		//如果页面本来就没有head头，则直接返回
		if(!isset($m[0]) || $m[0]=="")
			return;

		$head = $m[1];
		if(isset($config['title']))
		{
			$title = "<title>{$config['title']}</title>";
			if(preg_match('!<title>.*?</title>!',$head))
			{
				$head = preg_replace("!<title>.*?</title>!ui",$title,$head,1);
			}
			else
			{
				$head .= "\n".$title;
			}
		}

		if(isset($config['keywords']))
		{
			$keywords = "<meta name='keywords' content='{$config['keywords']}'>";
			if(preg_match("!<meta\s.*?name=['\"]keywords!ui",$head))
			{
				$head = preg_replace("!<meta\s.*?name=['\"]keywords.*?/?>!ui",$keywords,$head,1);
			}
			else
			{
				$head .= "\n".$keywords;
			}
		}

		if(isset($config['description']))
		{
			$description = "<meta name='description' content='{$config['description']}'>";
			if(preg_match("!<meta\s.*?name=['\"]description!ui",$head))
			{
				$head = preg_replace("!<meta\s.*?name=['\"]description.*?/?>!ui",$description,$head,1);
			}
			else
			{
				$head .= "\n".$description;
			}
		}
		$head = "<head>{$head}</head>";
		$html = preg_replace("!<head>(.*?)</head>!ius",$head,$html,1);
		echo $html;
	}

	/**
	 * 对部分特殊ACTION进行变量预处理
	 * @param string $pathinfo 伪静态地址
	 * @param string $content SEO信息
	 */
	public function replaceSEOVar($pathinfo,$content)
	{
		//替换网站全局变量
		$content = strtr($content,array(
			"{web_name}"        => $this->controller()->_siteConfig->name,
			"{web_title}"       => $this->controller()->_siteConfig->index_seo_title,
			"{web_keywords}"    => $this->controller()->_siteConfig->index_seo_keywords,
			"{web_description}" => $this->controller()->_siteConfig->index_seo_description,
		));

		//根据网页具体内容替换变量
		$name        = "";
		$title       = "";
		$keywords    = "";
		$description = "";

		switch($pathinfo)
		{
			case "site/products":
			case "site/pic_show":
			case "site/pro_detail":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('goods');
				$contentRow = $contentDB->getObj('id = '.$id,"name,keywords,description");
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['name'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['description'];
				}
			}
			break;
			case "site/article_detail":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('article');
				$contentRow = $contentDB->getObj('id = '.$id,"title,keywords,description");
				if($contentRow)
				{
					$name        = $contentRow['title'];
					$title       = $contentRow['title'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['description'];
				}
			}
			break;
			case "site/article":
			{
				$id = IFilter::act(IReq::get('id'),'int');
				$contentDB  = new IModel('article_category');
				$contentRow = $contentDB->getObj('id = '.$id,"name");
				if($contentRow)
				{
					$name = $contentRow['name'];
				}
			}
			break;
			case "site/help_list":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("help_category");
				$contentRow = $contentDB->getObj("id = ".$id,'name');
				if($contentRow)
				{
					$name = $contentRow['name'];
				}
			}
			break;
			case "site/help":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("help");
				$contentRow = $contentDB->getObj("id = ".$id,'name');
				if($contentRow)
				{
					$name = $contentRow['name'];
				}
			}
			break;
			case "site/home":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("seller");
				$contentRow = $contentDB->getObj("id = ".$id,'true_name');
				if($contentRow)
				{
					$name = $contentRow['true_name'];
				}
			}
			break;
			case "site/pro_list":
			{
				$id         = IFilter::act(IReq::get("cat"),'int');
				$contentDB  = new IModel("category");
				$contentRow = $contentDB->getObj("id = ".$id,'name,keywords,descript,title');
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['title'];
					$keywords    = $contentRow['keywords'];
					$description = $contentRow['descript'];
				}
			}
			break;
			case "site/search_list":
			{
				$name = $this->controller()->word;
			}
			break;
			case "site/groupon":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("regiment");
				$contentRow = $contentDB->getObj("id = ".$id,'title');
				if($contentRow)
				{
					$name  = $contentRow['title'];
					$title = $contentRow['title'];
				}
			}
			break;
			case "site/brand_zone":
			{
				$id         = IFilter::act(IReq::get("id"),'int');
				$contentDB  = new IModel("brand");
				$contentRow = $contentDB->getObj("id = ".$id,'name,description');
				if($contentRow)
				{
					$name        = $contentRow['name'];
					$title       = $contentRow['name'];
					$description = $contentRow['description'];
				}
			}
			break;
		}

		$content = strtr($content,array(
			"{name}"        => $name,
			"{title}"       => $title,
			"{keywords}"    => $keywords,
			"{description}" => $description,
		));
		return $content;
	}
}