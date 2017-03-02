<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file countsum.php
 * @brief 计算购物车中的商品价格
 * @author chendeshan
 * @date 2011-02-24
 * @version 0.6
 */
class CountSum
{
	//用户ID
	public $user_id = 0;

	//用户组ID
	public $group_id = '';

	//用户组折扣
	public $group_discount = '';

	//错误信息
	public $error = '';

	/**
	 * 构造函数
	 */
	public function __construct($user_id = 0)
	{
		if($user_id)
		{
			$this->user_id = $user_id;
		}
		else
		{
			$userCheckRights = IWeb::$app->getController()->user;
			$this->user_id = ( isset($userCheckRights['user_id']) && $userCheckRights['user_id'] ) ? $userCheckRights['user_id'] : 0;
		}

		//获取用户组ID及组的折扣率
		if($this->user_id)
		{
			$groupObj = new IModel('member as m , user_group as g');
			$groupRow = $groupObj->getObj('m.user_id = '.$this->user_id.' and m.group_id = g.id','g.*');
			if($groupRow)
			{
				$this->group_id       = $groupRow['id'];
				$this->group_discount = $groupRow['discount'] * 0.01;
			}
		}
	}

	/**
	 * 获取会员组价格
	 * @param $id   int    商品或货品ID
	 * @param $type string goods:商品; product:货品
	 * @return float 价格
	 */
	public function getGroupPrice($id,$type = 'goods')
	{
		if(!$this->group_id)
		{
			return null;
		}

		//1,查询特定商品的组价格
		$groupPriceDB = new IModel('group_price');
		if($type == 'goods')
		{
			$discountRow = $groupPriceDB->getObj('goods_id = '.$id.' and group_id = '.$this->group_id,'price');
		}
		else
		{
			$discountRow = $groupPriceDB->getObj('product_id = '.$id.' and group_id = '.$this->group_id,'price');
		}

		if($discountRow)
		{
			return $discountRow['price'];
		}

		//2,根据会员折扣率计算商品折扣
		if($this->group_discount)
		{
			if($type == 'goods')
			{
				$goodsDB  = new IModel('goods');
				$goodsRow = $goodsDB->getObj('id = '.$id,'sell_price');
				return $goodsRow ? Util::priceFormat($goodsRow['sell_price'] * $this->group_discount) : null;
			}
			else
			{
				$productDB  = new IModel('products');
				$productRow = $productDB->getObj('id = '.$id,'sell_price');
				return $productRow ? Util::priceFormat($productRow['sell_price'] * $this->group_discount) : null;
			}
		}
		return null;
	}

