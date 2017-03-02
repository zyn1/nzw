<?php
/**
 * @brief 商家模块
 * @class Seller
 * @author chendeshan
 * @datetime 2014/7/19 15:18:56
 */
class Seller extends IController implements sellerAuthorization
{
	public $layout = 'seller';

	/**
	 * @brief 初始化检查
	 */
	public function init()
	{

	}
	/**
	 * @brief 商品添加中图片上传的方法
	 */
	public function goods_img_upload()
	{
	 	//调用文件上传类
		$photoObj = new PhotoUpload();
		$photo    = current($photoObj->run());

		//判断上传是否成功，如果float=1则成功
		if($photo['flag'] == 1)
		{
			$result = array(
				'flag'=> 1,
				'img' => $photo['img']
			);
		}
		else
		{
			$result = array('flag'=> $photo['flag']);
		}
		echo JSON::encode($result);
	}
	/**
	 * @brief 商品添加和修改视图
	 */
	public function goods_edit()
	{
		$goods_id = IFilter::act(IReq::get('id'),'int');

		//初始化数据
		$goods_class = new goods_class($this->seller['seller_id']);

		//获取所有商品扩展相关数据
		$data = $goods_class->edit($goods_id);

		if($goods_id && !$data)
		{
			die("没有找到相关商品！");
		}

		$this->setRenderData($data);
		$this->redirect('goods_edit');
	}
	//商品更新动作
	public function goods_update()
	{
		$id       = IFilter::act(IReq::get('id'),'int');
		$callback = IFilter::act(IReq::get('callback'));
		$callback = strpos($callback,'seller/goods_list') === false ? '' : $callback;

		//检查表单提交状态
		if(!$_POST)
		{
			die('请确认表单提交正确');
		}

		//初始化商品数据
		unset($_POST['id']);
		unset($_POST['callback']);

		$goodsObject = new goods_class($this->seller['seller_id']);
		$goodsObject->update($id,$_POST);

		$callback ? $this->redirect($callback) : $this->redirect("goods_list");
	}
	//商品列表
	public function goods_list()
	{
		$seller_id = $this->seller['seller_id'];
		$searchArray = Util::getUrlParam('search');
		$searchParam = http_build_query($searchArray);
		$condition = Util::goodsSearch(IReq::get('search'));
		$where = "go.seller_id='$seller_id' ";
		$where .= $condition ? " and ".$condition : "";
		$join = isset($searchArray['search']['category_id']) ? " left join category_extend as ce on ce.goods_id = go.id " : "";
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$goodHandle = new IQuery('goods as go');
		$goodHandle->order  = "go.id desc";
		$goodHandle->fields = "distinct go.id,go.name,go.sell_price,go.market_price,go.store_nums,go.img,go.is_del,go.seller_id,go.is_share,go.sort";
		$goodHandle->where  = $where;
		$goodHandle->page	= $page;
		$goodHandle->join	= $join;

		$this->goodHandle = $goodHandle;

		$goods_info = array();
		$goods_info['seller_id'] = $seller_id;
		$goods_info['searchParam'] = $searchParam;
		$this->setRenderData($goods_info);
		$this->redirect('goods_list');
	}

	//商品列表
	public function goods_report()
	{
		$seller_id = $this->seller['seller_id'];
		$condition = Util::goodsSearch(IReq::get('search'));

		$where  = 'go.seller_id='.$seller_id;
		$where .= $condition ? " and ".$condition : "";
		$join = isset($_GET['search']['category_id']) ? " left join category_extend as ce on ce.goods_id = go.id " : "";

		$goodHandle = new IQuery('goods as go');
		$goodHandle->order  = "go.id desc";
		$goodHandle->fields = "go.*";
		$goodHandle->where  = $where;
		$goodHandle->join	= $join;
		$goodList = $goodHandle->find();

		//构建 Excel table;
		$strTable ='<table width="500" border="1">';
		$strTable .= '<tr>';
		$strTable .= '<td style="text-align:center;font-size:12px;">商品名称</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="160">分类</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="60">售价</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="60">库存</td>';
		$strTable .= '</tr>';

		foreach($goodList as $k=>$val){
			$strTable .= '<tr>';
			$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['name'].'</td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.goods_class::getGoodsCategory($val['id']).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['sell_price'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['store_nums'].' </td>';
			$strTable .= '</tr>';
		}
		$strTable .='</table>';
		unset($goodList);
		$reportObj = new report();
		$reportObj->setFileName('goods');
		$reportObj->toDownload($strTable);
		exit();
	}

	//商品删除
	public function goods_del()
	{
		//post数据
	    $id = IFilter::act(IReq::get('id'),'int');

	    //生成goods对象
	    $goods = new goods_class();
	    $goods->seller_id = $this->seller['seller_id'];

	    if($id)
		{
			if(is_array($id))
			{
				foreach($id as $key => $val)
				{
					$goods->del($val);
				}
			}
			else
			{
				$goods->del($id);
			}
		}
		$this->redirect("goods_list");
	}


	//商品状态修改
	public function goods_status()
	{
	    $id        = IFilter::act(IReq::get('id'),'int');
		$is_del    = IFilter::act(IReq::get('is_del'),'int');
		$is_del    = $is_del === 0 ? 3 : $is_del; //不能等于0直接上架
		$seller_id = $this->seller['seller_id'];

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('is_del' => $is_del));

