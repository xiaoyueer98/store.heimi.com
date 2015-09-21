<?php
/**
 * request class file
 *
 * @author octopus <zhangguipo@747.cn>
 * @final 2014-10-20
 */


class TZ_Request
{
	//验证手机号是否合法
	static public function checkTelephone()
	{
		$params = self::getParams('post');
		if (empty($params['telephone']))
			self::error('手机号码不能为空。');
		if (!preg_match("#^1[3-8][0-9]{9}$#", $params['telephone']))
			self::error('手机号码错误。');	
		return $params['telephone'];
	}
        
        static public function checkUid()
        {
            $params = self::getParams('post');
            if(empty($params['uid']))
                self::error ('uid不能为空。');
            return $params['uid'];
        }


        //验证reset是否合法
	static public function checkVerifyMode()
	{
		$params = self::getParams('post');
		if (empty($params['reset']))
			self::error('reset not found.');
		if (!in_array($params['reset'], array('1', '2')))
			self::error('验证码类型错误。');
		return $params['reset'];
	}

	//check verify code
	static public function checkVerifyCode()
	{
		$params = self::getParams('post');
		if (empty($params['verify_code']))
			self::error('verify_code not found.');
		if (!preg_match('#^[0-9]{4}$#', $params['verify_code']))
			self::error('验证码错误。');	
		return $params['verify_code'];
	}

	//check name
	static public function checkName()
	{
		$params = self::getParams('post');
		if (empty($params['name']))
			self::error('名称不能为空。');
		return self::clean($params['name']);
	}
	
	//check nickname
	static public function checkNickname()
	{
		$params = self::getParams('post');
		if (empty($params['nickname']))
			self::error('昵称不能为空。');
		return self::clean($params['nickname']);
	}
	
	//check gender
	static public function checkGender()
	{
		$params = self::getParams('post');
		if (!isset($params['gender']))
			self::error('gender not found.');
		if (!in_array($params['gender'], array('0', '1')))
			self::error('性别错误。');
		return $params['gender'];
	}

	//check password
	static public function checkPassword()
	{
		$params = self::getParams('post');
		if (empty($params['password']))
			self::error('密码不能为空。');
		return self::clean($params['password']);
	}
	
	//check old_password
	static public function checkOldPassword()
	{
		$params = self::getParams('post');
		if (empty($params['old_password']))
			self::error('原密码不能为空。');
		return self::clean($params['old_password']);
	}
	
	//check session id
	static public function checkSessionId($method = 'post')
	{
		$params = self::getParams($method);
		if (empty($params['session_id']))
			self::error('请先登陆帐号。');
		return self::clean($params['session_id']);
	}
	
	//check beans
	static public function checkBeans($method = 'post')
	{
		$params = self::getParams($method);
		if (empty($params['beans']) 
		    || !is_numeric($params['beans'])
		    || $params['beans'] <= 0)
			self::error('银豆错误。');
		return self::clean($params['beans']);
	}

	//验证金豆
	static public function checkCardType($method = 'post')
	{
		$params = self::getParams($method);
		if (empty($params['card_type'])||!is_numeric($params['card_type']))
			self::error('卡类型错误.');
		return self::clean($params['card_type']);
	}

	//验证url是否合法
	static public function checkUrl($url)
	{
		return preg_match("#^http:\/\/#i", $url);
	}
	
	//获取参数
	static public function getParams($method = 'get')
	{
		switch ($method) {
			case 'get':
				return $_GET;

			case 'post':
				return !empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), 1);