	/**
	 * @brief 计算商品价格
	 * @param Array $buyInfo ,购物车格式
	 * @promo string 活动类型 团购，抢购
	 * @active_id int 活动ID
	 * @return array or bool
	 */
	public function goodsCount($buyInfo,$promo='',$active_id='')
	{
		$this->sum           = 0;       //原始总额(优惠前)
		$this->final_sum     = 0;       //应付总额(优惠后)
    	$this->weight        = 0;       //总重量
    	$this->reduce        = 0;       //减少总额
    	$this->count         = 0;       //总数量
    	$this->promotion     = array(); //促销活动规则文本
    	$this->proReduce     = 0;       //促销活动规则优惠额
    	$this->point         = 0;       //增加积分
    	$this->exp           = 0;       //增加经验
    	$this->freeFreight   = array(); //商家免运费,免运费的商家ID,自营ID为0
    	$this->tax           = 0;       //商品税金
    	$this->seller        = array(); //商家商品总额统计, 商家ID => 商品金额

		$user_id      = $this->user_id;
		$group_id     = $this->group_id;
    	$goodsList    = array();
    	$productList  = array();
        
        $userDB = new IModel('user');
        $userRow = $userDB->getObj('id = '.$this->user_id, 'type,relate_id');

		//活动购买情况
    	if($promo && $active_id)
    	{
    		$ac_type    = isset($buyInfo['goods']) && $buyInfo['goods']['id'] ? "goods" : "product";
    		$ac_id      = current($buyInfo[$ac_type]['id']);
    		$ac_buy_num = $buyInfo[$ac_type]['data'][$ac_id]['count'];

			//开启促销活动
	    	$activeObject = new Active($promo,$active_id,$user_id,$ac_id,$ac_type,$ac_buy_num);
	    	$activeResult = $activeObject->checkValid();
	    	if($activeResult === true)
	    	{
	    		$typeRow  = $activeObject->originalGoodsInfo;
	    		$disPrice = $activeObject->activePrice;

				//设置优惠价格，如果不存在则优惠价等于商品原价
				$typeRow['reduce'] = $typeRow['sell_price'] - $disPrice;
				$typeRow['count']  = $ac_buy_num;
    			$current_sum_all   = $typeRow['sell_price'] * $ac_buy_num;
    			$current_reduce_all= $typeRow['reduce']     * $ac_buy_num;
				$typeRow['sum']    = $current_sum_all - $current_reduce_all;

    			if(!isset($this->seller[$typeRow['seller_id']]))
    			{
    				$this->seller[$typeRow['seller_id']] = 0;
    			}
    			$this->seller[$typeRow['seller_id']] += $typeRow['sum'];

    			//全局统计
		    	$this->weight += $typeRow['weight'] * $ac_buy_num;
		    	$this->point  += $typeRow['point']  * $ac_buy_num;
		    	$this->exp    += $typeRow['exp']    * $ac_buy_num;
		    	$this->sum    += $current_sum_all;
		    	$this->reduce += $current_reduce_all;
		    	$this->count  += $ac_buy_num;
		    	$this->tax    += self::getGoodsTax($typeRow['sum'],$typeRow['seller_id']);
		    	$typeRow == "goods" ? ($goodsList[] = $typeRow) : ($productList[] = $typeRow);
	    	}
	    	else
	    	{
	    		$this->error .= $activeResult;
	    		return $activeResult;
	    	}
    	}
    	else
    	{            
            $cartObj = new Cart();
			/*开始计算goods和product的优惠信息 , 会根据条件分析出执行以下哪一种情况:
			 *(1)查看此商品(货品)是否已经根据不同会员组设定了优惠价格;
			 *(2)当前用户是否属于某个用户组中的成员，并且此用户组享受折扣率;
			 *(3)优惠价等于商品(货品)原价;
			 */

			//获取商品或货品数据
			/*Goods 拼装商品优惠价的数据*/
	    	if(isset($buyInfo['goods']['id']) && $buyInfo['goods']['id'])
	    	{
	    		//购物车中的商品数据
	    		$goodsIdStr = join(',',$buyInfo['goods']['id']);
	    		$goodsObj   = new IModel('goods as go');
                $goodsList  = $goodsObj->query('go.id in ('.$goodsIdStr.')','go.name,go.cost_price,go.channel_price,go.id as goods_id,go.img,go.sell_price,go.market_price,go.point,go.weight,go.store_nums,go.exp,go.goods_no,0 as product_id,go.seller_id');
                if($userRow && $userRow['type'] == 4)
                {
	    		    $goodsListSelf  = $goodsObj->query('go.id in ('.$goodsIdStr.') and seller_id != '.$userRow['relate_id'],'go.name,go.cost_price,go.channel_price,go.id as goods_id,go.img,go.sell_price,go.market_price,go.point,go.weight,go.store_nums,go.exp,go.goods_no,0 as product_id,go.seller_id');
                    $cou1 = count($goodsList);
                    $cou2 = count($goodsListSelf);
                    if(($cou1 > $cou2) && $cou2 > 0)
                    {
                        //删除购物车中自己店铺的商品
                        $temp  = $goodsObj->query('go.id in ('.$goodsIdStr.') and seller_id = '.$userRow['relate_id'],'go.id as gid');
                        foreach($temp as $v)
                        {
                            $cartObj->del($v['gid'], 'goods');
                        }
                        unset($temp);                        
                        $goodsList = $goodsListSelf;
                    }
                    elseif(($cou1 > $cou2) && $cou2 == 0)
                    {
                        $cartObj->clear();
                        $this->error .= '不能购买自己店铺的商品';
                        return $this->error;
                    }
                }

	    		//开始优惠情况判断
	    		foreach($goodsList as $key => $val)
	    		{
                    //检查是否是自己商家的商品
                    
	    			//检查库存
	    			if($buyInfo['goods']['data'][$val['goods_id']]['count'] <= 0 || $buyInfo['goods']['data'][$val['goods_id']]['count'] > $val['store_nums'])
	    			{
	    				$goodsList[$key]['name'] .= "【无库存】";
	    				$this->error .= "<商品：".$val['name']."> 购买数量超出库存，请重新调整购买数量。";
	    			}

	    			$groupPrice                = $this->getGroupPrice($val['goods_id'],'goods');
                    //渠道商购买使用渠道价
	    			$goodsList[$key]['reduce'] = $userRow['type'] != 1 ? ($val['channel_price'] == 0 ? 0 : $val['sell_price'] - $val['channel_price']) :($groupPrice === null ? 0 : $val['sell_price'] - $groupPrice);
	    			$goodsList[$key]['count']  = $buyInfo['goods']['data'][$val['goods_id']]['count'];
	    			$current_sum_all           = $goodsList[$key]['sell_price'] * $goodsList[$key]['count'];
	    			$current_reduce_all        = $goodsList[$key]['reduce']     * $goodsList[$key]['count'];
	    			$goodsList[$key]['sum']    = $current_sum_all - $current_reduce_all;
	    			if(!isset($this->seller[$val['seller_id']]))
	    			{
	    				$this->seller[$val['seller_id']] = 0;
	    			}
	    			$this->seller[$val['seller_id']] += $goodsList[$key]['sum'];

	    			//全局统计
			    	$this->weight += $val['weight'] * $goodsList[$key]['count'];
			    	$this->point  += $val['point']  * $goodsList[$key]['count'];
			    	$this->exp    += $val['exp']    * $goodsList[$key]['count'];
			    	$this->sum    += $current_sum_all;
			    	$this->reduce += $current_reduce_all;
			    	$this->count  += $goodsList[$key]['count'];
			    	$this->tax    += self::getGoodsTax($goodsList[$key]['sum'],$val['seller_id']);
			    }
	    	}

			/*Product 拼装商品优惠价的数据*/
	    	if(isset($buyInfo['product']['id']) && $buyInfo['product']['id'])
	    	{
	    		//购物车中的货品数据
	    		$productIdStr = join(',',$buyInfo['product']['id']);
	    		$productObj   = new IQuery('products as pro,goods as go');
	    		$productObj->where  = 'pro.id in ('.$productIdStr.') and go.id = pro.goods_id';
	    		$productObj->fields = 'pro.sell_price,pro.market_price,pro.cost_price,pro.channel_price,pro.weight,pro.id as product_id,pro.spec_array,pro.goods_id,pro.store_nums,pro.products_no as goods_no,go.name,go.point,go.exp,go.img,go.seller_id';
	    		$productList  = $productObj->find();
                
                if($userRow && $userRow['type'] == 4)
                {
                    $productObj->where  = 'pro.id in ('.$productIdStr.') and go.id = pro.goods_id and go.seller_id != '.$userRow['relate_id'];
                    $productObj->fields = 'pro.sell_price,pro.market_price,pro.cost_price,pro.channel_price,pro.weight,pro.id as product_id,pro.spec_array,pro.goods_id,pro.store_nums,pro.products_no as goods_no,go.name,go.point,go.exp,go.img,go.seller_id';
                    $productListSelf  = $productObj->find();
                    
                    $cou1 = count($productList);
                    $cou2 = count($productListSelf);
                    if(($cou1 > $cou2) && $cou2 > 0)
                    {
                        //删除购物车中自己店铺的商品
                        $productObj->where  = 'pro.id in ('.$productIdStr.') and go.id = pro.goods_id and go.seller_id = '.$userRow['relate_id'];
                        $productObj->fields = 'pro.id as pid';
                        $temp = $productObj->find();
                        foreach($temp as $v)
                        {
                            $cartObj->del($v['pid'], 'product');
                        }
                        unset($temp);
                        $productList = $productListSelf;
                    }
                    elseif(($cou1 > $cou2) && $cou2 == 0)
                    {
                        $cartObj->clear();
                        $this->error .= '不能购买自己店铺的商品';
                        return $this->error;
                    }
                }

	    		//开始优惠情况判断
	    		foreach($productList as $key => $val)
	    		{
	    			//检查库存
	    			if($buyInfo['product']['data'][$val['product_id']]['count'] <= 0 || $buyInfo['product']['data'][$val['product_id']]['count'] > $val['store_nums'])
	    			{
	    				$productList[$key]['name'] .= "【无库存】";
	    				$this->error .= "<货品：".$val['name']."> 购买数量超出库存，请重新调整购买数量。";
	    			}

	    			$groupPrice                  = $this->getGroupPrice($val['product_id'],'product');
                    //渠道商购买时使用渠道价
					$productList[$key]['reduce'] = $userRow['type'] != 1 ? ($val['channel_price'] == 0 ? 0 : $val['sell_price'] - $val['channel_price']) : ($groupPrice === null ? 0 : $val['sell_price'] - $groupPrice);
	    			$productList[$key]['count']  = $buyInfo['product']['data'][$val['product_id']]['count'];
	    			$current_sum_all             = $productList[$key]['sell_price']  * $productList[$key]['count'];
	    			$current_reduce_all          = $productList[$key]['reduce']      * $productList[$key]['count'];
	    			$productList[$key]['sum']    = $current_sum_all - $current_reduce_all;
	    			if(!isset($this->seller[$val['seller_id']]))
	    			{
	    				$this->seller[$val['seller_id']] = 0;
	    			}
	    			$this->seller[$val['seller_id']] += $productList[$key]['sum'];

	    			//全局统计
			    	$this->weight += $val['weight'] * $productList[$key]['count'];
			    	$this->point  += $val['point']  * $productList[$key]['count'];
			    	$this->exp    += $val['exp']    * $productList[$key]['count'];
			    	$this->sum    += $current_sum_all;
			    	$this->reduce += $current_reduce_all;
			    	$this->count  += $productList[$key]['count'];
			    	$this->tax    += self::getGoodsTax($productList[$key]['sum'],$val['seller_id']);
			    }
	    	}

	    	//总金额满足的促销规则
	    	if($user_id)
	    	{
	    		//计算每个商家促销规则
	    		foreach($this->seller as $seller_id => $sum)
	    		{
			    	$proObj = new ProRule($sum,$seller_id);
			    	$proObj->setUserGroup($group_id);
			    	if($proObj->isFreeFreight() == true)
			    	{
			    		$this->freeFreight[] = $seller_id;
			    	}
			    	$this->promotion = array_merge($proObj->getInfo(),$this->promotion);
			    	$this->proReduce += $sum - $proObj->getSum();
	    		}
	    	}
    	}

    	$this->final_sum = $this->sum - $this->reduce - $this->proReduce;
    	$this->final_sum = $this->final_sum <= 0 ? 0 : $this->final_sum;
    	$resultList      = array_merge($goodsList,$productList);
    	if(!$resultList)
    	{
    		$this->error .= "商品信息不存在";
    	}

    	return array(
    		'final_sum'  => $this->final_sum,
    		'promotion'  => $this->promotion,
    		'proReduce'  => $this->proReduce,
    		'sum'        => $this->sum,
    		'goodsList'  => $resultList,
    		'count'      => $this->count,
    		'reduce'     => $this->reduce,
    		'weight'     => $this->weight,
    		'point'      => $this->point,
    		'exp'        => $this->exp,
    		'tax'        => $this->tax,
    		'freeFreight'=> $this->freeFreight,
    		'seller'     => $this->seller,
            'extendRe'   => $userRow['type'] <> 1 ? 1 : 0
    	);
	}

