<?php
/**
 * @brief 会员模块
 * @class Member
 * @note  后台
 */
class Member extends IController implements adminAuthorization
{
	public $checkRight  = 'all';
    public $layout='admin';
	private $data = array();

	function init()
	{

	}

	/**
	 * @brief 添加会员
	 */
	function member_edit()
	{
		$uid  = IFilter::act(IReq::get('uid'),'int');

		//编辑会员信息读取会员信息
		if($uid)
		{
			$userDB = new IQuery('user as u');
			$userDB->join = 'left join member as m on u.id = m.user_id';
			$userDB->where= 'u.id = '.$uid;
			$userInfo = $userDB->find();

			if($userInfo)
			{
				$this->userInfo = current($userInfo);
			}
			else
			{
				$this->member_list();
				Util::showMessage("没有找到相关记录！");
				exit;
			}
		}
		$this->redirect('member_edit');
	}

	//保存会员信息
	function member_save()
	{
		$user_id    = IFilter::act(IReq::get('user_id'),'int');
		$user_name  = IFilter::act(IReq::get('username'));
		$email      = IFilter::act(IReq::get('email'));
		$password   = IFilter::act(IReq::get('password'));
		$repassword = IFilter::act(IReq::get('repassword'));
		$group_id   = IFilter::act(IReq::get('group_id'),'int');
		$truename   = IFilter::act(IReq::get('true_name'));
		$sex        = IFilter::act(IReq::get('sex'),'int');
		$telephone  = IFilter::act(IReq::get('telephone'));
		$mobile     = IFilter::act(IReq::get('mobile'));
		$province   = IFilter::act(IReq::get('province'),'int');
		$city       = IFilter::act(IReq::get('city'),'int');
		$area       = IFilter::act(IReq::get('area'),'int');
		$contact_addr = IFilter::act(IReq::get('contact_addr'));
		$zip        = IFilter::act(IReq::get('zip'));
		$qq         = IFilter::act(IReq::get('qq'));
		$exp        = IFilter::act(IReq::get('exp'),'int');
		$point      = IFilter::act(IReq::get('point'),'int');
		$status     = IFilter::act(IReq::get('status'),'int');

		$_POST['area'] = "";
		if($province && $city && $area)
		{
			$_POST['area'] = array($province,$city,$area);
		}

		if(!$user_id && $password == '')
		{
			$this->setError('请输入密码！');
		}

		if($password != $repassword)
		{
			$this->setError('两次输入的密码不一致！');
		}

		//创建会员操作类
		$userDB   = new IModel("user");
		$memberDB = new IModel("member");

		if($userDB->getObj("username='".$user_name."' and id != ".$user_id))
		{
			$this->setError('用户名重复');
		}

		if($email && $userDB->getObj("email='".$email."' and id != ".$user_id))
		{
			$this->setError('邮箱重复');
		}

		if($mobile && $userDB->getObj("mobile='".$mobile."' and id != ".$user_id))
		{
			$this->setError('手机号码重复');
		}

		//操作失败表单回填
		if($errorMsg = $this->getError())
		{
			$this->userInfo = $_POST;
			$this->redirect('member_edit',false);
			Util::showMessage($errorMsg);
		}

		$member = array(
			'true_name'    => $truename,
			'telephone'    => $telephone,
			'area'         => $_POST['area'] ? ",".join(",",$_POST['area'])."," : "",
			'contact_addr' => $contact_addr,
			'qq'           => $qq,
			'sex'          => $sex,
			'zip'          => $zip,
			'exp'          => $exp,
			'point'        => $point,
			'group_id'     => $group_id,
			'status'       => $status,
		);

		//添加新会员
		if(!$user_id)
		{
			$user = array(
				'username' => $user_name,
                'email'        => $email,
                'mobile'       => $mobile,
				'password' => md5($password),
			);
			$userDB->setData($user);
			$user_id = $userDB->add();

			$member['user_id'] = $user_id;
			$member['time']    = ITime::getDateTime();

			$memberDB->setData($member);
			$memberDB->add();
		}
		//编辑会员
		else
		{
			$user = array(
				'username' => $user_name,
                'email'        => $email,
                'mobile'       => $mobile,
			);
			//修改密码
			if($password)
			{
				$user['password'] = md5($password);
			}
			$userDB->setData($user);
			$userDB->update('id = '.$user_id);

			$member_info = $memberDB->getObj('user_id='.$user_id);

			//修改积分记录日志
			if($point != $member_info['point'])
			{
				$ctrlType = $point > $member_info['point'] ? '增加' : '减少';
				$diffPoint= $point-$member_info['point'];

				$pointObj = new Point();
				$pointConfig = array(
					'user_id' => $user_id,
					'point'   => $diffPoint,
					'log'     => '管理员'.$this->admin['admin_name'].'将积分'.$ctrlType.$diffPoint.'积分',
				);
				$pointObj->update($pointConfig);
			}

			$memberDB->setData($member);
			$memberDB->update("user_id = ".$user_id);
		}
		$this->redirect('member_list');
	}

