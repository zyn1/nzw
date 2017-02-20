<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file _userInfo.php
 * @brief 用户注册登录插件
 * @author nswe
 * @date 2016/4/15 8:51:13
 * @version 4.4
 */
class _userInfo extends pluginBase
{
	//注册事件
	public function reg()
	{
		//用户登录后的操作回调
		plugin::reg("userLoginCallback",$this,"userLoginCallback");

		//从cookie或者session中获取用户信息
		plugin::reg("getUser",function(){
			return self::getUser();
		});

		//获取验证通过的用户信息
		plugin::reg("isValidUser",function($data){
			list($username,$password) = $data;
			return self::isValidUser($username,$password);
		});

		//初始化用户数据控制器里面
		plugin::reg("onBeforeCreateAction",$this,"initUser");

		//注册页面拦截配置
		plugin::reg("onCreateView@simple@reg",$this,"initUserReg");
		plugin::reg("onCreateView@simple@bind_user",$this,"initUserReg");

        //用户注册方法
        plugin::reg("userRegAct",$this,"userRegAct");      

		//手机注册验证码
		plugin::reg("onBeforeCreateAction@simple@_sendMobileCode",function(){
			self::controller()->_sendMobileCode = function(){$this->sendRegMobileCode();};
		});

		//邮箱验证
		plugin::reg("onBeforeCreateAction@simple@check_mail",function(){
			self::controller()->check_mail = function(){$this->check_mail();};
		});

		//用户登录校验
		plugin::reg("userLoginAct",$this,"userLoginAct");

		//登陆页面拦截
		plugin::reg('onCreateView@simple@login',function(){
			//记录callback地址
			$this->saveCallback();
		});

		//获取保存的路径地址
		plugin::reg("getCallback",function(){
			return ISession::get('callback');
		});

		//保存的路径地址
		plugin::reg("setCallback",function($url){
			return ISession::set('callback',$url);
		});
	}

	//注册用户初始化
	public function initUserReg()
	{
		//记录callback地址
		$this->saveCallback();

		$siteObj = new Config('site_config');
		if($siteObj->reg_option == 2)
		{
			IError::show("网站当前已经关闭注册");
		}

		else
		{
			plugin::reg("onFinishView",function(){
				$this->view("mobileCheck");
			});
		}
	}

	//处理callback回调地址
	public function saveCallback()
	{
		$callback = IReq::get('callback') ? htmlspecialchars(IReq::get('callback')) : IUrl::getRefRoute();
		if($callback && strpos($callback,"/simple/reg") === false && strpos($callback,"/simple/login") === false && strpos($callback,"bind_user") === false)
		{
			ISession::set('callback',rtrim($callback,"/"));
		}
	}

