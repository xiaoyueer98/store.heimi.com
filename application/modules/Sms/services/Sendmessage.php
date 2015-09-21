<?php
/**
 * sendmessage service file
 *
 * @author  刑天<wangtongmeng@747.cn>
 * @final 2015-01-09
 */
class SendmessageService
{
	/*
	 * 与短信模板对应关系
	 * 1	发送验证码
	 * 2	发送test消息
	 */
	private $arrTemplate = array(
		'1' => array('uuid' => '1000','id' => '10'),
		'2' => array('uuid' => '1001','id' => '11'),
		'3' => array('uuid' => '1006','id' => '16')
	);

	/*
	 * 发送短信
	 * tId		短信模板对应关系ID
	 * tel		要发送的手机号
	 * params	要发送的内容
	 * type		发送方式，get或post
	 * delimiter域名和参数之间的分割方式，兼容不同的框架路由形式
	 * needcharset	数据需要转换成的编码
	 * charset		原数据编码
	 *
	 * return max
	 */
    public function send($tId, $tel, $params, $type='post', $delimiter='?', $charset='utf-8', $needcharset='utf-8')
    {
		$config  = Yaf_Registry::get('config');
		$smsHost = $config->sms->host; 
		if(array_key_exists($tId,$this->arrTemplate))
		{	
			$params['phone']	= $tel;
			$params['uuid']		= $this->arrTemplate[$tId]['uuid'];
			$params['id']		= $this->arrTemplate[$tId]['id'];
			$result = TZ_Loader::service('CurlTool','Sms')->sendcurl($smsHost,$type,$params,$charset,$needcharset,$delimiter);
			return $result;
		}else
		{
			$error = array('result' => 'Not find template ID', 'msg' => 'fail');
			return json_encode($error);
		}
    }
}