	/**
	 * @brief 会员列表
	 */
	function member_list()
	{
		$search = IFilter::act(IReq::get('search'),'strict');
		$keywords = IFilter::act(IReq::get('keywords'));
		$where = ' 1 ';
		if($search && $keywords)
		{
			$where .= " and $search like '%{$keywords}%' ";
		}
		$this->data['search'] = $search;
		$this->data['keywords'] = $keywords;
		$this->data['where'] = $where;
		$tb_user_group = new IModel('user_group');
		$data_group = $tb_user_group->query();
		$group      = array();
		foreach($data_group as $value)
		{
			$group[$value['id']] = $value['group_name'];
		}
		$this->data['group'] = $group;
		$this->setRenderData($this->data);
		$this->redirect('member_list');
	}

	/**
	 * 用户余额管理页面
	 */
	function member_balance()
	{
		$this->layout = '';
		$this->redirect('member_balance');
	}
	/**
	 * @brief 删除至回收站
	 */
	function member_reclaim()
	{
		$user_ids = IReq::get('check');
		$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
		$user_ids = IFilter::act($user_ids,'int');
		if($user_ids)
		{
			$ids = implode(',',$user_ids);
			if($ids)
			{
				$tb_member = new IModel('member');
				$tb_member->setData(array('status'=>'2'));
				$where = "user_id in (".$ids.")";
				$tb_member->update($where);
			}
		}
		$this->member_list();
	}
	//批量用户余额操作
    function member_recharge()
    {
    	$id       = IFilter::act(IReq::get('check'),'int');
    	$balance  = IFilter::act(IReq::get('balance'),'float');
    	$type     = IFIlter::act(IReq::get('type')); //操作类型 recharge充值,withdraw提现金
    	$even     = '';

    	if(!$id)
    	{
			die(JSON::encode(array('flag' => 'fail','message' => '请选择要操作的用户')));
			return;
    	}

    	//执行写入操作
    	$id = is_array($id) ? join(',',$id) : $id;
    	$memberDB = new IModel('member');
    	$memberData = $memberDB->query('user_id in ('.$id.')');

		foreach($memberData as $value)
		{
			//用户余额进行的操作记入account_log表
			$log = new AccountLog();
			$config=array
			(
				'user_id'  => $value['user_id'],
				'admin_id' => $this->admin['admin_id'],
				'event'    => $type,
				'num'      => $balance,
			);
			$re = $log->write($config);
			if($re == false)
			{
				die(JSON::encode(array('flag' => 'fail','message' => $log->error)));
			}
		}
		die(JSON::encode(array('flag' => 'success')));
    }
	/**
	 * @brief 用户组添加
	 */
	function group_edit()
	{
		$gid = (int)IReq::get('gid');
		//编辑会员等级信息 读取会员等级信息
		if($gid)
		{
			$tb_user_group = new IModel('user_group');
			$group_info = $tb_user_group->query("id=".$gid);

			if(is_array($group_info) && ($info=$group_info[0]))
			{
				$this->data['group'] = array(
					'group_id'	=>	$info['id'],
					'group_name'=>	$info['group_name'],
					'discount'	=>	$info['discount'],
					'minexp'	=>	$info['minexp'],
					'maxexp'	=>	$info['maxexp']
				);
			}
			else
			{
				$this->redirect('group_list',false);
				Util::showMessage("没有找到相关记录！");
				return;
			}
		}
		$this->setRenderData($this->data);
		$this->redirect('group_edit');
	}

	/**
	 * @brief 保存用户组修改
	 */
	function group_save()
	{
		$group_id = IFilter::act(IReq::get('group_id'),'int');
		$maxexp   = IFilter::act(IReq::get('maxexp'),'int');
		$minexp   = IFilter::act(IReq::get('minexp'),'int');
		$discount = IFilter::act(IReq::get('discount'),'float');
		$group_name = IFilter::act(IReq::get('group_name'));

		$group = array(
			'maxexp' => $maxexp,
			'minexp' => $minexp,
			'discount' => $discount,
			'group_name' => $group_name
		);

		if($discount > 100)
		{
			$errorMsg = '折扣率不能大于100';
		}

		if($maxexp <= $minexp)
		{
			$errorMsg = '最大经验值必须大于最小经验值';
		}

		if(isset($errorMsg) && $errorMsg)
		{
			$group['group_id'] = $group_id;
			$data = array($group);

			$this->setRenderData($data);
			$this->redirect('group_edit',false);
			Util::showMessage($errorMsg);
			exit;
		}
		$tb_user_group = new IModel("user_group");
		$tb_user_group->setData($group);

		if($group_id)
		{
			$affected_rows = $tb_user_group->update("id=".$group_id);
			$this->redirect('group_list');
		}
		else
		{
			$tb_user_group->add();
			$this->redirect('group_list');
		}
	}

	/**
	 * @brief 删除会员组
	 */
	function group_del()
	{
		$group_ids = IReq::get('check');
		$group_ids = is_array($group_ids) ? $group_ids : array($group_ids);
		$group_ids = IFilter::act($group_ids,'int');
		if($group_ids)
		{
			$ids = implode(',',$group_ids);
			if($ids)
			{
				$tb_user_group = new IModel('user_group');
				$where = "id in (".$ids.")";
				$tb_user_group->del($where);
			}
		}
		$this->redirect('group_list');
	}

