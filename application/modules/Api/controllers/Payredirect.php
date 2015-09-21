<?php
/**
 * Payresult controller class
 * @author  nick <zhaozhiwei@747.cn>
 * @final 2014-12-09
 */
class PayredirectController extends Yaf_Controller_Abstract
{
	//同步 http://boxapi.wifigo.cn/api/payredirect/index
	public function indexAction()
	{
        $res = $_REQUEST['res'];
        if(strtoupper($res) == 'SUCCESS')
        {
            $this->_view->display('pay_success.html');
        }
		else
        {
            $this->_view->display('pay_failed.html');
        }
	}	
}