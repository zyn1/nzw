<?php
/**
 * @brief 检索装修公司类
 * @date 2017-1-23 09:02:34
 * @author zyn
 */
class search_company
{

	/**
	 * @brief [条件检索url处理]对于query url中已经存在的数据进行删除;没有的参数进行添加
	 * @param string or array $queryKey 字段名称
	 * @param string or array $queryVal 字段值
	 */
	public static function searchUrl($queryKey,$queryVal = '')
	{
		if(is_array($queryKey))
		{
			$condition = array();
			foreach($queryKey as $key => $colum)
			{
				$columValue = is_array($queryVal) ? $queryVal[$key] : $queryVal;
				$condition[]= $colum."=".$columValue;
			}
			$condition = join("&",$condition);
		}
		else
		{
			$condition = $queryKey."=".$queryVal;
		}

		//生成解析本次要检索的条件
		parse_str($condition,$inputArray);

		//解析当前URL已经存在的条件
		$queryArray  = array();
		$orgUrl      = IUrl::getUri();
		$parseUrlArr = parse_url($orgUrl);
		if(isset($parseUrlArr['query']) && $parseUrlArr['query'])
		{
			parse_str($parseUrlArr['query'], $queryArray);
		}

		//对条件进行替换产生最终的条件结果
		$resultQuery = array_replace_recursive($queryArray,$inputArray);
		$resultQuery = IFilter::emptyArray($resultQuery);

		//产生跳转的URL地址
		$parseUrlArr['query'] = "?".http_build_query($resultQuery);
		return join($parseUrlArr);
	}

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
		return array('cou' =>'综合排序');
	}

	/**
	 * @brief 商品检索,可以直接读取 $_GET 全局变量:attr,order,brand,min_price,max_price
	 *        在检索商品过程中计算商品结果中的进一步属性和规格的筛选
	 * @param mixed $defaultWhere string(条件) or array('search' => '模糊查找','category_extend' => '商品分类ID','commend_extend'=>'推荐类型id','字段' => 对应数据)
	 * @param int $limit 读取数量
	 * @param bool $isCondition 是否筛选出商品的属性，价格等数据
	 * @return IQuery
	 */
	public static function find($defaultWhere = '',$limit = 21,$isCondition = true)
	{
		//排序字段
		$orderArray = array();

		//开始查询
		$companyObj           = new IQuery("company as c");
		$companyObj->page     = isset($_GET['page']) ? intval($_GET['page']) : 1;
		$companyObj->fields   = 'u.head_ico,c.user_id,c.true_name,c.address';
		$companyObj->pagesize = $limit;

		/*where条件拼接*/
        $where = array('c.is_del = 0 and c.is_lock = 0');
		$join  = array();
        $join[] = ' left join user as u on u.id = c.user_id';

		//(3),处理defaultWhere条件
		if($defaultWhere)
		{
			//兼容array 和 string 数据类型的条件筛选
			$companyCondArray = array();
			if(is_string($defaultWhere))
			{
				$companyCondArray[] = $defaultWhere;
			}
			else if(is_array($defaultWhere))
			{
				foreach($defaultWhere as $key => $val)
				{
					if($val === '' || $val === null)
					{
						continue;
					}
					else if($key == 'search')
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
									$wordWhere[]     = ' c.true_name like "%'.$word.'%" ';
									$wordLikeOrder[] = $word;
								}

								//分词排序
								if($wordLikeOrder)
								{
									$orderTempArray = array();
									foreach($wordLikeOrder as $key => $val)
									{
										$orderTempArray[] = "(CASE WHEN c.true_name LIKE '%".$val."%' THEN ".$key." ELSE 100 END)";
									}
									$orderArray[] = " (".join('+',$orderTempArray).") asc ";
								}
							}
						}

						//存在分词结果
						if($wordWhere)
						{
							$companyCondArray[] = join(" and ",$wordWhere);
						}
						else
						{
							$companyCondArray[] = ' c.true_name like "%'.$defaultWhere['search'];
						}
					}
					//其他条件
					else
					{
						$companyCondArray[] = $key.' = "'.$val.'"';
					}
				}
			}

			//where 条件
			if($companyCondArray)
			{
				$where[] = "(".join(" and ",$companyCondArray).")";
			}
		}
        
        $type = IReq::get('type');
        $style = IReq::get('style');
        $price = IReq::get('price');
        if($type !== null || $style !== null || $price !== null)
        {
            $join[] = ' left join case as ca on ca.user_id = c.user_id';
            $companyObj->group    = 'c.user_id';
            if($type !== null)
            {
                $where[] = 'ca.type = '.$type;
            }
            if($style !== null)
            {
                $where[] = 'find_in_set("'.$style.'",ca.style)';
            }
            if($price !== null)
            {
                switch($price)
                {
                    case 1:
                        $where[] = 'ca.price <= 3';
                        break;
                    case 2:
                        $where[] = 'ca.price >= 3 and ca.price <= 5';
                        break;
                    case 3:
                        $where[] = 'ca.price >= 5 and ca.price <= 8';
                        break;
                    case 4:
                        $where[] = 'ca.price >= 8 and ca.price <= 10';
                        break;
                    case 5:
                        $where[] = 'ca.price >= 12 and ca.price <= 18';
                        break;
                    case 6:
                        $where[] = 'ca.price >= 18 and ca.price <= 30';
                        break;
                    case 7:
                        $where[] = 'ca.price >= 30 and ca.price <= 100';
                        break;
                    case 8:
                        $where[] = 'ca.price >= 100';
                        break;
                }
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
            //综合
            case "cou":
            {
                $orderArray[] = ' c.user_id '.$by;
            }
            break;

            //根据排序字段
            default:
            {
                $orderArray[] = ' c.sort asc ';
            }
        }
        

		//设置IQuery类的各个属性
		$companyObj->join  = join(" ",array_filter($join));
		$companyObj->where = join(" and ",array_filter($where));
		$companyObj->order = join(',',array_filter($orderArray));
		return $companyObj;
	}
}