	/**
	 * @brief 回收站
	 */
	function recycling()
	{
		$search = IReq::get('search');
		$keywords = IReq::get('keywords');
		$search_sql = IFilter::act($search,'strict');
		$keywords = IFilter::act($keywords,'strict');

		$where = ' 1 ';
		if($search && $keywords)
		{
			$where .= " and $search_sql like '%{$keywords}%' ";
		}
		$this->data['search'] = $search;
		$this->data['keywords'] = $keywords;
		$this->data['where'] = $where;
		$tb_user_group = new IModel('user_group');
		$data_group = $tb_user_group->query();
		$data_group = is_array($data_group) ? $data_group : array();
		$group = array();
		foreach($data_group as $value)
		{
			$group[$value['id']] = $value['group_name'];
		}
		$this->data['group'] = $group;
		$this->setRenderData($this->data);
		$this->redirect('recycling');
	}

	/**
	 * @brief 彻底删除会员
	 */
	function member_del()
	{
		$user_ids = IReq::get('check');
		$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
		$user_ids = IFilter::act($user_ids,'int');
		if($user_ids)
		{
			$ids = implode(',',$user_ids);

			if($ids)
			{
				$tb_member = new IModel('member');
				$where = "user_id in (".$ids.")";
				$tb_member->del($where);

				$tb_user = new IModel('user');
				$where = "id in (".$ids.")";
				$tb_user->del($where);

				$logObj = new log('db');
				$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"删除了用户","被删除的用户ID为：".$ids));
			}
		}
		$this->redirect('member_list');
	}

	/**
	 * @brief 从回收站还原会员
	 */
	function member_restore()
	{
		$user_ids = IReq::get('check');
		$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);
		if($user_ids)
		{
			$user_ids = IFilter::act($user_ids,'int');
			$ids = implode(',',$user_ids);
			if($ids)
			{
				$tb_member = new IModel('member');
				$tb_member->setData(array('status'=>'1'));
				$where = "user_id in (".$ids.")";
				$tb_member->update($where);
			}
		}
		$this->redirect('recycling');
	}

	//[提现管理] 删除
	function withdraw_del()
	{
		$id = IFilter::act(IReq::get('id'));

		if($id)
		{
			$id = IFilter::act($id,'int');
			$withdrawObj = new IModel('withdraw');

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
			}
			else
			{
				$where = 'id = '.$id;
			}

			$withdrawObj->del($where);
			$this->redirect('withdraw_recycle');
		}
		else
		{
			$this->redirect('withdraw_recycle',false);
			Util::showMessage('请选择要删除的数据');
		}
	}

	//[提现管理] 回收站 删除,恢复
	function withdraw_update()
	{
		$id   = IFilter::act( IReq::get('id') , 'int' );
		$type = IReq::get('type') ;

		if(!empty($id))
		{
			$withdrawObj = new IModel('withdraw');

			$is_del = ($type == 'res') ? '0' : '1';
			$dataArray = array(
				'is_del' => $is_del
			);

			if(is_array($id))
			{
				$idStr = join(',',$id);
				$where = ' id in ('.$idStr.')';
			}
			else
			{
				$where = 'id = '.$id;
			}

			$dataArray = array(
				'is_del' => $is_del,
			);

			$withdrawObj->setData($dataArray);
			$withdrawObj->update($where);
			$this->redirect('withdraw_list');
		}
		else
		{
			if($type == 'del')
			{
				$this->redirect('withdraw_list',false);
			}
			else
			{
				$this->redirect('withdraw_recycle',false);
			}
			Util::showMessage('请选择要删除的数据');
		}
	}

	//[提现管理] 详情展示
	function withdraw_detail()
	{
		$id = IFilter::act( IReq::get('id'),'int' );

		if($id)
		{
			$withdrawObj = new IModel('withdraw');
			$where       = 'id = '.$id;
			$withdrawRow = $withdrawObj->getObj($where);
            $para = JSON::decode($withdrawRow['para']);
            if($para)
            {
                $withdrawRow['charge'] = $para['charge'];
                $withdrawRow['am'] = $para['am'];
            }
            
            $this->withdrawRow = $withdrawRow;

			$userDB = new IModel('user as u,member as m');
			$this->userRow = $userDB->getObj('u.id = m.user_id and u.id = '.$this->withdrawRow['user_id']);
			$this->redirect('withdraw_detail',false);
		}
		else
		{
			$this->redirect('withdraw_list');
		}
	}

	//[提现管理] 修改提现申请的状态
	function withdraw_status()
	{
		$id      = IFilter::act( IReq::get('id'),'int');
		$re_note = IFilter::act( IReq::get('re_note'),'string');
		$status  = IFilter::act(IReq::get('status'),'int');

		if($id)
		{
			$withdrawObj = new IModel('withdraw');
			$dataArray = array(
				're_note'=> $re_note,
				'status' => $status,
			);
			$withdrawObj->setData($dataArray);
			$where = "`id`= {$id} AND `status` = 0";
			$re = $withdrawObj->update($where);

			if($re)
			{
				$logObj = new log('db');
				$logObj->write('operation',array("管理员:".$this->admin['admin_name'],"修改了提现申请","ID值为：".$id));

				//提现成功
				if($status == 2)
				{
					$withdrawRow = $withdrawObj->getObj('id = '.$id);

					//用户余额进行的操作记入account_log表
					$log = new AccountLog();
					$config=array
					(
						'user_id'  => $withdrawRow['user_id'],
						'admin_id' => $this->admin['admin_id'],
						'event'    => "withdraw",
						'num'      => $withdrawRow['amount'],
					);
					$result = $log->write($config);
					$result ? "" : die($log->error);
                    
                    //手续费记入平台账户余额
                    $para = JSON::decode($withdrawRow['para']);
                    if($para && $para['charge'] > 0)
                    {
                        $config['charge'] = $para['charge'];
                        $result = $log->writeSystem($config);
                        $result ? "" : die($log->error);
                    }
				}
			}
			$this->withdraw_detail();
			Util::showMessage("更新成功");
		}
		else
		{
			$this->redirect('withdraw_list');
		}
	}

    /**
     * @brief 商家修改页面
     */
    public function seller_edit()
    {
        $seller_id = IFilter::act(IReq::get('id'),'int');

        //修改页面
        if($seller_id)
        {
            $sellerDB        = new IModel('seller');
            $this->sellerRow = $sellerDB->getObj('id = '.$seller_id);
        }
        $this->redirect('seller_edit');
    }

    /**
     * @brief 商户的增加动作
     */
    public function seller_add()
    {
        $seller_id   = IFilter::act(IReq::get('id'),'int');
        $seller_name = IFilter::act(IReq::get('seller_name'));
        $email       = IFilter::act(IReq::get('email'));
        $password    = IFilter::act(IReq::get('password'));
        $repassword  = IFilter::act(IReq::get('repassword'));
        $truename    = IFilter::act(IReq::get('true_name'));
        $contacts_name    = IFilter::act(IReq::get('contacts_name'));
        $phone       = IFilter::act(IReq::get('phone'));
        $mobile      = IFilter::act(IReq::get('mobile'));
        $province    = IFilter::act(IReq::get('province'),'int');
        $city        = IFilter::act(IReq::get('city'),'int');
        $area        = IFilter::act(IReq::get('area'),'int');
        //$cash        = IFilter::act(IReq::get('cash'),'float');
        $is_vip      = IFilter::act(IReq::get('is_vip'),'int');
        $is_lock     = IFilter::act(IReq::get('is_lock'),'int');
        $is_recomm   = IFilter::act(IReq::get('is_recomm'),'int');
        $is_invoice   = IFilter::act(IReq::get('is_invoice'),'int');
        $address     = IFilter::act(IReq::get('address'));
        $account     = IFilter::act(IReq::get('account'));
        $server_num  = IFilter::act(IReq::get('server_num'));
        $home_url    = IFilter::act(IReq::get('home_url'));
        $sort        = IFilter::act(IReq::get('sort'),'int');
        $is_pay      = IFilter::act(IReq::get('is_pay'),'int');
        $suggest      = IFilter::act(IReq::get('suggest'));

        if(!$seller_id && $password == '')
        {
            $errorMsg = '请输入密码！';
        }

        if($password != $repassword)
        {
            $errorMsg = '两次输入的密码不一致！';
        }

        //创建商家操作类
        $sellerDB = new IModel("seller");

        if($sellerDB->getObj("seller_name = '{$seller_name}' and id != {$seller_id}"))
        {
            $errorMsg = "登录用户名重复";
        }
        else if($sellerDB->getObj("true_name = '{$truename}' and id != {$seller_id}"))
        {
            $errorMsg = "商户真实全称重复";
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
            'true_name' => $truename,
            'contacts_name' => $contacts_name,
            'account'   => $account,
            'phone'     => $phone,
            'mobile'    => $mobile,
            'email'     => $email,
            'address'   => $address,
            'is_vip'    => $is_vip,
            'is_lock'   => $is_lock,
            'is_recomm' => $is_recomm,
            'is_invoice' => $is_invoice,
            //'cash'      => $cash,
            'province'  => $province,
            'city'      => $city,
            'area'      => $area,
            'server_num'=> $server_num,
            'home_url'  => $home_url,
            'sort'      => $sort,
            'is_pay'    => $is_pay
        );

        //商户资质上传
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
        //添加新会员
        if(!$seller_id)
        {
            $sellerRow['seller_name'] = $seller_name;
            $sellerRow['password']    = md5($password);
            $sellerRow['create_time'] = ITime::getDateTime();

            $sellerDB->setData($sellerRow);
            $sellerDB->add();
        }
        //编辑会员
        else
        {
            //修改密码
            if($password)
            {
                $sellerRow['password'] = md5($password);
            }
        
            //查询商户原始开通状态
            $data = $sellerDB->getObj('id = '.$seller_id, 'is_lock');

            $sellerDB->setData($sellerRow);
            $sellerDB->update("id = ".$seller_id);
            $content = '';
            if($data['is_lock'] == 2)
            {
                $title = "耐装网申请开店审核结果";
                $content = $is_lock == 1 ? '未通过审核' : ($is_lock == 0 ? '审核通过' : '');
            }
            elseif($data['is_lock'] <> $is_lock)
            {
                $title = "耐装网";
                $content = $is_lock == 1 ? '您的店铺被锁定' : '您的店铺已解锁';
            }
            if($content)
            {
                if($suggest)
                {
                    $msg = $content.',原因是：'.$suggest;
                    if($data['is_lock'] == 2 && $is_lock == 1)
                    {
                        $temp = rand(100000,999999);
                        $model = new IModel('seller_rej_sign');
                        $model->setData(array('seller_id' => $seller_id, 'code' => $temp));
                        $model->add();
                        $url = IUrl::getHost().IUrl::creatUrl('/simple/sellerRej/_i/'.$seller_id.'/_c/'.$temp);
                        $msg .= "<br/>点击下面这个链接重新编辑开店信息：<a href='{$url}'>{$url}</a>。<br />如果不能点击，请您把它复制到地址栏中打开。";
                    }
                }
                else
                {
                    $msg = $content;
                }
                $smtp   = new SendMail();
                $result = $smtp->send($email,$title,$msg);
                if($result===false)
                {
                    Util::showMessage("发信失败,请重试！或者联系管理员查看邮件服务是否开启");
                }
            }
            
        }
        $this->redirect('seller_list');
    }
    /**
     * @brief 商户的删除动作
     */
    public function seller_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $sellerDB = new IModel('seller');
        $data = array('is_del' => 1);
        $sellerDB->setData($data);

        if(is_array($id))
        {
            $sellerDB->update('id in ('.join(",",$id).')');
        }
        else
        {
            $sellerDB->update('id = '.$id);
        }
        $this->redirect('seller_list');
    }
    /**
     * @brief 商户的回收站删除动作
     */
    public function seller_recycle_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $sellerDB = new IModel('seller');
        $goodsDB  = new IModel('goods');
        $merch_ship_infoDB = new IModel('merch_ship_info');
        $specDB = new IModel('spec');

        if(is_array($id))
        {
            $id = join(",",$id);
        }

        $sellerDB->del('id in ('.$id.')');
        $goodsDB->del('seller_id in ('.$id.')');
        $merch_ship_infoDB->del('seller_id in ('.$id.')');
        $specDB->del('seller_id in ('.$id.')');

        $this->redirect('seller_recycle_list');
    }
    /**
     * @brief 商户的回收站恢复动作
     */
    public function seller_recycle_restore()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $sellerDB = new IModel('seller');
        $data = array('is_del' => 0);
        $sellerDB->setData($data);
        if(is_array($id))
        {
            $sellerDB->update('id in ('.join(",",$id).')');
        }
        else
        {
            $sellerDB->update('id = '.$id);
        }

        $this->redirect('seller_recycle_list');
    }
    //商户状态ajax
    public function ajax_seller_lock()
    {
        $id   = IFilter::act(IReq::get('id'));
        $lock = IFilter::act(IReq::get('lock'));
        $sellerObj = new IModel('seller');
        $sellerObj->setData(array('is_lock' => $lock));
        $sellerObj->update("id = ".$id);

        //短信通知状态修改
        $sellerRow = $sellerObj->getObj('id = '.$id);
        if(isset($sellerRow['mobile']) && $sellerRow['mobile'])
        {
            $result = $lock == 0 ? "正常" : "锁定";
            $content = smsTemplate::sellerCheck(array('{result}' => $result));
            $result = Hsms::send($sellerRow['mobile'],$content,0);
        }
    }

    /**
     * @brief 装修公司修改页面
     */
    public function company_edit()
    {
        $id  = IFilter::act(IReq::get('id'),'int');

        //编辑装修公司信息读取装修公司信息
        if($id)
        {
            $userDB = new IQuery('user as u');
            $userDB->join = 'left join company as c on u.id = c.user_id';
            $userDB->where= 'u.id = '.$id;
            $companyInfo = $userDB->find();

            if($companyInfo)
            {
                $this->companyInfo = current($companyInfo);
            }
            else
            {
                $this->redirect('company_list');
                Util::showMessage("没有找到相关记录！");
                exit;
            }
        }
        $this->redirect('company_edit');
    }

    /**
     * @brief 装修公司的增加动作
     */
    public function company_add()
    {
        $id   = IFilter::act(IReq::get('id'),'int');
        $user_name  = IFilter::act(IReq::get('username'));
        $email      = IFilter::act(IReq::get('email'));
        $password   = IFilter::act(IReq::get('password'));
        $repassword = IFilter::act(IReq::get('repassword'));
        $truename   = IFilter::act(IReq::get('true_name'));
        $contacts_name    = IFilter::act(IReq::get('contacts_name'));
        $phone       = IFilter::act(IReq::get('phone'));
        $mobile     = IFilter::act(IReq::get('mobile'));
        $province   = IFilter::act(IReq::get('province'),'int');
        $city       = IFilter::act(IReq::get('city'),'int');
        $area       = IFilter::act(IReq::get('area'),'int');
        $is_lock     = IFilter::act(IReq::get('is_lock'),'int');
        $card_type   = IFilter::act(IReq::get('card_type'),'int');
        /*$is_vip      = IFilter::act(IReq::get('is_vip'),'int');
        $is_recomm   = IFilter::act(IReq::get('is_recomm'),'int');
        $is_invoice   = IFilter::act(IReq::get('is_invoice'),'int');
        $is_pay      = IFilter::act(IReq::get('is_pay'),'int');
        $home_url    = IFilter::act(IReq::get('home_url'));
        $server_num  = IFilter::act(IReq::get('server_num'));
        $account     = IFilter::act(IReq::get('account'));*/
        $address     = IFilter::act(IReq::get('address'));   
        $sort        = IFilter::act(IReq::get('sort'),'int');
        $suggest      = IFilter::act(IReq::get('suggest'));


        if(!$id && $password == '')
        {
            $errorMsg = '请输入密码！';
        }

        if($password != $repassword)
        {
            $errorMsg = '两次输入的密码不一致！';
        }

        //创建操作类
        $userDB   = new IModel("user");
        $companyDB = new IModel("company");

        if($userDB->getObj("username='".$user_name."' and id != ".$id))
        {
            $errorMsg = '登录用户名重复';
        }

        if($email && $userDB->getObj("email='".$email."' and id != ".$id))
        {
            $errorMsg = '邮箱重复';
        }

        if($mobile && $userDB->getObj("mobile='".$mobile."' and id != ".$id))
        {
            $errorMsg = '手机号码重复';
        }
        if($truename && $companyDB->getObj("true_name = '{$truename}' and user_id != {$id}"))
        {
            $errorMsg = "公司名称重复";
        }

        //操作失败表单回填
        if(isset($errorMsg))
        {
            $this->companyInfo = $_POST;
            $this->redirect('company_edit',false);
            Util::showMessage($errorMsg);
        }

        $company = array(
            'true_name' => $truename,
            'contacts_name' => $contacts_name,
            'phone'     => $phone,
            'address'   => $address,
            'is_lock'   => $is_lock,
            /*'is_vip'    => $is_vip,
            'is_recomm' => $is_recomm,
            'is_invoice' => $is_invoice,
            'home_url'  => $home_url,
            'is_pay'    => $is_pay,
            'account'   => $account,*/
            'province'  => $province,
            'city'      => $city,
            'area'      => $area,
            'sort'      => $sort
        );
        
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
        }
        else
        {
            $paperData = array();
            if($id)
            {
                $localData = $companyDB->getObj('user_id = '.$id, 'paper_img');
                $paperData = JSON::decode($localData['paper_img']);
            }
            if(isset($photoInfo['paper_imgs']['img']) && file_exists($photoInfo['paper_imgs']['img']))
            {
                $paperData['paper_imgs'] = $photoInfo['paper_imgs']['img'];
            }
            if(isset($photoInfo['tax_img']['img']) && file_exists($photoInfo['tax_img']['img']))
            {
                $paperData['tax_img'] = $photoInfo['tax_img']['img'];
            }
            if(isset($photoInfo['code_img']['img']) && file_exists($photoInfo['code_img']['img']))
            {
                $paperData['code_img'] = $photoInfo['code_img']['img'];
            }
            unset($paperData['paper_img']);
            $company['paper_img'] = JSON::encode($paperData);
        }

        if(isset($photoInfo['identity_card']['img']) && file_exists($photoInfo['identity_card']['img']))
        {
            $company['identity_card'] = $photoInfo['identity_card']['img'];
        }
        
        $user = array(
            'username' => $user_name,
            'email'        => $email,
            'mobile'       => $mobile
        );
        
        if(isset($photoInfo['head_ico']['img']) && file_exists($photoInfo['head_ico']['img']))
        {
            $user['head_ico'] = $photoInfo['head_ico']['img'];
        }
        
        //添加新装修公司
        if(!$id)
        {
            $user['password'] = md5($password);
            $userDB->setData($user);
            $user_id = $userDB->add();

            $company['user_id'] = $user_id;
            $company['create_time']    = ITime::getDateTime();

            $companyDB->setData($company);
            $companyDB->add();
        }
        //编辑会员
        else
        {
            //修改密码
            if($password)
            {
                $user['password'] = md5($password);
            }
            $userDB->setData($user);
            $userDB->update('id = '.$id);
            
            //查询原始开通状态
            $data = $companyDB->getObj('user_id = '.$id, 'is_lock');

            $companyDB->setData($company);
            $companyDB->update("user_id = ".$id);
            
            $content = '';
            if($data['is_lock'] == 2)
            {
                $content = $is_lock == 1 ? '您在耐装网开通的装修公司未通过审核' : ($is_lock == 0 ? '您在耐装网开通的装修公司审核通过' : '');
            }
            /*elseif($data['is_lock'] <> $is_lock)
            {
                $content = $is_lock == 1 ? '您在耐装网开通的装修公司被锁定' : '您在耐装网开通的装修公司已解锁';
            }*/
            if($content)
            {
                if($suggest)
                {
                    $msg = $content.',原因是：'.$suggest;
                    if($data['is_lock'] == 2 && $is_lock == 1)
                    {
                        $temp = rand(100000,999999);
                    }
                }
                else
                {
                    $msg = $content;
                }
                $result = Hsms::send($mobile,$msg);
            }
        }
        $this->redirect('company_list');
    }
    /**
     * @brief 装修公司的删除动作
     */
    public function company_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $companyDB = new IModel('company');
        $data = array('is_del' => 1);
        $companyDB->setData($data);

        if(is_array($id))
        {
            $companyDB->update('user_id in ('.join(",",$id).')');
        }
        else
        {
            $companyDB->update('user_id = '.$id);
        }
        $this->redirect('company_list');
    }
    /**
     * @brief 装修公司的回收站删除动作
     */
    public function company_recycle_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $companyDB = new IModel('company');

        if(is_array($id))
        {
            $id = join(",",$id);
        }

        $companyDB->del('user_id in ('.$id.')');

        $this->redirect('company_recycle_list');
    }
    /**
     * @brief 装修公司的回收站恢复动作
     */
    public function company_recycle_restore()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $companyDB = new IModel('company');
        $data = array('is_del' => 0);
        $companyDB->setData($data);
        if(is_array($id))
        {
            $companyDB->update('user_id in ('.join(",",$id).')');
        }
        else
        {
            $companyDB->update('user_id = '.$id);
        }

        $this->redirect('company_recycle_list');
    }
    //装修公司状态ajax
    public function ajax_company_lock()
    {
        $id   = IFilter::act(IReq::get('id'));
        $lock = IFilter::act(IReq::get('lock'));
        $companyObj = new IModel('company');
        $companyObj->setData(array('is_lock' => $lock));
        $companyObj->update("user_id = ".$id);

        //短信通知状态修改
        $userObj = new IModel('user');
        $userRow = $userObj->getObj('id = '.$id);
        if(isset($userRow['mobile']) && $userRow['mobile'])
        {
            $result = $lock == 0 ? "正常" : "锁定";
            $content = smsTemplate::sellerCheck(array('{result}' => $result));
            $result = Hsms::send($userRow['mobile'],$content,0);
        }
    }

	/**
	 * @brief 运营中心修改页面
	 */
	public function operator_edit()
	{
        $id  = IFilter::act(IReq::get('id'),'int');

        //编辑装修公司信息读取装修公司信息
        if($id)
        {
            $userDB = new IQuery('user as u');
            $userDB->join = 'left join operator as o on u.id = o.user_id';
            $userDB->where= 'u.id = '.$id;
            $operatorInfo = $userDB->find();

            if($operatorInfo)
            {
                $this->operatorInfo = current($operatorInfo);
            }
            else
            {
                $this->redirect('operator_list');
                Util::showMessage("没有找到相关记录！");
                exit;
            }
        }
        $this->redirect('operator_edit');
	}

	/**
	 * @brief 运营中心的增加动作
	 */
	public function operator_add()
	{
        $id   = IFilter::act(IReq::get('id'),'int');
        $user_name  = IFilter::act(IReq::get('username'));
        $email      = IFilter::act(IReq::get('email'));
        $password   = IFilter::act(IReq::get('password'));
        $repassword = IFilter::act(IReq::get('repassword'));
        $truename   = IFilter::act(IReq::get('true_name'));
        $contacts_name    = IFilter::act(IReq::get('contacts_name'));
        $phone       = IFilter::act(IReq::get('phone'));
        $mobile     = IFilter::act(IReq::get('mobile'));
        $province   = IFilter::act(IReq::get('province'),'int');
        $city       = IFilter::act(IReq::get('city'),'int');
        $area       = IFilter::act(IReq::get('area'),'int');
        $is_lock     = IFilter::act(IReq::get('is_lock'),'int');
        $address     = IFilter::act(IReq::get('address'));   
        $sort        = IFilter::act(IReq::get('sort'),'int');


        if(!$id && $password == '')
        {
            $errorMsg = '请输入密码！';
        }

        if($password != $repassword)
        {
            $errorMsg = '两次输入的密码不一致！';
        }

        //创建操作类
        $userDB   = new IModel("user");
        $operatorDB = new IModel("operator");

        if($userDB->getObj("username='".$user_name."' and id != ".$id))
        {
            $errorMsg = '登录用户名重复';
        }

        if($email && $userDB->getObj("email='".$email."' and id != ".$id))
        {
            $errorMsg = '邮箱重复';
        }

        if($mobile && $userDB->getObj("mobile='".$mobile."' and id != ".$id))
        {
            $errorMsg = '手机号码重复';
        }
        if($truename && $operatorDB->getObj("true_name = '{$truename}' and user_id != {$id}"))
        {
            $errorMsg = "运营中心名称重复";
        }

        //操作失败表单回填
        if(isset($errorMsg))
        {
            $this->operatorInfo = $_POST;
            $this->redirect('operator_edit',false);
            Util::showMessage($errorMsg);
        }

        $operator = array(
            'true_name' => $truename,
            'contacts_name' => $contacts_name,
            'phone'     => $phone,
            'address'   => $address,
            'is_lock'   => $is_lock,
            'province'  => $province,
            'city'      => $city,
            'area'      => $area,
            'sort'      => $sort
        );
        
        //文件上传
        if((isset($_FILES['head_ico']['name']) && $_FILES['head_ico']['name']) || (isset($_FILES['identity_card']['name']) && $_FILES['identity_card']['name']))
        {
            $uploadObj = new PhotoUpload();
            $uploadObj->setIterance(false);
            $photoInfo = $uploadObj->run();
        }
        if(isset($photoInfo['identity_card']['img']) && file_exists($photoInfo['identity_card']['img']))
        {
            $operator['identity_card'] = $photoInfo['identity_card']['img'];
        }
        
        $user = array(
            'username' => $user_name,
            'email'        => $email,
            'mobile'       => $mobile
        );
        
        if(isset($photoInfo['head_ico']['img']) && file_exists($photoInfo['head_ico']['img']))
        {
            $user['head_ico'] = $photoInfo['head_ico']['img'];
        }
        
        //添加新运营中心
        if(!$id)
        {
            $user['password'] = md5($password);
            $user['type'] = 4;
            $userDB->setData($user);
            $user_id = $userDB->add();

            $operator['user_id'] = $user_id;
            $operator['create_time']    = ITime::getDateTime();

            $operatorDB->setData($operator);
            $operatorDB->add();
        }
        //编辑运营中心
        else
        {
            //修改密码
            if($password)
            {
                $user['password'] = md5($password);
            }
            $userDB->setData($user);
            $userDB->update('id = '.$id);

            $operatorDB->setData($operator);
            $operatorDB->update("user_id = ".$id);
        }
        $this->redirect('operator_list');
	}
	/**
	 * @brief 运营中心的删除动作
	 */
	public function operator_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$operatorDB = new IModel('operator');
		$data = array('is_del' => 1);
		$operatorDB->setData($data);

		if(is_array($id))
		{
			$operatorDB->update('user_id in ('.join(",",$id).')');
		}
		else
		{
			$operatorDB->update('user_id = '.$id);
		}
		$this->redirect('operator_list');
	}
	/**
	 * @brief 运营中心的回收站删除动作
	 */
	public function operator_recycle_del()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$operatorDB = new IModel('operator');

		if(is_array($id))
		{
			$id = join(",",$id);
		}

		$operatorDB->del('user_id in ('.$id.')');

		$this->redirect('operator_recycle_list');
	}
	/**
	 * @brief 运营中心的回收站恢复动作
	 */
	public function operator_recycle_restore()
	{
		$id = IFilter::act(IReq::get('id'),'int');
		$operatorDB = new IModel('operator');
		$data = array('is_del' => 0);
		$operatorDB->setData($data);
		if(is_array($id))
		{
			$operatorDB->update('user_id in ('.join(",",$id).')');
		}
		else
		{
			$operatorDB->update('user_id = '.$id);
		}

		$this->redirect('operator_recycle_list');
	}
	//运营中心状态ajax
	public function ajax_operator_lock()
	{
		$id   = IFilter::act(IReq::get('id'));
		$lock = IFilter::act(IReq::get('lock'));
		$operatorObj = new IModel('operator');
		$operatorObj->setData(array('is_lock' => $lock));
		$operatorObj->update("user_id = ".$id);
	}

    /**
     * @brief 合同修改页面
     */
    public function contract_edit()
    {
        $contract_id = IFilter::act(IReq::get('id'),'int');

        //修改页面
        if($contract_id)
        {
            $contractDB        = new IModel('contract');
            $this->contractRow = $contractDB->getObj('id = '.$contract_id);
        }
        $this->redirect('contract_edit');
    }

    /**
     * @brief 合同的增加动作
     */
    public function contract_add()
    {
        $contract_id = IFilter::act(IReq::get('id'),'int');
        $name        = IFilter::act(IReq::get('name'));
        $con_num     = IFilter::act(IReq::get('con_num'));
        $con_file    = IFilter::act(IReq::get('con_file'));

        $contractDB = new IModel("contract");
        
        //待更新的数据
        $contractRow = array(
            'name'      => $name,
            'con_num'   => $con_num,
        );

        //合同文件上传        
        $upObj  = new IUpload();
        $attach = 'con_file';
        $dir = IWeb::$app->config['upload'].'/file/'.date('Y')."/".date('m')."/".date('d');
        $upObj->setDir($dir);
        $upState = $upObj->execute();
        if(!isset($upState[$attach]))
        {
            if($con_file == '')
            {
                $error_message = '没有上传文件';
            }
        }
        else
        {
            if($upState[$attach][0]['flag']== 1)
            {
                $con_file = $dir.'/'.$upState[$attach][0]['name'];
            }
            else
            {
                $error_message = IUpload::errorMessage($upState[$attach][0]['flag']);
            }
        }
        $contractRow['con_file'] = $con_file;
        
        //添加新合同
        if(!$contract_id)
        {
            $contractRow['create_time'] = ITime::getDateTime();

            $contractDB->setData($contractRow);
            $contractDB->add();
        }
        //编辑合同
        else
        {
            $contractDB->setData($contractRow);
            $contractDB->update("id = ".$contract_id);
        }
        $this->redirect('contract_list');
    }
    /**
     * @brief 合同的删除动作
     */
    public function contract_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $contractDB = new IModel('contract');
        $data = array('is_del' => 1);
        $contractDB->setData($data);

        if(is_array($id))
        {
            $contractDB->update('id in ('.join(",",$id).')');
        }
        else
        {
            $contractDB->update('id = '.$id);
        }
        $this->redirect('contract_list');
    }

    //修改风格页面
    function style_edit()
    {
        $this->layout = '';

        $id        = IFilter::act(IReq::get('id'),'int');    

        $dataRow = array(
            'id'        => '',
            'name'      => '',
            'sort'      => ''
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

        $editData = array(
            'id'        => $id,
            'name'      => $name, 
            'sort'      => $sort
        );

        //执行操作
        $obj = new IModel('case_style');
        $obj->setData($editData);

        //更新修改
        if($id)
        {
            $where = 'id = '.$id;   
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
}