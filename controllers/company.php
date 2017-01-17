<?php
/**
 * @brief 装修公司模块
 * @class Company
 * @author zyn
 * @datetime 2017-1-17 13:40:27
 */
class Company extends IController implements companyAuthorization
{
	public $layout = 'company';

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

    //修改风格页面
    function style_edit()
    {
        $this->layout = '';

        $id        = IFilter::act(IReq::get('id'),'int');
        $user_id = IFilter::act(IReq::get('com_id'),'int');

        $dataRow = array(
            'id'        => '',
            'name'      => '',
            'sort'      => '',
            'user_id'   => $user_id,
        );

        if($id)
        {
            $obj     = new IModel('case_style');
            $dataRow = $obj->getObj("id = {$id}");
        }

        $this->setRenderData($dataRow);
        $this->redirect('style_edit');
    }
    
    //增加或者修改风格
    function style_update()
    {
        $id         = IFilter::act(IReq::get('id'),'int');
        $name       = IFilter::act(IReq::get('name')); 
        $sort       = IFilter::act(IReq::get('sort'));
        $user_id  = IFilter::act(IReq::get('user_id'),'int');

        $editData = array(
            'id'        => $id,
            'name'      => $name, 
            'sort'      => $sort,
            'user_id'   => $user_id,
        );

        //执行操作
        $obj = new IModel('case_style');
        $obj->setData($editData);

        //更新修改
        if($id)
        {
            $where = 'id = '.$id;
            if($user_id)
            {
                $where .= ' and user_id = '.$user_id;
            }
            $result = $obj->update($where);
        }
        //添加插入
        else
        {
            $result = $obj->add();
        }

        //执行状态
        if($result===false)
        {
            die( JSON::encode(array('flag' => 'fail','message' => '数据库更新失败')) );
        }
        else
        {
            //获取自动增加ID
            $editData['id'] = $id ? $id : $result;
            die( JSON::encode(array('flag' => 'success','data' => $editData)) );
        }
    }               

	//风格删除
	public function style_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');