	//购物车计算
	public function cart_count($id = '',$type = '',$buy_num = 1,$promo='',$active_id='', $buyInfo = array())
	{
		//单品购买
		if($id && $type)
		{
			$type = ($type == "goods") ? "goods" : "product";
            
			//规格必填
			if($type == "goods")
			{
                $productsDB = new IModel('products');
				if($productsDB->getObj('goods_id = '.$id))
				{
					$this->error .= '请先选择商品的规格';
					return $this->error;
				}
			}

    		$buyInfo = array(
    			$type => array('id' => array($id),'data' => array($id => array('count' => $buy_num)),'count' => $buy_num)
    		);
		}
		else if(empty($buyInfo))
		{
			//获取购物车中的商品和货品信息
	    	$cartObj = new Cart();
	    	$buyInfo = $cartObj->getMyCart();
		}
    	return $this->goodsCount($buyInfo,$promo,$active_id);
    }

    /**
     * 计算订单信息,其中部分计算都是以商品原总价格计算的$goodsSum
     * @param $goodsResult array CountSum结果集
     * @param $province_id int 省份ID
     * @param $delievery_id int 配送方式ID
     * @param $payment_id int 支付ID
     * @param $is_invoice int 是否要发票
     * @param $discount float 订单的加价或者减价
     * @param $promo string 促销活动
     * @param $active_id int 促销活动ID
     * @return $result 最终的返回数组
     */
    public function countOrderFee($goodsResult,$province_id,$delivery_id,$payment_id,$is_invoice,$discount = 0,$promo = '',$active_id = '',$if_protected = 1)
    {
    	//根据商家进行商品分组
    	$sellerGoods = array();
    	foreach($goodsResult['goodsList'] as $key => $val)
    	{
    		if(!isset($sellerGoods[$val['seller_id']]))
    		{
    			$sellerGoods[$val['seller_id']] = array();
    		}
    		$sellerGoods[$val['seller_id']][] = $val;
    	}

		$cartObj = new Cart();
    	foreach($sellerGoods as $seller_id => $item)
    	{
    		$num          = array();
    		$productID    = array();
    		$goodsID      = array();
    		$goodsArray   = array();
    		$productArray = array();
    		foreach($item as $key => $val)
    		{
    			$goodsID[]   = $val['goods_id'];
    			$productID[] = $val['product_id'];
    			$num[]       = $val['count'];
	    		if($val['product_id'] > 0)
	    		{
	    			$productArray[$val['product_id']] = $val['count'];
	    		}
	    		else
	    		{
	    			$goodsArray[$val['goods_id']] = $val['count'];
	    		}
    		}
    		$sellerData = $this->goodsCount($cartObj->cartFormat(array("goods" => $goodsArray,"product" => $productArray)),$promo,$active_id);
	    	if(is_string($sellerData))
	    	{
				return $sellerData;
	    	}

	    	$deliveryList = Delivery::getDelivery($province_id,$delivery_id,$goodsID,$productID,$num);
	    	if(is_string($deliveryList))
	    	{
				return $deliveryList;
	    	}

			//有促销免运费活动
			if(isset($sellerData['freeFreight']) && $sellerData['freeFreight'])
			{
				foreach($sellerData['freeFreight'] as $sid)
				{
					if(isset($deliveryList['seller_price'][$sid]))
					{
						$deliveryList['price'] -= $deliveryList['seller_price'][$sid];
						$deliveryList['seller_price'][$sid] = 0;
					}
				}
			}

	    	$extendArray = array(
	    		'deliveryOrigPrice' => $deliveryList['org_price'],
	    		'deliveryPrice'     => $deliveryList['price'],
	    		'insuredPrice'      => $if_protected ? $deliveryList['protect_price'] : 0,
	    		'taxPrice'          => $is_invoice == true ? $sellerData['tax'] : 0,
	    		'paymentPrice'      => $payment_id != 0 ? self::getGoodsPaymentPrice($payment_id,$sellerData['final_sum']) : 0,
	    		'goodsResult'       => $sellerData,
	    		'orderAmountPrice'  => 0,
	    	);
	    	$orderAmountPrice = array_sum(array(
		    	$sellerData['final_sum'],
		    	$deliveryList['price'],
		    	$extendArray['insuredPrice'],
		    	$extendArray['taxPrice'],
		    	$extendArray['paymentPrice'],
		    	$discount,
	    	));

			$extendArray['orderAmountPrice'] = $orderAmountPrice <= 0 ? 0 : round($orderAmountPrice,2);
			$sellerGoods[$val['seller_id']]  = array_merge($sellerData,$extendArray);
    	}
    	return $sellerGoods;
    }

