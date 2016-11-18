<?php
/**
 * @class interface_native
 * @brief 扫一扫微信支付
 * @date 2016-11-15 11:16:57
 */
require_once dirname(__FILE__)."/class/RequestHandler.class.php";
require_once dirname(__FILE__)."/class/ClientResponseHandler.class.php";        
require_once dirname(__FILE__)."/class/PayHttpClient.class.php"; 
class interface_native extends paymentPlugin
{
    //支付插件名称
    public $name = '扫一扫微信支付';
    
    private $resHandler = null;
    private $reqHandler = null;
    private $pay = null;

    /**
     * @see paymentplugin::getSubmitUrl()
     */
    public function getSubmitUrl()
    {
        return 'https://pay.swiftpass.cn/pay/gateway';
    }

    /**
     * @see paymentplugin::notifyStop()
     */
    public function notifyStop()
    {
        die("success");
    }

    /**
     * @see paymentplugin::callback()
     */
    public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo){}

    /**
     * @see paymentplugin::serverCallback()
     */
    public function serverCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
    {
        $this->resHandler = new ClientResponseHandler();
        $xml = file_get_contents('php://input');
        //$res = self::parseXML($xml);
        $this->resHandler->setContent($xml);
        //var_dump($this->resHandler->setContent($xml));
        $this->resHandler->setKey(Payment::getConfigParam($paymentId,'key'));
        if($this->resHandler->isTenpaySign()){
            if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
           
                //更改订单状态
                $orderNo = $this->resHandler->getParameter('out_trade_no');
                $money   = $this->resHandler->getParameter('total_fee')/100;
                //记录回执流水号
                if($this->resHandler->getParameter('transaction_id'))
                {
                    $this->recordTradeNo($orderNo,$this->resHandler->getParameter('transaction_id'));
                }
                
                self::dataRecodes('接口回调收到通知参数',$this->resHandler->getAllParameters());
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * @see paymentplugin::getSendData()
     */
    public function getSendData($payment)
    {
        $return = array();
        //基本参数
        $return['mch_id']           = $payment['mch_id'];
        $return['key']              = $payment['key'];
        $return['body']             = '微信支付';
        $return['out_trade_no']     = $payment['M_OrderNO'];
        $return['total_fee']        = $payment['M_Amount']*100;
        $return['spbill_create_ip'] = IClient::getIp();
        $return['notify_url']       = $this->serverCallbackUrl;

        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($return);
        return $para_filter;
    }

    /**
     * @see paymentplugin::doPay()
     */
    public function doPay($sendData)
    {
        $this->reqHandler = new RequestHandler();
        $this->pay = new PayHttpClient();
        $this->reqHandler->setGateUrl('https://pay.swiftpass.cn/pay/gateway');
        $this->reqHandler->setKey($sendData['key']);
        $this->reqHandler->setParameter('out_trade_no',$sendData['out_trade_no']);
        $this->reqHandler->setParameter('body',$sendData['body']);
        $this->reqHandler->setParameter('attach','');
        $this->reqHandler->setParameter('total_fee',$sendData['total_fee']);
        $this->reqHandler->setParameter('mch_create_ip',isset($sendData['spbill_create_ip']) ? $sendData['spbill_create_ip'] : '127.0.0.1');
        $this->reqHandler->setParameter('time_start',ITime::getNow('YmdHis'));
        $this->reqHandler->setParameter('time_expire',date('YmdHis', time()+600));
        $this->reqHandler->setParameter('service','pay.weixin.native');//接口类型：pay.weixin.native
        $this->reqHandler->setParameter('mch_id',$sendData['mch_id']);//必填项，商户号，由威富通分配
        $this->reqHandler->setParameter('version','2.0');
        $this->reqHandler->setParameter('notify_url',$sendData['notify_url']);//通知回调地址，目前默认是空格，商户在测试支付和上线时必须改为自己的，且保证外网能访问到
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $this->reqHandler->createSign();//创建签名
        
        $data = self::toXml($this->reqHandler->getAllParameters());
        //var_dump($data);
        
        $this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
        if($this->pay->call()){
            $this->resHandler = new ClientResponseHandler();
            $this->resHandler->setContent($this->pay->getResContent());
            $this->resHandler->setKey($this->reqHandler->getKey());
            if($this->resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
                    $return = array('code_img_url'=>$this->resHandler->getParameter('code_img_url'),'code_url'=>$this->resHandler->getParameter('code_url'),'code_status'=>$this->resHandler->getParameter('code_status'));
                    //在线充值
                    if(stripos($sendData['out_trade_no'],'recharge') !== false)
                    {
                        $return['url'] = IUrl::getHost().IUrl::creatUrl('/ucenter/account_log');
                    }
                    
                    //开店服务费
                    elseif(stripos($sendData['out_trade_no'],'service') !== false)
                    {
                        $return['url'] = IUrl::getHost().IUrl::creatUrl('/site/index');
                    }
                    else
                    {
                        $return['url'] = IUrl::getHost().IUrl::creatUrl('/ucenter/order');
                    }
                    
                    include(dirname(__FILE__).'/template/pay.php');
                }else{
                    /*echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('err_code').' Error Message:'.$this->resHandler->getParameter('err_msg')));*/
                    die($this->resHandler->getParameter('err_msg'));
                }
            }
            else
            {
                /*echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message')));*/
                die($this->resHandler->getParameter('message'));
            }
        }else{
            /*echo json_encode(array('status'=>500,'msg'=>'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo()));*/
            die($this->pay->getErrInfo());
        }
    }

    /**
     * @param 获取配置参数
     */
    public function configParam()
    {
        $result = array(
            'mch_id'    => '商户号',
            'key'       => '商户支付密钥',
        );
        return $result;
    }
    
    
    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para)
    {
        $para_filter = array();
        foreach($para as $key => $val)
        {
            if($key == "sign" || $key == "sign_type" || $val == "")
            {
                continue;
            }
            else
            {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }
    
     /**
     * 将数据转为XML
     */
    public static function toXml($array){
        $xml = '<xml>';
        forEach($array as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        return $xml;
    }
    
    public static function dataRecodes($title,$data){
        $handler = fopen('result.txt','a+');
        $content = "================".$title."===================\n";
        if(is_string($data) === true){
            $content .= $data."\n";
        }
        if(is_array($data) === true){
            forEach($data as $k=>$v){
                $content .= "key: ".$k." value: ".$v."\n";
            }
        }
        $flag = fwrite($handler,$content);
        fclose($handler);
        return $flag;
    }

    public static function parseXML($xmlSrc){
        if(empty($xmlSrc)){
            return false;
        }
        $array = array();
        $xml = simplexml_load_string($xmlSrc);
        $encode = self::getXmlEncode($xmlSrc);

        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);
                    
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                
                if($encode!="" && $encode != "UTF-8") {
                    $k = iconv("UTF-8", $encode, $k);
                    $v = iconv("UTF-8", $encode, $v);
                }
                $array[$k] = $v;
            }
        }
        return $array;
    }

    //获取xml编码
    function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }
    
    /**
     * 退款
     */
    public function refund($sendData){
        $this->reqHandler = new RequestHandler();
        $this->pay = new PayHttpClient();
        //$this->reqHandler->setReqParams($_POST,array('method'));
        $this->reqHandler->setGateUrl('https://pay.swiftpass.cn/pay/gateway');
        $this->reqHandler->setKey($sendData['key']);
        $this->reqHandler->setParameter('out_trade_no',$sendData['M_Order_NO']);
        $this->reqHandler->setParameter('transaction_id',$sendData['M_Trade_NO']);
        $this->reqHandler->setParameter('out_refund_no',$sendData['M_OrderNO']);
        $this->reqHandler->setParameter('refund_fee',$sendData['M_Amount']*100);
        $this->reqHandler->setParameter('total_fee',$sendData['M_total']*100);
        $reqParam = $this->reqHandler->getAllParameters();
        if(empty($reqParam['transaction_id']) && empty($reqParam['out_trade_no'])){
            return array('status'=>500,'msg'=>'参数错误！');
        }
        $this->reqHandler->setParameter('version','2.0');
        $this->reqHandler->setParameter('service','trade.single.refund');//接口类型：trade.single.refund
        $this->reqHandler->setParameter('mch_id',$sendData['mch_id']);//必填项，商户号，由威富通分配
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $this->reqHandler->setParameter('op_user_id',$sendData['mch_id']);//必填项，操作员帐号,默认为商户号

        $this->reqHandler->createSign();//创建签名
        $data = Utils::toXml($this->reqHandler->getAllParameters());//将提交参数转为xml，目前接口参数也只支持XML方式

        $this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
        if($this->pay->call()){
            $this->resHandler->setContent($this->pay->getResContent());
            $this->resHandler->setKey($this->reqHandler->getKey());
            if($this->resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
                    $res = $this->resHandler->getAllParameters();
                    Utils::dataRecodes('提交退款',$res);
                    return true;
                }else{
                    return array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('err_code').' Error Message:'.$this->resHandler->getParameter('err_msg'));
                }
            }
            return array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message'));
        }else{
            return array('status'=>500,'msg'=>'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo());
        }
    }
}