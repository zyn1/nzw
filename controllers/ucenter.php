<?php
/**
 * @brief 用户中心模块
 * @class Ucenter
 * @note  前台
 */
class Ucenter extends IController implements userAuthorization
{
	public $layout = 'ucenter';

	public function init()
	{

	}
    public function index()
    {
    	//获取用户基本信息
		$user = Api::run('getMemberInfo',$this->user['user_id'],$this->user['type']);

		//获取用户各项统计数据
		$statistics = Api::run('getMemberTongJi',$this->user['user_id'],$this->user['type']);
             
		//获取用户站内信条数
		$msgObj = new Mess($this->user['user_id'],$this->user['type']);
		$msgNum = $msgObj->needReadNum();

        $data = array(
            "user"       => $user,
            "statistics" => $statistics,
            "msgNum"     => $msgNum
            );
        if($this->user['type'] == 1)
        {
            //获取用户代金券
            $propIds = trim($user['prop'],',');
            $propIds = $propIds ? $propIds : 0;
            $propData= Api::run('getPropTongJi',$propIds);
            $data['propData'] = $propData;
        }
		

		$this->setRenderData($data);

        $this->initPayment();
        $this->redirect('index');
    }

	//[用户头像]上传
	function user_ico_upload()
	{
		$result = array(
			'isError' => true,
		);

		if(isset($_FILES['attach']['name']) && $_FILES['attach']['name'] != '')
		{
			$photoObj = new PhotoUpload();
			$photo    = $photoObj->run();

			if($photo['attach']['img'])
			{
				$user_id   = $this->user['user_id'];
				$user_obj  = new IModel('user');
				$dataArray = array(
					'head_ico' => $photo['attach']['img'],
				);
				$user_obj->setData($dataArray);
				$where  = 'id = '.$user_id;
				$isSuss = $user_obj->update($where);

				if($isSuss !== false)
				{
					$result['isError'] = false;
					$result['data'] = IUrl::creatUrl().$photo['attach']['img'];
					ISafe::set('head_ico',$dataArray['head_ico']);
				}
				else
				{
					$result['message'] = '上传失败';
				}
			}
			else
			{
				$result['message'] = '上传失败';
			}
		}
		else
		{
			$result['message'] = '请选择图片';
		}
        if(IClient::getDevice() == IClient::PC)
        {
            echo '<script type="text/javascript">parent.callback_user_ico('.JSON::encode($result).');</script>';
        }
		else
        {
            $this->info_edit();
        }
	}

    /**
     * @brief 我的订单列表
     */
    public function order()
    {
        $this->initPayment();
        $this->orderStatus = IReq::get('_s');
        $this->redirect('order');

    }
    /**
     * @brief 初始化支付方式
     */
    private function initPayment()
    {
        $payment = new IQuery('payment');
        $payment->fields = 'id,name,type';
        $payments = $payment->find();
        $items = array();
        foreach($payments as $pay)
        {
            $items[$pay['id']]['name'] = $pay['name'];
            $items[$pay['id']]['type'] = $pay['type'];
        }
        $this->payments = $items;
    }
    /**
     * @brief 订单详情
     * @return String
     */
    public function order_detail()
    {
        $id = IFilter::act(IReq::get('id'),'int');

        $orderObj = new order_class();
        $this->order_info = $orderObj->getOrderShow($id,$this->user['user_id']);
        if(!$this->order_info)
        {
        	IError::show(403,'订单信息不存在');
        }
        $orderStatus = Order_Class::getOrderStatus($this->order_info);
        $this->setRenderData(array('orderStatus' => $orderStatus));
        
        //自动完成订单倒计时
        if(in_array($orderStatus,array(11,3)))
        {
            $pluginDB    = new IModel('plugin');
            if($dataRow = $pluginDB->getObj("class_name = 'orderAutoUpdate' and is_open = 1", 'config_param'))
            {
                $configData = JSON::decode($dataRow['config_param']);
                $order_finish_time = isset($configData['order_finish_time']) ? intval($configData['order_finish_time']) : 0;
                $send_time = $this->order_info['send_time'];
                $finish_time = strtotime("$send_time + $order_finish_time days");
                $this->time = $finish_time-ITime::getNow();
            }
        }
        
        $this->redirect('order_detail',false);
    }

    //操作订单状态
	public function order_status()
	{
		$op    = IFilter::act(IReq::get('op'));
		$id    = IFilter::act( IReq::get('order_id'),'int' );
		$model = new IModel('order');

		switch($op)
		{
			case "cancel":
			{
				$model->setData(array('status' => 3));
				if($model->update("id = ".$id." and distribution_status = 0 and status = 1 and user_id = ".$this->user['user_id']))
				{
					order_class::resetOrderProp($id);
				}
			}
			break;

			case "confirm":
			{
				$model->setData(array('status' => 5,'completion_time' => ITime::getDateTime()));
				if($model->update("id = ".$id." and distribution_status = 1 and user_id = ".$this->user['user_id']))
				{
					$orderRow = $model->getObj('id = '.$id);

					//确认收货后进行支付
					Order_Class::updateOrderStatus($orderRow['order_no']);

		    		//增加用户评论商品机会
		    		Order_Class::addGoodsCommentChange($id);

		    		//确认收货以后直接跳转到评论页面
		    		$this->redirect('evaluation');
				}
			}
			break;
		}
        if(IClient::getDevice() == IClient::PC || !is_null(IReq::get('_call')))
        {
            $this->redirect("order_detail/id/$id");
        }
		else
        {
            $this->redirect("order");
        }
	}

