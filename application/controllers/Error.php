<?php
/**
 * Error controller file
 * 
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */
class ErrorController extends Yaf_Controller_Abstract
{
	//异常捕获
	public function errorAction($exception)
	{	
		$code = 500;
		$detail = $exception->getMessage();
		$error = array(
			'code' => $code, 
			'detail' => $detail,
			'data' => array()
		);
		TZ_Request::send($error);
	}
}