	//用户登录
	public function userLoginAct()
	{
    	$login_info = IFilter::act(IReq::get('login_info','post'));
        $password   = IReq::get('password','post');
    	$captcha    = IFilter::act(IReq::get('captcha','post'));
        $_captcha   = ISafe::get('captcha');
    	$is_auto   = IFilter::act(IReq::get('is_auto','post'));

        if((!$_captcha || !$captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            return "图形验证码输入不正确";
        }
        
    	if($login_info == '')
    	{
    		return '请填写用户名，邮箱，手机号';
    	}

		if(!preg_match('|\S{6,32}|',$password))
    	{
    		return '密码格式不正确,请输入6-32个字符';
    	}

    	$password = md5($password);
		if($userRow = plugin::trigger("isValidUser",array($login_info,$password)))
		{
			$this->userLoginCallback($userRow);

			//记住帐号
			if($is_auto == 1)
			{
                ICookie::set('loginName',$login_info);
				ICookie::set('loginPassword',md5($password.'nz826.com'));
			}
			return $userRow;
		}
		return "用户名或密码错误";
	}

    //用户注册
    public function userRegAct()
    {                                                         
        $mobile     = IFilter::act(IReq::get('mobile','post'));
        $mobile_code= IFilter::act(IReq::get('mobile_code','post'));
        $true_name  = IFilter::act(IReq::get('true_name','post'));
        $username   = IFilter::act(IReq::get('username','post'));
        $contacts_name = IFilter::act(IReq::get('contacts_name', 'post'));
        $password   = IFilter::act(IReq::get('password','post'));
        $repassword = IFilter::act(IReq::get('repassword','post'));
        $captcha    = IFilter::act(IReq::get('captcha','post'));
        $address = IFilter::act(IReq::get('address', 'post'));
        $_captcha   = ISafe::get('captcha');
        
        $province = IReq::get('province');
        $city = IReq::get('city');
        $area = IReq::get('area');
        if(!$province || !$city || !$area)
        {
            return "请选择地区";
        }

        //获取注册配置参数
        $siteConfig = new Config('site_config');
        $reg_option = $siteConfig->reg_option;
        $reg_type = IFilter::act(IReq::get('reg_type'), 'int');

        /*注册信息校验*/
        if($reg_option == 2)
        {
            return "当前网站禁止新用户注册";
        }

        if(!preg_match('|\S{6,32}|',$password))
        {
            return "密码是字母，数字，下划线组成的6-32个字符";
        }

        if($password != $repassword && IClient::getDevice() == IClient::PC)
        {
            return "2次密码输入不一致";
        }

        if((!$_captcha || !$captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
        {
            return "图形验证码输入不正确";
        }
        
        if(IValidate::mobi($mobile) == false)
        {
            return "手机号格式不正确";
        }

        $_mobileCode = ISafe::get('code'.$mobile);
        if(!$mobile_code || !$_mobileCode || $mobile_code != $_mobileCode)
        {
            return "手机号验证码不正确";
        }

        $userObj = new IModel('user');
        $userRow = $userObj->getObj('mobile = "'.$mobile.'"');
        if($userRow)
        {
            return "手机号已经被注册";
        }

        //用户名检查
        if($username && IValidate::name($username) == false)
        {
            return "用户名必须是由2-20个字符，可以为字数，数字下划线和中文";
        }
        elseif($username)
        {
            $userObj = new IModel('user');
            $userRow = $userObj->getObj('username = "'.$username.'"');
            if($userRow)
            {
                return "用户名已经被注册";
            }
        }

        //插入user表
        $userArray = array(
            'password' => md5($password),
            'mobile'  => $mobile,  
            'type'    => IReq::get('t') ? IFilter::act(IReq::get('t')) : 1
        );
        if($userArray['type'] == 1)
        {
            $userArray['username'] = $username;
        }
        if($userArray['type'] == 2 && empty($address))
        {
            return "请填写地址";
        }
        $userObj->setData($userArray);
        $user_id = $userObj->add();
        if(!$user_id)
        {
            return "用户创建失败";
        }

        if($userArray['type'] == 1)
        {
            //插入member表
            $memberArray = array(
                'user_id' => $user_id,
                'time'    => ITime::getDateTime(),
                'status'  => 1,
                'area' => $province.','.$city.','.$area,
                'true_name'  => $true_name,
            );
            $memberObj = new IModel('member');
            $memberObj->setData($memberArray);
            $memberObj->add();
            
            //绑定运营中心
            $sellerObj = new IModel('user as u, seller as s');
            if($row = $sellerObj->getObj('u.type = 4 and s.is_del = 0 and s.is_lock = 0 and s.area = '.$area.' and u.relate_id = s.id', 's.id'))
            {
                $obj = new IModel('operational_user');
                $data = array(
                            'object_id' => $user_id,
                            'operation_id' => $row['id'],
                            'type' => 1,
                            'time' => ITime::getDateTime()
                        );
                $obj->setData($data);
                $obj->add();
            }
        }
        elseif($userArray['type'] == 2)
        {
            //插入member表
            $companyArray = array(
                'user_id' => $user_id,
                'contacts_name' => $contacts_name,
                'create_time'    => ITime::getDateTime(),
                'is_lock'  => 2,
                'province'  => $province,
                'city'  => $city,
                'area'  => $area,
                'true_name'  => $true_name,
                'address' => $address
            );
            $companyObj = new IModel('company');
            $companyObj->setData($companyArray);
            $companyObj->add();
        }  

        $userArray['id']       = $user_id;
        $userArray['head_ico'] = "";
        if($userArray['type'] == 1)
        {
            $this->userLoginCallback($userArray);
        }
        return $userArray;
    }

	//发送注册验证码
	public function sendRegMobileCode()
	{
		$mobile   = IReq::get('mobile');
        $captcha  = IReq::get('captcha');
		$_captcha = ISafe::get('captcha');
		if(IValidate::mobi($mobile) == false)
		{
			die("请填写正确的手机号码");
		}
		if((!$captcha || !$_captcha || $captcha != $_captcha) && IClient::getDevice() == IClient::PC)
		{
			die("请填写正确的图形验证码");
		}

		$userObj = new IModel('user');
		$userRow = $userObj->getObj('mobile = "'.$mobile.'"');
		if($userRow)
		{
			die("手机号已经被注册");
		}

		$mobile_code = rand(100000,999999);
		$content = smsTemplate::checkCode(array('{mobile_code}' => $mobile_code));
		$result = Hsms::send($mobile,$content);
		if($result == 'success')
		{
			ISafe::set("code".$mobile,$mobile_code);
		}
		else
		{
			die($result);
		}
	}

	/**
	 * @brief 用户数据初始化赋值给控制器
	 */
	public function initUser()
	{
		$controller       = self::controller();
		$controller->user = self::getUser();
	}

	/**
	 * @brief 获取通用的注册用户数组
	 * @return array or null用户数据
	 */
	public static function getUser()
	{
        if(ISafe::get('loginName'))
        {
            //自动登录
            $user = array(
                'username' => ISafe::get('loginName'),
                'user_pwd' => ISafe::get('loginPassword'),
                't'     => 1
            );
        }
        else
        {
		    $user = array(
			    'username' => ISafe::get('username','session'),
			    'user_pwd' => ISafe::get('user_pwd','session'),
                't'     => 2
		    );
        }

		if($userRow = self::isValidUser($user['username'],$user['user_pwd'],$user['t']))
		{
			$user['user_id'] = $userRow['id'];
            $user['head_ico']= $userRow['head_ico'];
			$user['type']= $userRow['type'];
            unset($user['t']);
			return $user;
		}
		else
		{
			plugin::trigger('clearUser');
			return null;
		}
	}

	/**
	 * @brief  校验注册用户身份信息
	 * @param  string $login_info 用户名或者email
	 * @param  string $password   用户名的md5密码
	 * @return array or false 如果合法则返回用户数据;不合法返回false
	 */
	public static function isValidUser($login_info,$password,$type = 2)
	{
		$login_info = IFilter::addSlash($login_info);
		$password   = IFilter::addSlash($password); 
        $userDB = new IModel('user');
        $userDetail = $userDB->getObj("username = '{$login_info}' or email = '{$login_info}' or mobile='{$login_info}'");
        if($userDetail)
        {
            if($userDetail['type'] == 1 || $userDetail['type'] == 4)
            {
		        $memberObj = new IModel('member');
		        $where   = "status = 1 and user_id = ".$userDetail['id'];
		        $row = $memberObj->getObj($where);
            }
            elseif($userDetail['type'] == 2)
            {
                $companyObj = new IModel('company');
                $where   = "is_lock = 0 and is_del = 0 and user_id = ".$userDetail['id'];
                $row = $companyObj->getObj($where);
            }
            $userRow = array_merge($userDetail,$row);                 
		    if(isset($row) && !empty($row))
		    {
                if(($type == 1 && (md5($userRow['password'].'nz826.com') == $password)) || ($type == 2 && ($userRow['password'] == $password)))
                {
                    return $userRow;   
                }
                else
                {
                    return false;
                }
		    }
        }
		return false;
	}

	/**
	 * @brief 用户登录
	 * @param array $userRow 用户信息登录
	 */
	public function userLoginCallback($userRow)
	{
		//用户私密数据
		ISafe::set('user_id',$userRow['id'],'session');
		ISafe::set('username',$userRow['username'],'session');
		ISafe::set('user_pwd',$userRow['password'],'session');
        ISafe::set('head_ico',isset($userRow['head_ico']) ? $userRow['head_ico'] : '');
		ISafe::set('user_type',isset($userRow['type']) ? $userRow['type'] : 1);
		ISafe::set('last_login',isset($userRow['last_login']) ? $userRow['last_login'] : '');

        if(isset($userRow['type']) && ($userRow['type'] == 1 || $userRow['type'] == 4))
        {
            $time = ITime::getDateTime();
		    //更新最后一次登录时间
		    $memberObj = new IModel('member');
		    $dataArray = array(
			    'last_login' => $time,
		    );
		    $memberObj->setData($dataArray);
		    $where     = 'user_id = '.$userRow["id"];
		    $memberObj->update($where);

		    //根据经验值分会员组
		    $memberRow = $memberObj->getObj($where,'exp');
		    $groupObj  = new IModel('user_group');
		    $groupRow  = $groupObj->getObj($memberRow['exp'].' between minexp and maxexp and minexp > 0 and maxexp > 0','id','discount desc');
		    if($groupRow)
		    {
			    $dataArray = array('group_id' => $groupRow['id']);
			    $memberObj->setData($dataArray);
			    $memberObj->update($where);
		    }
            
            if($userRow['type'] == 4)
            {
                $userDB = new IModel('user');
                $sellerId = $userDB->getObj('id = '.$userRow['id'], 'relate_id');
                $sellerDB = new IModel('seller');
                $sellerRow['login_time'] = $time;
                $sellerDB->setData($sellerRow);
                $sellerDB->update('id = '.$sellerId['relate_id']);
                
                ISafe::set('seller_id',$sellerId['relate_id'],'session');
                ISafe::set('seller_name',$userRow['username'],'session');
                ISafe::set('seller_pwd',$userRow['password'],'session');
            }
        }
        elseif(isset($userRow['type']) && $userRow['type'] == 2)
        {
            //更新最后一次登录时间
            $companyObj = new IModel('company');
            $dataArray = array(
                'last_login' => ITime::getDateTime(),
            );
            $companyObj->setData($dataArray);
            $where     = 'user_id = '.$userRow["id"];
            $companyObj->update($where);
        }
	}

	/**
	 * @brief 发送验证邮箱邮件
	 * @param $email string 邮箱地址
	 */
	public function send_check_mail($email)
	{
		if(IValidate::email($email) == false)
		{
			IError::show(403,'邮件格式错误');
		}

		$userDB  = new IModel('user');
		$userRow = $userDB->getObj('email = "'.$email.'"');
		if(!$userRow)
		{
			IError::show(403,'用户信息不存在');
		}
		$code    = base64_encode($userRow['email']."|".$userRow['id']);
		$url     = IUrl::getHost().IUrl::creatUrl("/simple/check_mail/code/{$code}");
		$content = mailTemplate::checkMail(array("{url}" => $url));

		//发送邮件
		$smtp   = new SendMail();
		$result = $smtp->send($email,"用户注册邮箱验证",$content);
		if($result===false)
		{
			IError::show(403,"发信失败,请重试！或者联系管理员查看邮件服务是否开启");
		}

		$message = "您的邮箱验证邮件已发送到{$email}！请到您的邮箱中去激活";
		self::controller()->redirect('/site/success?message='.urlencode($message).'&email='.$email);
	}

	/**
	 * @brief 验证邮箱
	 */
	public function check_mail()
	{
		$code = IReq::get("code");
		list($email,$user_id) = explode('|',base64_decode($code));
		if(IValidate::email($email) == false)
		{
			$message = "邮箱格式不正确";
		}
		else
		{
			$email   = IFilter::act($email);
			$user_id = IFilter::act($user_id,'int');

            $memberObj = new IModel("member");
			$userObj = new IModel("user");
			$userRow = $userObj->getObj(" email = '{$email}' and id = ".$user_id );
			if($userRow)
			{
				//更新用户状态
				$memberObj->setData(array("status" => 1));
				$memberObj->update("user_id = ".$user_id);

				//获取用户信息
				$userRow = $userObj->getObj('id = '.$user_id);
				$message = "恭喜，您的邮箱激活成功！";
				$this->userLoginCallback($userRow);
			}
			else
			{
				$message = "验证信息有误，请核实！";
			}
		}
		self::controller()->redirect('/site/success?message='.urlencode($message));
	}
}