	    if($id)
		{
			if(is_array($id))
			{
				foreach($id as $key => $val)
				{
					$goodsDB->update("id = ".$val." and seller_id = ".$seller_id);
				}
			}
			else
			{
				$goodsDB->update("id = ".$val." and seller_id = ".$seller_id);
			}
		}
		$this->redirect("goods_list");
	}

	//规格删除
	public function spec_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$idString = is_array($id) ? join(',',$id) : $id;
			$specObj  = new IModel('spec');
			$specObj->del("id in ( {$idString} ) and seller_id = ".$this->seller['seller_id']);
			$this->redirect('spec_list');
		}
		else
		{
			$this->redirect('spec_list',false);
			Util::showMessage('请选择要删除的规格');
		}
	}
	//修改排序
	public function ajax_sort()
	{
		$id   = IFilter::act(IReq::get('id'),'int');
		$sort = IFilter::act(IReq::get('sort'),'int');

		$goodsDB = new IModel('goods');
		$goodsDB->setData(array('sort' => $sort));
		$goodsDB->update("id = {$id} and seller_id = ".$this->seller['seller_id']);
	}

	//咨询回复
	public function refer_reply()
	{
		$rid     = IFilter::act(IReq::get('refer_id'),'int');
		$content = IFilter::act(IReq::get('content'),'text');

		if($rid && $content)
		{
			$tb_refer = new IModel('refer');
			$seller_id = $this->seller['seller_id'];//商户id
			$data = array(
				'answer' => $content,
				'reply_time' => ITime::getDateTime(),
				'seller_id' => $seller_id,
				'status' => 1
			);
			$tb_refer->setData($data);
			$tb_refer->update("id=".$rid);
		}
		$this->redirect('refer_list');
	}
	/**
	 * @brief查看订单
	 */
	public function order_show()
	{
		//获得post传来的值
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data = array();
		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id,0,$this->seller['seller_id']);
			if($data)
			{
		 		//获取地区
		 		$data['area_addr'] = join('&nbsp;',area::name($data['province'],$data['city'],$data['area']));

			 	$this->setRenderData($data);
				$this->redirect('order_show',false);
			}
		}
		if(!$data)
		{
			$this->redirect('order_list');
		}
	}
	/**
	 * @brief 发货订单页面
	 */
	public function order_deliver()
	{
		$order_id = IFilter::act(IReq::get('id'),'int');
		$data     = array();

		if($order_id)
		{
			$order_show = new Order_Class();
			$data = $order_show->getOrderShow($order_id);
			if($data)
			{
				$this->setRenderData($data);
				$this->redirect('order_deliver');
			}
		}
		if(!$data)
		{
			IError::show("订单信息不存在",403);
		}
	}
	/**
	 * @brief 发货操作
	 */
	public function order_delivery_doc()
	{
	 	//获得post变量参数
	 	$order_id = IFilter::act(IReq::get('id'),'int');

	 	//发送的商品关联
	 	$sendgoods = IFilter::act(IReq::get('sendgoods'),'int');

	 	if(!$sendgoods)
	 	{
	 		die('请选择要发货的商品');
	 	}

	 	$result = Order_Class::sendDeliveryGoods($order_id,$sendgoods,$this->seller['seller_id'],'seller');
	 	if($result === true)
	 	{
            $fapiao_id = IFilter::act(IReq::get('fapiao_id'), 'int');
            $fapiao_status = IFilter::act(IReq::get('fapiao_status'), 'int');
            $fapiao_money = IReq::get('fapiao_money');
            if($fapiao_id && $fapiao_status)
            {
                $db_fa = new IModel('order_fapiao');
                $data=array(
                    'money'=>$fapiao_money,
                    'status'=>1
                );
                $db_fa->setData($data);
                $db_fa->update('id='.$fapiao_id);
            }
	 		$this->redirect('order_list');
	 	}
	 	else
	 	{
	 		IError::show($result);
	 	}
	}
	/**
	 * @brief 订单列表
	 */
	public function order_list()
	{
		$seller_id = $this->seller['seller_id'];
		$searchArray = Util::getUrlParam('search');
		$searchParam = http_build_query($searchArray);
		$condition = Util::orderSearch(IReq::get('search'));
		$where  = "o.seller_id='$seller_id' and o.if_del=0 and o.status not in(3,4)";
		$where .= $condition ? " and ".$condition : "";
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->where  = $where;
		$orderHandle->page	 = $page;
		$this->orderHandle   = $orderHandle;
		$order_info = array();
		$order_info['seller_id'] = $seller_id;
		$order_info['searchParam'] = $searchParam;
		$this->setRenderData($order_info);
		$this->redirect('order_list');
	}

	//订单导出 Excel
	public function order_report()
	{
		$seller_id = $this->seller['seller_id'];
		$condition = Util::orderSearch(IReq::get('search'));

		$where  = "o.seller_id = ".$seller_id." and o.if_del=0 and o.status not in(3,4)";
		$where .= $condition ? " and ".$condition : "";

		//拼接sql
		$orderHandle = new IQuery('order as o');
		$orderHandle->order  = "o.id desc";
		$orderHandle->fields = "o.*,p.name as payment_name";
		$orderHandle->join   = "left join payment as p on p.id = o.pay_type";
		$orderHandle->where  = $where;
		$orderList = $orderHandle->find();

		//构建 Excel table
		$strTable ='<table width="500" border="1">';
		$strTable .= '<tr>';
		$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
		$strTable .= '</tr>';

		foreach($orderList as $k=>$val){
			$strTable .= '<tr>';
			$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_no'].'</td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['accept_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;'.$val['telphone'].'&nbsp;'.$val['mobile'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['payable_amount'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['real_amount'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['payment_name'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderPayStatusText($val).' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.Order_Class::getOrderDistributionStatusText($val).' </td>';

			$orderGoods = Order_class::getOrderGoods($val['id']);

			$strGoods="";
			foreach($orderGoods as $good){
				$strGoods .= "商品编号：".$good['goodsno']." 商品名称：".$good['name'];
				if ($good['value']!='') $strGoods .= " 规格：".$good['value'];
				$strGoods .= "<br />";
			}
			unset($orderGoods);

			$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
			$strTable .= '</tr>';
		}
		$strTable .='</table>';
		//输出成EXcel格式文件并下载
		$reportObj = new report();
		$reportObj->setFileName('order');
		$reportObj->toDownload($strTable);
		exit();
	}

	//修改商户信息
	public function seller_edit()
	{
		$seller_id = $this->seller['seller_id'];
		$sellerDB        = new IModel('seller');
		$this->sellerRow = $sellerDB->getObj('id = '.$seller_id);
		$this->redirect('seller_edit');
	}

	/**
	 * @brief 商户的增加动作
	 */
	public function seller_add()
	{
		$seller_id   = $this->seller['seller_id'];
        $email       = IFilter::act(IReq::get('email'));
		$contacts_name       = IFilter::act(IReq::get('contacts_name'));
		$password    = IFilter::act(IReq::get('password'));
		$repassword  = IFilter::act(IReq::get('repassword'));
		$phone       = IFilter::act(IReq::get('phone'));
		$mobile      = IFilter::act(IReq::get('mobile'));
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$account     = IFilter::act(IReq::get('account'));
		$server_num  = IFilter::act(IReq::get('server_num'));
        $home_url    = IFilter::act(IReq::get('home_url'));
		$sales       = IFilter::act(IReq::get('sales'));
		$tax         = IFilter::act(IReq::get('tax'),'float');

		if(!$seller_id && $password == '')
		{
			$errorMsg = '请输入密码！';
		}

		if($password != $repassword)
		{
			$errorMsg = '两次输入的密码不一致！';
		}

		//操作失败表单回填
		if(isset($errorMsg))
		{
			$this->sellerRow = $_POST;
			$this->redirect('seller_edit',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$sellerRow = array(
			'account'   => $account,
			'phone'     => $phone,
			'mobile'    => $mobile,
            'email'     => $email,
			'contacts_name'     => $contacts_name,
			'address'   => $address,
			'province'  => $province,
			'city'      => $city,
			'area'      => $area,
			'server_num'=> $server_num,
            'home_url'  => $home_url,
			'sales'     => $sales,
			'tax'       => $tax,
		);

		//创建商家操作类
		$sellerDB   = new IModel("seller");

		//修改密码
		if($password)
		{
			$sellerRow['password'] = md5($password);
		}

		$sellerDB->setData($sellerRow);
		$sellerDB->update("id = ".$seller_id);
        
        if(ISafe::get('user_type') == 4)
        {
            $userDB = new IModel('user');
            $memberDB = new IModel('member');
            $userRow = array(
                    'mobile'    => $mobile,
                    'email'     => $email,
                );
                
            if($password)
            {
                $userRow['password'] = md5($password);
            }
            $userDB->setData($userRow);
            $userDB->update('relate_id = '.$seller_id);
            $user_id = $userDB->getObj('relate_id = '.$seller_id, 'id');
            $areaArr = array($province,$city,$area);
            $memberRow = array(
                    'telephone' => $phone,
                    'contact_addr' => $address,
                    'area'      => $areaArr ? ",".join(",",$areaArr)."," : "",
                );
            $memberDB->setData($memberRow);
            $memberDB->update('user_id = '.$user_id['id']);
        }

		$this->redirect('seller_edit');
	}

	//[团购]添加修改[单页]
	function regiment_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$regimentObj = new IModel('regiment');
			$where       = 'id = '.$id.' and seller_id = '.$this->seller['seller_id'];
			$regimentRow = $regimentObj->getObj($where);
			if(!$regimentRow)
			{
				$this->redirect('regiment_list');
			}

			//促销商品
			$goodsObj = new IModel('goods');
			$goodsRow = $goodsObj->getObj('id = '.$regimentRow['goods_id']);

			$result = array(
				'isError' => false,
				'data'    => $goodsRow,
			);
			$regimentRow['goodsRow'] = JSON::encode($result);
			$this->regimentRow = $regimentRow;
		}
		$this->redirect('regiment_edit');
	}

	//[团购]删除
	function regiment_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$regObj = new IModel('regiment');
			if(is_array($id))
			{
				$id    = join(',',$id);
			}
			$where = ' id in ('.$id.') and seller_id = '.$this->seller['seller_id'];
			$regObj->del($where);
			$this->redirect('regiment_list');
		}
		else
		{
			$this->redirect('regiment_list',false);
			Util::showMessage('请选择要删除的id值');
		}
	}

	//[团购]添加修改[动作]
	function regiment_edit_act()
	{
		$id      = IFilter::act(IReq::get('id'),'int');
		$goodsId = IFilter::act(IReq::get('goods_id'),'int');

		$dataArray = array(
			'id'        	=> $id,
			'title'     	=> IFilter::act(IReq::get('title','post')),
			'start_time'	=> IFilter::act(IReq::get('start_time','post')),
			'end_time'  	=> IFilter::act(IReq::get('end_time','post')),
			'is_close'      => 1,
			'intro'     	=> IFilter::act(IReq::get('intro','post')),
			'goods_id'      => $goodsId,
			'store_nums'    => IFilter::act(IReq::get('store_nums','post')),
			'limit_min_count' => IFilter::act(IReq::get('limit_min_count','post'),'int'),
			'limit_max_count' => IFilter::act(IReq::get('limit_max_count','post'),'int'),
			'regiment_price'=> IFilter::act(IReq::get('regiment_price','post')),
			'seller_id'     => $this->seller['seller_id'],
		);

		$dataArray['limit_min_count'] = $dataArray['limit_min_count'] <= 0 ? 1 : $dataArray['limit_min_count'];
		$dataArray['limit_max_count'] = $dataArray['limit_max_count'] <= 0 ? $dataArray['store_nums'] : $dataArray['limit_max_count'];

		if($goodsId)
		{
			$goodsObj = new IModel('goods');
			$where    = 'id = '.$goodsId.' and seller_id = '.$this->seller['seller_id'];
			$goodsRow = $goodsObj->getObj($where);

			//商品信息不存在
			if(!$goodsRow)
			{
				$this->regimentRow = $dataArray;
				$this->redirect('regiment_edit',false);
				Util::showMessage('请选择商户自己的商品');
			}

			//处理上传图片
			if(isset($_FILES['img']['name']) && $_FILES['img']['name'] != '')
			{
				$uploadObj = new PhotoUpload();
				$photoInfo = $uploadObj->run();
				$dataArray['img'] = $photoInfo['img']['img'];
			}
			else
			{
				$dataArray['img'] = $goodsRow['img'];
			}

			$dataArray['sell_price'] = $goodsRow['sell_price'];
		}
		else
		{
			$this->regimentRow = $dataArray;
			$this->redirect('regiment_edit',false);
			Util::showMessage('请选择要关联的商品');
		}

		$regimentObj = new IModel('regiment');
		$regimentObj->setData($dataArray);

		if($id)
		{
			$where = 'id = '.$id.' and seller_id = '.$this->seller['seller_id'];
			$regimentObj->update($where);
		}
		else
		{
			$regimentObj->add();
		}
		$this->redirect('regiment_list');
	}

	//结算单修改
	public function bill_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$billRow = array();

		if($id)
		{
			$billDB  = new IModel('bill');
			$billRow = $billDB->getObj('id = '.$id.' and seller_id = '.$this->seller['seller_id']);
            if($billRow)
            {
                $tmp = JSON::decode($billRow['para']);
                $billRow['start'] = date('Y/m/d', strtotime($billRow['start_time']));
                $billRow['end'] = date('Y/m/d', strtotime($billRow['end_time']));
                $billRow['new_time']   = date('Y/m/d', strtotime($billRow['end_time'])+24*3600);
                $billRow['orgRealFee']   = $tmp['orgRealFee'];
                $billRow['orgDeliveryFee']   = $tmp['orgDeliveryFee'];
                $billRow['commission']   = $tmp['commission'];
                $billRow['countFee']   = $tmp['countFee'];
                $billRow['otherFee']   = isset($tmp['otherFee']) ? $tmp['otherFee'] : 0;
                $billRow['otherInfo']   = isset($tmp['otherInfo']) ? $tmp['otherInfo'] : '';
            }
		}
		$this->billRow = $billRow;
		$this->redirect('bill_edit');
	}

	//结算单删除
	public function bill_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$billDB = new IModel('bill');
			$billDB->del('id = '.$id.' and seller_id = '.$this->seller['seller_id'].' and is_pay = 0');
		}

		$this->redirect('bill_list');
	}

	//结算单更新
	public function bill_update()
	{
        $id            = IFilter::act(IReq::get('id'),'int');
		$is_agree      = IFilter::act(IReq::get('is_agree'),'int');
		$start_time    = IFilter::act(IReq::get('start_time'));
		$end_time      = IFilter::act(IReq::get('end_time'));
		$apply_content = IFilter::act(IReq::get('apply_content'));

		$billDB = new IModel('bill');

		if($id)
		{
			$billRow = $billDB->getObj('id = '.$id);
			if($billRow['is_pay'] == 0)
			{
				$billDB->setData(array('apply_content' => $apply_content, 'is_agree' => $is_agree));
				$billDB->update('id = '.$id.' and seller_id = '.$this->seller['seller_id']);
			}
		}
		else
		{
            if(!$start_time || !$end_time)
            {
                $this->redirect('bill_edit',false);
                Util::showMessage('请填写完整的时间段');
            }
            if($start_time >= $end_time)
            {
                $this->redirect('bill_edit',false);
                Util::showMessage('请选择正确的结算周期');
            }
            if(date('m', strtotime($end_time))-date('m', strtotime($start_time)) > 0)
            {
                $this->redirect('bill_edit',false);
                Util::showMessage('只能结算同一个月的订单');
            }
            
			//判断是否存在未处理的申请
			$isSubmitBill = $billDB->getObj(" seller_id = ".$this->seller['seller_id']." and is_pay = 0");
			if($isSubmitBill)
			{
				$this->redirect('bill_list',false);
				Util::showMessage('请耐心等待管理员结算后才能再次提交申请');
			}
            $endTime = date('Y-m-d', strtotime($end_time)+24*3600);
			//获取未结算的订单
			$queryObject = CountSum::getSellerGoodsFeeQuery($this->seller['seller_id'],$start_time,$endTime,0);
			$countData   = CountSum::countSellerOrderFee($queryObject->find());
			if($countData['countFee'] > 0)
			{
                
				$countData['start_time'] = $start_time;
				$countData['end_time']   = $end_time;
				$billString = AccountLog::sellerNewBillTemplate($countData);
				$data = array(
					'seller_id'  => $this->seller['seller_id'],
					'apply_time' => ITime::getDateTime(),
					'apply_content' => IFilter::act(IReq::get('apply_content')),
					'start_time' => $start_time,
					'end_time' => $end_time,
					'log' => $billString,
					'order_ids' => join(",",$countData['order_ids']),
                    'para' => JSON::encode(array('countFee' => $countData['countFee'], 'otherFee' => 0, 'orgRealFee' => $countData['orgRealFee'], 'orgDeliveryFee' => $countData['orgDeliveryFee'], 'refundFee' => $countData['refundFee'], 'commission' => $countData['commission'], 'otherFee' => 0, 'otherInfo' => ''))
				);
				$billDB->setData($data);
				$billDB->add();
			}
			else
			{
				$this->redirect('bill_list',false);
				Util::showMessage('当前时间段内没有任何结算货款');
			}
		}
		$this->redirect('bill_list');
	}

	//计算应该结算的货款明细
	public function countGoodsFee()
	{
		$seller_id   = $this->seller['seller_id'];
		$start_time  = IFilter::act(IReq::get('start_time'));
		$end_time    = IFilter::act(IReq::get('end_time'));

		$queryObject = CountSum::getSellerGoodsFeeQuery($seller_id,$start_time,$end_time,0);
		$countData   = CountSum::countSellerOrderFee($queryObject->find());

		if($countData['countFee'] > 0)
		{
			/*$countData['start_time'] = date('Y/m/d', strtotime($start_time));
            $countData['end_time']   = date('Y/m/d', strtotime($end_time));
			$countData['new_time']   = date('Y/m/d', strtotime($end_time)+24*3600);

			$billString = AccountLog::sellerNewBillTemplate($countData);*/
			$result     = array('result' => 'success'/*,'data' => $billString, 'countData' => $countData*/);
		}
		else
		{
			$result = array('result' => 'fail','data' => '当前没有任何款项可以结算');
		}

		die(JSON::encode($result));
	}

	/**
	 * @brief 显示评论信息
	 */
	function comment_edit()
	{
		$cid = IFilter::act(IReq::get('cid'),'int');

		if(!$cid)
		{
			$this->comment_list();
			return false;
		}
		$query = new IQuery("comment as c");
		$query->join = "left join goods as goods on c.goods_id = goods.id left join user as u on c.user_id = u.id";
		$query->fields = "c.*,u.username,goods.name,goods.seller_id";
		$query->where = "c.id=".$cid." and goods.seller_id = ".$this->seller['seller_id'];
		$items = $query->find();

		if($items)
		{
            $comment = current($items);
            $photo = new IModel('comment_photo');
            $comment['photo'] = $photo->query('comment_id = '.$comment['id'].' and is_reply = 0', 'img');
            if($comment['second_contents'])
            {
                $comment['reply_photo'] = $photo->query('comment_id = '.$comment['id'].' and is_reply = 1', 'img');
            }
            $this->comment = $comment;
			$this->redirect('comment_edit');
		}
		else
		{
			$this->comment_list();
			$msg = '没有找到相关记录！';
			Util::showMessage($msg);
		}
	}

	/**
	 * @brief 回复评论
	 */
	function comment_update()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$recontent = IFilter::act(IReq::get('recontents'));
		if($id)
		{
			$commentDB = new IQuery('comment as c');
			$commentDB->join = 'left join goods as go on go.id = c.goods_id';
			$commentDB->where= 'c.id = '.$id.' and go.seller_id = '.$this->seller['seller_id'];
			$checkList = $commentDB->find();
			if(!$checkList)
			{
				IError::show(403,'该商品不属于您，无法对其评论进行回复');
			}

			$updateData = array(
				'recontents' => $recontent,
				'recomment_time' => ITime::getDateTime(),
			);
			$commentDB = new IModel('comment');
			$commentDB->setData($updateData);
			$commentDB->update('id = '.$id);
		}
		$this->redirect('comment_list');
	}

	//商品退款详情
	function refundment_show()
	{
	 	//获得post传来的退款单id值
	 	$refundment_id = IFilter::act(IReq::get('id'),'int');
	 	$data = array();
	 	if($refundment_id)
	 	{
	 		$tb_refundment = new IQuery('refundment_doc as c');
	 		$tb_refundment->join=' left join order as o on c.order_id=o.id left join user as u on u.id = c.user_id';
	 		$tb_refundment->fields = 'u.username,c.*,o.*,c.id as id,c.pay_status as pay_status,c.type as type';
	 		$tb_refundment->where = 'c.id='.$refundment_id.' and c.seller_id = '.$this->seller['seller_id'];
	 		$refundment_info = $tb_refundment->find();
	 		if($refundment_info)
	 		{
	 			$data = current($refundment_info);
	 			$this->data = $data;
	 			$this->setRenderData($data);
	 			$this->redirect('refundment_show',false);
	 		}
	 	}

	 	if(!$data)
		{
			$this->redirect('refundment_list');
		}
	}

	//商品退款操作
	function refundment_update()
	{
        $id           = IFilter::act(IReq::get('id'),'int');
		$type         = IFilter::act(IReq::get('type'),'int');
        $order_id     = IFilter::act(IReq::get('order_id'),'int');
		$pay_status   = IFilter::act(IReq::get('pay_status'),'int');
		$dispose_idea = IFilter::act(IReq::get('dispose_idea'));
		$amount       = IFilter::act(IReq::get('amount'),'float');

		if(!$pay_status)
		{
			die('选择处理状态');
		}

		//商户处理退换货
		if($id && Order_Class::isSellerRefund($id,$type,$this->seller['seller_id']) == 2)
		{
			$updateData = array(
				'dispose_time' => ITime::getDateTime(),
				'dispose_idea' => $dispose_idea,
				'pay_status'   => $pay_status,
				'amount'       => $amount,
			);
			$tb_refundment_doc = new IModel('refundment_doc');
			$tb_refundment_doc->setData($updateData);
			$tb_refundment_doc->update('id = '.$id);

			if($pay_status == 2)
			{
                if($type == 1)
                {
				    $result = Order_Class::refund($id,$this->seller['seller_id'],'seller','origin');
				    if(is_string($result))
				    {
					    $tb_refundment_doc->rollback();
					    die($result);
				    }
                }
                else
                {
                    $result = Order_Class::changeGoods($order_id,$id);
                    if(is_string($result))
                    {
                        $tb_refundment_doc->rollback();
                        die($result);
                    }
                }
			}
            if($pay_status == 5)
            {
                if($type == 2)
                {
                    $result = Order_Class::changeGoods($order_id,$id);
                    if(is_string($result))
                    {
                        $tb_refundment_doc->rollback();
                        die($result);
                    }
                }
            }
		}
        if($type == 1)
        {
            $this->redirect('refundment_list');
        }
        else
        {
		    $this->redirect('change_list');
        }
	}

	//商品复制
	function goods_copy()
	{
		$idArray = explode(',',IReq::get('id'));
		$idArray = IFilter::act($idArray,'int');

		$goodsDB     = new IModel('goods');
		$goodsAttrDB = new IModel('goods_attribute');
		$goodsPhotoRelationDB = new IModel('goods_photo_relation');
		$productsDB = new IModel('products');

		$goodsData = $goodsDB->query('id in ('.join(',',$idArray).') and is_share = 1 and is_del = 0 and seller_id = 0','*');
		if($goodsData)
		{
			foreach($goodsData as $key => $val)
			{
				//判断是否重复
				if( $goodsDB->getObj('seller_id = '.$this->seller['seller_id'].' and name = "'.$val['name'].'"') )
				{
					die('商品不能重复复制');
				}

				$oldId = $val['id'];

				//商品数据
				unset($val['id'],$val['visit'],$val['favorite'],$val['sort'],$val['comments'],$val['sale'],$val['grade'],$val['is_share']);
				$val['seller_id'] = $this->seller['seller_id'];
				$val['goods_no'] .= '-'.$this->seller['seller_id'];
				$val['name']      = IFilter::act($val['name'],'text');
				$val['content']   = IFilter::act($val['content'],'text');

				$goodsDB->setData($val);
				$goods_id = $goodsDB->add();

				//商品属性
				$attrData = $goodsAttrDB->query('goods_id = '.$oldId);
				if($attrData)
				{
					foreach($attrData as $k => $v)
					{
						unset($v['id']);
						$v['goods_id'] = $goods_id;
						$goodsAttrDB->setData($v);
						$goodsAttrDB->add();
					}
				}

				//商品图片
				$photoData = $goodsPhotoRelationDB->query('goods_id = '.$oldId);
				if($photoData)
				{
					foreach($photoData as $k => $v)
					{
						unset($v['id']);
						$v['goods_id'] = $goods_id;
						$goodsPhotoRelationDB->setData($v);
						$goodsPhotoRelationDB->add();
					}
				}

				//货品
				$productsData = $productsDB->query('goods_id = '.$oldId);
				if($productsData)
				{
					foreach($productsData as $k => $v)
					{
						unset($v['id']);
						$v['products_no'].= '-'.$this->seller['seller_id'];
						$v['goods_id']    = $goods_id;
						$productsDB->setData($v);
						$productsDB->add();
					}
				}
			}
			die('success');
		}
		else
		{
			die('复制的商品不存在');
		}
	}

	/**
	 * @brief 添加/修改发货信息
	 */
	public function ship_info_edit()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get("sid"),'int');
    	if($id)
    	{
    		$tb_ship   = new IModel("merch_ship_info");
    		$ship_info = $tb_ship->getObj("id=".$id." and seller_id = ".$this->seller['seller_id']);
    		if($ship_info)
    		{
    			$this->data = $ship_info;
    		}
    		else
    		{
    			die('数据不存在');
    		}
    	}
    	$this->setRenderData($this->data);
		$this->redirect('ship_info_edit');
	}
	/**
	 * @brief 设置发货信息的默认值
	 */
	public function ship_info_default()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::string(IReq::get('default'));
        $tb_merch_ship_info = new IModel('merch_ship_info');
        if($default == 1)
        {
            $tb_merch_ship_info->setData(array('is_default'=>0));
            $tb_merch_ship_info->update("seller_id = ".$this->seller['seller_id']);
        }
        $tb_merch_ship_info->setData(array('is_default' => $default));
        $tb_merch_ship_info->update("id = ".$id." and seller_id = ".$this->seller['seller_id']);
        $this->redirect('ship_info_list');
	}
	/**
	 * @brief 保存添加/修改发货信息
	 */
	public function ship_info_update()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('sid'),'int');
    	$ship_name = IFilter::act(IReq::get('ship_name'));
    	$ship_user_name = IFilter::act(IReq::get('ship_user_name'));
    	$sex = IFilter::act(IReq::get('sex'),'int');
    	$province =IFilter::act(IReq::get('province'),'int');
    	$city = IFilter::act(IReq::get('city'),'int');
    	$area = IFilter::act(IReq::get('area'),'int');
    	$address = IFilter::act(IReq::get('address'));
    	$postcode = IFilter::act(IReq::get('postcode'),'int');
    	$mobile = IFilter::act(IReq::get('mobile'));
    	$telphone = IFilter::act(IReq::get('telphone'));
    	$is_default = IFilter::act(IReq::get('is_default'),'int');

    	$tb_merch_ship_info = new IModel('merch_ship_info');

    	//判断是否已经有了一个默认地址
    	if(isset($is_default) && $is_default==1)
    	{
    		$tb_merch_ship_info->setData(array('is_default' => 0));
    		$tb_merch_ship_info->update('seller_id = 0');
    	}
    	//设置存储数据
    	$arr['ship_name'] = $ship_name;
	    $arr['ship_user_name'] = $ship_user_name;
	    $arr['sex'] = $sex;
    	$arr['province'] = $province;
    	$arr['city'] =$city;
    	$arr['area'] =$area;
    	$arr['address'] = $address;
    	$arr['postcode'] = $postcode;
    	$arr['mobile'] = $mobile;
    	$arr['telphone'] =$telphone;
    	$arr['is_default'] = $is_default;
    	$arr['is_del'] = 1;
    	$arr['seller_id'] = $this->seller['seller_id'];

    	$tb_merch_ship_info->setData($arr);
    	//判断是添加还是修改
    	if($id)
    	{
    		$tb_merch_ship_info->update('id='.$id." and seller_id = ".$this->seller['seller_id']);
    	}
    	else
    	{
    		$tb_merch_ship_info->add();
    	}
		$this->redirect('ship_info_list');
	}
	/**
	 * @brief 删除发货信息到回收站中
	 */
	public function ship_info_del()
	{
		// 获取POST数据
    	$id = IFilter::act(IReq::get('id'),'int');
		//加载 商家发货点信息
    	$tb_merch_ship_info = new IModel('merch_ship_info');
		if($id)
		{
			$tb_merch_ship_info->del(Util::joinStr($id)." and seller_id = ".$this->seller['seller_id']);
			$this->redirect('ship_info_list');
		}
		else
		{
			$this->redirect('ship_info_list',false);
			Util::showMessage('请选择要删除的数据');
		}
	}

	/**
	 * @brief 配送方式修改
	 */
    public function delivery_edit()
	{
		$data = array();
        $delivery_id = IFilter::act(IReq::get('id'),'int');

        if($delivery_id)
        {
            $delivery = new IModel('delivery_extend');
            $data = $delivery->getObj('delivery_id = '.$delivery_id.' and seller_id = '.$this->seller['seller_id']);
		}
		else
		{
			die('配送方式');
		}

		//获取省份
		$areaData = array();
		$areaDB = new IModel('areas');
		$areaList = $areaDB->query('parent_id = 0');
		foreach($areaList as $val)
		{
			$areaData[$val['area_id']] = $val['area_name'];
		}
		$this->areaList  = $areaList;
		$this->data_info = $data;
		$this->area      = $areaData;
        $this->redirect('delivery_edit');
	}

	/**
	 * 配送方式修改
	 */
    public function delivery_update()
    {
        //首重重量
        $first_weight = IFilter::act(IReq::get('first_weight'),'float');
        //续重重量
        $second_weight = IFilter::act(IReq::get('second_weight'),'float');
        //首重价格
        $first_price = IFilter::act(IReq::get('first_price'),'float');
        //续重价格
        $second_price = IFilter::act(IReq::get('second_price'),'float');
        //是否支持物流保价
        $is_save_price = IFilter::act(IReq::get('is_save_price'),'int');
        //地区费用类型
        $price_type = IFilter::act(IReq::get('price_type'),'int');
        //启用默认费用
        $open_default = IFilter::act(IReq::get('open_default'),'int');
        //支持的配送地区ID
        $area_groupid = serialize(IReq::get('area_groupid'));
        //配送地址对应的首重价格
        $firstprice = serialize(IReq::get('firstprice'));
        //配送地区对应的续重价格
        $secondprice = serialize(IReq::get('secondprice'));
        //保价费率
        $save_rate = IFilter::act(IReq::get('save_rate'),'float');
        //最低保价
        $low_price = IFilter::act(IReq::get('low_price'),'float');
		//配送ID
        $delivery_id = IFilter::act(IReq::get('deliveryId'),'int');

        $deliveryDB  = new IModel('delivery');
        $deliveryRow = $deliveryDB->getObj('id = '.$delivery_id);
        if(!$deliveryRow)
        {
        	die('配送方式不存在');
        }

        //如果选择指定地区配送就必须要选择地区
        if($price_type == 1 && !$area_groupid)
        {
			die('请设置配送地区');
        }

        $data = array(
        	'first_weight' => $first_weight,
        	'second_weight'=> $second_weight,
        	'first_price'  => $first_price,
        	'second_price' => $second_price,
        	'is_save_price'=> $is_save_price,
        	'price_type'   => $price_type,
        	'open_default' => $open_default,
        	'area_groupid' => $area_groupid,
        	'firstprice'   => $firstprice,
        	'secondprice'  => $secondprice,
        	'save_rate'    => $save_rate,
        	'low_price'    => $low_price,
        	'seller_id'    => $this->seller['seller_id'],
        	'delivery_id'  => $delivery_id,
        );
        $deliveryExtendDB = new IModel('delivery_extend');
        $deliveryExtendDB->setData($data);
        $deliveryObj = $deliveryExtendDB->getObj("delivery_id = ".$delivery_id." and seller_id = ".$this->seller['seller_id']);
        //已经存在了
        if($deliveryObj)
        {
        	$deliveryExtendDB->update('delivery_id = '.$delivery_id.' and seller_id = '.$this->seller['seller_id']);
        }
        else
        {
        	$deliveryExtendDB->add();
        }
		$this->redirect('delivery');
    }

	//[促销活动] 添加修改 [单页]
	function pro_rule_edit()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if($id)
		{
			$promotionObj = new IModel('promotion');
			$where = 'id = '.$id.' and seller_id='.$this->seller['seller_id'];
			$this->promotionRow = $promotionObj->getObj($where);
		}
		$this->redirect('pro_rule_edit');
	}

	//[促销活动] 添加修改 [动作]
	function pro_rule_edit_act()
	{
		$id           = IFilter::act(IReq::get('id'),'int');
		$user_group   = IFilter::act(IReq::get('user_group','post'));
		$promotionObj = new IModel('promotion');
		if(is_string($user_group))
		{
			$user_group_str = $user_group;
		}
		else
		{
			$user_group_str = ",".join(',',$user_group).",";
		}

		$dataArray = array(
			'name'       => IFilter::act(IReq::get('name','post')),
			'condition'  => IFilter::act(IReq::get('condition','post')),
			'is_close'   => IFilter::act(IReq::get('is_close','post')),
			'start_time' => IFilter::act(IReq::get('start_time','post')),
			'end_time'   => IFilter::act(IReq::get('end_time','post')),
			'intro'      => IFilter::act(IReq::get('intro','post')),
			'award_type' => IFilter::act(IReq::get('award_type','post')),
			'type'       => 0,
			'user_group' => $user_group_str,
			'award_value'=> IFilter::act(IReq::get('award_value','post')),
			'seller_id'  => $this->seller['seller_id'],
		);

		if(!in_array($dataArray['award_type'],array(1,2,6)))
		{
			IError::show('促销类型不符合规范',403);
		}

		$promotionObj->setData($dataArray);

		if($id)
		{
			$where = 'id = '.$id;
			$promotionObj->update($where);
		}
		else
		{
			$promotionObj->add();
		}
		$this->redirect('pro_rule_list');
	}

	//[促销活动] 删除
	function pro_rule_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if(!empty($id))
		{
			$promotionObj = new IModel('promotion');
			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
			}
			else
			{
				$where = 'id = '.$id;
			}
			$promotionObj->del($where.' and seller_id = '.$this->seller['seller_id']);
			$this->redirect('pro_rule_list');
		}
		else
		{
			$this->redirect('pro_rule_list',false);
			Util::showMessage('请选择要删除的促销活动');
		}
	}

	//修改订单价格
	public function order_discount()
	{
		$order_id = IFilter::act(IReq::get('order_id'),'int');
		$discount = IFilter::act(IReq::get('discount'),'float');
		$orderDB  = new IModel('order');
		$orderRow = $orderDB->getObj('id = '.$order_id.' and status = 1 and distribution_status = 0 and seller_id = '.$this->seller['seller_id']);
		if($orderRow)
		{
			//还原价格
			$newOrderAmount = ($orderRow['order_amount'] - $orderRow['discount']) + $discount;
			$orderDB->setData(array('discount' => $discount,'order_amount' => $newOrderAmount));
			if($orderDB->update('id = '.$order_id))
			{
				die(JSON::encode(array('result' => true,'orderAmount' => $newOrderAmount)));
			}
		}
		die(JSON::encode(array('result' => false)));
	}

	// 消息通知
	public function message_list()
	{
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$seller_messObject = new seller_mess($this->seller['seller_id']);
		$msgIds = $seller_messObject->getAllMsgIds();
		$msgIds = empty($msgIds) ? 0 : $msgIds;
		$needReadNum = $seller_messObject->needReadNum();

		$seller_messageHandle = new IQuery('seller_message');
		$seller_messageHandle->where = "id in(".$msgIds.")";
		$seller_messageHandle->order= "id desc";
		$seller_messageHandle->page = $page;

		$this->needReadNum = $needReadNum;
		$this->seller_messObject = $seller_messObject;
		$this->seller_messageHandle = $seller_messageHandle;

		$this->redirect("message_list");
	}

	// 消息详情
	public function message_show()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if (!empty($id))
		{
			$seller_messObject = new seller_mess($this->seller['seller_id']);
			$seller_messObject->writeMessage($id, 1);
		}
		$this->redirect('message_show');
	}

	// 消息删除
	public function message_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if (!empty($id))
		{
			$seller_messObject = new seller_mess($this->seller['seller_id']);
			if (is_array($id)) {
				foreach ($id as $val)
				{
					$seller_messObject->delMessage($val);
				}
			}else {
				$seller_messObject->delMessage($id);
			}
		}
		$this->redirect('message_list');
	}

	//订单备注
	public function order_note()
	{
	 	//获得post数据
	 	$order_id = IFilter::act(IReq::get('order_id'),'int');
	 	$note = IFilter::act(IReq::get('note'),'text');

	 	//获得order的表对象
	 	$tb_order =  new IModel('order');
	 	$tb_order->setData(array(
	 		'note'=>$note
	 	));
	 	$tb_order->update('id = '.$order_id.' and seller_id = '.$this->seller['seller_id']);
	 	$this->redirect("/seller/order_show/id/".$order_id,true);
	}

	/**
	 * @brief 删除咨询信息
	 */
	function refer_del()
	{
		$refer_ids = IFilter::act(IReq::get('id'),'int');
		$refer_ids = is_array($refer_ids) ? $refer_ids : array($refer_ids);
		if($refer_ids)
		{
			$ids = join(',',$refer_ids);
			if($ids)
			{
				//查询咨询的商品是否属于当前商户
				$referDB        = new IQuery('refer as re,goods as go');
				$referDB->where = "re.id in (".$ids.") and re.goods_id = go.id and go.seller_id = ".$this->seller['seller_id'];
				$referDB->fields= "re.id";
				$referGoods     = $referDB->find();
				$referModel     = new IModel('refer');
				foreach($referGoods as $reId)
				{
					$referModel->del($reId['id']);
				}
			}
		}
		$this->redirect('refer_list');
	}
    
    //发票列表
    public function fapiao_list(){
        $status = IReq::get('status');
        $search = Util::search(IReq::get('search'));$whereAdd = $search ? " and ".$search : "";
        $seller_id = $this->seller['seller_id'];
        $page=(isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
        $fapiao_db = new IQuery('order_fapiao as f');
        $fapiao_db->join = 'left join order as o on o.id = f.order_id   left join user as u on u.id = f.user_id';
        $fapiao_db->where = 'f.seller_id ='. $seller_id.' AND f.status = '.$status.$whereAdd;
        
        $fapiao_db->order = 'f.id DESC';
        $fapiao_db->page = $page;
        $fapiao_db->fields = 'f.*,o.order_no,u.username';
        $this->fapiaoData = $fapiao_db->find();
    
        $this->db = $fapiao_db;
        $this->redirect('fapiao_list');
    }
    
    //显示发票详情
    public function fapiao_show(){
        $seller_id = $this->seller['seller_id'];
        $id = IFilter::act(IReq::get('id'),'int');
        $db_fa = new IQuery('order_fapiao as f');
        $db_fa->join = 'left join order as o on o.id = f.order_id  left join user as u on u.id = f.user_id';
        $db_fa->where = 'f.id ='. $id.' AND f.seller_id = '.$seller_id;
        $db_fa->limit = 1;
        $db_fa->fields = 'u.username,o.order_no,o.real_amount,f.*';
        $data = $db_fa->find();
        $data = $data[0];
        if($data['money']==0)$data['money']=$data['real_amount'];
        
        $this->setRenderData($data);
        $this->redirect('fapiao_show');
    }
    //发票处理
    public function fapiao_show_save(){
        $id = IFilter::act(IReq::get('id'),'int');
        $money = IFilter::act(IReq::get('money'),'float');
        if(!$id || !$money){
            $this->redirect('fapiao_list/status/1');
        }
        $db_fa = new IModel('order_fapiao');
        $data=array(
                'money'=>$money,
                'status'=>1
        );
        $db_fa->setData($data);
        $db_fa->update('id='.$id);
        $this->redirect('fapiao_list/status/1');
    }
    
    //商家申请服务费发票列表
    public function bill_fapiao_list(){
        $search = Util::search(IReq::get('search'));$whereAdd = $search ? " and ".$search : "";
        $seller_id = $this->seller['seller_id'];
        $page=(isset($_GET['page'])&&(intval($_GET['page'])>0))?intval($_GET['page']):1;
        $fapiao_db = new IQuery('bill_fapiao');
        $fapiao_db->where = 'seller_id ='. $seller_id.$whereAdd;
        
        $fapiao_db->order = 'id DESC';
        $fapiao_db->page = $page;
        $this->fapiaoData = $fapiao_db->find();
    
        $this->db = $fapiao_db;
        $this->redirect('bill_fapiao_list');
    }
    
    //商家申请发票页面
    public function bill_fapiao()
    {
        $sellerDB = new IQuery('seller as s');
        $sellerDB->join = 'left join bill_fapiao as b on s.id = b.seller_id';
        $sellerDB->where = 's.id = '.$this->seller['seller_id'].' and (b.bill_id = -1 or s.is_invoice = 1)';
        $sellerDB->fields = 'b.id, b.status';
        $this->data = $sellerDB->find();
        
        $billDB = new IModel('bill');
        $billRow = $billDB->query('seller_id = '.$this->seller['seller_id'].' and  is_pay = 1 and is_agree = 1 and is_invoice = 0', 'id,apply_content,para,start_time,end_time');
        $fapiaoDB = new IModel('bill_fapiao');
        foreach($billRow as $k => $v)
        {
            if($fapiaoDB->getObj('bill_id = '.$v['id'], 'id'))
            {
                unset($billRow[$k]);
            }
        }
        $this->billRow = $billRow;
        if($this->data && count($this->billRow) == 0)
        {
            die('没有需要申请的发票');
        }
        $this->redirect('bill_fapiao');
    }
    
    //商家申请发票提交
    public function bill_fapiao_save()
    {
        $bill_id = IReq::get('bill_id') ? IFilter::act(IReq::get('bill_id'), 'int') : 0;
        if($bill_id == 0)
        {
            die('请选择结算单');
        }
        $type = IReq::get('type') ? IFilter::act(IReq::get('type'), 'int') : 0;
        $data = array();
        $data['bill_id'] = $bill_id;
        $data['money'] = IReq::get('money');
        if($type == 1)
        {
            $data['com'] = IReq::get('com') ? IFilter::act(IReq::get('com')) : '';
            $data['tax_no'] = IReq::get('tax_no') ? IFilter::act(IReq::get('tax_no')) : '';
            $data['address'] = IReq::get('address') ? IFilter::act(IReq::get('address')) : '';
            $data['telphone'] = IReq::get('telphone') ? IFilter::act(IReq::get('telphone')) : '';
            $data['bank'] = IReq::get('bank') ? IFilter::act(IReq::get('bank')) : '';
            $data['account'] = IReq::get('account') ? IFilter::act(IReq::get('account')) : '';
        }
        else
        {
            $data['taitou'] = IReq::get('taitou') ? IFilter::act(IReq::get('taitou')) : '';
        }
        $data['type'] = $type;
        $data['status'] = 0;
        $data['seller_id'] = $this->seller['seller_id'];
        $data['create_time'] = ITime::getNow('Y-m-d H:i:s');
        $fapiao = new IModel('bill_fapiao');
        $fapiao->setData($data);
        $fapiao->add();
        $this->redirect('bill_fapiao_list');
    }
    
    /**
     * @brief 品牌分类添加、修改
     */
    function category_edit()
    {
        $this->layout = '';
        
        $id        = IFilter::act(IReq::get('id'),'int');
        $seller_id = $this->seller['seller_id'];
        $category_info = array(
            'id'                => '',
            'name'              => '',
            'goods_category_id' => '',
            'seller_id'         => $seller_id,
        );
        //编辑品牌分类 读取品牌分类信息
        
        if($id)
        {
            $obj_brand_category = new IModel('brand_category');
            $category_info = $obj_brand_category->getObj('id='.$id.' and seller_id = '.$seller_id);

        }
        $this->cateRow = $category_info;
        $this->redirect('category_edit');
    }

    /**
     * @brief 保存品牌分类
     */
    function category_save()
    {
        $id                = IFilter::act(IReq::get('id'),'int');
        $goods_category_id = IFilter::act(IReq::get('goods_category_id'),'int');
        $name              = IFilter::act(IReq::get('name'));
        $seller_id         = $this->seller['seller_id'];

        $category_info = array(
            'name' => $name,
            'goods_category_id' => $goods_category_id,
            'seller_id' => $seller_id
        );
        $tb_brand_category = new IModel('brand_category');
        $tb_brand_category->setData($category_info);

        //更新品牌分类
        if($id)
        {
            $where = "id=".$id;
            $result = $tb_brand_category->update($where);
        }
        //添加品牌分类
        else
        {
            $result = $tb_brand_category->add();
        }
        
        //执行状态
        if($result===false)
        {
            die( JSON::encode(array('flag' => 'fail','message' => '分类添加失败')) );
        }
        else
        {
            //获取自动增加ID
            $editData['id'] = $id ? $id : $result;
            die( JSON::encode(array('flag' => 'success','data' => $editData)) );
        }
    }

    /**
     * @brief 删除品牌分类
     */
    function category_del()
    {
        $category_id = IFilter::act(IReq::get('id'), 'int');
        if($category_id)
        {
            $brand_category = new IModel('brand_category');
            $brand = new IModel('brand');
            if(is_array($category_id))
            {
                foreach($category_id as $k => $v)
                {
                    if($brand->query('FIND_IN_SET('.$v.',category_ids)', 'id'))
                    {
                        $message = "要删除的分类下还有品牌，请先处理相关品牌！";
                        unset($category_id[$k]);
                        break;
                    }
                }
                if($category_id)
                {
                    $tem = implode(',', $category_id);
                    $where = "id in (".$tem.") and seller_id = ".$this->seller['seller_id'];
                }
                else
                {
                    IError::show("要删除的分类下还有品牌，请先处理相关品牌！", 403);
                }
            }
            else
            {
                if($brand->query('FIND_IN_SET('.$category_id.',category_ids)', 'id'))
                {
                    IError::show("要删除的分类下还有品牌，请先处理相关品牌！", 403);
                }
                $where = "id=".$category_id.' and seller_id = '.$this->seller['seller_id'];
            }
            if($brand_category->del($where))
            {
                if(isset($message))
                {
                    IError::show("要删除的分类下还有品牌，请先处理相关品牌！", 403);
                }
                $this->redirect('category_list');
            }
            else
            {
                $this->redirect('category_list');
                IError::show("没有找到相关分类记录！", 403);
            }
        }
        else
        {
            $this->redirect('category_list');
            IError::show("没有找到相关分类记录！", 403);
        }
    }

    /**
     * @brief 修改品牌
     */
    function brand_edit()
    {
        $brand_id = (int)IReq::get('id');
        $seller_id = $this->seller['seller_id'];
        $brand_info = array();
        //编辑品牌 读取品牌信息
        if($brand_id)
        {
            $obj_brand = new IModel('brand');
            $brand_info = $obj_brand->getObj('id='.$brand_id.' and seller_id = '.$seller_id);
            if(!$brand_info)
            {
                $this->redirect('brand_list');
                IError::show("没有找到相关品牌！", 403);
            }
        }
        $this->setRenderData($brand_info);
        $this->redirect('brand_edit',false);
    }

    /**
     * @brief 保存品牌
     */
    function brand_save()
    {
        $brand_id = IFilter::act(IReq::get('brand_id'),'int');
        $name = IFilter::act(IReq::get('name'));
        $sort = IFilter::act(IReq::get('sort'),'int');
        $url = IFilter::act(IReq::get('url'));
        $category = IFilter::act(IReq::get('category'),'int');
        $description = IFilter::act(IReq::get('description'),'text');
        $seller_id = $this->seller['seller_id'];

        $tb_brand = new IModel('brand');
        $brand = array(
            'name'=>$name,
            'sort'=>$sort,
            'url'=>$url,
            'description' => $description,
            'seller_id' => $seller_id
        );

        if($category && is_array($category))
        {
            $categorys = join(',',$category);
            $brand['category_ids'] = $categorys;
        }
        else
        {
            $brand['category_ids'] = '';
        }
        if(isset($_FILES['logo']['name']) && $_FILES['logo']['name']!='')
        {
            $uploadObj = new PhotoUpload();
            $uploadObj->setIterance(false);
            $photoInfo = $uploadObj->run();
            if(isset($photoInfo['logo']['img']) && file_exists($photoInfo['logo']['img']))
            {
                $brand['logo'] = $photoInfo['logo']['img'];
            }
        }
        $tb_brand->setData($brand);
        if($brand_id)
        {
            //保存修改分类信息
            $where = "id=".$brand_id;
            $tb_brand->update($where);
        }
        else
        {
            //添加新品牌
            $tb_brand->add();
        }
        $this->redirect('brand_list');
    }

    /**
     * @brief 删除品牌
     */
    function brand_del()
    {
        $brand_id = IFilter::act(IReq::get('id'), 'int');
        if($brand_id)
        {
            $tb_brand = new IModel('brand');
            if(is_array($brand_id))
            {
                $tem = implode(',',$brand_id);
                $where = "id in (".$tem.') and seller_id = '.$this->seller['seller_id'];
            }
            else
            {
                $where = "id=".$brand_id.' and seller_id = '.$this->seller['seller_id'];
            }
            if($tb_brand->del($where))
            {
                $this->redirect('brand_list');
            }
            else
            {
                $this->redirect('brand_list');
                IError::show("没有找到相关品牌！", 403);
            }
        }
        else
        {
            $this->brand_list();
            IError::show("没有找到相关品牌记录！", 403);
        }
    }
    
    /**
     * @brief 运营商删除绑定用户/商家
     */
    function bind_del()
    {                    
        $id = IFilter::act(IReq::get('id'),'int');
        $model = new IModel('operational_user');
        $where =  'id = '.$id.' and operation_id ='.$this->seller['seller_id'];
        $row = $model->getObj($where, 'type');
        $type = $row ? $row['type'] : 1;
        $redirectUrl = $type == 1 ? 'bind_user_list' : 'bind_seller_list';
        if(!$row)
        {
            $this->redirect($redirectUrl);
            IError::show("参数错误！", 403);
        }                                
        $model->del($where);
        $this->redirect($redirectUrl);
    }
    
    /**
     * @brief 运营商绑定用户/商家
     */
     function bind_act($id,$type=1)
     {
        $redirectUrl = $type == 1 ? 'bind_user_list' : 'bind_seller_list';
        $msg = $type == 1 ? '该用户' : '该商家';
        $model = new IModel('operational_user');
        if($model->getObj('object_id = '.$id.' and type ='.$type))
        {
            $this->redirect($redirectUrl);
            IError::show($msg."已被绑定！", 403);
        }
        $data = array(
                     'object_id' => $id,
                     'operation_id' => $this->seller['seller_id'],
                     'type' => $type ,
                     'time' => ITime::getDateTime()
                );
        $model->setData($data);
        $model->add();
        $this->redirect($redirectUrl);
     }
    
    /**
     * @brief 运营商绑定用户
     */
     function bind_user_act()
     {
        $id = IReq::get('id');
        $this->bind_act($id, 1);
     }
    
    /**
     * @brief 运营商绑定商家
     */
     function bind_seller_act()
     {
        $id = IReq::get('id');
        $this->bind_act($id, 2);
     }
}