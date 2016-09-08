<?php
/**
 * @brief 升级更新控制器
 */
class Update extends IController
{
	/**
	 * @brief iwebshop16060600 版本升级更新
	 */
	public function index()
	{
		set_time_limit(0);
		$sql = array(
			"UPDATE `{pre}oauth` SET file = 'wechatOauth' where file = 'wechat';",
			"ALTER TABLE `{pre}ad_position` CHANGE `width` `width` VARCHAR( 255 ) NOT NULL COMMENT '广告位宽度';",
			"ALTER TABLE `{pre}ad_position` CHANGE `height` `height` VARCHAR( 255 ) NOT NULL COMMENT '广告位高度';",
			"ALTER TABLE `{pre}refundment_doc` ADD `way` VARCHAR( 20 ) NOT NULL DEFAULT  '' COMMENT '退款方式';",
		);

		foreach($sql as $key => $val)
		{
			IDBFactory::getDB()->query( $this->_c($val) );
		}

		die("升级成功!! V4.5版本");
	}

	public function _c($sql)
	{
		return str_replace('{pre}',IWeb::$app->config['DB']['tablePre'],$sql);
	}
}