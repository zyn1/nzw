<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file Simple.php
 * @brief
 * @author webning
 * @date 2011-03-22
 * @version 0.6
 * @note
 */
/**
 * @brief Simple
 * @class Simple
 * @note
 */
class Simple extends IController
{
    public $layout='site';

	function init()
	{

	}

	function login()
	{
		//如果已经登录，就跳到ucenter页面
		if($this->user)
		{
			$this->redirect("/ucenter/index");
		}
		else
		{
            $this->layout = "site_log";
			$this->redirect('login');
		}
	}

	//退出登录
    function logout()
    {
    	plugin::trigger('clearUser');
        if($this->user['type'] == 4)
        {
            plugin::trigger('clearSeller');
        }
    	$this->redirect('login');
    }
    
    function reg()
    {
        if($this->user)
        {
            $this->redirect("/ucenter/index");
        }
        else{
            $this->layout = 'site_log';
            $this->redirect('reg');
        }
        
    }
    // 企业用户认证
    function reg_identify()
    {
        if($this->user)
        {
            $this->redirect("/ucenter/index");
        }
        else{
            $this->layout = 'site_log';
            $id = IReq::get('_i');
            $this->code = IReq::get('_c');
            $db = new IModel('company');
            $data = $db->getObj('user_id = '.$id, 'address');
            $this->id = $id ;
            $this->address = $data['address'];
            $this->redirect('reg_identify');
        }
        
    }
    function identify_success()
    {
        $code = IFilter::act(IReq::get('code','post'));
        $id = IFilter::act(IReq::get('id','post')); 
        $address = IFilter::act(IReq::get('address', 'post'));
        $_code   = ISafe::get('user_code_'.$id);
        if($code != $_code)
        {
            $this->setError('参数错误');
            $this->redirect('/simple/reg_identify/_i/'.$id.'/_c/'.$_code,false);
            Util::showMessage('参数错误');
        }
        
        $card_type   = IFilter::act(IReq::get('card_type'),'int');
        
        //文件上传
        if((isset($_FILES['paper_img']['name']) && $_FILES['paper_img']['name']) || (isset($_FILES['head_ico']['name']) && $_FILES['head_ico']['name']) || (isset($_FILES['paper_imgs']['name']) && $_FILES['paper_imgs']['name']) || (isset($_FILES['tax_img']['name']) && $_FILES['tax_img']['name']) || (isset($_FILES['code_img']['name']) && $_FILES['code_img']['name']) || (isset($_FILES['identity_card']['name']) && $_FILES['identity_card']['name']))
        {
            $uploadObj = new PhotoUpload();
            $uploadObj->setIterance(false);
            $photoInfo = $uploadObj->run();
        }
        if($card_type == 1)
        {
            if(isset($photoInfo['paper_img']['img']) && file_exists($photoInfo['paper_img']['img']))
            {
                $company['paper_img'] = JSON::encode(array('paper_img' => $photoInfo['paper_img']['img']));
            }
            else
            {
                $this->setError('请上传营业执照');
                $this->redirect('/simple/reg_identify/_i/'.$id.'/_c/'.$_code,false);
                Util::showMessage('请上传营业执照');
            }
        }
        else
        {
            $paperData = array();
            if(isset($photoInfo['paper_imgs']['img']) && file_exists($photoInfo['paper_imgs']['img']))
            {
                $paperData['paper_imgs'] = $photoInfo['paper_imgs']['img'];
            }
            else
            {
                $this->setError('请上传营业执照');
                $this->redirect('/simple/reg_identify/_i/'.$id.'/_c/'.$_code,false);
                Util::showMessage('请上传营业执照');
            }
            if(isset($photoInfo['tax_img']['img']) && file_exists($photoInfo['tax_img']['img']))
            {
                $paperData['tax_img'] = $photoInfo['tax_img']['img'];
            }
            else
            {
                $this->setError('请上传税务登记证');
                $this->redirect('/simple/reg_identify/_i/'.$id.'/_c/'.$_code,false);
                Util::showMessage('请上传税务登记证');
            }
            if(isset($photoInfo['code_img']['img']) && file_exists($photoInfo['code_img']['img']))
            {
                $paperData['code_img'] = $photoInfo['code_img']['img'];
            }
            else
            {
                $this->setError('请上传组织机构代码证');
                $this->redirect('/simple/reg_identify/_i/'.$id.'/_c/'.$_code,false);
                Util::showMessage('请上传组织机构代码证');
            }
            $company['paper_img'] = JSON::encode($paperData);
        }

        if(isset($photoInfo['identity_card']['img']) && file_exists($photoInfo['identity_card']['img']))
        {
            $company['identity_card'] = $photoInfo['identity_card']['img'];
        }
        $company['phone'] = IFilter::act(IReq::get('phone','post'));
        $companyDB = new IModel('company');
        $companyDB->setData($company);
        $companyDB->update('user_id = '.$id);
        $this->layout = 'site_log';
        $this->redirect('identify_success');        
    }
    //用户注册
    function reg_act()
    {
    	//调用_userInfo注册插件
    	$result = plugin::trigger("userRegAct",$_POST);
    	if(is_array($result))
    	{
			//自定义跳转页面
            if(isset($_POST['t']) && $_POST['t'] == 2)
            {
                $code = rand(100000,999999);
                ISafe::set("user_code_".$result['id'],$code);
                $this->redirect('/simple/reg_identify/_i/'.$result['id'].'/_c/'.$code);
            }
            else
            {
			    $this->redirect('/site/success?message='.urlencode("注册成功！"));
            }
    	}
    	else
    	{
    		$this->setError($result);
    		$this->redirect('reg',false);
    		Util::showMessage($result);
    	}
    }
    
    //用户注册验证数据可用性
    function verifyAbled()
    {
        $tableName = IReq::get('tableName');
        $fields = IReq::get('fields');
        $value = IReq::get('value');
        $DB = new IModel($tableName);
        if($value && $row = $DB->getObj($fields." = '".$value."'"))
        {
            if(($fields == 'email' && $row['status'] <> 3) || $fields <> 'email')
            {
                echo 0;
            }
            else
            {
                echo 1;
            }
        }
        else
        {
            echo 1;
        }
    }

    //用户登录
    function login_act()
    {
    	//调用_userInfo登录插件
		$result = plugin::trigger('userLoginAct',$_POST);
		if(is_array($result))
		{
			//自定义跳转页面
			$callback = plugin::trigger('getCallback');
			if($callback)
			{
				$this->redirect($callback);
			}
			else
			{
				$this->redirect('/ucenter/index');
			}
		}
		else
		{
			$this->setError($result);
			$this->redirect('login',false);
			Util::showMessage($result);
		}
    }

    //商品加入购物车[ajax]
    function joinCart()
    {
    	$link      = IReq::get('link');
    	$goods_id  = IFilter::act(IReq::get('goods_id'),'int');
    	$goods_num = IReq::get('goods_num') === null ? 1 : intval(IReq::get('goods_num'));
		$type      = IFilter::act(IReq::get('type'));

		//加入购物车
    	$cartObj   = new Cart();
    	$addResult = $cartObj->add($goods_id,$goods_num,$type);

    	if($link != '')
    	{
    		if($addResult === false)
    		{
    			$this->cart(false);
    			Util::showMessage($cartObj->getError());
    		}
    		else
    		{
    			$this->redirect($link);
    		}
    	}
    	else
    	{
	    	if($addResult === false)
	    	{
		    	$result = array(
		    		'isError' => true,
		    		'message' => $cartObj->getError(),
		    	);
	    	}
	    	else
	    	{
		    	$result = array(
		    		'isError' => false,
		    		'message' => '添加成功',
		    	);
	    	}
	    	echo JSON::encode($result);
    	}
    }

    //根据goods_id获取货品
    function getProducts()
    {
    	$id           = IFilter::act(IReq::get('id'),'int');
    	$productObj   = new IModel('products');
    	$productsList = $productObj->query('goods_id = '.$id,'sell_price,id,spec_array,goods_id','store_nums desc',7);
		if($productsList)
		{
			foreach($productsList as $key => $val)
			{
				$productsList[$key]['specData'] = Block::show_spec($val['spec_array']);
			}
		}
		echo JSON::encode($productsList);
    }

    //删除购物车
    function removeCart()
    {
    	$link      = IReq::get('link');
    	$goods_id  = IFilter::act(IReq::get('goods_id'),'int');
    	$type      = IFilter::act(IReq::get('type'));

    	$cartObj   = new Cart();
    	$cartInfo  = $cartObj->getMyCart();
    	$delResult = $cartObj->del($goods_id,$type);

    	if($link != '')
    	{
    		if($delResult === false)
    		{
    			$this->cart(false);
    			Util::showMessage($cartObj->getError());
    		}
    		else
    		{
    			$this->redirect($link);
    		}
    	}
    	else
    	{
	    	if($delResult === false)
	    	{
	    		$result = array(
		    		'isError' => true,
		    		'message' => $cartObj->getError(),
	    		);
	    	}
	    	else
	    	{
		    	$goodsRow = $cartInfo[$type]['data'][$goods_id];
		    	$cartInfo['sum']   -= $goodsRow['sell_price'] * $goodsRow['count'];
		    	$cartInfo['count'] -= $goodsRow['count'];

		    	$result = array(
		    		'isError' => false,
		    		'data'    => $cartInfo,
		    	);
	    	}

	    	echo JSON::encode($result);
    	}
    }

    //清空购物车
    function clearCart()
    {
    	$cartObj = new Cart();
    	$cartObj->clear();
    	$this->redirect('cart');
    }