    //物流轨迹查询
    public function logistics()
    {
        $id = IFilter::act(IReq::get('p'),'int');

        if($id)
        {
            $tb_freight = new IQuery('delivery_doc as d');
            $tb_freight->join  = 'left join freight_company as f on f.id = d.freight_id';
            $tb_freight->where = 'd.id = '.$id;
            $tb_freight->fields= 'd.*,f.freight_type,f.freight_name';
            $freightData = $tb_freight->find();
            if($freightData)
            {
                $freightData = current($freightData);
                $this->setRenderData($freightData);
                if($freightData['freight_type'] && $freightData['delivery_code'])
                {
                    $result = freight_facade::line($freightData['freight_type'],$freightData['delivery_code']);
                    if($result['result'] == 'success')
                    {
                        $this->data = array_reverse($result['data']);
                    }
                    else
                    {
                        
                        $this->msg = isset($result['reason']) ? $result['reason'] : '物流接口发生错误';
                    }
                }
                else
                {
                    $this->msg = '缺少物流信息';
                }
            }
        }
        $this->msg = '发货单信息不存在';
        $this->redirect('logistics');
    }
    /**
     * @brief 我的地址
     */
    public function addressChoose()
    {
        //取得自己的地址
        $query = new IQuery('address');
        $query->where = 'user_id = '.$this->user['user_id'];
        $address = $query->find();
        $areas   = array();

        if($address)
        {
            foreach($address as $ad)
            {
                $temp = area::name($ad['province'],$ad['city'],$ad['area']);
                if(isset($temp[$ad['province']]) && isset($temp[$ad['city']]) && isset($temp[$ad['area']]))
                {
                    $areas[$ad['province']] = $temp[$ad['province']];
                    $areas[$ad['city']]     = $temp[$ad['city']];
                    $areas[$ad['area']]     = $temp[$ad['area']];
                }
            }
        }

        $this->areas = $areas;
        $this->address = $address;
        $this->redirect('addressChoose');
    }
    /**
     * @brief 我的地址
     */
    public function address()
    {
		//取得自己的地址
		$query = new IQuery('address');
        $query->where = 'user_id = '.$this->user['user_id'];
		$address = $query->find();
		$areas   = array();

		if($address)
		{
			foreach($address as $ad)
			{
				$temp = area::name($ad['province'],$ad['city'],$ad['area']);
				if(isset($temp[$ad['province']]) && isset($temp[$ad['city']]) && isset($temp[$ad['area']]))
				{
					$areas[$ad['province']] = $temp[$ad['province']];
					$areas[$ad['city']]     = $temp[$ad['city']];
					$areas[$ad['area']]     = $temp[$ad['area']];
				}
			}
		}

		$this->areas = $areas;
		$this->address = $address;
        $this->redirect('address');
    }
    
    public function address_add()
    {
        $id = IFilter::act(IReq::get('id'), 'int');
        $user_id = $this->user['user_id'];
        $addressDB = new IModel('address');
        $addressRow = $addressDB->getObj('id = '.$id.' and user_id = '.$user_id);
        $this->addressRow = $addressRow;
        $this->redirect('address_add');
    }
    