			default:
				return false;
		}
	}

	//成功
	static public function success($data = array(),$other=array())
	{

		$response = array(
			'code'	=>	200,
			'detail'=>	'',
			'data'  =>	$data
		);
        if($other)
        {
            foreach($other AS $key => $val)
            {
                $response[$key] = $val;
            }
        }
//		try{
//			//写日志
//			self::addQueue($data,200,'');
//		}catch (Exception $e){
//		}
		/*if (Yaf_Registry::get('config')->log->success)
			TZ_Log::set(200, '');*/
		self::send($response);
	}

	//失败
	static public function error($detail, $code = 404)
	{
		$response = array(
			'code'	 => $code,
			'detail' =>	$detail,
			'data'	 => array()
		);
//		try{
//			//写日志
//			self::addQueue('',$code,$detail);
//		}catch (Exception $e){
//		}
		/*if (Yaf_Registry::get('config')->log->error)
			TZ_Log::set($code, $detail);*/
		self::send($response);
	}
	
	//获取客户段ip
	static function getRemoteIp()
	{
		if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR') 
			&& strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$ip = getenv('REMOTE_ADDR');
		} elseif (isset($_SERVER['REMOTE_ADDR']) 
			&& $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = 'unknown';
		}
		return $ip;
	}
	
	//filter
	static public function clean($str)
	{
		return addslashes(self::_xssClean($str));
	}

	//send
	static public function send($response)
	{
		$jsonData = json_encode($response);
		self::_xhprofDisable();
		die($jsonData);
	}
	
	//去掉js和html
	static private function _xssClean($str)
	{
		$str = trim($str);
		if (strlen($str) <= 0)
			return $str;
		return @preg_replace_callback(self::$_search, self::$_replace, $str);
	}
	
	static private 	$_search = array(
		"'<script[^>]*?>.*?</script>'si",  			// 去掉 javascript
		"'<[\/\!]*?[^<>]*?>'si",          			// 去掉 HTML 标记
		"'([\r\n])[\s]+'",                			// 去掉空白字符
		"'&(quot|#34);'i",                			// 替换 HTML 实体
		"'&(amp|#38);'i",
		"'&(lt|#60);'i",
		"'&(gt|#62);'i",
		"'&(nbsp|#160);'i"
	); 
	static private $_replace = array(				// 作为 PHP 代码运行
		'',
		'',
		"\\1",
		"\"",
		"&",
		"<",
		">",
		''
	);
	
	//xhprof disable
	static public function _xhprofDisable()
	{
		if (!empty($_GET['debug'])) {
			if (!extension_loaded('xhprof'))
				return false;
			$xhprof_data = xhprof_disable();
			$XHPROF_ROOT = APP_PATH.'/library/Xhprof';
			include_once $XHPROF_ROOT . "/utils/xhprof_lib.php";
			include_once $XHPROF_ROOT . "/utils/xhprof_runs.php";
			
			$xhprof_runs = new XHProfRuns_Default();
			
			$source = $_GET['debug'];
			$run_id = $xhprof_runs->save_run($xhprof_data, $source);
			$url = "/xhprof/xhprof_html/index.php?run=$run_id&source={$source}";
		
			TZ_Redis::connect('user')->setex("debug:xhprof:{$source}", 3600, $url);
		}
	}
	//验证推荐人是否合法
	static public function checkReferTelephone()
	{
		$params = self::getParams('post');
		if (empty($params['refertelephone']))
			return false;
		if (!preg_match("#^1[3-8][0-9]{9}$#", $params['refertelephone']))
			return false;	
		return $params['refertelephone'];
	}
	static  function addQueue($data,$code,$details)
	{
		if(empty($_GET)){
			$args=$_POST;
		}else{
			$args=$_GET;
		}
		//得到actionID
		if(empty($_SERVER['REQUEST_URI'])){
			$action='';
		}else{
			$action=$_SERVER['REQUEST_URI'];
		}
		//得到sessionID
		if(empty($args['session_id'])){
			$sessionID='';
			$uniqueUserID=-1;
		}else{
			$sessionID=$args['session_id'];
			$uniqueUserID=TZ_Loader::service('SessionManager', 'User')->getUid($sessionID);
		}
		$rabbitMq = new Rabbit();
        $rabbitMq->addCallLog($action,$uniqueUserID,$sessionID,$code,$details,$args,$data);
	}
	
}