    /**
     * 获取商品的税金
     * @param $goodsSum float 商品总价格
     * @param $seller_id int 商家ID
     * @return $goodsTaxPrice float 商品的税金
     */
    public static function getGoodsTax($goodsSum,$seller_id = 0)
    {
    	if($seller_id)
    	{
    		$sellerDB = new IModel('seller');
    		$sellerRow= $sellerDB->getObj('id = '.$seller_id);
    		$tax_per  = $sellerRow['tax'];
    	}
    	else
    	{
			$siteConfigObj = new Config("site_config");
			$site_config   = $siteConfigObj->getInfo();
			$tax_per       = isset($site_config['tax']) ? $site_config['tax'] : 0;
    	}
		$goodsTaxPrice = $goodsSum * ($tax_per * 0.01);
		return round($goodsTaxPrice,2);
    }

    /**
     * 获取商品金额的支付费用
     * @param $payment_id int 支付方式ID
     * @param $goodsSum float 商品总价格
     * @return $goodsPayPrice
     */
    public static function getGoodsPaymentPrice($payment_id,$goodsSum)
    {
		$paymentObj = new IModel('payment');
		$paymentRow = $paymentObj->getObj('id = '.$payment_id,'poundage,poundage_type');

		if($paymentRow)
		{
			if($paymentRow['poundage_type'] == 1)
			{
				//按照百分比
				return $goodsSum * ($paymentRow['poundage'] * 0.01);
			}
			//按照固定金额
			return $paymentRow['poundage'];
		}
		return 0;
    }

