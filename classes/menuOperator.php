<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file menuOperator.php
 * @brief 运营中心菜单管理
 * @author zyn
 * @date 2017-2-7 15:51:23
 * @version 1.0
 */
class menuOperator
{
    //菜单的配制数据
	public static $menu = array
	(
		"统计结算模块" => array(
			"/operator/index" => "管理首页",
			"/operator/account" => "销售额统计",
			"/operator/order_goods_list" => "货款明细列表",
            "/operator/bill_list" => "货款结算申请",
            "/operator/bill_fapiao_list" => "申请发票列表",
			"/operator/bill_fapiao" => "申请发票",
		),

		"商品模块" => array(
			"/operator/goods_list" => "商品列表",
			"/operator/goods_edit" => "添加商品",
			/*"/operator/share_list" => "平台共享商品",
			"/operator/refer_list" => "商品咨询",*/
			"/operator/comment_list" => "商品评价",
            "/operator/refundment_list" => "商品退款",
            "/operator/change_list" => "换货列表",
            "/operator/spec_list" => "规格列表",
			"/operator/category_list" => "品牌分类",
			"/operator/brand_list" => "品牌列表",
		),

		"订单模块" => array(
            "operator/order_list" => "订单列表",
            "operator/fapiao_list/status/1" => "已开发票列表",
			"operator/fapiao_list/status/0" => "未开发票列表",
		),

		/*"营销模块" => array(
			"/operator/regiment_list" => "团购",
			"/operator/pro_rule_list" => "促销活动列表",
		),*/

		"配置模块" => array(
			"/operator/delivery" => "物流配送",
			"/operator/message_list" => "消息通知",
			"/operator/ship_info_list" => "发货地址",
			"/operator/operator_edit" => "资料修改",
		),
	);

    /**
     * @brief 根据权限初始化菜单
     * @param int $roleId 角色ID
     * @return array 菜单数组
     */
    public static function init($roleId = "")
    {
		//菜单创建事件触发
		plugin::trigger("onSellerMenuCreate");
		return self::$menu;
    }
}