    /**
     * @brief 收货地址管理
     */
	public function address_edit()
	{
		$id          = IFilter::act(IReq::get('id'),'int');
		$accept_name = IFilter::act(IReq::get('accept_name'),'name');
		$province    = IFilter::act(IReq::get('province'),'int');
		$city        = IFilter::act(IReq::get('city'),'int');
		$area        = IFilter::act(IReq::get('area'),'int');
		$address     = IFilter::act(IReq::get('address'));
		$zip         = IFilter::act(IReq::get('zip'),'zip');
		$telphone    = IFilter::act(IReq::get('telphone'),'phone');
		$mobile      = IFilter::act(IReq::get('mobile'),'mobile');
		$default     = IReq::get('is_default')!= 1 ? 0 : 1;
        $user_id     = $this->user['user_id'];

		$model = new IModel('address');
		$data  = array('user_id'=>$user_id,'accept_name'=>$accept_name,'province'=>$province,'city'=>$city,'area'=>$area,'address'=>$address,'zip'=>$zip,'telphone'=>$telphone,'mobile'=>$mobile,'is_default'=>$default);

        //如果设置为首选地址则把其余的都取消首选
        if($default==1)
        {
            $model->setData(array('is_default' => 0));
            $model->update("user_id = ".$this->user['user_id']);
        }

		$model->setData($data);

		if($id == '')
		{
			$model->add();
		}
		else
		{
			$model->update('id = '.$id);
		}
		$this->redirect('address');
	}
    /**
     * @brief 收货地址删除处理
     */
	public function address_del()
	{
		$id = IFilter::act( IReq::get('id'),'int' );
		$model = new IModel('address');
		$model->del('id = '.$id.' and user_id = '.$this->user['user_id']);
		$this->redirect('address');
	}
    /**
     * @brief 设置默认的收货地址
     */
    public function address_default()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $default = IFilter::act(IReq::get('is_default'));
        $model = new IModel('address');
        if($default == 1)
        {
            $model->setData(array('is_default' => 0));
            $model->update("user_id = ".$this->user['user_id']);
        }
        $model->setData(array('is_default' => $default));
        $model->update("id = ".$id." and user_id = ".$this->user['user_id']);
        $this->redirect('address');
    }
    /**
     * @brief 提交退货单号
     */
    public function refunds_update2()
    {
        $id            = IFilter::act( IReq::get('id'),'int' );
        $type          = IFilter::act( IReq::get('type'),'int' );
        $order_no      = IFilter::act( IReq::get('num') );
        $company       = IFilter::act(IReq::get('company'));
        $delivery_code = IFilter::act(IReq::get('delivery_code'));
        $address       = IFilter::act(IReq::get('address'));
        $info          = IFilter::act(IReq::get('info'), 'text');
        
        if($type == 1)
        {
            $redirectUrl = 'refunds';
        }
        else
        {
            $redirectUrl = 'changeRefunds';
        }
        if(!$id || !$order_no)
        {
            $this->redirect($redirectUrl,false);
            Util::showMessage('缺少参数！');
        }
        $refundmentDB = new IModel('refundment_doc');
        if(!$refundmentDB->getObj('id = '.$id.' and order_no = "'.$order_no.'" and if_del = 0 and pay_status = 3', 'id'))
        {
            $this->redirect($redirectUrl,false);
            Util::showMessage('参数错误！');
        }
        
        //退款单数据
        $updateData = array(
            'refund_id'     => $id,
            'company'       => $company,
            'delivery_code' => $delivery_code,
            'address'       => $address,
            'info'          => $info
        );

        //写入数据库
        $refundsExtendDB = new IModel('refundment_extend');
        $refundsExtendDB->setData($updateData);
        $result = $refundsExtendDB->add();
        if($result)
        {
            $refundmentDB->setData(array('pay_status' => 4));
            $refundmentDB->update('id = '.$id);
            $message = "提交成功，请耐心等待";
        }
        else
        {
            $message = "系统错误";
        }
        $this->redirect($redirectUrl,false);
        Util::showMessage($message);
    }
    /**
     * @brief 退款申请页面
     */
    public function refunds_update()
    {
        $order_goods_id = IFilter::act( IReq::get('order_goods_id'),'int' );
        $order_id       = IFilter::act( IReq::get('order_id'),'int' );
        $type           = IFilter::act( IReq::get('type'),'int' );
        $user_id        = $this->user['user_id'];
        $content        = IFilter::act(IReq::get('content'),'text');
        $message        = '';

        if(!$type)
        {
            $message = "请选择退换类型";
            $this->redirect('refunds',false);
            Util::showMessage($message);
        }
        if(!$content || !$order_goods_id)
        {
        	$message = "请填写退款理由和选择要退款的商品";
	        $this->redirect('refunds',false);
	        Util::showMessage($message);
        }

        $orderDB      = new IModel('order');
        $orderRow     = $orderDB->getObj("id = ".$order_id." and user_id = ".$user_id);
        if($type == 1)
        {
            $refundResult = Order_Class::isRefundmentApply($orderRow,$order_goods_id);
        }
        if($type == 2)
        {
            $refundResult = Order_Class::isChangeApply($orderRow,$order_goods_id);
        }
        
        $redirectUrl = $type == 1 ? 'refunds' : 'changeRefunds';
            
        //判断退款申请是否有效
        if($refundResult === true)
        {
			//退款单数据
    		$updateData = array(
				'order_no'       => $orderRow['order_no'],
                'order_id'       => $order_id,
				'type'           => $type,
				'user_id'        => $user_id,
				'time'           => ITime::getDateTime(),
				'content'        => $content,
				'seller_id'      => $orderRow['seller_id'],
				'order_goods_id' => join(",",$order_goods_id),
			);

    		//写入数据库
    		$refundsDB = new IModel('refundment_doc');
    		$refundsDB->setData($updateData);
            
            //查询当前订单是否已有退换单
            //订单中商品的数量
            $orderGoodsDB = new IModel('order_goods');
            $goodsCount = $orderGoodsDB->getObj('order_id = '.$order_id.' and is_send <> 2', 'count(*) as num');
            
            if($goodsCount['num'] == 0)
            {
                //没有未退换的商品
                $data['refunds_status'] = 0;
            }
            elseif($goodsCount['num'] == 1)
            {
                //订单中只有一件商品
                $data['refunds_status'] = $type == 1 ? 1 : 2;
            }
            else
            {
                //一次全部申请退换
                if(count($order_goods_id) >= $goodsCount['num'])
                {
                    $data['refunds_status'] = $type == 1 ? 1 : 2;
                }
                else
                {
                    //订单中已申请退货商品数量
                    $refundRow = $refundsDB->getObj('order_id ='.$order_id.' and type =1 and if_del=0 and (pay_status = 0 or pay_status = 3 or pay_status = 4)','count(*) as num');
                    //订单中已申请换货商品数量
                    $changeRow = $refundsDB->getObj('order_id ='.$order_id.' and type = 2 and if_del=0 and (pay_status = 0 or pay_status = 3 or pay_status = 4)','count(*) as num');
                    if($refundRow['num'] == 0 && $changeRow['num'] == 0)
                    {
                        $data['refunds_status'] = $type == 1 ? 3 : 4;
                    }
                    elseif($refundRow['num'] > 0 && $changeRow['num'] == 0)
                    {
                        $data['refunds_status'] = $type == 1 ? (($refundRow['num'] + count($order_goods_id)) >= $goodsCount['num'] ? 1 : 3) : (($refundRow['num'] + count($order_goods_id)) >= $goodsCount['num'] ? 6 : 5);
                    }
                    elseif($refundRow['num'] == 0 && $changeRow['num'] > 0)
                    {
                        $data['refunds_status'] = $type == 1 ? (($changeRow['num'] + count($order_goods_id)) >= $goodsCount['num'] ? 6 : 5) : (($changeRow['num'] + count($order_goods_id)) >= $goodsCount['num'] ? 2 : 4);
                    }
                    else
                    {
                        $data['refunds_status'] = ($refundRow['num'] + $changeRow['num'] + count($order_goods_id)) >= $goodsCount['num'] ? 6 : 5;
                    }
                }
            }
            $refundsDB->add();
            $orderDB->setData($data);
            $orderDB->update('id = '.$order_id);
            $this->redirect($redirectUrl);
        }
        else
        {
        	$message = $refundResult;
            $this->redirect($redirectUrl, false);
	        Util::showMessage($message);
        }
    }
    /**
     * @brief 退款申请删除
     */
    public function refunds_del()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $refundsDB = new IModel("refundment_doc");
        $orderId = $refundsDB->getObj("id = ".$id." and user_id = ".$this->user['user_id'], 'order_id');
        $order_id = $orderId['order_id'];
        $refundsDB->del("id = ".$id." and user_id = ".$this->user['user_id']);

        //查询订单中商品的数量
        $orderGoodsDB = new IModel('order_goods');
        $goodsCount = $orderGoodsDB->getObj('order_id = '.$order_id.' and is_send <> 2', 'count(*) as num');
        
        //查询是否还有该订单的申请退换货信息---退货
        $refundRow = $refundsDB->getObj('order_id ='.$order_id.' and type =1 and if_del=0 and (pay_status = 0 or pay_status = 3 or pay_status = 4)','count(*) as num');
        //换货
        $changeRow = $refundsDB->getObj('order_id ='.$order_id.' and type = 2 and if_del=0 and (pay_status = 0 or pay_status = 3 or pay_status = 4)','count(*) as num');
        $data = array();
        if($refundRow['num'] == 0 && $changeRow['num'] == 0)
        {
            $data['refunds_status'] = 0;
        }
        elseif($refundRow['num'] > 0 && $changeRow['num'] == 0)
        {
            $data['refunds_status'] = ($refundRow['num'] >= $goodsCount['num']) ? 1 : 3;
        }
        elseif($refundRow['num'] == 0 && $changeRow['num'] > 0)
        {
            $data['refunds_status'] = ($changeRow['num'] >= $goodsCount['num']) ? 2 : 4;
        }
        else
        {
            $data['refunds_status'] = ($refundRow['num'] + $changeRow['num']) >= $goodsCount['num'] ? 6 : 5;
        }
        $orderDB = new IModel('order');
        $orderDB->setData($data);
        $orderDB->update('id = '.$order_id);
        
        $this->redirect('refunds');
    }
    /**
     * @brief 查看退款申请详情
     */
    public function refunds_detail()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $refundDB = new IModel("refundment_doc");
        $refundRow = $refundDB->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
        if($refundRow)
        {
        	//获取商品信息
        	$orderGoodsDB   = new IModel('order_goods');
        	$orderGoodsList = $orderGoodsDB->query("id in (".$refundRow['order_goods_id'].")");
        	if($orderGoodsList)
        	{
        		$refundRow['goods'] = $orderGoodsList;
        		$this->data = $refundRow;
        	}
        	else
        	{
	        	$this->redirect('refunds',false);
	        	Util::showMessage("没有找到要退款的商品");
        	}
            
            //自动同意倒计时
            if($refundRow['pay_status'] == 0)
            {
                $pluginDB    = new IModel('plugin');
                if($dataRow = $pluginDB->getObj("class_name = 'orderAutoUpdate' and is_open = 1", 'config_param'))
                {
                    $configData = JSON::decode($dataRow['config_param']);
                    $order_agree_time = isset($configData['order_agree_time']) ? intval($configData['order_agree_time']) : 0;
                    $time = $refundRow['time'];
                    $agree_time = strtotime("$time + $order_agree_time days");
                    $this->time = $agree_time-ITime::getNow();
                }
            }
        	$this->redirect('refunds_detail');
        }
        else
        {
        	$this->redirect('refunds',false);
        	Util::showMessage("退款信息不存在");
        }
    }
    
    public function refund_order()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $refundDB = new IModel("refundment_doc");
        $refundRow = $refundDB->getObj("id = ".$id." and user_id = ".$this->user['user_id']);
        if($refundRow)
        {
            $this->data = $refundRow;
            $this->redirect('refund_order');
        }
        else
        {
            $this->redirect('refunds',false);
            Util::showMessage("退款信息不存在");
        }
    }
    
    /**
     * @brief 查看退款申请详情
     */
	public function refunds_edit()
	{
		$order_id = IFilter::act(IReq::get('order_id'),'int');
		if($order_id)
		{
			$orderDB  = new IModel('order');
			$orderRow = $orderDB->getObj('id = '.$order_id.' and user_id = '.$this->user['user_id']);
			if($orderRow)
			{
				$this->orderRow = $orderRow;
				$this->redirect('refunds_edit');
				return;
			}
		}
		$this->redirect('refunds');
	}

    /**
     * @brief 建议中心
     */
    public function complain_edit()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $title = IFilter::act(IReq::get('title'),'string');
        $content = IFilter::act(IReq::get('content'),'string' );
        $user_id = $this->user['user_id'];
        $model = new IModel('suggestion');
        $model->setData(array('user_id'=>$user_id,'title'=>$title,'content'=>$content,'time'=>ITime::getDateTime()));
        if($id =='')
        {
            $model->add();
        }
        else
        {
            $model->update('id = '.$id.' and user_id = '.$this->user['user_id']);
        }
        $this->redirect('complain');
    }
    //站内消息
    public function message()
    {
        $msgObj = new Mess($this->user['user_id'],$this->user['type']);
        $msgIds = $msgObj->getAllMsgIds();
        $msgIds = $msgIds ? $msgIds : 0;
        $this->setRenderData(array('msgIds' => $msgIds,'msgObj' => $msgObj));
        $this->redirect('message');
    }
    //站内消息编辑
    public function message_edit()
    {
    	$msgObj = new Mess($this->user['user_id'],$this->user['type']);
    	$msgIds = $msgObj->getAllMsgIds();
    	$msgIds = $msgIds ? $msgIds : 0;
		$this->setRenderData(array('msgIds' => $msgIds,'msgObj' => $msgObj));
    	$this->redirect('message_edit');
    }
    /**
     * @brief 删除消息
     * @param int $id 消息ID
     */
    public function message_del()
    {
        $id = IFilter::act( IReq::get('id') ,'int' );
        $msg = new Mess($this->user['user_id'],$this->user['type']);
        $msg->delMessage($id);
        $this->redirect('message');
    }
    /**
     * @brief 删除多条消息
     * @param array $id 消息ID
     */
    public function messages_del()
    {
        $ids = IFilter::act(IReq::get('sub') ,'int' );
        $msg = new Mess($this->user['user_id'],$this->user['type']);
        foreach($ids as $id)
        {
            $msg->delMessage($id);
        }
        $this->redirect('message_edit');
    }
    public function message_read()
    {
        $id = IFilter::act( IReq::get('id'),'int' );
        $msg = new Mess($this->user['user_id'],$this->user['type']);
        echo $msg->writeMessage($id,1);
    }

    //[修改密码]修改动作
    function password_edit()
    {
    	$user_id    = $this->user['user_id'];

    	$fpassword  = IReq::get('fpassword');
    	$password   = IReq::get('password');
    	$repassword = IReq::get('repassword');

    	$userObj    = new IModel('user');
    	$where      = 'id = '.$user_id;
    	$userRow    = $userObj->getObj($where);

		if(!preg_match('|\w{6,32}|',$password))
		{
			$message = '密码格式不正确，请重新输入';
		}
    	else if($password != $repassword)
    	{
    		$message  = '二次密码输入的不一致，请重新输入';
    	}
    	else if(md5($fpassword) != $userRow['password'])
    	{
    		$message  = '原始密码输入错误';
    	}
    	else
    	{
    		$passwordMd5 = md5($password);
	    	$dataArray = array(
	    		'password' => $passwordMd5,
	    	);

	    	$userObj->setData($dataArray);
	    	$result  = $userObj->update($where);
	    	if($result)
	    	{
	    		ISafe::set('user_pwd',$passwordMd5,'session');
	    		$message = '密码修改成功';
	    	}
	    	else
	    	{
	    		$message = '密码修改失败';
	    	}
		}

    	$this->redirect('password',false);
    	Util::showMessage($message);
    }

    //[个人资料]展示 单页
    function info()
    {
        $user_id = $this->user['user_id'];

        $userObj       = new IModel('user');
        $where         = 'id = '.$user_id;
        $this->userRow = $userObj->getObj($where);

        $memberObj       = new IModel('member');
        $where           = 'user_id = '.$user_id;
        $this->memberRow = $memberObj->getObj($where);
        $this->redirect('info');
    }

    //[个人资料]编辑 单页
    function info_edit()
    {
    	$user_id = $this->user['user_id'];

    	$userObj       = new IModel('user');
    	$where         = 'id = '.$user_id;
    	$this->userRow = $userObj->getObj($where);

    	$memberObj       = new IModel('member');
    	$where           = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);
    	$this->redirect('info_edit');
    }

    //[个人资料] 修改 [动作]
    function info_edit_act()
    {
        $email     = IFilter::act( IReq::get('email'),'string');
        $mobile    = IFilter::act( IReq::get('mobile'),'string');
		$desc_info = IFilter::act( IReq::get('desc_info'),'string');

    	$user_id   = $this->user['user_id'];
    	$memberObj = new IModel('member');
        $userObj = new IModel('user');
    	$where     = 'user_id = '.$user_id;
        if($email)
        {
            $userRow = $userObj->getObj('id != '.$user_id.' and email = "'.$email.'"');
            if($userRow)
            {
                IError::show('邮箱已经被注册');
            }
        }

        if($mobile)
        {
            $userRow = $userObj->getObj('id != '.$user_id.' and mobile = "'.$mobile.'"');
            if($userRow)
            {
                IError::show('手机已经被注册');
            }
        }

    	//地区
    	$province = IFilter::act( IReq::get('province','post') ,'string');
    	$city     = IFilter::act( IReq::get('city','post') ,'string' );
    	$area     = IFilter::act( IReq::get('area','post') ,'string' );
    	$areaArr  = array_filter(array($province,$city,$area));

    	$dataArray       = array(
    		'true_name'    => IFilter::act( IReq::get('true_name') ,'string'),
    		'sex'          => IFilter::act( IReq::get('sex'),'int' ),
    		'birthday'     => IFilter::act( IReq::get('birthday') ),
    		'zip'          => IFilter::act( IReq::get('zip') ,'string' ),
    		'qq'           => IFilter::act( IReq::get('qq') , 'string' ),
    		'contact_addr' => IFilter::act( IReq::get('contact_addr'), 'string'),
    		'telephone'    => IFilter::act( IReq::get('telephone'),'string'),
    		'area'         => $areaArr ? ",".join(",",$areaArr)."," : "",
            'desc_info'    => $desc_info
    	);
        if(IClient::getDevice() == IClient::PC)
        {
            $data['email'] = $email;
            $data['mobile'] = $mobile;
            $userObj->setData($data);
            $userObj->update('id = '.$user_id);
        }

    	$memberObj->setData($dataArray);
    	$memberObj->update($where);
    	$this->info();
    }

    //[账户余额] 展示[单页]
    function withdraw()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member','balance');
    	$where     = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where);
    	$this->redirect('withdraw');
    }

	//[账户余额] 提现动作
    function withdraw_act()
    {
    	$user_id = $this->user['user_id'];
    	$amount  = IReq::get('amount');
    	$message = '';
    	$dataArray = array(
    		'name'   => urldecode(IReq::get('name')),
            'note'   => urldecode(IReq::get('note')),
            'bank'   => urldecode(IReq::get('bank')),
    		'account'=> urldecode(IReq::get('account')),
			'amount' => $amount,
			'user_id'=> $user_id,
			'time'   => ITime::getDateTime(),
    	);
        if($this->_siteConfig->withdrawAccount)
        {
            $para['charge'] = round($amount*$this->_siteConfig->withdrawAccount/100, 2);
            $para['am'] = $amount-$para['charge'];
        }
        else
        {
            $para['charge'] = 0;
            $para['am'] = $amount;
        }
        $dataArray['para'] = JSON::encode($para);
		$mixAmount = 0;
		$memberObj = new IModel('member');
		$where     = 'user_id = '.$user_id;
		$memberRow = $memberObj->getObj($where,'balance,pay_password');
        $result = array(
                        'result' => false,
                        'msg' => ''
                    );
        //验证支付密码
        $pay_pwd = IReq::get('pay_pwd');
        if(!$pay_pwd)
        {
            $result['msg'] = '请输入支付密码';
        }
        elseif(md5($pay_pwd) != $memberRow['pay_password'])
        {
            $result['msg'] = '支付密码输入错误';
        }

		//提现金额范围
		elseif($amount <= $mixAmount)
		{
			$result['msg'] = '提现的金额必须大于'.$mixAmount.'元';
		}
		else if($amount > $memberRow['balance'])
		{
			$result['msg'] = '提现的金额不能大于您的帐户余额';
		}
		else
		{
	    	$obj = new IModel('withdraw');
	    	$obj->setData($dataArray);
	    	$res = $obj->add();
            if($res)
            {
                $result['result'] = true;
            }
	    	else
            {
                $result['msg'] = '申请提现失败，请稍后再试';
            }
		}
        echo JSON::encode($result);
    }

    //[账户余额] 提现详情
    function withdraw_detail()
    {
    	$user_id = $this->user['user_id'];

        $memberObj = new IModel('member','balance');
        $this->memberRow = $memberObj->getObj('user_id = '.$user_id);

    	$id  = IFilter::act( IReq::get('id'),'int' );
    	$obj = new IModel('withdraw');
    	$where = 'id = '.$id.' and user_id = '.$user_id;
    	$withdrawRow = $obj->getObj($where);
        $para = JSON::decode($withdrawRow['para']);
        if($para)
        {
            $withdrawRow['charge'] = $para['charge'];
            $withdrawRow['am'] = $para['am'];
        }
        
        $this->withdrawRow = $withdrawRow;
    	$this->redirect('withdraw_detail');
    }

    //[提现申请] 取消
    function withdraw_del()
    {
    	$id = IFilter::act( IReq::get('id'),'int');
    	if($id)
    	{
    		$dataArray   = array('is_del' => 1);
    		$withdrawObj = new IModel('withdraw');
            $where = 'user_id = '.$this->user['user_id'];
            if(is_array($id))
            {
                $idStr = join(',',$id);
                $where .= ' and id in ('.$idStr.')';
            }
            else
            {
                $where .= ' and id = '.$id;
            }
    		$withdrawObj->setData($dataArray);
    		$withdrawObj->update($where);
            $this->redirect('withdraw');
    	}
        else
        {
            $this->redirect('withdraw',false);
            Util::showMessage('请选择要删除的数据');
        }
    }

    //[余额交易记录]
    function account_log()
    {
    	$user_id   = $this->user['user_id'];

    	$memberObj = new IModel('member');
    	$where     = 'user_id = '.$user_id;
    	$this->memberRow = $memberObj->getObj($where, 'balance');
    	$this->redirect('account_log');
    }

    //[收藏夹]备注信息
    function edit_summary()
    {
    	$user_id = $this->user['user_id'];

    	$id      = IFilter::act( IReq::get('id'),'int' );
    	$summary = IFilter::act( IReq::get('summary'),'string' );

    	//ajax返回结果
    	$result  = array(
    		'isError' => true,
    	);

    	if(!$id)
    	{
    		$result['message'] = '收藏夹ID值丢失';
    	}
    	else if(!$summary)
    	{
    		$result['message'] = '请填写正确的备注信息';
    	}
    	else
    	{
	    	$favoriteObj = new IModel('favorite');
	    	$where       = 'id = '.$id.' and user_id = '.$user_id;

	    	$dataArray   = array(
	    		'summary' => $summary,
	    	);

	    	$favoriteObj->setData($dataArray);
	    	$is_success = $favoriteObj->update($where);

	    	if($is_success === false)
	    	{
	    		$result['message'] = '更新信息错误';
	    	}
	    	else
	    	{
	    		$result['isError'] = false;
	    	}
    	}
    	echo JSON::encode($result);
    }

    //[收藏夹]删除
    function favorite_del()
    {
    	$user_id = $this->user['user_id'];
    	$id      = IReq::get('id');

		if(!empty($id))
		{
			$id = IFilter::act($id,'int');

			$favoriteObj = new IModel('favorite');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = 'user_id = '.$user_id.' and id in ('.$idStr.')';
			}
			else
			{
				$where = 'user_id = '.$user_id.' and id = '.$id;
			}

			$favoriteObj->del($where);
			$this->redirect('favorite');
		}
		else
		{
			$this->redirect('favorite',false);
			Util::showMessage('请选择要删除的数据');
		}
    }

    //[我的积分] 单页展示
    function integral()
    {
    	/*获取积分增减的记录日期时间段*/
    	$this->historyTime = IFilter::string( IReq::get('history_time','post') );
    	$defaultMonth = 3;//默认查找最近3个月内的记录

		$lastStamp    = ITime::getTime(ITime::getNow('Y-m-d')) - (3600*24*30*$defaultMonth);
		$lastTime     = ITime::getDateTime('Y-m-d',$lastStamp);

		if($this->historyTime != null && $this->historyTime != 'default')
		{
			$historyStamp = ITime::getDateTime('Y-m-d',($lastStamp - (3600*24*30*$this->historyTime)));
			$this->c_datetime = 'datetime >= "'.$historyStamp.'" and datetime < "'.$lastTime.'"';
		}
		else
		{
			$this->c_datetime = 'datetime >= "'.$lastTime.'"';
		}

    	$memberObj         = new IModel('member');
    	$where             = 'user_id = '.$this->user['user_id'];
    	$this->memberRow   = $memberObj->getObj($where,'point');
    	$this->redirect('integral',false);
    }

    //[我的积分]积分兑换代金券 动作
    function trade_ticket()
    {
    	$ticketId = IFilter::act( IReq::get('ticket_id','post'),'int' );
    	$message  = '';
    	if(intval($ticketId) == 0)
    	{
    		$message = '请选择要兑换的代金券';
    	}
    	else
    	{
    		$nowTime   = ITime::getDateTime();
    		$ticketObj = new IModel('ticket');
    		$ticketRow = $ticketObj->getObj('id = '.$ticketId.' and point > 0 and start_time <= "'.$nowTime.'" and end_time > "'.$nowTime.'"');
    		if(empty($ticketRow))
    		{
    			$message = '对不起，此代金券不能兑换';
    		}
    		else
    		{
	    		$memberObj = new IModel('member');
	    		$where     = 'user_id = '.$this->user['user_id'];
	    		$memberRow = $memberObj->getObj($where,'point');

	    		if($ticketRow['point'] > $memberRow['point'])
	    		{
	    			$message = '对不起，您的积分不足，不能兑换此类代金券';
	    		}
	    		else
	    		{
	    			//生成红包
					$dataArray = array(
						'condition' => $ticketRow['id'],
						'name'      => $ticketRow['name'],
						'card_name' => 'T'.IHash::random(8),
						'card_pwd'  => IHash::random(8),
						'value'     => $ticketRow['value'],
						'start_time'=> $ticketRow['start_time'],
						'end_time'  => $ticketRow['end_time'],
						'is_send'   => 1,
					);
					$propObj = new IModel('prop');
					$propObj->setData($dataArray);
					$insert_id = $propObj->add();

					//更新用户prop字段
					$memberArray = array('prop' => "CONCAT(IFNULL(prop,''),'{$insert_id},')");
					$memberObj->setData($memberArray);
					$result = $memberObj->update('user_id = '.$this->user["user_id"],'prop');

					//代金券成功
					if($result)
					{
						$pointConfig = array(
							'user_id' => $this->user['user_id'],
							'point'   => '-'.$ticketRow['point'],
							'log'     => '积分兑换代金券，扣除了 -'.$ticketRow['point'].'积分',
						);
						$pointObj = new Point;
						$pointObj->update($pointConfig);
					}
	    		}
    		}
    	}

    	//展示
    	if($message != '')
    	{
    		$this->integral();
    		Util::showMessage($message);
    	}
    	else
    	{
    		$this->redirect('redpacket');
    	}
    }

    /**
     * 余额付款
     * T:支付失败;
     * F:支付成功;
     */
    function payment_balance()
    {
    	$urlStr  = '';
    	$user_id = intval($this->user['user_id']);

    	$return['attach']     = IReq::get('attach');
    	$return['total_fee']  = IReq::get('total_fee');
    	$return['order_no']   = IReq::get('order_no');
    	$return['return_url'] = IReq::get('return_url');
    	$sign                 = IReq::get('sign');
        if(stripos($return['order_no'],'recharge') !== false)
        {
            IError::show(403,'余额支付方式不能用于在线充值');
        }
    	if(stripos($return['order_no'],'service') !== false)
    	{
    		IError::show(403,'余额不能用于支付开店服务费');
    	}

    	if(floatval($return['total_fee']) < 0 || $return['order_no'] == '' || $return['return_url'] == '' || $return['attach'] == '')
    	{
    		IError::show(403,'支付参数不正确');
    	}

		$paymentDB  = new IModel('payment');
		$paymentRow = $paymentDB->getObj('class_name = "balance" ');
		$pkey       = Payment::getConfigParam($paymentRow['id'],'M_PartnerKey');

    	//md5校验
    	ksort($return);
		foreach($return as $key => $val)
		{
			$urlStr .= $key.'='.urlencode($val).'&';
		}

		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStr .= $pkey.$encryptKey;
		if($sign != md5($urlStr))
		{
			IError::show(403,'数据校验不正确');
		}

    	$memberObj = new IModel('member');
    	$memberRow = $memberObj->getObj('user_id = '.$user_id,'user_id,balance');

    	if(empty($memberRow))
    	{
    		IError::show(403,'用户信息不存在');
    	}

    	if($memberRow['balance'] < $return['total_fee'])
    	{
    		IError::show(403,'账户余额不足');
    	}

		//检查订单状态
		$orderObj = new IModel('order');
		$orderRow = $orderObj->getObj('order_no  = "'.$return['order_no'].'" and pay_status = 0 and status = 1 and user_id = '.$user_id);
		if(!$orderRow)
		{
			IError::show(403,'订单号【'.$return['order_no'].'】已经被处理过，请查看订单状态');
		}

		//扣除余额并且记录日志
		$logObj = new AccountLog();
		$config = array(
			'user_id'  => $user_id,
			'event'    => 'pay',
			'num'      => $return['total_fee'],
			'order_no' => str_replace("_",",",$return['attach']),
		);
		$is_success = $logObj->write($config);
		if(!$is_success)
		{
			IError::show(403,$logObj->error ? $logObj->error : '用户余额更新失败');
		}

		$return['is_success'] = $is_success ? 'T' : 'F';
    	ksort($return);

    	//返还的URL地址
		$responseUrl = '';
		foreach($return as $key => $val)
		{
			$responseUrl .= $key.'='.urlencode($val).'&';
		}

		$returnUrl = urldecode($return['return_url']);
		if(stripos($returnUrl,'?') === false)
		{
			$returnJumpUrl = $returnUrl.'?'.$responseUrl;
		}
		else
		{
			$returnJumpUrl = $returnUrl.'&'.$responseUrl;
		}

		//计算要发送的md5校验
		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStrMD5  = md5($responseUrl.$pkey.$encryptKey);

		//拼接进返还的URL中
		$returnJumpUrl.= 'sign='.$urlStrMD5;

		//同步通知
    	header('location:'.$returnJumpUrl);
    }

    //我的代金券
    function redpacket()
    {
		$member_info = Api::run('getMemberInfo',$this->user['user_id'],$this->user['type']);
		$propIds     = trim($member_info['prop'],',');
		$propIds     = $propIds ? $propIds : 0;
		$this->setRenderData(array('propId' => $propIds));
		$this->redirect('redpacket');
    }
    
    //[我的足迹]删除
    function history_del()
    {
        $user_id = $this->user['user_id'];
        $id      = IReq::get('id');

        if(!empty($id))
        {
            $id = IFilter::act($id,'int');

            $historyObj = new IModel('user_history');

            if(is_array($id))
            {
                $idStr = join(',',$id);
                $where = 'user_id = '.$user_id.' and goods_id in ('.$idStr.')';
            }
            else
            {
                $where = 'user_id = '.$user_id.' and goods_id = '.$id;
            }

            $historyObj->del($where);
            $this->redirect('history');
        }
        else
        {
            $this->redirect('history',false);
            Util::showMessage('请选择要删除的数据');
        }
    }
    
    //修改绑定邮箱
    function changeEmail()
    {
        $user_id = $this->user['user_id'];
        $user = new IModel('user');
        $email = $user->getObj('id = '.$user_id, 'email');
        if(empty($email['email']))
        {
            $this->redirect('index');
        }
        else
        {
            $this->email = $email['email'];
            $this->redirect('changeEmail');
        }
    }
    
    //修改绑定邮箱发送验证邮件
    function _sendChangeEmailCode()
    {
        $_email = IFilter::act(IReq::get('email'));
        $user = new IModel('user');
        if(!$_email)
        {
            $user_id = $this->user['user_id'];
            $email = $user->getObj('id = '.$user_id, 'email');
            if(empty($email['email']))
            {
                die("参数错误");
            }
            $_email = $email['email'];
            $captcha = IFilter::act(IReq::get('captcha'));
            $_captcha = ISafe::get('captcha');
            if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
            {
                die("请填写正确的图形验证码");
            }
        }
        else
        {
            if(!IValidate::email($_email)){
                die('邮箱格式错误');
            }
            if($user->getObj('email = "'.$_email.'"', 'id'))
            {
                die('该邮箱已注册');
            }
        }
        $email_code = rand(100000,999999);
        ISafe::set('emailValidate',array('code'=>$email_code,'email'=>$_email,'time'=>time()));
        $content = mailTemplate::changeEmail(array("{email_code}" => $email_code));

        $smtp   = new SendMail();
        $result = $smtp->send($_email,"耐装网用户修改邮箱验证",$content);

        if($result===false)
        {
            die("发信失败,请重试！或者联系管理员查看邮件服务是否开启");
        }
    }
    
    //绑定新邮箱
    function changeEmail1()
    {
        $code = IReq::get('email_code');
        $captcha = IFilter::act(IReq::get('captcha'));
        $_captcha = ISafe::get('captcha');
        if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            die("请填写正确的图形验证码");
        }
        $user_id = $this->user['user_id'];
        $user = new IModel('user');
        $email = $user->getObj('id = '.$user_id, 'email');
        $checkRes = ISafe::get('emailValidate');
        if($checkRes && $email['email']==$checkRes['email'] &&time()- $checkRes['time']<1800 && $code == $checkRes['code']){
            $this->redirect('changeEmail1');
        }else{
            IError::show(403,"邮箱验证码不正确或已过期");
        }
    }
    
    //绑定新邮箱
    function changeEmail2()
    {
        $newEmail = IFilter::act(IReq::get('email','post'));
        $code =IFilter::act(IReq::get('email_code','post'));
        $user = new IModel('user');
        if($user->getObj('email="'.$newEmail.'"', 'id')){
            IError::show(403,"该邮箱已注册");
        }
        if(!IValidate::email($newEmail)){
            IError::show(403,"邮箱格式错误");
        }
        if(!$code){
            IError::show(403,"请填写验证码");
        }
        $checkRes = ISafe::get('emailValidate');
        if($checkRes && $newEmail==$checkRes['email'] &&time()- $checkRes['time']<1800 && $code == $checkRes['code']){
                $user_id = $this->user['user_id'];
                $where         = 'id = '.$user_id;
                $user->setData(array('email'=>$newEmail));
                if($user->update($where)){
                    ISafe::set('email',$newEmail);
                    $this->redirect('changeEmail2');
                }else{
                    IError::show(403,"邮箱更新失败");
                }
        }else{
            IError::show(403,"邮箱验证码不正确或已过期");
        }
    }
    
    //修改绑定手机号
    function changePhone()
    {
        $user_id = $this->user['user_id'];
        $user = new IModel('user');
        $mobile = $user->getObj('id = '.$user_id, 'mobile');
        if(empty($mobile['mobile']))
        {
            $this->redirect('index');
        }
        else
        {
            $this->mobile = $this->resetCode($mobile['mobile']);
            $this->redirect('changePhone');
        }
    }
    
    private function resetCode($phone)
    {
        if($phone)
            return substr_replace($phone,'****',3,4);
        return false;
    }
    
    //换绑手机号发送手机验证码短信
    function _sendMobileCode()
    {
        $_mobile = IFilter::act(IReq::get('phone'));
        $captcha = IFilter::act(IReq::get('captcha'));
        $_safeName = IReq::get('name') ? IReq::get('name') : 'phoneValidate';
        $_captcha = ISafe::get('captcha');
        $user = new IModel('user');
        if(!$_mobile)
        {
            $user_id = $this->user['user_id'];
            $mobile = $user->getObj('id = '.$user_id, 'mobile');
            if(empty($mobile['mobile']))
            {
                die("参数错误");
            }
            $_mobile = $mobile['mobile'];
            $captcha = IFilter::act(IReq::get('captcha'));
            $_captcha = ISafe::get('captcha');
            if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
            {
                die("请填写正确的图形验证码");
            }
        }
        else
        {
            if($_mobile === null || !IValidate::mobi($_mobile))
            {
                die("请输入正确的手机号码");
            }
            if($user->getObj('mobile = "'.$_mobile.'"', 'id'))
            {
                die('该手机号已注册');
            }
        }
        
        $mobile_code = rand(100000,999999);
        ISafe::set($_safeName,array('code'=>$mobile_code,'mobile'=>$_mobile,'time'=>time()));
        $content = smsTemplate::findPassword(array('{mobile_code}' => $mobile_code));

        $result = Hsms::send($_mobile,$content);
        die($result);
    }
    
    //绑定新手机号
    function changePhone1()
    {
        $code = IReq::get('phone_code');
        $captcha = IFilter::act(IReq::get('captcha'));
        $_captcha = ISafe::get('captcha');
        if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            IError::show(403,"请填写正确的图形验证码");
        }
        $user_id = $this->user['user_id'];
        $user = new IModel('user');
        $mobile = $user->getObj('id = '.$user_id, 'mobile');
        $checkRes = ISafe::get('phoneValidate');
        if($checkRes && $mobile['mobile']==$checkRes['mobile'] &&time()- $checkRes['time']<1800 && $code == $checkRes['code']){
            $this->redirect('changePhone1');
        }else{
            IError::show(403,"手机验证码不正确或已过期");
        }
    }
    
    //绑定新手机号
    function changePhone2()
    {
        $newPhone = IFilter::act(IReq::get('phone','post'));
        $code =IFilter::act(IReq::get('phone_code','post'));
        $user = new IModel('user');
        if($user->getObj('mobile="'.$newPhone.'"', 'id')){
            IError::show(403,"该手机号已注册");
        }
        if(!IValidate::mobi($newPhone)){
            IError::show(403,"手机格式错误");
        }
        if(!$code){
            IError::show(403,"请填写验证码");
        }
        $checkRes = ISafe::get('phoneValidate');
        if($checkRes && $newPhone==$checkRes['mobile'] &&time()- $checkRes['time']<1800 && $code == $checkRes['code']){
                $user_id = $this->user['user_id'];
                $where         = 'id = '.$user_id;
                $user->setData(array('mobile'=>$newPhone));
                if($user->update($where)){
                    ISafe::set('phone',$newPhone);
                    $this->redirect('changePhone2');
                }else{
                    IError::show(403,"手机号更新失败");
                }
        }else{
            IError::show(403,"验证码不正确或已过期");
        }
    }
    
    //修改支付密码
    public function payPass_edit()
    {
        $user_id = $this->user['user_id'];
        $memberObj       = new IModel('member');
        $where           = 'user_id = '.$user_id;
        $pay_pass = $memberObj->getObj($where, 'pay_password');
        $this->pay_pass = $pay_pass['pay_password'];
        $this->redirect('payPass_edit');
    }
    
    //修改支付密码--动作
    public function payPass_update()
    {
        $user_id = $this->user['user_id'];
        $memberObj       = new IModel('member');
        $where           = 'user_id = '.$user_id;
        $pay_pass = $memberObj->getObj($where, 'pay_password');
        $this->pay_pass = $pay_pass['pay_password'];
        $fpassword = IReq::get('fpassword');
        $password = IReq::get('password');
        $repassword = IReq::get('repassword');
        if(!preg_match('|\w{6,32}|',$password))
        {
            $message = '密码格式不正确，请重新输入';
        }
        else if($password != $repassword)
        {
            $message  = '二次密码输入的不一致，请重新输入';
        }
        else if($pay_pass['pay_password'] && md5($fpassword) != $pay_pass['pay_password'])
        {
            $message  = '原始密码输入错误';
        }
        else
        {
            $passwordMd5 = md5($password);
            $dataArray = array(
                'pay_password' => $passwordMd5,
            );

            $memberObj->setData($dataArray);
            $result  = $memberObj->update($where);
            if($result)
            {
                $message = '支付密码设置成功';
            }
            else
            {
                $message = '支付密码设置失败';
            }
            $this->pay_pass = $passwordMd5;
        }

        $this->redirect('payPass_edit',false);
        Util::showMessage($message);
    }
    
    //找回支付密码
    public function findPayPass2()
    {
        $code = IReq::get('phone_code');
        $captcha = IFilter::act(IReq::get('captcha'));
        $_captcha = ISafe::get('captcha');
        if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            IError::show(403,"请填写正确的图形验证码");
        }
        $user_id = $this->user['user_id'];
        $user = new IModel('user');
        $mobile = $user->getObj('id = '.$user_id, 'mobile');
        $checkRes = ISafe::get('findPassPhoneValidate');
        if($checkRes && $mobile['mobile']==$checkRes['mobile'] &&time()- $checkRes['time']<1800 && $code == $checkRes['code']){
            $this->code = $code;
            $this->redirect('findPayPass2');
        }else{
            IError::show(403,"手机验证码不正确或已过期");
        }
    }
    
    //找回重置支付密码--动作
    public function findPayPassUpdate()
    {
        $user_id = $this->user['user_id'];
        $memberObj       = new IModel('member');
        $where           = 'user_id = '.$user_id;
        $userObj = new IModel('user');
        $mobile = $userObj->getObj('id = '.$user_id, 'mobile');
        $password = IReq::get('password');
        $repassword = IReq::get('repassword');
        $code = IReq::get('code');
        $checkRes = ISafe::get('findPassPhoneValidate');
        if(!$checkRes || $mobile['mobile']!=$checkRes['mobile'] || time()- $checkRes['time']>=1800 || $code != $checkRes['code']){
            $message = '验证错误或手机验证码已过期';
        }
        else if(!preg_match('|\w{6,32}|',$password))
        {
            $message = '密码格式不正确，请重新输入';
        }
        else if($password != $repassword)
        {
            $message  = '二次密码输入的不一致，请重新输入';
        }
        else
        {
            $passwordMd5 = md5($password);
            $dataArray = array(
                'pay_password' => $passwordMd5,
            );

            $memberObj->setData($dataArray);
            $result  = $memberObj->update($where);
            if($result)
            {
                $res = 1;
                $message = '支付密码重置成功';
                $this->pay_pass = $passwordMd5;
            }
            else
            {
                $message = '支付密码重置失败';
            }
        }
        if(isset($res))
        {
            $this->redirect('payPass_edit',false);
            Util::showMessage($message);
        }
        else
        {
            $this->code = $checkRes['code'];
            $this->redirect('findPayPass2',false);
            Util::showMessage($message);
        }
        
    }
}