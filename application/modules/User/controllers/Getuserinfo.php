<?php
/**
 * account controller file
 * 查询用户相关信息
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-09-23
 */
class GetuserinfoController extends Yaf_Controller_Abstract
{
	/**
	 * Hello World!
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$params = TZ_Request::getParams('get');
		if(isset($params['session_id'])){
			if (empty($params['session_id'])){
				throw new Exception('无会话id.');
			}
			$sessionId = TZ_Request::clean($params['session_id']);
			$uid = TZ_loader::service('SessionManager')->getUid($sessionId);
			if (!$uid)
				throw new Exception('你还没有登录.');
			$userInfo=TZ_Loader::service('CallUser')->getUserInfo(array('friendlyName:eq'=>$uid));
		}elseif (isset($params['telephone'])){
			if (empty($params['telephone'])){
					throw new Exception('请输入手机号码.');
				}
			$userInfo=TZ_Loader::service('CallUser')->getUserInfo(array('mobile:eq'=>$params['telephone']));	
		}else{
			throw new Exception('请输入必要参数.');
		} 

		TZ_Request::success(array($userInfo));
	}
}