    //购物车div展示
    function showCart()
    {
    	$cartObj  = new Cart();
    	$cartList = $cartObj->getMyCart();
    	$data['data'] = array_merge($cartList['goods']['data'],$cartList['product']['data']);
    	$data['count']= $cartList['count'];
    	$data['sum']  = $cartList['sum'];
    	echo JSON::encode($data);
    }

    //购物车页面及商品价格计算[复杂]
    function cart($redirect = false)
    {
    	//防止页面刷新
    	header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);

        $user_id       = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
         
		//开始计算购物车中的商品价格
    	$countObj = new CountSum($user_id);
    	$result   = $countObj->cart_count();

    	if(is_string($result))
    	{
    		IError::show($result,403);
    	}

    	//返回值
    	$this->final_sum = $result['final_sum'];
    	$this->promotion = $result['promotion'];
    	//$this->proReduce = $result['proReduce'];
    	$this->sum       = $result['sum'];
    	$this->goodsList = $result['goodsList'];
        if(IClient::getDevice() == IClient::MOBILE)
        {
            $sellerDB = new IModel('seller');
            $sellerGoods = array();
            foreach($result['goodsList'] as $v)
            {
                if(!isset($sellerGoods[$v['seller_id']]['seller_name']))
                {
                    $sellerRow = $sellerDB->getObj('id ='.$v['seller_id'], 'true_name');
                    $sellerGoods[$v['seller_id']]['true_name'] = $sellerRow['true_name'];
                }
                $sellerGoods[$v['seller_id']]['goodsList'][] = $v;
            }
            $this->goodsList = $sellerGoods;
        }
    	$this->count     = $result['count'];
    	//$this->reduce    = $result['reduce'];
    	//$this->weight    = $result['weight'];

