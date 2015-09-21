<?php
/**
 * pay service file
 *
 * @author  octopus <zhangguipo@747.cn>
 * @final 2014-10-28
 */
class PayService {

	/*
	 * 调用支付请求
	 * 应用系统在调用第三方支付系统时（HTTP方式），需要提供以下参数
	 *
	 * platform : 支付平台名称（可为空，默认为支付宝快捷支付）
	 * bankCode : (各支付平台银行代码) 通过支付平台直连银行，缺省参数，暂未使用，传递0000即可 
	 * uid :  用户的UID， 如果获取不到用户信息，传递 nouser 即可
	 * orderNumber 应用系统订单号。
	 * subject  产品描述（可以为空,中文请使用urlencode 编码）
	 * price  支付金额 （不可为空，单位/元 [最多保留两位小数点]）
	 * sysCode  由支付系统系统分配的系统编码,不能为空
	 * sysKey  系统秘钥，各系统自行生成，系统密匙需要告知支付系统（最长64位）
	 * key 用户uid+订单号+价格+系统编码+加密密匙(字符串加密顺序要一致) 用SHA256方式加密后生成key传递给支付平台
	 * 支付平台将进行参数验证，验证成功后 将返回根据参数生成的支付地址
	 * 实例程序如下
	 */
	public function pay($uid,$orderNumber,$price,$goodsName){
		$config  = Yaf_Registry::get('config');
		$price=sprintf("%.2f", $price);
		$platform="webAlipay";
		$bankCode="0000";
		$subject = urlencode($goodsName);
		$sysCode=$config->pay->mark;
		$merchantUrl = $config->heimi->appstore->host."/api/pay/index?ccid=".$ccid;
		$sysKey="aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d";
        $url     = $config->pay->host;
		$key =  hash('sha256',$uid.$orderNumber.$price.$sysCode.$sysKey);
		$parameters="?platform=".$platform."&bankCode=0000&orderNumber=".$orderNumber."&subject=".$subject."&price=". $price."&sysCode=".$sysCode."&uid=".$uid."&key=".$key."&merchantUrl=".$merchantUrl."";
		//调用系统
		$result = file_get_contents($url.$parameters);
		$data=json_decode($result,true);
		if(isset($data['status'])&&$data['status']==1){
			return $data['data'];
		}
		throw new Exception('支付异常，请重新支付。');
	}
	

}
