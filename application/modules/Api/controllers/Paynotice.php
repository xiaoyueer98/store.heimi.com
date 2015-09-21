<?php
/**
 * Payresult controller class
 * @author  nick <zhaozhiwei@747.cn>
 * @final 2014-12-09
 */
class PaynoticeController extends Yaf_Controller_Abstract
{
    //异步 http://boxapi.wifigo.cn/api/paynotice/index
	public function indexAction()
	{
        $params     = $_POST;
        $key        = $params['key'];
        $price      = $params['price'];
		$orderId    = $params['orderNumber'];
		$status     = $params['status'];
        if(strtoupper($status) != 'SUCCESS')
        {
            die('Filed Request.');
        }
        //判断该请求是否来源自支付中心
        $sysKey ="aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d";
        $sec = hash('sha256',$orderId.$price.$sysKey.$status);
        if($key == $sec)
        {
            $res = TZ_Loader::service('Pay','Api')->updateOrder($orderId,2);
            if($res)
            {
                die('SUCCESS');
            }
            else
            {
                die('Filed SQL.');
            }
        }
        else
        {
            die('Filed Key.');
        }
    }
}