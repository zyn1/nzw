<?php
/**
 * @copyright (c) 2011 aircheng.com
 * @file notice.php
 * @brief 用户中心api方法
 * @author chendeshan
 * @date 2014/10/12 13:59:44
 * @version 2.7
 */
class APIUcenter
{

	///用户中心-账户余额
	public function getUcenterAccoutLog($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('account_log');
		$query->where="user_id = ".$userid;
		$query->order = 'id desc';
		$query->page  = $page;
		return $query;
	}
	//用户中心-我的建议
	public function getUcenterSuggestion($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('suggestion');
		$query->where="user_id = ".$userid;
		$query->page  = $page;
		$query->order = 'id desc';
		return $query;
	}
	//用户中心-商品讨论
	public function getUcenterConsult($userid)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('refer as r');
		$query->join   = "join goods as go on r.goods_id = go.id ";
		$query->where  = "r.user_id =". $userid;
		$query->fields = "time,name,question,status,answer,admin_id,go.id as gid,reply_time";
		$query->page   = $page;
		$query->order = 'r.id desc';
		return $query;
	}
	//用户中心-商品评价
	public function getUcenterEvaluation($userid,$status = '')
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('comment as c');
		$query->join   = "left join goods as go on c.goods_id = go.id ";
		$query->where  = ($status === '') ? "c.user_id = ".$userid :  "c.user_id = ".$userid." and c.status ".$status;
		$query->fields = "go.name,c.*";
		$query->page   = $page;
		$query->order = 'c.id desc';
		return $query;
	}

	//用户中心-用户信息
	public function getMemberInfo($userid,$_type){
        $info = array();
        if($_type == 1 || $_type == 4)
        {
            $tb_member = new IModel('member as m,user as u');
            $info = $tb_member->getObj("m.user_id = u.id and m.user_id=".$userid);
            $info['group_name'] = "";
            if($info['group_id'])
            {
                $userGroup = new IModel('user_group');
                $groupRow  = $userGroup->getObj('id = '.$info['group_id']);
                $info['group_name'] = $groupRow ? $groupRow['group_name'] : "";
            }
        } 
        else if($_type == 2)
        {
            $tb_company = new IModel('company as c,user as u');
            $info = $tb_company->getObj("c.user_id = u.id and c.user_id=".$userid);
        }
		return $info;
	}
	//用户中心-个人主页统计
	public function getMemberTongJi($userid,$_type){
		$result = array();  
        $query = new IQuery('order');
        $query->fields = "count(id) as num";
        $query->where  = "user_id = ".$userid." and if_del = 0";
        $info = $query->find();
        $result['num'] = $info[0]['num'];

        $query->fields = "sum(order_amount) as amount";
        $query->where  = "user_id = ".$userid." and status = 5 and if_del = 0";
        $info = $query->find();
        $result['amount'] = $info[0]['amount'] ? $info[0]['amount'] : '0.00';        

		return $result;
	}
	//用户中心-代金券统计
	public function getPropTongJi($propIds){
		$query = new IQuery('prop');
		$query->fields = "count(id) as prop_num";
		$query->where  = "id in (".$propIds.") and type = 0";
		$info = $query->find();
		return $info[0];
	}
	//用户中心-积分列表
	public function getUcenterPointLog($userid,$c_datetime)
	{
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('point_log');
		$query->where  = "user_id = ".$userid." and ".$c_datetime;
		$query->page   = $page;
		$query->order= "id desc";
		return $query;
	}
	//用户中心-信息列表
	public function getUcenterMessageList($msgIds){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('message');
		$query->where= "id in(".$msgIds.")";
		$query->order= "id desc";
		$query->page = $page;
		return $query;
	}
	//用户中心-订单列表
	public function getOrderList($userid,$status){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('order as o');
        $where = "o.user_id =".$userid." and o.if_del= 0";
        if($status == 1)
        {
            $where .= ' and o.status = 1 and o.pay_type != 0';
        }
        elseif($status == 2)
        {
            $where .= ' and ((o.status = 1 and o.pay_type = 0 and o.distribution_status = 0) or (o.status = 2 and o.distribution_status = 0))';
        }
        elseif($status == 3)
        {
            $where .= ' and ((o.status = 1 and o.pay_type = 0 and o.distribution_status = 1) or (o.status = 2 and o.distribution_status = 1))';
        }
        elseif($status ==4)
        {
            $where .= ' and o.status = 5 and o.refunds_status != 1 and o.refunds_status != 2 and o.refunds_status != 3 and o.refunds_status != 4 and o.refunds_status != 5 and o.refunds_status != 6';
            $query->fields="o.*";
        }
		$query->where = $where;
		$query->order = "o.id desc";
		$query->page  = $page;
		return $query;
	}
	//用户中心-我的代金券
	public function getPropList($ids){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('prop');
		$query->where  = "id in(".$ids.") and is_send = 1";
		$query->page   = $page;
		return $query;
	}
	//用户中心-退款记录
	public function getRefundmentDocList($userid,$type){
		$page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
		$query = new IQuery('refundment_doc');
		$query->where = "user_id = ".$userid." and type = ".$type;
		$query->order = "id desc";
		$query->page  = $page;
		return $query;
	}
    //用户中心-提现记录
    public function getWithdrawList($userid){
        $page = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $query = new IQuery('withdraw');
        $query->where = "user_id = ".$userid." and is_del = 0";
        $query->order = "id desc";
        $query->page  = $page;
        return $query;
    }

	//用户中心-提现详情
	public function getWithdrawRow($id){
		$query = new IModel('withdraw');
		$row = $query->getObj("id = ".$id);
		return $row;
	}

    //[收藏夹]获取收藏夹数据
    public function getFavorite($userid,$cat = '')
    {
        //获取收藏夹信息
        $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;
        $cat_id = IFilter::act($cat,'int');

        $favoriteObj = new IQuery("favorite as f");
        $favoriteObj->join  = "left join goods as go on go.id = f.rid";
        $favoriteObj->fields= " f.*,go.name,go.id as goods_id,go.img,go.store_nums,go.sell_price,go.market_price,go.seller_id";

        $where = 'user_id = '.$userid;
        $where.= $cat_id ? ' and cat_id = '.$cat_id : "";

        $favoriteObj->where = $where;
        $favoriteObj->page  = $page;
        return $favoriteObj;
    }

    //[我的足迹]获取浏览记录数据
	public function getHistory($userid,$limit = 0)
    {
		//获取收藏夹信息
	    $page   = IReq::get('page') ? IFilter::act(IReq::get('page'),'int') : 1;

		$historyObj = new IQuery("user_history as h");
		$historyObj->join  = "left join goods as go on go.id = h.goods_id";
		$historyObj->fields= " h.*,go.name,go.id as goods_id,go.img,go.store_nums,go.sell_price,go.market_price,go.seller_id";
        $historyObj->order="h.time DESC";
        $historyObj->group="go.id";
        if($limit)
        {
            $historyObj->limit = $limit;
        }

		$where = 'h.user_id = '.$userid;

		$historyObj->where = $where;
		$historyObj->page  = $page;
		return $historyObj;
    }
}