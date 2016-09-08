<?php
/**
 * @copyright Copyright(c) 2011 aircheng.com
 * @file pay_balance.php
 * @brief 账户余额支付接口
 * @author chendeshan
 * @date 2011-01-27
 * @version 0.6
 * @note
 */

 /**
 * @class balance
 * @brief 账户余额支付接口
 */
class balance extends paymentPlugin
{
	//插件名称
    public $name = '账户余额支付';

	/**
	 * @see paymentplugin::getSubmitUrl()
	 */
    public function getSubmitUrl()
    {
    	return IUrl::getHost() . IUrl::creatUrl('/ucenter/payment_balance');
    }

	/**
	 * @see paymentplugin::getSendData()
	 */
    public function getSendData($payment)
    {
    	$partnerId  = $payment['M_PartnerId'];
    	$partnerKey = $payment['M_PartnerKey'];

		$return['attach']     = $payment['M_BatchOrderNO'];
		$return['total_fee']  = $payment['M_Amount'];
		$return['order_no']   = $payment['M_OrderNO'];
		$return['return_url'] = $this->callbackUrl;

		$urlStr = '';

		ksort($return);
		foreach($return as $key => $val)
		{
			$urlStr .= $key.'='.urlencode($val).'&';
		}

		$encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
		$urlStr .= $partnerKey . $encryptKey;
		$return['sign'] = md5($urlStr);

        return $return;
    }

	/**
	 * @see paymentplugin::callback()
	 */
    public function callback($ExternalData,&$paymentId,&$money,&$message,&$orderNo)
    {
        $partnerKey = Payment::getConfigParam($paymentId,'M_PartnerKey');

        if(!$ExternalData['order_no'] || !$ExternalData['total_fee'] || !$ExternalData['sign'])
        {
        	$message = '缺少必要参数';
        	return false;
        }

		ksort($ExternalData);

		$temp = array();
        foreach($ExternalData as $k => $v)
        {
            if($k!='sign')
            {
                $temp[] = $k.'='.urlencode($v);
            }
        }

        $encryptKey = isset(IWeb::$app->config['encryptKey']) ? IWeb::$app->config['encryptKey'] : 'iwebshop';
        $testStr = join('&',$temp).'&'.$partnerKey.$encryptKey;

        $orderNo = $ExternalData['order_no'];
        $money   = $ExternalData['total_fee'];

        if($ExternalData['sign'] == md5($testStr))
        {
            //支付单号
            switch($ExternalData['is_success'])
            {
                case 'T':
                {
                	$this->recordTradeNo($orderNo,$orderNo);
                	return true;
                }
                break;

                case 'F':
                {
                	return false;
                }
                break;
            }
        }
        else
        {
        	$message = '校验码不正确';
        }
        return false;
    }

	/**
	 * @see paymentplugin::serverCallback()
	 */
    public function serverCallback($ExternalData,&$paymentId,&$money,&$message,&$orderNo)
    {
    	return $this->callback($ExternalData,$paymentId,$money,$message,$orderNo);
    }

	/**
	 * @see paymentplugin::notifyStop()
	 */
    public function notifyStop()
    {
    	echo "success";
    }
}