<?php
/**
 * @brief 检索商家
 * @date 2016-9-22 14:36:12
 * @author zyn
 */
class search_seller
{

	/**
	 * @brief 获取排列顺序： asc,desc
	 * @param string 排列字段 sort,new...来自 self::getOrderType()
	 * @return string asc:正序;desc:倒序
	 */
	public static function getOrderBy($order)
	{
		$issetOrder = IReq::get('order');
		$issetBy    = IReq::get('by');
		if($order == $issetOrder && $issetBy == "asc")
		{
			return "desc";
		}
		return "asc";
	}

	/**
	 * @brief 获取总的排序方式
	 * @return array(代号 => 名字)
	 */
	public static function getOrderType()
	{
		return array('default' =>'默认排序','sale' =>'销量','grade' =>'信用');
	}

	/**
	 * @param int $limit 读取数量
	 *
	 * @return IQuery
	 */
	public static function find($defaultWhere = '',$limit = 20)
	{
		//排序字段
		$orderArray = array();

		//开始查询
		$sellerObj           = new IQuery("seller as s");
		$sellerObj->page     = isset($_GET['page']) ? intval($_GET['page']) : 1;
		$sellerObj->fields   = 's.id,s.true_name,s.seller_logo,s.sale,s.sales,s.province,s.city,s.area';
		$sellerObj->pagesize = $limit;
		$sellerObj->group    = 's.id';

		/*where条件拼接*/
		//(1),当前产品分类
		$where = array('s.is_del = 0 and s.is_lock = 0');

		//(3),处理defaultWhere条件 goods, category_extend
		if($defaultWhere)
		{
			//兼容array 和 string 数据类型的goods条件筛选
			$sellerCondArray = array();
			if(is_string($defaultWhere))
			{
				$sellerCondArray[] = $defaultWhere;
			}
			else if(is_array($defaultWhere))
			{
				foreach($defaultWhere as $key => $val)
				{
					if($val === '' || $val === null)
					{
						continue;
					}
					//搜索词模糊
					if($key == 'search')
					{
						$wordWhere     = array();
						$wordLikeOrder = array();

						//进行分词
						if(IString::getStrLen($defaultWhere['search']) >= 4 || IString::getStrLen($defaultWhere['search']) <= 100)
						{
							$wordData = plugin::trigger("onSearchGoodsWordsPart",$defaultWhere['search']);
							if(isset($wordData['data']) && count($wordData['data']) >= 2)
							{
								foreach($wordData['data'] as $word)
								{
									$wordWhere[]     = ' go.name like "%'.$word.'%" ';
									$wordLikeOrder[] = $word;
								}

								//分词排序
								if($wordLikeOrder)
								{
									$orderTempArray = array();
									foreach($wordLikeOrder as $key => $val)
									{
										$orderTempArray[] = "(CASE WHEN s.true_name LIKE '%".$val."%' THEN ".$key." ELSE 100 END)";
									}
									$orderArray[] = " (".join('+',$orderTempArray).") asc ";
								}
							}
						}

						//存在分词结果
						if($wordWhere)
						{
							$sellerCondArray[] = join(" and ",$wordWhere);
						}
						else
						{
							$sellerCondArray[] = ' s.true_name like "%'.$defaultWhere['search'].'%"';
						}
					}
					//其他条件
					else
					{
						$sellerCondArray[] = $key.' = "'.$val.'"';
					}
				}
			}

			//goods 条件
			if($sellerCondArray)
			{
				$where[] = "(".join(" and ",$sellerCondArray).")";
			}
		}
        
		//排序类别
		$order = IReq::get('order');
		$by    = IReq::get('by') == "desc" ? "desc" : "asc";
		if($order == null)
		{
			//获取配置信息
			$siteConfigObj = new Config("site_config");
			$site_config   = $siteConfigObj->getInfo();
			$order         = isset($site_config['order_by']) ? $site_config['order_by'] :'';
		}

		switch($order)
		{
			//销售量
			case "sale":
			{
				$orderArray[] = ' s.sale '.$by;
			}
			break;

			//评分
			case "grade":
			{
				$orderArray[] = ' s.grade '.$by;
			}
			break;

			//根据排序字段
			default:
			{
				$orderArray[] = ' s.is_vip desc,s.is_recomm desc,s.sort asc ';
			}
		}

		//设置IQuery类的各个属性
		$sellerObj->where = join(" and ",array_filter($where));
		$sellerObj->order = join(',',array_filter($orderArray));
		return $sellerObj;
	}
}