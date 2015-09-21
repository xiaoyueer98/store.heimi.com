<?php
/**
 * @author 刑天 <wangtongmeng@747.cn>
 * @final 2014-12-5
 */
class SmsController extends Yaf_Controller_Abstract 
{
    
	/**
	 * 获得用户订单信息
	 *
	 * @param  $session_id 用户session
	 *
	 * @Return array 
	 */
	public function indexAction()
	{
		$params = array();
		$params = array('day'=>date('Y-m-d H:i:s'),'company'=>'黑米世纪');
		//$params = array('code'=>1222);
		$resutl = TZ_Loader::service('Sendmessage','Sms')->send('2','15201655587',$params);
		var_dump($resutl);
	}

}
