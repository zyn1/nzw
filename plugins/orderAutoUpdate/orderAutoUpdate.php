<?php
/**
 * @copyright Copyright(c) 2016 aircheng.com
 * @file orderAutoUpdate.php
 * @brief 订单自动更新
 * @notice 未付款取消，发货后自动确认收货
 * @author nswe
 * @date 2016/2/28 10:57:26
 * @version 4.4
 */
class orderAutoUpdate extends pluginBase
{
	//注册事件
	public function reg()
	{
        plugin::reg("onCreateAction@order@order_list",$this,"orderUpdate");
        plugin::reg("onCreateAction@ucenter@order",$this,"orderUpdate");
        plugin::reg("onCreateAction@ucenter@refunds",$this,"refundsUpdate");
        plugin::reg("onCreateAction@ucenter@changeRefunds",$this,"refundsUpdate");
        plugin::reg("onCreateAction@order@refundment_list",$this,"refundsUpdate");
        plugin::reg("onCreateAction@order@changeGoods_list",$this,"refundsUpdate");
	}

    //订单自动更新
    public function orderUpdate()
    {
        //获取配置信息
        $configData = $this->config();

        //按照分钟计算
        $order_finish_time = (isset($configData['order_finish_time']) && $configData['order_finish_time']) ? intval($configData['order_finish_time']) : 20*24*60;
        $order_agree_time = (isset($configData['order_agree_time']) && $configData['order_agree_time']) ? intval($configData['order_agree_time']) : 3*24*60;

        $orderModel = new IModel('order');
        $orderCreateData  = $order_finish_time > 0 ? $orderModel->query(" if_del = 0 and distribution_status = 1 and status in(1,2) and timestampdiff(minute,send_time,NOW()) >= {$order_finish_time} ","id,order_no") : array();

        if($orderCreateData)
        {
            $refundmentDB = new IModel('refundment_doc');
            foreach($orderCreateData as $key => $val)
            {
                $order_id = $val['id'];
                $order_no = $val['order_no'];
                if(!$refundmentDB->getObj('order_id = '.$order_id.' and if_del = 0 and pay_status in (0,3,4)', 'id'))
                {

                    //oerder表的对象
                    $tb_order = new IModel('order');
                    $tb_order->setData(array(
                        'status'          => $type,
                        'completion_time' => ITime::getDateTime(),
                    ));
                    $tb_order->update('id='.$order_id);

                    //生成订单日志
                    $tb_order_log = new IModel('order_log');

                    //订单自动完成
                    $action = '完成';
                    $note   = '订单【'.$order_no.'】完成成功';

                    //完成订单并且进行支付
                    Order_Class::updateOrderStatus($order_no);

                    //增加用户评论商品机会
                    Order_Class::addGoodsCommentChange($order_id);

                    $logObj = new log('db');
                    $logObj->write('operation',array("系统自动","订单更新为完成",'订单号：'.$order_no));

                    $tb_order_log->setData(array(
                        'order_id' => $order_id,
                        'user'     => "系统自动",
                        'action'   => $action,
                        'result'   => '成功',
                        'note'     => $note,
                        'addtime'  => ITime::getDateTime(),
                    ));
                    $tb_order_log->add();
                }
                else
                {
                    continue;
                }
            }
        }
        
        if($order_agree_time > 0)
        {
            $this->refundsUpdate();   
        }
    }
    
    //申请退换货自动完成
     public function refundsUpdate()
     {
         //获取配置信息
        $configData = $this->config();

        //按照分钟计算
        $order_agree_time = (isset($configData['order_agree_time']) && $configData['order_agree_time']) ? intval($configData['order_agree_time']) : 3*24*60;
        
        $refundmentDB = new IModel('refundment_doc');
        $refundsList = $order_agree_time > 0 ? $refundmentDB->query("if_del = 0 and pay_status = 0 and timestampdiff(minute,time,NOW()) >= {$order_agree_time}", 'id,order_id,type,amount,order_goods_id,seller_id') : array();
        if($refundsList)
        {
            $orderGoodsDB = new IModel('order_goods');
            $orderDB = new IModel('order');
            $sellerDB = new IModel('seller');
            foreach($refundsList as $k => $v)
            {
                //退货时判断是否已结算
                if($v['type'] == 1 && $v['seller_id'] <> 0)
                {
                    $orderRow = $orderDB->getObj("id = {$v['order_id']}", 'is_checkout');
                }
                if(isset($orderRow) && $orderRow['is_checkout'] == 1)
                {
                    continue;
                }
                $tips = '';
                $sellerRow = $sellerDB->getObj('id = '.$v['seller_id'], 'address,mobile');
                if($orderGoodsDB->getObj("id in ({$v['order_goods_id']}) and is_send = 1", 'order_id'))
                {
                    $pay_status = 3;
                    $tips = '请您尽快退回商品并填写快递单号等相关信息，拒收到付件，平邮件，谢谢合作！<br/>退货地址为：'.$sellerRow['address'].',电话：'.$sellerRow['mobile'];
                }
                else
                {
                    $pay_status = 2;
                    $tips = '同意';
                }
                $updateData = array(
                    'dispose_time' => ITime::getDateTime(),
                    'dispose_idea' => $tips,
                    'pay_status'   => $pay_status,
                    'amount'       => $v['amount'],
                );
                $refundmentDB->setData($updateData);
                $res = $refundmentDB->update('id = '.$v['id']);
                if($res)
                {
                    if($pay_status == 2)
                    {
                        if($v['type'] == 1)
                        {
                            $result = Order_Class::refund($v['id'],$v['seller_id'],'seller');
                            var_dump($result);
                            if(is_string($result))
                            {
                                $refundmentDB->rollback();
                            }
                        }
                        else
                        {
                            $result = Order_Class::changeGoods($v['order_id'],$v['id']);
                            if(is_string($result))
                            {
                                $refundmentDB->rollback();
                            }
                        }
                    }
                    
                    if($v['type'] == 1)
                    {
                        $msg = '退货';
                    }
                    else
                    {
                        $msg = '换货';
                    }
                    $logObj = new log('db');
                    $logObj->write('operation',array("系统自动","同意".$msg.'申请',$msg.'申请单号：'.$v['id']));
                }
            }
        }
     }

	/**
	 * @brief 默认插件参数信息，写入到plugin表config_param字段
	 * @return array
	 */
	public static function configName()
	{
		return array(
			"order_finish_time" => array("name" => "已发货订单 X(分钟)自动完成","type" => "text","pattern" => "int"),
            //"order_cancel_time" => array("name" => "未付款订单 X(分钟)自动取消","type" => "text","pattern" => "int"),
			"order_agree_time" => array("name" => "申请退换货的订单 X(分钟)自动同意","type" => "text","pattern" => "int"),
		);
	}

	/**
	 * @brief 插件名字
	 * @return string
	 */
	public static function name()
	{
		return "订单自动完成和退换货自动处理";
	}

	/**
	 * @brief 插件描述
	 * @return string
	 */
	public static function description()
	{
		return "订单自动完成和退换货自动处理。1，已经发货的订单会在X分钟后自动完成；2，申请退换货的订单会在X分钟后自动同意";
	}
}