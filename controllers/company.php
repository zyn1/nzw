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
	 * @brief 图片上传的方法
	 */
	public function img_upload()
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
        
        //文件上传
        if(isset($_FILES['head_ico']['name']) && $_FILES['head_ico']['name'])
        {
            $uploadObj = new PhotoUpload();
            $uploadObj->setIterance(false);
            $photoInfo = $uploadObj->run();
        }
        
        if(isset($photoInfo['head_ico']['img']) && file_exists($photoInfo['head_ico']['img']))
        {
            $data['head_ico'] = $photoInfo['head_ico']['img'];
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

    //修改装修公司简介
    public function company_desc()
    {
        $user_id = $this->company['user_id'];
        $companyDB = new IModel('company');
        $desc = IFilter::act(IReq::get('content'));
        if($desc)
        {
            $data['desc'] = $desc;
            $companyDB->setData($data);
            $companyDB->update('user_id = '.$user_id);
            $this->redirect('index');
        }
        else
        {                                                                         
            $this->companyRow = $companyDB->getObj('user_id = '.$user_id, '`desc`');
            $this->redirect('company_desc');
        }
    }
    
    //编辑案例
    public function case_edit()
    {
        $id = IReq::get('id') ? IReq::get('id') : 0;
        $caseDB = new IModel('case');
        $caseRow = $caseDB->getObj('id = '.$id);
        $this->caseRow = $caseRow;
        $this->redirect('case_edit');
    }
    
    public function case_update()
    {
        $id      = IFilter::act(IReq::get('id'),'int');
        $user_id = $this->company['user_id'];
        $caseDB = new IModel('case');
        $where = 'id = '.$id.' and user_id = '.$user_id;
        if($id && !$caseDB->getObj($where))
        {
            $this->redirect('case_list');
        }
        $styles = IFilter::act(IReq::get('style'), 'int'); 

        $dataArray = array(
            'user_id' => $user_id,        
            'title' => IFilter::act(IReq::get('title','post')),
            'style' => $styles ? implode(',',$styles) : '',
            'house_size' => IFilter::act(IReq::get('size','post')),
            'area' => IReq::get('province').','.IReq::get('city').','.IReq::get('area'),
            'address' => IFilter::act(IReq::get('address','post')),
            'type' => IFilter::act(IReq::get('type','post')),
            'house_type' => IFilter::act(IReq::get('house_type')),
            'price' => IFilter::act(IReq::get('price','post')),
            'intro' => IFilter::act(IReq::get('intro','post')),
            'description' => IFilter::act(IReq::get('description','post')),
            'time' => ITime::getDateTime('Y-m-d') 
        ); 
          
        //处理上传图片
        if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != '')
        {
            $uploadObj = new PhotoUpload();
            $photoInfo = $uploadObj->run();
            $dataArray['photo'] = $photoInfo['photo']['img'];
        }
        elseif(!$id)
        {
             die('请上传主图');
        }                               
        $caseDB->setData($dataArray);
        if($id)
        {
            $caseDB->update($where);
        }
        else
        {
            $caseDB->add();
        }
        $this->redirect('case_list');
    }
    
    public function case_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');

        if($id)
        {
            $idString = is_array($id) ? join(',',$id) : $id;
            $caseObj  = new IModel('case');
            $caseObj->del("id in ( {$idString} ) and user_id = ".$this->company['user_id']);
            $this->redirect('case_list');
        }
        else
        {
            $this->redirect('case_list',false);
            Util::showMessage('请选择要删除的案例');
        }
    }
    
    //编辑设计师
    public function designer_edit()
    {
        $id = IReq::get('id') ? IReq::get('id') : 0;
        $designerDB = new IModel('designer');
        $designerRow = $designerDB->getObj('id = '.$id);
        $this->designerRow = $designerRow;
        $this->redirect('designer_edit');
    }
    
    public function designer_update()
    {
        $id = IFilter::act(IReq::get('id'),'int');
        $user_id = $this->company['user_id'];
        $designerDB = new IModel('designer');
        $where = 'id = '.$id.' and user_id = '.$user_id;
        if($id && !$designerDB->getObj($where))
        {
            $this->redirect('designer_list');
        }
        $styles = IFilter::act(IReq::get('style'), 'int'); 

        $dataArray = array(
            'user_id' => $user_id,        
            'name' => IFilter::act(IReq::get('name','post')),
            'style' => $styles ? implode(',',$styles) : '',                            
            'num' => IFilter::act(IReq::get('num','post')),       
            'intro' => IFilter::act(IReq::get('intro','post')),
            'desc' => IFilter::act(IReq::get('desc','post')),  
        ); 
          
        //处理上传图片
        if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != '')
        {
            $uploadObj = new PhotoUpload();
            $photoInfo = $uploadObj->run();
            $dataArray['photo'] = $photoInfo['photo']['img'];
        }
        elseif(!$id)
        {
             die('请上传头像');
        }                               
        $designerDB->setData($dataArray);
        if($id)
        {
            $designerDB->update($where);
        }
        else
        {
            $designerDB->add();
        }
        $this->redirect('designer_list');
    }
    
    public function designer_del()
    {
        $id = IFilter::act(IReq::get('id'),'int');

        if($id)
        {
            $idString = is_array($id) ? join(',',$id) : $id;
            $designerObj  = new IModel('designer');
            $designerObj->del("id in ( {$idString} ) and user_id = ".$this->company['user_id']);
            $this->redirect('designer_list');
        }
        else
        {
            $this->redirect('designer_list',false);
            Util::showMessage('请选择要删除的设计师');
        }
    } 
}