    /**
     * @brief 获取商户订单货款结算
     * @param int $seller_id 商户ID
     * @param datetime $start_time 订单开始时间
     * @param datetime $end_time 订单结束时间
     * @param string $is_checkout 是否已经结算 0:未结算; 1:已结算; null:不限
     * @param IQuery 结果集对象
     */
    public static function getSellerGoodsFeeQuery($seller_id = '',$start_time = '',$end_time = '',$is_checkout = '')
    {
        $where  = "status in (5,6,7) and pay_type != 0 and pay_status = 1 and distribution_status in (1,2)";
        $where .= $is_checkout !== '' ? " and is_checkout = ".$is_checkout : "";
        $where .= $seller_id          ? " and seller_id = ".$seller_id : "";
        $where .= $start_time         ? " and create_time >= '{$start_time}' " : "";
        //可以结算所选结束日期当天完成的订单
        $where .= $end_time           ? " and create_time <= '{$end_time} 23:59:59' "   : "";

        $orderGoodsDB = new IQuery('order');
        $orderGoodsDB->order = "id desc";
        $orderGoodsDB->where = $where;
        return $orderGoodsDB;
    }

	/**
	 * @brief 计算商户货款及其他费用
	 * @param array $orderList 订单数据关联
	 * @return array(
	 * 'orderAmountPrice' => 订单金额（去掉pay_fee支付手续费）,'refundFee' => 退款金额, 'orgCountFee' => 原始结算金额,
	 * 'countFee' => 实际结算金额, 'platformFee' => 平台促销活动金额(代金券等平台补贴给商家),'commission' => '手续费' ,'commissionPer' => '手续费比率',
	 * 'orderNum' => 订单数量, 'order_ids' => 订单IDS,'orderNoList' => 订单编号
	 * ),
	 */
    public static function countSellerOrderFee($orderList)
    {
    	$result = array(
			'orderAmountPrice' => 0,
			'refundFee'        => 0,
			'orgCountFee'      => 0,
			'countFee'         => 0,
			'platformFee'      => 0,
			'commission'       => 0,
			'commissionPer'    => 0,
			'orderNum'         => count($orderList),
			'order_ids'        => array(),
            'orderNoList'      => array(),
            
            //zyn  新增
            'orderRealAmount'  => 0,
            'orderRealFreight' => 0,
            'refundGoodsPrice' => 0,
			'refundDeliveryPrice' => 0,
    	);
    	if($orderList && is_array($orderList))
    	{
    		$refundObj = new IModel("refundment_doc");
    		$propObj   = new IModel("prop");
            $orderGoodsObj = new IModel('order_goods');
    		foreach($orderList as $key => $item)
    		{
    			//检查平台促销活动
    			//1,代金券
    			if($item['prop'])
    			{
    				$propRow = $propObj->getObj('id = '.$item['prop'].' and type = 0');
    				if($propRow && $propRow['seller_id'] == 0)
    				{
    					$propRow['value'] = min($item['real_amount'],$propRow['value']);
    					$result['platformFee'] += $propRow['value'];
    				}
    			}

    			$result['orderAmountPrice'] += $item['order_amount'] - $item['pay_fee'];
                
                //zyn  新增  分开计算结算商品价格及运费
                $result['orderRealAmount'] += $item['real_amount'] - $item['pay_fee'];
                
                //运费加保价费用
                $result['orderRealFreight'] += $item['real_freight']+$item['insured'];
                //end
                
    			$result['order_ids'][]       = $item['id'];
    			$result['orderNoList'][]     = $item['order_no'];

    			//是否存在退款
    			$refundList = $refundObj->query("order_id = ".$item['id'].' and pay_status = 2 and type = 1');
    			foreach($refundList as $k => $val)
    			{                    
    				$result['refundFee'] += $val['amount'];
                    
                    //zyn  新增  分开计算结算订单退款商品价格及运费
                    $goodsPrice = $orderGoodsObj->getObj('id in ('.$val['order_goods_id'].')', 'sum(real_price) as price');
                    $result['refundGoodsPrice'] += $goodsPrice['price'];
                    $result['refundDeliveryPrice'] += $val['amount'] - $goodsPrice['price'];
                    //end
    			}
    		}
    	}

		//应该结算金额
		$result['orgCountFee'] = $result['orderAmountPrice'] - $result['refundFee'] + $result['platformFee'];

		//获取结算手续费
		$siteConfigData = new Config('site_config');
		$result['commissionPer'] = $siteConfigData->commission ? $siteConfigData->commission : 0;
		//$result['commission']    = round($result['orgCountFee'] * $result['commissionPer']/100,2);
        
        //zyn  修改  运费不扣手续费
        $result['orgRealFee'] = $result['orderRealAmount'] - $result['refundGoodsPrice'] + $result['platformFee'];
        $result['orgDeliveryFee'] = $result['orderRealFreight'] - $result['refundDeliveryPrice'];
        $result['commission']    = round($result['orgRealFee'] * $result['commissionPer']/100,2);
        //end

		//最终结算金额
		$result['countFee'] = $result['orgCountFee'] - $result['commission'];

    	return $result;
    }

    /**
     * @brief 获取分红商户订单
     * @param datetime $start_time 订单开始时间
     * @param datetime $end_time 订单结束时间
     * @param IQuery 结果集对象
     */
    public static function getSellerGoodsBonusQuery($start_time = '',$end_time = '')
    {
        $where  = "o.status in (5,6,7) and o.pay_type != 0 and o.pay_status = 1 and o.distribution_status in (1,2)";
        $where .= $start_time         ? " and o.create_time >= '{$start_time}' " : "";
        //可以结算所选结束日期当天完成的订单
        $where .= $end_time           ? " and o.create_time <= '{$end_time} 23:59:59' "   : "";

        $orderGoodsDB = new IQuery('order_extend as od');
        $orderGoodsDB->join = 'left join order as o on o.id = od.order_id';
        $orderGoodsDB->fields = 'od.*,o.order_no,o.seller_id,o.user_id,o.create_time,o.completion_time,o.order_amount'; 
        $orderGoodsDB->order = "o.id desc";
        $orderGoodsDB->where = $where;
        return $orderGoodsDB;
    }
}