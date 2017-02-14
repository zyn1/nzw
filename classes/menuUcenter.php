<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file menuUcenter.php
 * @brief 用户中心菜单管理
 * @author nswe
 * @date 2016/3/8 9:33:25
 * @version 4.4
 */
class menuUcenter
{
    //菜单的配制数据
	public static $menu = array(
		"账户资金" => array(
			"/ucenter/account_log" => "帐户余额",
            "/ucenter/online_recharge" => "在线充值",
			"/ucenter/payPass_edit" => "管理支付密码",
		),

		"交易记录" => array(
			"/ucenter/order" => "我的订单",
			// "/ucenter/integral" => "我的积分",
			// "/ucenter/redpacket" => "我的代金券",
		),

		"个人设置" => array(
			"/ucenter/address" => "收货地址管理",
			"/ucenter/info" => "完善个人资料",
			"/ucenter/password" => "修改登录密码",
			"/ucenter/changePhone" => "修改绑定手机",
			"/ucenter/changeEmail" => "修改绑定邮箱",
		),

		"服务中心" => array(
            "/ucenter/refunds" => "退款申请",
			"/ucenter/changeRefunds" => "换货申请",
			// "/ucenter/complain" => "站点建议",
			/*"/ucenter/consult" => "商品咨询",*/
			"/ucenter/evaluation" => "商品评价",
		),

		"应用中心" => array(
			"/ucenter/message" => "我的消息",
			"/ucenter/favorite" => "我的收藏",
			"/ucenter/history" => "我的足迹",
		),

	);

    /**
     * @brief 根据权限初始化菜单
     * @param int $roleId 角色ID
     * @return array 菜单数组
     */
    public static function init($userId = "")
    {
		//菜单创建事件触发
		plugin::trigger("onUcenterMenuCreate");
        if($userId)
        {
            $user  = new IModel("user");
            $_user   = $user->getObj("id='{$userId}'", 'mobile,email,type');
            if(empty($_user['mobile'])){
                unset(self::$menu['个人设置']['/ucenter/changePhone']);
            }
            if(empty($_user['email'])){
                unset(self::$menu['个人设置']['/ucenter/changeEmail']);
            }
            if($_user['type'] != 1)
            {                                                       
                unset(self::$menu['账户资金']['/ucenter/online_recharge']);
            }
        }
		return self::$menu;
    }
}