		//渲染视图
    	$this->redirect('cart',$redirect);
    }

    //计算促销规则[ajax]
    function promotionRuleAjax()
    {
    	$goodsId   = IFilter::act(IReq::get("goodsId"),'int');
    	$productId = IFilter::act(IReq::get("productId"),'int');
    	$num       = IFilter::act(IReq::get("num"),'int');

    	if(!$goodsId || !$num)
    	{
			return;
    	}

		$goodsArray  = array();
		$productArray= array();

    	foreach($goodsId as $key => $goods_id)
    	{
    		$pid = $productId[$key];
    		$nVal= $num[$key];

    		if($pid > 0)
    		{
    			$productArray[$pid] = $nVal;
    		}
    		else
    		{
    			$goodsArray[$goods_id] = $nVal;
    		}
    	}

		$countSumObj    = new CountSum();
		$cartObj        = new Cart();
		$countSumResult = $countSumObj->goodsCount($cartObj->cartFormat(array("goods" => $goodsArray,"product" => $productArray)));
    	echo JSON::encode($countSumResult);
    }
    
    //保存发票信息
    function invoiceSafe()
    {
        $user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
        if($user_id == 0)
        {
            $this->redirect('/simple/login?tourist&callback=/simple/cart2');
        }
        ICookie::set('invoice'.$user_id, JSON::encode($_POST));
        $this->redirect('/simple/cart2');
    }
    
    //开发票页面
    function invoice()
    {
        $user_id = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
        if($user_id == 0)
        {
            $this->redirect('/simple/login?tourist&callback=/simple/cart2');
        }
        $this->invoiceData = JSON::decode(ICookie::get('invoice'.$user_id));
        $this->redirect('invoice');
    }

    //填写订单信息cart2
    function cart2()
    {
        //游客的user_id默认为0
        $user_id       = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
        $addressId = IReq::get('addressId') ? IReq::get('addressId') : (isset($data['addressId']) ? $data['addressId'] : 0);
        
        if(empty($_POST))
        {
            $_POST = JSON::decode(ICookie::get('cart2'.$user_id));
            $_POST['addressId'] = $addressId;
        }
        $id        = IFilter::act(IReq::get('id'),'int');
        $type      = IFilter::act(IReq::get('type'));//goods,product
        $promo     = IFilter::act(IReq::get('promo'));
        $active_id = IFilter::act(IReq::get('active_id'),'int');
        $buy_num   = IReq::get('num') ? IFilter::act(IReq::get('num'),'int') : 1;
        $tourist   = IReq::get('tourist');//游客方式购物
        $checked = IReq::get('sub');
        
        //记录数据
        ICookie::set('cart2'.$user_id, JSON::encode($_POST));
        
        //是否开发票
        $this->invoiceData = JSON::decode(ICookie::get('invoice'.$user_id));

    	//必须为登录用户
    	if($tourist === null && $this->user['user_id'] == null)
    	{
    		if($id == 0 || $type == '')
    		{
    			$this->redirect('/simple/login?tourist&callback=/simple/cart2');
    		}
    		else
    		{
    			$url  = '/simple/login?tourist&callback=/simple/cart2/id/'.$id.'/type/'.$type.'/num/'.$buy_num;
    			$url .= $promo     ? '/promo/'.$promo         : '';
    			$url .= $active_id ? '/active_id/'.$active_id : '';
    			$this->redirect($url);
    		}
    	}
        
        $cartData = array();
        //计算商品
        $countSumObj = new CountSum($user_id);
        if($id && $type)
        {
            $result = $countSumObj->cart_count($id,$type,$buy_num,$promo,$active_id);
        }
        else if(empty($checked))
        {
            $this->redirect('cart');
        }
        else
        {
            $goodsdata = $_POST;
            foreach($checked as $key=>$val){//转换成购物车的数据结构
                $tem = explode('-',$val);
                if(isset($goodsdata[$val]))
                {
                    $cartData[$tem[0]]['id'][] = intval($tem[1]);
                    $cartData[$tem[0]]['data'][intval($tem[1])] = array('count' => intval($goodsdata[$val]));
                    $cartData[$tem[0]]['data']['count'] = intval($goodsdata[$val]);
                }
            }
            $result = $countSumObj->cart_count($id,$type,$buy_num,$promo,$active_id,$cartData);   
        }     
		if($countSumObj->error)
		{
			IError::show(403,$countSumObj->error);
		}

    	//获取收货地址
    	$addressObj  = new IModel('address');
        $addressDetail = array();
    	$addressList = $addressObj->query('user_id = '.$user_id,"*","is_default desc");

		//更新$addressList数据
    	foreach($addressList as $key => $val)
    	{
    		$temp = area::name($val['province'],$val['city'],$val['area']);
    		if(isset($temp[$val['province']]) && isset($temp[$val['city']]) && isset($temp[$val['area']]))
    		{
	    		$addressList[$key]['province_val'] = $temp[$val['province']];
	    		$addressList[$key]['city_val']     = $temp[$val['city']];
	    		$addressList[$key]['area_val']     = $temp[$val['area']];
    		}
    	}
        if($addressId)
        {
            $addressDetail = $addressObj->getObj('user_id = '.$user_id.' and id = '.$addressId,"*");
        }
        if($addressDetail)
        {
            $temp = area::name($addressDetail['province'],$addressDetail['city'],$addressDetail['area']);
            if(isset($temp[$addressDetail['province']]) && isset($temp[$addressDetail['city']]) && isset($temp[$addressDetail['area']]))
            {
                $addressDetail['province_val'] = $temp[$addressDetail['province']];
                $addressDetail['city_val']     = $temp[$addressDetail['city']];
                $addressDetail['area_val']     = $temp[$addressDetail['area']];
            }
            $defaultAddress = $addressDetail;
        }
        else
        {
            $defaultAddress = empty($addressList) ? array() : $addressList[0];
        }

		//获取习惯方式
		$memberObj = new IModel('member');
		$memberRow = $memberObj->getObj('user_id = '.$user_id,'custom');
		if(isset($memberRow['custom']) && $memberRow['custom'])
		{
			$this->custom = unserialize($memberRow['custom']);
		}
		else
		{
			$this->custom = array(
				'payment'  => '',
				'delivery' => '',
			);
		}

    	//返回值
		$this->gid       = $id;
		$this->type      = $type;
		$this->num       = $buy_num;
		$this->promo     = $promo;
		$this->active_id = $active_id;
    	$this->final_sum = $result['final_sum'];
    	$this->promotion = $result['promotion'];
    	$this->proReduce = $result['proReduce'];
    	$this->sum       = $result['sum'];
    	$this->goodsList = $result['goodsList'];
    	$this->count       = $result['count'];
    	$this->reduce      = $result['reduce'];
    	$this->weight      = $result['weight'];
    	$this->freeFreight = $result['freeFreight'];
    	$this->seller      = $result['seller'];
        if(IClient::getDevice() == IClient::MOBILE)
        {
            $sellerDB = new IModel('seller');
            $sellerGoods = array();
            foreach($result['goodsList'] as $v)
            {
                if(!isset($sellerGoods[$v['seller_id']]['seller_name']))
                {
                    $sellerRow = $sellerDB->getObj('id ='.$v['seller_id'], 'true_name, seller_logo');
                    $sellerGoods[$v['seller_id']]['true_name'] = $sellerRow['true_name'];
                    $sellerGoods[$v['seller_id']]['seller_logo'] = $sellerRow['seller_logo'];
                }
                $sellerGoods[$v['seller_id']]['goodsList'][] = $v;
            }
            $this->goodsList = $sellerGoods;
        }

		//收货地址列表
		$this->addressList = $addressList;
        
        //默认地址
        $this->defaultAddress = $defaultAddress;

		//获取商品税金
		$this->goodsTax    = $result['tax'];

    	//渲染页面
    	$this->redirect('cart2');
    }

    /**
     * 生成订单
     */
    function cart3()
    {
        $address_id    = IFilter::act(IReq::get('radio_address'),'int');
        $delivery_id   = IFilter::act(IReq::get('delivery_id'),'int');
        $accept_time   = IFilter::act(IReq::get('accept_time'));
        $payment       = IFilter::act(IReq::get('payment'),'int');
        $order_message = IFilter::act(IReq::get('message'));
        $ticket_id     = IFilter::act(IReq::get('ticket_id'),'int');
        $taxes         = IFilter::act(IReq::get('taxes'),'float');
        $gid           = IFilter::act(IReq::get('direct_gid'),'int');
        $num           = IFilter::act(IReq::get('direct_num'),'int');
        $type          = IFilter::act(IReq::get('direct_type'));//商品或者货品
        $promo         = IFilter::act(IReq::get('direct_promo'));
        $active_id     = IFilter::act(IReq::get('direct_active_id'),'int');
        $takeself      = IFilter::act(IReq::get('takeself'),'int');
        $order_type    = 0;
        $dataArray     = array();
        $user_id       = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
        $invoice       = isset($_POST['taxes']) ? 1 : 0;
        
        //是否保价
        $if_protected = IFilter::act(IReq::get('if_protected'),'int');

        //获取商品数据信息
        $countSumObj = new CountSum($user_id);
        if($gid)
        {
            $goodsResult = $countSumObj->cart_count($gid,$type,$num,$promo,$active_id);
        }
        else
        {
            $goodsData     = IFilter::act(IReq::get('goods'));
            if(count($goodsData)==0){$this->redirect('cart');return false;}
            $cartData = array();
            $delCart = array();
            foreach($goodsData as $val){
                $tem =explode('-',$val);
                $cartData[$tem[0]]['id'][] = intval($tem[1]);
                $cartData[$tem[0]]['data'][intval($tem[1])] = array('count' => intval($tem[2]));
                $cartData[$tem[0]]['data']['count'] = intval($tem[2]);
                $delCart[$tem[1]] = $tem[0];
            }
            //计算购物车中的商品价格$goodsResult
            $goodsResult = $countSumObj->cart_count($gid,$type,$num,$promo,$active_id,$cartData);
        }

        if($countSumObj->error)
        {
            IError::show(403,$countSumObj->error);
        }

        //处理收件地址
        //1,访客; 2,注册用户
        if($user_id == 0)
        {
            $addressRow = ISafe::get('address');
        }
        else
        {
            $addressDB = new IModel('address');
            $addressRow= $addressDB->getObj('id = '.$address_id.' and user_id = '.$user_id);
        }

        if(!$addressRow)
        {
            IError::show(403,"收货地址信息不存在");
        }
        $accept_name   = IFilter::act($addressRow['accept_name'],'name');
        $province      = $addressRow['province'];
        $city          = $addressRow['city'];
        $area          = $addressRow['area'];
        $address       = IFilter::act($addressRow['address']);
        $mobile        = IFilter::act($addressRow['mobile'],'mobile');
        $telphone      = IFilter::act($addressRow['telphone'],'phone');
        $zip           = IFilter::act($addressRow['zip'],'zip');
        
        $tax_title     = IReq::get('tax_title') ? IFilter::act(IReq::get('tax_title')) : $accept_name;
        //检查订单重复
        $checkData = array(
            "accept_name" => $accept_name,
            "address"     => $address,
            "mobile"      => $mobile,
            "distribution"=> $delivery_id,
        );
        $result = order_class::checkRepeat($checkData,$goodsResult['goodsList']);
        if( is_string($result) )
        {
            IError::show(403,$result);
        }
        
        //配送方式,判断是否为货到付款
        $deliveryObj = new IModel('delivery');
        $deliveryRow = $deliveryObj->getObj('id = '.$delivery_id);
        if(!$deliveryRow)
        {
            IError::show(403,'配送方式不存在');
        }

        if($deliveryRow['type'] == 0)
        {
            if($payment == 0)
            {
                IError::show(403,'请选择正确的支付方式');
            }
        }
        else if($deliveryRow['type'] == 1)
        {
            $payment = 0;
        }
        else if($deliveryRow['type'] == 2)
        {
            if($takeself == 0)
            {
                IError::show(403,'请选择正确的自提点');
            }
        }
        //如果不是自提方式自动清空自提点
        if($deliveryRow['type'] != 2)
        {
            $takeself = 0;
        }

        if(!empty($delCart))
        {
            $cart = new Cart();
            foreach($delCart as $k => $v)
            {
                $cart->del($k, $v);
            }
        }

        //判断商品是否存在
        if(is_string($goodsResult) || empty($goodsResult['goodsList']))
        {
            IError::show(403,'商品数据错误');
        }

        //加入促销活动
        if($promo && $active_id)
        {
            $activeObject = new Active($promo,$active_id,$user_id,$gid,$type,$num);
            $order_type = $activeObject->getOrderType();
        }

        $paymentObj = new IModel('payment');
        $paymentRow = $paymentObj->getObj('id = '.$payment,'type,name');
        if(!$paymentRow)
        {
            IError::show(403,'支付方式不存在');
        }
        $paymentName= $paymentRow['name'];
        $paymentType= $paymentRow['type'];

        //最终订单金额计算
        $orderData = $countSumObj->countOrderFee($goodsResult,$province,$delivery_id,$payment,$taxes,0,$promo,$active_id,$if_protected);
        if(is_string($orderData))
        {
            IError::show(403,$orderData);
            exit;
        }
        //根据商品所属商家不同批量生成订单
        $orderIdArray  = array();
        $orderNumArray = array();
        $final_sum     = 0;
        foreach($orderData as $seller_id => $goodsResult)
        {
            //生成的订单数据
            $dataArray = array(
                'order_no'            => Order_Class::createOrderNum(),
                'user_id'             => $user_id,
                'accept_name'         => $accept_name,
                'pay_type'            => $payment,
                'distribution'        => $delivery_id,
                'postcode'            => $zip,
                'telphone'            => $telphone,
                'province'            => $province,
                'city'                => $city,
                'area'                => $area,
                'address'             => $address,
                'mobile'              => $mobile,
                'create_time'         => ITime::getDateTime(),
                'postscript'          => $order_message,
                'accept_time'         => $accept_time,
                'exp'                 => $goodsResult['exp'],
                'point'               => $goodsResult['point'],
                'type'                => $order_type,

                //商品价格
                'payable_amount'      => $goodsResult['sum'],
                'real_amount'         => $goodsResult['final_sum'],

                //运费价格
                'payable_freight'     => $goodsResult['deliveryOrigPrice'],
                'real_freight'        => $goodsResult['deliveryPrice'],

                //手续费
                'pay_fee'             => $goodsResult['paymentPrice'],

                //税金
                'invoice'             => $invoice,
                'invoice_title'       => $tax_title,
                'taxes'               => $goodsResult['taxPrice'],

                //优惠价格
                'promotions'          => $goodsResult['proReduce'] + $goodsResult['reduce'],

                //订单应付总额
                'order_amount'        => $goodsResult['orderAmountPrice'],

                //订单保价
                'insured'             => $goodsResult['insuredPrice'],

                //自提点ID
                'takeself'            => $takeself,

                //促销活动ID
                'active_id'           => $active_id,

                //商家ID
                'seller_id'           => $seller_id,

                //备注信息
                'note'                => '',
            );

            //获取红包减免金额
            if($ticket_id)
            {
                $memberObj = new IModel('member');
                $memberRow = $memberObj->getObj('user_id = '.$user_id,'prop,custom');
                foreach($ticket_id as $tk => $tid)
                {
                    //游客手动添加或注册用户道具中已有的代金券
                    if(ISafe::get('ticket_'.$tid) == $tid || stripos(','.trim($memberRow['prop'],',').',',','.$tid.',') !== false)
                    {
                        $propObj   = new IModel('prop');
                        $ticketRow = $propObj->getObj('id = '.$tid.' and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1');
                        if(!$ticketRow)
                        {
                            IError::show(403,'代金券不可用');
                        }

                        if($ticketRow['seller_id'] == 0 || $ticketRow['seller_id'] == $seller_id)
                        {
                            $ticketRow['value']         = $ticketRow['value'] >= $goodsResult['final_sum'] ? $goodsResult['final_sum'] : $ticketRow['value'];
                            $dataArray['prop']          = $tid;
                            $dataArray['promotions']   += $ticketRow['value'];
                            $dataArray['order_amount'] -= $ticketRow['value'];
                            $goodsResult['promotion'][] = array("plan" => "代金券","info" => "使用了￥".$ticketRow['value']."代金券");

                            //锁定红包状态
                            $propObj->setData(array('is_close' => 2));
                            $propObj->update('id = '.$tid);

                            unset($ticket_id[$tk]);
                            break;
                        }
                    }
                }
            }

            //促销规则
            if(isset($goodsResult['promotion']) && $goodsResult['promotion'])
            {
                foreach($goodsResult['promotion'] as $key => $val)
                {
                    $dataArray['note'] .= join("，",$val)."。";
                }
            }

            $dataArray['order_amount'] = $dataArray['order_amount'] <= 0 ? 0 : $dataArray['order_amount'];

            //生成订单插入order表中
            $orderObj  = new IModel('order');
            $orderObj->setData($dataArray);
            $order_id = $orderObj->add();

            if($order_id == false)
            {
                IError::show(403,'订单生成错误');
            }

            /*将订单中的商品插入到order_goods表*/
            $orderInstance = new Order_Class();
            $orderInstance->insertOrderGoods($order_id,$goodsResult['goodsResult']);

            //订单金额小于等于0直接免单
            if($dataArray['order_amount'] <= 0)
            {
                Order_Class::updateOrderStatus($dataArray['order_no']);
            }
            else
            {
                $orderIdArray[]  = $order_id;
                $orderNumArray[] = $dataArray['order_no'];
                $final_sum      += $dataArray['order_amount'];
            }
            
            if($invoice){
                $db_fapiao = new IModel('order_fapiao');
                $fapiao_data = array(
                        'order_id'=> $order_id,
                        'money' => $goodsResult['orderAmountPrice'],
                        'status' => 0,
                        'user_id' => $user_id,
                        'type'    => IReq::get('fapiao_type'),
                        'create_time'=> ITime::getDateTime(),
                        'taitou' => $tax_title,
                        'seller_id' => $seller_id
                );
                if($fapiao_data['type']==1){
                    $fapiao_data['com'] = IFilter::act(IReq::get('tax_com'));
                    $fapiao_data['tax_no']= IFilter::act(IReq::get('tax_no'));
                    $fapiao_data['address'] = IFilter::act(IReq::get('tax_address'));
                    $fapiao_data['telphone'] = IFilter::act(IReq::get('tax_telphone'));
                    $fapiao_data['bank'] = IFilter::act(IReq::get('tax_bank'));
                    $fapiao_data['account'] = IFilter::act(IReq::get('tax_account'));
                }
                $db_fapiao->setData($fapiao_data);
                $db_fapiao->add();
            }
            
            //渠道商购买材料记录分红数据
            if($goodsResult['extendRe'])
            {
                $orderExtendDB = new IModel('order_extend');
                $data = array(
                            'order_id' => $order_id,
                            'bonus_amount' => $goodsResult['reduce']
                        );
                $userDB = new IModel('user');
                $model = new IModel('operational_user');
                $userRow = $userDB->getObj('id = '.$user_id, 'type,relate_id');
                $para = array();
                
                //卖方运营中心
                if($userDB->getObj('relate_id = '.$seller_id.' and type = 4','id'))
                {
                    $para['sales'] = $seller_id;
                }
                else
                {
                    $sales = $model->getObj('object_id = '.$seller_id.' and type = 2', 'operation_id');
                    $sales ? $para['sales'] = $sales['operation_id'] : '';
                }
                
                //买方运营中心
                $para['buys'] = $model->getObj('object_id = '.$user_id.' and type = 1', 'operation_id');
                
                //预约装修及预约设计师功能未做，做完相关功能后可在此添加所签约装修公司和签约设计师参与分红
                //companys  装修公司   designers  设计师
                
                $data['para'] = JSON::encode($para);
                $orderExtendDB->setData($data);
                $orderExtendDB->add();                
            }
        }

        //记录用户默认习惯的数据
        if(!isset($memberRow['custom']))
        {
            $memberObj = new IModel('member');
            $memberRow = $memberObj->getObj('user_id = '.$user_id,'custom');
        }

        $memberData = array(
            'custom' => serialize(
                array(
                    'payment'  => $payment,
                    'delivery' => $delivery_id,
                )
            ),
        );
        $memberObj->setData($memberData);
        $memberObj->update('user_id = '.$user_id);

        //收货地址的处理
        if($user_id)
        {
            $addressDefRow = $addressDB->getObj('user_id = '.$user_id.' and is_default = 1');
            if(!$addressDefRow)
            {
                $addressDB->setData(array('is_default' => 1));
                $addressDB->update('user_id = '.$user_id.' and id = '.$address_id);
            }
        }

        //获取备货时间
        $this->stockup_time = $this->_siteConfig->stockup_time ? $this->_siteConfig->stockup_time : 2;

        //数据渲染
        $this->order_id    = join("_",$orderIdArray);
        $this->final_sum   = $final_sum;
        $this->order_num   = join(",",$orderNumArray);
        $this->payment     = $paymentName;
        $this->paymentType = $paymentType;
        $this->delivery    = $deliveryRow['name'];
        $this->tax_title   = $tax_title;
        $this->deliveryType= $deliveryRow['type'];
        plugin::trigger('setCallback','/ucenter/order');
        //订单金额为0时，订单自动完成
        if($this->final_sum <= 0)
        {
            $this->redirect('/site/success/message/'.urlencode("订单确认成功，等待发货"));
        }
        else
        {
            $this->setRenderData($dataArray);
            $this->redirect('cart3');
        }
    }

	/**
	 * 手机端生成订单
	 */
    function cart35()
    {
    	$address_id    = IFilter::act(IReq::get('radio_address'),'int');
    	$delivery_id   = IFilter::act(IReq::get('delivery_id'),'int');
    	$accept_time   = IFilter::act(IReq::get('accept_time'));
    	$payment       = IFilter::act(IReq::get('payment'),'int');
    	$order_message = IFilter::act(IReq::get('message'));
    	$ticket_id     = IFilter::act(IReq::get('ticket_id'),'int');
    	$taxes         = IFilter::act(IReq::get('taxes'),'float');
    	$gid           = IFilter::act(IReq::get('direct_gid'),'int');
    	$num           = IFilter::act(IReq::get('direct_num'),'int');
    	$type          = IFilter::act(IReq::get('direct_type'));//商品或者货品
    	$promo         = IFilter::act(IReq::get('direct_promo'));
    	$active_id     = IFilter::act(IReq::get('direct_active_id'),'int');
    	$takeself      = IFilter::act(IReq::get('takeself'),'int');
    	$order_type    = 0;
    	$dataArray     = array();
    	$user_id       = ($this->user['user_id'] == null) ? 0 : $this->user['user_id'];
        
        //是否保价
        $if_protected = IFilter::act(IReq::get('if_protected'),'int');
        
        //获取发票信息
        $invoiceData   = JSON::decode(ICookie::get('invoice'.$user_id));

		//获取商品数据信息
    	$countSumObj = new CountSum($user_id);
        if($gid)
        {
            $goodsResult = $countSumObj->cart_count($gid,$type,$num,$promo,$active_id);
        }
		else
        {
            $goodsData     = IFilter::act(IReq::get('goods'));
            if(count($goodsData)==0){$this->redirect('cart');return false;}
            $cartData = array();
            $delCart = array();
            foreach($goodsData as $val){
                $tem =explode('-',$val);
                $cartData[$tem[0]]['id'][] = intval($tem[1]);
                $cartData[$tem[0]]['data'][intval($tem[1])] = array('count' => intval($tem[2]));
                $cartData[$tem[0]]['data']['count'] = intval($tem[2]);
                $delCart[$tem[1]] = $tem[0];
            }
            //计算购物车中的商品价格$goodsResult
            $goodsResult = $countSumObj->cart_count($gid,$type,$num,$promo,$active_id,$cartData);
        }

        $return = array();
		if($countSumObj->error)
		{
            $return['code'] = 0;
			$return['msg'] = $countSumObj->error;
            echo JSON::encode($return);exit;
		}

		//处理收件地址
		//1,访客; 2,注册用户
		if($user_id == 0)
		{
			$addressRow = ISafe::get('address');
		}
		else
		{
			$addressDB = new IModel('address');
			$addressRow= $addressDB->getObj('id = '.$address_id.' and user_id = '.$user_id);
		}

		if(!$addressRow)
		{
            $return['code'] = 0;
            $return['msg'] = '收货地址信息不存在';
            echo JSON::encode($return);exit;
		}
    	$accept_name   = IFilter::act($addressRow['accept_name'],'name');
    	$province      = $addressRow['province'];
    	$city          = $addressRow['city'];
    	$area          = $addressRow['area'];
    	$address       = IFilter::act($addressRow['address']);
    	$mobile        = IFilter::act($addressRow['mobile'],'mobile');
    	$telphone      = IFilter::act($addressRow['telphone'],'phone');
        $zip           = IFilter::act($addressRow['zip'],'zip');
        
        $tax_title     = $invoiceData['tax_title'] ? IFilter::act($invoiceData['tax_title']) : $accept_name;
		//检查订单重复
    	$checkData = array(
    		"accept_name" => $accept_name,
    		"address"     => $address,
    		"mobile"      => $mobile,
    		"distribution"=> $delivery_id,
    	);
    	$result = order_class::checkRepeat($checkData,$goodsResult['goodsList']);
    	if( is_string($result) )
    	{
            $return['code'] = 0;
            $return['msg'] = $result;
            echo JSON::encode($return);
            exit;
    	}
        
		//配送方式,判断是否为货到付款
		$deliveryObj = new IModel('delivery');
		$deliveryRow = $deliveryObj->getObj('id = '.$delivery_id);
		if(!$deliveryRow)
		{
            $return['code'] = 0;
            $return['msg'] = '配送方式不存在';
            echo JSON::encode($return);exit;
		}

		if($deliveryRow['type'] == 0)
		{
			if($payment == 0)
			{
                $return['code'] = 0;
                $return['msg'] = '请选择正确的支付方式';
                echo JSON::encode($return);exit;
			}
		}
		else if($deliveryRow['type'] == 1)
		{
			$payment = 0;
		}
		else if($deliveryRow['type'] == 2)
		{
			if($takeself == 0)
			{
                $return['code'] = 0;
                $return['msg'] = '请选择正确的自提点';
                echo JSON::encode($return);exit;
			}
		}
		//如果不是自提方式自动清空自提点
		if($deliveryRow['type'] != 2)
		{
			$takeself = 0;
		}

		if(!empty($delCart))
		{
            $cart = new Cart();
            foreach($delCart as $k => $v)
            {
                $cart->del($k, $v);
            }
		}

    	//判断商品是否存在
    	if(is_string($goodsResult) || empty($goodsResult['goodsList']))
    	{
            $return['code'] = 0;
            $return['msg'] = '商品数据错误';
            echo JSON::encode($return);exit;
    	}

    	//加入促销活动
    	if($promo && $active_id)
    	{
    		$activeObject = new Active($promo,$active_id,$user_id,$gid,$type,$num);
    		$order_type = $activeObject->getOrderType();
    	}

		$paymentObj = new IModel('payment');
		$paymentRow = $paymentObj->getObj('id = '.$payment,'type,name');
		if(!$paymentRow)
		{
            $return['code'] = 0;
            $return['msg'] = '支付方式不存在';
            echo JSON::encode($return);exit;
		}
		$paymentName= $paymentRow['name'];
		$paymentType= $paymentRow['type'];

		//最终订单金额计算
		$orderData = $countSumObj->countOrderFee($goodsResult,$province,$delivery_id,$payment,$taxes,0,$promo,$active_id,$if_protected);
		if(is_string($orderData))
		{
            $return['code'] = 0;
            $return['msg'] = $orderData;
            echo JSON::encode($return);exit;
		}
		//根据商品所属商家不同批量生成订单
		$orderIdArray  = array();
		$orderNumArray = array();
		$final_sum     = 0;
		foreach($orderData as $seller_id => $goodsResult)
		{
			//生成的订单数据
			$dataArray = array(
				'order_no'            => Order_Class::createOrderNum(),
				'user_id'             => $user_id,
				'accept_name'         => $accept_name,
				'pay_type'            => $payment,
				'distribution'        => $delivery_id,
				'postcode'            => $zip,
				'telphone'            => $telphone,
				'province'            => $province,
				'city'                => $city,
				'area'                => $area,
				'address'             => $address,
				'mobile'              => $mobile,
				'create_time'         => ITime::getDateTime(),
				'postscript'          => $order_message,
				'accept_time'         => $accept_time,
				'exp'                 => $goodsResult['exp'],
				'point'               => $goodsResult['point'],
				'type'                => $order_type,

				//商品价格
				'payable_amount'      => $goodsResult['sum'],
				'real_amount'         => $goodsResult['final_sum'],

				//运费价格
				'payable_freight'     => $goodsResult['deliveryOrigPrice'],
				'real_freight'        => $goodsResult['deliveryPrice'],

				//手续费
				'pay_fee'             => $goodsResult['paymentPrice'],

				//税金
				'invoice'             => $taxes ? 1 : 0,
				'invoice_title'       => $tax_title,
				'taxes'               => $goodsResult['taxPrice'],

				//优惠价格
				'promotions'          => $goodsResult['proReduce'] + $goodsResult['reduce'],

				//订单应付总额
				'order_amount'        => $goodsResult['orderAmountPrice'],

				//订单保价
				'insured'             => $goodsResult['insuredPrice'],

				//自提点ID
				'takeself'            => $takeself,

				//促销活动ID
				'active_id'           => $active_id,

				//商家ID
				'seller_id'           => $seller_id,

				//备注信息
				'note'                => '',
			);

			//获取红包减免金额
			if($ticket_id)
			{
				$memberObj = new IModel('member');
				$memberRow = $memberObj->getObj('user_id = '.$user_id,'prop,custom');
				foreach($ticket_id as $tk => $tid)
				{
					//游客手动添加或注册用户道具中已有的代金券
					if(ISafe::get('ticket_'.$tid) == $tid || stripos(','.trim($memberRow['prop'],',').',',','.$tid.',') !== false)
					{
						$propObj   = new IModel('prop');
						$ticketRow = $propObj->getObj('id = '.$tid.' and NOW() between start_time and end_time and type = 0 and is_close = 0 and is_userd = 0 and is_send = 1');
						if(!$ticketRow)
						{
                            $return['code'] = 0;
                            $return['msg'] = '代金券不可用';
                            echo JSON::encode($return);exit;
						}

						if($ticketRow['seller_id'] == 0 || $ticketRow['seller_id'] == $seller_id)
						{
							$ticketRow['value']         = $ticketRow['value'] >= $goodsResult['final_sum'] ? $goodsResult['final_sum'] : $ticketRow['value'];
							$dataArray['prop']          = $tid;
							$dataArray['promotions']   += $ticketRow['value'];
							$dataArray['order_amount'] -= $ticketRow['value'];
							$goodsResult['promotion'][] = array("plan" => "代金券","info" => "使用了￥".$ticketRow['value']."代金券");

							//锁定红包状态
							$propObj->setData(array('is_close' => 2));
							$propObj->update('id = '.$tid);

							unset($ticket_id[$tk]);
							break;
						}
					}
				}
			}

			//促销规则
			if(isset($goodsResult['promotion']) && $goodsResult['promotion'])
			{
				foreach($goodsResult['promotion'] as $key => $val)
				{
					$dataArray['note'] .= join("，",$val)."。";
				}
			}

			$dataArray['order_amount'] = $dataArray['order_amount'] <= 0 ? 0 : $dataArray['order_amount'];

			//生成订单插入order表中
			$orderObj  = new IModel('order');
			$orderObj->setData($dataArray);
			$order_id = $orderObj->add();

			if($order_id == false)
			{
                $return['code'] = 0;
                $return['msg'] = '订单生成错误';
                echo JSON::encode($return);exit;
			}

			/*将订单中的商品插入到order_goods表*/
	    	$orderInstance = new Order_Class();
	    	$orderInstance->insertOrderGoods($order_id,$goodsResult['goodsResult']);

			//订单金额小于等于0直接免单
			if($dataArray['order_amount'] <= 0)
			{
				Order_Class::updateOrderStatus($dataArray['order_no']);
			}
			else
			{
				$orderIdArray[]  = $order_id;
				$orderNumArray[] = $dataArray['order_no'];
				$final_sum      += $dataArray['order_amount'];
			}
            
            if($taxes){
                $db_fapiao = new IModel('order_fapiao');
                $fapiao_data = array(
                        'order_id'=> $order_id,
                        'money' => $goodsResult['orderAmountPrice'],
                        'status' => 0,
                        'user_id' => $user_id,
                        'type'    => $invoiceData['fapiao_type'],
                        'create_time'=> ITime::getDateTime(),
                        'taitou' => $tax_title,
                        'seller_id' => $seller_id
                );
                if($fapiao_data['type']==1){
                    $fapiao_data['com'] = IFilter::act($invoiceData['tax_com']);
                    $fapiao_data['tax_no']= IFilter::act($invoiceData['tax_no']);
                    $fapiao_data['address'] = IFilter::act($invoiceData['tax_address']);
                    $fapiao_data['telphone'] = IFilter::act($invoiceData['tax_telphone']);
                    $fapiao_data['bank'] = IFilter::act($invoiceData['tax_bank']);
                    $fapiao_data['account'] = IFilter::act($invoiceData['tax_account']);
                }
                $db_fapiao->setData($fapiao_data);
                $db_fapiao->add();
            }
            
            //渠道商购买材料记录分红数据
            if($goodsResult['extendRe'])
            {
                $orderExtendDB = new IModel('order_extend');
                $data = array(
                            'order_id' => $order_id,
                            'bonus_amount' => $goodsResult['reduce']
                        );
                $userDB = new IModel('user');
                $model = new IModel('operational_user');
                $userRow = $userDB->getObj('id = '.$user_id, 'type,relate_id');
                $para = array();
                
                //卖方运营中心
                if($userDB->getObj('relate_id = '.$seller_id.' and type = 4','id'))
                {
                    $para['sales'] = $seller_id;
                }
                else
                {
                    $sales = $model->getObj('object_id = '.$seller_id.' and type = 2', 'operation_id');
                    $sales ? $para['sales'] = $sales['operation_id'] : '';
                }
                
                //买方运营中心
                $para['buys'] = $model->getObj('object_id = '.$user_id.' and type = 1', 'operation_id');
                
                //预约装修及预约设计师功能未做，做完相关功能后可在此添加所签约装修公司和签约设计师参与分红 
                //companys  装修公司   designers  设计师
                
                $data['para'] = JSON::encode($para);
                $orderExtendDB->setData($data);
                $orderExtendDB->add();                
            }
		}

		//记录用户默认习惯的数据
		if(!isset($memberRow['custom']))
		{
			$memberObj = new IModel('member');
			$memberRow = $memberObj->getObj('user_id = '.$user_id,'custom');
		}

		$memberData = array(
			'custom' => serialize(
				array(
					'payment'  => $payment,
					'delivery' => $delivery_id,
				)
			),
		);
		$memberObj->setData($memberData);
		$memberObj->update('user_id = '.$user_id);

		//收货地址的处理
		if($user_id)
		{
			$addressDefRow = $addressDB->getObj('user_id = '.$user_id.' and is_default = 1');
			if(!$addressDefRow)
			{
				$addressDB->setData(array('is_default' => 1));
				$addressDB->update('user_id = '.$user_id.' and id = '.$address_id);
			}
		}

		//获取备货时间
		$this->stockup_time = $this->_siteConfig->stockup_time ? $this->_siteConfig->stockup_time : 2;

		plugin::trigger('setCallback','/ucenter/order');
        ICookie::clear('cart2'.$user_id);
        ICookie::clear('invoice'.$user_id);
		//订单金额为0时，订单自动完成
        $dataArray['code'] = 1;
        $dataArray['js_order_id'] = join("_",$orderIdArray);
        $dataArray['js_final_sum'] = $final_sum;
        $dataArray['js_paymentType'] = $paymentType;
        $dataArray['js_deliveryType'] = $deliveryRow['type'];
        $dataArray['js_delevery'] = $deliveryRow['name'];
        $dataArray['js_payment'] = $paymentName;
        $dataArray['js_order_num'] = join(",",$orderNumArray);
        $dataArray['js_tax_title'] = $tax_title;
        $dataArray['js_fapiao_type'] = $taxes ? ($fapiao_data['type']==1 ? '增值税专票' : '增值税普票') : '无';
        $dataArray['code'] = 1;
        echo JSON::encode($dataArray);
    }

    //到货通知处理动作
	function arrival_notice()
	{
		$user_id  = $this->user['user_id'];
		$email    = IFilter::act(IReq::get('email'));
		$mobile   = IFilter::act(IReq::get('mobile'));
		$goods_id = IFilter::act(IReq::get('goods_id'),'int');
		$register_time = ITime::getDateTime();

		if(!$goods_id)
		{
			IError::show(403,'商品ID不存在');
		}

		$model = new IModel('notify_registry');
		$obj = $model->getObj("email = '{$email}' and user_id = '{$user_id}' and goods_id = '$goods_id'");
		if(empty($obj))
		{
			$model->setData(array('email'=>$email,'user_id'=>$user_id,'mobile'=>$mobile,'goods_id'=>$goods_id,'register_time'=>$register_time));
			$model->add();
		}
		else
		{
			$model->setData(array('email'=>$email,'user_id'=>$user_id,'mobile'=>$mobile,'goods_id'=>$goods_id,'register_time'=>$register_time,'notify_status'=>0));
			$model->update('id = '.$obj['id']);
		}
		$this->redirect('/site/success',true);
	}
    
    
    //找回密码
    function find_password()
    {
        if($this->user)
        {
            plugin::trigger('clearUser');
        }
        $this->layout = 'site_log';
        $this->redirect('find_password');
    }

	/**
	 * @brief 邮箱找回密码进行
	 */
    function find_password_email()
	{
		$email = IReq::get("email");
		if($email === null || !IValidate::email($email ))
		{
			IError::show(403,"请输入正确的邮箱地址");
		}

        $captcha  = IReq::get('captcha');
        $_captcha = ISafe::get('captcha');
        if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            IError::show("请填写正确的图形验证码");
        }
        
		$tb_user  = new IModel("user");
		$email    = IFilter::act($email);
		$user     = $tb_user->getObj("email='{$email}' ");
		if(!$user)
		{
			IError::show(403,"对不起，用户不存在");
		}
		$hash = IHash::md5( microtime(true) .mt_rand());

		//重新找回密码的数据
		$tb_find_password = new IModel("find_password");
		$tb_find_password->setData( array( 'hash' => $hash ,'user_id' => $user['id'] , 'addtime' => time() ) );

		if($tb_find_password->query("`hash` = '{$hash}'") || $tb_find_password->add())
		{
			$url     = IUrl::getHost().IUrl::creatUrl("/simple/restore_password/hash/{$hash}/user_id/".$user['id']);
			$content = mailTemplate::findPassword(array("{url}" => $url));

			$smtp   = new SendMail();
			$result = $smtp->send($user['email'],"您的密码找回",$content);

			if($result===false)
			{
				IError::show(403,"发信失败,请重试！或者联系管理员查看邮件服务是否开启");
			}
		}
		else
		{
			IError::show(403,"生成HASH重复，请重试");
		}
		$message = "恭喜您，密码重置邮件已经发送！请到您的邮箱中去激活";
		$this->redirect("/site/success/message/".urlencode($message));
	}

	//手机短信找回密码
	function find_password_mobile()
	{
		$mobile = IReq::get("mobile");
		if($mobile === null || !IValidate::mobi($mobile))
		{
			IError::show(403,"请输入正确的电话号码");
		}

		$mobile_code = IFilter::act(IReq::get('mobile_code'));
		if($mobile_code === null)
		{
			IError::show(403,"请输入短信校验码");
		}

		$userDB = new IModel('user');
		$userRow = $userDB->getObj('mobile = "'.$mobile.'"');
		if($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow = $findPasswordDB->getObj('user_id = '.$userRow['id'].' and hash = "'.$mobile_code.'"');
			if($dataRow)
			{
				//短信验证码已经过期
				if(time() - $dataRow['addtime'] > 3600)
				{
					$findPasswordDB->del("user_id = ".$userRow['id']);
					IError::show(403,"您的短信校验码已经过期了，请重新找回密码");
				}
				else
				{
					$this->redirect('/simple/restore_password/hash/'.$mobile_code.'/user_id/'.$userRow['id']);
				}
			}
			else
			{
				IError::show(403,"您输入的短信校验码错误");
			}
		}
		else
		{
			IError::show(403,"用户名与手机号码不匹配");
		}
	}

	//找回密码发送手机验证码短信
	function send_message_mobile()
	{
        $mobile = IFilter::act(IReq::get('mobile'));
		$captcha = IFilter::act(IReq::get('captcha'));
        $_captcha = ISafe::get('captcha');

		if($mobile === null || !IValidate::mobi($mobile))
		{
			die("请输入正确的手机号码");
		}
        if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            die("请填写正确的图形验证码");
        }

		$userDB = new IModel('user');
		$userRow = $userDB->getObj('mobile = "'.$mobile.'"');

		if($userRow)
		{
			$findPasswordDB = new IModel('find_password');
			$dataRow = $findPasswordDB->query('user_id = '.$userRow['id'],'*','addtime desc');
			$dataRow = current($dataRow);

			//120秒是短信发送的间隔
			if( isset($dataRow['addtime']) && (time() - $dataRow['addtime'] <= 120) )
			{
				die("申请验证码的时间间隔过短，请稍候再试");
			}
			$mobile_code = rand(100000,999999);
			$findPasswordDB->setData(array(
				'user_id' => $userRow['id'],
				'hash'    => $mobile_code,
				'addtime' => time(),
			));
			if($findPasswordDB->add())
			{
				$content = smsTemplate::findPassword(array('{mobile_code}' => $mobile_code));
				$result = Hsms::send($mobile,$content);
				if($result == 'success')
				{
					die('success');
				}
				die($result);
			}
		}
		else
		{
			die('手机号码与用户名不符合');
		}
	}

	/**
	 * @brief 重置密码验证
	 */
	function restore_password()
	{
        $this->layout = 'site_log';
		$hash = IFilter::act(IReq::get("hash"));
		$user_id = IFilter::act(IReq::get("user_id"),'int');

		if(!$hash)
		{
			IError::show(403,"找不到校验码");
		}
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$where  = " `hash`='$hash' AND addtime > $addtime ";
		$where .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if(!$row)
		{
			IError::show(403,"校验码已经超时");
		}

		if($row['user_id'] != $user_id)
		{
			IError::show(403,"验证码不属于此用户");
		}

		$this->formAction = IUrl::creatUrl("/simple/do_restore_password/hash/$hash/user_id/".$user_id);
		$this->redirect("restore_password");
	}

	/**
	 * @brief 执行密码修改重置操作
	 */
	function do_restore_password()
	{
		$hash = IFilter::act(IReq::get("hash"));
		$user_id = IFilter::act(IReq::get("user_id"),'int');

		if(!$hash)
		{
			IError::show(403,"找不到校验码");
		}
		$tb = new IModel("find_password");
		$addtime = time() - 3600*72;
		$where  = " `hash`='$hash' AND addtime > $addtime ";
		$where .= $this->user['user_id'] ? " and user_id = ".$this->user['user_id'] : "";

		$row = $tb->getObj($where);
		if(!$row)
		{
			IError::show(403,"校验码已经超时");
		}

		if($row['user_id'] != $user_id)
		{
			IError::show(403,"验证码不属于此用户");
		}

		//开始修改密码
		$pwd   = IReq::get("password");
		$repwd = IReq::get("repassword");
		if($pwd == null || strlen($pwd) < 6 || $repwd!=$pwd)
		{
			IError::show(403,"新密码至少六位，且两次输入的密码应该一致。");
		}
		$pwd = md5($pwd);
		$tb_user = new IModel("user");
		$tb_user->setData(array("password" => $pwd));
		$re = $tb_user->update("id='{$row['user_id']}'");
		if($re !== false)
		{
			$message = "修改密码成功";
			$tb->del("`hash`='{$hash}'");
			$this->redirect("/site/success/message/".urlencode($message));
			return;
		}
		IError::show(403,"密码修改失败，请重试");
	}

    //添加收藏夹
    function favorite_add()
    {
    	$goods_id = IFilter::act(IReq::get('goods_id'),'int');
    	$message  = '';

    	if($goods_id == 0)
    	{
    		$message = '商品id值不能为空';
    	}
    	else if(!isset($this->user['user_id']) || !$this->user['user_id'])
    	{
    		$message = '请先登录';
    	}
    	else
    	{
    		$favoriteObj = new IModel('favorite');
    		$goodsRow    = $favoriteObj->getObj('user_id = '.$this->user['user_id'].' and rid = '.$goods_id);
    		if($goodsRow)
    		{
    			$message = '您已经收藏过此件商品';
    		}
    		else
    		{
    			$catObj = new IModel('category_extend');
    			$catRow = $catObj->getObj('goods_id = '.$goods_id);
    			$cat_id = $catRow ? $catRow['category_id'] : 0;

	    		$dataArray   = array(
	    			'user_id' => $this->user['user_id'],
	    			'rid'     => $goods_id,
	    			'time'    => ITime::getDateTime(),
	    			'cat_id'  => $cat_id,
	    		);
	    		$favoriteObj->setData($dataArray);
	    		$favoriteObj->add();
	    		$message = '收藏成功';

	    		//商品收藏信息更新
	    		$goodsDB = new IModel('goods');
	    		$goodsDB->setData(array("favorite" => "favorite + 1"));
	    		$goodsDB->update("id = ".$goods_id,'favorite');
    		}
    	}
		$result = array(
			'isError' => true,
			'message' => $message,
		);

    	echo JSON::encode($result);
    }

    //获取oauth登录地址
    public function oauth_login()
    {
    	$id = IFilter::act(IReq::get('id'),'int');
    	if($id)
    	{
    		$oauthObj = new Oauth($id);
			$result   = array(
				'isError' => false,
				'url'     => $oauthObj->getLoginUrl(),
			);
    	}
    	else
    	{
			$result   = array(
				'isError' => true,
				'message' => '请选择要登录的平台',
			);
    	}
    	echo JSON::encode($result);
    }

    //第三方登录回调
    public function oauth_callback()
    {
    	$oauth_name = IFilter::act(IReq::get('oauth_name'));
    	$oauthObj   = new IModel('oauth');
    	$oauthRow   = $oauthObj->getObj('file = "'.$oauth_name.'"');

    	if(!$oauth_name && !$oauthRow)
    	{
    		IError::show(403,"{$oauth_name} 第三方平台信息不存在");
    	}
		$id       = $oauthRow['id'];
    	$oauthObj = new Oauth($id);
    	$result   = $oauthObj->checkStatus($_GET);

    	if($result === true)
    	{
    		$oauthObj->getAccessToken($_GET);
	    	$userInfo = $oauthObj->getUserInfo();

	    	if(isset($userInfo['id']) && isset($userInfo['name']) && $userInfo['id'] && $userInfo['name'])
	    	{
	    		$this->bindUser($userInfo,$id);
	    		return;
	    	}
    	}
    	else
    	{
    		IError::show("回调URL参数错误");
    	}
    }

    //同步绑定用户数据
    public function bindUser($userInfo,$oauthId)
    {
    	$userObj      = new IModel('user');
    	$oauthUserObj = new IModel('oauth_user');
    	$oauthUserRow = $oauthUserObj->getObj("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ",'user_id');
		if($oauthUserRow)
		{
			//清理oauth_user和user表不同步匹配的问题
			$tempRow = $userObj->getObj("id = '{$oauthUserRow['user_id']}'");
			if(!$tempRow)
			{
				$oauthUserObj->del("oauth_user_id = '{$userInfo['id']}' and oauth_id = '{$oauthId}' ");
			}
		}

    	//存在绑定账号oauth_user与user表同步正常！
    	if(isset($tempRow) && $tempRow)
    	{
    		$userRow = plugin::trigger("isValidUser",array($tempRow['username'] ? $tempRow['username'] : ($tempRow['mobile'] ? $tempRow['mobile'] : $tempRow['email']),$tempRow['password']));
    		plugin::trigger("userLoginCallback",$userRow);
    		$callback = plugin::trigger('getCallback');
    		$callback = $callback ? $callback : "/ucenter/index";
			$this->redirect($callback);
    	}
    	//没有绑定账号
    	else
    	{
	    	$userCount = $userObj->getObj("username = '{$userInfo['name']}'",'count(*) as num');

	    	//没有重复的用户名
	    	if($userCount['num'] == 0)
	    	{
	    		$username = $userInfo['name'];
	    	}
	    	else
	    	{
	    		//随即分配一个用户名
	    		$username = $userInfo['name'].$userCount['num'];
	    	}
			$userInfo['name'] = $username;
	    	ISession::set('oauth_id',$oauthId);
	    	ISession::set('oauth_userInfo',$userInfo);
	    	$this->setRenderData($userInfo);
	    	$this->redirect('bind_user');
    	}
    }

	//执行绑定已存在用户
    public function bind_exists_user()
    {
    	$login_info     = IReq::get('login_info');
    	$password       = IReq::get('password');
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

    	if(!$oauth_id || !$oauth_userInfo || !isset($oauth_userInfo['id']))
    	{
    		IError::show("缺少oauth信息");
    	}

    	if($userRow = plugin::trigger("isValidUser",array($login_info,md5($password))))
    	{
    		$oauthUserObj = new IModel('oauth_user');

    		//插入关系表
    		$oauthUserData = array(
    			'oauth_user_id' => $oauth_userInfo['id'],
    			'oauth_id'      => $oauth_id,
    			'user_id'       => $userRow['user_id'],
    			'datetime'      => ITime::getDateTime(),
    		);
    		$oauthUserObj->setData($oauthUserData);
    		$oauthUserObj->add();

    		plugin::trigger("userLoginCallback",$userRow);

			//自定义跳转页面
			$this->redirect('/site/success?message='.urlencode("登录成功！"));
    	}
    	else
    	{
    		$this->setError("用户名和密码不匹配");
    		$_GET['bind_type'] = 'exists';
    		$this->redirect('bind_user',false);
    		Util::showMessage("用户名和密码不匹配");
    	}
    }

	//执行绑定注册新用户用户
    public function bind_not_exists_user()
    {
    	$oauth_id       = IFilter::act(ISession::get('oauth_id'));
    	$oauth_userInfo = IFilter::act(ISession::get('oauth_userInfo'));

    	if(!$oauth_id || !$oauth_userInfo || !isset($oauth_userInfo['id']))
    	{
    		IError::show("缺少oauth信息");
    	}

    	//调用_userInfo注册插件
		$result = plugin::trigger('userRegAct',$_POST);
		if(is_array($result))
		{
			//插入关系表
			$oauthUserObj = new IModel('oauth_user');
			$oauthUserData = array(
				'oauth_user_id' => $oauth_userInfo['id'],
				'oauth_id'      => $oauth_id,
				'user_id'       => $result['id'],
				'datetime'      => ITime::getDateTime(),
			);
			$oauthUserObj->setData($oauthUserData);
			$oauthUserObj->add();
			$this->redirect('/site/success?message='.urlencode("注册成功！"));
		}
		else
		{
    		$this->setError($result);
    		$this->redirect('bind_user',false);
    		Util::showMessage($result);
		}
    }
    
    public function sellerRej()
    {
        $id = IReq::get('_i');
        $code = IReq::get('_c');
        $model = new IModel('seller_rej_sign');
        if(!$model->getObj('seller_id = '.$id.' and code = '.$code))
        {
            IError::show(403,'修改地址已失效，请与管理员联系');
        }
        $this->code = $code;
        $model = new IModel('seller');
        $this->sellerRow = $model->getObj('id = '.$id);
        $this->redirect('sellerRej');
    }

    /**
     * @brief 商户的修改动作
     */
    public function sellerRej_reg()
    {
        $seller_name = IValidate::name(IReq::get('seller_name')) ? IReq::get('seller_name') : "";
        $contacts_name = IValidate::name(IReq::get('contacts_name')) ? IReq::get('contacts_name') : "";
        $email       = IValidate::email(IReq::get('email'))      ? IReq::get('email')       : "";
        $truename    = IValidate::name(IReq::get('true_name'))   ? IReq::get('true_name')   : "";
        $phone       = IValidate::phone(IReq::get('phone'))      ? IReq::get('phone')       : "";
        //$mobile      = IValidate::mobi(IReq::get('mobile'))      ? IReq::get('mobile')      : "";
        $home_url    = IValidate::url(IReq::get('home_url'))     ? IReq::get('home_url')    : "";
        $province    = IFilter::act(IReq::get('province'),'int');
        $city        = IFilter::act(IReq::get('city'),'int');
        $area        = IFilter::act(IReq::get('area'),'int');
        $address     = IFilter::act(IReq::get('address'));
        $id = IReq::get('_i');
        $code = IReq::get('_c');
        $model = new IModel('seller_rej_sign');
        if(!$model->getObj('seller_id = '.$id.' and code = '.$code))
        {
            $errorMsg = '系统错误';
        }
        
        if(!$seller_name)
        {
            $errorMsg = '填写正确的登陆用户名';
        }

        if(!$truename)
        {
            $errorMsg = '填写正确的商户真实全称';
        }

        if(!$contacts_name)
        {
            $errorMsg = '填写正确的联系人';
        }

        //创建商家操作类
        $sellerDB = new IModel("seller");
        if($seller_name && $sellerDB->getObj("seller_name = '{$seller_name}' and id != {$id}"))
        {
            $errorMsg = "登录用户名重复";
        }
        else if($truename && $sellerDB->getObj("true_name = '{$truename}' and id != {$id}"))
        {
            $errorMsg = "商户真实全称重复";
        }
        //操作失败表单回填
        if(isset($errorMsg))
        {
            $this->sellerRow = $sellerDB->getObj('id = '.$id);
            $this->code = $code;
            $this->redirect('sellerRej',false);
            Util::showMessage($errorMsg);
        }

        //待更新的数据
        $sellerRow = array(
            'true_name' => $truename,
            'contacts_name' => $contacts_name,
            'phone'     => $phone,
            //'mobile'    => $mobile,
            'email'     => $email,
            'address'   => $address,
            'province'  => $province,
            'city'      => $city,
            'area'      => $area,
            'home_url'  => $home_url,
            'is_lock'   => 2,
        );

        //商户资质、logo上传
        if((isset($_FILES['paper_img']['name']) && $_FILES['paper_img']['name']) || (isset($_FILES['seller_logo']['name']) && $_FILES['seller_logo']['name']) || (isset($_FILES['identity_card']['name']) && $_FILES['identity_card']['name']))
        {
            $uploadObj = new PhotoUpload();
            $uploadObj->setIterance(false);
            $photoInfo = $uploadObj->run();
        }

        if(isset($photoInfo['paper_img']['img']) && file_exists($photoInfo['paper_img']['img']))
        {
            $sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
        }
            
        if(isset($photoInfo['seller_logo']['img']) && file_exists($photoInfo['seller_logo']['img']))
        {
            $sellerRow['seller_logo'] = $photoInfo['seller_logo']['img'];
        }
            
        if(isset($photoInfo['identity_card']['img']) && file_exists($photoInfo['identity_card']['img']))
        {
            $sellerRow['identity_card'] = $photoInfo['identity_card']['img'];
        }
        $sellerRow['seller_name'] = $seller_name;

        $sellerDB->setData($sellerRow);
        $sellerDB->update('id = '.$id);

        //短信通知商城平台
        if($this->_siteConfig->mobile)
        {
            $content = smsTemplate::sellerRej(array('{true_name}' => $truename));
            $result = Hsms::send($this->_siteConfig->mobile,$content);
        }
        $model->del('seller_id = '.$id.' and code = '.$code);
        $this->redirect('/site/success?message='.urlencode("提交成功！请耐心等待管理员的审核"));
    }

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_reg()
	{
        $seller_name = IValidate::name(IReq::get('seller_name')) ? IReq::get('seller_name') : "";
		$contacts_name = IValidate::name(IReq::get('contacts_name')) ? IReq::get('contacts_name') : "";
		$email       = IValidate::email(IReq::get('email'))      ? IReq::get('email')       : "";
		$truename    = IValidate::name(IReq::get('true_name'))   ? IReq::get('true_name')   : "";
		$phone       = IValidate::phone(IReq::get('phone'))      ? IReq::get('phone')       : "";
		$mobile      = IValidate::mobi(IReq::get('mobile'))      ? IReq::get('mobile')      : "";
		$home_url    = IValidate::url(IReq::get('home_url'))     ? IReq::get('home_url')    : "";

		$password    = IFilter::act(IReq::get('password'));
		$repassword  = IFilter::act(IReq::get('repassword'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
        $address     = IFilter::act(IReq::get('address'));
        $con_num     = IFilter::act(IReq::get('con_num'));
		$type        = IFilter::act(IReq::get('type'));
        $mobile_code = IFilter::act(IReq::get('mobile_code','post'));
        $captcha     = IFilter::act(IReq::get('captcha','post'));
        $_captcha    = ISafe::get('captcha');
		if($password == '')
		{
			$errorMsg = '请输入密码！';
		}

		if($password != $repassword)
		{
			$errorMsg = '两次输入的密码不一致！';
		}

		if(!$seller_name)
		{
			$errorMsg = '填写正确的登陆用户名';
		}

        if(!$truename)
        {
            $errorMsg = '填写正确的商户真实全称';
        }

		if(!$contacts_name)
		{
			$errorMsg = '填写正确的联系人';
		}
        if(!$_FILES['paper_img']['name'])
        {
            $errorMsg = '填上传营业执照';
        }
        if(!$_FILES['identity_card']['name'])
        {
            $errorMsg = '填上传负责人证件';
        }

        if(!$_captcha || !$captcha || $captcha != $_captcha)
        {
            $errorMsg = "图形验证码输入不正确";
        }

        $_mobileCode = ISafe::get('seller_code'.$mobile);
        if(!$mobile_code || !$_mobileCode || $mobile_code != $_mobileCode)
        {
            $errorMsg = "手机验证码不正确";
        }


		//创建商家操作类
		$sellerDB = new IModel("seller");
		if($seller_name && $sellerDB->getObj("seller_name = '{$seller_name}'"))
		{
			$errorMsg = "登录用户名重复";
		}
		else if($truename && $sellerDB->getObj("true_name = '{$truename}'"))
		{
			$errorMsg = "商户真实全称重复";
		}

		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->sellerRow = IFilter::act($_POST,'text');
			$this->redirect('seller',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
            'true_name' => $truename,
			'contacts_name' => $contacts_name,
			'phone'     => $phone,
			'mobile'    => $mobile,
			'email'     => $email,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
            'home_url'  => $home_url,
            'con_num'   => $con_num,
			'type'      => $type,
			'is_lock'   => 2,
		);
        if($type == 2)
        {
            $sellerRow['is_pay'] = 1;
        }

		//营业执照、负责人证件、logo上传
		$uploadObj = new PhotoUpload();
		$uploadObj->setIterance(false);
		$photoInfo = $uploadObj->run();

        if(isset($photoInfo['paper_img']['img']) && file_exists($photoInfo['paper_img']['img']))
        {
            $sellerRow['paper_img'] = $photoInfo['paper_img']['img'];
        }
            
        if(isset($photoInfo['seller_logo']['img']) && file_exists($photoInfo['seller_logo']['img']))
        {
            $sellerRow['seller_logo'] = $photoInfo['seller_logo']['img'];
        }
            
        if(isset($photoInfo['identity_card']['img']) && file_exists($photoInfo['identity_card']['img']))
        {
            $sellerRow['identity_card'] = $photoInfo['identity_card']['img'];
        }
		$sellerRow['seller_name'] = $seller_name;
		$sellerRow['password']    = md5($password);
		$sellerRow['create_time'] = ITime::getDateTime();

		$sellerDB->setData($sellerRow);
		$seller_id = $sellerDB->add();
        
        //绑定运营中心
        $sellerObj = new IModel('user as u, seller as s');
        if($row = $sellerObj->getObj('u.type = 4 and s.is_del = 0 and s.is_lock = 0 and s.area = '.$area.' and u.relate_id = s.id', 's.id'))
        {
            $obj = new IModel('operational_user');
            $data = array(
                        'object_id' => $seller_id,
                        'operation_id' => $row['id'],
                        'type' => 2,
                        'time' => ITime::getDateTime()
                    );
            $obj->setData($data);
            $obj->add();
        }

		//短信通知商城平台
		if($this->_siteConfig->mobile)
		{
			$content = smsTemplate::sellerReg(array('{true_name}' => $truename));
			$result = Hsms::send($this->_siteConfig->mobile,$content);
		}
		$this->redirect('/site/success?message='.urlencode("申请成功！请耐心等待管理员的审核"));
	}

    //商家开店发送验证码
    public function sendSellerMobileCode()
    {
        $mobile   = IReq::get('mobile');
        $captcha  = IReq::get('captcha');
        $_captcha = ISafe::get('captcha');
        if(IValidate::mobi($mobile) == false)
        {
            die("请填写正确的手机号码");
        }
        if(!$captcha || !$_captcha || $captcha != $_captcha)
        {
            die("请填写正确的图形验证码");
        }

        $sellerObj = new IModel('seller');
        $sellerRow = $sellerObj->getObj('mobile = "'.$mobile.'"');
        if($sellerRow)
        {
            die("手机号已经被申请");
        }

        $mobile_code = rand(100000,999999);
        $content = smsTemplate::checkCode(array('{mobile_code}' => $mobile_code));
        $result = Hsms::send($mobile,$content);
        if($result == 'success')
        {
            ISafe::set("seller_code".$mobile,$mobile_code);
        }
        else
        {
            die($result);
        }
    }
    
    //申请开店支付页面
    function sellerPay()
    {
        $seller_id = IReq::get('sId');
        $sellerDB = new IModel('seller');
        if(!$sellerDB->getObj('id = '.$seller_id, 'id'))
        {
            IError::show(403,'没有此商家');
        }
        else if($sellerDB->getObj('id = '.$seller_id.' and is_pay = 1 and is_lock = 2', 'id'))
        {
            IError::show(403,'已支付服务费,请耐心等待管理员的审核');
        }
        else if($sellerDB->getObj('id = '.$seller_id.' and is_pay = 1 and is_lock = 1', 'id'))
        {
            IError::show(403,'已支付服务费,管理员审核未通过,请与管理员联系');
        }
        else if($sellerDB->getObj('id = '.$seller_id.' and is_pay = 1 and is_lock = 0', 'id'))
        {
            $this->redirect('/site/home/id/'.$seller_id);
        }
        $this->seller_id = $seller_id;
        $this->redirect('sellerPay');
    }
	//添加地址ajax
	function address_add()
	{
		$id          = IFilter::act(IReq::get('id'),'int');
		$accept_name = IFilter::act(IReq::get('accept_name'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$zip         = IFilter::act(IReq::get('zip'));
		$telphone    = IFilter::act(IReq::get('telphone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
        $user_id     = $this->user['user_id'];

		//整合的数据
        $sqlData = array(
        	'user_id'     => $user_id,
        	'accept_name' => $accept_name,
        	'zip'         => $zip,
        	'telphone'    => $telphone,
        	'province'    => $province,
        	'city'        => $city,
        	'area'        => $area,
        	'address'     => $address,
        	'mobile'      => $mobile,
        );

        $checkArray = $sqlData;
        unset($checkArray['telphone'],$checkArray['zip'],$checkArray['user_id']);
        foreach($checkArray as $key => $val)
        {
        	if(!$val)
        	{
        		$result = array('result' => false,'msg' => '请仔细填写收货地址');
				die(JSON::encode($result));
        	}
        }

        if($user_id)
        {
        	$model = new IModel('address');
        	$model->setData($sqlData);
        	if($id)
        	{
        		$model->update("id = ".$id." and user_id = ".$user_id);
        	}
        	else
        	{
        		$id = $model->add();
        	}
        	$sqlData['id'] = $id;
        }
        //访客地址保存
        else
        {
        	ISafe::set("address",$sqlData);
        }

        $areaList = area::name($province,$city,$area);
		$sqlData['province_val'] = $areaList[$province];
		$sqlData['city_val']     = $areaList[$city];
		$sqlData['area_val']     = $areaList[$area];
		$result = array('data' => $sqlData);
		die(JSON::encode($result));
	}
    
    //注册服务协议页面
    function regSevice()
    {
        $this->layout = '';
        $this->redirect('regSevice');
    }
}
