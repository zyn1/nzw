<?php
/**
 * @brief 商家登录控制器
 * @class Seller
 * @author chendeshan
 * @datetime 2014/7/19 15:18:56
 */
class SystemSeller extends IController
{
	public $layout = '';

    /**
     * @brief 商家登录动作
     */
    public function login()
    {
        $seller_name = IFilter::act(IReq::get('username'));
        $password    = IReq::get('password');
        $message     = '';

        if($seller_name == '')
        {
            $message = '登录名不能为空';
        }
        else if($password == '')
        {
            $message = '密码不能为空';
        }
        else
        {
            $sellerObj = new IModel('seller');
            $sellerRow = $sellerObj->getObj('seller_name = "'.$seller_name.'" and is_del = 0 and is_lock = 0');
            if($sellerRow && ($sellerRow['password'] == md5($password)))
            {
                $dataArray = array(
                    'login_time' => ITime::getDateTime(),
                );
                $sellerObj->setData($dataArray);
                $where = 'id = '.$sellerRow["id"];
                $sellerObj->update($where);

                //存入私密数据
                ISafe::set('seller_id',$sellerRow['id'],'session');
                ISafe::set('seller_name',$sellerRow['seller_name'],'session');
                ISafe::set('seller_pwd',$sellerRow['password'],'session');

                $this->redirect('/seller/index');
            }
            else
            {
                $message = '用户名与密码不匹配';
            }
        }

        if($message != '')
        {
            $this->redirect('index',false);
            Util::showMessage($message);
        }
    }

	/**
	 * @brief 商家找回密码
	 */
	public function findPassword()
	{
        $message     = '';
        $seller_name = IFilter::act(IReq::get('username'));
		$mobile = IFilter::act(IReq::get('mobile'));
		$mobile_code = IFilter::act(IReq::get('mobile_code'));
        if(!$mobile || !IValidate::mobi($mobile))
        {
            $message = "请输入手机号";
        }
        elseif(!$mobile_code)
        {
            $message = "请输入短信校验码";
        }
		elseif($seller_name == '')
		{
			$message = '登录名不能为空';
		}
        else
		{
            $sellerDB = new IModel('seller');
            $sellerRow = $sellerDB->getObj('seller_name = "'.$seller_name.'" and mobile = "'.$mobile.'"');
            if($sellerRow)
            {
                $findPasswordDB = new IModel('find_password_seller');
                $dataRow = $findPasswordDB->getObj('seller_id = '.$sellerRow['id'].' and hash = "'.$mobile_code.'"');
                if($dataRow)
                {
                    //短信验证码已经过期
                    if(time() - $dataRow['addtime'] > 3600)
                    {
                        $findPasswordDB->del("seller_id = ".$sellerRow['id']);
                        $message = "您的短信校验码已经过期了，请重新找回密码";
                    }
                    else
                    {
                        $this->redirect('/systemseller/restore_password/hash/'.$mobile_code.'/id/'.$sellerRow['id']);
                    }
                }
                else
                {
                    $message = "您输入的短信校验码错误";
                }
            }
            else
            {
                $message = "用户名与手机号码不匹配";
            }
        }
		if($message != '')
		{
			$this->redirect('find_password',false);
			Util::showMessage($message);
		}
	}

    //找回密码发送手机验证码短信
    function send_message_mobile()
    {
        $username = IFilter::act(IReq::get('username'));
        $mobile = IFilter::act(IReq::get('mobile'));
        $captcha = IFilter::act(IReq::get('captcha'));
        $_captcha = ISafe::get('captcha');
        if(!$username)
        {
            die("请输入正确的用户名");
        }

        if(!$mobile || !IValidate::mobi($mobile))
        {
            die("请输入正确的手机号码");
        }
        if(!$captcha || !$_captcha || $captcha != $_captcha)
        {
            die("请填写正确的图形验证码");
        }

        $sellerDB = new IModel('seller');
        $sellerRow = $sellerDB->getObj('seller_name = "'.$username.'" and mobile = "'.$mobile.'"');

        if($sellerRow)
        {
            $findPasswordDB = new IModel('find_password_seller');
            $dataRow = $findPasswordDB->query('seller_id = '.$sellerRow['id'],'*','addtime desc');
            $dataRow = current($dataRow);

            //120秒是短信发送的间隔
            /*if( isset($dataRow['addtime']) && (time() - $dataRow['addtime'] <= 120) )
            {
                die("申请验证码的时间间隔过短，请稍候再试");
            }*/
            $mobile_code = rand(100000,999999);
            $findPasswordDB->setData(array(
                'seller_id' => $sellerRow['id'],
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
        $message = '';
        $hash = IFilter::act(IReq::get("hash"));
        $id = IFilter::act(IReq::get("id"),'int');

        if(!$hash)
        {
            $message = "找不到校验码";
        }
        else
        {
            $tb = new IModel("find_password_seller");
            $addtime = time() - 3600*72;
            $where  = " `hash`='$hash' AND addtime > $addtime ";

            $row = $tb->getObj($where);
            if(!$row)
            {
                $message = "校验码已经超时";
            }

            else if($row['seller_id'] != $id)
            {
                $message = "验证码不属于此用户";
            }
        }
        if($message)
        {
            Util::showMessage($message);
        }
        $this->formAction = IUrl::creatUrl("/systemseller/do_restore_password/hash/$hash/id/".$id);
        $this->redirect("restore_password");
    }

    /**
     * @brief 执行密码修改重置操作
     */
    function do_restore_password()
    {
        $message = '';
        $hash = IFilter::act(IReq::get("hash"));
        $id = IFilter::act(IReq::get("id"),'int');

        if(!$hash)
        {
            $message = "找不到校验码";
        }
        $tb = new IModel("find_password_seller");
        $addtime = time() - 3600*72;
        $where  = " `hash`='$hash' AND addtime > $addtime ";

        $row = $tb->getObj($where);
        if(!$row)
        {
            $message = "校验码已经超时";
        }

        if($row['seller_id'] != $id)
        {
            $message = "验证码不属于此用户";
        }

        //开始修改密码
        $pwd   = IReq::get("password");
        $repwd = IReq::get("repassword");
        if(!$pwd == null || $repwd!=$pwd)
        {
            $message = "请输入新密码，且两次输入的密码应该一致。";
        }
        $pwd = md5($pwd);
        $tb_seller = new IModel("seller");
        $tb_seller->setData(array("password" => $pwd));
        $re = $tb_seller->update("id='{$row['seller_id']}'");
        if($re !== false)
        {
            $message = "修改密码成功";
            $tb->del("`hash`='{$hash}'");
            $this->redirect("/site/success/message/".urlencode($message));
            return;
        }
        $message = "密码修改失败，请重试";
        if($message)
        {
            Util::showMessage($message);
        }
    }


	//后台登出
	function logout()
	{
		plugin::trigger('clearSeller');
    	$this->redirect('index');
	}
}