		if($id)
		{
			$idString = is_array($id) ? join(',',$id) : $id;
			$styleObj  = new IModel('case_style');
			$styleObj->del("id in ( {$idString} ) and user_id = ".$this->company['user_id']);
			$this->redirect('style_list');
		}
		else
		{
			$this->redirect('style_list',false);
			Util::showMessage('请选择要删除的风格');
		}
	}        

	//修改装修公司信息
	public function company_edit()
	{
        $user_id = $this->company['user_id'];

        $userObj       = new IModel('user');
        $where         = 'id = '.$user_id;
        $this->userRow = $userObj->getObj($where);
                                             
		$companyDB        = new IModel('company');
		$this->companyRow = $companyDB->getObj('user_id = '.$user_id);
		$this->redirect('company_edit');
	}

	/**
	 * @brief 装修公司的增加动作
	 */
	public function company_add()
	{
		$user_id   = $this->company['user_id'];
        
        $email     = IFilter::act( IReq::get('email'),'string');
        $mobile    = IFilter::act( IReq::get('mobile'),'string'); 
        $username    = IFilter::act( IReq::get('username'),'string'); 
                                                            
        $userObj = new IModel('user');
        $data = array(); 
        if($email)
        {
            $userRow = $userObj->getObj('id != '.$user_id.' and email = "'.$email.'"');
            if($userRow)
            {
                $errorMsg = '邮箱已经被注册';
            }
            $data['email'] = $email;
        }

        if($mobile)
        {
            $userRow = $userObj->getObj('id != '.$user_id.' and mobile = "'.$mobile.'"');
            if($userRow)
            {
                $errorMsg = '手机已经被注册';
            }
            $data['mobile'] = $mobile;
        }

        if($username)
        {
            $userRow = $userObj->getObj('id != '.$user_id.' and username = "'.$username.'"');
            if($userRow)
            {
                $errorMsg = '登录用户名已经被注册';
            }
            $data['username'] = $username;
        }
        if($data)
        {
            $userObj->setData($data);
            $userObj->update('id = '.$user_id);  
        }  

		//操作失败表单回填
		if(isset($errorMsg))
		{
            $this->userRow = $userObj->getObj('id = '.$user_id);
			$this->companyRow = $_POST;
			$this->redirect('company_edit',false);
			Util::showMessage($errorMsg);
		}

		//待更新的数据
		$companyDB = new IModel('company');
        $dataArray = array(
            'contacts_name' => IFilter::act(IReq::get('contacts_name')),  
            'phone' => IFilter::act(IReq::get('phone')),
            'province' => IFilter::act(IReq::get('province'),'int'),
            'city' => IFilter::act(IReq::get('city'),'int'),
            'area' => IFilter::act(IReq::get('area'),'int'),     
            'address' => IFilter::act(IReq::get('address'))
        ); 
        $companyDB->setData($dataArray);
        $companyDB->update('user_id = '.$user_id);  

		$this->redirect('company_edit');
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
			'company_id'     => $this->company['company_id'],
		);

		$dataArray['limit_min_count'] = $dataArray['limit_min_count'] <= 0 ? 1 : $dataArray['limit_min_count'];
		$dataArray['limit_max_count'] = $dataArray['limit_max_count'] <= 0 ? $dataArray['store_nums'] : $dataArray['limit_max_count'];

		if($goodsId)
		{
			$goodsObj = new IModel('goods');
			$where    = 'id = '.$goodsId.' and company_id = '.$this->company['company_id'];
			$goodsRow = $goodsObj->getObj($where);

			//商品信息不存在
			if(!$goodsRow)
			{
				$this->regimentRow = $dataArray;
				$this->redirect('regiment_edit',false);
				Util::showMessage('请选择装修公司自己的商品');
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
			$where = 'id = '.$id.' and company_id = '.$this->company['company_id'];
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
			$billRow = $billDB->getObj('id = '.$id.' and company_id = '.$this->company['company_id']);
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
			$billDB->del('id = '.$id.' and company_id = '.$this->company['company_id'].' and is_pay = 0');
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
				$billDB->update('id = '.$id.' and company_id = '.$this->company['company_id']);
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
			$isSubmitBill = $billDB->getObj(" company_id = ".$this->company['company_id']." and is_pay = 0");
			if($isSubmitBill)
			{
				$this->redirect('bill_list',false);
				Util::showMessage('请耐心等待管理员结算后才能再次提交申请');
			}
            $endTime = date('Y-m-d', strtotime($end_time)+24*3600);
			//获取未结算的订单
			$queryObject = CountSum::getSellerGoodsFeeQuery($this->company['company_id'],$start_time,$endTime,0);
			$countData   = CountSum::countSellerOrderFee($queryObject->find());
			if($countData['countFee'] > 0)
			{
                
				$countData['start_time'] = $start_time;
				$countData['end_time']   = $end_time;
				$billString = AccountLog::companyNewBillTemplate($countData);
				$data = array(
					'company_id'  => $this->company['company_id'],
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
		$company_id   = $this->company['company_id'];
		$start_time  = IFilter::act(IReq::get('start_time'));
		$end_time    = IFilter::act(IReq::get('end_time'));

		$queryObject = CountSum::getSellerGoodsFeeQuery($company_id,$start_time,$end_time,0);
		$countData   = CountSum::countSellerOrderFee($queryObject->find());

		if($countData['countFee'] > 0)
		{
			/*$countData['start_time'] = date('Y/m/d', strtotime($start_time));
            $countData['end_time']   = date('Y/m/d', strtotime($end_time));
			$countData['new_time']   = date('Y/m/d', strtotime($end_time)+24*3600);

			$billString = AccountLog::companyNewBillTemplate($countData);*/
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
		$query->fields = "c.*,u.username,goods.name,goods.company_id";
		$query->where = "c.id=".$cid." and goods.company_id = ".$this->company['company_id'];
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
			$commentDB->where= 'c.id = '.$id.' and go.company_id = '.$this->company['company_id'];
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

	// 消息通知
	public function message_list()
	{
		$page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$company_messObject = new company_mess($this->company['company_id']);
		$msgIds = $company_messObject->getAllMsgIds();
		$msgIds = empty($msgIds) ? 0 : $msgIds;
		$needReadNum = $company_messObject->needReadNum();

		$company_messageHandle = new IQuery('company_message');
		$company_messageHandle->where = "id in(".$msgIds.")";
		$company_messageHandle->order= "id desc";
		$company_messageHandle->page = $page;

		$this->needReadNum = $needReadNum;
		$this->company_messObject = $company_messObject;
		$this->company_messageHandle = $company_messageHandle;

		$this->redirect("message_list");
	}

	// 消息详情
	public function message_show()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if (!empty($id))
		{
			$company_messObject = new company_mess($this->company['company_id']);
			$company_messObject->writeMessage($id, 1);
		}
		$this->redirect('message_show');
	}

	// 消息删除
	public function message_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		if (!empty($id))
		{
			$company_messObject = new company_mess($this->company['company_id']);
			if (is_array($id)) {
				foreach ($id as $val)
				{
					$company_messObject->delMessage($val);
				}
			}else {
				$company_messObject->delMessage($id);
			}
		}
		$this->redirect('message_list');
	}                  
}