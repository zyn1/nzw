<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file menuCompany.php
 * @brief 装修公司系统菜单管理
 * @author zyn
 * @date 2017-1-17 13:34:13
 * @version 1.0
 */
class menuCompany
{
    //菜单的配制数据
	public static $menu = array
	(
		"统计结算模块" => array(
			"/company/index" => "管理首页",
			/*"/company/account" => "销售额统计",
			"/company/order_goods_list" => "货款明细列表",
            "/company/bill_list" => "货款结算申请",
            "/company/bill_fapiao_list" => "申请发票列表",
			"/company/bill_fapiao" => "申请发票",*/
		),

		"装修案例模块" => array(
			"/company/case_list" => "装修案例列表",
			"/company/case_edit" => "添加装修案例", 
		),

		"设计师模块" => array(
            "company/designer_list" => "设计师列表",
            "company/designer_edit" => "添加设计师"
		), 

		"配置模块" => array(                  
			/*"/company/message_list" => "消息通知", */ 
            "/company/company_edit" => "资料修改",
            "/company/company_desc" => "公司简介"
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
		plugin::trigger("onCompanyMenuCreate");
		return self::$menu;
    }
}